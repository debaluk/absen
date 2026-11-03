<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class AttendanceHistory extends Page
{
    protected string $view = 'filament.pages.attendance-history';
    protected static ?string $title = 'Riwayat Absensi';
    protected static ?string $navigationLabel = 'Riwayat';
    protected static string | UnitEnum | null $navigationGroup = 'Absensi';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clock';
    protected static ?int $navigationSort = 2;

    public string $filterMonth = '';
    public string $filterWorkLocationId = '';
    public bool $showEditModal = false;
    public ?int $editingEmployeeId = null;
    public string $editingDate = '';
    public string $editingEmployeeName = '';
    public ?string $checkIn = null;
    public ?string $checkOut = null;
    public string $correctionReason = '';

    public function mount(): void
    {
        $this->filterMonth = now()->format('Y-m');
    }

    public function refreshHistory(): void
    {
        // Render ulang agar scan yang sudah disinkronkan terbaca otomatis.
    }

    public function editAttendance(int $employeeId, string $date): void
    {
        $employee = DB::table('employees')->where('id', $employeeId)
            ->where('employment_status', 'active')->first(['id', 'name']);
        abort_unless($employee, 404);

        $corrections = DB::table('corrections')
            ->where('pin', DB::table('employees')->where('id', $employeeId)->value('machine_user_pin'))
            ->where('date', $date)
            ->orderBy('time')
            ->get(['time', 'reason']);

        $times = DB::table('logs')
            ->join('employees', function ($join) {
                $join->on('employees.machine_user_cloud_id', '=', 'logs.cloud_id')
                    ->on('employees.machine_user_pin', '=', 'logs.pin');
            })
            ->where('employees.id', $employeeId)
            ->whereDate('logs.scan_date', $date)
            ->orderBy('logs.scan_date')
            ->pluck('logs.scan_date')
            ->map(fn ($time) => substr($time, 11, 5))
            ->values();

        $first = $times->first();
        $last = $times->count() > 1 ? $times->last() : null;
        $minGap = (int) (DB::table('work_schedule_settings')->where('id', 1)->value('min_gap') ?? 60);
        if ($first && $last && (strtotime($date . ' ' . $last) - strtotime($date . ' ' . $first)) < $minGap * 60) {
            $last = null;
        }

        $this->editingEmployeeId = $employeeId;
        $this->editingDate = $date;
        $this->editingEmployeeName = $employee->name;
        if ($corrections->isNotEmpty()) {
            $first = substr($corrections->first()->time, 0, 5);
            $last = $corrections->count() > 1 ? substr($corrections->last()->time, 0, 5) : null;
        }

        $this->checkIn = $first;
        $this->checkOut = $last;
        $this->correctionReason = $corrections->pluck('reason')->filter()->unique()->implode('; ');
        $this->showEditModal = true;
    }

    public function saveAttendanceCorrection(): void
    {
        $data = $this->validate([
            'editingEmployeeId' => ['required', 'integer', 'exists:employees,id'],
            'editingDate' => ['required', 'date'],
            'checkIn' => ['nullable', 'date_format:H:i'],
            'checkOut' => ['nullable', 'date_format:H:i'],
            'correctionReason' => ['required', 'string', 'max:1000'],
        ], ['correctionReason.required' => 'Alasan koreksi wajib diisi.']);

        abort_unless(DB::table('employees')->where('id', $data['editingEmployeeId'])
            ->where('employment_status', 'active')->exists(), 404);

        $employee = DB::table('employees')->where('id', $data['editingEmployeeId'])
            ->first(['machine_user_pin']);
        abort_unless($employee?->machine_user_pin, 422, 'PIN mesin karyawan belum ditautkan.');

        DB::transaction(function () use ($data, $employee): void {
            DB::table('corrections')
                ->where('pin', $employee->machine_user_pin)
                ->where('date', $data['editingDate'])
                ->delete();

            foreach (array_filter([$data['checkIn'], $data['checkOut']]) as $time) {
                DB::table('corrections')->insert([
                    'id' => (string) Str::uuid(),
                    'pin' => $employee->machine_user_pin,
                    'date' => $data['editingDate'],
                    'time' => $time,
                    'reason' => trim($data['correctionReason']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $this->closeEditModal();
        Notification::make()->title('Koreksi absensi berhasil disimpan.')->success()->send();
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->reset(['editingEmployeeId', 'editingDate', 'editingEmployeeName', 'checkIn', 'checkOut', 'correctionReason']);
        $this->resetValidation();
    }

    protected function getViewData(): array
    {
        $month = preg_match('/^\d{4}-\d{2}$/', $this->filterMonth) ? $this->filterMonth : now()->format('Y-m');
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));
        $today = today()->toDateString();

        $employees = DB::table('employees')
            ->leftJoin('work_locations', 'work_locations.id', '=', 'employees.work_location_id')
            ->where('employees.employment_status', 'active')
            ->when($this->filterWorkLocationId !== '', fn ($q) => $q->where('employees.work_location_id', $this->filterWorkLocationId))
            ->orderBy('work_locations.name')->orderBy('employees.name')
            ->get([
                'employees.id', 'employees.employee_no', 'employees.name',
                'employees.attendance_start_date', 'employees.work_location_id',
                'work_locations.name as work_location_name',
                'employees.machine_user_cloud_id', 'employees.machine_user_pin',
            ]);

        $days = [];
        for ($time = strtotime($start); $time <= strtotime($end); $time = strtotime('+1 day', $time)) {
            $days[] = date('Y-m-d', $time);
        }

        $ids = $employees->pluck('id');
        $scanMap = [];
        $scans = DB::table('logs')->join('employees', function ($join) {
            $join->on('employees.machine_user_cloud_id', '=', 'logs.cloud_id')
                ->on('employees.machine_user_pin', '=', 'logs.pin');
        })->whereIn('employees.id', $ids)
            ->whereBetween('logs.scan_date', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->orderBy('logs.scan_date')
            ->get(['employees.id as employee_id', 'logs.scan_date']);

        foreach ($scans as $scan) {
            $scanMap[$scan->employee_id][substr($scan->scan_date, 0, 10)][] = substr($scan->scan_date, 11, 5);
        }

        $leaveMap = [];
        $leaves = DB::table('leaves')->whereIn('employee_id', $ids)
            ->whereDate('from', '<=', $end)->whereDate('to', '>=', $start)
            ->get(['employee_id', 'from', 'to', 'kind']);
        foreach ($leaves as $leave) {
            for ($date = max($start, $leave->from); $date <= min($end, $leave->to); $date = date('Y-m-d', strtotime($date . ' +1 day'))) {
                $leaveMap[$leave->employee_id][$date] = $leave->kind;
            }
        }

        $pins = $employees->pluck('machine_user_pin')->filter()->unique()->values();
        $correctionMap = DB::table('corrections')->whereIn('pin', $pins)
            ->whereBetween('date', [$start, $end])
            ->orderBy('time')
            ->get(['pin', 'date', 'time', 'reason'])
            ->groupBy(fn ($row) => $row->pin . '|' . $row->date);

        $settings = DB::table('work_schedule_settings')->where('id', 1)->first();
        $scheduleDays = $settings ? json_decode($settings->days, true) : [];
        $minGap = (int) ($settings->min_gap ?? 60);

        $holidays = DB::table('holidays')->whereBetween('date', [$start, $end])->get();
        $holidayLocations = DB::table('holiday_work_locations')->get()->groupBy('holiday_id');
        $holidayMap = [];
        $globalHolidayMap = [];
        foreach ($holidays as $holiday) {
            $locations = $holidayLocations->get($holiday->id, collect())->pluck('work_location_id')->all();
            $holidayMap[$holiday->date] = $locations;
            if (count($locations) === 0) {
                $globalHolidayMap[$holiday->date] = true;
            }
        }

        $dayOffMap = [];
        foreach ($days as $date) {
            $weekday = (int) date('w', strtotime($date));
            $scheduled = is_array($scheduleDays[$weekday] ?? null) && ! empty($scheduleDays[$weekday]);
            $locationHoliday = $holidayMap[$date] ?? [];
            $selectedLocationHoliday = $this->filterWorkLocationId !== ''
                && in_array((int) $this->filterWorkLocationId, array_map('intval', $locationHoliday), true);

            $dayOffMap[$date] = ! $scheduled || isset($globalHolidayMap[$date]) || $selectedLocationHoliday;
        }

        $matrix = [];
        foreach ($employees as $employee) {
            foreach ($days as $date) {
                $times = $scanMap[$employee->id][$date] ?? [];
                $in = $times[0] ?? null;
                $out = count($times) > 1 ? $times[count($times) - 1] : null;
                if ($in && $out && (strtotime($date . ' ' . $out) - strtotime($date . ' ' . $in)) < $minGap * 60) {
                    $out = null;
                }

                $employeeCorrections = $employee->machine_user_pin
                    ? $correctionMap->get($employee->machine_user_pin . '|' . $date, collect())
                    : collect();
                if ($employeeCorrections->isNotEmpty()) {
                    $in = substr($employeeCorrections->first()->time, 0, 5);
                    $out = $employeeCorrections->count() > 1
                        ? substr($employeeCorrections->last()->time, 0, 5)
                        : null;
                }

                $leave = $leaveMap[$employee->id][$date] ?? null;
                $weekday = (int) date('w', strtotime($date));
                $scheduled = is_array($scheduleDays[$weekday] ?? null) && ! empty($scheduleDays[$weekday]);
                $holidayScope = $holidayMap[$date] ?? null;
                $holiday = $holidayScope !== null && (
                    count($holidayScope) === 0 ||
                    in_array((int) $employee->work_location_id, array_map('intval', $holidayScope), true)
                );

                $status = $leave ?: (($in || $out) ? 'hadir' : null);
                if ($status === null && $date < $today && $scheduled && ! $holiday
                    && $employee->attendance_start_date && $employee->attendance_start_date <= $date) {
                    $status = 'alpa';
                }

                $matrix[$employee->id][$date] = [
                    'check_in' => $in,
                    'check_out' => $out,
                    'status' => $status,
                    'is_day_off' => ! $scheduled || $holiday,
                ];
            }
        }

        return [
            'employees' => $employees,
            'days' => $days,
            'dayOffMap' => $dayOffMap,
            'matrix' => $matrix,
            'workLocations' => DB::table('work_locations')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'monthLabel' => \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y'),
        ];
    }
}
