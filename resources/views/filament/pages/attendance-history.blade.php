<x-filament-panels::page>
    <div wire:poll.10s="refreshHistory" class="space-y-4">
        <div class="flex flex-wrap items-end gap-3">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Bulan
                <input type="month" wire:model.live="filterMonth" class="mt-1 block h-10 rounded-lg border-gray-300 bg-white text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
            </label>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Lokasi Kerja
                <select wire:model.live="filterWorkLocationId" class="mt-1 block h-10 min-w-56 rounded-lg border-gray-300 bg-white text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                    <option value="">Semua Lokasi Kerja</option>
                    @foreach ($workLocations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </label>
            <div class="pb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $monthLabel }}</div>
            <div class="ml-auto pb-2 text-xs text-gray-500 dark:text-gray-400">Pembaruan otomatis</div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <table class="min-w-max divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="sticky top-0 z-20 bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="sticky left-0 z-30 min-w-56 border-r border-gray-200 bg-gray-50 px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">Karyawan Aktif</th>
                        @foreach ($days as $date)
                            <th class="min-w-28 px-2 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300">
                                <div>{{ \Illuminate\Support\Carbon::parse($date)->format('d') }}</div>
                                <div class="font-normal">{{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('D') }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($employees as $employee)
                        <tr wire:key="attendance-employee-{{ $employee->id }}" class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40">
                            <td class="sticky left-0 z-10 border-r border-gray-100 bg-white px-4 py-2 dark:border-gray-800 dark:bg-gray-900">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $employee->name }}</div>
                                <div class="text-xs text-gray-500">{{ $employee->employee_no }} · {{ $employee->work_location_name ?? 'Lokasi belum diatur' }}</div>
                            </td>
                            @foreach ($days as $date)
                                @php($cell = $matrix[$employee->id][$date])
                                @php($statusColors = [
                                    'hadir' => '#10b981',
                                    'izin' => '#3b82f6',
                                    'sakit' => '#f59e0b',
                                    'cuti' => '#8b5cf6',
                                    'dinas' => '#3b82f6',
                                    'alpa' => '#f43f5e',
                                ])
                                <td class="px-2 py-2 text-center" wire:key="attendance-cell-{{ $employee->id }}-{{ $date }}">
                                    <div class="flex min-w-24 flex-col items-center gap-1">
                                        @if ($cell['status'])
                                            <span style="background-color: {{ $statusColors[$cell['status']] ?? '#6b7280' }}; color: #ffffff;" class="rounded-md px-2 py-0.5 text-[10px] font-bold uppercase">
                                                {{ $cell['status'] === 'hadir' ? 'Hadir' : ($cell['status'] === 'alpa' ? 'Alpa' : ($cell['status'] === 'dinas' ? 'Dinas' : $cell['status'])) }}
                                            </span>
                                        @endif
                                        @if ($cell['check_in'] || $cell['check_out'])
                                            <div class="flex items-center gap-1 whitespace-nowrap text-xs font-semibold text-gray-800 dark:text-gray-100">
                                                <button type="button" wire:click="editAttendance({{ $employee->id }}, '{{ $date }}')" class="rounded px-1 py-0.5 hover:bg-primary-50 hover:text-primary-700 dark:hover:bg-primary-950" title="Ubah jam masuk">{{ $cell['check_in'] ?? '—' }}</button>
                                                <span class="text-gray-400">–</span>
                                                <button type="button" wire:click="editAttendance({{ $employee->id }}, '{{ $date }}')" class="rounded px-1 py-0.5 hover:bg-primary-50 hover:text-primary-700 dark:hover:bg-primary-950" title="Ubah jam pulang">{{ $cell['check_out'] ?? '—' }}</button>
                                            </div>
                                        @elseif (! $cell['status'])
                                            <button type="button" wire:click="editAttendance({{ $employee->id }}, '{{ $date }}')" class="rounded px-2 py-1 text-xs text-gray-400 hover:bg-gray-100 hover:text-primary-600 dark:hover:bg-gray-800" title="Tambah atau koreksi absensi">—</button>
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($days) + 1 }}" class="px-6 py-12 text-center text-sm text-gray-500">Tidak ada karyawan aktif pada lokasi kerja ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap gap-4 text-xs text-gray-600 dark:text-gray-300">
            <span class="flex items-center gap-1"><i class="h-2.5 w-2.5 rounded-full bg-emerald-500"></i>Hadir</span>
            <span class="flex items-center gap-1"><i class="h-2.5 w-2.5 rounded-full bg-blue-500"></i>Izin / Dinas</span>
            <span class="flex items-center gap-1"><i class="h-2.5 w-2.5 rounded-full bg-amber-500"></i>Sakit</span>
            <span class="flex items-center gap-1"><i class="h-2.5 w-2.5 rounded-full bg-violet-500"></i>Cuti</span>
            <span class="flex items-center gap-1"><i class="h-2.5 w-2.5 rounded-full bg-rose-500"></i>Alpa</span>
        </div>
    </div>

    @if ($showEditModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-950/50 p-4">
            <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl dark:bg-gray-900">
                <div class="mb-5 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Koreksi Absensi</h2>
                    <button type="button" wire:click="closeEditModal" class="text-gray-500">✕</button>
                </div>
                <div class="mb-4 text-sm text-gray-600 dark:text-gray-300">{{ $editingEmployeeName }} · {{ \Illuminate\Support\Carbon::parse($editingDate)->format('d/m/Y') }}</div>
                <form wire:submit="saveAttendanceCorrection" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Jam Masuk
                            <input type="time" wire:model="checkIn" class="mt-1 block h-10 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                            @error('checkIn')<span class="text-xs text-danger-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Jam Pulang
                            <input type="time" wire:model="checkOut" class="mt-1 block h-10 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                            @error('checkOut')<span class="text-xs text-danger-600">{{ $message }}</span>@enderror
                        </label>
                    </div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Alasan Koreksi
                        <textarea rows="3" wire:model="correctionReason" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
                        @error('correctionReason')<span class="text-xs text-danger-600">{{ $message }}</span>@enderror
                    </label>
                    <div class="flex justify-end gap-2">
                        <x-filament::button type="button" color="gray" wire:click="closeEditModal">Batal</x-filament::button>
                        <x-filament::button type="submit">Simpan Koreksi</x-filament::button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-filament-panels::page>
