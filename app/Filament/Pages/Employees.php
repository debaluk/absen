<?php

namespace App\\Filament\\Pages;

use App\\Services\\FreedomFingerEmployeeSynchronizer;
use BackedEnum;
use Filament\\Pages\\Page;
use Illuminate\\Support\\Facades\\DB;
use UnitEnum;

class Employees extends Page
{
    protected string $view = 'filament.pages.employees';

    protected static ?string $title = 'Karyawan';

    protected static ?string $navigationLabel = 'Karyawan';

    protected static string | UnitEnum | null $navigationGroup = 'Kelola';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 1;

    protected ?string $subheading = 'Data karyawan yang tersimpan dari Freedom Finger.';

    protected function getViewData(): array
    {
        $sync = app(FreedomFingerEmployeeSynchronizer::class)->sync();

        $employees = DB::table('freedom_finger_employees')
            ->orderBy('name')
            ->orderBy('pin')
            ->get();

        return [
            'employees' => $employees,
            'totalEmployees' => $employees->count(),
            'syncedCount' => $sync['synced'],
            'syncSuccess' => $sync['success'],
            'syncMessage' => $sync['message'],
        ];
    }
}
