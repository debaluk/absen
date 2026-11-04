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
            border-collapse: separate;
            border-spacing: 0;
            font-size: .75rem;
        }
        .attendance-report .ar-table th,
        .attendance-report .ar-table td {
            border-bottom: 1px solid rgb(229 231 235);
        }
        .dark .attendance-report .ar-table th,
        .dark .attendance-report .ar-table td {
            border-color: rgb(55 65 81);
        }
        .attendance-report .ar-table thead th {
            background: rgb(249 250 251);
        }
        .dark .attendance-report .ar-table thead th {
            background: rgb(31 41 55);
        }
    </style>

    <div class="attendance-report space-y-3">
        <div class="flex flex-wrap items-end gap-2">
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                Bulan
                <input type="month" wire:model.change="filterMonth" class="ar-filter mt-1 block">
            </label>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                Lokasi Kerja
                <select wire:model.change="filterWorkLocationId" class="ar-filter mt-1 block min-w-48">
                    <option value="">Semua Lokasi Kerja</option>
                    @foreach ($workLocations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block min-w-56 flex-1 text-xs font-medium text-gray-600 dark:text-gray-300">
                Cari Nama Karyawan
                <input type="search" wire:model.live.debounce.300ms="filterName" class="ar-filter mt-1 block w-full" placeholder="Ketik nama karyawan...">
            </label>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <table class="ar-table min-w-[850px] w-full">
                <thead>
                    <tr>
                        <th class="px-3 py-3 text-left text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">No.</th>
                        <th class="px-3 py-3 text-left text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Karyawan Aktif</th>
                        <th class="px-3 py-3 text-left text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Lokasi Kerja</th>
                        <th class="px-3 py-3 text-center text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Jml. Hari</th>
                        <th class="px-3 py-3 text-center text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Hadir</th>
                        <th class="px-3 py-3 text-center text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Izin</th>
                        <th class="px-3 py-3 text-center text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Sakit</th>
                        <th class="px-3 py-3 text-center text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Cuti</th>
                        <th class="px-3 py-3 text-center text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Dinas</th>
                        <th class="px-3 py-3 text-center text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Alpa</th>
                        <th class="px-3 py-3 text-center text-[10px] font-semibold uppercase text-gray-600 dark:text-gray-300">Total</th>
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
                            <td class="px-3 py-2.5 text-center font-semibold tabular-nums">{{ $row['counts']['hadir'] }}/{{ $row['effective_days'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-6 py-10 text-center text-xs text-gray-500">
                                Tidak ada karyawan aktif yang sesuai dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="text-[11px] text-gray-500 dark:text-gray-400">Perhitungan berdasarkan log mesin, koreksi absensi, data izin, jadwal kerja, hari libur, dan tanggal mulai kewajiban absensi. Hari setelah tanggal hari ini belum dihitung.</p>
    </div>
</x-filament-panels::page>