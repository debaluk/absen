<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.pages.dashboard';

    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static string | UnitEnum | null $navigationGroup = 'Absensi';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 1;

    public string $workLocationId = '';

    public string $attendanceDate = '';

    public function mount(): void
    {
        $this->attendanceDate = today()->toDateString();
    }

    public function updatedWorkLocationId(): void
    {
        // Filter dashboard langsung diperbarui oleh Livewire.
    }

    public function updatedAttendanceDate(): void
    {
        try {
            $date = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $this->attendanceDate);
            if (! $date || $date->format('Y-m-d') !== $this->attendanceDate || $date->greaterThan(today())) {
                $this->attendanceDate = today()->toDateString();
            }
        } catch (\Throwable) {
            $this->attendanceDate = today()->toDateString();
        }
    }

    protected function getViewData(): array
    {
        $today = now()->toDateString();

        $employees = DB::table('employees')
            ->when($this->workLocationId !== '', fn ($query) => $query->where('work_location_id', $this->workLocationId));

        $totalEmployees = (clone $employees)->count();
        $activeEmployees = (clone $employees)->where('employment_status', 'active')->count();
        $inactiveEmployees = (clone $employees)->where('employment_status', 'inactive')->count();

        // Data status dan ringkasan absensi mengikuti tanggal yang dipilih.
        $attendanceDate = $this->attendanceDate ?: $today;
        $attendanceCarbon = \Illuminate\Support\Carbon::parse($attendanceDate);
        $attendanceEmployees = DB::table('employees')
            ->leftJoin('work_locations', 'work_locations.id', '=', 'employees.work_location_id')
            ->where('employees.employment_status', 'active')
            ->when($this->workLocationId !== '', fn ($query) => $query->where('employees.work_location_id', $this->workLocationId))
            ->orderBy('work_locations.name')
            ->orderBy('employees.name')
            ->get([
                'employees.id', 'employees.employee_no', 'employees.name',
                'employees.attendance_start_date', 'employees.work_location_id',
                'employees.machine_user_cloud_id', 'employees.machine_user_pin',
                'work_locations.name as work_location_name',
            ]);

        $attendanceIds = $attendanceEmployees->pluck('id');
        $attendanceScans = DB::table('logs')
            ->join('employees', function ($join) {
                $join->on('employees.machine_user_cloud_id', '=', 'logs.cloud_id')
                    ->on('employees.machine_user_pin', '=', 'logs.pin');
            })
            ->whereIn('employees.id', $attendanceIds)
            ->whereBetween('logs.scan_date', [$attendanceDate . ' 00:00:00', $attendanceDate . ' 23:59:59'])
            ->orderBy('logs.scan_date')
            ->get(['employees.id as employee_id', 'logs.scan_date'])
            ->groupBy('employee_id');

        $attendanceLeaves = DB::table('leaves')
            ->whereIn('employee_id', $attendanceIds)
            ->whereDate('from', '<=', $attendanceDate)
            ->whereDate('to', '>=', $attendanceDate)
            ->get(['employee_id', 'kind'])
            ->keyBy('employee_id');

        $attendanceCorrections = DB::table('attendance_corrections')
            ->whereIn('employee_id', $attendanceIds)
            ->whereDate('date', $attendanceDate)
            ->get(['employee_id', 'check_in', 'check_out'])
            ->keyBy('employee_id');

        $scheduleSettings = DB::table('work_schedule_settings')->where('id', 1)->first();
        $scheduleDays = $scheduleSettings ? json_decode($scheduleSettings->days, true) : [];
        $lateTolerance = (int) ($scheduleSettings->tolerance ?? 15);
        $minGap = (int) ($scheduleSettings->min_gap ?? 60);
        $weekday = (int) $attendanceCarbon->dayOfWeek;
        $daySchedule = $scheduleDays[$weekday] ?? null;
        $isScheduledDay = is_array($daySchedule) && ! empty($daySchedule) && empty($daySchedule['free']);

        $holidayRows = DB::table('holidays')->whereDate('date', $attendanceDate)->get(['id']);
        $holidayScopes = DB::table('holiday_work_locations')
            ->whereIn('holiday_id', $holidayRows->pluck('id'))
            ->get(['holiday_id', 'work_location_id'])
            ->groupBy('holiday_id');
        $isGlobalHoliday = $holidayRows->contains(fn ($holiday) => $holidayScopes->get($holiday->id, collect())->isEmpty());
        $holidayLocationIds = $holidayScopes->flatten(1)->pluck('work_location_id')->map(fn ($id) => (int) $id)->unique();

        $attendanceSummary = [
            'hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0,
            'cuti' => 0, 'dinas' => 0, 'alpa' => 0, 'belum_absen' => 0,
            'sudah_absen' => 0, 'pulang' => 0, 'libur' => 0,
            'belum_wajib' => 0, 'belum_waktunya' => 0,
        ];
        $attendanceRows = [];

        foreach ($attendanceEmployees as $employee) {
            $scans = $attendanceScans->get($employee->id, collect())->pluck('scan_date')->values();
            $checkIn = $scans->first() ? \Illuminate\Support\Carbon::parse($scans->first())->format('H:i') : null;
            $checkOut = $scans->count() > 1 ? \Illuminate\Support\Carbon::parse($scans->last())->format('H:i') : null;

            if ($checkIn && $checkOut
                && (strtotime($attendanceDate . ' ' . $checkOut) - strtotime($attendanceDate . ' ' . $checkIn)) < $minGap * 60) {
                $checkOut = null;
            }

            $correction = $attendanceCorrections->get($employee->id);
            if ($correction?->check_in) {
                $checkIn = substr($correction->check_in, 0, 5);
            }
            if ($correction?->check_out) {
                $checkOut = substr($correction->check_out, 0, 5);
            }

            $hasHoliday = $isGlobalHoliday || $holidayLocationIds->contains((int) $employee->work_location_id);
            $isWorkingDay = $isScheduledDay && ! $hasHoliday;
            $hasAttendanceObligation = $employee->attendance_start_date
                && $employee->attendance_start_date <= $attendanceDate;
            $leaveKind = strtolower((string) ($attendanceLeaves->get($employee->id)->kind ?? ''));
            $status = 'libur';

            if (! $hasAttendanceObligation) {
                $status = 'belum_wajib';
            } elseif (in_array($leaveKind, ['izin', 'sakit', 'cuti', 'dinas'], true)) {
                $status = $leaveKind;
            } elseif (! $isWorkingDay) {
                $status = 'libur';
            } elseif ($checkIn || $checkOut) {
                $late = false;
                if ($checkIn && ! empty($daySchedule['start'])) {
                    $late = strtotime($attendanceDate . ' ' . $checkIn)
                        > strtotime($attendanceDate . ' ' . $daySchedule['start']) + ($lateTolerance * 60);
                }
                $status = $late ? 'terlambat' : 'hadir';
                $attendanceSummary['sudah_absen']++;
                if ($checkOut) {
                    $attendanceSummary['pulang']++;
                }
            } elseif ($attendanceDate < $today) {
                $status = 'alpa';
            } elseif ($attendanceDate === $today) {
                $status = 'belum_absen';
            } else {
                $status = 'belum_waktunya';
            }

            if (array_key_exists($status, $attendanceSummary)) {
                $attendanceSummary[$status]++;
            }

            $attendanceRows[] = [
                'employee' => $employee,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'status' => $status,
            ];
        }

        $attendanceStatusKeys = ['hadir', 'terlambat', 'izin', 'sakit', 'cuti', 'dinas', 'alpa', 'belum_absen'];
        $attendanceStatusTotal = collect($attendanceStatusKeys)->sum(fn ($key) => $attendanceSummary[$key]);
        $attendanceColors = [
            'hadir' => '#10b981', 'terlambat' => '#f59e0b', 'izin' => '#3b82f6',
            'sakit' => '#f97316', 'cuti' => '#8b5cf6', 'dinas' => '#06b6d4',
            'alpa' => '#f43f5e', 'belum_absen' => '#cbd5e1',
        ];
        $pieParts = [];
        $pieOffset = 0;
        foreach ($attendanceStatusKeys as $key) {
            $percentage = $attendanceStatusTotal > 0
                ? ($attendanceSummary[$key] / $attendanceStatusTotal) * 100
                : 0;
            if ($percentage > 0) {
                $pieParts[] = $attendanceColors[$key] . ' ' . round($pieOffset, 2) . '% ' . round($pieOffset + $percentage, 2) . '%';
                $pieOffset += $percentage;
            }
        }
        $attendancePie = $pieParts
            ? 'conic-gradient(' . implode(', ', $pieParts) . ')'
            : 'conic-gradient(#e2e8f0 0 100%)';

        $todayLogsQuery = DB::table('logs')
            ->leftJoin('employees', function ($join) {
                $join->on('employees.machine_user_cloud_id', '=', 'logs.cloud_id')
                    ->on('employees.machine_user_pin', '=', 'logs.pin');
            })
            ->whereDate('logs.scan_date', $attendanceDate)
            ->when($this->workLocationId !== '', function ($query) {
                $query->where('employees.work_location_id', $this->workLocationId);
            });

        $todayScans = (clone $todayLogsQuery)->count('logs.id');

        $hourlyTotals = (clone $todayLogsQuery)
            ->selectRaw('HOUR(logs.scan_date) as scan_hour, COUNT(logs.id) as total')
            ->groupByRaw('HOUR(logs.scan_date)')
            ->pluck('total', 'scan_hour');

        $hourlyScans = collect(range(0, 23))->map(fn ($hour) => [
            'hour' => sprintf('%02d', $hour),
            'total' => (int) ($hourlyTotals[$hour] ?? 0),
        ]);

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
            'attendanceDate' => $attendanceDate,
            'attendanceDateLabel' => $attendanceCarbon->translatedFormat('l, d F Y'),
            'attendanceSummary' => $attendanceSummary,
            'attendanceRows' => $attendanceRows,
            'attendanceStatusTotal' => $attendanceStatusTotal,
            'attendancePie' => $attendancePie,
        ];
    }
}
