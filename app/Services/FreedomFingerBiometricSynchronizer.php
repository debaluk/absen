<?php

namespace App\\Services;

use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Http;
use Throwable;

class FreedomFingerBiometricSynchronizer
{
    /**
     * Memeriksa satu PIN per siklus agar antrean perintah mesin tetap terkendali.
     */
    public function syncNext(): array
    {
        $baseUrl = rtrim((string) config('services.freedom_finger.url'), '/');
        $token = (string) config('services.freedom_finger.token', '');

        if ($baseUrl === '' || $token === '') {
            return ['status' => 'unavailable', 'message' => 'URL dan token layanan mesin belum dikonfigurasi.'];
        }

        $user = DB::table('machine_users')
            ->where(function ($query) {
                $query->whereNull('biometric_checked_at')
                    ->orWhere('biometric_checked_at', '<=', now()->subDay());
            })
            ->orderByRaw('biometric_checked_at IS NULL DESC')
            ->orderBy('biometric_checked_at')
            ->first(['cloud_id', 'pin']);

        if (! $user) {
            return ['status' => 'idle'];
        }

        try {
            $commandResponse = Http::withToken($token)->acceptJson()->asJson()
                ->connectTimeout(3)->timeout(10)
                ->post($baseUrl . '/api/get_userinfo', [
                    'cloud_id' => $user->cloud_id,
                    'pin' => $user->pin,
                ]);

            if (! $commandResponse->successful()) {
                return $this->markUnavailable($user, 'Freedom Finger merespons HTTP ' . $commandResponse->status() . '.');
            }

            $command = $commandResponse->json();
            if (! is_array($command) || ($command['success'] ?? false) !== true || blank($command['trans_id'] ?? null)) {
                return $this->markUnavailable($user, 'Freedom Finger tidak memberikan ID perintah pemeriksaan pengguna.');
            }

            $deadline = microtime(true) + 150;

            while (microtime(true) < $deadline) {
                sleep(3);

                $resultResponse = Http::withToken($token)->acceptJson()->asJson()
                    ->connectTimeout(3)->timeout(10)
                    ->post($baseUrl . '/api/get_result', [
                        'cloud_id' => $user->cloud_id,
                        'trans_id' => $command['trans_id'],
                    ]);

                if (! $resultResponse->successful()) {
                    continue;
                }

                $result = $resultResponse->json();
                if (! is_array($result)) {
                    continue;
                }

                if (($result['status'] ?? null) === 'timeout') {
                    return $this->markUnavailable($user, 'Mesin tidak menjawab perintah pemeriksaan registrasi.');
                }

                if (($result['status'] ?? null) !== 'done') {
                    continue;
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
                        'biometric_checked_at' => now(),
                    ]);

                return ['status' => 'checked', 'cloud_id' => $user->cloud_id, 'pin' => $user->pin];
            }

            return $this->markUnavailable($user, 'Pemeriksaan registrasi melewati batas waktu tunggu.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->markUnavailable($user, 'Tidak dapat mengambil data registrasi dari mesin.');
        }
    }

    private function markUnavailable(object $user, string $message): array
    {
        DB::table('machine_users')
            ->where('cloud_id', $user->cloud_id)
            ->where('pin', $user->pin)
            ->update([
                'biometric_status' => 'unavailable',
                'biometric_checked_at' => now(),
            ]);

        return ['status' => 'unavailable', 'message' => $message];
    }
}
