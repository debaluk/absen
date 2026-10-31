<?php
namespace App\Filament\Pages;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;
class AttendanceSummary extends Page
{
    protected static string $view = 'filament.pages.module-placeholder';
    protected static ?string $title = 'Rekap Absensi';
    protected static ?string $navigationLabel = 'Rekap';
    protected static string | UnitEnum | null $navigationGroup = 'Absensi';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?int $navigationSort = 3;
    protected ?string $subheading = 'Rekapitulasi kehadiran berdasarkan periode dan unit kerja.';
}
