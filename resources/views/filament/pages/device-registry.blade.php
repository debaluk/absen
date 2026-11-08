<x-filament-panels::page>
    <div class="space-y-5">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <x-filament::section compact>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total mesin</span>
                    <x-filament::icon icon="heroicon-o-cpu-chip" class="h-5 w-5 text-gray-400" />
                </div>
                <div class="mt-2 text-2xl font-semibold tracking-tight">{{ $totalDevices }}</div>
            </x-filament::section>

            <x-filament::section compact>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Terhubung</span>
                    <x-filament::icon icon="heroicon-o-signal" class="h-5 w-5 text-success-600" />
                </div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-success-600">{{ $onlineDevices }}</div>
            </x-filament::section>

            <x-filament::section compact>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Terputus</span>
                    <x-filament::icon icon="heroicon-o-signal-slash" class="h-5 w-5 text-danger-600" />
                </div>
                <div class="mt-2 text-2xl font-semibold tracking-tight text-danger-600">{{ $offlineDevices }}</div>
            </x-filament::section>
        </div>

        <x-filament::section>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span @class([
                        'h-2.5 w-2.5 rounded-full',
                        'bg-success-500' => $engineConnected,
                        'bg-danger-500' => ! $engineConnected && $engineCheckedAt,
                        'bg-gray-400' => ! $engineCheckedAt,
                    ])></span>
                    <div>
                        <div class="font-semibold">Freedom Finger</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $engineConnected ? 'Engine terhubung' : ($engineCheckedAt ? 'Engine tidak terhubung' : 'Belum diperiksa') }}
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-x-6 gap-y-2 text-sm">
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Log hari ini</span>
                        <span class="ml-1 font-semibold">{{ $logsToday ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Total log</span>
                        <span class="ml-1 font-semibold">{{ $logsTotal ?? '—' }}</span>
                    </div>
                </div>
            </div>
            <div class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                <span>Pemeriksaan terakhir: {{ $engineCheckedAt ?: '—' }}</span>
                <span>Terakhir berhasil: {{ $engineLastSuccessAt ?: '—' }}</span>
            </div>
            @if ($connectionError)
                <p class="mt-3 text-sm text-danger-600">{{ $connectionError }}</p>
            @endif
        </x-filament::section>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2" wire:poll.15s>
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

                <div wire:key="device-{{ $device->id }}">
                    <x-filament::section>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-gray-100 p-2.5 dark:bg-gray-800">
                                <x-filament::icon icon="heroicon-o-device-tablet" class="h-6 w-6 text-gray-500" />
                            </div>
                            <div>
                                <h2 class="font-semibold">{{ $device->device_name ?: 'Mesin tanpa nama' }}</h2>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $device->device_code ?: 'Cloud ID tidak tersedia' }}
                                </p>
                            </div>
                        </div>
                        <x-filament::badge :color="$statusColor">{{ $statusLabel }}</x-filament::badge>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-4 border-t border-gray-200 pt-4 dark:border-gray-700 sm:grid-cols-2">
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">IP Address</div>
                            <div class="mt-1 text-sm font-medium">{{ $device->ip_address ?: '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">Terakhir aktif</div>
                            <div class="mt-1 text-sm font-medium">
                                {{ $device->last_seen_at ? \Illuminate\Support\Carbon::parse($device->last_seen_at)->format('d-m-Y H:i:s') : '—' }}
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="work-location-{{ $device->id }}" class="text-xs text-gray-500 dark:text-gray-400">
                                Lokasi
                            </label>
                            <select
                                id="work-location-{{ $device->id }}"
                                wire:change="assignWorkLocation('{{ $device->id }}', $event.target.value)"
                                class="mt-1 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                            >
                                <option value="">Pilih lokasi kerja</option>
                                @foreach ($workLocations as $location)
                                    <option
                                        value="{{ $location->id }}"
                                        @selected((string) $device->work_location_id === (string) $location->id)
                                    >
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2 flex flex-wrap items-center justify-between gap-3">
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $device->machine_users_count }} pengguna tersimpan
                                · {{ $device->biometric_checked_count }} biometrik diperiksa
                                @if ($device->biometric_pending_count > 0)
                                    · {{ $device->biometric_pending_count }} menunggu respons mesin
                                @endif
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <x-filament::button
                                    size="sm"
                                    color="gray"
                                    icon="heroicon-o-finger-print"
                                    wire:click="refreshMachineBiometrics('{{ $device->id }}')"
                                    wire:loading.attr="disabled"
                                    :disabled="$device->machine_users_count === 0"
                                >
                                    Ambil Biometrik
                                </x-filament::button>
                                <x-filament::button
                                    size="sm"
                                    color="primary"
                                    icon="heroicon-o-arrow-down-tray"
                                    wire:click="refreshMachineUsers('{{ $device->id }}')"
                                    wire:loading.attr="disabled"
                                >
                                    Ambil Data dari Mesin
                                </x-filament::button>
                            </div>
                        </div>
                    </div>
                    </x-filament::section>
                </div>
            @empty
                <div class="col-span-full">
                    <x-filament::section>
                        <div class="py-8 text-center">
                            <x-filament::icon icon="heroicon-o-device-tablet" class="mx-auto h-8 w-8 text-gray-400" />
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ $connectionError ?: 'Belum ada mesin yang terdeteksi.' }}
                            </p>
                        </div>
                    </x-filament::section>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
