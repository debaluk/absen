<?php

namespace App\\Filament\\Pages;

use BackedEnum;
use Filament\\Pages\\Page;
use Illuminate\\Support\\Facades\\DB;
use UnitEnum;

class AttendancePerformanceReport extends Page
{
    protected string $view = 'filament.pages.attendance-performance-report';

    protected static ?string $slug = 'performa-kehadiran';
    protected static ?string $title = 'Performa Kehadiran';
    protected static ?string $navigationLabel = 'Performa Kehadiran';
    protected static string | UnitEnum | null $navigationGroup = 'Absensi';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?int $navigationSort = 5;

    public string $filterMonth = '';
    public string $filterWorkLocationId = '';
    public string $filterDepartmentId = '';
    public string $filterPositionId = '';
    public string $filterName = '';

    public function mount(): void
    {
        $this->filterMonth = now()->format('Y-m');
    }

    protected function getViewData(): array
    {
        $month = preg_match('/^\\d{4}-\\d{2}$/', $this->filterMonth)
            ? $this->filterMonth
            : now()->format('Y-m');

        $employees = DB::table('employees')
            ->leftJoin('work_locations', 'work_locations.id', '=', 'employees.work_location_id')
            ->leftJoin('positions', 'positions.id', '=', 'employees.position_id')
            ->where('employees.employment_status', 'active')
            ->where('employees.include_in_reports', true)
            ->when($this->filterWorkLocationId !== '', fn ($query) => $query->where('employees.work_location_id', $this->filterWorkLocationId))
            ->when($this->filterDepartmentId !== '', function ($query) {
                $query->whereExists(function ($departmentQuery) {
                    $departmentQuery->selectRaw('1')
                        ->from('department_positions')
                        ->whereColumn('department_positions.position_id', 'employees.position_id')
                        ->where('department_positions.department_id', $this->filterDepartmentId);
                });
            })
            ->when($this->filterPositionId !== '', fn ($query) => $query->where('employees.position_id', $this->filterPositionId))
            ->when(trim($this->filterName) !== '', fn ($query) => $query->where('employees.name', 'like', '%' . trim($this->filterName) . '%'))
            ->orderBy('work_locations.name')
            ->orderBy('employees.name')
            ->get([
                'employees.id',
                'employees.employee_no',
                'employees.name',
                'employees.position_id',
                'work_locations.name as work_location_name',
                'positions.name as position_name',
            ]);

        return [
            'month' => $month,
            'monthLabel' => \\Illuminate\\Support\\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y'),
            'employees' => $employees,
            'workLocations' => DB::table('work_locations')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => DB::table('departments')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'positions' => DB::table('positions')->orderBy('name')->get(['id', 'name']),
        ];
    }
}
