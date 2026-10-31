<?php
namespace App\Filament\Pages;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;
class DeviceRegistry extends Page
{
    protected static string $view = 'filament.pages.module-placeholder';
    protected static ?string $title = 'Perangkat';
    protected static ?string $navigationLabel = 'Perangkat';
    protected static string | UnitEnum | null $navigationGroup = 'Kelola';
    protected static ?string $navigationParentItem = 'Mesin';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-device-tablet';
    protected static ?int $navigationSort = 2;
    protected ?string $subheading = 'Daftar mesin absensi yang terhubung ke sistem.';
}
