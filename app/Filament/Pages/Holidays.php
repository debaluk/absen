<?php

namespace App\Filament\Pages;

use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\WithPagination;
use UnitEnum;

class Holidays extends Page
{
    use WithPagination;

    protected string $view = 'filament.pages.holidays';

    protected static ?string $title = 'Hari Libur';
    protected static ?string $navigationLabel = 'Hari Libur';
    protected static string | UnitEnum | null $navigationGroup = 'Kelola';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-sun';
    protected static ?int $navigationSort = 3;
    protected ?string $subheading = 'Pengaturan hari libur nasional, perusahaan, dan cabang.';

    public ?int $holidayId = null;
    public string $date = '';
    public array $workLocationIds = [];
    public ?string $note = null;

    public string $filterMonth = '';
    public string $filterWorkLocationId = '';
    public string $viewMode = 'list';

    public function mount(): void
    {
        $this->filterMonth = now()->format('Y-m');
    }

    protected function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'workLocationIds' => ['array'],
            'workLocationIds.*' => ['integer', 'distinct', 'exists:work_locations,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function updatedFilterMonth(): void
    {
        $this->resetPage('holidayPage');
    }

    public function updatedFilterWorkLocationId(): void
    {
        $this->resetPage('holidayPage');
    }

    public function updatedViewMode(): void
    {
        $this->resetPage('holidayPage');
    }

    public function saveHoliday(): void
    {
        $validated = $this->validate();
        $locationIds = collect($validated['workLocationIds'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $duplicate = DB::table('holidays')
            ->whereDate('date', $validated['date'])
            ->where('note', $validated['note'])
            ->when($this->holidayId, fn ($query) => $query->where('id', '!=', $this->holidayId))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'date' => 'Hari libur dengan tanggal dan keterangan yang sama sudah terdaftar.',
            ]);
        }

        $data = [
            'date' => $validated['date'],
            'note' => $validated['note'],
            'updated_at' => now(),
        ];

        [$holidayId, $message] = DB::transaction(function () use ($data, $locationIds) {
            if ($this->holidayId) {
                $holidayId = $this->holidayId;
                DB::table('holidays')->where('id', $holidayId)->update($data);
                $message = 'Hari libur berhasil diperbarui.';
            } else {
                $data['created_at'] = now();
                $holidayId = DB::table('holidays')->insertGetId($data);
                $message = 'Hari libur berhasil ditambahkan.';
            }

            DB::table('holiday_work_locations')->where('holiday_id', $holidayId)->delete();

            if ($locationIds->isNotEmpty()) {
                $now = now();
                DB::table('holiday_work_locations')->insert(
                    $locationIds->map(fn ($locationId) => [
                        'holiday_id' => $holidayId,
                        'work_location_id' => $locationId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            }

            return [$holidayId, $message];
        });

        $this->resetForm();
        $this->resetPage('holidayPage');

        Notification::make()->title($message)->success()->send();
    }

    public function editHoliday(int $id): void
    {
        $holiday = DB::table('holidays')->where('id', $id)->first();
        abort_unless($holiday, 404);

        $this->holidayId = (int) $holiday->id;
        $this->date = $holiday->date;
        $this->workLocationIds = DB::table('holiday_work_locations')
            ->where('holiday_id', $id)
            ->pluck('work_location_id')
            ->map(fn ($id) => (string) $id)
            ->all();
        $this->note = $holiday->note;
    }

    public function deleteHoliday(int $id): void
    {
        $deleted = DB::table('holidays')->where('id', $id)->delete();

        if (! $deleted) {
            Notification::make()->title('Data hari libur tidak ditemukan.')->warning()->send();
            return;
        }

        if ($this->holidayId === $id) {
            $this->resetForm();
        }

        Notification::make()->title('Hari libur berhasil dihapus.')->success()->send();
    }

    public function resetForm(): void
    {
        $this->reset(['holidayId', 'date', 'workLocationIds', 'note']);
        $this->resetValidation();
    }

    public function exportExcel()
    {
        $month = Carbon::createFromFormat('Y-m', $this->filterMonth ?: now()->format('Y-m'))->startOfMonth();

        $scopeNames = DB::table('holiday_work_locations')
            ->join('work_locations', 'work_locations.id', '=', 'holiday_work_locations.work_location_id')
            ->select(
                'holiday_work_locations.holiday_id',
                DB::raw('GROUP_CONCAT(work_locations.name ORDER BY work_locations.name SEPARATOR ", ") as work_location_names')
            )
            ->groupBy('holiday_work_locations.holiday_id');

        $holidays = DB::table('holidays')
            ->leftJoinSub($scopeNames, 'holiday_scopes', fn ($join) => $join->on('holiday_scopes.holiday_id', '=', 'holidays.id'))
            ->whereBetween('holidays.date', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->when(
                filled($this->filterWorkLocationId),
                fn ($query) => $query->where(function ($query) {
                    $query->whereNotExists(function ($subquery) {
                        $subquery->selectRaw('1')
                            ->from('holiday_work_locations')
                            ->whereColumn('holiday_work_locations.holiday_id', 'holidays.id');
                    })->orWhereExists(function ($subquery) {
                        $subquery->selectRaw('1')
                            ->from('holiday_work_locations')
                            ->whereColumn('holiday_work_locations.holiday_id', 'holidays.id')
                            ->where('holiday_work_locations.work_location_id', $this->filterWorkLocationId);
                    });
                }),
            )
            ->orderBy('holidays.date')
            ->orderBy('holidays.id')
            ->get(['holidays.date', 'holidays.note', 'holiday_scopes.work_location_names']);

        $company = DB::table('companies')->orderBy('id')->first();
        $location = filled($this->filterWorkLocationId)
            ? DB::table('work_locations')->where('id', $this->filterWorkLocationId)->value('name') ?? 'Cabang tidak ditemukan'
            : 'Semua Cabang';

        $xmlEscape = fn ($value) => htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $rows = [];
        $rows[] = '<Row><Cell ss:MergeAcross="3" ss:StyleID="Company"><Data ss:Type="String">'.$xmlEscape($company->name ?? config('app.name')).'</Data></Cell></Row>';

        $companyInfo = collect([
            $company->address ?? null,
            $company->phone ?? null,
            $company->email ?? null,
            $company->website ?? null,
        ])->filter()->implode(' | ');

        if ($companyInfo !== '') {
            $rows[] = '<Row><Cell ss:MergeAcross="3" ss:StyleID="CompanyInfo"><Data ss:Type="String">'.$xmlEscape($companyInfo).'</Data></Cell></Row>';
        }

        $rows[] = '<Row ss:Height="26"><Cell ss:MergeAcross="3" ss:StyleID="ReportTitle"><Data ss:Type="String">JADWAL LIBUR</Data></Cell></Row>';
        $rows[] = '<Row><Cell ss:StyleID="MetaLabel"><Data ss:Type="String">Periode :</Data></Cell><Cell ss:MergeAcross="2" ss:StyleID="MetaValue"><Data ss:Type="String">'.$xmlEscape($month->translatedFormat('F Y')).'</Data></Cell></Row>';
        $rows[] = '<Row><Cell ss:StyleID="MetaLabel"><Data ss:Type="String">Lokasi :</Data></Cell><Cell ss:MergeAcross="2" ss:StyleID="MetaValue"><Data ss:Type="String">'.$xmlEscape($location).'</Data></Cell></Row>';
        $rows[] = '<Row ss:Height="8"></Row>';
        $rows[] = '<Row ss:Height="24"><Cell ss:StyleID="Header"><Data ss:Type="String">No.</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Tanggal</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Keterangan</Data></Cell><Cell ss:StyleID="Header"><Data ss:Type="String">Lokasi</Data></Cell></Row>';

        foreach ($holidays as $index => $holiday) {
            $holidayLocation = $holiday->work_location_names ?: 'Semua Cabang';
            $date = Carbon::parse($holiday->date)->format('d/m/Y');

            $rows[] = '<Row>'
                .'<Cell ss:StyleID="Center"><Data ss:Type="Number">'.($index + 1).'</Data></Cell>'
                .'<Cell ss:StyleID="Center"><Data ss:Type="String">'.$xmlEscape($date).'</Data></Cell>'
                .'<Cell ss:StyleID="Body"><Data ss:Type="String">'.$xmlEscape($holiday->note ?: '-').'</Data></Cell>'
                .'<Cell ss:StyleID="Body"><Data ss:Type="String">'.$xmlEscape($holidayLocation).'</Data></Cell>'
                .'</Row>';
        }

        if ($holidays->isEmpty()) {
            $rows[] = '<Row><Cell ss:MergeAcross="3" ss:StyleID="Empty"><Data ss:Type="String">Tidak ada data hari libur pada periode dan lokasi yang dipilih.</Data></Cell></Row>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<?mso-application progid="Excel.Sheet"?>'
            .'<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
            .'<Styles>'
            .'<Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="10"/></Style>'
            .'<Style ss:ID="Company"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="14" ss:Bold="1"/></Style>'
            .'<Style ss:ID="CompanyInfo"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="9"/></Style>'
            .'<Style ss:ID="ReportTitle"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="12" ss:Bold="1"/><Interior ss:Color="#D9EAF7" ss:Pattern="Solid"/></Style>'
            .'<Style ss:ID="MetaLabel"><Font ss:Bold="1"/></Style>'
            .'<Style ss:ID="MetaValue"><Alignment ss:Vertical="Center"/></Style>'
            .'<Style ss:ID="Header"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:Bold="1"/><Interior ss:Color="#D9E1F2" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>'
            .'<Style ss:ID="Center"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>'
            .'<Style ss:ID="Body"><Alignment ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>'
            .'<Style ss:ID="Empty"><Alignment ss:Horizontal="Center"/><Font ss:Italic="1" ss:Color="#666666"/></Style>'
            .'</Styles><Worksheet ss:Name="Jadwal Libur"><Table>'
            .'<Column ss:Width="45"/><Column ss:Width="95"/><Column ss:Width="230"/><Column ss:Width="210"/>'
            .implode('', $rows)
            .'</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><PageSetup><Layout x:Orientation="Portrait"/><PageMargins x:Bottom="0.5" x:Left="0.3" x:Right="0.3" x:Top="0.5"/></PageSetup><Print><FitWidth>1</FitWidth><FitHeight>0</FitHeight></Print><Selected/><Panes><Pane><Number>1</Number><ActiveRow>0</ActiveRow><ActiveCol>0</ActiveCol></Pane></Panes></WorksheetOptions></Worksheet></Workbook>';

        return response()->streamDownload(
            fn () => print($xml),
            'Jadwal-Libur-'.$month->format('Y-m').'.xls',
            ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']
        );
    }

    protected function getViewData(): array
    {
        $month = Carbon::createFromFormat('Y-m', $this->filterMonth ?: now()->format('Y-m'))->startOfMonth();
        $startDate = $month->copy()->startOfWeek(Carbon::MONDAY);
        $endDate = $startDate->copy()->addDays(41);

        $scopeNames = DB::table('holiday_work_locations')
            ->join('work_locations', 'work_locations.id', '=', 'holiday_work_locations.work_location_id')
            ->select(
                'holiday_work_locations.holiday_id',
                DB::raw('GROUP_CONCAT(work_locations.name ORDER BY work_locations.name SEPARATOR ", ") as work_location_names')
            )
            ->groupBy('holiday_work_locations.holiday_id');

        $holidays = DB::table('holidays')
            ->leftJoinSub($scopeNames, 'holiday_scopes', fn ($join) => $join->on('holiday_scopes.holiday_id', '=', 'holidays.id'))
            ->whereBetween('holidays.date', [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()])
            ->when(
                filled($this->filterWorkLocationId),
                fn ($query) => $query->where(function ($query) {
                    $query->whereNotExists(function ($subquery) {
                        $subquery->selectRaw('1')
                            ->from('holiday_work_locations')
                            ->whereColumn('holiday_work_locations.holiday_id', 'holidays.id');
                    })->orWhereExists(function ($subquery) {
                        $subquery->selectRaw('1')
                            ->from('holiday_work_locations')
                            ->whereColumn('holiday_work_locations.holiday_id', 'holidays.id')
                            ->where('holiday_work_locations.work_location_id', $this->filterWorkLocationId);
                    });
                }),
            )
            ->orderByDesc('holidays.date')
            ->orderBy('holidays.id')
            ->paginate(15, ['holidays.*', 'holiday_scopes.work_location_names'], 'holidayPage');

        $calendarHolidays = DB::table('holidays')
            ->leftJoinSub($scopeNames, 'holiday_scopes', fn ($join) => $join->on('holiday_scopes.holiday_id', '=', 'holidays.id'))
            ->whereBetween('holidays.date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when(
                filled($this->filterWorkLocationId),
                fn ($query) => $query->where(function ($query) {
                    $query->whereNotExists(function ($subquery) {
                        $subquery->selectRaw('1')
                            ->from('holiday_work_locations')
                            ->whereColumn('holiday_work_locations.holiday_id', 'holidays.id');
                    })->orWhereExists(function ($subquery) {
                        $subquery->selectRaw('1')
                            ->from('holiday_work_locations')
                            ->whereColumn('holiday_work_locations.holiday_id', 'holidays.id')
                            ->where('holiday_work_locations.work_location_id', $this->filterWorkLocationId);
                    });
                }),
            )
            ->orderBy('holidays.date')
            ->orderBy('holidays.id')
            ->get(['holidays.*', 'holiday_scopes.work_location_names'])
            ->groupBy('date');

        $calendarDays = collect();
        for ($day = $startDate->copy(); $day <= $endDate; $day->addDay()) {
            $dateKey = $day->toDateString();
            $calendarDays->push([
                'date' => $dateKey,
                'day' => $day->day,
                'inMonth' => $day->month === $month->month,
                'isToday' => $dateKey === today()->toDateString(),
                'holidays' => $calendarHolidays->get($dateKey, collect()),
            ]);
        }

        return [
            'holidays' => $holidays,
            'calendarDays' => $calendarDays,
            'calendarMonthLabel' => $month->translatedFormat('F Y'),
            'workLocations' => DB::table('work_locations')
                ->orderBy('is_active', 'desc')
                ->orderBy('name')
                ->get(['id', 'name', 'is_active']),
        ];
    }
}
