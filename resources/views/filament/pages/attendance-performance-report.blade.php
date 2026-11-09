<x-filament-panels::page>
    <div class="space-y-5">
        <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                    Periode
                    <input type="month" wire:model.live="filterMonth" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                </label>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                    Lokasi Kerja
                    <select wire:model.live="filterWorkLocationId" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        <option value="">Semua Lokasi</option>
                        @foreach ($workLocations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                    Departemen
                    <select wire:model.live="filterDepartmentId" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        <option value="">Semua Departemen</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                    Jabatan
                    <select wire:model.live="filterPositionId" class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        <option value="">Semua Jabatan</option>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}">{{ $position->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-300">
                    Cari Karyawan
                    <input type="search" wire:model.live.debounce.300ms="filterName" placeholder="Nama karyawan..." class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                </label>
            </div>
        </section>

        <section class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
            <div class="flex gap-3">
                <x-heroicon-o-information-circle class="h-5 w-5 shrink-0 text-blue-600 dark:text-blue-400" />
                <div>
                    <h2 class="text-sm font-semibold text-blue-900 dark:text-blue-200">Rancangan Laporan Performa — {{ $monthLabel }}</h2>
                    <p class="mt-1 text-xs leading-5 text-blue-800 dark:text-blue-300">
                        Tampilan dan filter laporan sudah disiapkan. Nilai performa belum dihitung atau ditampilkan sebagai hasil resmi sampai sumber jadwal kerja per karyawan dan aturan perhitungan periode diselesaikan.
                    </p>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Rincian Performa Karyawan</h2>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $employees->count() }} karyawan sesuai filter</p>
                </div>
                <span class="rounded-md bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">Periode Bulanan</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-gray-950">
                        <tr>
                            @foreach (['No.', 'Karyawan', 'Jabatan', 'Lokasi Kerja', 'Hari Efektif', 'Hadir', 'Izin', 'Sakit', 'Cuti', 'Dinas', 'ALPA', 'Terlambat (Menit)', 'Pulang Cepat (Menit)', 'Nilai Kehadiran', 'Nilai Ketepatan Waktu', 'Nilai Jam Kerja', 'Nilai Akhir', 'Kategori'] as $heading)
                                <th class="whitespace-nowrap px-3 py-3 text-left text-[10px] font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($employees as $index => $employee)
                            <tr wire:key="performance-report-{{ $employee->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="whitespace-nowrap px-3 py-3 text-xs text-gray-500">{{ $index + 1 }}</td>
                                <td class="whitespace-nowrap px-3 py-3">
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white">{{ $employee->name }}</div>
                                    <div class="text-[10px] text-gray-500">{{ $employee->employee_no }}</div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 text-xs text-gray-600 dark:text-gray-300">{{ $employee->position_name ?? '—' }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-xs text-gray-600 dark:text-gray-300">{{ $employee->work_location_name ?? '—' }}</td>
                                @for ($column = 0; $column < 14; $column++)
                                    <td class="px-3 py-3 text-center text-xs text-gray-400">—</td>
                                @endfor
                            </tr>
                        @empty
                            <tr>
                                <td colspan="18" class="px-6 py-10 text-center text-xs text-gray-500">Tidak ada karyawan aktif yang sesuai dengan filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
