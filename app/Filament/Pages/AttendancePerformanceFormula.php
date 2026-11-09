<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use UnitEnum;

class AttendancePerformanceFormula extends Page
{
    protected string $view = 'filament.pages.attendance-performance-formula';

    protected static ?string $slug = 'formula-performa';
    protected static ?string $title = 'Formula Performa';
    protected static ?string $navigationLabel = 'Formula Performa';
    protected static string | UnitEnum | null $navigationGroup = 'Kelola';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-calculator';
    protected static ?int $navigationSort = 8;

    public ?int $formulaId = null;
    public string $name = 'Formula Performa Kehadiran';
    public string $description = '';
    public string $effectiveFrom = '';
    public string $effectiveUntil = '';
    public float $attendanceWeight = 50;
    public float $punctualityWeight = 30;
    public float $worktimeWeight = 20;
    public int $lateToleranceMinutes = 5;
    public int $earlyLeaveToleranceMinutes = 0;
    public int $latePenaltyUnitMinutes = 30;
    public float $latePenaltyPoints = 1;
    public int $earlyLeavePenaltyUnitMinutes = 30;
    public float $earlyLeavePenaltyPoints = 1;
    public float $veryGoodMin = 98;
    public float $goodMin = 95;
    public float $fairMin = 90;
    public bool $confirmPolicy = false;

    public function mount(): void
    {
        $this->effectiveFrom = today()->toDateString();
    }

    public function newFormula(): void
    {
        $this->resetForm();
    }

    public function editFormula(int $id): void
    {
        $formula = DB::table('attendance_performance_formulas')->find($id);

        if (! $formula || $formula->status !== 'draft') {
            Notification::make()->title('Hanya formula Draft yang dapat diubah.')->warning()->send();
            return;
        }

        $this->formulaId = (int) $formula->id;
        $this->name = $formula->name;
        $this->description = $formula->description ?? '';
        $this->effectiveFrom = $formula->effective_from;
        $this->effectiveUntil = $formula->effective_until ?? '';
        $this->attendanceWeight = (float) $formula->attendance_weight;
        $this->punctualityWeight = (float) $formula->punctuality_weight;
        $this->worktimeWeight = (float) $formula->worktime_weight;
        $this->lateToleranceMinutes = (int) $formula->late_tolerance_minutes;
        $this->earlyLeaveToleranceMinutes = (int) $formula->early_leave_tolerance_minutes;
        $this->latePenaltyUnitMinutes = (int) $formula->late_penalty_unit_minutes;
        $this->latePenaltyPoints = (float) $formula->late_penalty_points;
        $this->earlyLeavePenaltyUnitMinutes = (int) $formula->early_leave_penalty_unit_minutes;
        $this->earlyLeavePenaltyPoints = (float) $formula->early_leave_penalty_points;
        $this->veryGoodMin = (float) $formula->very_good_min;
        $this->goodMin = (float) $formula->good_min;
        $this->fairMin = (float) $formula->fair_min;
        $this->confirmPolicy = false;
    }

    public function saveDraft(): void
    {
        $data = $this->validatedData();

        if ($data === null) {
            return;
        }

        $payload = $this->payload($data);

        if ($this->formulaId) {
            $formula = DB::table('attendance_performance_formulas')->find($this->formulaId);
            if (! $formula || $formula->status !== 'draft') {
                Notification::make()->title('Formula yang sudah aktif tidak dapat diubah.')->danger()->send();
                return;
            }

            DB::table('attendance_performance_formulas')->where('id', $this->formulaId)->update($payload);
        } else {
            do {
                $code = 'FP-' . strtoupper(Str::random(8));
            } while (DB::table('attendance_performance_formulas')->where('code', $code)->exists());

            $payload['code'] = $code;
            $payload['status'] = 'draft';
            $payload['created_by'] = Auth::id();
            $payload['created_at'] = now();
            DB::table('attendance_performance_formulas')->insert($payload);
        }

        $this->resetForm();
        Notification::make()->title('Formula Draft berhasil disimpan.')->success()->send();
    }

