<?php
namespace App\Filament\Pages;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;
class DeviceSynchronization extends Page
{
    protected string $view = 'filament.pages.module-placeholder';
    protected static ?string $title = 'Sinkronisasi';
    protected static ?string $navigationLabel = 'Sinkronisasi';
    protected static string | UnitEnum | null $navigationGroup = 'Kelola';
    protected static ?string $navigationParentItem = 'Mesin';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?int $navigationSort = 5;
    protected ?string $subheading = 'Pengiriman dan penarikan data antara aplikasi dan perangkat.';
}
