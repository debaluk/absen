<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class AttendanceReport extends Page
{
    protected string $view = 'filament.pages.attendance-report';
    protected static ?string $title = 'Rekap Absensi';
    protected static ?string $navigationLabel = 'Rekap';
    protected static string | UnitEnum | null $navigationGroup = 'Absensi';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?int $navigationSort = 3;

    public string $filterMonth = '';
    public string $filterWorkLocationId = '';
    public string $filterDepartmentId = '';
    public string $filterName = '';

    public function mount(): void
    {
        $this->filterMonth = now()->format('Y-m');
    }

    public function exportExcel(): StreamedResponse
    {
        $data = $this->getViewData();
        $company = $data['company'];
        $headers = [
            'No.',
            'Karyawan Aktif',
            'Lokasi Kerja',
            'Jml. Hari',
            'Hadir',
            'Izin',
            'Sakit',
            'Cuti',
            'Dinas',
            'Alpa',
            'Terlambat (Menit)',
            'Pulang Cepat (Menit)',
            'Kehadiran',
        ];

        $escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $mergedRow = static fn (string $value, string $style = 'meta'): string =>
            '<Row><Cell ss:StyleID="' . $style . '" ss:MergeAcross="12"><Data ss:Type="String">' . $escape($value) . '</Data></Cell></Row>';

        $companyDetails = array_filter([
            $company->address ?? null,
            $company->phone ?? null,
            $company->email ?? null,
            $company->website ?? null,
        ], fn ($value) => filled($value));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            . '<?mso-application progid="Excel.Sheet"?>'
            . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">'
            . '<Styles>'
            . '<Style ss:ID="company"><Font ss:Bold="1" ss:Size="13"/></Style>'
            . '<Style ss:ID="title"><Font ss:Bold="1" ss:Size="12"/></Style>'
            . '<Style ss:ID="meta"><Font ss:Size="10"/></Style>'
            . '<Style ss:ID="header"><Font ss:Bold="1"/><Interior ss:Color="#D9EAF7" ss:Pattern="Solid"/></Style>'
            . '<Style ss:ID="number"><NumberFormat ss:Format="0"/></Style>'
            . '</Styles><Worksheet ss:Name="Rekap Kehadiran"><Table>'
            . $mergedRow($company->name ?? config('app.name'), 'company');

        if ($companyDetails !== []) {
            $xml .= $mergedRow(implode(' | ', $companyDetails));
        }

        $xml .= $mergedRow('Rekap Kehadiran Karyawan', 'title')
            . $mergedRow('Bulan : ' . $data['monthLabel'])
            . $mergedRow('Departemen : ' . ($data['selectedDepartmentName'] ?: 'Semua Departemen'))
            . $mergedRow('Lokasi Kerja : ' . ($data['selectedWorkLocationName'] ?: 'Semua Lokasi'))
            . '<Row/>';

        $xml .= '<Row>' . implode('', array_map(
            fn (string $header): string => '<Cell ss:StyleID="header"><Data ss:Type="String">' . $escape($header) . '</Data></Cell>',
            $headers,
        )) . '</Row>';

        foreach ($data['rows'] as $index => $row) {
            $employee = $row['employee'];
            $counts = $row['counts'];
            $values = [
                $index + 1,
                $employee->name . ' (' . $employee->employee_no . ')',
                $employee->work_location_name ?? 'Belum diatur',
                $row['effective_days'],
                $counts['hadir'],
                $counts['izin'],
                $counts['sakit'],
                $counts['cuti'],
                $counts['dinas'],
                $counts['alpa'],
                $counts['late_minutes'],
                $counts['early_leave_minutes'],
                $counts['hadir'] . '/' . $row['effective_days'],
            ];

            $xml .= '<Row>' . implode('', array_map(
                fn ($value): string => '<Cell' . (is_int($value) ? ' ss:StyleID="number"' : '') . '><Data ss:Type="' . (is_int($value) ? 'Number' : 'String') . '">' . $escape($value) . '</Data></Cell>',
                $values,
            )) . '</Row>';
        }

        $xml .= '</Table></Worksheet></Workbook>';

        return response()->streamDownload(
            static function () use ($xml): void {
                echo $xml;
            },
            'rekap-kehadiran-' . str_replace('-', '', $this->filterMonth) . '.xls',
            ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8'],
        );
    }

    protected function getViewData(): array
    {
        $month = preg_match('/^\d{4}-\d{2}$/', $this->filterMonth)
            ? $this->filterMonth
            : now()->format('Y-m');
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));
        $today = today()->toDateString();
        $periodEnd = min($end, $today);

        $employees = DB::table('employees')
            ->leftJoin('work_locations', 'work_locations.id', '=', 'employees.work_location_id')
            ->leftJoin('department_positions', 'department_positions.position_id', '=', 'employees.position_id')
            ->leftJoin('departments', 'departments.id', '=', 'department_positions.department_id')
            ->where('employees.employment_status', 'active')
            ->where('employees.include_in_reports', true)
            ->when($this->filterWorkLocationId !== '', fn ($query) => $query->where('employees.work_location_id', $this->filterWorkLocationId))
            ->when($this->filterDepartmentId !== '', fn ($query) => $query->where('departments.id', $this->filterDepartmentId))
            ->when(trim($this->filterName) !== '', fn ($query) => $query->where('employees.name', 'like', '%' . trim($this->filterName) . '%'))
            ->orderBy('work_locations.name')
            ->orderBy('employees.name')
            ->get([
                'employees.id',
                'employees.employee_no',
                'employees.name',
                'employees.attendance_start_date',
                'employees.work_location_id',
                'work_locations.name as work_location_name',
            ]);

        $employeeIds = $employees->pluck('id');
        $scanMap = [];
        if ($employeeIds->isNotEmpty() && $periodEnd >= $start) {
            $scans = DB::table('logs')
                ->join('employees', function ($join) {
                    $join->on('employees.machine_user_cloud_id', '=', 'logs.cloud_id')
                        ->on('employees.machine_user_pin', '=', 'logs.pin');
                })
                ->whereIn('employees.id', $employeeIds)
                ->whereBetween('logs.scan_date', [$start . ' 00:00:00', $periodEnd . ' 23:59:59'])
                ->orderBy('logs.scan_date')
                ->get(['employees.id as employee_id', 'logs.scan_date']);

            foreach ($scans as $scan) {
                $date = substr($scan->scan_date, 0, 10);
                $scanMap[$scan->employee_id][$date][] = substr($scan->scan_date, 11, 5);
            }
        }

        $leaveMap = [];
        if ($employeeIds->isNotEmpty() && $periodEnd >= $start) {
            $leaves = DB::table('leaves')
                ->whereIn('employee_id', $employeeIds)
                ->whereDate('from', '<=', $periodEnd)
                ->whereDate('to', '>=', $start)
                ->get(['employee_id', 'from', 'to', 'kind']);

            foreach ($leaves as $leave) {
                $from = max($start, $leave->from);
                $to = min($periodEnd, $leave->to);
                for ($date = $from; $date <= $to; $date = date('Y-m-d', strtotime($date . ' +1 day'))) {
                    $leaveMap[$leave->employee_id][$date] = strtolower($leave->kind);
                }
            }
        }

        $correctionMap = collect();
        if ($employeeIds->isNotEmpty() && $periodEnd >= $start) {
            $correctionMap = DB::table('attendance_corrections')
                ->whereIn('employee_id', $employeeIds)
                ->whereBetween('date', [$start, $periodEnd])
                ->get(['employee_id', 'date', 'check_in', 'check_out'])
                ->keyBy(fn ($row) => $row->employee_id . '|' . $row->date);
        }

        $settings = DB::table('work_schedule_settings')->where('id', 1)->first();
        $scheduleDays = $settings ? json_decode($settings->days, true) : [];
        $lateTolerance = (int) ($settings->tolerance ?? 15);
        $minGap = (int) ($settings->min_gap ?? 60);

        $holidays = DB::table('holidays')->whereBetween('date', [$start, $end])->get(['id', 'date']);
        $holidayLocations = DB::table('holiday_work_locations')
            ->get(['holiday_id', 'work_location_id'])
            ->groupBy('holiday_id');
        $holidayMap = [];
        foreach ($holidays as $holiday) {
            $holidayMap[$holiday->date] = $holidayLocations->get($holiday->id, collect())
                ->pluck('work_location_id')->map(fn ($id) => (int) $id)->all();
        }

        $rows = [];

        foreach ($employees as $employee) {
            $counts = [
                'hadir' => 0,
                'izin' => 0,
                'sakit' => 0,
                'cuti' => 0,
                'dinas' => 0,
                'alpa' => 0,
                'late_minutes' => 0,
                'early_leave_minutes' => 0,
            ];
            $firstAttendanceDate = $employee->attendance_start_date;
            $effectiveDays = 0;

            // Jumlah hari efektif mengikuti kalender kerja bulanan, bukan tanggal mulai absensi karyawan.
            for ($effectiveDate = $start; $effectiveDate <= $end; $effectiveDate = date('Y-m-d', strtotime($effectiveDate . ' +1 day'))) {
                $effectiveWeekday = (int) date('w', strtotime($effectiveDate));
                $effectiveSchedule = $scheduleDays[$effectiveWeekday] ?? null;
                $effectiveScheduled = is_array($effectiveSchedule) && ! empty($effectiveSchedule) && empty($effectiveSchedule['free']);
                $effectiveHolidayScopes = $holidayMap[$effectiveDate] ?? null;
                $effectiveHoliday = $effectiveHolidayScopes !== null && (
                    count($effectiveHolidayScopes) === 0 ||
                    in_array((int) $employee->work_location_id, $effectiveHolidayScopes, true)
                );

                if ($effectiveScheduled && ! $effectiveHoliday) {
                    $effectiveDays++;
                }
            }

            for ($date = $start; $date <= $periodEnd; $date = date('Y-m-d', strtotime($date . ' +1 day'))) {
                $weekday = (int) date('w', strtotime($date));
                $schedule = $scheduleDays[$weekday] ?? null;
                $scheduled = is_array($schedule) && ! empty($schedule) && empty($schedule['free']);
                $holidayScopes = $holidayMap[$date] ?? null;
                $holiday = $holidayScopes !== null && (
                    count($holidayScopes) === 0 ||
                    in_array((int) $employee->work_location_id, $holidayScopes, true)
                );

                $times = $scanMap[$employee->id][$date] ?? [];
                $checkIn = $times[0] ?? null;
                $checkOut = count($times) > 1 ? $times[count($times) - 1] : null;
                if ($checkIn && $checkOut
                    && (strtotime($date . ' ' . $checkOut) - strtotime($date . ' ' . $checkIn)) < $minGap * 60) {
                    $checkOut = null;
                }

                $correction = $correctionMap->get($employee->id . '|' . $date);
                if ($correction?->check_in) {
                    $checkIn = substr($correction->check_in, 0, 5);
                }
                if ($correction?->check_out) {
                    $checkOut = substr($correction->check_out, 0, 5);
                }

                $status = $leaveMap[$employee->id][$date] ?? (($checkIn || $checkOut) ? 'hadir' : null);
                if ($status === null && $scheduled && ! $holiday
                    && $firstAttendanceDate && $firstAttendanceDate <= $date) {
                    $status = 'alpa';
                }

                if ($status !== null && array_key_exists($status, $counts)) {
                    $counts[$status]++;
                }

                // Keterlambatan dihitung setelah toleransi; pulang cepat dihitung dari jam akhir jadwal.
                if ($scheduled && $checkIn && ! empty($schedule['start'])) {
                    $lateMinutes = (int) floor((strtotime($date . ' ' . $checkIn) - strtotime($date . ' ' . $schedule['start'])) / 60);
                    $counts['late_minutes'] += max(0, $lateMinutes - $lateTolerance);
                }
                if ($scheduled && $checkOut && ! empty($schedule['end'])) {
                    $earlyMinutes = (int) floor((strtotime($date . ' ' . $schedule['end']) - strtotime($date . ' ' . $checkOut)) / 60);
                    $counts['early_leave_minutes'] += max(0, $earlyMinutes);
                }
            }

            $rows[] = [
                'employee' => $employee,
                'counts' => $counts,
                'effective_days' => $effectiveDays,
            ];
        }

        return [
            'rows' => $rows,
            'monthLabel' => \Illuminate\Support\Carbon::createFromFormat('Y-m', $month)->translatedFormat('F Y'),
            'workLocations' => DB::table('work_locations')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'departments' => DB::table('departments')->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'selectedDepartmentName' => $this->filterDepartmentId !== '' ? DB::table('departments')->where('id', $this->filterDepartmentId)->value('name') : null,
            'selectedWorkLocationName' => $this->filterWorkLocationId !== '' ? DB::table('work_locations')->where('id', $this->filterWorkLocationId)->value('name') : null,
            'company' => DB::table('companies')->first(),
            'periodEnd' => $periodEnd,
        ];
    }
}
