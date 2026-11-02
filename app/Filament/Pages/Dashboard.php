<?php

namespace App\\Filament\\Pages;

use BackedEnum;
use Filament\\Pages\\Page;
use Illuminate\\Support\\Facades\\DB;
use UnitEnum;

class Dashboard extends Page
{
    protected string $view = 'filament.pages.dashboard';

    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static string | UnitEnum | null $navigationGroup = 'Absensi';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 1;

    public string $workLocationId = '';

    public function updatedWorkLocationId(): void
    {
        // Filter dashboard langsung diperbarui oleh Livewire.
    }

    protected function getViewData(): array
    {
        $today = now()->toDateString();

        $employees = DB::table('employees')
            ->when($this->workLocationId !== '', fn ($query) => $query->where('work_location_id', $this->workLocationId));

        $totalEmployees = (clone $employees)->count();
        $activeEmployees = (clone $employees)->where('employment_status', 'active')->count();
        $inactiveEmployees = (clone $employees)->where('employment_status', 'inactive')->count();

        $todayLogsQuery = DB::table('logs')
            ->leftJoin('employees', function ($join) {
                $join->on('employees.machine_user_cloud_id', '=', 'logs.cloud_id')
                    ->on('employees.machine_user_pin', '=', 'logs.pin');
            })
            ->whereDate('logs.scan_date', $today)
            ->when($this->workLocationId !== '', function ($query) {
                $query->where('employees.work_location_id', $this->workLocationId);
            });

        $todayScans = (clone $todayLogsQuery)->count('logs.id');

        $hourlyScans = collect(range(0, 23))->map(function ($hour) use ($todayLogsQuery) {
            return [
                'hour' => sprintf('%02d', $hour),
                'total' => (clone $todayLogsQuery)->whereRaw('HOUR(logs.scan_date) = ?', [$hour])->count('logs.id'),
            ];
        });

        $locationStats = DB::table('work_locations')
            ->leftJoin('employees', 'employees.work_location_id', '=', 'work_locations.id')
            ->when($this->workLocationId !== '', fn ($query) => $query->where('work_locations.id', $this->workLocationId))
            ->select('work_locations.id', 'work_locations.name')
            ->selectRaw('COUNT(employees.id) as total_employees')
            ->selectRaw("SUM(CASE WHEN employees.employment_status = 'active' THEN 1 ELSE 0 END) as active_employees")
            ->groupBy('work_locations.id', 'work_locations.name')
            ->orderByDesc('total_employees')
            ->get();

        $recentLogs = DB::table('logs')
            ->leftJoin('devices', 'devices.cloud_id', '=', 'logs.cloud_id')
            ->leftJoin('machine_users', function ($join) {
                $join->on('machine_users.cloud_id', '=', 'logs.cloud_id')
                    ->on('machine_users.pin', '=', 'logs.pin');
            })
            ->leftJoin('employees', function ($join) {
                $join->on('employees.machine_user_cloud_id', '=', 'logs.cloud_id')
                    ->on('employees.machine_user_pin', '=', 'logs.pin');
            })
            ->when($this->workLocationId !== '', fn ($query) => $query->where('employees.work_location_id', $this->workLocationId))
            ->select([
                'logs.id',
                'logs.scan_date',
                'logs.pin',
                'logs.status_scan',
                'logs.cloud_id',
                'devices.name as device_name',
                'machine_users.name as machine_user_name',
                'employees.name as employee_name',
            ])
            ->orderByDesc('logs.scan_date')
            ->orderByDesc('logs.id')
            ->limit(8)
            ->get();

        $devices = DB::table('devices')
            ->leftJoin('devices_location', 'devices_location.cloud_id', '=', 'devices.cloud_id')
            ->leftJoin('work_locations', 'work_locations.id', '=', 'devices_location.work_location_id')
            ->when($this->workLocationId !== '', fn ($query) => $query->where('devices_location.work_location_id', $this->workLocationId))
            ->select([
                'devices.cloud_id',
                'devices.name',
                'devices.last_activity',
                'work_locations.name as location_name',
            ])
            ->orderBy('devices.name')
            ->get();

        $syncEnabled = DB::table('application_settings')
            ->where('key', 'freedom_finger_auto_sync')
            ->value('value') === '1';

        return [
            'workLocations' => DB::table('work_locations')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'totalEmployees' => $totalEmployees,
            'activeEmployees' => $activeEmployees,
            'inactiveEmployees' => $inactiveEmployees,
            'todayScans' => $todayScans,
            'hourlyScans' => $hourlyScans,
            'maxHourlyScans' => max(1, (int) $hourlyScans->max('total')),
            'locationStats' => $locationStats,
            'recentLogs' => $recentLogs,
            'devices' => $devices,
            'syncEnabled' => $syncEnabled,
            'todayLabel' => now()->translatedFormat('l, d F Y'),
        ];
    }
}
