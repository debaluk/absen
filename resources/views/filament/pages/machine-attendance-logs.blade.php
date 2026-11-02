<x-filament-panels::page>
    <div class="space-y-5">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <x-filament::section compact>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total seluruh log</span>
                    <x-filament::icon icon="heroicon-o-clipboard-document-list" class="h-5 w-5 text-gray-400" />
                </div>
                <div class="mt-2 text-2xl font-semibold tracking-tight">{{ number_format($totalLogs, 0, ',', '.') }}</div>
            </x-filament::section>

            <x-filament::section compact>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Log sesuai filter</span>
                    <x-filament::icon icon="heroicon-o-funnel" class="h-5 w-5 text-gray-400" />
                </div>
                <div class="mt-2 text-2xl font-semibold tracking-tight">{{ number_format($filteredLogs, 0, ',', '.') }}</div>
            </x-filament::section>
        </div>

        <x-filament::section>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="font-semibold text-gray-950 dark:text-white">Sinkronisasi Freedom Finger</div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Aktifkan agar mesin, pengguna, dan log diperbarui otomatis setiap menit oleh service server.</p>
                </div>
                <label class="inline-flex cursor-pointer items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-200">
                    <input type="checkbox" wire:model.live="autoSyncEnabled" class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900">
                    <span>Aktifkan Sync Otomatis</span>
                </label>
                <x-filament::badge :color="$autoSyncEnabled ? 'success' : 'gray'">{{ $autoSyncEnabled ? 'SYNC AKTIF' : 'SYNC NONAKTIF' }}</x-filament::badge>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label for="log-date-from" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Dari tanggal</label>
                    <input id="log-date-from" type="date" wire:model.live="dateFrom"
                        class="block h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm leading-5 text-gray-950 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                </div>
                <div>
                    <label for="log-date-to" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Sampai tanggal</label>
                    <input id="log-date-to" type="date" wire:model.live="dateTo"
                        class="block h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm leading-5 text-gray-950 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                </div>
                <div>
                    <label for="log-device" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Mesin</label>
                    <select id="log-device" wire:model.live="device"
                        class="block h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm leading-5 text-gray-950 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                        <option value="">Semua mesin</option>
                        @foreach ($devices as $item)
                            <option value="{{ $item->cloud_id }}">{{ $item->name ?: $item->cloud_id }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="log-search" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cari pengguna / PIN</label>
                    <input id="log-search" type="search" wire:model.live.debounce.400ms="search"
                        placeholder="Nama, PIN, atau Cloud ID"
                        class="block h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm leading-5 text-gray-950 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                </div>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-700">
                <x-filament::button color="gray" outlined icon="heroicon-o-arrow-path" wire:click="resetFilters">
                    Reset filter
                </x-filament::button>
                <x-filament::button color="success" icon="heroicon-o-arrow-down-tray" wire:click="exportCsv">
                    Export CSV
                </x-filament::button>
            </div>
        </x-filament::section>

        <x-filament::section :padding="false">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-4 py-3 dark:border-gray-700 sm:px-6">
                <div class="font-semibold text-gray-950 dark:text-white">Daftar Log Absensi Mesin</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Menampilkan {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} dari {{ number_format($logs->total(), 0, ',', '.') }} data
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[950px] divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800/60">
                        <tr>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Waktu scan</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Pengguna / PIN</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Mesin</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Status scan</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Verifikasi</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">Diterima server</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-900">
                        @forelse ($logs as $log)
                            <tr wire:key="machine-log-{{ $log->id }}" class="transition hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="whitespace-nowrap px-4 py-3.5 font-medium text-gray-950 dark:text-white">
                                    {{ IlluminateSupportCarbon::parse($log->scan_date)->format('d-m-Y H:i:s') }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="font-medium text-gray-950 dark:text-white">{{ $log->user_name ?: 'Pengguna belum dikenal' }}</div>
                                    <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">PIN: {{ $log->pin }}</div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="font-medium text-gray-800 dark:text-gray-200">{{ $log->device_name ?: 'Mesin tidak dikenal' }}</div>
                                    <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $log->cloud_id }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5">
                                    <x-filament::badge color="gray">{{ $log->status_scan === null ? '—' : 'Kode ' . $log->status_scan }}</x-filament::badge>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-gray-700 dark:text-gray-300">
                                    {{ $log->verify === null ? '—' : 'Kode ' . $log->verify }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-gray-500 dark:text-gray-400">
                                    {{ $log->received_at ? IlluminateSupportCarbon::parse($log->received_at)->format('d-m-Y H:i:s') : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center">
                                    <x-filament::icon icon="heroicon-o-inbox" class="mx-auto h-8 w-8 text-gray-400" />
                                    <p class="mt-2 text-sm font-medium text-gray-700 dark:text-gray-300">Tidak ada log absensi</p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ubah filter tanggal atau kata kunci pencarian.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700 sm:px-6">
                <div class="mb-3 flex flex-wrap items-center gap-x-5 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                    <span class="font-semibold text-gray-700 dark:text-gray-300">Keterangan status scan:</span>
                    <span><span class="font-medium text-gray-700 dark:text-gray-200">Kode 0</span> = Masuk</span>
                    <span><span class="font-medium text-gray-700 dark:text-gray-200">Kode 1</span> = Pulang</span>
                </div>
                {{ $logs->onEachSide(1)->links() }}
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
