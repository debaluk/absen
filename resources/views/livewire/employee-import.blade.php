<div>
    <button
        type="button"
        wire:click="openModal"
        class="inline-flex items-center gap-2 rounded-lg border border-primary-600 px-4 py-2 text-sm font-semibold text-primary-700 hover:bg-primary-50 dark:text-primary-300 dark:hover:bg-primary-950"
    >
        <x-heroicon-o-arrow-up-tray class="h-5 w-5" />
        Import
    </button>

    <div
        x-data="{ open: @entangle('isOpen') }"
        x-show="open"
        x-cloak
        x-on:keydown.escape.window="open = false; $wire.closeModal()"
        class="fixed inset-0 z-[110] flex items-center justify-center overflow-y-auto bg-gray-950/60 p-4"
        style="display: none;"
    >
        <div
            x-show="open"
            x-transition
            class="my-8 w-full max-w-xl overflow-hidden rounded-xl bg-white shadow-xl dark:bg-gray-900"
            @click.outside="open = false; $wire.closeModal()"
        >
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <div>
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Import Karyawan</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Import satu karyawan dari file Excel.</p>
                </div>
                <button
                    type="button"
                    wire:click="closeModal"
                    class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800"
                    aria-label="Tutup"
                >✕</button>
            </div>

            <div class="space-y-4 p-5">
                <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                    <p class="font-semibold">Informasi Import</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-xs text-gray-600 dark:text-gray-300">
                        <li>Satu file hanya untuk satu karyawan, bukan import massal.</li>
                        <li>Kode karyawan dibuat otomatis mengikuti urutan kode yang sudah ada.</li>
                        <li>Import dibatalkan jika NIK dan nama sudah terdaftar.</li>
                        <li>PIN dan ID Mesin harus cocok dengan data mesin. PIN yang sudah digunakan pada lokasi mesin yang sama akan ditolak.</li>
                        <li>Lokasi Kerja dipilih di bawah ini dan tidak harus sama dengan lokasi mesin.</li>
                        <li>Jabatan dicocokkan berdasarkan nama jabatan yang sudah terdaftar. Tanggal gunakan format YYYY-MM-DD.</li>
                    </ul>
                    <button
                        type="button"
                        wire:click="downloadTemplate"
                        wire:loading.attr="disabled"
                        wire:target="downloadTemplate"
                        class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-primary-700 hover:underline disabled:opacity-50 dark:text-primary-300"
                    >
                        <x-heroicon-o-document-arrow-down class="h-4 w-4" />
                        Download Template Excel
                    </button>
                </div>

                <div>
                    <label for="importWorkLocationId" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
                        Lokasi Kerja <span class="text-danger-600">*</span>
                    </label>
                    <x-filament::input.wrapper class="border" :valid="! $errors->has('workLocationId')">
                        <x-filament::input.select id="importWorkLocationId" wire:model="workLocationId">
                            <option value="">Pilih lokasi kerja</option>
                            @foreach ($workLocations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                    @error('workLocationId') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="employeeImportFile" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
                        File Excel <span class="text-danger-600">*</span>
                    </label>
                    <input
                        id="employeeImportFile"
                        type="file"
                        wire:model="file"
                        accept=".xls"
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm file:font-semibold dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:file:bg-gray-700"
                    />
                    @error('file') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    <div wire:loading wire:target="file" class="mt-2 text-xs text-gray-500 dark:text-gray-400">Mengunggah file...</div>
                </div>

                <div wire:loading wire:target="importEmployee" class="space-y-2" role="status" aria-live="polite">
                    <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                        <div class="h-2 w-2/3 animate-pulse rounded-full bg-primary-600"></div>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Memeriksa data, validasi PIN mesin, dan menyimpan karyawan...</p>
                </div>

                @if ($progress === 100 && $resultStatus)
                    <div class="space-y-2">
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-2 w-full rounded-full {{ $resultStatus === 'success' ? 'bg-success-600' : 'bg-danger-600' }}"></div>
                        </div>
                        <div class="rounded-lg p-3 {{ $resultStatus === 'success' ? 'bg-success-50 text-success-800 dark:bg-success-950 dark:text-success-200' : 'bg-danger-50 text-danger-800 dark:bg-danger-950 dark:text-danger-200' }}">
                            <p class="text-sm font-semibold">{{ $resultStatus === 'success' ? 'Import Berhasil' : 'Import Dibatalkan' }}</p>
                            <p class="mt-1 text-sm">{{ $resultMessage }}</p>
                        </div>
                    </div>
                @endif

                <div class="flex justify-end gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
                    <button
                        type="button"
                        wire:click="closeModal"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
                    >{{ $resultStatus ? 'Tutup' : 'Batal Import' }}</button>
                    @if (! $resultStatus)
                        <button
                            type="button"
                            wire:click="importEmployee"
                            wire:loading.attr="disabled"
                            wire:target="importEmployee,file"
                            class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500 disabled:cursor-wait disabled:opacity-50"
                        >
                            Mulai Import
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
