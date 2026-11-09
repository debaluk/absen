<?php

namespace App\Jobs;

use App\Services\FreedomFingerBiometricSynchronizer;
use App\Services\MachineDataSyncProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class ContinueMachineDataSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60;

    public function __construct(public string $cloudId)
    {
    }

    public function handle(
        FreedomFingerBiometricSynchronizer $biometrics,
        MachineDataSyncProgress $progress,
    ): void {
        $sync = DB::table('machine_data_syncs')->where('cloud_id', $this->cloudId)->first();

        if (! $sync || $sync->status !== 'running') {
            return;
        }

        try {
            $result = $biometrics->syncNext($this->cloudId);

            // PIN selesai, langsung mulai PIN berikutnya pada siklus Job yang sama.
            if (in_array($result['status'] ?? null, ['checked', 'unavailable'], true)) {
                $result = $biometrics->syncNext($this->cloudId);
            }

            $state = $progress->refresh($this->cloudId);

            if ($state['completed']) {
                return;
            }

            if (($result['status'] ?? null) === 'idle') {
                $progress->fail(
                    $this->cloudId,
                    'Proses berhenti sebelum seluruh pengguna selesai diperiksa.',
                );
                return;
            }

            self::dispatch($this->cloudId)->delay(now()->addSeconds(2));
        } catch (Throwable $exception) {
            report($exception);
            $progress->fail($this->cloudId, 'Pemeriksaan biometrik gagal. Periksa koneksi Freedom Finger dan log aplikasi.');
        }
    }

    public function failed(Throwable $exception): void
    {
        app(MachineDataSyncProgress::class)->fail(
            $this->cloudId,
            'Proses pemeriksaan biometrik gagal dijalankan.',
        );
    }
}
