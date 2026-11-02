<?php

namespace App\Filament\Pages;

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
    protected string $view = 'filament.pages.employees';

    protected static ?string $title = 'Karyawan';

    protected static ?string $navigationLabel = 'Karyawan';

    protected static string | UnitEnum | null $navigationGroup = 'Kelola';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 1;

    protected ?string $subheading = 'Master karyawan, jabatan, lokasi kerja, dan identitas PIN mesin.';

    public ?int $editingEmployeeId = null;

    public bool $isFormModalOpen = false;

    public ?string $filterWorkLocationId = null;

    public string $filterStatus = '';

    public int $page = 1;

    public int $perPage = 15;

    public function updatedFilterWorkLocationId(): void
    {
        $this->page = 1;
    }

    public function updatedFilterStatus(): void
    {
        $this->page = 1;
    }

    public function goToEmployeePage(int $page): void
    {
        $this->page = max(1, min($page, $this->lastPage));
    }

    public string $employee_no = '';

    public ?string $nik = null;

    public string $name = '';

    public ?string $hire_date = null;

    public ?string $attendance_start_date = null;

    public string $employment_status = 'active';

    public ?string $position_id = null;

    public ?string $work_location_id = null;

    public string $machineUserSelection = '';

    public function clearEmployeeFilters(): void
    {
        $this->filterWorkLocationId = null;
        $this->filterStatus = '';
        $this->page = 1;
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
                'employees.position_id',
                'employees.work_location_id',
                'employees.machine_user_cloud_id',
                'employees.machine_user_pin',
                'positions.name as position_name',
                'work_locations.name as work_location_name',
                'machine_users.name as machine_user_name',
            ])
            ->orderBy('employees.name')
            ->get();

        $allEmployees = $employees;

        $filteredEmployees = $employees
            ->when(filled($this->filterWorkLocationId), fn ($rows) => $rows->where('work_location_id', (int) $this->filterWorkLocationId))
            ->when(filled($this->filterStatus), fn ($rows) => $rows->where('employment_status', $this->filterStatus))
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

        return [
            'employees' => $employees,
            'machineUsers' => $machineUsers,
            'positions' => $positions,
            'workLocations' => $workLocations,
            'totalFilteredEmployees' => $totalFilteredEmployees,
            'currentPage' => $this->page,
            'lastPage' => $lastPage,
            'perPage' => $this->perPage,
        ];
    }
}
