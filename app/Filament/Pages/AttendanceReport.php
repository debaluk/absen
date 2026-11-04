<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class AttendanceReport extends Page
{
    protected string $view = 'filament.pages.attendance-report';
    protected static ?string $title = 'Rekap Absensi';
    protected static ?string $navigationLabel = 'Rekap';
    protected static string | UnitEnum | null $navigationGroup = 'Absensi';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?int $navigationSort = 3;

    public string $filterMonth = '';
    public string $filterWorkLocationId = '';
    public string $filterName = '';

    public function mount(): void
    {
        $this->filterMonth = now()->format('Y-m');
    }

    protected function getViewData(): array
    {
        $month = preg_match('/^\d{4}-\d{2}$/', $this->filterMonth)
            ? $this->filterMonth
            : now()->format('Y-m');
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));
        $today = today()->toDateString();
        $periodEnd = min($end, $today);

        $employees = DB::table('employees')
            ->leftJoin('work_locations', 'work_locations.id', '=', 'employees.work_location_id')
            ->where('employees.employment_status', 'active')
            ->when($this->filterWorkLocationId !== '', fn ($query) => $query->where('employees.work_location_id', $this->filterWorkLocationId))
            ->when(trim($this->filterName) !== '', fn ($query) => $query->where('employees.name', 'like', '%' . trim($this->filterName) . '%'))
            ->orderBy('work_locations.name')
            ->orderBy('employees.name')
            ->get([
                'employees.id',
                'employees.employee_no',
                'employees.name',
                'employees.attendance_start_date',
                'employees.work_location_id',
                'work_locations.name as work_location_name',
            ]);

        $employeeIds = $employees->pluck('id');
        $scanMap = [];
        if ($employeeIds->isNotEmpty() && $periodEnd >= $start) {
            $scans = DB::table('logs')
                ->join('employees', function ($join) {
                    $join->on('employees.machine_user_cloud_id', '=', 'logs.cloud_id')
                        ->on('employees.machine_user_pin', '=', 'logs.pin');
                })
                ->whereIn('employees.id', $employeeIds)
                ->whereBetween('logs.scan_date', [$start . ' 00:00:00', $periodEnd . ' 23:59:59'])
                ->orderBy('logs.scan_date')
                ->get(['employees.id as employee_id', 'logs.scan_date']);

            foreach ($scans as $scan) {
                $date = substr($scan->scan_date, 0, 10);
                $scanMap[$scan->employee_id][$date][] = substr($scan->scan_date, 11, 5);
            }
        }

        $leaveMap = [];
        if ($employeeIds->isNotEmpty() && $periodEnd >= $start) {
            $leaves = DB::table('leaves')
                ->whereIn('employee_id', $employeeIds)
                ->whereDate('from', '<=', $periodEnd)
                ->whereDate('to', '>=', $start)
                ->get(['employee_id', 'from', 'to', 'kind']);

            foreach ($leaves as $leave) {
                $from = max($start, $leave->from);
                $to = min($periodEnd, $leave->to);
                for ($date = $from; $date <= $to; $date = date('Y-m-d', strtotime($date . ' +1 day'))) {
                    $leaveMap[$leave->employee_id][$date] = strtolower($leave->kind);
                }
            }
        }

        $correctionMap = collect();
        if ($employeeIds->isNotEmpty() && $periodEnd >= $start) {
            $correctionMap = DB::table('attendance_corrections')
                ->whereIn('employee_id', $employeeIds)
                ->whereBetween('date', [$start, $periodEnd])
                ->get(['employee_id', 'date', 'check_in', 'check_out'])
                ->keyBy(fn ($row) => $row->employee_id . '|' . $row->date);
        }

        $settings = DB::table('work_schedule_settings')->where('id', 1)->first();
        $scheduleDays = $settings ? json_decode($settings->days, true) : [];
        $minGap = (int) ($settings->min_gap ?? 60);

        $holidays = DB::table('holidays')->whereBetween('date', [$start, $end])->get(['id', 'date']);
        $holidayLocations = DB::table('holiday_work_locations')
            ->get(['holiday_id', 'work_location_id'])
            ->groupBy('holiday_id');
        $holidayMap = [];
        foreach ($holidays as $holiday) {
            $holidayMap[$holiday->date] = $holidayLocations->get($holiday->id, collect())
                ->pluck('work_location_id')->map(fn ($id) => (int) $id)->all();
        }

        $rows = [];

        foreach ($employees as $employee) {
            $counts = [
                'hadir' => 0,
                'izin' => 0,
                'sakit' => 0,
                'cuti' => 0,
                'dinas' => 0,
                'alpa' => 0,
            ];
            $firstAttendanceDate = $employee->attendance_start_date;
            $effectiveDays = 0;

            // Hitung hari kerja efektif untuk satu bulan penuh, mulai dari kewajiban absensi karyawan.
            $effectiveStart = $firstAttendanceDate ? max($start, $firstAttendanceDate) : $end;
            for ($effectiveDate = $effectiveStart; $effectiveDate <= $end; $effectiveDate = date('Y-m-d', strtotime($effectiveDate . ' +1 day'))) {
                $effectiveWeekday = (int) date('w', strtotime($effectiveDate));
                $effectiveScheduled = is_array($scheduleDays[$effectiveWeekday] ?? null) && ! empty($scheduleDays[$effectiveWeekday]);
                $effectiveHolidayScopes = $holidayMap[$effectiveDate] ?? null;
                $effectiveHoliday = $effectiveHolidayScopes !== null && (
                    count($effectiveHolidayScopes) === 0 ||
                    in_array((int) $employee->work_location_id, $effectiveHolidayScopes, true)
                );

                if ($effectiveScheduled && ! $effectiveHoliday) {
                    $effectiveDays++;
                }
            }

            for ($date = $start; $date <= $periodEnd; $date = date('Y-m-d', strtotime($date . ' +1 day'))) {
                $weekday = (int) date('w', strtotime($date));
                $scheduled = is_array($scheduleDays[$weekday] ?? null) && ! empty($scheduleDays[$weekday]);
                $holidayScopes = $holidayMap[$date] ?? null;
                $holiday = $holidayScopes !== null && (
                    count($holidayScopes) === 0 ||
                    in_array((int) $employee->work_location_id, $holidayScopes, true)
                );

                $times = $scanMap[$employee->id][$date] ?? [];
                $checkIn = $times[0] ?? null;
                $checkOut = count($times) > 1 ? $times[count($times) - 1] : null;
                if ($checkIn && $checkOut
                    && (strtotime($date . ' ' . $checkOut) - strtotime($date . ' ' . $checkIn)) < $minGap * 60) {
                    $checkOut = null;
                }

                $correction = $correctionMap->get($employee->id . '|' . $date);
                if ($correction?->check_in) {
                    $checkIn = substr($correction->check_in, 0, 5);
                }
                if ($correction?->check_out) {
                    $checkOut = substr($correction->check_out, 0, 5);
                }

                $status = $leaveMap[$employee->id][$date] ?? (($checkIn || $checkOut) ? 'hadir' : null);
                if ($status === null && $scheduled && ! $holiday
                    && $firstAttendanceDate && $firstAttendanceDate <= $date) {
                    $status = 'alpa';
                }

                if ($status !== null && array_key_exists($status, $counts)) {
                    $counts[$status]++;
                }
            }

            $rows[] = [
                'employee' => $employee,
                'counts' => $counts,
                'effective_days' => $effectiveDays,
            ];
        }

        return [
            'rows' => $rows,
            'monthLabel' => \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y'),
            'workLocations' => DB::table('work_locations')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'periodEnd' => $periodEnd,
        ];
    }
}
