<x-filament-panels::page>
    <div class="space-y-5">
        {{-- Ringkasan status perangkat --}}
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Total mesin</span>
                    <x-heroicon-o-device-tablet class="h-5 w-5 text-gray-400" />
                </div>
                <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $totalDevices }}</p>
            </div>
            <div class="rounded-xl border border-success-200 bg-success-50/70 px-4 py-3 dark:border-success-900 dark:bg-success-950/20">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-success-700 dark:text-success-300">Terhubung</span>
                    <x-heroicon-o-signal class="h-5 w-5 text-success-600 dark:text-success-400" />
                </div>
                <p class="mt-2 text-2xl font-semibold tracking-tight text-success-700 dark:text-success-300">{{ $onlineDevices }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500 dark:text-gray-400">Terputus</span>
                    <x-heroicon-o-signal-slash class="h-5 w-5 text-gray-400" />
                </div>
                <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $offlineDevices }}</p>
            </div>
        </div>

        @if ($devices->isNotEmpty())
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Daftar mesin</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Informasi dan status koneksi perangkat absensi.</p>
                </div>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $totalDevices }} perangkat</span>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            @forelse ($devices as $device)
                @php
                    $isOnline = $device->connection_state === 'online';
                    $statusLabel = match ($device->connection_state) {
                        'online' => 'Terhubung',
                        'offline' => 'Terputus',
                        default => 'Belum diketahui',
                    };
                @endphp

                <section wire:key="device-{{ $device->id }}" class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                    <header class="flex items-start justify-between gap-3 border-b border-gray-100 px-4 py-4 dark:border-gray-800 sm:px-5">
                        <div class="flex min-w-0 items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-400/10 dark:text-primary-400">
                                <x-heroicon-o-device-tablet class="h-5 w-5" />
                            </div>
                            <div class="min-w-0">
                                <h3 class="truncate font-semibold text-gray-950 dark:text-white">
                                    {{ $device->device_name ?: $device->device_code }}
                                </h3>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $device->device_code ?: 'Kode perangkat belum tersedia' }}
                                    @if ($device->model)
                                        <span class="mx-1">·</span>{{ $device->model }}
                                    @endif
                                </p>
                                @if ($device->engine_name)
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Engine: {{ $device->engine_name }}</p>
                                @endif
                            </div>
                        </div>
                        <span @class([
                            'inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium',
                            'bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-300' => $isOnline,
                            'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' => ! $isOnline,
                        ])>
                            <span @class(['h-1.5 w-1.5 rounded-full', 'bg-success-500' => $isOnline, 'bg-gray-400' => ! $isOnline])></span>
                            {{ $statusLabel }}
                        </span>
                    </header>

                    <div class="grid grid-cols-2 gap-x-4 gap-y-4 px-4 py-4 sm:px-5">
                        <div class="col-span-2 sm:col-span-1">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Terakhir aktif</p>
                            <p class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                                {{ $device->last_seen_at ? \Illuminate\Support\Carbon::parse($device->last_seen_at)->format('d-m-Y H:i:s') : 'Belum ada data' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Karyawan terdaftar</p>
                            <p class="mt-1 text-sm font-semibold text-gray-950 dark:text-white">{{ $device->employee_count }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Cloud ID</p>
                            <p class="mt-1 break-all font-mono text-xs text-gray-800 dark:text-gray-200">{{ $device->freedom_device_id ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">IP Address</p>
                            <p class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $device->ip_address ?: '—' }}</p>
                        </div>
                        <div class="col-span-2 border-t border-gray-100 pt-3 dark:border-gray-800 sm:col-span-1 sm:border-0 sm:pt-0">
                            <p class="text-xs text-gray-500 dark:text-gray-400">Serial Number</p>
                            <p class="mt-1 break-all text-sm font-medium text-gray-950 dark:text-white">{{ $device->serial_number ?: '—' }}</p>
                        </div>
                    </div>

                    <footer class="flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 bg-gray-50/70 px-4 py-3 dark:border-gray-800 dark:bg-gray-950/40 sm:px-5">
                        <span class="text-xs text-gray-500 dark:text-gray-400">ID perangkat: {{ $device->id }}</span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">Data perangkat dari Freedom Engine</span>
                    </footer>
                </section>
            @empty
                <div class="col-span-full rounded-xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center dark:border-gray-700 dark:bg-gray-900">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800">
                        <x-heroicon-o-device-tablet class="h-6 w-6" />
                    </div>
                    <h2 class="mt-4 text-base font-semibold text-gray-950 dark:text-white">Belum ada mesin terdaftar</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Mesin akan tampil di sini setelah datanya terdaftar di sistem.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
