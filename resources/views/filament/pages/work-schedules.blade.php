<x-filament-panels::page>
    <x-filament::tabs label="Pengaturan jadwal kerja">
        <x-filament::tabs.item :active="$activeTab === 'jadwal'" wire:click="setTab('jadwal')">Jadwal Kerja</x-filament::tabs.item>
        <x-filament::tabs.item :active="$activeTab === 'penugasan'" wire:click="setTab('penugasan')">Penugasan Karyawan</x-filament::tabs.item>
        <x-filament::tabs.item :active="$activeTab === 'aturan'" wire:click="setTab('aturan')">Aturan Absensi</x-filament::tabs.item>
    </x-filament::tabs>

    @if ($activeTab === 'jadwal')
        <div class="grid gap-4 xl:grid-cols-3">
            <section class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold">Daftar Jadwal</h2>
                    <x-filament::button size="sm" wire:click="newSchedule">Tambah</x-filament::button>
                </div>
                @foreach ($schedules as $schedule)
                    <button type="button" wire:click="selectSchedule({{ $schedule->id }})"
                        class="flex w-full items-center justify-between rounded-xl border p-4 text-left {{ $scheduleId === $schedule->id ? 'border-primary-500 bg-primary-50 dark:bg-primary-950' : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900' }}">
                        <span class="flex items-center gap-3">
                            <span class="h-3 w-3 rounded-full" style="background: {{ $schedule->color }}"></span>
                            <span>
                                <span class="block text-sm font-medium">{{ $schedule->name }}</span>
                                <span class="text-xs text-gray-500">{{ $schedule->is_default ? 'Jadwal utama' : 'Jadwal tambahan' }}</span>
                            </span>
                        </span>
                        @if ($schedule->is_default)<x-filament::badge color="success">Utama</x-filament::badge>@endif
                    </button>
                @endforeach
            </section>

            <section class="space-y-4 xl:col-span-2">
                <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm">Nama jadwal
                            <input type="text" wire:model="scheduleName" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-950">
                        </label>
                        <label class="block text-sm">Warna
                            <input type="color" wire:model="scheduleColor" class="mt-1 block h-10 w-20 rounded border-gray-300">
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
                    <div class="mt-4 flex flex-wrap justify-between gap-2">
                        <div>
                            @if ($scheduleId && !($schedules->firstWhere('id', $scheduleId)?->is_default ?? false))
                                <x-filament::button color="danger" outlined wire:click="deleteSchedule({{ $scheduleId }})" wire:confirm="Nonaktifkan jadwal ini?">Nonaktifkan</x-filament::button>
                            @endif
                        </div>
                        <x-filament::button wire:click="saveSchedule">Simpan Jadwal</x-filament::button>
                    </div>
                </div>
            </section>
        </div>
    @elseif ($activeTab === 'penugasan')
        <div class="grid gap-5 xl:grid-cols-3">
            <form wire:submit="saveAssignment" class="space-y-4 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
                <h2 class="font-semibold">Tetapkan Jadwal</h2>
                <label class="block text-sm">Karyawan
                    <select wire:model="assignEmployeeId" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-950">
                        <option value="">Pilih karyawan</option>
                        @foreach ($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no }} — {{ $employee->name }}</option>@endforeach
                    </select>
                </label>
                <label class="block text-sm">Jadwal
                    <select wire:model="assignScheduleId" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-950">
                        <option value="">Pilih jadwal</option>
                        @foreach ($schedules as $schedule)<option value="{{ $schedule->id }}">{{ $schedule->name }}</option>@endforeach
                    </select>
                </label>
                <label class="block text-sm">Mulai berlaku
                    <input type="date" wire:model="assignStartDate" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-950">
                </label>
                @error('assignEmployeeId')<p class="text-xs text-danger-600">{{ $message }}</p>@enderror
                @error('assignScheduleId')<p class="text-xs text-danger-600">{{ $message }}</p>@enderror
                @error('assignStartDate')<p class="text-xs text-danger-600">{{ $message }}</p>@enderror
                <x-filament::button type="submit" class="w-full">Simpan Penugasan</x-filament::button>
            </form>
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900 xl:col-span-2">
                <div class="border-b border-gray-200 p-4 dark:border-gray-700"><h2 class="font-semibold">Jadwal Aktif Karyawan</h2></div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 dark:bg-gray-800"><tr><th class="p-3">Karyawan</th><th class="p-3">Jadwal</th><th class="p-3">Mulai Berlaku</th></tr></thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($assignments as $assignment)
                                <tr><td class="p-3">{{ $assignment->employee_no }} — {{ $assignment->employee_name }}</td><td class="p-3">{{ $assignment->schedule_name }}</td><td class="p-3">{{ \Illuminate\Support\Carbon::parse($assignment->start_date)->format('d-m-Y') }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="p-5 text-center text-gray-500">Belum ada penugasan khusus. Karyawan menggunakan jadwal utama.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <form wire:submit="saveRules" class="max-w-3xl space-y-5 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
            <div><h2 class="font-semibold">Aturan Pengolahan Absensi</h2><p class="text-sm text-gray-500">Aturan global untuk perhitungan kehadiran dan lembur.</p></div>
            <label class="block text-sm">Toleransi keterlambatan (menit)
                <input type="number" min="0" max="240" wire:model="lateToleranceMinutes" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-950">
            </label>
            <label class="block text-sm">Jeda minimum antar scan untuk dihitung sebagai scan pulang (menit)
                <input type="number" min="1" max="240" wire:model="checkoutScanGapMinutes" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-950">
            </label>
            <label class="block text-sm">Ambang waktu sebelum dihitung lembur (menit)
                <input type="number" min="0" max="600" wire:model="overtimeThresholdMinutes" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-950">
            </label>
            <x-filament::button type="submit">Simpan Aturan</x-filament::button>
        </form>
    @endif
</x-filament-panels::page>
