<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use UnitEnum;

class Settings extends Page
{
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
        $company = DB::table('company_profiles')->first();

        if ($company) {
            $this->companyName = $company->company_name;
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

        DB::table('company_profiles')->updateOrInsert(
            ['id' => 1],
            [
                'company_name' => $data['companyName'],
                'address' => $data['companyAddress'] ?: null,
                'phone' => $data['companyPhone'] ?: null,
                'email' => $data['companyEmail'] ?: null,
                'website' => $data['companyWebsite'] ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->dispatch('notify', type: 'success', message: 'Informasi perusahaan berhasil disimpan.');
    }

    public function editPosition(int $id): void
    {
        $position = DB::table('job_positions')->find($id);

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
            'positionParentId' => ['nullable', 'integer', Rule::exists('job_positions', 'id')],
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
            $parent = DB::table('job_positions')->find($this->positionParentId);
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
            DB::table('job_positions')->where('id', $this->positionId)->update($data);
            $this->refreshPositionLevels($this->positionId);
        } else {
            $data['created_at'] = now();
            DB::table('job_positions')->insert($data);
        }

        $this->resetPositionForm();
        $this->dispatch('notify', type: 'success', message: 'Jabatan berhasil disimpan.');
    }

    private function isPositionDescendant(int $candidateId, int $ancestorId): bool
    {
        $current = DB::table('job_positions')->find($candidateId);

        while ($current) {
            if ((int) $current->id === $ancestorId) {
                return true;
            }
            $current = $current->parent_id ? DB::table('job_positions')->find($current->parent_id) : null;
        }

        return false;
    }

    private function refreshPositionLevels(int $id): void
    {
        $position = DB::table('job_positions')->find($id);
        if (! $position) {
            return;
        }

        $level = 1;
        if ($position->parent_id) {
            $parent = DB::table('job_positions')->find($position->parent_id);
            $level = $parent ? $parent->level + 1 : 1;
        }

        DB::table('job_positions')->where('id', $id)->update(['level' => $level, 'updated_at' => now()]);

        foreach (DB::table('job_positions')->where('parent_id', $id)->pluck('id') as $childId) {
            $this->refreshPositionLevels((int) $childId);
        }
    }

    public function deletePosition(int $id): void
    {
        if (DB::table('job_positions')->where('parent_id', $id)->exists()) {
            $this->dispatch('notify', type: 'danger', message: 'Jabatan masih memiliki bawahan dan tidak dapat dihapus.');
            return;
        }

        DB::table('job_positions')->where('id', $id)->delete();
        $this->dispatch('notify', type: 'success', message: 'Jabatan berhasil dihapus.');
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
        $this->validate([
            'locationCode' => ['required', 'string', 'max:30', Rule::unique('work_locations', 'code')->ignore($this->locationId)],
            'locationName' => ['required', 'string', 'max:255'],
            'locationAddress' => ['nullable', 'string'],
            'locationActive' => ['boolean'],
        ]);

        $data = [
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
        $this->dispatch('notify', type: 'success', message: 'Lokasi kerja berhasil disimpan.');
    }

    public function deleteLocation(int $id): void
    {
        DB::table('work_locations')->where('id', $id)->delete();
        $this->dispatch('notify', type: 'success', message: 'Lokasi kerja berhasil dihapus.');
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
            'positions' => DB::table('job_positions')->orderBy('level')->orderBy('name')->get(),
            'locations' => DB::table('work_locations')->orderBy('name')->get(),
        ];
    }
}
