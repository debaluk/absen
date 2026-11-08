<?php

namespace App\Filament\Pages;

use App\Services\FreedomFingerMachineUserRefreshService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class Devices extends Page
{
    public static function canAccess(): bool
    {
        return (int) \Illuminate\Support\Facades\Auth::user()?->level === 0;
    }

    protected string $view = 'filament.pages.device-registry';

    protected static ?string $title = 'Mesin';

    protected static ?string $navigationLabel = 'Mesin';

    protected static string | UnitEnum | null $navigationGroup = 'Pengaturan';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?int $navigationSort = 4;

    protected ?string $subheading = 'Status koneksi dan informasi seluruh mesin absensi.';

    public function refreshMachineUsers(string $cloudId): void
    {
        $result = app(FreedomFingerMachineUserRefreshService::class)->start($cloudId);

        Notification::make()
            ->title($result['success'] ? 'Pengambilan data mesin dimulai.' : 'Pengambilan data mesin tidak dapat dimulai.')
            ->body($result['message'] ?? null)
            ->color($result['success'] ? 'success' : 'danger')
            ->send();
    }

    public function assignWorkLocation(string $cloudId, ?string $workLocationId): void
    {
        $deviceExists = DB::table('devices')->where('cloud_id', $cloudId)->exists();

        abort_unless($deviceExists, 404);

        if (blank($workLocationId)) {
            DB::table('devices_location')->where('cloud_id', $cloudId)->delete();

            Notification::make()
                ->title('Lokasi kerja perangkat dihapus.')
                ->success()
                ->send();

            return;
        }

        $locationExists = DB::table('work_locations')
            ->where('id', $workLocationId)
            ->where('is_active', true)
            ->exists();

        if (! $locationExists) {
            Notification::make()
                ->title('Lokasi kerja tidak valid atau tidak aktif.')
                ->danger()
                ->send();

            return;
        }

        DB::table('devices_location')->updateOrInsert(
            ['cloud_id' => $cloudId],
            ['work_location_id' => $workLocationId],
        );

        Notification::make()
            ->title('Lokasi kerja perangkat berhasil disimpan.')
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        $settings = DB::table('application_settings')
            ->whereIn('key', [
                'freedom_finger_engine_connected',
                'freedom_finger_engine_checked_at',
                'freedom_finger_engine_last_success_at',
                'freedom_finger_engine_info',
                'freedom_finger_engine_error',
            ])
            ->pluck('value', 'key');

        $engine = json_decode((string) ($settings['freedom_finger_engine_info'] ?? ''), true);
        $engine = is_array($engine) ? $engine : null;

        $workLocations = DB::table('work_locations')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $devices = DB::table('devices')
            ->leftJoin('devices_location', 'devices_location.cloud_id', '=', 'devices.cloud_id')
            ->leftJoin('work_locations', 'work_locations.id', '=', 'devices_location.work_location_id')
            ->leftJoin('machine_user_refreshes', 'machine_user_refreshes.cloud_id', '=', 'devices.cloud_id')
            ->orderBy('devices.name')
            ->get([
                'devices.cloud_id',
                'devices.name',
                'devices.info',
                'devices.last_activity',
                'devices_location.work_location_id',
                'work_locations.name as work_location_name',
                'machine_user_refreshes.status as refresh_status',
                'machine_user_refreshes.total_pins as refresh_total',
                'machine_user_refreshes.processed_pins as refresh_processed',
                'machine_user_refreshes.error_count as refresh_errors',
                'machine_user_refreshes.message as refresh_message',
                'machine_user_refreshes.finished_at as refresh_finished_at',
            ])
            ->map(function ($device) {
                return (object) [
                    'id' => $device->cloud_id,
                    'device_code' => $device->cloud_id,
                    'device_name' => $device->name,
                    'freedom_device_id' => $device->cloud_id,
                    'ip_address' => data_get(json_decode($device->info, true), 'ip'),
                    'last_seen_at' => $device->last_activity,
                    'connection_state' => data_get(json_decode($device->info, true), 'connected') ? 'online' : 'offline',
                    'work_location_id' => $device->work_location_id,
                    'work_location_name' => $device->work_location_name,
                    'refresh_status' => $device->refresh_status,
                    'refresh_total' => (int) ($device->refresh_total ?? 0),
                    'refresh_processed' => (int) ($device->refresh_processed ?? 0),
                    'refresh_errors' => (int) ($device->refresh_errors ?? 0),
                    'refresh_message' => $device->refresh_message,
                    'refresh_finished_at' => $device->refresh_finished_at,
                ];
            });

        return [
            'devices' => $devices,
            'workLocations' => $workLocations,
            'totalDevices' => $devices->count(),
            'onlineDevices' => $devices->where('connection_state', 'online')->count(),
            'offlineDevices' => $devices->where('connection_state', 'offline')->count(),
            'engineConnected' => ($settings['freedom_finger_engine_connected'] ?? null) === '1',
            'engineCheckedAt' => $settings['freedom_finger_engine_checked_at'] ?? null,
            'engineLastSuccessAt' => $settings['freedom_finger_engine_last_success_at'] ?? null,
            'engineVersion' => data_get($engine, 'version'),
            'engineServerIp' => data_get($engine, 'server_ip'),
            'enginePort' => data_get($engine, 'port'),
            'logsToday' => data_get($engine, 'logs_today'),
            'logsTotal' => data_get($engine, 'logs_total'),
            'connectionError' => $settings['freedom_finger_engine_error'] ?? null,
        ];
    }
}
