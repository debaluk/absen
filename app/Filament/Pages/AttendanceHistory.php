<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class AttendanceHistory extends Page
{
    protected static string $view = 'filament.pages.module-placeholder';
    protected static ?string $title = 'Riwayat Absensi';
    protected static ?string $navigationLabel = 'Riwayat';
    protected static string | UnitEnum | null $navigationGroup = 'Absensi';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clock';
    protected static ?int $navigationSort = 2;
    protected ?string $subheading = 'Riwayat kehadiran karyawan.';
}
