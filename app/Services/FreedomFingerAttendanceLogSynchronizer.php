<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class FreedomFingerAttendanceLogSynchronizer
{
    public function sync(int $days = 7): array
    {
        $baseUrl = rtrim((string) config('services.freedom_finger.url'), '/');
        $token = (string) config('services.freedom_finger.token', '');

        if ($baseUrl === '' || $token === '') {
            return ['success' => false, 'message' => 'URL dan token Freedom Finger wajib dikonfigurasi.', 'synced' => 0];
        }

        try {
            $devices = DB::table('devices')->pluck('cloud_id');
            if ($devices->isEmpty()) {
                return ['success' => true, 'message' => null, 'synced' => 0];
            }

            $startDate = now()->subDays(max(1, $days) - 1)->toDateString();
            $endDate = now()->toDateString();
            $synced = 0;

            foreach ($devices as $cloudId) {
                $response = Http::withToken($token)->acceptJson()->asJson()
                    ->connectTimeout(3)->timeout(30)
                    ->post($baseUrl . '/api/get_attlog', [
                        'cloud_id' => $cloudId,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                    ]);

                if (! $response->successful()) {
                    throw new \RuntimeException('Gagal mengambil log mesin ' . $cloudId . ' (HTTP ' . $response->status() . ').');
                }

                $result = $response->json();
                if (! is_array($result) || ($result['success'] ?? false) !== true || ! is_array($result['data'] ?? null)) {
                    throw new \RuntimeException('Format log mesin ' . $cloudId . ' tidak valid.');
                }

                foreach ($result['data'] as $log) {
                    if (! is_array($log) || blank($log['pin'] ?? null) || blank($log['scan_date'] ?? null)) {
                        continue;
                    }

                    $scanDate = Carbon::parse($log['scan_date'])->format('Y-m-d H:i:s');
                    DB::table('logs')->updateOrInsert(
                        [
                            'cloud_id' => (string) $cloudId,
                            'pin' => (string) $log['pin'],
                            'scan_date' => $scanDate,
                        ],
                        [
                            'verify' => $log['verify'] ?? null,
                            'status_scan' => $log['status_scan'] ?? null,
                            'io_mode' => $log['io_mode'] ?? null,
                            'verify_mode' => $log['verify_mode'] ?? null,
                            'photo' => $log['photo'] ?? null,
                        ],
                    );
                    $synced++;
                }
            }

            return ['success' => true, 'message' => null, 'synced' => $synced];
        } catch (Throwable $exception) {
            report($exception);
            return ['success' => false, 'message' => $exception->getMessage(), 'synced' => 0];
        }
    }
}
