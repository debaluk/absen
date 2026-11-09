<?php

namespace App\Livewire;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class EmployeeImport extends Component
{
    use WithFileUploads;

    public bool $isOpen = false;

    public ?string $workLocationId = null;

    public $file = null;

    public int $progress = 0;

    public ?string $resultStatus = null;

    public ?string $resultMessage = null;

    public function openModal(): void
    {
        $this->resetImport();
        $this->isOpen = true;
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
        $this->resetImport();
    }

    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'NIK',
            'Nama Karyawan',
            'Alamat',
            'No. HP/Telp',
            'Tempat Lahir',
            'Tanggal Lahir',
            'Mulai Bekerja',
            'Mulai Wajib Absensi',
            'Jabatan',
            'PIN',
            'ID Mesin',
        ];

        $escape = static fn (string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $headerCells = implode('', array_map(
            fn (string $header): string => '<Cell><Data ss:Type="String">' . $escape($header) . '</Data></Cell>',
            $headers,
        ));
        $emptyCells = str_repeat('<Cell><Data ss:Type="String"></Data></Cell>', count($headers));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<?mso-application progid="Excel.Sheet"?>'
            . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
            . '<Worksheet ss:Name="Import Karyawan"><Table>'
            . '<Row>' . $headerCells . '</Row>'
            . '<Row>' . $emptyCells . '</Row>'
            . '</Table></Worksheet></Workbook>';

        return response()->streamDownload(
            static function () use ($xml): void {
                echo $xml;
            },
            'template-import-karyawan.xls',
            ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8'],
        );
    }

    public function importEmployee(): void
    {
        $this->resetValidation();
        $this->resultStatus = null;
        $this->resultMessage = null;
        $this->progress = 0;

        try {
            $validated = Validator::make(
                [
                    'workLocationId' => $this->workLocationId,
                    'file' => $this->file,
                ],
                [
                    'workLocationId' => ['required', 'integer', 'exists:work_locations,id'],
                    'file' => ['required', 'file', 'extensions:xls', 'max:2048'],
                ],
                [
                    'workLocationId.required' => 'Lokasi kerja wajib dipilih.',
                    'workLocationId.exists' => 'Lokasi kerja tidak ditemukan.',
                    'file.required' => 'File Excel wajib dipilih.',
                    'file.extensions' => 'Gunakan file template Excel berformat .xls.',
                    'file.max' => 'Ukuran file maksimal 2 MB.',
                ],
            )->validate();

            $employeeData = $this->readEmployeeFromSpreadsheet($this->file->get());

            $this->progress = 35;

            $this->validateEmployeeData($employeeData);

            $employeeNo = DB::transaction(function () use ($employeeData, $validated) {
                $this->assertNoDuplicateEmployee($employeeData);
                $machineIdentity = $this->resolveMachineIdentity($employeeData);

                $positionId = null;
                if (filled($employeeData['position'])) {
                    $positionId = DB::table('positions')
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($employeeData['position']))])
                        ->value('id');

                    if (! $positionId) {
                        throw new ImportCancelledException('Jabatan "' . $employeeData['position'] . '" tidak ditemukan. Periksa nama jabatan pada template.');
                    }
                }

                $employeeNo = $this->nextEmployeeNo();

                DB::table('employees')->insert([
                    'employee_no' => $employeeNo,
                    'nik' => $employeeData['nik'] ?: null,
                    'name' => $employeeData['name'],
                    'address' => $employeeData['address'] ?: null,
                    'phone' => $employeeData['phone'] ?: null,
                    'birth_place' => $employeeData['birth_place'] ?: null,
                    'birth_date' => $employeeData['birth_date'] ?: null,
                    'hire_date' => $employeeData['hire_date'] ?: null,
                    'attendance_start_date' => $employeeData['attendance_start_date'] ?: null,
                    'employment_status' => 'active',
                    'position_id' => $positionId,
                    'work_location_id' => $validated['workLocationId'],
                    'machine_user_cloud_id' => $machineIdentity['cloud_id'],
                    'machine_user_pin' => $machineIdentity['pin'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return $employeeNo;
            });

            $this->progress = 100;
            $this->resultStatus = 'success';
            $this->resultMessage = 'Karyawan berhasil diimport dengan kode ' . $employeeNo . '.';
            $this->file = null;
        } catch (ImportCancelledException $exception) {
            $this->progress = 100;
            $this->resultStatus = 'cancelled';
            $this->resultMessage = $exception->getMessage();
        } catch (ValidationException $exception) {
            $this->progress = 100;
            $this->resultStatus = 'cancelled';
            $this->resultMessage = collect($exception->errors())->flatten()->first() ?: 'Data karyawan tidak valid. Import dibatalkan.';
        } catch (Throwable $exception) {
            report($exception);
            $this->progress = 100;
            $this->resultStatus = 'cancelled';
            $this->resultMessage = 'Import dibatalkan karena terjadi kesalahan saat memproses data. Periksa kembali file dan data mesin.';
        }
    }

    private function readEmployeeFromSpreadsheet(string $contents): array
    {
        if (! preg_match('/<(?:[a-zA-Z0-9_]+:)?Workbook\\b/i', $contents)) {
            throw new ImportCancelledException('File Excel tidak dapat dibaca. Gunakan template yang disediakan.');
        }

        preg_match_all('/<(?:[a-zA-Z0-9_]+:)?Row\\b[^>]*>(.*?)<\\/(?:[a-zA-Z0-9_]+:)?Row>/is', $contents, $rowMatches);
        $rows = $rowMatches[1] ?? [];

        if (count($rows) < 2) {
            throw new ImportCancelledException('Template harus berisi satu baris judul dan satu baris data karyawan.');
        }

        $headers = $this->readSpreadsheetRow($rows[0]);
        $values = $this->readSpreadsheetRow($rows[1]);

        $columnMap = [
            'NIK' => 'nik',
            'Nama Karyawan' => 'name',
            'Alamat' => 'address',
            'No. HP/Telp' => 'phone',
            'Tempat Lahir' => 'birth_place',
            'Tanggal Lahir' => 'birth_date',
            'Mulai Bekerja' => 'hire_date',
            'Mulai Wajib Absensi' => 'attendance_start_date',
            'Jabatan' => 'position',
            'PIN' => 'pin',
            'ID Mesin' => 'cloud_id',
        ];

        $mapped = [];
        foreach ($columnMap as $header => $key) {
            $index = array_search($header, $headers, true);
            $mapped[$key] = $index === false ? null : trim((string) ($values[$index] ?? ''));
        }

        foreach ($columnMap as $header => $key) {
            if (! in_array($header, $headers, true)) {
                throw new ImportCancelledException('Kolom "' . $header . '" tidak ditemukan. Download ulang template Import Karyawan.');
            }
        }

        $hasAdditionalData = false;
        for ($index = 2; $index < count($rows); $index++) {
            if (collect($this->readSpreadsheetRow($rows[$index]))->contains(fn ($value) => filled($value))) {
                $hasAdditionalData = true;
                break;
            }
        }

        if ($hasAdditionalData) {
            throw new ImportCancelledException('Import hanya menerima satu karyawan dalam satu file. Hapus baris data tambahan.');
        }

        return $mapped;
    }

    private function readSpreadsheetRow(string $rowContent): array
    {
        preg_match_all('/<(?:[a-zA-Z0-9_]+:)?Cell\\b([^>]*?)(?:\\/\\s*>|>(.*?)<\\/(?:[a-zA-Z0-9_]+:)?Cell>)/is', $rowContent, $cellMatches, PREG_SET_ORDER);

        $values = [];
        $nextIndex = 0;

        foreach ($cellMatches as $cell) {
            $attributes = $cell[1] ?? '';
            $cellContent = $cell[2] ?? '';

            if (preg_match('/(?:ss:)?Index\\s*=\\s*["\\'](\\d+)["\\']/i', $attributes, $indexMatch)) {
                $nextIndex = max(0, (int) $indexMatch[1] - 1);
            }

            $value = '';
            if (preg_match('/<(?:[a-zA-Z0-9_]+:)?Data\\b[^>]*>(.*?)<\\/(?:[a-zA-Z0-9_]+:)?Data>/is', $cellContent, $dataMatch)) {
                $value = html_entity_decode(strip_tags($dataMatch[1]), ENT_QUOTES | ENT_XML1, 'UTF-8');
            }

            $values[$nextIndex] = trim($value);
            $nextIndex++;
        }

        if ($values === []) {
            return [];
        }

        $maxIndex = max(array_keys($values));
        return array_map(fn ($index) => $values[$index] ?? '', range(0, $maxIndex));
    }

    private function validateEmployeeData(array &$data): void
    {
        foreach (['birth_date', 'hire_date', 'attendance_start_date'] as $field) {
            if (blank($data[$field])) {
                $data[$field] = null;
                continue;
            }

            try {
                $data[$field] = Carbon::parse($data[$field])->toDateString();
            } catch (Throwable) {
                throw new ImportCancelledException('Format tanggal pada kolom ' . $field . ' tidak valid. Gunakan format YYYY-MM-DD.');
            }
        }

        $validator = Validator::make($data, [
            'nik' => ['nullable', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:5000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'birth_place' => ['nullable', 'string', 'max:100'],
            'birth_date' => ['nullable', 'date'],
            'hire_date' => ['nullable', 'date'],
            'attendance_start_date' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) use ($data) {
                    if (filled($value) && filled($data['hire_date']) && $value < $data['hire_date']) {
                        $fail('Tanggal mulai wajib absensi tidak boleh sebelum tanggal mulai bekerja.');
                    }
                },
            ],
            'position' => ['nullable', 'string', 'max:100'],
            'pin' => ['nullable', 'string', 'max:32'],
            'cloud_id' => ['nullable', 'string', 'max:32'],
        ], [
            'name.required' => 'Nama Karyawan wajib diisi.',
        ]);

        if ($validator->fails()) {
            throw new ImportCancelledException($validator->errors()->first());
        }

        if (filled($data['pin']) xor filled($data['cloud_id'])) {
            throw new ImportCancelledException('PIN dan ID Mesin harus diisi bersama. Keduanya boleh dikosongkan jika karyawan tidak ditautkan ke mesin.');
        }
    }

    private function assertNoDuplicateEmployee(array $data): void
    {
        if (filled($data['nik'])) {
            $existing = DB::table('employees')
                ->where('nik', $data['nik'])
                ->lockForUpdate()
                ->first(['id', 'name']);

            if ($existing) {
                if (mb_strtolower(trim($existing->name)) === mb_strtolower(trim($data['name']))) {
                    throw new ImportCancelledException('NIK dan nama karyawan sudah terdaftar. Import dibatalkan.');
                }

                throw new ImportCancelledException('NIK sudah digunakan oleh karyawan dengan nama berbeda. Import dibatalkan karena NIK harus unik.');
            }
        }
    }

    private function resolveMachineIdentity(array $data): array
    {
        if (blank($data['pin']) && blank($data['cloud_id'])) {
            return ['cloud_id' => null, 'pin' => null];
        }

        $machineUserExists = DB::table('machine_users')
            ->where('cloud_id', $data['cloud_id'])
            ->where('pin', $data['pin'])
            ->exists();

        if (! $machineUserExists) {
            throw new ImportCancelledException('Kombinasi PIN dan ID Mesin tidak ditemukan pada data machine_users. Import dibatalkan.');
        }

        $machineLocationId = DB::table('devices_location')
            ->where('cloud_id', $data['cloud_id'])
            ->value('work_location_id');

        if (! $machineLocationId) {
            throw new ImportCancelledException('Lokasi mesin untuk ID Mesin tersebut belum diatur. Import dibatalkan.');
        }

        $duplicateMachineUser = DB::table('employees')
            ->join('devices_location', 'devices_location.cloud_id', '=', 'employees.machine_user_cloud_id')
            ->where('employees.machine_user_pin', $data['pin'])
            ->where('devices_location.work_location_id', $machineLocationId)
            ->lockForUpdate()
            ->exists();

        if ($duplicateMachineUser) {
            throw new ImportCancelledException('PIN tersebut sudah digunakan pada lokasi mesin yang sama. Import dibatalkan.');
        }

        return ['cloud_id' => $data['cloud_id'], 'pin' => $data['pin']];
    }

    private function nextEmployeeNo(): string
    {
        $lastEmployeeNo = DB::table('employees')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->value('employee_no');

        if (is_string($lastEmployeeNo) && preg_match('/^(.*?)(\d+)$/', $lastEmployeeNo, $matches)) {
            $prefix = $matches[1];
            $number = (int) $matches[2] + 1;
            $padding = strlen($matches[2]);
            $candidate = $prefix . str_pad((string) $number, $padding, '0', STR_PAD_LEFT);
        } else {
            $nextId = ((int) DB::table('employees')->max('id')) + 1;
            $candidate = 'EMP' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
        }

        while (DB::table('employees')->where('employee_no', $candidate)->exists()) {
            if (preg_match('/^(.*?)(\d+)$/', $candidate, $matches)) {
                $candidate = $matches[1] . str_pad((string) ((int) $matches[2] + 1), strlen($matches[2]), '0', STR_PAD_LEFT);
            } else {
                $candidate = 'EMP' . str_pad((string) (((int) DB::table('employees')->max('id')) + 1), 6, '0', STR_PAD_LEFT);
            }
        }

        return $candidate;
    }

    private function resetImport(): void
    {
        $this->reset(['workLocationId', 'file', 'progress', 'resultStatus', 'resultMessage']);
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.employee-import', [
            'workLocations' => DB::table('work_locations')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }
}

class ImportCancelledException extends \RuntimeException
{
}
