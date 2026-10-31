<?php
namespace App\Filament\Pages;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use UnitEnum;
class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Hari Ini';
    protected static string | UnitEnum | null $navigationGroup = 'Absensi';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?int $navigationSort = 1;
}
