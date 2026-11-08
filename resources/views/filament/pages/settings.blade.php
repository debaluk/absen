 <x-filament-panels::page>
    <div class="space-y-5">
        <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
            <button type="button" wire:click="setTab('umum')" class="border-b-2 px-4 py-3 text-sm font-medium {{ $activeTab === 'umum' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500' }}">Umum</button>
            <button type="button" wire:click="setTab('jabatan')" class="border-b-2 px-4 py-3 text-sm font-medium {{ $activeTab === 'jabatan' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500' }}">Jabatan</button>
            <button type="button" wire:click="setTab('departemen')" class="border-b-2 px-4 py-3 text-sm font-medium {{ $activeTab === 'departemen' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500' }}">Departemen</button>
            <button type="button" wire:click="setTab('lokasi-kerja')" class="border-b-2 px-4 py-3 text-sm font-medium {{ $activeTab === 'lokasi-kerja' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500' }}">Lokasi Kerja</button>
        </div>

    @if ($activeTab === 'umum')
        <form wire:key="tab-umum" wire:submit="saveCompany" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div>
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Informasi Perusahaan</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Identitas umum perusahaan.</p>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <label class="block md:col-span-2">
                    <span class="text-sm font-medium">Nama Perusahaan *</span>
                    <input wire:model="companyName" type="text" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800" style="display:block;width:100%;min-height:42px;padding:9px 12px;color:#111827;background-color:#ffffff;border:1px solid #9ca3af;border-radius:8px;font-size:14px;line-height:1.5;box-sizing:border-box;">
                    @error('companyName') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="block md:col-span-2">
                    <span class="text-sm font-medium">Alamat</span>
                    <textarea wire:model="companyAddress" rows="3" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800" style="display:block;resize:vertical;width:100%;min-height:42px;padding:9px 12px;color:#111827;background-color:#ffffff;border:1px solid #9ca3af;border-radius:8px;font-size:14px;line-height:1.5;box-sizing:border-box;"></textarea>
                    @error('companyAddress') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-sm font-medium">Telepon</span>
                    <input wire:model="companyPhone" type="text" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800" style="display:block;width:100%;min-height:42px;padding:9px 12px;color:#111827;background-color:#ffffff;border:1px solid #9ca3af;border-radius:8px;font-size:14px;line-height:1.5;box-sizing:border-box;">
                    @error('companyPhone') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-sm font-medium">Email</span>
                    <input wire:model="companyEmail" type="email" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800" style="display:block;width:100%;min-height:42px;padding:9px 12px;color:#111827;background-color:#ffffff;border:1px solid #9ca3af;border-radius:8px;font-size:14px;line-height:1.5;box-sizing:border-box;">
                    @error('companyEmail') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="block md:col-span-2">
                    <span class="text-sm font-medium">Website</span>
                    <input wire:model="companyWebsite" type="url" placeholder="https://" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800" style="display:block;width:100%;min-height:42px;padding:9px 12px;color:#111827;background-color:#ffffff;border:1px solid #9ca3af;border-radius:8px;font-size:14px;line-height:1.5;box-sizing:border-box;">
                    @error('companyWebsite') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
            </div>
            <div class="flex justify-end">
                <x-filament::button type="submit">Simpan Informasi</x-filament::button>
            </div>
        </form>
    @elseif ($activeTab === 'jabatan')
        <div wire:key="tab-jabatan" class="grid gap-6 xl:grid-cols-3">
            <form wire:submit="savePosition" class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div>
                    <h2 class="text-lg font-semibold">{{ $positionId ? 'Ubah Jabatan' : 'Tambah Jabatan' }}</h2>
                    <p class="text-sm text-gray-500">Level ditentukan otomatis dari atasan langsung.</p>
                </div>
                <label class="block">
                    <span class="text-sm font-medium">Nama Jabatan *</span>
                    <input wire:model="positionName" type="text" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800" style="display:block;width:100%;min-height:42px;padding:9px 12px;color:#111827;background-color:#ffffff;border:1px solid #9ca3af;border-radius:8px;font-size:14px;line-height:1.5;box-sizing:border-box;">
                    @error('positionName') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-sm font-medium">Atasan Langsung</span>
                    <select wire:model="positionParentId" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800" style="display:block;width:100%;min-height:42px;padding:9px 12px;color:#111827;background-color:#ffffff;border:1px solid #9ca3af;border-radius:8px;font-size:14px;line-height:1.5;box-sizing:border-box;">
                        <option value="">— Jabatan tertinggi —</option>
                        @foreach ($positions as $position)
                            @if ($position->id !== $positionId)
                                <option value="{{ $position->id }}">Level {{ $position->level }} · {{ $position->name }}</option>
                            @endif
                        @endforeach
                    </select>
                    @error('positionParentId') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input wire:model="positionActive" type="checkbox" class="rounded border-gray-300">
                    Aktif
                </label>
                <div class="flex gap-2">
                    <x-filament::button type="submit">Simpan</x-filament::button>
                    @if ($positionId)
                        <x-filament::button color="gray" type="button" wire:click="resetPositionForm">Batal</x-filament::button>
                    @endif
                </div>
            </form>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900 xl:col-span-2">
                <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                    <h2 class="font-semibold">Struktur Jabatan</h2>
                    <p class="text-sm text-gray-500">Hierarki perusahaan berdasarkan level dan atasan langsung.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            <tr><th class="px-4 py-3">Jabatan</th><th class="px-4 py-3">Atasan</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($positions as $position)
                                <tr>
                                    <td class="px-4 py-3 font-medium">{{ str_repeat('— ', max(0, $position->level - 1)) }}{{ $position->name }}</td>
                                    <td class="px-4 py-3">{{ $positions->firstWhere('id', $position->parent_id)?->name ?? '—' }}</td>
                                    <td class="px-4 py-3">{{ $position->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                                    <td class="space-x-2 whitespace-nowrap px-4 py-3 text-right">
                                        <button type="button" wire:click="editPosition({{ $position->id }})" class="text-primary-600">Ubah</button>
                                        <button type="button" wire:click="deletePosition({{ $position->id }})" wire:confirm="Hapus jabatan ini?" class="text-danger-600">Hapus</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada data jabatan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @elseif ($activeTab === 'departemen')
        <div wire:key="tab-departemen" class="grid gap-6 xl:grid-cols-3">
            <form wire:submit="saveDepartment" class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div>
                    <h2 class="text-lg font-semibold">{{ $departmentId ? 'Ubah Departemen' : 'Tambah Departemen' }}</h2>
                    <p class="text-sm text-gray-500">Kelompokkan jabatan ke dalam satu departemen. Satu jabatan hanya dapat masuk ke satu departemen.</p>
                </div>
                <label class="block">
                    <span class="text-sm font-medium">Kode Departemen *</span>
                    <input wire:model="departmentCode" type="text" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800" style="display:block;width:100%;min-height:42px;padding:9px 12px;color:#111827;background-color:#ffffff;border:1px solid #9ca3af;border-radius:8px;font-size:14px;line-height:1.5;box-sizing:border-box;">
                    @error('departmentCode') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-sm font-medium">Nama Departemen *</span>
                    <input wire:model="departmentName" type="text" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800" style="display:block;width:100%;min-height:42px;padding:9px 12px;color:#111827;background-color:#ffffff;border:1px solid #9ca3af;border-radius:8px;font-size:14px;line-height:1.5;box-sizing:border-box;">
                    @error('departmentName') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-sm font-medium">Keterangan</span>
                    <textarea wire:model="departmentDescription" rows="2" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800" style="display:block;resize:vertical;width:100%;min-height:42px;padding:9px 12px;color:#111827;background-color:#ffffff;border:1px solid #9ca3af;border-radius:8px;font-size:14px;line-height:1.5;box-sizing:border-box;"></textarea>
                    @error('departmentDescription') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
                <div>
                    <span class="text-sm font-medium">Mapping Jabatan</span>
                    <div class="mt-2 max-h-72 space-y-2 overflow-y-auto rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        @forelse ($positions as $position)
                            <label class="flex items-start gap-2 text-sm">
                                <input wire:model="departmentPositionIds" type="checkbox" value="{{ $position->id }}" class="mt-1 rounded border-gray-300">
                                <span>{{ str_repeat('— ', max(0, $position->level - 1)) }}{{ $position->name }} @if (! $position->is_active)<span class="text-gray-500">(Nonaktif)</span>@endif</span>
                            </label>
                        @empty
                            <p class="text-sm text-gray-500">Belum ada jabatan. Tambahkan jabatan terlebih dahulu.</p>
                        @endforelse
                    </div>
                    @error('departmentPositionIds') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                    @error('departmentPositionIds.*') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input wire:model="departmentActive" type="checkbox" class="rounded border-gray-300">
                    Aktif
                </label>
                <div class="flex gap-2">
                    <x-filament::button type="submit">Simpan</x-filament::button>
                    @if ($departmentId)
                        <x-filament::button color="gray" type="button" wire:click="resetDepartmentForm">Batal</x-filament::button>
                    @endif
                </div>
            </form>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900 xl:col-span-2">
                <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                    <h2 class="font-semibold">Daftar Departemen</h2>
                    <p class="text-sm text-gray-500">Setiap jabatan hanya dapat dipetakan ke satu departemen.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            <tr><th class="px-4 py-3">Kode</th><th class="px-4 py-3">Departemen</th><th class="px-4 py-3">Jabatan</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($departments as $department)
                                <tr>
                                    <td class="px-4 py-3">{{ $department->code }}</td>
                                    <td class="px-4 py-3 font-medium">{{ $department->name }}</td>
                                    <td class="px-4 py-3">{{ $departmentMappings->get($department->id, '—') }}</td>
                                    <td class="px-4 py-3">{{ $department->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                                    <td class="space-x-2 whitespace-nowrap px-4 py-3 text-right">
                                        <button type="button" wire:click="editDepartment({{ $department->id }})" class="text-primary-600">Ubah</button>
                                        <button type="button" wire:click="deleteDepartment({{ $department->id }})" wire:confirm="Hapus departemen ini? Jabatan tidak akan ikut terhapus." class="text-danger-600">Hapus</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada data departemen.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @elseif ($activeTab === 'lokasi-kerja')
        <div wire:key="tab-lokasi-kerja" class="grid gap-6 xl:grid-cols-3">
            <form wire:submit="saveLocation" class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div>
                    <h2 class="text-lg font-semibold">{{ $locationId ? 'Ubah Lokasi Kerja' : 'Tambah Lokasi Kerja' }}</h2>
                    <p class="text-sm text-gray-500">Lokasi kerja sekaligus menjadi cabang perusahaan.</p>
                </div>
                <label class="block">
                    <span class="text-sm font-medium">Kode Lokasi *</span>
                    <input wire:model="locationCode" type="text" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800" style="display:block;width:100%;min-height:42px;padding:9px 12px;color:#111827;background-color:#ffffff;border:1px solid #9ca3af;border-radius:8px;font-size:14px;line-height:1.5;box-sizing:border-box;">
                    @error('locationCode') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-sm font-medium">Nama Lokasi *</span>
                    <input wire:model="locationName" type="text" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800" style="display:block;width:100%;min-height:42px;padding:9px 12px;color:#111827;background-color:#ffffff;border:1px solid #9ca3af;border-radius:8px;font-size:14px;line-height:1.5;box-sizing:border-box;">
                    @error('locationName') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="block">
                    <span class="text-sm font-medium">Alamat</span>
                    <textarea wire:model="locationAddress" rows="3" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800" style="display:block;resize:vertical;width:100%;min-height:42px;padding:9px 12px;color:#111827;background-color:#ffffff;border:1px solid #9ca3af;border-radius:8px;font-size:14px;line-height:1.5;box-sizing:border-box;"></textarea>
                    @error('locationAddress') <span class="text-sm text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input wire:model="locationActive" type="checkbox" class="rounded border-gray-300">
                    Aktif
                </label>
                <div class="flex gap-2">
                    <x-filament::button type="submit">Simpan</x-filament::button>
                    @if ($locationId)
                        <x-filament::button color="gray" type="button" wire:click="resetLocationForm">Batal</x-filament::button>
                    @endif
                </div>
            </form>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900 xl:col-span-2">
                <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                    <h2 class="font-semibold">Daftar Lokasi Kerja</h2>
                    <p class="text-sm text-gray-500">Daftar cabang/lokasi kerja perusahaan.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            <tr><th class="px-4 py-3">Kode</th><th class="px-4 py-3">Nama Lokasi</th><th class="px-4 py-3">Alamat</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($locations as $location)
                                <tr>
                                    <td class="px-4 py-3">{{ $location->code }}</td>
                                    <td class="px-4 py-3 font-medium">{{ $location->name }}</td>
                                    <td class="px-4 py-3">{{ $location->address ?: '—' }}</td>
                                    <td class="px-4 py-3">{{ $location->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                                    <td class="space-x-2 whitespace-nowrap px-4 py-3 text-right">
                                        <button type="button" wire:click="editLocation({{ $location->id }})" class="text-primary-600">Ubah</button>
                                        <button type="button" wire:click="deleteLocation({{ $location->id }})" wire:confirm="Hapus lokasi ini?" class="text-danger-600">Hapus</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada lokasi kerja.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
