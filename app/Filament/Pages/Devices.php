<?php

namespace App\Filament\Pages;

use App\Services\FreedomFingerBiometricSynchronizer;
use App\Services\FreedomFingerEmployeeSynchronizer;
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
        $result = app(FreedomFingerEmployeeSynchronizer::class)->syncMachineUsers($cloudId);

        Notification::make()
            ->title($result['success'] ? 'Data pengguna mesin diperbarui.' : 'Pengambilan data mesin gagal.')
            ->body($result['success']
                ? $result['synced'] . ' pengguna berhasil dibaca dari mesin ini.'
                : ($result['message'] ?? 'Periksa koneksi Freedom Finger.'))
            ->color($result['success'] ? 'success' : 'danger')
            ->send();
    }

    public function refreshMachineBiometrics(string $cloudId): void
    {
        $result = app(FreedomFingerBiometricSynchronizer::class)->requestForMachine($cloudId);

        Notification::make()
            ->title($result['success'] ? 'Pemeriksaan biometrik dikirim.' : 'Pemeriksaan biometrik gagal.')
            ->body($result['success']
                ? $result['requested'] . ' PIN diminta, ' . $result['skipped'] . ' masih diproses, '
                    . $result['failed'] . ' gagal meminta data. Hasil diperbarui saat mesin menjawab.'
                : ($result['message'] ?? 'Periksa koneksi Freedom Finger.'))
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
            ->selectSub(
                DB::table('machine_users')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('machine_users.cloud_id', 'devices.cloud_id'),
                'machine_users_count'
            )
            ->selectSub(
                DB::table('machine_users')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('machine_users.cloud_id', 'devices.cloud_id')
                    ->where('biometric_status', 'checked'),
                'biometric_checked_count'
            )
            ->selectSub(
                DB::table('machine_users')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('machine_users.cloud_id', 'devices.cloud_id')
                    ->where('biometric_status', 'pending'),
                'biometric_pending_count'
            )
            ->orderBy('devices.name')
            ->get([
                'devices.cloud_id',
                'devices.name',
                'devices.info',
                'devices.last_activity',
                'devices_location.work_location_id',
                'work_locations.name as work_location_name',
                'machine_users_count',
                'biometric_checked_count',
                'biometric_pending_count',
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
                    'machine_users_count' => (int) $device->machine_users_count,
                    'biometric_checked_count' => (int) $device->biometric_checked_count,
                    'biometric_pending_count' => (int) $device->biometric_pending_count,
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
