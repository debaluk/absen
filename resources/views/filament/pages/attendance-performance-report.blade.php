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

        @if ($formula)
            <section class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $formula->name }}</h2>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Formula {{ $formula->code }} · Berlaku {{ \Illuminate\Support\Carbon::parse($formula->effective_from)->format('d/m/Y') }}
                            @if ($formula->effective_until)
                                s.d. {{ \Illuminate\Support\Carbon::parse($formula->effective_until)->format('d/m/Y') }}
                            @else
                                · Tanpa batas akhir
                            @endif
                        </p>
                    </div>
                    <span class="rounded-md bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700 dark:bg-green-950 dark:text-green-300">Formula aktif</span>
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Bobot: Kehadiran {{ number_format($formula->attendance_weight, 2) }}% · Ketepatan waktu {{ number_format($formula->punctuality_weight, 2) }}% · Kepatuhan jam kerja {{ number_format($formula->worktime_weight, 2) }}%.
                </p>
            </section>
        @else
            <section class="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">Belum ada formula aktif untuk periode {{ $monthLabel }}.</p>
                <p class="mt-1 text-xs text-amber-800 dark:text-amber-300">Data kehadiran tetap dihitung, tetapi nilai akhir dan kategori belum dapat ditentukan sampai formula diaktifkan.</p>
            </section>
        @endif

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Rincian Performa Karyawan</h2>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ count($rows) }} karyawan · Periode {{ $monthLabel }}</p>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400">Data dihitung sampai {{ \Illuminate\Support\Carbon::parse($periodEnd)->format('d/m/Y') }}</span>
            </div>
            <div style="display: block; width: 100%; max-width: 100%; max-height: 65vh; overflow-x: auto; overflow-y: auto; overscroll-behavior: contain;">
                <table style="width: max-content; min-width: 100%; border-collapse: separate; border-spacing: 0;">
                    <thead>
                        <tr>
                            @foreach (['No.', 'Karyawan', 'Jabatan', 'Lokasi Kerja', 'Hari Efektif', 'Hadir', 'Izin', 'Sakit', 'Cuti', 'Dinas', 'ALPA', 'Terlambat (Menit)', 'Pulang Cepat (Menit)', 'Nilai Kehadiran', 'Nilai Ketepatan Waktu', 'Nilai Jam Kerja', 'Nilai Akhir', 'Kategori'] as $heading)
                                <th class="sticky top-0 z-20 whitespace-nowrap border-b border-gray-200 bg-gray-50 px-3 py-3 text-left text-[10px] font-semibold uppercase tracking-wide text-gray-600 dark:border-gray-800 dark:bg-gray-950 dark:text-gray-300 {{ $loop->first ? 'left-0 z-30 w-12 min-w-12' : ($loop->iteration === 2 ? 'left-12 z-30 min-w-[190px]' : '') }}">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($rows as $index => $row)
                            <tr wire:key="performance-report-{{ $row['employee']->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="sticky left-0 z-10 w-12 min-w-12 whitespace-nowrap border-b border-gray-100 bg-white px-3 py-3 text-xs text-gray-500 dark:border-gray-800 dark:bg-gray-900">{{ $index + 1 }}</td>
                                <td class="sticky left-12 z-10 min-w-[190px] whitespace-nowrap border-b border-gray-100 bg-white px-3 py-3 dark:border-gray-800 dark:bg-gray-900">
                                    <div class="text-xs font-semibold text-gray-900 dark:text-white">{{ $row['employee']->name }}</div>
                                    <div class="text-[10px] text-gray-500">{{ $row['employee']->employee_no }}</div>
                                </td>
                                <td class="whitespace-nowrap px-3 py-3 text-xs text-gray-600 dark:text-gray-300">{{ $row['employee']->position_name ?? '—' }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-xs text-gray-600 dark:text-gray-300">{{ $row['employee']->work_location_name ?? '—' }}</td>
                                <td class="px-3 py-3 text-center text-xs tabular-nums">{{ $row['effective_days'] }}</td>
                                @foreach (['hadir', 'izin', 'sakit', 'cuti', 'dinas', 'alpa'] as $key)
                                    <td class="px-3 py-3 text-center text-xs tabular-nums">{{ $row['counts'][$key] }}</td>
                                @endforeach
                                <td class="px-3 py-3 text-center text-xs tabular-nums">{{ number_format($row['counts']['late_minutes']) }}</td>
                                <td class="px-3 py-3 text-center text-xs tabular-nums">{{ number_format($row['counts']['early_leave_minutes']) }}</td>
                                <td class="px-3 py-3 text-center text-xs tabular-nums">{{ number_format($row['attendance_score'], 2) }}</td>
                                <td class="px-3 py-3 text-center text-xs tabular-nums">{{ number_format($row['punctuality_score'], 2) }}</td>
                                <td class="px-3 py-3 text-center text-xs tabular-nums">{{ number_format($row['worktime_score'], 2) }}</td>
                                <td class="px-3 py-3 text-center text-xs font-bold tabular-nums">{{ $row['final_score'] === null ? '—' : number_format($row['final_score'], 2) }}</td>
                                <td class="whitespace-nowrap px-3 py-3 text-xs font-semibold">{{ $row['grade'] ?? '—' }}</td>
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
        <p class="text-[11px] text-gray-500 dark:text-gray-400">
            Cuti dan sakit dikeluarkan dari hari efektif; dinas luar dihitung hadir; izin tetap masuk hari efektif; ALPA tetap masuk hari efektif dan menurunkan nilai kehadiran. Karyawan baru mulai dihitung sejak tanggal mulai kewajiban absensinya.
        </p>
    </div>
</x-filament-panels::page>
