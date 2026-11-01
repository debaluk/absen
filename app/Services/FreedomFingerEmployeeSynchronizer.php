<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class FreedomFingerEmployeeSynchronizer
{
    public function sync(): array
    {
        $baseUrl = rtrim((string) config('services.freedom_finger.url', 'http://103.119.237.50:8013'), '/');
        $token = (string) config('services.freedom_finger.token', '');

        if ($token === '') {
            return [
                'success' => false,
                'message' => 'Token API Freedom Finger belum dikonfigurasi di Laravel.',
                'synced' => 0,
            ];
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->asJson()
                ->connectTimeout(3)
                ->timeout(15)
                ->post($baseUrl . '/api/get_users', (object) []);

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Freedom Finger merespons HTTP ' . $response->status() . '.',
                    'synced' => 0,
                ];
            }

            $result = $response->json();

            if (! is_array($result) || ($result['success'] ?? false) !== true || ! is_array($result['data'] ?? null)) {
                return [
                    'success' => false,
                    'message' => 'Format data karyawan dari Freedom Finger tidak valid.',
                    'synced' => 0,
                ];
            }

            $now = now();
            $rows = [];

            foreach ($result['data'] as $employee) {
                if (! is_array($employee) || blank($employee['pin'] ?? null) || blank($employee['cloud_id'] ?? null)) {
                    continue;
                }

                $rows[] = [
                    'cloud_id' => (string) $employee['cloud_id'],
                    'pin' => (string) $employee['pin'],
                    'name' => $employee['name'] ?? null,
                    'last_synced_at' => $now,
                    'raw_data' => json_encode($employee, JSON_THROW_ON_ERROR),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('freedom_finger_employees')->upsert(
                    $chunk,
                    ['cloud_id', 'pin'],
                    ['name', 'last_synced_at', 'raw_data', 'updated_at'],
                );
            }

            return [
                'success' => true,
                'message' => null,
                'synced' => count($rows),
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'success' => false,
                'message' => 'Tidak dapat mengambil data karyawan dari Freedom Finger.',
                'synced' => 0,
            ];
        }
    }
}
