<x-filament-panels::page>
    <x-filament::tabs label="Pengaturan jadwal kerja">
        <x-filament::tabs.item :active="$activeTab === 'jadwal'" wire:click="setTab('jadwal')">Jadwal Kerja</x-filament::tabs.item>
        <x-filament::tabs.item :active="$activeTab === 'aturan'" wire:click="setTab('aturan')">Aturan Absensi</x-filament::tabs.item>
    </x-filament::tabs>

    @if ($activeTab === 'jadwal')
        <div class="grid gap-4 xl:grid-cols-3">
            <section class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold">Daftar Jadwal</h2>
                    <x-filament::button size="sm" wire:click="newSchedule">Tambah</x-filament::button>
                </div>
                <button type="button" wire:click="selectSchedule('main')"
                    class="flex w-full items-center justify-between rounded-xl border p-4 text-left {{ $scheduleId === 'main' ? 'border-primary-500 bg-primary-50 dark:bg-primary-950' : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900' }}">
                    <span class="text-sm font-medium">Jadwal Utama</span>
                    <x-filament::badge color="success">Utama</x-filament::badge>
                </button>
                @foreach ($schedules as $schedule)
                    <button type="button" wire:click="selectSchedule(@js($schedule->id))"
                        class="flex w-full items-center justify-between rounded-xl border p-4 text-left {{ $scheduleId === $schedule->id ? 'border-primary-500 bg-primary-50 dark:bg-primary-950' : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900' }}">
                        <span class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full" style="background: {{ $schedule->color ?: '#64748b' }}"></span>
                            <span class="text-sm font-medium">{{ $schedule->name }}</span>
                        </span>
                    </button>
                @endforeach
            </section>

            <section class="space-y-4 xl:col-span-2">
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm">Nama jadwal
                            <input type="text" wire:model="scheduleName" @disabled($scheduleId === 'main') class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-950">
                        </label>
                        <label class="block text-sm">Warna
                            <input type="color" wire:model="scheduleColor" @disabled($scheduleId === 'main') class="mt-1 block h-10 w-20 rounded border-gray-300">
                        </label>
                    </div>
                    @error('scheduleName')<p class="mt-1 text-sm text-danger-600">{{ $message }}</p>@enderror
                    <div class="mt-5 divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ([1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',0=>'Minggu'] as $day => $label)
                            <div class="grid gap-3 py-4 lg:grid-cols-12 lg:items-center">
                                <label class="flex items-center gap-2 text-sm font-medium lg:col-span-2">
                                    <input type="checkbox" wire:model.live="days.{{ $day }}.is_working"> {{ $label }}
                                </label>
                                <div class="flex flex-wrap items-center gap-2 lg:col-span-5">
                                    <input type="time" wire:model="days.{{ $day }}.start_time" @disabled(!($days[$day]['is_working'] ?? false) || ($days[$day]['is_free'] ?? false)) class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-950">
                                    <span class="text-xs text-gray-500">s.d.</span>
                                    <input type="time" wire:model="days.{{ $day }}.end_time" @disabled(!($days[$day]['is_working'] ?? false) || ($days[$day]['is_free'] ?? false)) class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-950">
                                </div>
                                <label class="flex items-center gap-2 text-xs lg:col-span-2">
                                    <input type="checkbox" wire:model="days.{{ $day }}.is_free" @disabled(!($days[$day]['is_working'] ?? false))> Jam bebas
                                </label>
                                <div class="flex items-center gap-2 text-xs lg:col-span-3">
                                    <label class="flex items-center gap-1"><input type="checkbox" wire:model="days.{{ $day }}.overtime_enabled" @disabled(!($days[$day]['is_working'] ?? false))> Lembur</label>
                                    <input type="number" min="0" max="24" step="0.5" wire:model="days.{{ $day }}.max_overtime_hours" @disabled(!($days[$day]['overtime_enabled'] ?? false)) class="w-20 rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-950">
                                    <span>jam</span>
                                </div>
                                @error("days.$day.end_time")<p class="text-xs text-danger-600 lg:col-span-12">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 flex justify-end gap-2">
                        @if ($scheduleId !== 'main' && $scheduleId !== 'new')
                            <x-filament::button color="danger" outlined wire:click="deleteSchedule(@js($scheduleId))" wire:confirm="Hapus jadwal ini?">Hapus</x-filament::button>
                        @endif
                        <x-filament::button wire:click="saveSchedule">Simpan Jadwal</x-filament::button>
                    </div>
                </div>
            </section>
        </div>
    @else
        <form wire:submit="saveRules" class="max-w-3xl space-y-5 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
            <div><h2 class="font-semibold">Aturan Pengolahan Absensi</h2><p class="text-sm text-gray-500">Pengaturan ini disimpan pada work_schedule_settings.</p></div>
            <label class="block text-sm">Toleransi keterlambatan (menit)
                <input type="number" min="0" max="240" wire:model="lateToleranceMinutes" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-950">
            </label>
            <label class="block text-sm">Jeda minimum antar scan (menit)
                <input type="number" min="1" max="240" wire:model="checkoutScanGapMinutes" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-950">
            </label>
            <label class="block text-sm">Ambang waktu sebelum dihitung lembur (menit)
                <input type="number" min="0" max="600" wire:model="overtimeThresholdMinutes" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-950">
            </label>
            <x-filament::button type="submit">Simpan Aturan</x-filament::button>
        </form>
    @endif
</x-filament-panels::page>
