<x-filament-panels::page>
    <div x-data="{ syncing: false }" class="flex justify-end gap-2">
        <button
            type="button"
            x-on:click="syncing = true; $wire.syncMachineUsers().finally(() => syncing = false)"
            x-bind:disabled="syncing"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:cursor-wait disabled:opacity-60 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800"
        >
            <svg x-show="syncing" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span x-text="syncing ? 'Download nama karyawan dari mesin sedang diproses...' : 'Ambil dari Mesin'"></span>
        </button>
        <button type="button" wire:click="openGlobalActionModal" class="rounded-lg border border-primary-600 px-4 py-2 text-sm font-semibold text-primary-700 hover:bg-primary-50 dark:text-primary-300 dark:hover:bg-primary-950">
            Tindakan Global
        </button>
        <button type="button" wire:click="createEmployee" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500">
            Tambah Karyawan
        </button>
    </div>

    <div
        x-data="{ open: @entangle('isGlobalActionModalOpen') }"
        x-show="open"
        x-cloak
        x-on:keydown.escape.window="open = false; $wire.set('isGlobalActionModalOpen', false)"
        class="fixed inset-0 z-[90] flex items-center justify-center bg-gray-950/60 p-4"
        style="display: none;"
    >
        <div x-show="open" x-transition class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900" @click.outside="open = false; $wire.set('isGlobalActionModalOpen', false)">
            <div class="mb-5 flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Tindakan Global</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Atur tanggal mulai wajib absen untuk seluruh karyawan aktif yang belum memilikinya.</p>
                </div>
                <button type="button" @click="open = false; $wire.set('isGlobalActionModalOpen', false)" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800" aria-label="Tutup">✕</button>
            </div>

            <div class="mb-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                <p class="text-sm text-gray-600 dark:text-gray-300">Karyawan aktif dengan tanggal mulai absen masih kosong</p>
                <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ number_format($globalAttendanceStartDateCandidateCount, 0, ',', '.') }} karyawan</p>
            </div>

            <form wire:submit="applyGlobalAttendanceStartDate" class="space-y-4">
                <div>
                    <label for="globalAttendanceStartDate" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Tanggal Mulai Wajib Absen</label>
                    <x-filament::input.wrapper class="border" :valid="! $errors->has('globalAttendanceStartDate')">
                        <x-filament::input id="globalAttendanceStartDate" type="date" wire:model="globalAttendanceStartDate" />
                    </x-filament::input.wrapper>
                    @error('globalAttendanceStartDate') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                <p class="text-xs text-gray-500 dark:text-gray-400">Hanya karyawan berstatus Aktif dan kolom tanggal mulai absen yang masih kosong yang akan diperbarui. Tanggal yang sudah terisi tidak akan ditimpa. Jika tanggal yang dipilih mendahului tanggal mulai bekerja salah satu karyawan, seluruh proses dibatalkan.</p>

                <div class="flex justify-end gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
                    <button type="button" @click="open = false; $wire.set('isGlobalActionModalOpen', false)" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">Batal</button>
                    <button type="submit" wire:confirm="Terapkan tanggal mulai wajib absen kepada seluruh karyawan aktif yang tanggalnya masih kosong?" wire:loading.attr="disabled" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500 disabled:opacity-50">Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    <div
        x-show="syncing"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-[100] flex items-center justify-center bg-gray-950/50 p-4"
        style="display: none;"
    >
        <div class="w-full max-w-sm rounded-xl bg-white p-6 text-center shadow-xl dark:bg-gray-900">
            <svg class="mx-auto mb-4 h-10 w-10 animate-spin text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <p class="text-sm font-semibold text-gray-900 dark:text-white">Download nama karyawan dari mesin sedang diproses...</p>
            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Mohon tunggu.</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-wrap items-end gap-3 border-b border-gray-200 p-4 dark:border-gray-700">
            <div class="min-w-52 flex-1">
                <label for="filterWorkLocationId" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Lokasi Kerja</label>
                <x-filament::input.wrapper class="border" :valid="! $errors->has('filterWorkLocationId')">
                            <x-filament::input.select id="filterWorkLocationId" wire:model.live="filterWorkLocationId">

                    <option value="">Semua Lokasi</option>
                    @foreach ($workLocations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                                            </x-filament::input.select>
                        </x-filament::input.wrapper>
            </div>
            <div class="min-w-56 flex-1">
                <label for="filterName" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Cari Nama Karyawan</label>
                <x-filament::input.wrapper class="border">
                    <x-filament::input id="filterName" type="search" wire:model.live.debounce.300ms="filterName" placeholder="Masukkan nama karyawan..." />
                </x-filament::input.wrapper>
            </div>
            <div class="min-w-44 flex-1">
                <label for="filterStatus" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Status</label>
                <x-filament::input.wrapper class="border" :valid="! $errors->has('filterStatus')">
                            <x-filament::input.select id="filterStatus" wire:model.live="filterStatus">

                    <option value="">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Tidak Aktif</option>
                                            </x-filament::input.select>
                        </x-filament::input.wrapper>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">No</th>
                        <th class="px-4 py-3">Nama Karyawan</th>
                        <th class="px-4 py-3">NIK</th>
                        <th class="px-4 py-3">Jabatan</th>
                        <th class="px-4 py-3">Lokasi</th>
                        <th class="px-4 py-3">PIN</th>
                        <th class="px-4 py-3">ID Mesin</th>
                        <th class="px-4 py-3 text-center">Ikut Rekap</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="w-24 px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($employees as $employee)
                        <tr class="text-gray-700 dark:text-gray-200">
                            <td class="whitespace-nowrap px-4 py-3">{{ (($currentPage - 1) * $perPage) + $loop->iteration }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $employee->name }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $employee->nik ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $employee->position_name ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $employee->work_location_name ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $employee->machine_user_pin ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs">{{ $employee->machine_user_cloud_id ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-center">
                                <button
                                    type="button"
                                    wire:click="toggleReportInclusion({{ $employee->id }})"
                                    wire:loading.attr="disabled"
                                    aria-label="{{ $employee->include_in_reports ? 'Keluarkan dari rekap' : 'Sertakan dalam rekap' }}"
                                    title="Klik untuk mengubah status Ikut Rekap"
                                    class="rounded-full px-3 py-1 text-xs font-semibold {{ $employee->include_in_reports ? 'bg-success-50 text-success-700 dark:bg-success-950 dark:text-success-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}"
                                >
                                    {{ $employee->include_in_reports ? 'Ya' : 'Tidak' }}
                                </button>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if ($employee->employment_status === 'active')
                                    <span class="rounded-full bg-success-50 px-2 py-1 text-xs font-medium text-success-700 dark:bg-success-950 dark:text-success-300">Aktif</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">Tidak Aktif</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <div class="flex items-center justify-center gap-3">
                                    <button type="button" wire:click="editEmployee({{ $employee->id }})" title="Ubah" aria-label="Ubah" class="text-primary-600 hover:text-primary-500 dark:text-primary-400"><x-heroicon-m-pencil-square class="h-5 w-5" /></button>
                                    <button type="button" wire:click="deleteEmployee({{ $employee->id }})" wire:confirm="Yakin ingin menghapus karyawan {{ addslashes($employee->name) }}?" title="Hapus" aria-label="Hapus" class="text-danger-600 hover:text-danger-500 dark:text-danger-400"><x-heroicon-m-trash class="h-5 w-5" /></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Data karyawan tidak ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-4 py-3 dark:border-gray-700">
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Menampilkan
                <span class="font-medium">{{ $totalFilteredEmployees === 0 ? 0 : (($currentPage - 1) * $perPage) + 1 }}</span>
                sampai
                <span class="font-medium">{{ min($currentPage * $perPage, $totalFilteredEmployees) }}</span>
                dari
                <span class="font-medium">{{ $totalFilteredEmployees }}</span> karyawan
            </p>
            <div class="flex items-center gap-1">
                <button type="button" wire:click="goToEmployeePage({{ $currentPage - 1 }})" @disabled($currentPage <= 1) class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                    Sebelumnya
                </button>
                <span class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300">Halaman {{ $currentPage }} dari {{ $lastPage }}</span>
                <button type="button" wire:click="goToEmployeePage({{ $currentPage + 1 }})" @disabled($currentPage >= $lastPage) class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                    Berikutnya
                </button>
            </div>
        </div>
    </div>

    <div
        x-data="{ open: @entangle('isFormModalOpen') }"
        x-show="open"
        x-cloak
        x-on:keydown.escape.window="open = false; $wire.resetEmployeeForm()"
        class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-950/60 p-4"
        style="display: none;"
    >
        <div x-show="open" x-transition class="my-8 w-full max-w-4xl overflow-hidden rounded-xl bg-white shadow-xl dark:bg-gray-900" @click.outside="open = false; $wire.resetEmployeeForm()">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <div>
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">{{ $editingEmployeeId ? 'Ubah Karyawan' : 'Tambah Karyawan' }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Lengkapi data karyawan dan tautkan PIN mesin jika tersedia.</p>
                </div>
                <button type="button" @click="open = false; $wire.resetEmployeeForm()" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800" aria-label="Tutup">✕</button>
            </div>

            <form wire:submit="saveEmployee" class="space-y-4 p-5">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <label for="employee_no" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">No. Karyawan <span class="text-danger-600">*</span></label>
                        <x-filament::input.wrapper class="border" :valid="! $errors->has('employee_no')">
                            <x-filament::input id="employee_no" type="text" wire:model="employee_no" maxlength="30" />
                        </x-filament::input.wrapper>
                        @error('employee_no') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="nik" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">NIK</label>
                        <x-filament::input.wrapper class="border" :valid="! $errors->has('nik')">
                            <x-filament::input id="nik" type="text" wire:model="nik" maxlength="32" />
                        </x-filament::input.wrapper>
                        @error('nik') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Nama Karyawan <span class="text-danger-600">*</span></label>
                        <x-filament::input.wrapper class="border" :valid="! $errors->has('name')">
                            <x-filament::input id="name" type="text" wire:model="name" maxlength="255" />
                        </x-filament::input.wrapper>
                        @error('name') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="hire_date" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Tanggal Mulai Bekerja</label>
                        <x-filament::input.wrapper class="border" :valid="! $errors->has('hire_date')">
                            <x-filament::input id="hire_date" type="date" wire:model="hire_date" />
                        </x-filament::input.wrapper>
                        @error('hire_date') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="attendance_start_date" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Mulai Wajib Absensi</label>
                        <x-filament::input.wrapper class="border" :valid="! $errors->has('attendance_start_date')">
                            <x-filament::input id="attendance_start_date" type="date" wire:model="attendance_start_date" min="{{ $hire_date }}" />
                        </x-filament::input.wrapper>
                        @error('attendance_start_date') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="position_id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Jabatan</label>
                        <x-filament::input.wrapper class="border" :valid="! $errors->has('position_id')">
                            <x-filament::input.select id="position_id" wire:model="position_id">

                            <option value="">Pilih jabatan</option>
                            @foreach ($positions as $position)
                                <option value="{{ $position->id }}">{{ $position->name }}</option>
                            @endforeach
                                                    </x-filament::input.select>
                        </x-filament::input.wrapper>
                        @error('position_id') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="work_location_id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Lokasi Kerja</label>
                        <x-filament::input.wrapper class="border" :valid="! $errors->has('work_location_id')">
                            <x-filament::input.select id="work_location_id" wire:model="work_location_id">

                            <option value="">Pilih lokasi kerja</option>
                            @foreach ($workLocations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                                                    </x-filament::input.select>
                        </x-filament::input.wrapper>
                        @error('work_location_id') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="employment_status" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Status</label>
                        <x-filament::input.wrapper class="border" :valid="! $errors->has('employment_status')">
                            <x-filament::input.select id="employment_status" wire:model="employment_status">

                            <option value="active">Aktif</option>
                            <option value="inactive">Tidak Aktif</option>
                                                    </x-filament::input.select>
                        </x-filament::input.wrapper>
                        @error('employment_status') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2 xl:col-span-3">
                        <label for="machineUserSelection" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">PIN Mesin (opsional)</label>
                        <x-filament::input.wrapper class="border" :valid="! $errors->has('machineUserSelection')">
                            <x-filament::input.select id="machineUserSelection" wire:model="machineUserSelection">

                            <option value="">Tidak ditautkan ke PIN mesin</option>
                            @foreach ($machineUsers as $machineUser)
                                <option value="{{ $machineUser->selection }}">PIN {{ $machineUser->pin }} — {{ $machineUser->name ?: 'Tanpa nama' }} — {{ $machineUser->device_name ?: $machineUser->cloud_id }} (ID Mesin: {{ $machineUser->cloud_id }})</option>
                            @endforeach
                                                    </x-filament::input.select>
                        </x-filament::input.wrapper>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Satu PIN pada satu mesin hanya dapat ditautkan ke satu karyawan.</p>
                        @error('machineUserSelection') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
                    <button type="button" @click="open = false; $wire.resetEmployeeForm()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">Batal</button>
                    <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500 disabled:opacity-50">{{ $editingEmployeeId ? 'Simpan Perubahan' : 'Simpan Karyawan' }}</button>
                </div>
            </form>
        </div>
    </div>
</x-filament-panels::page>
