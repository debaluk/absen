<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use UnitEnum;

class Settings extends Page
{
    public static function canAccess(): bool
    {
        return (int) \Illuminate\Support\Facades\Auth::user()?->level === 0;
    }

    protected string $view = 'filament.pages.settings';

    protected static ?string $title = 'Pengaturan';

    protected static ?string $navigationLabel = 'Organisasi';

    protected static string | UnitEnum | null $navigationGroup = 'Pengaturan';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 2;

    public string $activeTab = 'umum';

    public string $companyName = '';
    public string $companyAddress = '';
    public string $companyPhone = '';
    public string $companyEmail = '';
    public string $companyWebsite = '';

    public ?int $positionId = null;
    public string $positionName = '';
    public ?int $positionParentId = null;
    public bool $positionActive = true;

    public ?int $locationId = null;
    public string $locationCode = '';
    public string $locationName = '';
    public string $locationAddress = '';
    public bool $locationActive = true;

    public function mount(): void
    {
        $company = DB::table('companies')->first();

        if ($company) {
            $this->companyName = $company->name;
            $this->companyAddress = $company->address ?? '';
            $this->companyPhone = $company->phone ?? '';
            $this->companyEmail = $company->email ?? '';
            $this->companyWebsite = $company->website ?? '';
        }
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, ['umum', 'jabatan', 'lokasi-kerja'], true)) {
            $this->activeTab = $tab;
            $this->resetValidation();
        }
    }

    public function saveCompany(): void
    {
        $data = $this->validate([
            'companyName' => ['required', 'string', 'max:255'],
            'companyAddress' => ['nullable', 'string'],
            'companyPhone' => ['nullable', 'string', 'max:50'],
            'companyEmail' => ['nullable', 'email', 'max:255'],
            'companyWebsite' => ['nullable', 'url', 'max:255'],
        ]);

        if (DB::table('companies')->where('id', 1)->exists()) {
            DB::table('companies')->where('id', 1)->update([
                'name' => $data['companyName'],
                'address' => $data['companyAddress'] ?: null,
                'phone' => $data['companyPhone'] ?: null,
                'email' => $data['companyEmail'] ?: null,
                'website' => $data['companyWebsite'] ?: null,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('companies')->insert([
                'id' => 1,
                'code' => 'MAIN',
                'name' => $data['companyName'],
                'address' => $data['companyAddress'] ?: null,
                'phone' => $data['companyPhone'] ?: null,
                'email' => $data['companyEmail'] ?: null,
                'website' => $data['companyWebsite'] ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Notification::make()->title('Informasi perusahaan berhasil disimpan.')->success()->send();
    }

    public function editPosition(int $id): void
    {
        $position = DB::table('positions')->find($id);

        if (! $position) {
            return;
        }

        $this->positionId = $position->id;
        $this->positionName = $position->name;
        $this->positionParentId = $position->parent_id;
        $this->positionActive = (bool) $position->is_active;
    }

    public function savePosition(): void
    {
        $this->validate([
            'positionName' => ['required', 'string', 'max:255'],
            'positionParentId' => ['nullable', 'integer', Rule::exists('positions', 'id')],
            'positionActive' => ['boolean'],
        ]);

        if ($this->positionId && $this->positionParentId === $this->positionId) {
            $this->addError('positionParentId', 'Jabatan tidak boleh menjadi atasannya sendiri.');
            return;
        }

        if ($this->positionId && $this->positionParentId && $this->isPositionDescendant($this->positionParentId, $this->positionId)) {
            $this->addError('positionParentId', 'Atasan tidak boleh dipilih dari bawahan jabatan ini.');
            return;
        }

        $level = 1;
        if ($this->positionParentId) {
            $parent = DB::table('positions')->find($this->positionParentId);
            $level = $parent->level + 1;
        }

        $data = [
            'name' => trim($this->positionName),
            'parent_id' => $this->positionParentId,
            'level' => $level,
            'is_active' => $this->positionActive,
            'updated_at' => now(),
        ];

        if ($this->positionId) {
            DB::table('positions')->where('id', $this->positionId)->update($data);
            $this->refreshPositionLevels($this->positionId);
        } else {
            do {
                $code = 'POS-' . strtoupper(Str::random(8));
            } while (DB::table('positions')->where('code', $code)->exists());

            $data['code'] = $code;
            $data['created_at'] = now();
            DB::table('positions')->insert($data);
        }

        $this->resetPositionForm();
        Notification::make()->title('Jabatan berhasil disimpan.')->success()->send();
    }

    private function isPositionDescendant(int $candidateId, int $ancestorId): bool
    {
        $current = DB::table('positions')->find($candidateId);

        while ($current) {
            if ((int) $current->id === $ancestorId) {
                return true;
            }
            $current = $current->parent_id ? DB::table('positions')->find($current->parent_id) : null;
        }

        return false;
    }

    private function refreshPositionLevels(int $id): void
    {
        $position = DB::table('positions')->find($id);
        if (! $position) {
            return;
        }

        $level = 1;
        if ($position->parent_id) {
            $parent = DB::table('positions')->find($position->parent_id);
            $level = $parent ? $parent->level + 1 : 1;
        }

        DB::table('positions')->where('id', $id)->update(['level' => $level, 'updated_at' => now()]);

        foreach (DB::table('positions')->where('parent_id', $id)->pluck('id') as $childId) {
            $this->refreshPositionLevels((int) $childId);
        }
    }

    public function deletePosition(int $id): void
    {
        if (DB::table('positions')->where('parent_id', $id)->exists()) {
            Notification::make()->title('Jabatan masih memiliki bawahan dan tidak dapat dihapus.')->danger()->send();
            return;
        }

        DB::table('positions')->where('id', $id)->delete();
        Notification::make()->title('Jabatan berhasil dihapus.')->success()->send();
    }

    public function resetPositionForm(): void
    {
        $this->reset(['positionId', 'positionName', 'positionParentId']);
        $this->positionActive = true;
        $this->resetValidation();
    }

    public function editLocation(int $id): void
    {
        $location = DB::table('work_locations')->find($id);

        if (! $location) {
            return;
        }

        $this->locationId = $location->id;
        $this->locationCode = $location->code;
        $this->locationName = $location->name;
        $this->locationAddress = $location->address ?? '';
        $this->locationActive = (bool) $location->is_active;
    }

    public function saveLocation(): void
    {
        $companyId = DB::table('companies')->value('id');

        if (! $companyId) {
            Notification::make()
                ->title('Simpan informasi perusahaan terlebih dahulu.')
                ->warning()
                ->send();
            return;
        }

        $this->validate([
            'locationCode' => ['required', 'string', 'max:30', Rule::unique('work_locations', 'code')->ignore($this->locationId)],
            'locationName' => ['required', 'string', 'max:255'],
            'locationAddress' => ['nullable', 'string'],
            'locationActive' => ['boolean'],
        ]);

        $data = [
            'company_id' => $companyId,
            'code' => strtoupper(trim($this->locationCode)),
            'name' => trim($this->locationName),
            'address' => $this->locationAddress ?: null,
            'is_active' => $this->locationActive,
            'updated_at' => now(),
        ];

        if ($this->locationId) {
            DB::table('work_locations')->where('id', $this->locationId)->update($data);
        } else {
            $data['created_at'] = now();
            DB::table('work_locations')->insert($data);
        }

        $this->resetLocationForm();
        Notification::make()->title('Lokasi kerja berhasil disimpan.')->success()->send();
    }

    public function deleteLocation(int $id): void
    {
        DB::table('work_locations')->where('id', $id)->delete();
        Notification::make()->title('Lokasi kerja berhasil dihapus.')->success()->send();
    }

    public function resetLocationForm(): void
    {
        $this->reset(['locationId', 'locationCode', 'locationName', 'locationAddress']);
        $this->locationActive = true;
        $this->resetValidation();
    }

    protected function getViewData(): array
    {
        return [
            'positions' => DB::table('positions')->orderBy('level')->orderBy('name')->get(),
            'locations' => DB::table('work_locations')->orderBy('name')->get(),
        ];
    }
}
