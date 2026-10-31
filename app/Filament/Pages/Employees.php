<?php
namespace App\Filament\Pages;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;
class Employees extends Page
{
    protected string $view = 'filament.pages.module-placeholder';
    protected static ?string $title = 'Karyawan';
    protected static ?string $navigationLabel = 'Karyawan';
    protected static string | UnitEnum | null $navigationGroup = 'Kelola';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static ?int $navigationSort = 1;
    protected ?string $subheading = 'Data induk dan informasi karyawan.';
}
