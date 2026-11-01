<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use Throwable;

class FreedomFingerDeviceSynchronizer
{
    public function sync(): array
    {
        $baseUrl = rtrim((string) config('services.freedom_finger.url', 'http://103.119.237.50:8013'), '/');

        try {
            $response = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(8)
                ->get($baseUrl . '/status.json');

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Freedom Finger merespons HTTP ' . $response->status() . '.',
                    'engine' => null,
                    'synced' => 0,
                ];
            }

            $engine = $response->json();

            if (! is_array($engine) || ! is_array($engine['devices'] ?? null)) {
                return [
                    'success' => false,
                    'message' => 'Format data perangkat dari Freedom Finger tidak valid.',
                    'engine' => null,
                    'synced' => 0,
                ];
            }

            $now = now();
            $rows = [];

            foreach ($engine['devices'] as $device) {
                if (! is_array($device) || blank($device['cloud_id'] ?? null)) {
                    continue;
                }

                $rows[] = [
                    'cloud_id' => (string) $device['cloud_id'],
                    'device_name' => $device['device_name'] ?? null,
                    'ip_address' => $device['ip'] ?? null,
                    'is_connected' => (bool) ($device['connected'] ?? false),
                    'last_activity' => filled($device['last_activity'] ?? null)
                        ? Carbon::parse($device['last_activity'])
                        : null,
                    'last_synced_at' => $now,
                    'raw_data' => json_encode($device, JSON_THROW_ON_ERROR),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('freedom_finger_devices')->upsert(
                    $chunk,
                    ['cloud_id'],
                    [
                        'device_name',
                        'ip_address',
                        'is_connected',
                        'last_activity',
                        'last_synced_at',
                        'raw_data',
                        'updated_at',
                    ],
                );
            }

            return [
                'success' => true,
                'message' => null,
                'engine' => $engine,
                'synced' => count($rows),
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'success' => false,
                'message' => 'Tidak dapat menyinkronkan perangkat dari Freedom Finger.',
                'engine' => null,
                'synced' => 0,
            ];
        }
    }
}
