<?php

namespace App\\Services;

use Illuminate\\Support\\Carbon;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Http;
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

            $client = Http::withToken($token)->acceptJson()->asJson()->connectTimeout(3);
            $pulled = 0;

            // Tarik log dari memori setiap mesin sebelum membaca database engine.
            foreach ($devices as $cloudId) {
                $queued = $client->timeout(20)->post($baseUrl . '/api/sync_attlog', [
                    'cloud_id' => (string) $cloudId,
                ]);

                if (! $queued->successful()) {
                    throw new \\RuntimeException('Gagal meminta penarikan log mesin ' . $cloudId . ' (HTTP ' . $queued->status() . ').');
                }

                $queueResult = $queued->json();
                $transId = $queueResult['trans_id'] ?? null;
                if (($queueResult['success'] ?? false) !== true || ! is_string($transId) || $transId === '') {
                    throw new \\RuntimeException('Freedom Finger tidak memberikan trans_id untuk penarikan log mesin ' . $cloudId . '.');
                }

                $completed = false;
                $deadline = microtime(true) + 270;
                while (microtime(true) < $deadline) {
                    sleep(3);
                    $poll = $client->timeout(15)->post($baseUrl . '/api/get_result', [
                        'cloud_id' => (string) $cloudId,
                        'trans_id' => $transId,
                    ]);

                    if (! $poll->successful()) {
                        throw new \\RuntimeException('Gagal memeriksa hasil penarikan log mesin ' . $cloudId . ' (HTTP ' . $poll->status() . ').');
                    }

                    $status = $poll->json();
                    if (($status['success'] ?? false) !== true) {
                        throw new \\RuntimeException('Respons status perintah mesin ' . $cloudId . ' tidak valid.');
                    }

                    if (($status['status'] ?? null) === 'done') {
                        if (($status['result_code'] ?? null) !== 'OK') {
                            throw new \\RuntimeException('Mesin ' . $cloudId . ' menolak penarikan log ('
                                . (string) ($status['result_code'] ?? 'kode tidak diketahui') . ').');
                        }
                        $pulled += (int) ($status['data']['added'] ?? 0);
                        $completed = true;
                        break;
                    }

                    if (($status['status'] ?? null) === 'timeout') {
                        throw new \\RuntimeException('Mesin ' . $cloudId . ' tidak menjawab perintah penarikan log.');
                    }
                }

                if (! $completed) {
                    throw new \\RuntimeException('Waktu tunggu penarikan log mesin ' . $cloudId . ' habis.');
                }
            }

            $startDate = now()->subDays(max(1, $days) - 1)->toDateString();
            $endDate = now()->toDateString();
            $synced = 0;

            foreach ($devices as $cloudId) {
                $response = $client->timeout(30)->post($baseUrl . '/api/get_attlog', [
                    'cloud_id' => (string) $cloudId,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ]);

                if (! $response->successful()) {
                    throw new \\RuntimeException('Gagal mengambil log mesin ' . $cloudId . ' (HTTP ' . $response->status() . ').');
                }

                $result = $response->json();
                if (! is_array($result) || ($result['success'] ?? false) !== true || ! is_array($result['data'] ?? null)) {
                    throw new \\RuntimeException('Format log mesin ' . $cloudId . ' tidak valid.');
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
                            // API get_attlog memang tidak mengembalikan io_mode dan verify_mode.
                            'photo' => $log['photo'] ?? null,
                        ],
                    );
                    $synced++;
                }
            }

            return ['success' => true, 'message' => null, 'synced' => $synced, 'pulled' => $pulled];
        } catch (Throwable $exception) {
            report($exception);
            return ['success' => false, 'message' => $exception->getMessage(), 'synced' => 0];
        }
    }
}
