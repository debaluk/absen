<?php

namespace App\\Services;

use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Http;
use Throwable;

class FreedomFingerBiometricSynchronizer
{
    /**
     * Menyiapkan pemeriksaan hanya untuk PIN yang cache mesinnya berubah atau belum pernah diperiksa.
     */
    public function requestForMachine(string $cloudId): array
    {
        $baseUrl = rtrim((string) config('services.freedom_finger.url'), '/');
        $token = (string) config('services.freedom_finger.token', '');

        if ($baseUrl === '' || $token === '') {
            return ['success' => false, 'message' => 'URL dan token layanan mesin belum dikonfigurasi.'];
        }

        if (! DB::table('devices')->where('cloud_id', $cloudId)->exists()) {
            return ['success' => false, 'message' => 'Mesin tidak ditemukan.'];
        }

        $userCount = DB::table('machine_users')->where('cloud_id', $cloudId)->count();

        if ($userCount === 0) {
            return ['success' => false, 'message' => 'Belum ada data pengguna untuk mesin ini.'];
        }

        // Cache yang belum memiliki patokan, berubah, atau gagal diperiksa perlu diminta ulang.
        $queued = DB::table('machine_users')
            ->where('cloud_id', $cloudId)
            ->whereNull('biometric_trans_id')
            ->where(function ($query) {
                $query->whereNull('biometric_source_updated_at')
                    ->orWhereNull('updated')
                    ->orWhereColumn('updated', '!=', 'biometric_source_updated_at')
                    ->orWhereIn('biometric_status', ['unknown', 'unavailable']);
            })
            ->update([
                'biometric_status' => 'unknown',
                'biometric_checked_at' => null,
            ]);

        $pending = DB::table('machine_users')
            ->where('cloud_id', $cloudId)
            ->whereNotNull('biometric_trans_id')
            ->count();

        $started = $this->syncNext($cloudId);

        return [
            'success' => true,
            'queued' => $queued,
            'pending' => $pending,
            'started_status' => $started['status'] ?? 'idle',
        ];
    }

