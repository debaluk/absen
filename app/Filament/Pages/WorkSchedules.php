<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use UnitEnum;

class WorkSchedules extends Page
{
    protected string $view = 'filament.pages.work-schedules';

    protected static ?string $slug = 'jadwal-kerja';
    protected static ?string $title = 'Jadwal Kerja';
    protected static ?string $navigationLabel = 'Jadwal Kerja';
    protected static string | UnitEnum | null $navigationGroup = 'Kelola';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?int $navigationSort = 2;

    public string $activeTab = 'jadwal';
    public ?int $scheduleId = null;
    public string $scheduleName = '';
    public string $scheduleColor = '#64748b';
    public array $days = [];
    public ?int $assignEmployeeId = null;
    public ?int $assignScheduleId = null;
    public string $assignStartDate = '';
    public ?int $lateToleranceMinutes = 0;
    public ?int $checkoutScanGapMinutes = 5;
    public ?int $overtimeThresholdMinutes = 30;

    private const DAY_ORDER = [1, 2, 3, 4, 5, 6, 0];
    private const DAY_NAMES = [0 => 'Minggu', 1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu'];

    public function mount(): void
    {
        $this->loadSettings();
        $default = DB::table('work_schedules')->where('is_default', true)->first();

        if (! $default) {
            $id = DB::table('work_schedules')->insertGetId([
                'name' => 'Jadwal Utama',
                'color' => '#64748b',
                'is_default' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->scheduleId = $id;
            $this->saveDefaultDays($id);
        } else {
            $this->scheduleId = $default->id;
            if (! DB::table('work_schedule_days')->where('work_schedule_id', $default->id)->exists()) {
                $this->saveDefaultDays((int) $default->id);
            }
        }

        $this->loadSchedule();
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['jadwal', 'penugasan', 'aturan'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function selectSchedule(int $id): void
    {
        if (DB::table('work_schedules')->where('id', $id)->where('is_active', true)->exists()) {
            $this->scheduleId = $id;
            $this->loadSchedule();
        }
    }

    public function newSchedule(): void
    {
        $this->scheduleId = null;
        $this->scheduleName = '';
        $this->scheduleColor = '#64748b';
        $this->days = $this->defaultDays();
    }

    public function saveSchedule(): void
    {
        $this->validate([
            'scheduleName' => ['required', 'string', 'max:100'],
            'scheduleColor' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'days' => ['required', 'array', 'size:7'],
        ]);

        foreach ($this->days as $day => $data) {
            if (! empty($data['is_working']) && empty($data['is_free'])) {
                if (empty($data['start_time']) || empty($data['end_time']) || $data['end_time'] <= $data['start_time']) {
                    $this->addError("days.{$day}.end_time", 'Jam pulang harus setelah jam masuk.');
                    return;
                }
            }
        }

        DB::transaction(function (): void {
            $data = [
                'name' => trim($this->scheduleName),
                'color' => $this->scheduleColor,
                'updated_at' => now(),
            ];

            if ($this->scheduleId) {
                DB::table('work_schedules')->where('id', $this->scheduleId)->update($data);
            } else {
                $data += ['is_default' => false, 'is_active' => true, 'created_at' => now()];
                $this->scheduleId = DB::table('work_schedules')->insertGetId($data);
            }

            foreach ($this->days as $day => $values) {
                DB::table('work_schedule_days')->updateOrInsert(
                    ['work_schedule_id' => $this->scheduleId, 'day_of_week' => (int) $day],
                    [
                        'is_working' => (bool) ($values['is_working'] ?? false),
                        'start_time' => ! empty($values['is_free']) || empty($values['is_working']) ? null : ($values['start_time'] ?: null),
                        'end_time' => ! empty($values['is_free']) || empty($values['is_working']) ? null : ($values['end_time'] ?: null),
                        'is_free' => (bool) ($values['is_free'] ?? false),
                        'overtime_enabled' => (bool) ($values['overtime_enabled'] ?? false),
                        'max_overtime_hours' => (float) ($values['max_overtime_hours'] ?? 0),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        });

        Notification::make()->title('Jadwal kerja berhasil disimpan.')->success()->send();
        $this->loadSchedule();
    }

    public function deleteSchedule(int $id): void
    {
        $schedule = DB::table('work_schedules')->find($id);
        if (! $schedule || $schedule->is_default) {
            Notification::make()->title('Jadwal utama tidak dapat dihapus.')->danger()->send();
            return;
        }

        if (DB::table('employee_work_schedule_assignments')->where('work_schedule_id', $id)->whereNull('end_date')->exists()) {
            Notification::make()->title('Jadwal masih digunakan karyawan. Akhiri penugasannya terlebih dahulu.')->danger()->send();
            return;
        }

        DB::table('work_schedules')->where('id', $id)->update(['is_active' => false, 'updated_at' => now()]);
        $this->scheduleId = (int) DB::table('work_schedules')->where('is_default', true)->value('id');
        $this->loadSchedule();
        Notification::make()->title('Jadwal berhasil dinonaktifkan.')->success()->send();
    }

    public function saveAssignment(): void
    {
        $this->validate([
            'assignEmployeeId' => ['required', 'integer', Rule::exists('employees', 'id')],
            'assignScheduleId' => ['required', 'integer', Rule::exists('work_schedules', 'id')->where('is_active', true)],
            'assignStartDate' => ['required', 'date'],
        ]);

        $currentAssignment = DB::table('employee_work_schedule_assignments')
            ->where('employee_id', $this->assignEmployeeId)
            ->whereNull('end_date')
            ->first();

        if ($currentAssignment && $this->assignStartDate <= $currentAssignment->start_date) {
            $this->addError('assignStartDate', 'Tanggal mulai harus setelah tanggal mulai jadwal yang sedang berlaku.');
            return;
        }

        DB::transaction(function () use ($currentAssignment): void {
            if ($currentAssignment) {
                DB::table('employee_work_schedule_assignments')
                    ->where('id', $currentAssignment->id)
                    ->update(['end_date' => date('Y-m-d', strtotime($this->assignStartDate . ' -1 day')), 'updated_at' => now()]);
            }

            DB::table('employee_work_schedule_assignments')->insert([
                'employee_id' => $this->assignEmployeeId,
                'work_schedule_id' => $this->assignScheduleId,
                'start_date' => $this->assignStartDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        Notification::make()->title('Penugasan jadwal berhasil disimpan.')->success()->send();
        $this->reset(['assignEmployeeId', 'assignScheduleId', 'assignStartDate']);
    }

    public function saveRules(): void
    {
        $this->validate([
            'lateToleranceMinutes' => ['required', 'integer', 'min:0', 'max:240'],
            'checkoutScanGapMinutes' => ['required', 'integer', 'min:1', 'max:240'],
            'overtimeThresholdMinutes' => ['required', 'integer', 'min:0', 'max:600'],
        ]);

        $row = [
            'late_tolerance_minutes' => $this->lateToleranceMinutes,
            'checkout_scan_gap_minutes' => $this->checkoutScanGapMinutes,
            'overtime_threshold_minutes' => $this->overtimeThresholdMinutes,
            'updated_at' => now(),
        ];

        if (DB::table('attendance_settings')->exists()) {
            DB::table('attendance_settings')->limit(1)->update($row);
        } else {
            $row['created_at'] = now();
            DB::table('attendance_settings')->insert($row);
        }

        Notification::make()->title('Aturan absensi berhasil disimpan.')->success()->send();
    }

    protected function getViewData(): array
    {
        $schedules = DB::table('work_schedules')->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();
        $employees = DB::table('employees')->orderBy('name')->get();
        $assignments = DB::table('employee_work_schedule_assignments as a')
            ->join('employees as e', 'e.id', '=', 'a.employee_id')
            ->join('work_schedules as s', 's.id', '=', 'a.work_schedule_id')
            ->select('a.*', 'e.employee_no', 'e.name as employee_name', 's.name as schedule_name')
            ->whereNull('a.end_date')
            ->orderBy('e.name')
            ->get();

        return compact('schedules', 'employees', 'assignments');
    }

    private function loadSettings(): void
    {
        $settings = DB::table('attendance_settings')->first();
        if ($settings) {
            $this->lateToleranceMinutes = $settings->late_tolerance_minutes;
            $this->checkoutScanGapMinutes = $settings->checkout_scan_gap_minutes;
            $this->overtimeThresholdMinutes = $settings->overtime_threshold_minutes;
        }
    }

    private function loadSchedule(): void
    {
        $schedule = $this->scheduleId ? DB::table('work_schedules')->find($this->scheduleId) : null;
        if (! $schedule) {
            $this->days = $this->defaultDays();
            return;
        }

        $this->scheduleName = $schedule->name;
        $this->scheduleColor = $schedule->color;
        $stored = DB::table('work_schedule_days')->where('work_schedule_id', $schedule->id)->get()->keyBy('day_of_week');
        $this->days = $this->defaultDays();

        foreach ($stored as $day => $row) {
            $this->days[$day] = [
                'is_working' => (bool) $row->is_working,
                'start_time' => $row->start_time ? substr($row->start_time, 0, 5) : '08:00',
                'end_time' => $row->end_time ? substr($row->end_time, 0, 5) : '17:00',
                'is_free' => (bool) $row->is_free,
                'overtime_enabled' => (bool) $row->overtime_enabled,
                'max_overtime_hours' => (float) $row->max_overtime_hours,
            ];
        }
    }

    private function defaultDays(): array
    {
        $days = [];
        foreach (self::DAY_ORDER as $day) {
            $days[$day] = [
                'is_working' => $day !== 0,
                'start_time' => '08:00',
                'end_time' => '17:00',
                'is_free' => false,
                'overtime_enabled' => false,
                'max_overtime_hours' => 0,
            ];
        }
        return $days;
    }

    private function saveDefaultDays(int $id): void
    {
        foreach ($this->defaultDays() as $day => $values) {
            DB::table('work_schedule_days')->insert([
                'work_schedule_id' => $id,
                'day_of_week' => $day,
                ...$values,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
