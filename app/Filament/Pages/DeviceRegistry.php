<?php

namespace App\\Filament\\Pages;

use BackedEnum;
use Filament\\Pages\\Page;
use Illuminate\\Support\\Facades\\DB;
use UnitEnum;

class DeviceRegistry extends Page
{
    protected string $view = 'filament.pages.device-registry';

    protected static ?string $title = 'Perangkat';

    protected static ?string $navigationLabel = 'Perangkat';

    protected static string | UnitEnum | null $navigationGroup = 'Kelola';

    protected static ?string $navigationParentItem = 'Mesin';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-device-tablet';

    protected static ?int $navigationSort = 2;

    protected ?string $subheading = 'Status koneksi dan informasi perangkat absensi.';

    protected function getViewData(): array
    {
        $devices = DB::table('attendance_devices as devices')
            ->leftJoin('freedom_engines as engines', 'engines.id', '=', 'devices.engine_id')
            ->leftJoinSub(
                DB::table('device_status_logs')
                    ->select('device_id', DB::raw('MAX(checked_at) as latest_checked_at'))
                    ->groupBy('device_id'),
                'latest_status',
                'latest_status.device_id',
                '=',
                'devices.id'
            )
            ->leftJoin('device_status_logs as status_logs', function ($join) {
                $join->on('status_logs.device_id', '=', 'devices.id')
                    ->on('status_logs.checked_at', '=', 'latest_status.latest_checked_at');
            })
            ->select([
                'devices.id',
                'devices.device_code',
                'devices.device_name',
                'devices.freedom_device_id',
                'devices.serial_number',
                'devices.model',
                'devices.ip_address',
                'devices.last_seen_at',
                'devices.device_status',
                'engines.engine_name',
                'engines.connection_status as engine_status',
                'status_logs.status as latest_status',
                'status_logs.checked_at as status_checked_at',
            ])
            ->selectSub(
                DB::table('employee_devices')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('employee_devices.device_id', 'devices.id')
                    ->where('employee_devices.is_active', true),
                'employee_count'
            )
            ->orderBy('devices.device_name')
            ->get()
            ->map(function ($device) {
                $device->connection_state = $device->latest_status
                    ?? $device->engine_status
                    ?? 'unknown';

                return $device;
            });

        return [
            'devices' => $devices,
            'totalDevices' => $devices->count(),
            'onlineDevices' => $devices->where('connection_state', 'online')->count(),
            'offlineDevices' => $devices->where('connection_state', 'offline')->count(),
        ];
    }
}
