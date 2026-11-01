<x-filament-panels::page>
    <div class="space-y-5">
        <section class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <div class="mb-4">
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white">
                    {{ $holidayId ? 'Ubah Hari Libur' : 'Tambah Hari Libur' }}
                </h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Pilih semua cabang untuk hari libur bersama, atau tentukan satu cabang.
                </p>
            </div>

            <form wire:submit="saveHoliday" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="block text-sm">
                    <span class="mb-1 block font-medium text-gray-700 dark:text-gray-300">Tanggal</span>
                    <input type="date" wire:model="date"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                    @error('date') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block font-medium text-gray-700 dark:text-gray-300">Berlaku untuk</span>
                    <select wire:model="workLocationId"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="">Semua Cabang</option>
                        @foreach ($workLocations as $location)
                            <option value="{{ $location->id }}">
                                {{ $location->name }}{{ $location->is_active ? '' : ' (Nonaktif)' }}
                            </option>
                        @endforeach
                    </select>
                    @error('workLocationId') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                </label>

                <label class="block text-sm md:col-span-2">
                    <span class="mb-1 block font-medium text-gray-700 dark:text-gray-300">Keterangan</span>
                    <input type="text" wire:model="note" maxlength="255" placeholder="Contoh: Hari Raya Idul Fitri"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                    @error('note') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                </label>

                <div class="flex items-end gap-2 md:col-span-2 xl:col-span-4">
                    <x-filament::button type="submit" size="sm">
                        {{ $holidayId ? 'Simpan Perubahan' : 'Tambah Hari Libur' }}
                    </x-filament::button>
                    @if ($holidayId)
                        <x-filament::button type="button" color="gray" size="sm" wire:click="resetForm">
                            Batal
                        </x-filament::button>
                    @endif
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                <div>
                    <h2 class="text-sm font-semibold text-gray-950 dark:text-white">Daftar Hari Libur</h2>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Hari libur semua cabang berlaku untuk seluruh lokasi kerja.
                    </p>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($holidays->count(), 0, ',', '.') }} data</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Cakupan</th>
                            <th class="px-4 py-3">Keterangan</th>
                            <th class="w-24 px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($holidays as $holiday)
                            <tr class="text-gray-700 dark:text-gray-200">
                                <td class="whitespace-nowrap px-4 py-3 font-medium">
                                    {{ \Illuminate\Support\Carbon::parse($holiday->date)->translatedFormat('d M Y') }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if ($holiday->work_location_id === null)
                                        <span class="inline-flex rounded-full bg-primary-50 px-2.5 py-1 text-xs font-medium text-primary-700 dark:bg-primary-950 dark:text-primary-300">Semua Cabang</span>
                                    @else
                                        {{ $holiday->work_location_name ?: 'Lokasi tidak ditemukan' }}
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $holiday->note ?: '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <div class="flex items-center justify-center gap-3">
                                        <button type="button" wire:click="editHoliday({{ $holiday->id }})"
                                            title="Ubah" aria-label="Ubah"
                                            class="text-primary-600 hover:text-primary-500 dark:text-primary-400">
                                            <x-heroicon-m-pencil-square class="h-5 w-5" />
                                        </button>
                                        <button type="button" wire:click="deleteHoliday({{ $holiday->id }})"
                                            wire:confirm="Hapus hari libur ini?"
                                            title="Hapus" aria-label="Hapus"
                                            class="text-danger-600 hover:text-danger-500 dark:text-danger-400">
                                            <x-heroicon-m-trash class="h-5 w-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    Belum ada data hari libur.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
