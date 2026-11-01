<?php
namespace App\Filament\Pages;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;
class Settings extends Page
{
    protected string $view = 'filament.pages.settings';
    protected static ?string $title = 'Pengaturan';
    protected static ?string $navigationLabel = 'Organisasi';
    protected static string | UnitEnum | null $navigationGroup = 'Pengaturan';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?int $navigationSort = 2;
    public string $activeTab = 'umum';
    public function setTab(string $tab): void
    {
        if (in_array($tab, ['umum', 'organisasi', 'pengguna', 'role', 'permission'], true)) {
            $this->activeTab = $tab;
        }
    }
}
