<?php
namespace App\Filament\Pages;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;
class WorkSchedules extends Page
{
    protected string $view = 'filament.pages.work-schedules';
    protected static ?string $title = 'Jam Kerja';
    protected static ?string $navigationLabel = 'Jam Kerja';
    protected static string | UnitEnum | null $navigationGroup = 'Kelola';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-calendar';
    protected static ?int $navigationSort = 2;
    public string $activeTab = 'master-shift';
    public function setTab(string $tab): void
    {
        if (in_array($tab, ['master-shift', 'penugasan-jadwal'], true)) {
            $this->activeTab = $tab;
        }
    }
}
