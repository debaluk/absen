<?php

namespace App\Jobs;

use App\Services\FreedomFingerBiometricSynchronizer;
use App\Services\FreedomFingerEmployeeSynchronizer;
use App\Services\MachineDataSyncProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class StartMachineDataSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60;

    public function __construct(public string $cloudId)
    {
    }

    public function handle(
        FreedomFingerEmployeeSynchronizer $employees,
        FreedomFingerBiometricSynchronizer $biometrics,
        MachineDataSyncProgress $progress,
    ): void {
        $sync = DB::table('machine_data_syncs')->where('cloud_id', $this->cloudId)->first();

        if (! $sync || ! in_array($sync->status, ['queued', 'running'], true)) {
            return;
        }

        try {
            DB::table('machine_data_syncs')->where('cloud_id', $this->cloudId)->update([
                'status' => 'running',
                'message' => 'Mengambil daftar pengguna dari mesin.',
                'started_at' => $sync->started_at ?: now(),
                'updated_at' => now(),
            ]);

            $result = $employees->syncMachineUsers($this->cloudId);

            if (! $result['success']) {
                throw new \RuntimeException($result['message'] ?? 'Gagal mengambil daftar pengguna mesin.');
            }

            if (DB::table('machine_users')->where('cloud_id', $this->cloudId)->count() === 0) {
                $progress->refresh($this->cloudId);
                DB::table('machine_data_syncs')->where('cloud_id', $this->cloudId)->update([
                    'message' => 'Tidak ada pengguna yang terdaftar pada mesin ini.',
                    'updated_at' => now(),
                ]);
                return;
            }

            $result = $biometrics->requestForMachine($this->cloudId);

            if (! $result['success']) {
                throw new \RuntimeException($result['message'] ?? 'Gagal memulai pemeriksaan biometrik.');
            }

            $state = $progress->refresh($this->cloudId);

            if (! $state['completed']) {
                ContinueMachineDataSync::dispatch($this->cloudId)->delay(now()->addSeconds(2));
            }
        } catch (Throwable $exception) {
            report($exception);
            $progress->fail($this->cloudId, 'Pengambilan data mesin gagal. Periksa koneksi Freedom Finger dan log aplikasi.');
        }
    }

    public function failed(Throwable $exception): void
    {
        app(MachineDataSyncProgress::class)->fail(
            $this->cloudId,
            'Proses pengambilan data mesin gagal dijalankan.',
        );
    }
}
