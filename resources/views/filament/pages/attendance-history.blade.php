<x-filament-panels::page>
    <style>
        .attendance-history .ah-filter {
            min-height: 2.25rem;
            border: 1px solid rgb(209 213 219);
            border-radius: .5rem;
            background: white;
            padding: .375rem .625rem;
            font-size: .8125rem;
        }
        .dark .attendance-history .ah-filter {
            border-color: rgb(75 85 99);
            background: rgb(17 24 39);
            color: rgb(243 244 246);
        }
        .attendance-history .ah-table {
            border-collapse: separate;
            border-spacing: 0;
            font-size: .75rem;
        }
        .attendance-history .ah-table th,
        .attendance-history .ah-table td {
            border-bottom: 1px solid rgb(229 231 235);
        }
        .dark .attendance-history .ah-table th,
        .dark .attendance-history .ah-table td {
            border-color: rgb(55 65 81);
        }
        .attendance-history .ah-scroll {
            max-height: min(72vh, 52rem);
            overflow: auto;
            overscroll-behavior: contain;
            scrollbar-gutter: stable;
        }
        .attendance-history .ah-table thead th {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgb(249 250 251);
        }
        .dark .attendance-history .ah-table thead th {
            background: rgb(31 41 55);
        }
        .attendance-history .ah-day {
            min-width: 4.6rem;
            padding: .4rem .25rem;
            text-align: center;
        }
        .attendance-history .ah-day-off {
            background: rgb(229 231 235) !important;
            color: rgb(75 85 99) !important;
        }
        .dark .attendance-history .ah-day-off {
            background: rgb(55 65 81) !important;
            color: rgb(209 213 219) !important;
        }
        .attendance-history .ah-cell-off {
            background: rgb(249 250 251);
        }
        .dark .attendance-history .ah-cell-off {
            background: rgb(31 41 55 / 65%);
        }
        .attendance-history .ah-employee {
            position: sticky;
            left: 0;
            z-index: 30;
            min-width: 13rem;
            padding: .45rem .75rem;
            border-right: 1px solid rgb(229 231 235);
            background: white;
            text-align: left;
        }
        .dark .attendance-history .ah-employee {
            border-color: rgb(55 65 81);
            background: rgb(17 24 39);
        }
        .attendance-history .ah-table thead .ah-employee {
            z-index: 40;
            background: rgb(249 250 251);
        }
        .dark .attendance-history .ah-table thead .ah-employee {
            background: rgb(31 41 55);
        }
        .attendance-history .ah-cell {
            min-width: 4.6rem;
            padding: .25rem .125rem;
            text-align: center;
        }
        .attendance-history .ah-status {
            display: inline-block;
            border-radius: .25rem;
            padding: .1rem .3rem;
            color: white;
            font-size: .58rem;
            font-weight: 700;
            line-height: 1rem;
            text-transform: uppercase;
        }
        .attendance-history .ah-status-hadir { background: #10b981; }
        .attendance-history .ah-status-izin,
        .attendance-history .ah-status-dinas { background: #3b82f6; }
        .attendance-history .ah-status-sakit { background: #f59e0b; }
        .attendance-history .ah-status-cuti { background: #8b5cf6; }
        .attendance-history .ah-status-alpa { background: #f43f5e; }
        .attendance-history .ah-time {
            display: inline-flex;
            align-items: center;
            gap: .15rem;
            white-space: nowrap;
            color: rgb(55 65 81);
            font-size: .64rem;
            font-weight: 600;
            line-height: 1rem;
        }
        .dark .attendance-history .ah-time { color: rgb(229 231 235); }
        .attendance-history .ah-time button {
            border-radius: .2rem;
            padding: 0 .1rem;
        }
        .attendance-history .ah-time button:hover {
            background: rgb(243 244 246);
            color: rgb(29 78 216);
        }
        .dark .attendance-history .ah-time button:hover { background: rgb(31 41 55); }
        .attendance-history .ah-legend {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border: 1px solid rgb(229 231 235);
            border-radius: .375rem;
            padding: .2rem .45rem;
            background: white;
            font-size: .68rem;
            font-weight: 600;
        }
        .dark .attendance-history .ah-legend {
            border-color: rgb(55 65 81);
            background: rgb(17 24 39);
            color: rgb(229 231 235);
        }
        .attendance-history .ah-legend i {
            width: .45rem;
            height: .45rem;
            border-radius: 9999px;
        }
    </style>

    <div class="attendance-history space-y-3" wire:poll.visible.30s="refreshHistory">
        <div class="flex flex-wrap items-end gap-2">
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">Bulan
                <input type="month" wire:model.change="filterMonth" class="ah-filter mt-1 block">
            </label>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">Lokasi Kerja
                <select wire:model.change="filterWorkLocationId" class="ah-filter mt-1 block min-w-48">
                    <option value="">Semua Lokasi Kerja</option>
                    @foreach ($workLocations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </label>
            <div class="pb-2 text-xs font-semibold text-gray-600 dark:text-gray-300">{{ $monthLabel }}</div>
            <div class="ml-auto pb-2 text-[11px] text-gray-400">Pembaruan otomatis setiap 30 detik</div>
        </div>

        <div class="ah-scroll rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <table class="ah-table min-w-max w-full">
                <thead>
                    <tr>
                        <th class="ah-employee text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Karyawan Aktif</th>
                        @foreach ($days as $date)
                            <th @class([
                                'ah-day text-[10px] font-semibold text-gray-600 dark:text-gray-300',
                                'ah-day-off' => $dayOffMap[$date] ?? false,
                            ]) title="{{ ($dayOffMap[$date] ?? false) ? 'Hari libur' : 'Hari kerja' }}">
                                <div>{{ substr($date, 8, 2) }}</div>
                                <div class="font-normal">{{ ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'][\Illuminate\Support\Carbon::parse($date)->dayOfWeek] }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr wire:key="attendance-employee-{{ $employee->id }}" class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40">
                            <td class="ah-employee">
                                <div class="text-xs font-semibold text-gray-900 dark:text-white">{{ $employee->name }}</div>
                                <div class="text-[10px] text-gray-500">{{ $employee->employee_no }} · {{ $employee->work_location_name ?? 'Lokasi belum diatur' }}</div>
                            </td>
                            @foreach ($days as $date)
                                @php($cell = $matrix[$employee->id][$date])
                                @php($status = strtolower((string) $cell['status']))
                                <td @class([
                                    'ah-cell',
                                    'ah-cell-off' => $cell['is_day_off'] ?? false,
                                ]) wire:key="attendance-cell-{{ $employee->id }}-{{ $date }}">
                                    <div class="flex flex-col items-center gap-0.5">
                                        @if ($cell['status'])
                                            <span class="ah-status ah-status-{{ $status }}">
                                                {{ $status === 'hadir' ? 'Hadir' : ($status === 'alpa' ? 'Alpa' : ucfirst($status)) }}
                                            </span>
                                        @endif
                                        @if ($cell['check_in'] || $cell['check_out'])
                                            <div class="ah-time">
                                                <button type="button" wire:click="editAttendance({{ $employee->id }}, '{{ $date }}')" title="Ubah jam masuk">{{ $cell['check_in'] ?? '—' }}</button>
                                                <span class="text-gray-400">–</span>
                                                <button type="button" wire:click="editAttendance({{ $employee->id }}, '{{ $date }}')" title="Ubah jam pulang">{{ $cell['check_out'] ?? '—' }}</button>
                                            </div>
                                        @elseif (! $cell['status'])
                                            <button type="button" wire:click="editAttendance({{ $employee->id }}, '{{ $date }}')" class="text-[10px] text-gray-400 hover:text-primary-600" title="Tambah atau koreksi absensi">—</button>
                                        @endif
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($days) + 1 }}" class="px-6 py-10 text-center text-xs text-gray-500">Tidak ada karyawan aktif pada lokasi kerja ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap gap-1.5">
            <span class="ah-legend"><i style="background:#10b981"></i>Hadir</span>
            <span class="ah-legend"><i style="background:#3b82f6"></i>Izin</span>
            <span class="ah-legend"><i style="background:#f59e0b"></i>Sakit</span>
            <span class="ah-legend"><i style="background:#8b5cf6"></i>Cuti</span>
            <span class="ah-legend"><i style="background:#3b82f6"></i>Dinas</span>
            <span class="ah-legend"><i style="background:#f43f5e"></i>Alpa</span>
            <span class="ah-legend"><i style="background:#d1d5db"></i>Libur</span>
            <span class="w-full text-[11px] text-gray-500 dark:text-gray-400">Libur: Berdasarkan kalender umum dan jadwal kerja.</span>
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
