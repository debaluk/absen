<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class Employees extends Page
{

    use WithFileUploads;

    public bool $isOpen = false;
    public ?string $workLocationId = null;
    public $file = null;
    public int $progress = 0;
    public ?string $resultStatus = null;
    public ?string $resultMessage = null;
    public bool $importRunning = false;
    public array $importRows = [];
    public array $failedRows = [];
    public int $totalRows = 0;
    public int $processedRows = 0;
    public int $successCount = 0;
    public int $failedCount = 0;
    protected string $view = 'filament.pages.employees';

    protected static ?string $title = 'Karyawan';

    protected static ?string $navigationLabel = 'Karyawan';

    protected static string | UnitEnum | null $navigationGroup = 'Kelola';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 1;

    protected ?string $subheading = 'Master karyawan, jabatan, lokasi kerja, dan identitas PIN mesin.';

    public ?int $editingEmployeeId = null;

    public bool $isFormModalOpen = false;

    public bool $isGlobalActionModalOpen = false;

    public ?string $globalAttendanceStartDate = null;

    public ?string $filterWorkLocationId = null;

    public ?string $filterDepartmentId = null;

    public string $filterMachineRegistration = '';

    public string $filterStatus = '';

    public string $filterName = '';

    public int $page = 1;

    public int $perPage = 15;

    public function updatedFilterWorkLocationId(): void
    {
        $this->page = 1;
    }

    public function updatedFilterDepartmentId(): void
    {
        $this->page = 1;
    }

    public function updatedFilterMachineRegistration(): void
    {
        $this->page = 1;
    }

    public function updatedFilterStatus(): void
    {
        $this->page = 1;
    }

    public function updatedFilterName(): void
    {
        $this->page = 1;
    }

    public function updatedMachineUserSelection(): void
    {
        if (blank($this->machineUserSelection)) {
            return;
        }

        [$cloudId] = array_pad(explode('|', $this->machineUserSelection, 2), 2, null);

        $locationId = DB::table('devices_location')
            ->where('cloud_id', $cloudId)
            ->value('work_location_id');

        if ($locationId) {
            $this->work_location_id = (string) $locationId;
        }
    }

    public function goToEmployeePage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public string $employee_no = '';

    public ?string $nik = null;

    public string $name = '';

    public ?string $address = null;

    public ?string $phone = null;

    public ?string $birth_place = null;

    public ?string $birth_date = null;

    public ?string $hire_date = null;

    public ?string $attendance_start_date = null;

    public string $employment_status = 'active';

    public ?string $position_id = null;

    public ?string $work_location_id = null;

    public string $machineUserSelection = '';

    public function clearEmployeeFilters(): void
    {
        $this->filterWorkLocationId = null;
        $this->filterDepartmentId = null;
        $this->filterMachineRegistration = '';
        $this->filterStatus = '';
        $this->filterName = '';
        $this->page = 1;
    }

    public function syncMachineUsers(): void
    {
        $result = app(\App\Services\FreedomFingerEmployeeSynchronizer::class)->sync();

        if (! $result['success']) {
            Notification::make()
                ->title('Gagal mengambil data dari mesin.')
                ->body($result['message'] ?? 'Periksa koneksi Freedom Finger.')
                ->danger()
                ->send();

            return;
        }

        // Lokasi karyawan mengikuti lokasi mesin jika identitas PIN dan Cloud ID sudah tertaut.
        DB::table('employees')
            ->join('devices_location', 'devices_location.cloud_id', '=', 'employees.machine_user_cloud_id')
            ->whereNotNull('employees.machine_user_pin')
            ->update([
                'employees.work_location_id' => DB::raw('devices_location.work_location_id'),
                'employees.updated_at' => now(),
            ]);

        Notification::make()
            ->title('Download nama karyawan dari mesin selesai.')
            ->body($result['synced'] . ' pengguna berhasil dibaca.')
            ->success()
            ->send();
    }

    public function openGlobalActionModal(): void
    {
        $this->globalAttendanceStartDate = today()->toDateString();
        $this->isGlobalActionModalOpen = true;
        $this->resetValidation();
    }

    public function applyGlobalAttendanceStartDate(): void
    {
        $validated = Validator::make(
            ['globalAttendanceStartDate' => $this->globalAttendanceStartDate],
            ['globalAttendanceStartDate' => ['required', 'date']],
            [
                'globalAttendanceStartDate.required' => 'Tanggal mulai wajib absen harus diisi.',
                'globalAttendanceStartDate.date' => 'Tanggal mulai wajib absen tidak valid.',
            ],
        )->validate();

        $date = $validated['globalAttendanceStartDate'];

        $result = DB::transaction(function () use ($date) {
            $employees = DB::table('employees')
                ->where('employment_status', 'active')
                ->whereNull('attendance_start_date');

            $invalidHireDateCount = (clone $employees)
                ->whereNotNull('hire_date')
                ->where('hire_date', '>', $date)
                ->count();

            if ($invalidHireDateCount > 0) {
                return [
                    'updated' => 0,
                    'invalid_hire_date' => $invalidHireDateCount,
                ];
            }

            $updated = (clone $employees)->update([
                'attendance_start_date' => $date,
                'updated_at' => now(),
            ]);

            return [
                'updated' => $updated,
                'invalid_hire_date' => 0,
            ];
        });

        if ($result['invalid_hire_date'] > 0) {
            Notification::make()
                ->title('Tanggal tidak dapat diterapkan.')
                ->body($result['invalid_hire_date'] . ' karyawan aktif memiliki tanggal mulai bekerja setelah tanggal yang dipilih. Tidak ada data yang diubah.')
                ->danger()
                ->send();

            return;
        }

        $this->isGlobalActionModalOpen = false;
        $this->globalAttendanceStartDate = null;

        Notification::make()
            ->title('Tindakan global selesai.')
            ->body($result['updated'] . ' karyawan aktif berhasil diisi tanggal mulai wajib absennya. Data yang sudah terisi tidak diubah.')
            ->success()
            ->send();
    }

    public function createEmployee(): void
    {
        $this->resetEmployeeForm();
        $this->isFormModalOpen = true;
    }

    public function saveEmployee(): void
    {
        $validated = Validator::make(
            [
                'employee_no' => $this->employee_no,
                'nik' => $this->nik,
                'name' => $this->name,
                'address' => $this->address,
                'phone' => $this->phone,
                'birth_place' => $this->birth_place,
                'birth_date' => $this->birth_date,
                'hire_date' => $this->hire_date,
                'attendance_start_date' => $this->attendance_start_date,
                'employment_status' => $this->employment_status,
                'position_id' => $this->position_id,
                'work_location_id' => $this->work_location_id,
                'machine_user_selection' => $this->machineUserSelection,
            ],
            [
                'employee_no' => [
                    'required',
                    'string',
                    'max:30',
                    Rule::unique('employees', 'employee_no')->ignore($this->editingEmployeeId),
                ],
                'nik' => [
                    'nullable',
                    'string',
                    'max:32',
                    Rule::unique('employees', 'nik')->ignore($this->editingEmployeeId),
                ],
                'name' => ['required', 'string', 'max:255'],
                'address' => ['nullable', 'string', 'max:5000'],
                'phone' => ['nullable', 'string', 'max:30'],
                'birth_place' => ['nullable', 'string', 'max:100'],
                'birth_date' => ['nullable', 'date'],
                'hire_date' => ['nullable', 'date'],
                'attendance_start_date' => [
                    'nullable',
                    'date',
                    function ($attribute, $value, $fail) {
                        if (filled($value) && filled($this->hire_date) && $value < $this->hire_date) {
                            $fail('Tanggal mulai absensi tidak boleh sebelum tanggal mulai bekerja.');
                        }
                    },
                ],
                'employment_status' => ['required', Rule::in(['active', 'inactive'])],
                'position_id' => ['nullable', 'integer', 'exists:positions,id'],
                'work_location_id' => ['nullable', 'integer', 'exists:work_locations,id'],
                'machine_user_selection' => ['nullable', 'string', 'max:70'],
            ],
            [
                'employee_no.required' => 'Nomor karyawan wajib diisi.',
                'employee_no.unique' => 'Nomor karyawan sudah digunakan.',
                'nik.unique' => 'NIK sudah digunakan oleh karyawan lain.',
                'name.required' => 'Nama karyawan wajib diisi.',
                'position_id.exists' => 'Jabatan yang dipilih tidak ditemukan.',
                'work_location_id.exists' => 'Lokasi kerja yang dipilih tidak ditemukan.',
            ],
        )->validate();

        $machineUserCloudId = null;
        $machineUserPin = null;

        if (filled($validated['machine_user_selection'])) {
            $parts = explode('|', $validated['machine_user_selection'], 2);

            if (count($parts) !== 2 || blank($parts[0]) || blank($parts[1])) {
                throw ValidationException::withMessages([
                    'machineUserSelection' => 'Pilihan PIN mesin tidak valid.',
                ]);
            }

            [$machineUserCloudId, $machineUserPin] = $parts;

            $machineUserExists = DB::table('machine_users')
                ->where('cloud_id', $machineUserCloudId)
                ->where('pin', $machineUserPin)
                ->exists();

            if (! $machineUserExists) {
                throw ValidationException::withMessages([
                    'machineUserSelection' => 'PIN tidak ditemukan pada data pengguna mesin.',
                ]);
            }

            $alreadyAssigned = DB::table('employees')
                ->where('machine_user_cloud_id', $machineUserCloudId)
                ->where('machine_user_pin', $machineUserPin)
                ->when($this->editingEmployeeId, fn ($query) => $query->where('id', '<>', $this->editingEmployeeId))
                ->exists();

            if ($alreadyAssigned) {
                throw ValidationException::withMessages([
                    'machineUserSelection' => 'PIN tersebut sudah dipilih oleh karyawan lain.',
                ]);
            }
        }

        $values = [
            'employee_no' => $validated['employee_no'],
            'nik' => filled($validated['nik']) ? $validated['nik'] : null,
            'name' => $validated['name'],
            'address' => filled($validated['address']) ? $validated['address'] : null,
            'phone' => filled($validated['phone']) ? $validated['phone'] : null,
            'birth_place' => filled($validated['birth_place']) ? $validated['birth_place'] : null,
            'birth_date' => $validated['birth_date'] ?: null,
            'hire_date' => $validated['hire_date'] ?: null,
            'attendance_start_date' => $validated['attendance_start_date'] ?: null,
            'employment_status' => $validated['employment_status'],
            'position_id' => $validated['position_id'] ?: null,
            'work_location_id' => $validated['work_location_id'] ?: null,
            'machine_user_cloud_id' => $machineUserCloudId,
            'machine_user_pin' => $machineUserPin,
            'updated_at' => now(),
        ];

        if ($this->editingEmployeeId) {
            DB::table('employees')->where('id', $this->editingEmployeeId)->update($values);
            $message = 'Data karyawan berhasil diperbarui.';
        } else {
            $values['created_at'] = now();
            DB::table('employees')->insert($values);
            $message = 'Karyawan berhasil ditambahkan.';
        }

        $this->resetEmployeeForm();
        $this->isFormModalOpen = false;

        Notification::make()
            ->title($message)
            ->success()
            ->send();
    }

    public function editEmployee(int $employeeId): void
    {
        $employee = DB::table('employees')->where('id', $employeeId)->first();

        abort_unless($employee, 404);

        $this->editingEmployeeId = (int) $employee->id;
        $this->employee_no = $employee->employee_no;
        $this->nik = $employee->nik;
        $this->name = $employee->name;
        $this->address = $employee->address;
        $this->phone = $employee->phone;
        $this->birth_place = $employee->birth_place;
        $this->birth_date = $employee->birth_date;
        $this->hire_date = $employee->hire_date;
        $this->attendance_start_date = $employee->attendance_start_date;
        $this->employment_status = $employee->employment_status;
        $this->position_id = $employee->position_id !== null ? (string) $employee->position_id : null;
        $this->work_location_id = $employee->work_location_id !== null ? (string) $employee->work_location_id : null;
        $this->machineUserSelection = $employee->machine_user_cloud_id !== null && $employee->machine_user_pin !== null
            ? $employee->machine_user_cloud_id . '|' . $employee->machine_user_pin
            : '';

        $this->resetValidation();
        $this->isFormModalOpen = true;
    }


    public function exportExcel(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $employees = DB::table('employees')
            ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
            ->leftJoin('work_locations', 'work_locations.id', '=', 'employees.work_location_id')
            ->leftJoin('department_positions', 'department_positions.position_id', '=', 'employees.position_id')
            ->leftJoin('departments', 'departments.id', '=', 'department_positions.department_id')
            ->leftJoin('machine_users', function ($join) {
                $join->on('machine_users.cloud_id', '=', 'employees.machine_user_cloud_id')
                    ->on('machine_users.pin', '=', 'employees.machine_user_pin');
            })
            ->select([
                'employees.employee_no',
                'employees.nik',
                'employees.name',
                'employees.address',
                'employees.phone',
                'employees.birth_place',
                'employees.birth_date',
                'employees.hire_date',
                'employees.machine_user_pin',
                'employees.machine_user_cloud_id',
                'employees.employment_status',
                'employees.work_location_id',
                'departments.id as department_id',
                'departments.name as department_name',
                'positions.name as position_name',
                'work_locations.name as work_location_name',
                'machine_users.finger_count',
                'machine_users.biometric_status',
            ])
            ->orderBy('employees.name')
            ->get()
            ->when(filled($this->filterWorkLocationId), fn ($rows) => $rows->where('work_location_id', (int) $this->filterWorkLocationId))
            ->when(filled($this->filterDepartmentId), fn ($rows) => $rows->where('department_id', (int) $this->filterDepartmentId))
            ->when($this->filterMachineRegistration === 'registered', fn ($rows) => $rows->filter(fn ($employee) => $employee->biometric_status === 'checked' && (int) $employee->finger_count > 0))
            ->when($this->filterMachineRegistration === 'not_registered', fn ($rows) => $rows->filter(fn ($employee) => $employee->biometric_status !== 'checked' || $employee->finger_count === null || (int) $employee->finger_count === 0))
            ->when(filled($this->filterStatus), fn ($rows) => $rows->where('employment_status', $this->filterStatus))
            ->when(filled($this->filterName), fn ($rows) => $rows->filter(fn ($employee) => str_contains(mb_strtolower($employee->name), mb_strtolower(trim($this->filterName)))))
            ->values();

        $company = DB::table('companies')->orderBy('id')->first();
        $locationLabel = filled($this->filterWorkLocationId)
            ? (DB::table('work_locations')->where('id', $this->filterWorkLocationId)->value('name') ?: '—')
            : 'Semua Lokasi';
        $departmentLabel = filled($this->filterDepartmentId)
            ? (DB::table('departments')->where('id', $this->filterDepartmentId)->value('name') ?: '—')
            : 'Semua Departemen';
        $registrationLabel = match ($this->filterMachineRegistration) {
            'registered' => 'Sudah Terdaftar',
            'not_registered' => 'Belum Terdaftar',
            default => 'Semua Registrasi',
        };
        $statusLabel = match ($this->filterStatus) {
            'active' => 'Aktif',
            'inactive' => 'Tidak Aktif',
            default => 'Semua Status',
        };

        $escape = static fn ($value): string => htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $cell = fn ($value, string $style = 'Cell'): string =>
            '<Cell ss:StyleID="' . $style . '"><Data ss:Type="String">' . $escape($value) . '</Data></Cell>';
        $mergedRow = fn ($value, string $style): string =>
            '<Row><Cell ss:StyleID="' . $style . '" ss:MergeAcross="13"><Data ss:Type="String">' . $escape($value) . '</Data></Cell></Row>';

        $xmlRows = [];
        $xmlRows[] = $mergedRow($company->name ?? 'Nama Perusahaan', 'Company');
        $xmlRows[] = $mergedRow($company->address ?? '', 'CompanyAddress');
        $xmlRows[] = $mergedRow('DATA KARYAWAN', 'Title');
        $xmlRows[] = $mergedRow('Lokasi : ' . $locationLabel, 'Filter');
        $xmlRows[] = $mergedRow('Departemen : ' . $departmentLabel, 'Filter');
        $xmlRows[] = $mergedRow('Reg. Mesin : ' . $registrationLabel, 'Filter');
        $xmlRows[] = $mergedRow('Status Aktif : ' . $statusLabel, 'Filter');

        $headers = ['No', 'Kode', 'Nama Karyawan', 'Jabatan', 'Departemen', 'NIK', 'Alamat', 'No. HP/Telp', 'Tempat/Tgl. Lahir', 'PIN', 'ID Mesin', 'Terdaftar', 'Mulai Bekerja', 'Lokasi Kerja'];
        $xmlRows[] = '<Row>' . implode('', array_map(fn ($header) => $cell($header, 'Header'), $headers)) . '</Row>';

        foreach ($employees as $index => $employee) {
            $birth = trim(implode(' / ', array_filter([
                $employee->birth_place,
                $employee->birth_date ? \Illuminate\Support\Carbon::parse($employee->birth_date)->format('d-m-Y') : null,
            ])));
            $registration = $employee->biometric_status === 'checked' && (int) $employee->finger_count > 0
                ? 'Sudah Terdaftar (' . (int) $employee->finger_count . ' Jari)'
                : 'Belum Terdaftar';
            $hireDate = $employee->hire_date
                ? \Illuminate\Support\Carbon::parse($employee->hire_date)->format('d-m-Y')
                : '';

            $values = [
                $index + 1,
                $employee->employee_no,
                $employee->name,
                $employee->position_name,
                $employee->department_name,
                $employee->nik,
                $employee->address,
                $employee->phone,
                $birth,
                $employee->machine_user_pin,
                $employee->machine_user_cloud_id,
                $registration,
                $hireDate,
                $employee->work_location_name,
            ];
            $xmlRows[] = '<Row>' . implode('', array_map(fn ($value) => $cell($value), $values)) . '</Row>';
        }

        $widths = [40, 80, 160, 120, 120, 110, 180, 110, 140, 65, 110, 130, 100, 120];
        $xmlColumns = implode('', array_map(
            fn ($width) => '<Column ss:Width="' . $width . '"/>',
            $widths,
        ));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<?mso-application progid="Excel.Sheet"?>'
            . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
            . '<Styles>'
            . '<Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="10"/></Style>'
            . '<Style ss:ID="Company"><Alignment ss:Horizontal="Center"/><Font ss:FontName="Arial" ss:Size="14" ss:Bold="1"/></Style>'
            . '<Style ss:ID="CompanyAddress"><Alignment ss:Horizontal="Center" ss:WrapText="1"/><Font ss:FontName="Arial" ss:Size="10"/></Style>'
            . '<Style ss:ID="Title"><Alignment ss:Horizontal="Center"/><Font ss:FontName="Arial" ss:Size="12" ss:Bold="1"/><Interior ss:Color="#D9EAF7" ss:Pattern="Solid"/></Style>'
            . '<Style ss:ID="Filter"><Font ss:FontName="Arial" ss:Size="10"/></Style>'
            . '<Style ss:ID="Header"><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Font ss:FontName="Arial" ss:Size="10" ss:Bold="1"/><Interior ss:Color="#D9E1F2" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>'
            . '<Style ss:ID="Cell"><Alignment ss:Vertical="Top" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9D9D9"/></Borders></Style>'
            . '</Styles><Worksheet ss:Name="Data Karyawan"><Table>' . $xmlColumns . implode('', $xmlRows)
            . '</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><FreezePanes/><FrozenNoSplit/><SplitHorizontal>8</SplitHorizontal><TopRowBottomPane>8</TopRowBottomPane><ActivePane>2</ActivePane></WorksheetOptions></Worksheet></Workbook>';

        return response()->streamDownload(
            static function () use ($xml): void {
                echo $xml;
            },
            'data-karyawan-' . now()->format('Ymd_His') . '.xls',
            ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8'],
        );
    }

    public function toggleReportInclusion(int $employeeId): void
    {
        $includeInReports = DB::transaction(function () use ($employeeId) {
            $employee = DB::table('employees')
                ->where('id', $employeeId)
                ->lockForUpdate()
                ->first();

            abort_unless($employee, 404);

            $includeInReports = ! (bool) $employee->include_in_reports;

            DB::table('employees')
                ->where('id', $employeeId)
                ->update([
                    'include_in_reports' => $includeInReports,
                    'updated_at' => now(),
                ]);

            return $includeInReports;
        });

        Notification::make()
            ->title('Status Ikut Rekap diperbarui.')
            ->body($includeInReports ? 'Karyawan akan disertakan dalam rekap.' : 'Karyawan tidak disertakan dalam rekap.')
            ->success()
            ->send();
    }

    public function deleteEmployee(int $employeeId): void
    {
        $employee = DB::table('employees')->where('id', $employeeId)->first();

        abort_unless($employee, 404);

        // Riwayat izin tetap dipertahankan; karyawan yang sudah memiliki izin tidak dihapus.
        if (DB::table('leaves')->where('employee_id', $employeeId)->exists()) {
            Notification::make()
                ->title('Karyawan tidak dapat dihapus karena sudah memiliki riwayat izin.')
                ->danger()
                ->send();

            return;
        }

        DB::table('employees')->where('id', $employeeId)->delete();

        if ($this->editingEmployeeId === $employeeId) {
            $this->resetEmployeeForm();
        }

        Notification::make()
            ->title('Data karyawan berhasil dihapus.')
            ->success()
            ->send();
    }

    public function resetEmployeeForm(): void
    {
        $this->reset([
            'editingEmployeeId',
            'employee_no',
            'nik',
            'name',
            'address',
            'phone',
            'birth_place',
            'birth_date',
            'hire_date',
            'attendance_start_date',
            'employment_status',
            'position_id',
            'work_location_id',
            'machineUserSelection',
        ]);

        $this->employment_status = 'active';
        $this->isFormModalOpen = false;
        $this->resetValidation();
    }

    public function openModal(): void
    {
        $this->resetImport();
        $this->isOpen = true;
    }

    public function closeModal(): void
    {
        if ($this->importRunning) {
            return;
        }

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
            . str_repeat('<Row>' . $emptyCells . '</Row>', 10)
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
        $this->resetImportResult();

        try {
            Validator::make(
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

            $this->importRows = $this->readEmployeesFromSpreadsheet($this->file->get());
            $this->totalRows = count($this->importRows);

            if ($this->totalRows === 0) {
                throw new ImportCancelledException('Tidak ada data karyawan yang dapat diimport.');
            }

            $this->importRunning = true;
            $this->file = null;
        } catch (ImportCancelledException $exception) {
            $this->resultStatus = 'failed';
            $this->resultMessage = $exception->getMessage();
        } catch (ValidationException $exception) {
            $this->resultStatus = 'failed';
            $this->resultMessage = collect($exception->errors())->flatten()->first() ?: 'Data import tidak valid.';
        } catch (Throwable $exception) {
            report($exception);
            $this->resultStatus = 'failed';
            $this->resultMessage = 'File tidak dapat diproses. Periksa kembali file template Excel.';
        }
    }

    public function processImportBatch(): void
    {
        if (! $this->importRunning) {
            return;
        }

        $batch = array_splice($this->importRows, 0, 10);

        foreach ($batch as $employeeData) {
            $rowNumber = (int) ($employeeData['_row'] ?? 0);

            try {
                $this->validateEmployeeData($employeeData);
                $this->saveImportedEmployee($employeeData);
                $this->successCount++;
            } catch (ImportCancelledException $exception) {
                $this->failedCount++;
                $this->failedRows[] = [
                    'row' => $rowNumber,
                    'message' => $exception->getMessage(),
                ];
            } catch (Throwable $exception) {
                report($exception);
                $this->failedCount++;
                $this->failedRows[] = [
                    'row' => $rowNumber,
                    'message' => 'Terjadi kesalahan saat menyimpan data karyawan.',
                ];
            }

            $this->processedRows++;
        }

        $this->progress = $this->totalRows > 0
            ? (int) floor(($this->processedRows / $this->totalRows) * 100)
            : 100;

        if ($this->processedRows >= $this->totalRows) {
            $this->importRunning = false;
            $this->resultStatus = $this->failedCount === 0
                ? 'success'
                : ($this->successCount > 0 ? 'partial' : 'failed');
            $this->resultMessage = sprintf(
                'Import selesai: %d berhasil dan %d gagal dari %d data.',
                $this->successCount,
                $this->failedCount,
                $this->totalRows,
            );
            $this->importRows = [];
            $this->progress = 100;
        }
    }

    private function readEmployeesFromSpreadsheet(string $contents): array
    {
        if (! preg_match('/<(?:[a-zA-Z0-9_]+:)?Workbook\\b/i', $contents)) {
            throw new ImportCancelledException('File Excel tidak dapat dibaca. Gunakan template yang disediakan.');
        }

        preg_match_all('/<(?:[a-zA-Z0-9_]+:)?Row\\b[^>]*>(.*?)<\\/(?:[a-zA-Z0-9_]+:)?Row>/is', $contents, $rowMatches);
        $rows = $rowMatches[1] ?? [];

        if (count($rows) < 2) {
            throw new ImportCancelledException('Template harus berisi judul kolom dan minimal satu baris data karyawan.');
        }

        $headers = $this->readSpreadsheetRow($rows[0]);
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

        $columnIndexes = [];
        foreach ($columnMap as $header => $key) {
            $columnIndex = array_search($header, $headers, true);
            if ($columnIndex === false) {
                throw new ImportCancelledException('Kolom "' . $header . '" tidak ditemukan. Download ulang template Import Data Karyawan.');
            }
            $columnIndexes[$key] = $columnIndex;
        }

        $employees = [];
        foreach (array_slice($rows, 1) as $rowIndex => $rowContent) {
            $values = $this->readSpreadsheetRow($rowContent);
            $mapped = [];

            foreach ($columnIndexes as $key => $columnIndex) {
                $mapped[$key] = trim((string) ($values[$columnIndex] ?? ''));
            }

            if (collect($mapped)->every(fn ($value) => blank($value))) {
                continue;
            }

            $mapped['_row'] = $rowIndex + 2;
            $employees[] = $mapped;
        }

        return $employees;
    }

    private function saveImportedEmployee(array $employeeData): void
    {
        DB::transaction(function () use ($employeeData): void {
            $this->assertNoDuplicateEmployee($employeeData);
            $machineIdentity = $this->resolveMachineIdentity($employeeData);

            $positionId = null;
            if (filled($employeeData['position'])) {
                $positionId = DB::table('positions')
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($employeeData['position']))])
                    ->value('id');

                if (! $positionId) {
                    throw new ImportCancelledException(
                        'Jabatan "' . $employeeData['position'] . '" tidak ditemukan.'
                    );
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
                'work_location_id' => $this->workLocationId,
                'machine_user_cloud_id' => $machineIdentity['cloud_id'],
                'machine_user_pin' => $machineIdentity['pin'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    private function resetImportResult(): void
    {
        $this->progress = 0;
        $this->resultStatus = null;
        $this->resultMessage = null;
        $this->importRunning = false;
        $this->importRows = [];
        $this->failedRows = [];
        $this->totalRows = 0;
        $this->processedRows = 0;
        $this->successCount = 0;
        $this->failedCount = 0;
    }

    private function readSpreadsheetRow(string $rowContent): array
    {
        preg_match_all('/<(?:[a-zA-Z0-9_]+:)?Cell\\b([^>]*?)(?:\\/\\s*>|>(.*?)<\\/(?:[a-zA-Z0-9_]+:)?Cell>)/is', $rowContent, $cellMatches, PREG_SET_ORDER);

        $values = [];
        $nextIndex = 0;

        foreach ($cellMatches as $cell) {
            $attributes = $cell[1] ?? '';
            $cellContent = $cell[2] ?? '';

            if (preg_match("/(?:ss:)?Index\\s*=\\s*[\"'](\\d+)[\"']/i", $attributes, $indexMatch)) {
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
        $this->reset(['workLocationId', 'file']);
        $this->resetImportResult();
        $this->resetValidation();
    }

    protected function getViewData(): array
    {
        $machineUsers = DB::table('machine_users')
            ->leftJoin('devices', 'devices.cloud_id', '=', 'machine_users.cloud_id')
            ->select([
                'machine_users.cloud_id',
                'machine_users.pin',
                'machine_users.name',
                'devices.name as device_name',
            ])
            ->orderBy('machine_users.name')
            ->orderBy('machine_users.pin')
            ->get()
            ->map(function ($machineUser) {
                $machineUser->selection = $machineUser->cloud_id . '|' . $machineUser->pin;

                return $machineUser;
            });

        $employees = DB::table('employees')
            ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
            ->leftJoin('work_locations', 'work_locations.id', '=', 'employees.work_location_id')
            ->leftJoin('department_positions', 'department_positions.position_id', '=', 'employees.position_id')
            ->leftJoin('departments', 'departments.id', '=', 'department_positions.department_id')
            ->leftJoin('machine_users', function ($join) {
                $join->on('machine_users.cloud_id', '=', 'employees.machine_user_cloud_id')
                    ->on('machine_users.pin', '=', 'employees.machine_user_pin');
            })
            ->select([
                'employees.id',
                'employees.employee_no',
                'employees.nik',
                'employees.name',
                'employees.hire_date',
                'employees.attendance_start_date',
                'employees.employment_status',
                'employees.include_in_reports',
                'employees.position_id',
                'employees.work_location_id',
                'departments.id as department_id',
                'departments.name as department_name',
                'employees.machine_user_cloud_id',
                'employees.machine_user_pin',
                'positions.name as position_name',
                'work_locations.name as work_location_name',
                'machine_users.name as machine_user_name',
                'machine_users.finger_count',
                'machine_users.biometric_status',
                'machine_users.biometric_checked_at',
            ])
            ->orderBy('employees.name')
            ->get();

        $filteredEmployees = $employees
            ->when(filled($this->filterWorkLocationId), fn ($rows) => $rows->where('work_location_id', (int) $this->filterWorkLocationId))
            ->when(filled($this->filterDepartmentId), fn ($rows) => $rows->where('department_id', (int) $this->filterDepartmentId))
            ->when($this->filterMachineRegistration === 'registered', fn ($rows) => $rows->filter(fn ($employee) => $employee->biometric_status === 'checked' && (int) $employee->finger_count > 0))
            ->when($this->filterMachineRegistration === 'not_registered', fn ($rows) => $rows->filter(fn ($employee) => $employee->biometric_status !== 'checked' || $employee->finger_count === null || (int) $employee->finger_count === 0))
            ->when(filled($this->filterStatus), fn ($rows) => $rows->where('employment_status', $this->filterStatus))
            ->when(filled($this->filterName), fn ($rows) => $rows->filter(fn ($employee) => str_contains(mb_strtolower($employee->name), mb_strtolower(trim($this->filterName)))))
            ->values();

        $totalFilteredEmployees = $filteredEmployees->count();
        $lastPage = max(1, (int) ceil($totalFilteredEmployees / $this->perPage));
        $this->page = min(max(1, $this->page), $lastPage);
        $employees = $filteredEmployees->forPage($this->page, $this->perPage)->values();

        $positions = DB::table('positions')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $workLocations = DB::table('work_locations')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $departments = DB::table('departments')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'employees' => $employees,
            'machineUsers' => $machineUsers,
            'positions' => $positions,
            'workLocations' => $workLocations,
            'departments' => $departments,
            'totalFilteredEmployees' => $totalFilteredEmployees,
            'currentPage' => $this->page,
            'lastPage' => $lastPage,
            'perPage' => $this->perPage,
            'globalAttendanceStartDateCandidateCount' => DB::table('employees')->where('employment_status', 'active')->whereNull('attendance_start_date')->count(),
        ];
    }
}

class ImportCancelledException extends \RuntimeException
{
}
