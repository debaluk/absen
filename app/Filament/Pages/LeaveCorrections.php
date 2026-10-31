<?php
namespace App\Filament\Pages;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;
class LeaveCorrections extends Page
{
    protected static string $view = 'filament.pages.module-placeholder';
    protected static ?string $title = 'Izin & Koreksi';
    protected static ?string $navigationLabel = 'Izin & Koreksi';
    protected static string | UnitEnum | null $navigationGroup = 'Absensi';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?int $navigationSort = 4;
    protected ?string $subheading = 'Pengelolaan izin, ketidakhadiran, dan koreksi data absensi.';
}
