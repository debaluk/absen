<x-filament-panels::page>
    <div class="space-y-4">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <x-filament::section compact>
                <div class="text-sm text-gray-500 dark:text-gray-400">Total seluruh log</div>
                <div class="mt-1 text-2xl font-semibold">{{ number_format($totalLogs, 0, ',', '.') }}</div>
            </x-filament::section>
            <x-filament::section compact>
                <div class="text-sm text-gray-500 dark:text-gray-400">Log sesuai filter</div>
                <div class="mt-1 text-2xl font-semibold">{{ number_format($filteredLogs, 0, ',', '.') }}</div>
            </x-filament::section>
        </div>

        <x-filament::section>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label for="log-date-from" class="mb-1 block text-sm font-medium">Dari tanggal</label>
                    <input id="log-date-from" type="date" wire:model.live="dateFrom"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                </div>
                <div>
                    <label for="log-date-to" class="mb-1 block text-sm font-medium">Sampai tanggal</label>
                    <input id="log-date-to" type="date" wire:model.live="dateTo"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                </div>
                <div>
                    <label for="log-device" class="mb-1 block text-sm font-medium">Mesin</label>
                    <select id="log-device" wire:model.live="device"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="">Semua mesin</option>
                        @foreach ($devices as $item)
                            <option value="{{ $item->cloud_id }}">{{ $item->name ?: $item->cloud_id }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="log-search" class="mb-1 block text-sm font-medium">Cari karyawan / PIN</label>
                    <input id="log-search" type="search" wire:model.live.debounce.400ms="search"
                        placeholder="Nama, PIN, atau Cloud ID"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                </div>
            </div>
            <div class="mt-3 flex justify-end">
                <x-filament::button color="gray" outlined
                    wire:click="resetFilters">
                    Reset filter
                </x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section :padding="false">
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800/60">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 text-left font-semibold">Waktu scan</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left font-semibold">Karyawan / PIN</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left font-semibold">Mesin</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left font-semibold">Status scan</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left font-semibold">Verifikasi</th>
                            <th class="whitespace-nowrap px-4 py-3 text-left font-semibold">Diterima server</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($logs as $log)
                            <tr wire:key="machine-log-{{ $log->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                <td class="whitespace-nowrap px-4 py-3 font-medium">
                                    {{ \Illuminate\Support\Carbon::parse($log->scan_date)->format('d-m-Y H:i:s') }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $log->user_name ?: 'Pengguna belum dikenal' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">PIN: {{ $log->pin }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div>{{ $log->device_name ?: 'Mesin tidak dikenal' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $log->cloud_id }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <x-filament::badge color="gray">{{ $log->status_scan === null ? '—' : 'Kode ' . $log->status_scan }}</x-filament::badge>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    {{ $log->verify === null ? '—' : 'Kode ' . $log->verify }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-500 dark:text-gray-400">
                                    {{ $log->received_at ? \Illuminate\Support\Carbon::parse($log->received_at)->format('d-m-Y H:i:s') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                                    Tidak ada log absensi untuk filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                {{ $logs->links() }}
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
