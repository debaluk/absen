<?php

namespace App\Console\Commands;

use App\Services\FreedomFingerDeviceSynchronizer;
use Illuminate\Console\Command;

class SyncFreedomFingerDevices extends Command
{
    protected $signature = 'freedom-finger:sync-devices';

    protected $description = 'Sinkronisasi data mesin Freedom Finger ke database Laravel';

    public function handle(FreedomFingerDeviceSynchronizer $synchronizer): int
    {
        $result = $synchronizer->sync();

        if (! $result['success']) {
            $this->error($result['message']);

            return self::FAILURE;
        }

        $this->info('Sinkronisasi berhasil. ' . $result['synced'] . ' mesin diproses.');

        return self::SUCCESS;
    }
}
