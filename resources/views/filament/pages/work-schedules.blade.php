<x-filament-panels::page>
    <div class="fs-work-schedules space-y-5">
        <div class="max-w-4xl space-y-1">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Jadwal Kerja Reguler</h2>
            <p class="text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                Jadwal ini berlaku sebagai jadwal reguler karyawan. Lembur diatur per hari.
                Pada hari kerja, lembur dihitung setelah jam pulang. Pada hari libur, seluruh jam kerja dihitung lembur.
                Maks. jam lembur dapat diisi angka atau "bebas" untuk tanpa batas.
            </p>
        </div>

        <section class="fs-schedule-panel">
            <div class="fs-schedule-title">
                <span class="fs-schedule-dot"></span>
                <h3 class="text-sm font-semibold">Utama</h3>
            </div>

            <div class="fs-schedule-grid fs-schedule-head">
                <span>Kerja</span>
                <span>Jam</span>
                <span class="text-center">Lembur</span>
                <span>Maks. jam</span>
            </div>

            <div class="fs-schedule-body">
                @foreach ([1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',0=>'Minggu'] as $day => $label)
                    <div class="fs-schedule-grid fs-schedule-row">
                        <div class="fs-schedule-work">
                            <span class="fs-schedule-day">{{ $label }}</span>
                            <label class="fs-switch">
                                <input type="checkbox" wire:model.live="days.{{ $day }}.is_working" class="fs-switch-input">
                                <span class="fs-switch-track"></span>
                            </label>
                        </div>

                        <div class="fs-schedule-hours">
                            @if ($days[$day]['is_working'] ?? false)
                                <input type="time" wire:model="days.{{ $day }}.start_time"
                                    @disabled($days[$day]['is_free'] ?? false)
                                    class="fs-time-input">
                                <span class="fs-time-separator">–</span>
                                <input type="time" wire:model="days.{{ $day }}.end_time"
                                    @disabled($days[$day]['is_free'] ?? false)
                                    class="fs-time-input">
                                <label class="fs-free-label">
                                    <input type="checkbox" wire:model="days.{{ $day }}.is_free"> Bebas
                                </label>
                            @else
                                <span class="fs-holiday-label">Libur</span>
                            @endif
                        </div>

                        <div class="fs-schedule-overtime">
                            <label class="fs-switch">
                                <input type="checkbox" wire:model="days.{{ $day }}.overtime_enabled" class="fs-switch-input">
                                <span class="fs-switch-track"></span>
                            </label>
                        </div>

                        <div class="fs-schedule-max">
                            <input type="text" wire:model="days.{{ $day }}.max_overtime_hours" placeholder="bebas"
                                class="fs-max-input">
                        </div>

                        @error("days.$day.end_time")
                            <p class="col-span-12 text-xs text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>

            <div class="fs-schedule-actions">
                <x-filament::button size="sm" wire:click="saveSchedule">Simpan jadwal</x-filament::button>
            </div>
        </section>

        <section class="space-y-2">
            <div>
                <h3 class="text-sm font-semibold">Aturan rekap</h3>
                <p class="text-xs text-gray-500">Berlaku untuk jadwal reguler.</p>
            </div>
            <form wire:submit="saveRules" class="grid gap-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900 md:grid-cols-3">
                <label class="block text-xs">
                    <span class="font-medium">Toleransi terlambat</span>
                    <div class="mt-1 flex items-center gap-2">
                        <input type="number" min="0" max="240" wire:model="lateToleranceMinutes"
                            class="w-20 rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-950">
                        <span class="text-gray-500">menit</span>
                    </div>
                    <span class="mt-1 block text-[11px] leading-relaxed text-gray-500">Keterlambatan tetap dihitung dari jam masuk.</span>
                    @error('lateToleranceMinutes')<span class="text-xs text-danger-600">{{ $message }}</span>@enderror
                </label>
                <label class="block text-xs">
                    <span class="font-medium">Scan kedua dihitung pulang bila minimal</span>
                    <div class="mt-1 flex items-center gap-2">
                        <input type="number" min="1" max="240" wire:model="checkoutScanGapMinutes"
                            class="w-20 rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-950">
                        <span class="text-gray-500">menit setelah masuk</span>
                    </div>
                    <span class="mt-1 block text-[11px] leading-relaxed text-gray-500">Scan yang terlalu dekat dianggap scan ganda.</span>
                    @error('checkoutScanGapMinutes')<span class="text-xs text-danger-600">{{ $message }}</span>@enderror
                </label>
                <label class="block text-xs">
                    <span class="font-medium">Lembur dihitung mulai</span>
                    <div class="mt-1 flex items-center gap-2">
                        <input type="number" min="0" max="600" wire:model="overtimeThresholdMinutes"
                            class="w-20 rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-950">
                        <span class="text-gray-500">menit setelah jam pulang</span>
                    </div>
                    <span class="mt-1 block text-[11px] leading-relaxed text-gray-500">Lembur di bawah batas ini dihitung 0.</span>
                    @error('overtimeThresholdMinutes')<span class="text-xs text-danger-600">{{ $message }}</span>@enderror
                </label>
                <div class="md:col-span-3 flex justify-end">
                    <x-filament::button type="submit" size="sm">Simpan aturan</x-filament::button>
                </div>
            </form>
        </section>
    </div>
</x-filament-panels::page>
