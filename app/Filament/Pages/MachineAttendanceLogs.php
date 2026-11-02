<?php

namespace App\Filament\Pages;

use BackedEnum;
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

    public function mount(): void
    {
        $this->dateFrom = now()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function updated($property): void
    {
        if (in_array($property, ['dateFrom', 'dateTo', 'device', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->device = '';
        $this->search = '';
        $this->resetPage();
    }

    public function exportCsv(): StreamedResponse
    {
        $fileName = 'log-absensi-mesin-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () {
            $output = fopen('php://output', 'w');

            // BOM agar karakter Indonesia terbaca dengan benar di Excel.
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                'Waktu Scan',
                'Nama Pengguna',
                'PIN',
                'Nama Mesin',
                'Cloud ID',
                'Status Scan (Kode)',
                'Verifikasi (Kode)',
                'Diterima Server',
            ], ';');

            $this->logsQuery()
                ->orderByDesc('logs.scan_date')
                ->orderByDesc('logs.id')
                ->chunk(1000, function ($logs) use ($output) {
                    foreach ($logs as $log) {
                        fputcsv($output, [
                            $log->scan_date,
                            $log->user_name ?: 'Pengguna belum dikenal',
                            $log->pin,
                            $log->device_name ?: 'Mesin tidak dikenal',
                            $log->cloud_id,
                            $log->status_scan,
                            $log->verify,
                            $log->received_at,
                        ], ';');
                    }
                });

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
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
                'logs.id',
                'logs.cloud_id',
                'logs.pin',
                'logs.scan_date',
                'logs.verify',
                'logs.status_scan',
                'logs.photo',
                'logs.received_at',
                'devices.name as device_name',
                'machine_users.name as user_name',
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

        $filteredLogs = (clone $query)->count('logs.id');

        $logs = $query
            ->orderByDesc('logs.scan_date')
            ->orderByDesc('logs.id')
            ->paginate(25);

        return [
            'logs' => $logs,
            'devices' => DB::table('devices')->orderBy('name')->get(['cloud_id', 'name']),
            'totalLogs' => DB::table('logs')->count(),
            'filteredLogs' => $filteredLogs,
        ];
    }
}
