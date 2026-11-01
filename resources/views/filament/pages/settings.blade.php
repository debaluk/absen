<x-filament-panels::page>
    <x-filament::tabs label="Pengaturan organisasi">
        <x-filament::tabs.item :active="$activeTab === 'umum'" wire:click="setTab('umum')">Umum</x-filament::tabs.item>
        <x-filament::tabs.item :active="$activeTab === 'jabatan'" wire:click="setTab('jabatan')">Jabatan</x-filament::tabs.item>
        <x-filament::tabs.item :active="$activeTab === 'lokasi-kerja'" wire:click="setTab('lokasi-kerja')">Lokasi Kerja</x-filament::tabs.item>
    </x-filament::tabs>

    @if ($activeTab === 'umum')
        <form wire:submit="saveCompany" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
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
        <div class="grid gap-6 xl:grid-cols-3">
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
                            <tr><th class="px-4 py-3">Level</th><th class="px-4 py-3">Jabatan</th><th class="px-4 py-3">Atasan</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($positions as $position)
                                <tr>
                                    <td class="px-4 py-3">L{{ $position->level }}</td>
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
    @else
        <div class="grid gap-6 xl:grid-cols-3">
            <form wire:submit="saveLocation" class="space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div>
                    <h2 class="text-lg font-semibold">{{ $locationId ? 'Ubah Lokasi Kerja' : 'Tambah Lokasi Kerja' }}</h2>
                    <p class="text-sm text-gray-500">Kelola cabang atau tempat kerja perusahaan.</p>
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
                    <p class="text-sm text-gray-500">Daftar cabang dan lokasi penempatan karyawan.</p>
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
