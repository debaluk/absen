<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <div class="flex flex-wrap items-center gap-x-8 gap-y-3">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-server-stack" class="h-5 w-5 text-gray-500" />
                    <span class="font-semibold">Total mesin</span>
                    <span class="text-lg font-semibold">{{ $totalDevices }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-signal" class="h-5 w-5 text-success-600" />
                    <span class="font-semibold">Terhubung</span>
                    <span class="text-lg font-semibold">{{ $onlineDevices }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-signal-slash" class="h-5 w-5 text-danger-600" />
                    <span class="font-semibold">Terputus</span>
                    <span class="text-lg font-semibold">{{ $offlineDevices }}</span>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Koneksi Freedom Finger</x-slot>
            <div class="flex flex-wrap items-center gap-x-6 gap-y-3 text-sm">
                <x-filament::badge :color="$engineConnected ? 'success' : 'danger'">
                    {{ $engineConnected ? 'Engine terhubung' : 'Engine tidak terhubung' }}
                </x-filament::badge>
                <span>Versi: <strong>{{ $engineVersion ?: '—' }}</strong></span>
                <span>Server: <strong>{{ $engineServerIp ?: '—' }}</strong></span>
                <span>Port: <strong>{{ $enginePort ?: '—' }}</strong></span>
                <span>Log hari ini: <strong>{{ $logsToday ?? '—' }}</strong></span>
                <span>Total log: <strong>{{ $logsTotal ?? '—' }}</strong></span>
            </div>
            @if ($connectionError)
                <p class="mt-3 text-sm text-danger-600">{{ $connectionError }}</p>
            @endif
        </x-filament::section>

        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-base font-semibold">Daftar mesin</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Data aktual perangkat dari Freedom Finger.</p>
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
                        Cloud ID: {{ $device->device_code ?: '—' }}
                    </x-slot>

                    <x-slot name="headerEnd">
                        <x-filament::badge :color="$statusColor">{{ $statusLabel }}</x-filament::badge>
                    </x-slot>

                    <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Terakhir aktif</dt>
                            <dd class="mt-1 text-sm font-medium">
                                {{ $device->last_seen_at ? \Illuminate\Support\Carbon::parse($device->last_seen_at)->format('d-m-Y H:i:s') : 'Belum ada data' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">IP Address mesin</dt>
                            <dd class="mt-1 text-sm font-medium">{{ $device->ip_address ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Cloud ID</dt>
                            <dd class="mt-1 break-all font-mono text-sm">{{ $device->freedom_device_id ?: '—' }}</dd>
                        </div>
                    </dl>

                    <x-slot name="footer">
                        <div class="flex flex-wrap justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <span>Freedom Finger</span>
                            <span>Data live dari engine</span>
                        </div>
                    </x-slot>
                </x-filament::section>
            @empty
                <div class="col-span-full">
                    <x-filament::section>
                        <div class="py-8 text-center">
                            <x-filament::icon icon="heroicon-o-device-tablet" class="mx-auto h-8 w-8 text-gray-400" />
                            <h2 class="mt-3 text-base font-semibold">Belum ada mesin terdeteksi</h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                @if ($connectionError)
                                    Periksa koneksi ke Freedom Finger.
                                @else
                                    Freedom Finger belum melaporkan perangkat yang terhubung.
                                @endif
                            </p>
                        </div>
                    </x-filament::section>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
