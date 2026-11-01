<?php

namespace App\Filament\Pages;

use App\Services\FreedomFingerEmployeeSynchronizer;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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

    protected ?string $subheading = 'Master karyawan dan penghubung data PIN dari mesin.';

    public array $nikInputs = [];

    public function linkMachineEmployee(int $sourceId): void
    {
        $source = DB::table('freedom_finger_employees')->where('id', $sourceId)->first();

        if (! $source) {
            Notification::make()->title('Data mesin tidak ditemukan.')->danger()->send();
            return;
        }

        $validated = Validator::make(
            ['nik' => $this->nikInputs[$sourceId] ?? null],
            ['nik' => ['required', 'string', 'max:32']],
            ['nik.required' => 'NIK wajib diisi sebelum data mesin dihubungkan.'],
        )->validate();

        DB::transaction(function () use ($source, $validated): void {
            $employee = DB::table('employees')->where('nik', $validated['nik'])->first();

            if (! $employee) {
                $employeeId = DB::table('employees')->insertGetId([
                    'employee_no' => 'TEMP-' . strtoupper(bin2hex(random_bytes(5))),
                    'nik' => $validated['nik'],
                    'name' => $source->name ?: 'Karyawan ' . $source->pin,
                    'employment_status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $employeeNo = 'EMP-' . str_pad((string) $employeeId, 6, '0', STR_PAD_LEFT);
                DB::table('employees')->where('id', $employeeId)->update([
                    'employee_no' => $employeeNo,
                    'updated_at' => now(),
                ]);
                $employeeIdToLink = $employeeId;
            } else {
                $employeeIdToLink = $employee->id;
            }

            $activeRegistration = DB::table('employee_device_registrations')
                ->where('cloud_id', $source->cloud_id)
                ->where('pin', $source->pin)
                ->where('is_active', true)
                ->whereNull('valid_until')
                ->first();

            if ($activeRegistration && (int) $activeRegistration->employee_id !== (int) $employeeIdToLink) {
                throw ValidationException::withMessages([
                    'nik' => 'PIN pada mesin ini sudah terhubung ke karyawan lain.',
                ]);
            }

            if (! $activeRegistration) {
                DB::table('employee_device_registrations')->insert([
                    'employee_id' => $employeeIdToLink,
                    'cloud_id' => $source->cloud_id,
                    'pin' => $source->pin,
                    'valid_from' => now()->toDateString(),
                    'valid_until' => null,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        unset($this->nikInputs[$sourceId]);

        Notification::make()
            ->title('Data mesin berhasil dihubungkan ke master karyawan.')
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        $sync = app(FreedomFingerEmployeeSynchronizer::class)->sync();

        $machineEmployees = DB::table('freedom_finger_employees as source')
            ->leftJoin('employee_device_registrations as registration', function ($join) {
                $join->on('registration.cloud_id', '=', 'source.cloud_id')
                    ->on('registration.pin', '=', 'source.pin')
                    ->where('registration.is_active', true)
                    ->whereNull('registration.valid_until');
            })
            ->leftJoin('employees', 'employees.id', '=', 'registration.employee_id')
            ->select([
                'source.id',
                'source.pin',
                'source.name as machine_name',
                'source.cloud_id',
                'source.last_synced_at',
                'employees.id as employee_id',
                'employees.employee_no',
                'employees.nik',
                'employees.name as employee_name',
            ])
            ->orderBy('source.name')
            ->orderBy('source.pin')
            ->get();

        $employees = DB::table('employees')->orderBy('name')->get();

        return [
            'machineEmployees' => $machineEmployees,
            'employees' => $employees,
            'totalMachineEmployees' => $machineEmployees->count(),
            'totalEmployees' => $employees->count(),
            'syncedCount' => $sync['synced'],
            'syncSuccess' => $sync['success'],
            'syncMessage' => $sync['message'],
        ];
    }
}
