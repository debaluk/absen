<x-filament-panels::page>
    @if (! $syncSuccess)
        <div class="rounded-lg border border-warning-300 bg-warning-50 px-4 py-3 text-sm text-warning-800 dark:border-warning-700 dark:bg-warning-950 dark:text-warning-200">
            {{ $syncMessage }}
            <span class="block mt-1">Data PIN tetap menggunakan data terakhir yang tersimpan di Laravel.</span>
        </div>
    @else
        <div class="rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-800 dark:border-success-800 dark:bg-success-950 dark:text-success-200">
            Sinkronisasi mesin berhasil. {{ number_format($syncedCount, 0, ',', '.') }} data PIN diproses.
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Karyawan</div>
            <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ number_format($totalEmployees, 0, ',', '.') }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">Karyawan Aktif</div>
            <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ number_format($totalActiveEmployees, 0, ',', '.') }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">PIN Tersedia dari Mesin</div>
            <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ number_format($totalMachineUsers, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <h2 class="font-semibold text-gray-950 dark:text-white">
                {{ $editingEmployeeId ? 'Ubah Karyawan' : 'Tambah Karyawan' }}
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pilih satu identitas PIN mesin jika karyawan menggunakan absensi sidik jari.</p>
        </div>

        <form wire:submit="saveEmployee" class="space-y-4 p-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label for="employee_no" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">No. Karyawan <span class="text-danger-600">*</span></label>
                    <input id="employee_no" type="text" wire:model="employee_no" maxlength="30" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                    @error('employee_no') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="nik" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">NIK</label>
                    <input id="nik" type="text" wire:model="nik" maxlength="32" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                    @error('nik') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Nama Karyawan <span class="text-danger-600">*</span></label>
                    <input id="name" type="text" wire:model="name" maxlength="255" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                    @error('name') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="position_id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Jabatan</label>
                    <select id="position_id" wire:model="position_id" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="">Pilih jabatan</option>
                        @foreach ($positions as $position)
                            <option value="{{ $position->id }}">{{ $position->name }}</option>
                        @endforeach
                    </select>
                    @error('position_id') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="work_location_id" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Lokasi Kerja Karyawan</label>
                    <select id="work_location_id" wire:model="work_location_id" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="">Pilih lokasi kerja</option>
                        @foreach ($workLocations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </select>
                    @error('work_location_id') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="employment_status" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Status</label>
                    <select id="employment_status" wire:model="employment_status" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="active">Aktif</option>
                        <option value="inactive">Tidak Aktif</option>
                    </select>
                    @error('employment_status') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2 xl:col-span-3">
                    <label for="machineUserSelection" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">PIN Mesin (opsional, pilih satu)</label>
                    <select id="machineUserSelection" wire:model="machineUserSelection" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="">Tidak ditautkan ke PIN mesin</option>
                        @foreach ($machineUsers as $machineUser)
                            <option value="{{ $machineUser->selection }}">
                                PIN {{ $machineUser->pin }} — {{ $machineUser->name ?: 'Tanpa nama' }} — {{ $machineUser->device_name ?: $machineUser->cloud_id }} (Cloud ID: {{ $machineUser->cloud_id }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">PIN dan Cloud ID disimpan sebagai satu referensi ke machine_users. Satu PIN pada satu mesin hanya dapat dipilih oleh satu karyawan.</p>
                    @error('machineUserSelection') <p class="mt-1 text-xs text-danger-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
                <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500 disabled:opacity-50">
                    {{ $editingEmployeeId ? 'Simpan Perubahan' : 'Simpan Karyawan' }}
                </button>
                @if ($editingEmployeeId)
                    <button type="button" wire:click="resetEmployeeForm" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                        Batal Ubah
                    </button>
                @endif
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <h2 class="font-semibold text-gray-950 dark:text-white">Daftar Master Karyawan</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Lokasi kerja adalah penempatan karyawan, bukan lokasi fisik mesin absensi.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">No. Karyawan</th>
                        <th class="px-4 py-3">NIK</th>
                        <th class="px-4 py-3">Nama Karyawan</th>
                        <th class="px-4 py-3">Jabatan</th>
                        <th class="px-4 py-3">Lokasi Kerja</th>
                        <th class="px-4 py-3">PIN</th>
                        <th class="px-4 py-3">Cloud ID</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($employees as $employee)
                        <tr class="text-gray-700 dark:text-gray-200">
                            <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $employee->employee_no }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $employee->nik ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $employee->name }}</td>
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
                                    <button type="button" wire:click="editEmployee({{ $employee->id }})" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                                        Ubah
                                    </button>
                                    <button type="button" wire:click="deleteEmployee({{ $employee->id }})" wire:confirm="Yakin ingin menghapus karyawan {{ addslashes($employee->name) }}?" class="rounded-lg border border-danger-300 px-3 py-1.5 text-xs font-medium text-danger-700 hover:bg-danger-50 dark:border-danger-800 dark:text-danger-300 dark:hover:bg-danger-950">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Master karyawan belum terbentuk.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
