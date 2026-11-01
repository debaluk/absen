<x-filament-panels::page>
    @if (! $syncSuccess)
        <div class="rounded-lg border border-warning-300 bg-warning-50 px-4 py-3 text-sm text-warning-800 dark:border-warning-700 dark:bg-warning-950 dark:text-warning-200">
            {{ $syncMessage }}
            <span class="block mt-1">Daftar di bawah tetap dibaca dari data yang terakhir tersimpan di Laravel.</span>
        </div>
    @else
        <div class="rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-800 dark:border-success-800 dark:bg-success-950 dark:text-success-200">
            Sinkronisasi berhasil. {{ number_format($syncedCount, 0, ',', '.') }} data karyawan diproses dari mesin.
        </div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <div>
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Daftar Karyawan</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Sumber tampilan: tabel Laravel</p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-semibold text-gray-950 dark:text-white">{{ number_format($totalEmployees, 0, ',', '.') }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Total karyawan</div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">No.</th>
                        <th class="px-4 py-3">PIN</th>
                        <th class="px-4 py-3">Nama Karyawan</th>
                        <th class="px-4 py-3">Cloud ID Mesin</th>
                        <th class="px-4 py-3">Terakhir Sinkron</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($employees as $employee)
                        <tr class="text-gray-700 dark:text-gray-200">
                            <td class="whitespace-nowrap px-4 py-3">{{ $loop->iteration }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $employee->pin }}</td>
                            <td class="px-4 py-3">{{ $employee->name ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs">{{ $employee->cloud_id }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-gray-500 dark:text-gray-400">
                                {{ $employee->last_synced_at ? \Illuminate\Support\Carbon::parse($employee->last_synced_at)->format('d-m-Y H:i:s') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                Belum ada data karyawan yang tersimpan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
