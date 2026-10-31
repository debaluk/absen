<?php
namespace App\Filament\Pages;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;
class Devices extends Page
{
    protected static string $view = 'filament.pages.module-placeholder';
    protected static ?string $title = 'Mesin';
    protected static ?string $navigationLabel = 'Mesin';
    protected static string | UnitEnum | null $navigationGroup = 'Kelola';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?int $navigationSort = 4;
    protected ?string $subheading = 'Integrasi Freedom Engine dan pengelolaan perangkat absensi.';
}
