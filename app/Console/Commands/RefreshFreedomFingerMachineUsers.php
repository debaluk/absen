<?php

namespace App\Console\Commands;

use App\Services\FreedomFingerMachineUserRefreshService;
use Illuminate\Console\Command;

class RefreshFreedomFingerMachineUsers extends Command
{
    protected $signature = 'freedom-finger:refresh-machine-users';

    protected $description = 'Memproses pengambilan data pengguna mesin yang diminta dari menu Mesin';

    public function handle(FreedomFingerMachineUserRefreshService $refresh): int
    {
        $result = $refresh->processNext();

        if (($result['status'] ?? 'idle') === 'failed') {
            $this->warn($result['message'] ?? 'Pengambilan data mesin gagal.');
        } elseif (($result['status'] ?? 'idle') !== 'idle') {
            $this->info('Proses ambil data mesin: ' . ($result['status'] ?? 'unknown') . '.');
        }

        return self::SUCCESS;
    }
}
