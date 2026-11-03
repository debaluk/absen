<?php

namespace App\\Services;

use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Http;
use Throwable;

class FreedomFingerEmployeeSynchronizer
{
    public function sync(): array
    {
        $baseUrl = rtrim((string) config('services.freedom_finger.url'), '/');
        $token = (string) config('services.freedom_finger.token', '');

        if ($baseUrl === '' || $token === '') {
            return ['success' => false, 'message' => 'URL dan token layanan mesin wajib dikonfigurasi.', 'synced' => 0];
        }

        try {
            $response = Http::withToken($token)->acceptJson()->asJson()
                ->connectTimeout(3)->timeout(20)
                ->post($baseUrl . '/api/get_users', (object) []);

            if (! $response->successful()) {
                return ['success' => false, 'message' => 'Layanan mesin merespons HTTP ' . $response->status() . '.', 'synced' => 0];
            }

            $result = $response->json();
            if (! is_array($result) || ($result['success'] ?? false) !== true || ! is_array($result['data'] ?? null)) {
                return ['success' => false, 'message' => 'Format daftar pengguna dari mesin tidak valid.', 'synced' => 0];
            }

            $synced = 0;

            DB::transaction(function () use ($result, &$synced) {
                foreach ($result['data'] as $user) {
                    if (! is_array($user) || blank($user['cloud_id'] ?? null) || blank($user['pin'] ?? null)) {
                        continue;
                    }

                    $cloudId = (string) $user['cloud_id'];
                    $pin = (string) $user['pin'];
                    $machineName = trim((string) ($user['name'] ?? ''));

                    // Simpan identitas mesin tanpa mengubah payload biometrik.
                    DB::table('machine_users')->updateOrInsert(
                        ['cloud_id' => $cloudId, 'pin' => $pin],
                        [
                            'name' => $machineName !== '' ? mb_substr($machineName, 0, 100) : null,
                            'privilege' => isset($user['privilege']) ? (string) $user['privilege'] : null,
                            'updated' => $user['updated'] ?? now(),
                        ],
                    );

                    $employee = DB::table('employees')
                        ->where('machine_user_cloud_id', $cloudId)
                        ->where('machine_user_pin', $pin)
                        ->first();

                    $locationId = DB::table('devices_location')
                        ->where('cloud_id', $cloudId)
                        ->value('work_location_id');

                    if ($employee) {
                        // Jangan menimpa nama master yang mungkin sudah dilengkapi menjadi nama lengkap.
                        $values = ['updated_at' => now()];
                        if ($locationId) {
                            $values['work_location_id'] = $locationId;
                        }

                        DB::table('employees')->where('id', $employee->id)->update($values);
                    } else {
                        DB::table('employees')->insert([
                            'employee_no' => $this->nextEmployeeNumber(),
                            'name' => $machineName !== '' ? mb_substr($machineName, 0, 15) : 'PIN ' . $pin,
                            'employment_status' => 'active',
                            'work_location_id' => $locationId,
                            'machine_user_cloud_id' => $cloudId,
                            'machine_user_pin' => $pin,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    $synced++;
                }
            });

            return ['success' => true, 'message' => null, 'synced' => $synced];
        } catch (Throwable $exception) {
            report($exception);

            return ['success' => false, 'message' => 'Gagal mengambil atau menyimpan data pengguna mesin.', 'synced' => 0];
        }
    }

    /**
     * Membuat nomor karyawan berurutan dengan format K0001, K0002, dan seterusnya.
     */
    private function nextEmployeeNumber(): string
    {
        $employeeNumbers = DB::table('employees')
            ->where('employee_no', 'like', 'K%')
            ->lockForUpdate()
            ->pluck('employee_no');

        $lastNumber = 0;

        foreach ($employeeNumbers as $employeeNumber) {
            if (preg_match('/^K(\\d+)$/', (string) $employeeNumber, $matches)) {
                $lastNumber = max($lastNumber, (int) $matches[1]);
            }
        }

        return 'K' . str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }
}
