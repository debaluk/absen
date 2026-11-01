<?php
namespace App\Filament\Pages;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;
class FreedomEngine extends Page
{
    protected static bool $shouldRegisterNavigation = false;
    protected string $view = 'filament.pages.module-placeholder';
    protected static ?string $title = 'Freedom Engine';
    protected static ?string $navigationLabel = 'Freedom Engine';
    protected static string | UnitEnum | null $navigationGroup = 'Kelola';
    protected static ?string $navigationParentItem = 'Mesin';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-server';
    protected static ?int $navigationSort = 1;
    protected ?string $subheading = 'Konfigurasi koneksi dan layanan Freedom Engine.';
}
