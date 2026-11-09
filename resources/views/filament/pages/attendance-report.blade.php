<x-filament-panels::page>
    <style>
        .attendance-report .ar-filter {
            min-height: 2.25rem;
            border: 1px solid rgb(209 213 219);
            border-radius: .5rem;
            background: white;
            padding: .375rem .625rem;
            color: rgb(17 24 39);
            font-size: .8125rem;
        }
        .dark .attendance-report .ar-filter {
            border-color: rgb(75 85 99);
            background: rgb(17 24 39);
            color: rgb(243 244 246);
        }
        .attendance-report .ar-table {
            width: 100%;
            min-width: 1180px;
            table-layout: fixed;
            border-collapse: separate;
            border-spacing: 0;
            font-size: .75rem;
        }
        .attendance-report .ar-table thead th {
            white-space: normal;
            overflow-wrap: anywhere;
            line-height: 1.25;
        }
        .attendance-report .ar-table th,
        .attendance-report .ar-table td {
            border-bottom: 1px solid rgb(229 231 235);
        }
        .dark .attendance-report .ar-table th,
        .dark .attendance-report .ar-table td {
            border-color: rgb(55 65 81);
        }
        .attendance-report .ar-scroll {
            max-height: min(72vh, 52rem);
            overflow: auto;
            overscroll-behavior: contain;
            scrollbar-gutter: stable;
        }
        .attendance-report .ar-table thead th {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgb(249 250 251);
        }
        .dark .attendance-report .ar-table thead th {
            background: rgb(31 41 55);
        }
    </style>

    <div class="attendance-report space-y-3">
        <div class="flex flex-wrap items-end gap-2">
            <div wire:loading.flex wire:target="filterMonth,filterWorkLocationId,filterDepartmentId,filterName" class="items-center gap-2 rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700 dark:border-primary-800 dark:bg-primary-950 dark:text-primary-300">
                <x-heroicon-o-arrow-path class="h-4 w-4 animate-spin" />
                Memuat rekap...
            </div>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                Bulan
                <input type="month" wire:model.change="filterMonth" class="ar-filter mt-1 block">
            </label>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                Lokasi Kerja
                <select wire:model.change="filterWorkLocationId" class="ar-filter mt-1 block min-w-48">
                    <option value="">Semua Lokasi</option>
                    @foreach ($workLocations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                Departemen
                <select wire:model.change="filterDepartmentId" class="ar-filter mt-1 block min-w-48">
                    <option value="">Semua Departemen</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block min-w-56 flex-1 text-xs font-medium text-gray-600 dark:text-gray-300">
                Cari Nama Karyawan
                <input type="search" wire:model.live.debounce.300ms="filterName" class="ar-filter mt-1 block w-full" placeholder="Ketik nama karyawan...">
            </label>
            <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel" class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg border border-green-700 bg-green-700 px-4 text-sm font-bold text-white shadow-md transition hover:border-green-800 hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-70">
                <span wire:loading.remove wire:target="exportExcel" class="inline-flex items-center gap-2">
                    <x-heroicon-o-arrow-down-tray class="h-5 w-5" />
                    Unduh Excel
                </span>
                <span wire:loading.flex wire:target="exportExcel" class="items-center gap-2">
                    <x-heroicon-o-arrow-path class="h-5 w-5 animate-spin" />
                    Menyiapkan...
                </span>
            </button>
        </div>

        <div class="relative">
            <div wire:loading.flex wire:target="filterMonth,filterWorkLocationId,filterDepartmentId,filterName" class="absolute inset-0 z-30 items-start justify-center bg-white/60 pt-16 backdrop-blur-[1px] dark:bg-gray-950/60">
                <div class="inline-flex h-fit items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-3 text-xs font-semibold text-gray-700 shadow-lg dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                    <x-heroicon-o-arrow-path class="h-4 w-4 animate-spin text-primary-600" />
                    Memperbarui data rekap...
                </div>
            </div>
            <div class="ar-scroll rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <table class="ar-table">
                <colgroup>
                    <col style="width: 52px">
                    <col style="width: 190px">
                    <col style="width: 150px">
                    <col style="width: 76px">
                    <col style="width: 62px">
                    <col style="width: 62px">
                    <col style="width: 62px">
                    <col style="width: 62px">
                    <col style="width: 62px">
                    <col style="width: 62px">
                    <col style="width: 100px">
                    <col style="width: 110px">
                    <col style="width: 90px">
                </colgroup>
                <thead>
                    <tr>
                        <th class="px-2 py-3 text-left text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">No.</th>
                        <th class="px-3 py-3 text-left text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Karyawan Aktif</th>
                        <th class="px-3 py-3 text-left text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Lokasi Kerja</th>
                        <th class="px-3 py-3 text-center text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Jml. Hari</th>
                        <th class="px-3 py-3 text-center text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Hadir</th>
                        <th class="px-3 py-3 text-center text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Izin</th>
                        <th class="px-3 py-3 text-center text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Sakit</th>
                        <th class="px-3 py-3 text-center text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Cuti</th>
                        <th class="px-3 py-3 text-center text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Dinas</th>
                        <th class="px-3 py-3 text-center text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Alpa</th>
                        <th class="px-3 py-3 text-center text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Terlambat (Menit)</th>
                        <th class="px-3 py-3 text-center text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Pulang Cepat (Menit)</th>
                        <th class="px-3 py-3 text-center text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Kehadiran</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $index => $row)
                        <tr wire:key="attendance-report-{{ $row['employee']->id }}" class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40">
                            <td class="px-3 py-2.5 text-gray-500">{{ $index + 1 }}</td>
                            <td class="px-3 py-2.5">
                                <div class="text-xs font-semibold text-gray-900 dark:text-white">{{ $row['employee']->name }}</div>
                                <div class="text-[10px] text-gray-500">{{ $row['employee']->employee_no }}</div>
                            </td>
                            <td class="px-3 py-2.5 text-xs text-gray-600 dark:text-gray-300">{{ $row['employee']->work_location_name ?? 'Belum diatur' }}</td>
                            <td class="px-3 py-2.5 text-center font-medium tabular-nums">{{ $row['effective_days'] }}</td>
                            @foreach (['hadir', 'izin', 'sakit', 'cuti', 'dinas', 'alpa'] as $key)
                                <td class="px-3 py-2.5 text-center tabular-nums">{{ $row['counts'][$key] }}</td>
                            @endforeach
                            <td class="px-3 py-2.5 text-center tabular-nums">{{ number_format($row['counts']['late_minutes']) }}</td>
                            <td class="px-3 py-2.5 text-center tabular-nums">{{ number_format($row['counts']['early_leave_minutes']) }}</td>
                            <td class="px-3 py-2.5 text-center font-semibold tabular-nums">{{ $row['counts']['hadir'] }}/{{ $row['effective_days'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="px-6 py-10 text-center text-xs text-gray-500">
                                Tidak ada karyawan aktif yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        <p class="text-[11px] text-gray-500 dark:text-gray-400">Perhitungan berdasarkan log mesin, koreksi absensi, data izin, jadwal kerja, hari libur, dan tanggal mulai kewajiban absensi. Hari setelah tanggal hari ini belum dihitung.</p>
    </div>
</x-filament-panels::page>