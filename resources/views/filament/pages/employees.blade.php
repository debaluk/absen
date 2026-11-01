<x-filament-panels::page>
    @if (! $syncSuccess)
        <div class="rounded-lg border border-warning-300 bg-warning-50 px-4 py-3 text-sm text-warning-800 dark:border-warning-700 dark:bg-warning-950 dark:text-warning-200">
            {{ $syncMessage }}
            <span class="block mt-1">Daftar tetap menggunakan data mesin yang terakhir tersimpan di Laravel.</span>
        </div>
    @else
        <div class="rounded-lg border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-800 dark:border-success-800 dark:bg-success-950 dark:text-success-200">
            Sinkronisasi mesin berhasil. {{ number_format($syncedCount, 0, ',', '.') }} data PIN diproses.
        </div>
    @endif

    @error('nik')
        <div class="rounded-lg border border-danger-300 bg-danger-50 px-4 py-3 text-sm text-danger-800 dark:border-danger-700 dark:bg-danger-950 dark:text-danger-200">
            {{ $message }}
        </div>
    @enderror

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">Master Karyawan</div>
            <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ number_format($totalEmployees, 0, ',', '.') }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">Data PIN dari Mesin</div>
            <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ number_format($totalMachineEmployees, 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <h2 class="font-semibold text-gray-950 dark:text-white">Data Karyawan dari Mesin</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Masukkan NIK untuk membuat master karyawan atau menghubungkan PIN ke karyawan yang sudah ada. Nama mesin hanya digunakan sebagai data awal.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">PIN</th>
                        <th class="px-4 py-3">Nama dari Mesin</th>
                        <th class="px-4 py-3">Cloud ID</th>
                        <th class="px-4 py-3">Master Karyawan</th>
                        <th class="px-4 py-3">NIK</th>
                        <th class="px-4 py-3">Proses</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($machineEmployees as $employee)
                        <tr class="text-gray-700 dark:text-gray-200">
                            <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $employee->pin }}</td>
                            <td class="px-4 py-3">{{ $employee->machine_name ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs">{{ $employee->cloud_id }}</td>
                            <td class="px-4 py-3">
                                @if ($employee->employee_id)
                                    <div class="font-medium text-success-700 dark:text-success-400">{{ $employee->employee_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $employee->employee_no }}</div>
                                @else
                                    <span class="text-warning-700 dark:text-warning-400">Belum terhubung</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($employee->employee_id)
                                    {{ $employee->nik }}
                                @else
                                    <input
                                        type="text"
                                        wire:model="nikInputs.{{ $employee->id }}"
                                        maxlength="32"
                                        placeholder="Masukkan NIK"
                                        class="w-40 rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                    >
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if (! $employee->employee_id)
                                    <button
                                        type="button"
                                        wire:click="linkMachineEmployee({{ $employee->id }})"
                                        wire:loading.attr="disabled"
                                        class="rounded-lg bg-primary-600 px-3 py-2 text-xs font-semibold text-white hover:bg-primary-500 disabled:opacity-50"
                                    >
                                        Simpan & Hubungkan
                                    </button>
                                @else
                                    <span class="text-xs text-success-700 dark:text-success-400">Terhubung</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Belum ada data dari mesin.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
            <h2 class="font-semibold text-gray-950 dark:text-white">Master Karyawan</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Identitas utama karyawan disimpan di aplikasi, bukan di mesin absensi.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    <tr>
                        <th class="px-4 py-3">No. Karyawan</th>
                        <th class="px-4 py-3">NIK</th>
                        <th class="px-4 py-3">Nama Karyawan</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($employees as $employee)
                        <tr class="text-gray-700 dark:text-gray-200">
                            <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $employee->employee_no }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $employee->nik ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $employee->name }}</td>
                            <td class="px-4 py-3">{{ ucfirst($employee->employment_status) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Master karyawan belum terbentuk.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
