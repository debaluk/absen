<?php

namespace App\Filament\Pages;

use App\Jobs\StartMachineDataSync;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Throwable;
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
        if (config('queue.default') === 'sync') {
            Notification::make()->title('Queue belum aktif.')
                ->body('Pengambilan data mesin membutuhkan koneksi Queue database.')
                ->danger()->send();
            return;
        }

        if (! DB::table('devices')->where('cloud_id', $cloudId)->exists()) {
            Notification::make()->title('Mesin tidak ditemukan.')->danger()->send();
            return;
        }

        $status = DB::table('machine_data_syncs')->where('cloud_id', $cloudId)->value('status');

        if (in_array($status, ['queued', 'running'], true)) {
            Notification::make()->title('Pengambilan data mesin sedang berjalan.')
                ->body('Pantau progres pada kartu mesin tersebut.')->info()->send();
            return;
        }

        DB::table('machine_data_syncs')->updateOrInsert(
            ['cloud_id' => $cloudId],
            [
                'status' => 'queued',
                'total' => 0,
                'checked' => 0,
                'unavailable' => 0,
                'pending' => 0,
                'message' => null,
                'started_at' => null,
                'finished_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        try {
            StartMachineDataSync::dispatch($cloudId);

            Notification::make()->title('Pengambilan data mesin dimasukkan ke antrean.')
                ->body('Proses berjalan di belakang dan progres tampil pada kartu mesin.')
                ->success()->send();
        } catch (Throwable $exception) {
            report($exception);
            DB::table('machine_data_syncs')->where('cloud_id', $cloudId)->update([
                'status' => 'failed',
                'message' => 'Gagal memasukkan proses ke antrean.',
                'finished_at' => now(),
                'updated_at' => now(),
            ]);
            Notification::make()->title('Gagal memulai pengambilan data mesin.')->danger()->send();
        }
    }

    public function assignWorkLocation(string $cloudId, ?string $workLocationId): void
    {
        $deviceExists = DB::table('devices')->where('cloud_id', $cloudId)->exists();
        abort_unless($deviceExists, 404);

        if (blank($workLocationId)) {
            DB::table('devices_location')->where('cloud_id', $cloudId)->delete();
            Notification::make()->title('Lokasi kerja perangkat dihapus.')->success()->send();
            return;
        }

        $locationExists = DB::table('work_locations')
            ->where('id', $workLocationId)
            ->where('is_active', true)
            ->exists();

        if (! $locationExists) {
            Notification::make()->title('Lokasi kerja tidak valid atau tidak aktif.')->danger()->send();
            return;
        }

        DB::table('devices_location')->updateOrInsert(
            ['cloud_id' => $cloudId],
            ['work_location_id' => $workLocationId],
        );

        Notification::make()->title('Lokasi kerja perangkat berhasil disimpan.')->success()->send();
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
            ->leftJoin('machine_data_syncs as sync', 'sync.cloud_id', '=', 'devices.cloud_id')
            ->select([
                'devices.cloud_id',
                'devices.name',
                'devices.info',
                'devices.last_activity',
                'devices_location.work_location_id',
                'work_locations.name as work_location_name',
                'sync.status as sync_status',
                'sync.total as sync_total',
                'sync.checked as sync_checked',
                'sync.unavailable as sync_unavailable',
                'sync.pending as sync_pending',
                'sync.message as sync_message',
            ])
            ->selectSub(
                DB::table('machine_users')->selectRaw('COUNT(*)')
                    ->whereColumn('machine_users.cloud_id', 'devices.cloud_id'),
                'machine_users_count'
            )
            ->selectSub(
                DB::table('machine_users')->selectRaw('COUNT(*)')
                    ->whereColumn('machine_users.cloud_id', 'devices.cloud_id')
                    ->where('biometric_status', 'checked'),
                'biometric_checked_count'
            )
            ->selectSub(
                DB::table('machine_users')->selectRaw('COUNT(*)')
                    ->whereColumn('machine_users.cloud_id', 'devices.cloud_id')
                    ->where('biometric_status', 'pending'),
                'biometric_pending_count'
            )
            ->orderBy('devices.name')
            ->get()
            ->map(function ($device) {
                $total = (int) ($device->sync_total ?? 0);
                $checked = (int) ($device->sync_checked ?? 0);
                $unavailable = (int) ($device->sync_unavailable ?? 0);
                $processed = $checked + $unavailable;

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
                    'sync_status' => $device->sync_status,
                    'sync_total' => $total,
                    'sync_checked' => $checked,
                    'sync_unavailable' => $unavailable,
                    'sync_pending' => (int) ($device->sync_pending ?? 0),
                    'sync_processed' => $processed,
                    'sync_progress' => $total > 0 ? min(100, (int) round(($processed / $total) * 100)) : 0,
                    'sync_message' => $device->sync_message,
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
