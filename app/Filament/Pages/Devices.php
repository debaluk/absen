<?php

namespace App\\Filament\\Pages;

use BackedEnum;
use Filament\\Pages\\Page;
use Illuminate\\Support\\Facades\\Http;
use Throwable;
use UnitEnum;

class Devices extends Page
{
    protected string $view = 'filament.pages.device-registry';

    protected static ?string $title = 'Mesin';

    protected static ?string $navigationLabel = 'Mesin';

    protected static string | UnitEnum | null $navigationGroup = 'Kelola';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?int $navigationSort = 4;

    protected ?string $subheading = 'Status koneksi dan informasi seluruh mesin absensi.';

    protected function getViewData(): array
    {
        $baseUrl = rtrim((string) config('services.freedom_finger.url', 'http://103.119.237.50:8013'), '/');
        $engine = null;
        $connectionError = null;
        $devices = collect();

        try {
            $response = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(8)
                ->get($baseUrl . '/status.json');

            if ($response->successful()) {
                $engine = $response->json();

                $devices = collect(data_get($engine, 'devices', []))
                    ->map(function (array $device, int $index) {
                        $connected = (bool) ($device['connected'] ?? false);

                        return (object) [
                            'id' => $device['cloud_id'] ?? ('device-' . ($index + 1)),
                            'device_code' => $device['cloud_id'] ?? null,
                            'device_name' => $device['device_name'] ?? null,
                            'freedom_device_id' => $device['cloud_id'] ?? null,
                            'serial_number' => null,
                            'model' => null,
                            'ip_address' => $device['ip'] ?? null,
                            'last_seen_at' => $device['last_activity'] ?? null,
                            'engine_name' => 'Freedom Finger',
                            'employee_count' => '—',
                            'connection_state' => $connected ? 'online' : 'offline',
                        ];
                    });
            } else {
                $connectionError = 'Freedom Finger merespons HTTP ' . $response->status() . '.';
            }
        } catch (Throwable $exception) {
            report($exception);
            $connectionError = 'Tidak dapat menghubungi Freedom Finger.';
        }

        return [
            'devices' => $devices,
            'totalDevices' => $devices->count(),
            'onlineDevices' => $devices->where('connection_state', 'online')->count(),
            'offlineDevices' => $devices->where('connection_state', 'offline')->count(),
            'engineConnected' => $engine !== null,
            'engineVersion' => data_get($engine, 'version'),
            'engineServerIp' => data_get($engine, 'server_ip'),
            'enginePort' => data_get($engine, 'port'),
            'logsToday' => data_get($engine, 'logs_today'),
            'logsTotal' => data_get($engine, 'logs_total'),
            'connectionError' => $connectionError,
        ];
    }
}
