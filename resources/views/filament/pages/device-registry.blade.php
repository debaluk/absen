<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total mesin</p>
                <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $totalDevices }}</p>
            </div>
            <div class="rounded-xl border border-success-200 bg-success-50 p-4 dark:border-success-800 dark:bg-success-950/30">
                <p class="text-sm text-success-700 dark:text-success-300">Terhubung</p>
                <p class="mt-1 text-2xl font-semibold text-success-700 dark:text-success-300">{{ $onlineDevices }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">Terputus</p>
                <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $offlineDevices }}</p>
            </div>
        </div>

        @forelse ($devices as $device)
            @php
                $isOnline = $device->connection_state === 'online';
                $statusLabel = match ($device->connection_state) {
                    'online' => 'Terhubung',
                    'offline' => 'Terputus',
                    default => 'Belum diketahui',
                };
            @endphp

            <section wire:key="device-{{ $device->id }}" class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                            {{ $device->device_name ?: $device->device_code }}
                        </h2>
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                            {{ $device->model ?: 'Model belum tercatat' }}
                            @if ($device->engine_name)
                                <span class="mx-1">·</span>{{ $device->engine_name }}
                            @endif
                        </p>
                    </div>
                    <span @class([
                        'inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium',
                        'bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-300' => $isOnline,
                        'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => ! $isOnline,
                    ])>
                        <span @class(['h-2 w-2 rounded-full', 'bg-success-500' => $isOnline, 'bg-gray-400' => ! $isOnline])></span>
                        {{ $statusLabel }}
                    </span>
                </div>

                <div class="grid grid-cols-1 gap-x-8 gap-y-5 p-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Jam mesin</p>
                        <p class="mt-1 font-semibold text-gray-900 dark:text-white">Belum tersedia</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Menunggu data waktu dari engine</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Terakhir aktif</p>
                        <p class="mt-1 font-semibold text-gray-900 dark:text-white">
                            {{ $device->last_seen_at ? \Illuminate\Support\Carbon::parse($device->last_seen_at)->format('d-m-Y H:i:s') : 'Belum ada data' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Cloud ID</p>
                        <p class="mt-1 break-all font-mono text-sm text-gray-900 dark:text-white">{{ $device->freedom_device_id ?: 'Belum tercatat' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">IP Address</p>
                        <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $device->ip_address ?: 'Belum tercatat' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Serial Number</p>
                        <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $device->serial_number ?: 'Belum tercatat' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Karyawan tercatat</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $device->employee_count }}</p>
                    </div>
                </div>

                <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-700">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Informasi tambahan</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Firmware, kemampuan pendaftaran, jam mesin, dan perintah perangkat ditampilkan setelah endpoint Freedom Engine untuk data tersebut terintegrasi.
                            </p>
                        </div>
                        <span class="text-xs text-gray-400">ID perangkat: {{ $device->id }}</span>
                    </div>
                </div>
            </section>
        @empty
            <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center dark:border-gray-700 dark:bg-gray-900">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800">
                    <x-heroicon-o-device-tablet class="h-6 w-6" />
                </div>
                <h2 class="mt-4 text-base font-semibold text-gray-950 dark:text-white">Belum ada perangkat terdaftar</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Perangkat akan muncul di sini setelah didaftarkan melalui integrasi Freedom Engine.</p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
