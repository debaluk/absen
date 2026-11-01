<?php

namespace App\Filament\Pages;

use App\Services\FreedomFingerDeviceSynchronizer;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class Devices extends Page
{
    protected string $view = 'filament.pages.device-registry';

    protected static ?string $title = 'Mesin';

    protected static ?string $navigationLabel = 'Mesin';

    protected static string | UnitEnum | null $navigationGroup = 'Pengaturan';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?int $navigationSort = 4;

    protected ?string $subheading = 'Status koneksi dan informasi seluruh mesin absensi.';

    protected function getViewData(): array
    {
        $sync = app(FreedomFingerDeviceSynchronizer::class)->sync();
        $engine = $sync['engine'];

        $devices = DB::table('freedom_finger_devices')
            ->orderBy('device_name')
            ->get()
            ->map(function ($device) {
                return (object) [
                    'id' => $device->id,
                    'device_code' => $device->cloud_id,
                    'device_name' => $device->device_name,
                    'freedom_device_id' => $device->cloud_id,
                    'ip_address' => $device->ip_address,
                    'last_seen_at' => $device->last_activity,
                    'connection_state' => $device->is_connected ? 'online' : 'offline',
                ];
            });

        return [
            'devices' => $devices,
            'totalDevices' => $devices->count(),
            'onlineDevices' => $devices->where('connection_state', 'online')->count(),
            'offlineDevices' => $devices->where('connection_state', 'offline')->count(),
            'engineConnected' => $sync['success'],
            'engineVersion' => data_get($engine, 'version'),
            'engineServerIp' => data_get($engine, 'server_ip'),
            'enginePort' => data_get($engine, 'port'),
            'logsToday' => data_get($engine, 'logs_today'),
            'logsTotal' => data_get($engine, 'logs_total'),
            'connectionError' => $sync['message'],
        ];
    }
}
