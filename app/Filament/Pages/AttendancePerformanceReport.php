<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
        $month = preg_match('/^\d{4}-\d{2}$/', $this->filterMonth)
            ? $this->filterMonth
            : now()->format('Y-m');
        $start = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($start));
        $today = today()->toDateString();
        $periodEnd = min($monthEnd, $today);

        $formula = DB::table('attendance_performance_formulas')
            ->whereIn('status', ['active', 'ended'])
            ->whereDate('effective_from', '<=', $periodEnd)
            ->where(function ($query) use ($start) {
                $query->whereNull('effective_until')
                    ->orWhereDate('effective_until', '>=', $start);
            })
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();

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
                'employees.attendance_start_date',
                'employees.work_location_id',
                'work_locations.name as work_location_name',
                'positions.name as position_name',
            ]);

        $employeeIds = $employees->pluck('id');
        $scanMap = [];
        $leaveMap = [];
        $correctionMap = collect();

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

            $correctionMap = DB::table('attendance_corrections')
                ->whereIn('employee_id', $employeeIds)
                ->whereBetween('date', [$start, $periodEnd])
                ->get(['employee_id', 'date', 'check_in', 'check_out'])
                ->keyBy(fn ($row) => $row->employee_id . '|' . $row->date);
        }

        $settings = DB::table('work_schedule_settings')->where('id', 1)->first();
        $scheduleDays = $settings ? json_decode($settings->days, true) : [];
        $minGap = (int) ($settings->min_gap ?? 60);

        $holidays = DB::table('holidays')->whereBetween('date', [$start, $periodEnd])->get(['id', 'date']);
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
                'hadir' => 0, 'izin' => 0, 'sakit' => 0, 'cuti' => 0,
                'dinas' => 0, 'alpa' => 0, 'late_minutes' => 0, 'early_leave_minutes' => 0,
            ];
            $scheduledDays = 0;
            $excludedDays = 0;
            $attendanceStart = $employee->attendance_start_date;

            if ($attendanceStart && $periodEnd >= $start) {
                $firstDate = max($start, $attendanceStart);
                for ($date = $firstDate; $date <= $periodEnd; $date = date('Y-m-d', strtotime($date . ' +1 day'))) {
                    $weekday = (int) date('w', strtotime($date));
                    $schedule = $scheduleDays[$weekday] ?? null;
                    $scheduled = is_array($schedule) && ! empty($schedule) && empty($schedule['free']);
                    $holidayScopes = $holidayMap[$date] ?? null;
                    $holiday = $holidayScopes !== null && (
                        count($holidayScopes) === 0
                        || in_array((int) $employee->work_location_id, $holidayScopes, true)
                    );

                    if (! $scheduled || $holiday) {
                        continue;
                    }

                    $scheduledDays++;
                    $leaveKind = $leaveMap[$employee->id][$date] ?? null;
                    if (in_array($leaveKind, ['cuti', 'sakit'], true)) {
                        $excludedDays++;
                    }
                }
            }

            for ($date = max($start, $attendanceStart ?: $start); $date <= $periodEnd; $date = date('Y-m-d', strtotime($date . ' +1 day'))) {
                $weekday = (int) date('w', strtotime($date));
                $schedule = $scheduleDays[$weekday] ?? null;
                $scheduled = is_array($schedule) && ! empty($schedule) && empty($schedule['free']);
                $holidayScopes = $holidayMap[$date] ?? null;
                $holiday = $holidayScopes !== null && (
                    count($holidayScopes) === 0
                    || in_array((int) $employee->work_location_id, $holidayScopes, true)
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

                $leave = $leaveMap[$employee->id][$date] ?? null;
                $hasAttendance = (bool) ($checkIn || $checkOut);
                $status = match ($leave) {
                    'cuti' => 'cuti',
                    'sakit' => 'sakit',
                    'dinas' => 'dinas',
                    'izin' => 'izin',
                    default => $hasAttendance ? 'hadir' : null,
                };

                if ($status === null && $scheduled && ! $holiday && $attendanceStart && $attendanceStart <= $date && $date < $today) {
                    $status = 'alpa';
                }

                if ($status !== null && array_key_exists($status, $counts)) {
                    $counts[$status]++;
                }

                if ($scheduled && $checkIn && ! empty($schedule['start'])) {
                    $late = (int) floor((strtotime($date . ' ' . $checkIn) - strtotime($date . ' ' . $schedule['start'])) / 60);
                    $counts['late_minutes'] += max(0, $late - (int) ($formula->late_tolerance_minutes ?? 0));
                }
                if ($scheduled && $checkOut && ! empty($schedule['end'])) {
                    $early = (int) floor((strtotime($date . ' ' . $schedule['end']) - strtotime($date . ' ' . $checkOut)) / 60);
                    $counts['early_leave_minutes'] += max(0, $early - (int) ($formula->early_leave_tolerance_minutes ?? 0));
                }
            }

            $effectiveDays = max(0, $scheduledDays - $excludedDays);
            $recognizedPresent = $counts['hadir'] + $counts['dinas'];
            $attendanceScore = $effectiveDays > 0
                ? min(100, ($recognizedPresent / $effectiveDays) * 100)
                : 0;

            $punctualityPenalty = $formula
                ? ($counts['late_minutes'] / max(1, (int) $formula->late_penalty_unit_minutes)) * (float) $formula->late_penalty_points
                : 0;
            $worktimePenalty = $formula
                ? ($counts['early_leave_minutes'] / max(1, (int) $formula->early_leave_penalty_unit_minutes)) * (float) $formula->early_leave_penalty_points
                : 0;
            $punctualityScore = max(0, 100 - $punctualityPenalty);
            $worktimeScore = max(0, 100 - $worktimePenalty);

            $finalScore = $formula
                ? ($attendanceScore * (float) $formula->attendance_weight / 100)
                    + ($punctualityScore * (float) $formula->punctuality_weight / 100)
                    + ($worktimeScore * (float) $formula->worktime_weight / 100)
                : null;

            $grade = $finalScore === null ? null : match (true) {
                $finalScore >= (float) $formula->very_good_min => 'Sangat Baik',
                $finalScore >= (float) $formula->good_min => 'Baik',
                $finalScore >= (float) $formula->fair_min => 'Cukup',
                default => 'Perlu Perbaikan',
            };

            $rows[] = [
                'employee' => $employee,
                'counts' => $counts,
                'scheduled_days' => $scheduledDays,
                'effective_days' => $effectiveDays,
                'attendance_score' => $attendanceScore,
                'punctuality_score' => $punctualityScore,
                'worktime_score' => $worktimeScore,
                'final_score' => $finalScore,
                'grade' => $grade,
            ];
        }

        return [
            'month' => $month,
            'monthLabel' => Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y'),
            'employees' => $employees,
            'rows' => $rows,
            'formula' => $formula,
            'periodEnd' => $periodEnd,
            'workLocations' => DB::table('work_locations')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => DB::table('departments')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'positions' => DB::table('positions')->orderBy('name')->get(['id', 'name']),
        ];
    }
}
