<?php
namespace App\Filament\Pages;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;
class EmployeeDeviceRegistration extends Page
{
    protected string $view = 'filament.pages.module-placeholder';
    protected static ?string $title = 'Registrasi Karyawan';
    protected static ?string $navigationLabel = 'Registrasi Karyawan';
    protected static string | UnitEnum | null $navigationGroup = 'Kelola';
    protected static ?string $navigationParentItem = 'Mesin';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-identification';
    protected static ?int $navigationSort = 3;
    protected ?string $subheading = 'Registrasi identitas dan sidik jari karyawan ke perangkat.';
}
