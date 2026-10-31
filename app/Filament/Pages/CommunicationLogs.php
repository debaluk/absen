<?php
namespace App\Filament\Pages;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;
class CommunicationLogs extends Page
{
    protected string $view = 'filament.pages.module-placeholder';
    protected static ?string $title = 'Log Komunikasi';
    protected static ?string $navigationLabel = 'Log Komunikasi';
    protected static string | UnitEnum | null $navigationGroup = 'Kelola';
    protected static ?string $navigationParentItem = 'Mesin';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 6;
    protected ?string $subheading = 'Riwayat komunikasi dan respons perangkat absensi.';
}
