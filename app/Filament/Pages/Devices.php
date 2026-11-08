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

    public ?string $syncingMachineCloudId = null;

    public bool $machineDataSyncing = false;

    public int $machineDataSyncTotal = 0;

    public int $machineDataSyncChecked = 0;

    public int $machineDataSyncUnavailable = 0;

    public int $machineDataSyncPending = 0;

    public function refreshMachineUsers(string $cloudId): void
    {
        if ($this->machineDataSyncing) {
            return;
        }

        $result = app(FreedomFingerEmployeeSynchronizer::class)->syncMachineUsers($cloudId);

        if (! $result['success']) {
            Notification::make()
                ->title('Pengambilan data mesin gagal.')
                ->body($result['message'] ?? 'Periksa koneksi Freedom Finger.')
                ->danger()
                ->send();

            return;
        }

        $this->syncingMachineCloudId = $cloudId;
        $this->machineDataSyncTotal = DB::table('machine_users')
            ->where('cloud_id', $cloudId)
            ->count();

        if ($this->machineDataSyncTotal === 0) {
            $this->syncingMachineCloudId = null;

            Notification::make()
                ->title('Data mesin diperbarui.')
                ->body('Tidak ada pengguna yang terdaftar pada mesin ini.')
                ->success()
                ->send();

            return;
        }

        $biometricResult = app(FreedomFingerBiometricSynchronizer::class)
            ->requestForMachine($cloudId);

        if (! $biometricResult['success']) {
            $this->syncingMachineCloudId = null;

            Notification::make()
                ->title('Data pengguna berhasil diambil, tetapi biometrik gagal dimulai.')
                ->body($biometricResult['message'] ?? 'Periksa koneksi Freedom Finger.')
                ->warning()
                ->send();

            return;
        }

        $this->machineDataSyncing = true;
        $this->refreshMachineDataProgress();
        $this->finishMachineDataSyncIfComplete();
    }

    public function continueMachineDataSync(): void
    {
        if (! $this->machineDataSyncing || blank($this->syncingMachineCloudId)) {
            return;
        }

        $result = app(FreedomFingerBiometricSynchronizer::class)
            ->syncNext($this->syncingMachineCloudId);

        $this->refreshMachineDataProgress();

        if ($this->finishMachineDataSyncIfComplete()) {
            return;
        }

        if (($result['status'] ?? null) === 'idle' && $this->machineDataSyncPending === 0) {
            $this->machineDataSyncing = false;
            $this->syncingMachineCloudId = null;

            Notification::make()
                ->title('Pemeriksaan biometrik belum selesai.')
                ->body('Sebagian pengguna belum mendapatkan hasil pemeriksaan. Silakan periksa status mesin.')
                ->warning()
                ->send();
        }
    }

    private function refreshMachineDataProgress(): void
    {
        $query = DB::table('machine_users')
            ->where('cloud_id', $this->syncingMachineCloudId);

        $this->machineDataSyncChecked = (clone $query)
            ->where('biometric_status', 'checked')
            ->count();

        $this->machineDataSyncUnavailable = (clone $query)
            ->where('biometric_status', 'unavailable')
            ->count();

        $this->machineDataSyncPending = (clone $query)
            ->where('biometric_status', 'pending')
            ->count();
    }

    private function finishMachineDataSyncIfComplete(): bool
    {
        $processed = $this->machineDataSyncChecked + $this->machineDataSyncUnavailable;

        if ($processed < $this->machineDataSyncTotal) {
            return false;
        }

        $checked = $this->machineDataSyncChecked;
        $unavailable = $this->machineDataSyncUnavailable;

        $this->machineDataSyncing = false;
        $this->syncingMachineCloudId = null;

        Notification::make()
            ->title('Pengambilan data mesin selesai.')
            ->body($checked . ' biometrik berhasil diperiksa; ' . $unavailable . ' tidak dapat diperiksa.')
            ->color($unavailable > 0 ? 'warning' : 'success')
            ->send();

        return true;
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
            ->select([
                'devices.cloud_id',
                'devices.name',
                'devices.info',
                'devices.last_activity',
                'devices_location.work_location_id',
                'work_locations.name as work_location_name',
            ])
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
            ->get()
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
