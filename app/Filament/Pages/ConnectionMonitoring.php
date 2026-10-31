<?php
namespace App\Filament\Pages;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;
class ConnectionMonitoring extends Page
{
    protected string $view = 'filament.pages.module-placeholder';
    protected static ?string $title = 'Monitoring Koneksi';
    protected static ?string $navigationLabel = 'Monitoring Koneksi';
    protected static string | UnitEnum | null $navigationGroup = 'Kelola';
    protected static ?string $navigationParentItem = 'Mesin';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-signal';
    protected static ?int $navigationSort = 4;
    protected ?string $subheading = 'Pemantauan status koneksi mesin absensi.';
}
