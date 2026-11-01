<?php

namespace App\Filament\Pages;

use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;
use UnitEnum;

class LeaveCorrections extends Page
{
    use WithPagination;

    protected string $view = 'filament.pages.leave-corrections';
    protected static ?string $title = 'Izin & Koreksi';
    protected static ?string $navigationLabel = 'Izin & Koreksi';
    protected static string | UnitEnum | null $navigationGroup = 'Absensi';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?int $navigationSort = 4;
    protected ?string $subheading = 'Pengelolaan izin dan koreksi data absensi.';

    public string $activeTab = 'leaves';
    public bool $showLeaveModal = false;
    public ?string $leaveId = null;
    public string $employeeSearch = '';
    public ?int $employeeId = null;
    public ?string $employeeName = null;
    public string $kind = 'izin';
    public string $dateFrom = '';
    public ?string $dateTo = null;
    public ?string $note = null;

    public string $filterFrom = '';
    public string $filterTo = '';
    public string $filterKind = '';

    protected function rules(): array
    {
        return [
            'employeeId' => ['required', 'integer', 'exists:employees,id'],
            'kind' => ['required', Rule::in(['izin', 'sakit', 'cuti', 'dinas'])],
            'dateFrom' => ['required', 'date'],
            'dateTo' => ['nullable', 'date', 'after_or_equal:dateFrom'],
            'note' => ['nullable', 'string'],
        ];
    }

    public function updatedEmployeeSearch(): void
    {
        if ($this->employeeSearch !== $this->employeeName) {
            $this->employeeId = null;
            $this->employeeName = null;
        }
    }

    public function updatedFilterFrom(): void { $this->resetPage('leavePage'); }
    public function updatedFilterTo(): void { $this->resetPage('leavePage'); }
    public function updatedFilterKind(): void { $this->resetPage('leavePage'); }

    public function selectEmployee(int $id): void
    {
        $employee = DB::table('employees')->where('id', $id)->first(['id', 'name']);
        if (! $employee) {
            Notification::make()->title('Karyawan tidak ditemukan.')->warning()->send();
            return;
        }

        $this->employeeId = (int) $employee->id;
        $this->employeeName = $employee->name;
        $this->employeeSearch = $employee->name;
        $this->resetValidation('employeeId');
    }

    public function openCreateLeave(): void
    {
        $this->resetLeaveForm();
        $this->dateFrom = today()->toDateString();
        $this->showLeaveModal = true;
    }

    public function editLeave(string $id): void
    {
        $leave = DB::table('leaves')
            ->leftJoin('employees', 'employees.id', '=', 'leaves.employee_id')
            ->where('leaves.id', $id)
            ->first(['leaves.*', 'employees.name as employee_name']);

        abort_unless($leave, 404);

        $this->leaveId = $leave->id;
        $this->employeeId = $leave->employee_id ? (int) $leave->employee_id : null;
        $this->employeeName = $leave->employee_name;
        $this->employeeSearch = $leave->employee_name ?? '';
        $this->kind = $leave->kind;
        $this->dateFrom = $leave->from;
        $this->dateTo = $leave->to === $leave->from ? null : $leave->to;
        $this->note = $leave->note;
        $this->showLeaveModal = true;
    }

    public function saveLeave(): void
    {
        $validated = $this->validate();
        $to = $validated['dateTo'] ?: $validated['dateFrom'];

        $data = [
            'employee_id' => $validated['employeeId'],
            'pin' => null,
            'from' => $validated['dateFrom'],
            'to' => $to,
            'kind' => $validated['kind'],
            'note' => $validated['note'],
            'updated_at' => now(),
        ];

        if ($this->leaveId) {
            DB::table('leaves')->where('id', $this->leaveId)->update($data);
            $message = 'Data izin berhasil diperbarui.';
        } else {
            $data['id'] = (string) Str::uuid();
            $data['created_at'] = now();
            DB::table('leaves')->insert($data);
            $message = 'Data izin berhasil ditambahkan.';
        }

        $this->showLeaveModal = false;
        $this->resetLeaveForm();
        $this->resetPage('leavePage');
        Notification::make()->title($message)->success()->send();
    }