    /**
     * Menyimpan timestamp cache terbaru setelah seluruh pemeriksaan PIN selesai.
     * get_userinfo juga memperbarui timestamp cache, sehingga patokan harus diambil ulang
     * setelah batch selesai, bukan memakai timestamp sebelum pemeriksaan.
     */
    public function finalizeMachine(string $cloudId): bool
    {
        $baseUrl = rtrim((string) config('services.freedom_finger.url'), '/');
        $token = (string) config('services.freedom_finger.token', '');

        if ($baseUrl === '' || $token === '') {
            return false;
        }

        try {
            $response = Http::withToken($token)->acceptJson()->asJson()
                ->connectTimeout(3)->timeout(20)
                ->post($baseUrl . '/api/get_users', ['cloud_id' => $cloudId]);

            if (! $response->successful()) {
                return false;
            }

            $result = $response->json();
            if (! is_array($result) || ($result['success'] ?? false) !== true || ! is_array($result['data'] ?? null)) {
                return false;
            }

            DB::transaction(function () use ($result, $cloudId) {
                foreach ($result['data'] as $user) {
                    if (! is_array($user)
                        || (string) ($user['cloud_id'] ?? '') !== $cloudId
                        || blank($user['pin'] ?? null)
                        || blank($user['updated'] ?? null)) {
                        continue;
                    }

                    DB::table('machine_users')
                        ->where('cloud_id', $cloudId)
                        ->where('pin', (string) $user['pin'])
                        ->where('biometric_status', 'checked')
                        ->update([
                            'updated' => $user['updated'],
                            'biometric_source_updated_at' => $user['updated'],
                        ]);
                }
            });

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    public function syncNext(?string $cloudId = null): array
    {
        $baseUrl = rtrim((string) config('services.freedom_finger.url'), '/');
        $token = (string) config('services.freedom_finger.token', '');

        if ($baseUrl === '' || $token === '') {
            return ['status' => 'unavailable', 'message' => 'URL dan token layanan mesin belum dikonfigurasi.'];
        }

        $pendingUser = DB::table('machine_users')
            ->when($cloudId !== null, fn ($query) => $query->where('cloud_id', $cloudId))
            ->whereNotNull('biometric_trans_id')
            ->orderBy('biometric_requested_at')
            ->first(['cloud_id', 'pin', 'biometric_trans_id', 'biometric_requested_at']);

        if ($pendingUser) {
            return $this->pollPendingUser($baseUrl, $token, $pendingUser);
        }

        $user = DB::table('machine_users')
            ->when($cloudId !== null, fn ($query) => $query->where('cloud_id', $cloudId))
            ->where('biometric_status', 'unknown')
            ->orderBy('pin')
            ->first(['cloud_id', 'pin']);

        if (! $user) {
            return ['status' => 'idle'];
        }

        try {
            $response = Http::withToken($token)->acceptJson()->asJson()
                ->connectTimeout(3)->timeout(10)
                ->post($baseUrl . '/api/get_userinfo', [
                    'cloud_id' => $user->cloud_id,
                    'pin' => $user->pin,
                ]);

            if (! $response->successful()) {
                return $this->markUnavailable($user, 'Freedom Finger merespons HTTP ' . $response->status() . '.');
            }

            $command = $response->json();
            if (! is_array($command) || ($command['success'] ?? false) !== true || blank($command['trans_id'] ?? null)) {
                return $this->markUnavailable($user, 'Freedom Finger tidak memberikan ID perintah pemeriksaan pengguna.');
            }

            DB::table('machine_users')
                ->where('cloud_id', $user->cloud_id)
                ->where('pin', $user->pin)
                ->update([
                    'biometric_status' => 'pending',
                    'biometric_trans_id' => (string) $command['trans_id'],
                    'biometric_requested_at' => now(),
                ]);

            return ['status' => 'pending', 'cloud_id' => $user->cloud_id, 'pin' => $user->pin];
        } catch (Throwable $exception) {
            report($exception);

            return $this->markUnavailable($user, 'Tidak dapat meminta data registrasi dari mesin.');
        }
    }

    private function pollPendingUser(string $baseUrl, string $token, object $user): array
    {
        if ($user->biometric_requested_at
            && now()->subMinutes(10)->greaterThanOrEqualTo($user->biometric_requested_at)) {
            return $this->markUnavailable($user, 'Mesin tidak menyelesaikan pemeriksaan registrasi dalam batas waktu.');
        }

        try {
            $response = Http::withToken($token)->acceptJson()->asJson()
                ->connectTimeout(3)->timeout(10)
                ->post($baseUrl . '/api/get_result', [
                    'cloud_id' => $user->cloud_id,
                    'trans_id' => $user->biometric_trans_id,
                ]);

            if (! $response->successful()) {
                return ['status' => 'pending'];
            }

            $result = $response->json();
            if (! is_array($result) || ($result['success'] ?? false) !== true) {
                return ['status' => 'pending'];
            }

            if (($result['status'] ?? null) === 'timeout') {
                return $this->markUnavailable($user, 'Mesin tidak menjawab perintah pemeriksaan registrasi.');
            }

            if (($result['status'] ?? null) !== 'done') {
                return ['status' => 'pending'];
            }

            $data = $result['data'] ?? null;
            if (($result['result_code'] ?? null) !== 'OK'
                || ! is_array($data)
                || ! is_numeric($data['finger'] ?? null)) {
                return $this->markUnavailable($user, 'Data registrasi sidik jari dari mesin tidak valid.');
            }

            $fingerCount = (int) $data['finger'];
            if ($fingerCount < 0 || $fingerCount > 255) {
                return $this->markUnavailable($user, 'Jumlah registrasi sidik jari dari mesin tidak valid.');
            }

            DB::table('machine_users')
                ->where('cloud_id', $user->cloud_id)
                ->where('pin', $user->pin)
                ->update([
                    'finger_count' => $fingerCount,
                    'biometric_status' => 'checked',
                    'biometric_trans_id' => null,
                    'biometric_requested_at' => null,
                    'biometric_checked_at' => now(),
                ]);

            return ['status' => 'checked', 'cloud_id' => $user->cloud_id, 'pin' => $user->pin];
        } catch (Throwable $exception) {
            report($exception);

            return ['status' => 'pending'];
        }
    }

    private function markUnavailable(object $user, string $message): array
    {
        DB::table('machine_users')
            ->where('cloud_id', $user->cloud_id)
            ->where('pin', $user->pin)
            ->update([
                'biometric_status' => 'unavailable',
                'biometric_trans_id' => null,
                'biometric_requested_at' => null,
                'biometric_checked_at' => now(),
            ]);

        return ['status' => 'unavailable', 'message' => $message];
    }
}
