<?php

namespace App\Console\Commands;

use App\Services\FreedomFingerAttendanceLogSynchronizer;
use App\Services\FreedomFingerDeviceSynchronizer;
use App\Services\FreedomFingerEmployeeSynchronizer;
use Illuminate\Console\Command;

class SyncFreedomFinger extends Command
{
    protected $signature = 'freedom-finger:sync {--days=7 : Rentang hari untuk mengambil ulang log absensi}';

    protected $description = 'Sinkronisasi mesin, pengguna, dan log dari Freedom Finger';

    public function handle(
        FreedomFingerDeviceSynchronizer $devices,
        FreedomFingerEmployeeSynchronizer $users,
        FreedomFingerAttendanceLogSynchronizer $logs,
    ): int {
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

        $logResult = $logs->sync(max(1, (int) $this->option('days')));
        if (! $logResult['success']) {
            $this->error('Log: ' . $logResult['message']);
            return self::FAILURE;
        }

        $this->info('Sinkronisasi selesai: ' . $deviceResult['synced'] . ' mesin, '
            . $userResult['synced'] . ' pengguna, ' . $logResult['synced'] . ' log diproses.');

        return self::SUCCESS;
    }
}
