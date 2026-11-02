<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class FreedomFingerEmployeeSynchronizer
{
    public function sync(): array
    {
        $baseUrl = rtrim((string) config('services.freedom_finger.url'), '/');
        $token = (string) config('services.freedom_finger.token', '');

        if ($baseUrl === '' || $token === '') {
            return ['success' => false, 'message' => 'URL dan token Freedom Finger wajib dikonfigurasi.', 'synced' => 0];
        }

        try {
            $response = Http::withToken($token)->acceptJson()->asJson()
                ->connectTimeout(3)->timeout(20)
                ->post($baseUrl . '/api/get_users', (object) []);

            if (! $response->successful()) {
                return ['success' => false, 'message' => 'Freedom Finger merespons HTTP ' . $response->status() . '.', 'synced' => 0];
            }

            $result = $response->json();
            if (! is_array($result) || ($result['success'] ?? false) !== true || ! is_array($result['data'] ?? null)) {
                return ['success' => false, 'message' => 'Format daftar pengguna dari Freedom Finger tidak valid.', 'synced' => 0];
            }

            $synced = 0;
            foreach ($result['data'] as $user) {
                if (! is_array($user) || blank($user['cloud_id'] ?? null) || blank($user['pin'] ?? null)) {
                    continue;
                }

                // Kolom data tidak disentuh agar cadangan biometrik yang sudah ada tetap utuh.
                DB::table('machine_users')->updateOrInsert(
                    ['cloud_id' => (string) $user['cloud_id'], 'pin' => (string) $user['pin']],
                    [
                        'name' => $user['name'] ?? null,
                        'privilege' => isset($user['privilege']) ? (string) $user['privilege'] : null,
                        'updated' => $user['updated'] ?? now(),
                    ],
                );
                $synced++;
            }

            return ['success' => true, 'message' => null, 'synced' => $synced];
        } catch (Throwable $exception) {
            report($exception);
            return ['success' => false, 'message' => 'Gagal mengambil daftar pengguna Freedom Finger.', 'synced' => 0];
        }
    }
}