    public function activateFormula(int $id): void
    {
        if (! $this->confirmPolicy) {
            Notification::make()->title('Konfirmasi bahwa parameter formula sudah disahkan terlebih dahulu.')->warning()->send();
            return;
        }

        $formula = DB::table('attendance_performance_formulas')->find($id);
        if (! $formula || $formula->status !== 'draft') {
            Notification::make()->title('Formula Draft tidak ditemukan.')->danger()->send();
            return;
        }

        if (round((float) $formula->attendance_weight + (float) $formula->punctuality_weight + (float) $formula->worktime_weight, 2) !== 100.0) {
            Notification::make()->title('Total bobot formula harus tepat 100%.')->danger()->send();
            return;
        }

        if ($formula->effective_until && $formula->effective_until < $formula->effective_from) {
            Notification::make()->title('Tanggal akhir tidak boleh mendahului tanggal mulai.')->danger()->send();
            return;
        }

        try {
            DB::transaction(function () use ($formula) {
                $start = $formula->effective_from;
                $end = $formula->effective_until ?: '9999-12-31';

                $overlapping = DB::table('attendance_performance_formulas')
                    ->where('status', 'active')
                    ->whereDate('effective_from', '<=', $end)
                    ->where(function ($query) use ($start) {
                        $query->whereNull('effective_until')
                            ->orWhereDate('effective_until', '>=', $start);
                    })
                    ->lockForUpdate()
                    ->get();

                foreach ($overlapping as $current) {
                    if ($current->effective_from >= $start) {
                        throw new \RuntimeException('Tanggal mulai formula baru bertabrakan dengan formula aktif yang sudah terjadwal.');
                    }

                    DB::table('attendance_performance_formulas')->where('id', $current->id)->update([
                        'effective_until' => date('Y-m-d', strtotime($start . ' -1 day')),
                        'status' => 'ended',
                        'ended_at' => now(),
                        'updated_by' => Auth::id(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('attendance_performance_formulas')->where('id', $formula->id)->update([
                    'status' => 'active',
                    'activated_at' => now(),
                    'updated_by' => Auth::id(),
                    'updated_at' => now(),
                ]);
            });
        } catch (\RuntimeException $exception) {
            Notification::make()->title($exception->getMessage())->danger()->send();
            return;
        }

        $this->confirmPolicy = false;
        Notification::make()->title('Formula berhasil diaktifkan. Formula sebelumnya diakhiri pada hari sebelum tanggal mulai formula baru.')->success()->send();
    }

    public function endFormula(int $id): void
    {
        $formula = DB::table('attendance_performance_formulas')->find($id);

        if (! $formula || $formula->status !== 'active') {
            return;
        }

        if ($formula->effective_from > today()->toDateString()) {
            Notification::make()->title('Formula yang belum mulai berlaku belum dapat diakhiri.')->warning()->send();
            return;
        }

        DB::table('attendance_performance_formulas')->where('id', $id)->update([
            'effective_until' => today()->toDateString(),
            'status' => 'ended',
            'ended_at' => now(),
            'updated_by' => Auth::id(),
            'updated_at' => now(),
        ]);

        Notification::make()->title('Masa berlaku formula telah diakhiri hari ini.')->success()->send();
    }

    protected function getViewData(): array
    {
        return [
            'formulas' => DB::table('attendance_performance_formulas')
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->get(),
        ];
    }

    private function validatedData(): ?array
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'effectiveFrom' => ['required', 'date'],
            'effectiveUntil' => ['nullable', 'date', 'after_or_equal:effectiveFrom'],
            'attendanceWeight' => ['required', 'numeric', 'min:0', 'max:100'],
            'punctualityWeight' => ['required', 'numeric', 'min:0', 'max:100'],
            'worktimeWeight' => ['required', 'numeric', 'min:0', 'max:100'],
            'lateToleranceMinutes' => ['required', 'integer', 'min:0', 'max:240'],
            'earlyLeaveToleranceMinutes' => ['required', 'integer', 'min:0', 'max:240'],
            'latePenaltyUnitMinutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'latePenaltyPoints' => ['required', 'numeric', 'min:0', 'max:100'],
            'earlyLeavePenaltyUnitMinutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'earlyLeavePenaltyPoints' => ['required', 'numeric', 'min:0', 'max:100'],
            'veryGoodMin' => ['required', 'numeric', 'min:0', 'max:100'],
            'goodMin' => ['required', 'numeric', 'min:0', 'max:100'],
            'fairMin' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        if (round((float) $data['attendanceWeight'] + (float) $data['punctualityWeight'] + (float) $data['worktimeWeight'], 2) !== 100.0) {
            $this->addError('attendanceWeight', 'Total bobot ketiga komponen harus tepat 100%.');
            return null;
        }

        if (! ((float) $data['veryGoodMin'] > (float) $data['goodMin']
            && (float) $data['goodMin'] > (float) $data['fairMin'])) {
            $this->addError('veryGoodMin', 'Batas kategori harus berurutan: Sangat Baik > Baik > Cukup.');
            return null;
        }

        return $data;
    }

    private function payload(array $data): array
    {
        return [
            'name' => trim($data['name']),
            'description' => filled($data['description']) ? trim($data['description']) : null,
            'effective_from' => $data['effectiveFrom'],
            'effective_until' => filled($data['effectiveUntil']) ? $data['effectiveUntil'] : null,
            'attendance_weight' => $data['attendanceWeight'],
            'punctuality_weight' => $data['punctualityWeight'],
            'worktime_weight' => $data['worktimeWeight'],
            'late_tolerance_minutes' => $data['lateToleranceMinutes'],
            'early_leave_tolerance_minutes' => $data['earlyLeaveToleranceMinutes'],
            'late_penalty_unit_minutes' => $data['latePenaltyUnitMinutes'],
            'late_penalty_points' => $data['latePenaltyPoints'],
            'early_leave_penalty_unit_minutes' => $data['earlyLeavePenaltyUnitMinutes'],
            'early_leave_penalty_points' => $data['earlyLeavePenaltyPoints'],
            'very_good_min' => $data['veryGoodMin'],
            'good_min' => $data['goodMin'],
            'fair_min' => $data['fairMin'],
            'updated_by' => Auth::id(),
            'updated_at' => now(),
        ];
    }

    private function resetForm(): void
    {
        $this->reset([
            'formulaId', 'description', 'effectiveUntil', 'confirmPolicy',
        ]);
        $this->name = 'Formula Performa Kehadiran';
        $this->effectiveFrom = today()->toDateString();
        $this->attendanceWeight = 50;
        $this->punctualityWeight = 30;
        $this->worktimeWeight = 20;
        $this->lateToleranceMinutes = 5;
        $this->earlyLeaveToleranceMinutes = 0;
        $this->latePenaltyUnitMinutes = 30;
        $this->latePenaltyPoints = 1;
        $this->earlyLeavePenaltyUnitMinutes = 30;
        $this->earlyLeavePenaltyPoints = 1;
        $this->veryGoodMin = 98;
        $this->goodMin = 95;
        $this->fairMin = 90;
        $this->resetValidation();
    }
}
