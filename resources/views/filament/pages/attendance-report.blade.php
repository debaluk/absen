<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex flex-wrap items-end gap-3">
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                Bulan
                <input type="month" wire:model.change="filterMonth" class="mt-1 block rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900">
            </label>
            <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                Lokasi Kerja
                <select wire:model.change="filterWorkLocationId" class="mt-1 block min-w-48 rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900">
                    <option value="">Semua Lokasi Kerja</option>
                    @foreach ($workLocations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </label>
            <div class="pb-2 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ $monthLabel }}</div>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
            @foreach (['hadir' => 'Hadir', 'izin' => 'Izin', 'sakit' => 'Sakit', 'cuti' => 'Cuti', 'dinas' => 'Dinas', 'alpa' => 'Alpa'] as $key => $label)
                <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $totals[$key] }}</div>
                </div>
            @endforeach
        </div>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <table class="w-full min-w-[850px] divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 text-xs uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    <tr>
                        <th class="px-3 py-3 text-left">No.</th>
                        <th class="px-3 py-3 text-left">Karyawan</th>
                        <th class="px-3 py-3 text-left">Lokasi Kerja</th>
                        <th class="px-3 py-3 text-center">Hadir</th>
                        <th class="px-3 py-3 text-center">Izin</th>
                        <th class="px-3 py-3 text-center">Sakit</th>
                        <th class="px-3 py-3 text-center">Cuti</th>
                        <th class="px-3 py-3 text-center">Dinas</th>
                        <th class="px-3 py-3 text-center">Alpa</th>
                        <th class="px-3 py-3 text-center">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($rows as $index => $row)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60">
                            <td class="px-3 py-2.5 text-gray-500">{{ $index + 1 }}</td>
                            <td class="px-3 py-2.5">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $row['employee']->name }}</div>
                                <div class="text-xs text-gray-500">{{ $row['employee']->employee_no }}</div>
                            </td>
                            <td class="px-3 py-2.5 text-gray-600 dark:text-gray-300">{{ $row['employee']->work_location_name ?? 'Belum diatur' }}</td>
                            @foreach (['hadir', 'izin', 'sakit', 'cuti', 'dinas', 'alpa'] as $key)
                                <td class="px-3 py-2.5 text-center tabular-nums">{{ $row['counts'][$key] }}</td>
                            @endforeach
                            <td class="px-3 py-2.5 text-center font-semibold tabular-nums">{{ $row['total'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-3 py-8 text-center text-gray-500">Tidak ada karyawan aktif.</td></tr>
                    @endforelse
                </tbody>
                @if (count($rows))
                    <tfoot class="bg-gray-50 font-semibold dark:bg-gray-800">
                        <tr>
                            <td colspan="3" class="px-3 py-3 text-right">Total</td>
                            @foreach (['hadir', 'izin', 'sakit', 'cuti', 'dinas', 'alpa'] as $key)
                                <td class="px-3 py-3 text-center tabular-nums">{{ $totals[$key] }}</td>
                            @endforeach
                            <td class="px-3 py-3 text-center tabular-nums">{{ array_sum($totals) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        <p class="text-xs text-gray-500 dark:text-gray-400">Rekap dihitung dari log mesin, koreksi absensi, data izin, jadwal kerja, hari libur, dan tanggal mulai kewajiban absensi. Hari setelah tanggal hari ini belum dihitung.</p>
    </div>
</x-filament-panels::page>
