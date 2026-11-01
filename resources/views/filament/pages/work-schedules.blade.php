<x-filament-panels::page>
    <div class="space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="max-w-4xl space-y-1">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Jadwal</h2>
                <p class="text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                    Jadwal utama menjadi jadwal default untuk karyawan.
                    Lembur diatur per hari. Hari kerja dihitung lembur setelah jam pulang, sedangkan hari libur seluruh jam kerja dihitung lembur.
                    Batas maksimal 0 berarti tanpa batas. Jam bebas berarti karyawan dapat datang dan pulang kapan saja selama melakukan scan.
                    Bulatan warna menunjukkan warna jadwal.
                </p>
            </div>
            <x-filament::button size="sm" wire:click="newSchedule" icon="heroicon-m-plus">
                Tambah jadwal lain
            </x-filament::button>
        </div>

        @if ($scheduleId === 'new' || $schedules->isNotEmpty())
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" wire:click="selectSchedule('main')"
                    class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm {{ $scheduleId === 'main' ? 'border-primary-500 bg-primary-50 dark:bg-primary-950' : 'border-gray-200 dark:border-gray-700' }}">
                    <span class="h-3 w-3 rounded-full bg-slate-500"></span> Utama
                </button>
                @foreach ($schedules as $schedule)
                    <button type="button" wire:click="selectSchedule(@js($schedule->id))"
                        class="inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm {{ $scheduleId === $schedule->id ? 'border-primary-500 bg-primary-50 dark:bg-primary-950' : 'border-gray-200 dark:border-gray-700' }}">
                        <span class="h-3 w-3 rounded-full" style="background: {{ $schedule->color ?: '#64748b' }}"></span>
                        {{ $schedule->name }}
                    </button>
                @endforeach
            </div>
        @endif

        <section class="max-w-2xl rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="h-4 w-4 rounded-full" style="background: {{ $scheduleColor }}"></span>
                    <h3 class="text-sm font-semibold">{{ $scheduleId === 'main' ? 'Utama' : ($scheduleName ?: 'Jadwal baru') }}</h3>
                </div>
                @if ($scheduleId !== 'main')
                    <div class="flex items-center gap-2">
                        <input type="text" wire:model="scheduleName" placeholder="Nama jadwal"
                            class="w-40 rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-950">
                        <input type="color" wire:model="scheduleColor" title="Warna jadwal" class="h-9 w-10 rounded border-gray-300">
                    </div>
                @endif
            </div>
            @error('scheduleName')<p class="mb-2 text-xs text-danger-600">{{ $message }}</p>@enderror

            <div class="mb-1 grid grid-cols-12 gap-2 px-1 text-[11px] text-gray-500">
                <span class="col-span-2"></span>
                <span class="col-span-1 text-center">Kerja</span>
                <span class="col-span-5">Jam kerja</span>
                <span class="col-span-2">Lembur</span>
                <span class="col-span-2">Maks. jam</span>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ([1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',0=>'Minggu'] as $day => $label)
                    <div class="grid grid-cols-12 items-center gap-2 py-1.5">
                        <span class="col-span-2 text-xs">{{ $label }}</span>
                        <div class="col-span-1 flex justify-center">
                            <input type="checkbox" wire:model.live="days.{{ $day }}.is_working"
                                class="rounded-full border-gray-300 text-success-600 focus:ring-success-500">
                        </div>
                        <div class="col-span-5 flex flex-wrap items-center gap-1">
                            @if ($days[$day]['is_working'] ?? false)
                                <input type="time" wire:model="days.{{ $day }}.start_time"
                                    @disabled($days[$day]['is_free'] ?? false)
                                    class="w-[6.5rem] rounded-lg border-gray-300 px-2 py-1 text-xs dark:border-gray-600 dark:bg-gray-950">
                                <span class="text-xs text-gray-400">-</span>
                                <input type="time" wire:model="days.{{ $day }}.end_time"
                                    @disabled($days[$day]['is_free'] ?? false)
                                    class="w-[6.5rem] rounded-lg border-gray-300 px-2 py-1 text-xs dark:border-gray-600 dark:bg-gray-950">
                                <label class="flex items-center gap-1 text-[11px]">
                                    <input type="checkbox" wire:model="days.{{ $day }}.is_free"> Bebas
                                </label>
                            @else
                                <span class="text-xs text-gray-400">Libur</span>
                            @endif
                        </div>
                        <div class="col-span-2">
                            <input type="checkbox" wire:model="days.{{ $day }}.overtime_enabled"
                                @disabled(!($days[$day]['is_working'] ?? false))
                                class="rounded-full border-gray-300 text-success-600 focus:ring-success-500">
                        </div>
                        <div class="col-span-2">
                            <input type="number" min="0" max="24" step="0.5"
                                wire:model="days.{{ $day }}.max_overtime_hours"
                                @disabled(!($days[$day]['overtime_enabled'] ?? false))
                                class="w-full rounded-lg border-gray-300 px-2 py-1 text-xs dark:border-gray-600 dark:bg-gray-950">
                        </div>
                        @error("days.$day.end_time")<p class="col-span-12 text-xs text-danger-600">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                <span class="text-[11px] text-gray-500">Maks. jam lembur: 0 = bebas / tanpa batas.</span>
                <div class="flex items-center gap-2">
                    @if ($scheduleId !== 'main' && $scheduleId !== 'new')
                        <x-filament::button color="danger" size="sm" outlined wire:click="deleteSchedule(@js($scheduleId))" wire:confirm="Hapus jadwal ini?">Hapus</x-filament::button>
                    @endif
                    <x-filament::button size="sm" wire:click="saveSchedule">Simpan jadwal</x-filament::button>
                </div>
            </div>
        </section>

        <section class="space-y-2">
            <div>
                <h3 class="text-sm font-semibold">Aturan rekap</h3>
                <p class="text-xs text-gray-500">Berlaku untuk semua jadwal.</p>
            </div>
            <form wire:submit="saveRules" class="grid gap-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900 md:grid-cols-3">
                <label class="block text-xs">
                    <span class="font-medium">Toleransi terlambat</span>
                    <div class="mt-1 flex items-center gap-2">
                        <input type="number" min="0" max="240" wire:model="lateToleranceMinutes"
                            class="w-20 rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-950">
                        <span class="text-gray-500">menit</span>
                    </div>
                    <span class="mt-1 block text-[11px] leading-relaxed text-gray-500">Keterlambatan tetap dihitung dari jam masuk. Contoh: masuk 08:00, toleransi 15 menit, scan 08:20 = terlambat 20 menit.</span>
                    @error('lateToleranceMinutes')<span class="text-xs text-danger-600">{{ $message }}</span>@enderror
                </label>
                <label class="block text-xs">
                    <span class="font-medium">Scan kedua dihitung pulang bila minimal</span>
                    <div class="mt-1 flex items-center gap-2">
                        <input type="number" min="1" max="240" wire:model="checkoutScanGapMinutes"
                            class="w-20 rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-950">
                        <span class="text-gray-500">menit setelah masuk</span>
                    </div>
                    <span class="mt-1 block text-[11px] leading-relaxed text-gray-500">Scan yang terlalu dekat dianggap scan ganda. Hari jam bebas: scan terakhir adalah pulang; scan tanpa pasangan menjadi scan berikutnya dalam 24 jam.</span>
                    @error('checkoutScanGapMinutes')<span class="text-xs text-danger-600">{{ $message }}</span>@enderror
                </label>
                <label class="block text-xs">
                    <span class="font-medium">Lembur dihitung mulai</span>
                    <div class="mt-1 flex items-center gap-2">
                        <input type="number" min="0" max="600" wire:model="overtimeThresholdMinutes"
                            class="w-20 rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-950">
                        <span class="text-gray-500">menit setelah jam pulang</span>
                    </div>
                    <span class="mt-1 block text-[11px] leading-relaxed text-gray-500">Lembur di bawah batas ini dihitung 0. Hari yang dihitung lembur diatur per hari pada jadwal.</span>
                    @error('overtimeThresholdMinutes')<span class="text-xs text-danger-600">{{ $message }}</span>@enderror
                </label>
                <div class="md:col-span-3 flex justify-end">
                    <x-filament::button type="submit" size="sm">Simpan aturan</x-filament::button>
                </div>
            </form>
        </section>
    </div>
</x-filament-panels::page>
