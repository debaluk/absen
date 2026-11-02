<x-filament-panels::page>
    <div class="fs-employees-page">
    <div class="flex justify-end">
        <button type="button" wire:click="createEmployee" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500">
            Tambah Karyawan
        </button>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <div class="flex flex-wrap items-end gap-3 border-b border-gray-200 p-4 dark:border-gray-700">
            <div class="min-w-52 flex-1">
                <label for="filterWorkLocationId" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Lokasi Kerja</label>
                <select id="filterWorkLocationId" wire:model.live="filterWorkLocationId" class="fs-form-control">
                    <option value="">Semua Lokasi</option>
                    @foreach ($workLocations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-44 flex-1">
                <label for="filterStatus" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Status</label>
                <select id="filterStatus" wire:model.live="filterStatus" class="fs-form-control">
                    <option value="">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="inactive">Tidak Aktif</option>
                </select>
            </div>
            <button type="button" wire:click="clearEmployeeFilters" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                Reset Filter
            </button>
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
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
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
                            <td class="whitespace-nowrap px-4 py-3">
                                @if ($employee->employment_status === 'active')
                                    <span class="rounded-full bg-success-50 px-2 py-1 text-xs font-medium text-success-700 dark:bg-success-950 dark:text-success-300">Aktif</span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">Tidak Aktif</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button type="button" wire:click="editEmployee({{ $employee->id }})" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">Ubah</button>
                                    <button type="button" wire:click="deleteEmployee({{ $employee->id }})" wire:confirm="Yakin ingin menghapus karyawan {{ addslashes($employee->name) }}?" class="rounded-lg border border-danger-300 px-3 py-1.5 text-xs font-medium text-danger-700 hover:bg-danger-50 dark:border-danger-800 dark:text-danger-300 dark:hover:bg-danger-950">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Data karyawan tidak ditemukan.</td>
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
                        <input id="employee_no" type="text" wire:model="employee_no" maxlength="30" class="fs-form-control">
                        @error('employee_no') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="nik" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">NIK</label>
                        <input id="nik" type="text" wire:model="nik" maxlength="32" class="fs-form-control">
                        @error('nik') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="name" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Nama Karyawan <span class="text-danger-600">*</span></label>
                        <input id="name" type="text" wire:model="name" maxlength="255" class="fs-form-control">
                        @error('name') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="hire_date" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Tanggal Mulai Bekerja</label>
                        <input id="hire_date" type="date" wire:model="hire_date" class="fs-form-control">
                        @error('hire_date') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="attendance_start_date" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Mulai Wajib Absensi</label>
                        <input id="attendance_start_date" type="date" wire:model="attendance_start_date" min="{{ $hire_date }}" class="fs-form-control">
                        @error('attendance_start_date') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="position_id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Jabatan</label>
                        <select id="position_id" wire:model="position_id" class="fs-form-control">
                            <option value="">Pilih jabatan</option>
                            @foreach ($positions as $position)
                                <option value="{{ $position->id }}">{{ $position->name }}</option>
                            @endforeach
                        </select>
                        @error('position_id') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="work_location_id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Lokasi Kerja</label>
                        <select id="work_location_id" wire:model="work_location_id" class="fs-form-control">
                            <option value="">Pilih lokasi kerja</option>
                            @foreach ($workLocations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                        @error('work_location_id') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="employment_status" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Status</label>
                        <select id="employment_status" wire:model="employment_status" class="fs-form-control">
                            <option value="active">Aktif</option>
                            <option value="inactive">Tidak Aktif</option>
                        </select>
                        @error('employment_status') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2 xl:col-span-3">
                        <label for="machineUserSelection" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">PIN Mesin (opsional)</label>
                        <select id="machineUserSelection" wire:model="machineUserSelection" class="fs-form-control">
                            <option value="">Tidak ditautkan ke PIN mesin</option>
                            @foreach ($machineUsers as $machineUser)
                                <option value="{{ $machineUser->selection }}">PIN {{ $machineUser->pin }} — {{ $machineUser->name ?: 'Tanpa nama' }} — {{ $machineUser->device_name ?: $machineUser->cloud_id }} (ID Mesin: {{ $machineUser->cloud_id }})</option>
                            @endforeach
                        </select>
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
    </div>
</x-filament-panels::page>
