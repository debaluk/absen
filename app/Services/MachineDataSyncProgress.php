<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class MachineDataSyncProgress
{
    public function refresh(string $cloudId): array
    {
        $counts = DB::table('machine_users')
            ->where('cloud_id', $cloudId)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN biometric_status = 'checked' THEN 1 ELSE 0 END) as checked")
            ->selectRaw("SUM(CASE WHEN biometric_status = 'unavailable' THEN 1 ELSE 0 END) as unavailable")
            ->first();

        $total = (int) ($counts->total ?? 0);
        $checked = (int) ($counts->checked ?? 0);
        $unavailable = (int) ($counts->unavailable ?? 0);
        $processed = $checked + $unavailable;
        $completed = $processed >= $total;

        DB::table('machine_data_syncs')->where('cloud_id', $cloudId)->update([
            'status' => $completed ? 'completed' : 'running',
            'total' => $total,
            'checked' => $checked,
            'unavailable' => $unavailable,
            'pending' => max(0, $total - $processed),
            'message' => $completed ? null : 'Sedang mengambil data pengguna dan memeriksa biometrik.',
            'finished_at' => $completed ? now() : null,
            'updated_at' => now(),
        ]);

        return [
            'total' => $total,
            'checked' => $checked,
            'unavailable' => $unavailable,
            'pending' => max(0, $total - $processed),
            'completed' => $completed,
        ];
    }

    public function fail(string $cloudId, string $message): void
    {
        DB::table('machine_data_syncs')->where('cloud_id', $cloudId)->update([
            'status' => 'failed',
            'message' => $message,
            'finished_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
