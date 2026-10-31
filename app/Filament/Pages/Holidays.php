<?php
namespace App\Filament\Pages;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;
class Holidays extends Page
{
    protected string $view = 'filament.pages.module-placeholder';
    protected static ?string $title = 'Hari Libur';
    protected static ?string $navigationLabel = 'Hari Libur';
    protected static string | UnitEnum | null $navigationGroup = 'Kelola';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-sun';
    protected static ?int $navigationSort = 3;
    protected ?string $subheading = 'Pengaturan hari libur nasional, perusahaan, dan cabang.';
}
