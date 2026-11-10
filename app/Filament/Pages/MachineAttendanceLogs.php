<?php

namespace App\Filament\Pages;

use App\Services\FreedomFingerAttendanceLogSynchronizer;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class MachineAttendanceLogs extends Page
{
    use WithPagination;

    protected string $view = 'filament.pages.machine-attendance-logs';

    protected static ?string $title = 'Log Absensi Mesin';

    protected static ?string $navigationLabel = 'Log Mesin';

    protected static string | UnitEnum | null $navigationGroup = 'Absensi';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 1;

    protected ?string $subheading = 'Data scan mentah yang diterima dari seluruh mesin absensi.';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $device = '';

    public string $search = '';

    public bool $autoSyncEnabled = false;

    public function mount(): void
    {
        $this->dateFrom = now()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->autoSyncEnabled = DB::table('application_settings')
            ->where('key', 'freedom_finger_auto_sync')
            ->value('value') === '1';
    }

    public function downloadLogs(FreedomFingerAttendanceLogSynchronizer $synchronizer): void
    {
        set_time_limit(0);

        $result = $synchronizer->sync(30);

        if (! $result['success']) {
            Notification::make()
                ->title('Download log mesin gagal.')
                ->body($result['message'] ?? 'Terjadi kesalahan saat mengambil log dari mesin.')
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $this->resetPage();

        Notification::make()
            ->title('Download log mesin selesai.')
            ->body(number_format((int) ($result['pulled'] ?? 0), 0, ',', '.') . ' log baru ditarik dari mesin; '
                . number_format((int) ($result['synced'] ?? 0), 0, ',', '.') . ' data log disinkronkan ke database.')
            ->success()
            ->send();
    }

    public function updatedAutoSyncEnabled(bool $enabled): void
    {
        $setting = DB::table('application_settings')->where('key', 'freedom_finger_auto_sync');

        if ($setting->exists()) {
            $setting->update(['value' => $enabled ? '1' : '0', 'updated_at' => now()]);
        } else {
            DB::table('application_settings')->insert([
                'key' => 'freedom_finger_auto_sync',
                'value' => $enabled ? '1' : '0',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Notification::make()
            ->title($enabled ? 'Sinkronisasi otomatis diaktifkan.' : 'Sinkronisasi otomatis dinonaktifkan.')
            ->success()
            ->send();
    }

    public function updated($property): void
    {
        if (in_array($property, ['dateFrom', 'dateTo', 'device', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function goToLogsPage(int $page): void
    {
        $lastPage = max(1, (int) ceil($this->logsQuery()->count('logs.id') / 15));

        $this->setPage(min(max(1, $page), $lastPage));
    }

    public function resetFilters(): void
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->device = '';
        $this->search = '';
        $this->resetPage();
    }

    public function exportExcel(): StreamedResponse
    {
        $logs = $this->logsQuery()
            ->orderByDesc('logs.scan_date')
            ->orderByDesc('logs.id')
            ->cursor();

        $rows = $logs->map(fn ($log) => [
            \Illuminate\Support\Carbon::parse($log->scan_date)->format('d-m-Y H:i:s'),
            ($log->user_name ?: 'Pengguna belum dikenal') . "\nPIN: " . $log->pin,
            ($log->device_name ?: 'Mesin tidak dikenal') . "\n" . $log->cloud_id,
            $log->status_scan === null ? '—' : 'Kode ' . $log->status_scan,
            $log->verify === null ? '—' : 'Kode ' . $log->verify,
            $log->received_at ? \Illuminate\Support\Carbon::parse($log->received_at)->format('d-m-Y H:i:s') : '—',
        ]);

        $period = ($this->dateFrom || $this->dateTo)
            ? ($this->dateFrom ?: 'Awal') . ' s.d. ' . ($this->dateTo ?: 'Sekarang')
            : 'Semua Periode';
        $deviceName = $this->device !== ''
            ? (DB::table('devices')->where('cloud_id', $this->device)->value('name') ?: $this->device)
            : 'Semua Mesin';

        return \App\Support\ExcelExport::download(
            'log-absensi-mesin-' . now()->format('Ymd-His') . '.xls',
            'Log Mesin',
            'LOG ABSENSI MESIN',
            DB::table('companies')->orderBy('id')->first(),
            $period,
            '—',
            $deviceName,
            ['Waktu scan', 'Pengguna / PIN', 'Mesin', 'Status scan', 'Verifikasi', 'Diterima server'],
            $rows,
            [130, 180, 180, 90, 90, 140],
        );
    }
    protected function logsQuery(): Builder
    {
        $query = DB::table('logs')
            ->leftJoin('devices', 'devices.cloud_id', '=', 'logs.cloud_id')
            ->leftJoin('machine_users', function ($join) {
                $join->on('machine_users.cloud_id', '=', 'logs.cloud_id')
                    ->on('machine_users.pin', '=', 'logs.pin');
            })
            ->select([
                'logs.id', 'logs.cloud_id', 'logs.pin', 'logs.scan_date',
                'logs.verify', 'logs.status_scan', 'logs.photo', 'logs.received_at',
                'devices.name as device_name', 'machine_users.name as user_name',
            ]);

        if ($this->dateFrom !== '') {
            $query->whereDate('logs.scan_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo !== '') {
            $query->whereDate('logs.scan_date', '<=', $this->dateTo);
        }

        if ($this->device !== '') {
            $query->where('logs.cloud_id', $this->device);
        }

        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('logs.pin', 'like', '%' . $search . '%')
                    ->orWhere('machine_users.name', 'like', '%' . $search . '%')
                    ->orWhere('devices.name', 'like', '%' . $search . '%')
                    ->orWhere('logs.cloud_id', 'like', '%' . $search . '%');
            });
        }

        return $query;
    }

    protected function getViewData(): array
    {
        $query = $this->logsQuery();

        return [
            'logs' => $query->orderByDesc('logs.scan_date')->orderByDesc('logs.id')->paginate(15),
            'devices' => DB::table('devices')->orderBy('name')->get(['cloud_id', 'name']),
            'totalLogs' => DB::table('logs')->count(),
            'filteredLogs' => (clone $query)->count('logs.id'),
        ];
    }
}
