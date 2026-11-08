<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class FreedomFingerMachineUserRefreshService
{
    public function start(string $cloudId): array
    {
        $baseUrl = rtrim((string) config('services.freedom_finger.url'), '/');
        $token = (string) config('services.freedom_finger.token', '');

        if ($baseUrl === '' || $token === '') {
            return ['success' => false, 'message' => 'URL dan token Freedom Finger belum dikonfigurasi.'];
        }

        if (! DB::table('devices')->where('cloud_id', $cloudId)->exists()) {
            return ['success' => false, 'message' => 'Mesin tidak ditemukan.'];
        }

        $active = DB::table('machine_user_refreshes')
            ->where('cloud_id', $cloudId)
            ->whereIn('status', ['waiting_pin_list', 'processing'])
            ->exists();

        if ($active) {
            return ['success' => false, 'message' => 'Pengambilan data untuk mesin ini masih berjalan.'];
        }

        if (DB::table('machine_users')->where('cloud_id', $cloudId)->whereNotNull('biometric_trans_id')->exists()) {
            return ['success' => false, 'message' => 'Masih ada pemeriksaan biometrik yang berjalan untuk mesin ini. Coba lagi setelah selesai.'];
        }

        try {
            $response = Http::withToken($token)->acceptJson()->asJson()
                ->connectTimeout(3)->timeout(10)
                ->post($baseUrl . '/api/get_all_pin', ['cloud_id' => $cloudId]);

            if (! $response->successful()) {
                return ['success' => false, 'message' => 'Freedom Finger merespons HTTP ' . $response->status() . '.'];
            }

            $command = $response->json();
            if (! is_array($command) || ($command['success'] ?? false) !== true || blank($command['trans_id'] ?? null)) {
                return ['success' => false, 'message' => data_get($command, 'message', 'Freedom Finger tidak memberikan ID perintah pengambilan PIN.')];
            }

            DB::table('machine_user_refreshes')->updateOrInsert(
                ['cloud_id' => $cloudId],
                [
                    'trans_id' => (string) $command['trans_id'],
                    'status' => 'waiting_pin_list',
                    'pin_list' => null,
                    'next_pin_index' => 0,
                    'current_pin' => null,
                    'requested_at' => now(),
                    'total_pins' => 0,
                    'processed_pins' => 0,
                    'error_count' => 0,
                    'message' => null,
                    'started_at' => now(),
                    'finished_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );

            return ['success' => true, 'message' => 'Permintaan daftar PIN dikirim ke mesin.'];
        } catch (Throwable $exception) {
            report($exception);

            return ['success' => false, 'message' => 'Tidak dapat mengirim permintaan ke Freedom Finger.'];
        }
    }

    /**
     * Memproses satu tahap per siklus agar tidak menahan scheduler saat mesin belum menjawab.
     */
    public function processNext(): array
    {
        $refresh = DB::table('machine_user_refreshes')
            ->whereIn('status', ['waiting_pin_list', 'processing'])
            ->orderBy('updated_at')
            ->first();

        if (! $refresh) {
            return ['status' => 'idle'];
        }

        $baseUrl = rtrim((string) config('services.freedom_finger.url'), '/');
        $token = (string) config('services.freedom_finger.token', '');

        if ($baseUrl === '' || $token === '') {
            return ['status' => 'unavailable'];
        }

        if ($refresh->requested_at && now()->subMinutes(10)->greaterThanOrEqualTo($refresh->requested_at)) {
            return $this->failRefresh($refresh, 'Mesin tidak menyelesaikan permintaan dalam batas waktu.');
        }

        if ($refresh->status === 'waiting_pin_list') {
            return $this->processPinList($baseUrl, $token, $refresh);
        }

        return $this->processUserInfo($baseUrl, $token, $refresh);
    }

    private function processPinList(string $baseUrl, string $token, object $refresh): array
    {
        $result = $this->getResult($baseUrl, $token, $refresh->cloud_id, $refresh->trans_id);

        if ($result === null || in_array($result['status'] ?? null, ['pending', 'sent'], true)) {
            $this->touchRefresh($refresh->cloud_id);

            return ['status' => 'pending', 'cloud_id' => $refresh->cloud_id];
        }

        if (($result['status'] ?? null) === 'timeout') {
            return $this->failRefresh($refresh, 'Mesin tidak menjawab permintaan daftar PIN.');
        }

        $data = $result['data'] ?? null;
        $pins = is_array($data) && is_array($data['pin_arr'] ?? null) ? $data['pin_arr'] : null;

        if (($result['success'] ?? false) !== true || ($result['result_code'] ?? null) !== 'OK' || $pins === null) {
            return $this->failRefresh($refresh, 'Daftar PIN yang diterima dari mesin tidak valid.');
        }

        $pins = array_values(array_unique(array_filter(
            array_map(fn ($pin) => trim((string) $pin), $pins),
            fn ($pin) => $pin !== '',
        )));

        DB::transaction(function () use ($refresh, $pins) {
            $now = now();

            foreach ($pins as $pin) {
                DB::table('machine_users')->insertOrIgnore([
                    'cloud_id' => $refresh->cloud_id,
                    'pin' => $pin,
                ]);
            }

            DB::table('machine_user_refreshes')->where('cloud_id', $refresh->cloud_id)->update([
                'trans_id' => null,
                'status' => $pins === [] ? 'completed' : 'processing',
                'pin_list' => json_encode($pins, JSON_THROW_ON_ERROR),
                'next_pin_index' => 0,
                'current_pin' => null,
                'requested_at' => null,
                'total_pins' => count($pins),
                'processed_pins' => 0,
                'error_count' => 0,
                'message' => $pins === [] ? 'Mesin tidak memiliki PIN pengguna.' : null,
                'finished_at' => $pins === [] ? now() : null,
                'updated_at' => now(),
            ]);
        });

        return ['status' => $pins === [] ? 'completed' : 'processing', 'cloud_id' => $refresh->cloud_id, 'total' => count($pins)];
    }

    private function processUserInfo(string $baseUrl, string $token, object $refresh): array
    {
        if (filled($refresh->trans_id) && filled($refresh->current_pin)) {
            $result = $this->getResult($baseUrl, $token, $refresh->cloud_id, $refresh->trans_id);

            if ($result === null || in_array($result['status'] ?? null, ['pending', 'sent'], true)) {
                $this->touchRefresh($refresh->cloud_id);

                return ['status' => 'pending', 'cloud_id' => $refresh->cloud_id, 'pin' => $refresh->current_pin];
            }

            $pin = (string) $refresh->current_pin;
            $valid = ($result['success'] ?? false) === true
                && ($result['status'] ?? null) === 'done'
                && ($result['result_code'] ?? null) === 'OK'
                && is_array($result['data'] ?? null)
                && (string) ($result['data']['pin'] ?? '') === $pin
                && is_numeric($result['data']['finger'] ?? null)
                && (int) $result['data']['finger'] >= 0
                && (int) $result['data']['finger'] <= 255;

            if ($valid) {
                $data = $result['data'];

                DB::table('machine_users')->where('cloud_id', $refresh->cloud_id)->where('pin', $pin)->update([
                    'name' => filled($data['name'] ?? null) ? mb_substr((string) $data['name'], 0, 100) : null,
                    'privilege' => isset($data['privilege']) ? (string) $data['privilege'] : null,
                    'updated' => now(),
                    'finger_count' => (int) $data['finger'],
                    'biometric_status' => 'checked',
                    'biometric_trans_id' => null,
                    'biometric_requested_at' => null,
                    'biometric_checked_at' => now(),
                ]);
            } else {
                $this->markUserUnavailable($refresh->cloud_id, $pin);
            }

            return $this->finishPin($refresh, ! $valid);
        }

        $pins = json_decode((string) $refresh->pin_list, true);
        $pins = is_array($pins) ? $pins : [];
        $index = (int) $refresh->next_pin_index;

        if (! isset($pins[$index])) {
            DB::table('machine_user_refreshes')->where('cloud_id', $refresh->cloud_id)->update([
                'status' => (int) $refresh->error_count > 0 ? 'completed_with_errors' : 'completed',
                'message' => (int) $refresh->error_count > 0 ? 'Sebagian PIN tidak berhasil diperbarui.' : 'Seluruh data PIN berhasil diperbarui.',
                'finished_at' => now(),
                'updated_at' => now(),
            ]);

            return ['status' => 'completed', 'cloud_id' => $refresh->cloud_id];
        }

        $pin = (string) $pins[$index];

        try {
            $response = Http::withToken($token)->acceptJson()->asJson()
                ->connectTimeout(3)->timeout(10)
                ->post($baseUrl . '/api/get_userinfo', ['cloud_id' => $refresh->cloud_id, 'pin' => $pin]);

            $command = $response->json();

            if (! $response->successful() || ! is_array($command) || ($command['success'] ?? false) !== true || blank($command['trans_id'] ?? null)) {
                $this->markUserUnavailable($refresh->cloud_id, $pin);

                DB::table('machine_user_refreshes')->where('cloud_id', $refresh->cloud_id)->update([
                    'next_pin_index' => $index + 1,
                    'processed_pins' => DB::raw('processed_pins + 1'),
                    'error_count' => DB::raw('error_count + 1'),
                    'message' => 'Sebagian PIN tidak berhasil diminta dari mesin.',
                    'updated_at' => now(),
                ]);

                return ['status' => 'processing', 'cloud_id' => $refresh->cloud_id, 'pin' => $pin];
            }

            DB::table('machine_user_refreshes')->where('cloud_id', $refresh->cloud_id)->update([
                'trans_id' => (string) $command['trans_id'],
                'current_pin' => $pin,
                'next_pin_index' => $index + 1,
                'requested_at' => now(),
                'updated_at' => now(),
            ]);

            return ['status' => 'pending', 'cloud_id' => $refresh->cloud_id, 'pin' => $pin];
        } catch (Throwable $exception) {
            report($exception);
            $this->markUserUnavailable($refresh->cloud_id, $pin);

            DB::table('machine_user_refreshes')->where('cloud_id', $refresh->cloud_id)->update([
                'next_pin_index' => $index + 1,
                'processed_pins' => DB::raw('processed_pins + 1'),
                'error_count' => DB::raw('error_count + 1'),
                'message' => 'Sebagian PIN tidak berhasil diminta dari mesin.',
                'updated_at' => now(),
            ]);

            return ['status' => 'processing', 'cloud_id' => $refresh->cloud_id, 'pin' => $pin];
        }
    }

    private function finishPin(object $refresh, bool $hasError): array
    {
        $processed = (int) $refresh->processed_pins + 1;
        $errors = (int) $refresh->error_count + ($hasError ? 1 : 0);
        $pins = json_decode((string) $refresh->pin_list, true);
        $total = is_array($pins) ? count($pins) : (int) $refresh->total_pins;
        $done = (int) $refresh->next_pin_index >= $total;

        DB::table('machine_user_refreshes')->where('cloud_id', $refresh->cloud_id)->update([
            'trans_id' => null,
            'current_pin' => null,
            'requested_at' => null,
            'processed_pins' => $processed,
            'error_count' => $errors,
            'status' => $done ? ($errors > 0 ? 'completed_with_errors' : 'completed') : 'processing',
            'message' => $done ? ($errors > 0 ? 'Sebagian PIN tidak berhasil diperbarui.' : 'Seluruh data PIN berhasil diperbarui.') : null,
            'finished_at' => $done ? now() : null,
            'updated_at' => now(),
        ]);

        return ['status' => $done ? 'completed' : 'processing', 'cloud_id' => $refresh->cloud_id, 'processed' => $processed, 'total' => $total];
    }


    private function touchRefresh(string $cloudId): void
    {
        DB::table('machine_user_refreshes')
            ->where('cloud_id', $cloudId)
            ->update(['updated_at' => now()]);
    }

    private function getResult(string $baseUrl, string $token, string $cloudId, ?string $transId): ?array
    {
        try {
            $response = Http::withToken($token)->acceptJson()->asJson()
                ->connectTimeout(3)->timeout(10)
                ->post($baseUrl . '/api/get_result', ['cloud_id' => $cloudId, 'trans_id' => $transId]);

            if (! $response->successful()) {
                return null;
            }

            $result = $response->json();

            return is_array($result) ? $result : null;
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function markUserUnavailable(string $cloudId, string $pin): void
    {
        DB::table('machine_users')->where('cloud_id', $cloudId)->where('pin', $pin)->update([
            'biometric_status' => 'unavailable',
            'biometric_trans_id' => null,
            'biometric_requested_at' => null,
            'biometric_checked_at' => now(),
        ]);
    }

    private function failRefresh(object $refresh, string $message): array
    {
        DB::table('machine_user_refreshes')->where('cloud_id', $refresh->cloud_id)->update([
            'status' => 'failed',
            'message' => $message,
            'finished_at' => now(),
            'updated_at' => now(),
        ]);

        return ['status' => 'failed', 'cloud_id' => $refresh->cloud_id, 'message' => $message];
    }
}
