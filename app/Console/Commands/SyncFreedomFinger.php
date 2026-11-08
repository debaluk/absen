<?php

namespace App\Console\Commands;

use App\Services\FreedomFingerAttendanceLogSynchronizer;
use App\Services\FreedomFingerBiometricSynchronizer;
use App\Services\FreedomFingerDeviceSynchronizer;
use App\Services\FreedomFingerEmployeeSynchronizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncFreedomFinger extends Command
{
    protected $signature = 'freedom-finger:sync {--days=7 : Rentang hari untuk mengambil ulang log absensi} {--manual : Jalankan tanpa memeriksa sakelar sinkronisasi otomatis}';

    protected $description = 'Sinkronisasi mesin, pengguna, dan log dari Freedom Finger';

    public function handle(
        FreedomFingerDeviceSynchronizer $devices,
        FreedomFingerEmployeeSynchronizer $users,
        FreedomFingerBiometricSynchronizer $biometrics,
        FreedomFingerAttendanceLogSynchronizer $logs,
    ): int {
        if (! $this->option('manual')) {
            $enabled = DB::table('application_settings')
                ->where('key', 'freedom_finger_auto_sync')
                ->value('value') === '1';

            if (! $enabled) {
                $this->comment('Sinkronisasi otomatis nonaktif; proses dilewati.');
                return self::SUCCESS;
            }
        }

        $deviceResult = $devices->sync();
        if (! $deviceResult['success']) {
            $this->error('Mesin: ' . $deviceResult['message']);
            return self::FAILURE;
        }

        $userResult = $users->sync();
        if (! $userResult['success']) {
            $this->error('Pengguna: ' . $userResult['message']);
            return self::FAILURE;
        }

        $biometricResult = $biometrics->syncNext();

        $logResult = $logs->sync(max(1, (int) $this->option('days')));
        if (! $logResult['success']) {
            $this->error('Log: ' . $logResult['message']);
            return self::FAILURE;
        }

        $this->info('Sinkronisasi selesai: ' . $deviceResult['synced'] . ' mesin, '
            . $userResult['synced'] . ' pengguna, ' . $logResult['synced'] . ' log diproses, '
            . ($logResult['pulled'] ?? 0) . ' log baru ditarik dari mesin. '
            . 'Status registrasi mesin: ' . match ($biometricResult['status'] ?? 'idle') {
                'checked' => '1 PIN diperbarui',
                'pending' => 'pemeriksaan menunggu mesin',
                'unavailable' => 'belum diketahui',
                default => 'tidak ada pemeriksaan yang jatuh tempo',
            } . '.');

        return self::SUCCESS;
    }
}
