<?php

namespace App\\Filament\\Pages;

use BackedEnum;
use Filament\\Notifications\\Notification;
use Filament\\Pages\\Page;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Str;
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
    public string $scheduleId = 'main';
    public string $scheduleName = 'Jadwal Utama';
    public string $scheduleColor = '#64748b';
    public array $days = [];
    public int $lateToleranceMinutes = 15;
    public int $checkoutScanGapMinutes = 60;
    public int $overtimeThresholdMinutes = 60;

    private const DAY_ORDER = [1, 2, 3, 4, 5, 6, 0];

    public function mount(): void
    {
        $this->loadSettings();
        $this->loadSchedule();
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['jadwal', 'aturan'], true)) {
            $this->activeTab = $tab;
        }
    }

    public function selectSchedule(string $id): void
    {
        if ($id === 'main' || DB::table('schedules')->where('id', $id)->exists()) {
            $this->scheduleId = $id;
            $this->loadSchedule();
        }
    }

    public function newSchedule(): void
    {
        $this->scheduleId = 'new';
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

        $days = [];
        $overtime = [];
        $overtimeMax = [];

        for ($day = 0; $day < 7; $day++) {
            $value = $this->days[$day] ?? [];
            $working = (bool) ($value['is_working'] ?? false);
            $free = (bool) ($value['is_free'] ?? false);

            $days[] = ! $working ? null : ($free
                ? ['free' => true]
                : ['start' => $value['start_time'], 'end' => $value['end_time']]);
            $overtime[] = (bool) ($value['overtime_enabled'] ?? false);
            $overtimeMax[] = (float) ($value['max_overtime_hours'] ?? 0);
        }

        $payload = [
            'name' => trim($this->scheduleName),
            'color' => $this->scheduleColor,
            'days' => json_encode($days, JSON_THROW_ON_ERROR),
            'overtime' => json_encode($overtime, JSON_THROW_ON_ERROR),
            'overtime_max' => json_encode($overtimeMax, JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ];

        if ($this->scheduleId === 'main') {
            DB::table('work_schedule_settings')->updateOrInsert(
                ['id' => 1],
                [
                    'days' => $payload['days'],
                    'tolerance' => $this->lateToleranceMinutes,
                    'overtime' => $payload['overtime'],
                    'overtime_min' => $this->overtimeThresholdMinutes,
                    'overtime_max' => $payload['overtime_max'],
                    'min_gap' => $this->checkoutScanGapMinutes,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        } else {
            if ($this->scheduleId === 'new') {
                $this->scheduleId = (string) Str::uuid();
                $payload['id'] = $this->scheduleId;
                $payload['created_at'] = now();
                DB::table('schedules')->insert($payload);
            } else {
                unset($payload['name'], $payload['color']);
                DB::table('schedules')->where('id', $this->scheduleId)->update($payload);
            }
        }

        Notification::make()->title('Jadwal kerja berhasil disimpan.')->success()->send();
        $this->loadSchedule();
    }

    public function deleteSchedule(string $id): void
    {
        if ($id === 'main') {
            Notification::make()->title('Jadwal utama tidak dapat dihapus.')->danger()->send();
            return;
        }

        DB::table('schedules')->where('id', $id)->delete();
        $this->scheduleId = 'main';
        $this->loadSchedule();
        Notification::make()->title('Jadwal berhasil dihapus.')->success()->send();
    }

    public function saveRules(): void
    {
        $this->validate([
            'lateToleranceMinutes' => ['required', 'integer', 'min:0', 'max:240'],
            'checkoutScanGapMinutes' => ['required', 'integer', 'min:1', 'max:240'],
            'overtimeThresholdMinutes' => ['required', 'integer', 'min:0', 'max:600'],
        ]);

        $settings = DB::table('work_schedule_settings')->where('id', 1)->first();
        DB::table('work_schedule_settings')->updateOrInsert(
            ['id' => 1],
            [
                'days' => $settings?->days ?? json_encode($this->defaultDaysJson(), JSON_THROW_ON_ERROR),
                'tolerance' => $this->lateToleranceMinutes,
                'overtime' => $settings?->overtime ?? json_encode(array_fill(0, 7, false), JSON_THROW_ON_ERROR),
                'overtime_min' => $this->overtimeThresholdMinutes,
                'overtime_max' => $settings?->overtime_max ?? json_encode(array_fill(0, 7, 0), JSON_THROW_ON_ERROR),
                'min_gap' => $this->checkoutScanGapMinutes,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        Notification::make()->title('Aturan absensi berhasil disimpan.')->success()->send();
    }

    protected function getViewData(): array
    {
        $schedules = DB::table('schedules')->orderBy('name')->get();
        return compact('schedules');
    }

    private function loadSettings(): void
    {
        $settings = DB::table('work_schedule_settings')->where('id', 1)->first();

        if (! $settings) {
            $this->days = $this->defaultDays();
            return;
        }

        $this->lateToleranceMinutes = (int) $settings->tolerance;
        $this->checkoutScanGapMinutes = (int) $settings->min_gap;
        $this->overtimeThresholdMinutes = (int) $settings->overtime_min;
    }

    private function loadSchedule(): void
    {
        if ($this->scheduleId === 'main') {
            $this->scheduleName = 'Jadwal Utama';
            $this->scheduleColor = '#64748b';
            $settings = DB::table('work_schedule_settings')->where('id', 1)->first();
            $days = $settings ? json_decode($settings->days, true) : null;
            $overtime = $settings ? json_decode($settings->overtime, true) : null;
            $overtimeMax = $settings ? json_decode($settings->overtime_max, true) : null;
        } else {
            $schedule = $this->scheduleId === 'new' ? null : DB::table('schedules')->where('id', $this->scheduleId)->first();
            if ($this->scheduleId !== 'new' && ! $schedule) {
                $this->scheduleId = 'main';
                $this->loadSchedule();
                return;
            }
            $this->scheduleName = $schedule?->name ?? $this->scheduleName;
            $this->scheduleColor = $schedule?->color ?? $this->scheduleColor;
            $days = $schedule ? json_decode($schedule->days, true) : null;
            $overtime = $schedule ? json_decode($schedule->overtime, true) : null;
            $overtimeMax = $schedule ? json_decode($schedule->overtime_max, true) : null;
        }

        $this->days = $this->defaultDays();
        for ($day = 0; $day < 7; $day++) {
            $value = $days[$day] ?? null;
            $this->days[$day] = [
                'is_working' => $value !== null,
                'start_time' => $value['start'] ?? '08:00',
                'end_time' => $value['end'] ?? '17:00',
                'is_free' => (bool) ($value['free'] ?? false),
                'overtime_enabled' => (bool) ($overtime[$day] ?? false),
                'max_overtime_hours' => (float) ($overtimeMax[$day] ?? 0),
            ];
        }
    }

    private function defaultDays(): array
    {
        $days = [];
        for ($day = 0; $day < 7; $day++) {
            $days[$day] = [
                'is_working' => $day >= 1 && $day <= 5,
                'start_time' => '08:00',
                'end_time' => '17:00',
                'is_free' => false,
                'overtime_enabled' => false,
                'max_overtime_hours' => 0,
            ];
        }
        return $days;
    }

    private function defaultDaysJson(): array
    {
        $days = [];
        for ($day = 0; $day < 7; $day++) {
            $days[] = $day >= 1 && $day <= 5 ? ['start' => '08:00', 'end' => '17:00'] : null;
        }
        return $days;
    }
}