    public function deleteLeave(string $id): void
    {
        $leave = DB::table('leaves')->where('id', $id)->first(['id', 'to']);
        if (! $leave) {
            Notification::make()->title('Data izin tidak ditemukan.')->warning()->send();
            return;
        }

        // Penghapusan hanya diizinkan sampai akhir bulan tanggal izin terakhir.
        $deleteDeadline = Carbon::parse($leave->to)->endOfMonth()->endOfDay();
        if (now()->greaterThan($deleteDeadline)) {
            Notification::make()
                ->title('Data izin tidak dapat dihapus.')
                ->body('Batas penghapusan sudah lewat. Data masih dapat diedit.')
                ->danger()
                ->send();
            return;
        }

        DB::table('leaves')->where('id', $id)->delete();
        Notification::make()->title('Data izin berhasil dihapus.')->success()->send();
    }

    public function closeLeaveModal(): void
    {
        $this->showLeaveModal = false;
        $this->resetLeaveForm();
    }

    public function resetLeaveForm(): void
    {
        $this->reset(['leaveId', 'employeeSearch', 'employeeId', 'employeeName', 'kind', 'dateFrom', 'dateTo', 'note']);
        $this->kind = 'izin';
        $this->resetValidation();
    }

    public function exportExcel()
    {
        $leaves = $this->leaveQuery()->orderBy('leaves.from')->orderBy('employees.name')->get();
        $company = DB::table('companies')->orderBy('id')->first();
        $xmlEscape = fn ($value) => htmlspecialchars((string) ($value ?? ''), ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $period = ($this->filterFrom || $this->filterTo)
            ? ($this->filterFrom ? Carbon::parse($this->filterFrom)->format('d/m/Y') : 'Awal')
                .' s.d. '.($this->filterTo ? Carbon::parse($this->filterTo)->format('d/m/Y') : 'Sekarang')
            : 'Semua Periode';

        $rows = [];
        $rows[] = '<Row><Cell ss:MergeAcross="5" ss:StyleID="Company"><Data ss:Type="String">'.$xmlEscape($company->name ?? config('app.name')).'</Data></Cell></Row>';
        $companyInfo = collect([$company->address ?? null, $company->phone ?? null, $company->email ?? null, $company->website ?? null])->filter()->implode(' | ');
        if ($companyInfo !== '') {
            $rows[] = '<Row><Cell ss:MergeAcross="5" ss:StyleID="CompanyInfo"><Data ss:Type="String">'.$xmlEscape($companyInfo).'</Data></Cell></Row>';
        }
        $rows[] = '<Row ss:Height="26"><Cell ss:MergeAcross="5" ss:StyleID="ReportTitle"><Data ss:Type="String">DATA LAPORAN IZIN KARYAWAN</Data></Cell></Row>';
        $rows[] = '<Row><Cell ss:StyleID="MetaLabel"><Data ss:Type="String">Periode :</Data></Cell><Cell ss:MergeAcross="4" ss:StyleID="MetaValue"><Data ss:Type="String">'.$xmlEscape($period).'</Data></Cell></Row>';
        $rows[] = '<Row ss:Height="8"></Row>';
        $headers = ['No.', 'Nama Karyawan', 'Jabatan', 'Jenis', 'Tanggal', 'Alasan'];
        $headerCells = '';
        foreach ($headers as $header) {
            $headerCells .= '<Cell ss:StyleID="Header"><Data ss:Type="String">'.$xmlEscape($header).'</Data></Cell>';
        }
        $rows[] = '<Row ss:Height="24">'.$headerCells.'</Row>';

        $labels = ['izin' => 'Izin', 'sakit' => 'Sakit', 'cuti' => 'Cuti', 'dinas' => 'Dinas Luar'];
        foreach ($leaves as $index => $leave) {
            $date = Carbon::parse($leave->from)->format('d/m/Y');
            if ($leave->to !== $leave->from) {
                $date .= ' - '.Carbon::parse($leave->to)->format('d/m/Y');
            }
            $values = [
                ['Number', (string) ($index + 1)],
                ['String', $leave->employee_name ?? 'Karyawan belum ditautkan'],
                ['String', ''],
                ['String', $labels[$leave->kind] ?? ucfirst($leave->kind)],
                ['String', $date],
                ['String', $leave->note ?? ''],
            ];
            $cells = '';
            foreach ($values as [$type, $value]) {
                $cells .= '<Cell ss:StyleID="'.($type === 'Number' ? 'Center' : 'Body').'"><Data ss:Type="'.$type.'">'.$xmlEscape($value).'</Data></Cell>';
            }
            $rows[] = '<Row>'.$cells.'</Row>';
        }

        if ($leaves->isEmpty()) {
            $rows[] = '<Row><Cell ss:MergeAcross="5" ss:StyleID="Empty"><Data ss:Type="String">Tidak ada data izin pada filter yang dipilih.</Data></Cell></Row>';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<?mso-application progid="Excel.Sheet"?>'
            .'<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
            .'<Styles>'
            .'<Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="10"/></Style>'
            .'<Style ss:ID="Company"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="14" ss:Bold="1"/></Style>'
            .'<Style ss:ID="CompanyInfo"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="9"/></Style>'
            .'<Style ss:ID="ReportTitle"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Arial" ss:Size="12" ss:Bold="1"/><Interior ss:Color="#D9EAF7" ss:Pattern="Solid"/></Style>'
            .'<Style ss:ID="MetaLabel"><Font ss:Bold="1"/></Style><Style ss:ID="MetaValue"><Alignment ss:Vertical="Center"/></Style>'
            .'<Style ss:ID="Header"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:Bold="1"/><Interior ss:Color="#D9E1F2" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>'
            .'<Style ss:ID="Center"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>'
            .'<Style ss:ID="Body"><Alignment ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/></Borders></Style>'
            .'<Style ss:ID="Empty"><Alignment ss:Horizontal="Center"/><Font ss:Italic="1" ss:Color="#666666"/></Style>'
            .'</Styles><Worksheet ss:Name="Data Izin"><Table>'
            .'<Column ss:Width="40"/><Column ss:Width="180"/><Column ss:Width="130"/><Column ss:Width="100"/><Column ss:Width="140"/><Column ss:Width="240"/>'
            .implode('', $rows)
            .'</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><PageSetup><Layout x:Orientation="Landscape"/><PageMargins x:Bottom="0.5" x:Left="0.3" x:Right="0.3" x:Top="0.5"/></PageSetup><Print><FitWidth>1</FitWidth><FitHeight>0</FitHeight></Print></WorksheetOptions></Worksheet></Workbook>';

        return response()->streamDownload(fn () => print($xml), 'Data-Laporan-Izin-Karyawan.xls', [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    protected function leaveQuery()
    {
        return DB::table('leaves')
            ->leftJoin('employees', 'employees.id', '=', 'leaves.employee_id')
            ->when($this->filterFrom, fn ($query) => $query->whereDate('leaves.to', '>=', $this->filterFrom))
            ->when($this->filterTo, fn ($query) => $query->whereDate('leaves.from', '<=', $this->filterTo))
            ->when($this->filterKind, fn ($query) => $query->where('leaves.kind', $this->filterKind))
            ->select('leaves.*', 'employees.name as employee_name');
    }

    protected function getViewData(): array
    {
        $employees = collect();
        if (mb_strlen(trim($this->employeeSearch)) >= 2) {
            $employees = DB::table('employees')
                ->where('name', 'like', '%'.trim($this->employeeSearch).'%')
                ->orWhere('employee_no', 'like', '%'.trim($this->employeeSearch).'%')
                ->orderBy('name')
                ->limit(10)
                ->get(['id', 'name']);
        }

        $leaves = $this->leaveQuery()
            ->orderByDesc('leaves.from')
            ->orderByDesc('leaves.created_at')
            ->paginate(15, ['*'], 'leavePage');

        $today = today();
        foreach ($leaves as $leave) {
            $leave->aging_days = $leave->created_at
                ? Carbon::parse($leave->created_at)->startOfDay()->diffInDays($today)
                : 0;
            $leave->can_delete = $today->lessThanOrEqualTo(Carbon::parse($leave->to)->endOfMonth());
            $leave->display_date = $leave->from === $leave->to
                ? Carbon::parse($leave->from)->format('d/m/Y')
                : Carbon::parse($leave->from)->format('d/m/Y').' - '.Carbon::parse($leave->to)->format('d/m/Y');
        }

        return [
            'employees' => $employees,
            'leaves' => $leaves,
            'leaveKinds' => ['izin' => 'Izin', 'sakit' => 'Sakit', 'cuti' => 'Cuti', 'dinas' => 'Dinas Luar'],
        ];
    }
}
