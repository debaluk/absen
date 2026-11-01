<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <x-filament::badge color="gray">Total mesin</x-filament::badge>
                    <p class="mt-2 text-2xl font-semibold">{{ $totalDevices }}</p>
                </div>
                <div>
                    <x-filament::badge color="success">Terhubung</x-filament::badge>
                    <p class="mt-2 text-2xl font-semibold">{{ $onlineDevices }}</p>
                </div>
                <div>
                    <x-filament::badge color="danger">Terputus</x-filament::badge>
                    <p class="mt-2 text-2xl font-semibold">{{ $offlineDevices }}</p>
                </div>
            </div>
        </x-filament::section>

        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-base font-semibold">Daftar mesin</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Informasi dan status koneksi perangkat absensi.</p>
            </div>
            <x-filament::badge color="gray">{{ $totalDevices }} perangkat</x-filament::badge>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            @forelse ($devices as $device)
                @php
                    $status = $device->connection_state;
                    $statusLabel = match ($status) {
                        'online' => 'Terhubung',
                        'offline' => 'Terputus',
                        default => 'Belum diketahui',
                    };
                    $statusColor = match ($status) {
                        'online' => 'success',
                        'offline' => 'danger',
                        default => 'gray',
                    };
                @endphp

                <x-filament::section :key="'device-' . $device->id">
                    <x-slot name="heading">
                        {{ $device->device_name ?: $device->device_code }}
                    </x-slot>

                    <x-slot name="description">
                        {{ $device->device_code ?: 'Kode perangkat belum tersedia' }}
                        @if ($device->model)
                            · {{ $device->model }}
                        @endif
                        @if ($device->engine_name)
                            · Engine: {{ $device->engine_name }}
                        @endif
                    </x-slot>

                    <x-slot name="headerEnd">
                        <x-filament::badge :color="$statusColor">{{ $statusLabel }}</x-filament::badge>
                    </x-slot>

                    <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Terakhir aktif</dt>
                            <dd class="mt-1 text-sm font-medium">
                                {{ $device->last_seen_at ? IlluminateSupportCarbon::parse($device->last_seen_at)->format('d-m-Y H:i:s') : 'Belum ada data' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Karyawan terdaftar</dt>
                            <dd class="mt-1 text-sm font-medium">{{ $device->employee_count }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Cloud ID</dt>
                            <dd class="mt-1 break-all font-mono text-sm">{{ $device->freedom_device_id ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">IP Address</dt>
                            <dd class="mt-1 text-sm font-medium">{{ $device->ip_address ?: '—' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Serial Number</dt>
                            <dd class="mt-1 break-all text-sm font-medium">{{ $device->serial_number ?: '—' }}</dd>
                        </div>
                    </dl>

                    <x-slot name="footer">
                        <div class="flex flex-wrap justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <span>ID perangkat: {{ $device->id }}</span>
                            <span>Data perangkat dari Freedom Engine</span>
                        </div>
                    </x-slot>
                </x-filament::section>
            @empty
                <div class="col-span-full">
                    <x-filament::section>
                        <div class="py-8 text-center">
                            <x-filament::icon icon="heroicon-o-device-tablet" class="mx-auto h-8 w-8 text-gray-400" />
                            <h2 class="mt-3 text-base font-semibold">Belum ada mesin terdaftar</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Mesin akan tampil di sini setelah datanya terdaftar di sistem.</p>
                        </div>
                    </x-filament::section>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
