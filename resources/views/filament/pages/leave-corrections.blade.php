<x-filament-panels::page>
    <div class="space-y-5">
        <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
            <button type="button" wire:click="$set('activeTab', 'leaves')" class="border-b-2 px-4 py-3 text-sm font-medium {{ $activeTab === 'leaves' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500' }}">Data Izin</button>
            <button type="button" wire:click="$set('activeTab', 'corrections')" class="border-b-2 px-4 py-3 text-sm font-medium {{ $activeTab === 'corrections' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500' }}">Data Koreksi Absen</button>
        </div>
        @if ($activeTab === 'leaves')
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex flex-col gap-3 border-b border-gray-200 p-4 dark:border-gray-700 lg:flex-row lg:items-end lg:justify-between">
                   
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Dari Tanggal<input type="date" wire:model.live="filterFrom" class="mt-1 h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm font-normal text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"></label>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Sampai Tanggal<input type="date" wire:model.live="filterTo" class="mt-1 h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm font-normal text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"></label>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Nama Karyawan<input type="search" wire:model.live.debounce.300ms="filterName" placeholder="Cari nama karyawan" class="mt-1 h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm font-normal text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"></label>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Jenis Izin<select wire:model.live="filterKind" class="mt-1 h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm font-normal text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"><option value="">Semua Jenis</option>@foreach ($leaveKinds as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                    </div>
                </div>
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                    <span class="text-xs text-gray-500">{{ number_format($totalFilteredLeaves, 0, ',', '.') }} data</span>
                    <div class="flex gap-2"><x-filament::button size="sm" color="gray" wire:click="exportExcel">Export Excel</x-filament::button><x-filament::button size="sm" wire:click="openCreateLeave">Tambah Izin</x-filament::button></div>
                </div>
                <div class="overflow-x-auto"><table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-600 dark:bg-gray-800"><tr><th class="px-4 py-3">Karyawan</th><th class="px-4 py-3">Jenis</th><th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">Keterangan / Alasan</th><th class="px-4 py-3 text-center">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($leaves as $leave)
                        <tr class="text-gray-700 dark:text-gray-200">
                            <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $leave->employee_name ?: 'Karyawan belum ditautkan' }}</td>
                            <td class="px-4 py-3">{{ $leaveKinds[$leave->kind] ?? ucfirst($leave->kind) }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $leave->display_date }}</td>
                            <td class="px-4 py-3">{{ $leave->note ?: '—' }}</td>
                            
                            <td class="px-4 py-3"><div class="flex justify-center gap-3"><button type="button" wire:click="editLeave('{{ $leave->id }}')" title="Ubah"><x-heroicon-m-pencil-square class="h-5 w-5 text-primary-600" /></button>
                            @if ($leave->can_delete)<button type="button" wire:click="deleteLeave('{{ $leave->id }}')" wire:confirm="Hapus data izin ini?" title="Hapus"><x-heroicon-m-trash class="h-5 w-5 text-danger-600" /></button>@else<span title="Batas penghapusan sudah lewat"><x-heroicon-m-lock-closed class="h-5 w-5 text-gray-400" /></span>@endif</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Belum ada data izin pada filter yang dipilih.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-4 py-3 dark:border-gray-800">
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Menampilkan
                        <span class="font-medium">{{ $totalFilteredLeaves === 0 ? 0 : (($currentPage - 1) * $perPage) + 1 }}</span>
                        sampai
                        <span class="font-medium">{{ min($currentPage * $perPage, $totalFilteredLeaves) }}</span>
                        dari
                        <span class="font-medium">{{ $totalFilteredLeaves }}</span> data
                    </p>
                    <div class="flex items-center gap-1">
                        <button type="button" wire:click="goToLeavePage({{ $currentPage - 1 }})" @disabled($currentPage <= 1) class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                            Sebelumnya
                        </button>
                        <span class="px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300">Halaman {{ $currentPage }} dari {{ $lastPage }}</span>
                        <button type="button" wire:click="goToLeavePage({{ $currentPage + 1 }})" @disabled($currentPage >= $lastPage) class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                            Berikutnya
                        </button>
                    </div>
                </div>
            </section>
        @else
            <section class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center dark:bg-gray-900"><h2 class="text-sm font-semibold">Data Koreksi Absen</h2><p class="mt-1 text-sm text-gray-500">Modul ini belum diaktifkan.</p></section>
        @endif
        @if ($showLeaveModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-950/50 p-4">
                <div class="w-full max-w-2xl rounded-xl bg-white p-5 shadow-xl dark:bg-gray-900">
                    <div class="mb-5 flex items-center justify-between"><h2 class="text-base font-semibold">{{ $leaveId ? 'Ubah Data Izin' : 'Tambah Data Izin' }}</h2><button type="button" wire:click="closeLeaveModal">✕</button></div>
                    <form wire:submit="saveLeave" class="space-y-4">
                        <div class="relative">
                            <label class="mb-1 block text-sm font-semibold text-gray-800 dark:text-gray-100">Karyawan</label>
                            <p class="mb-2 text-xs text-gray-500">Cari berdasarkan nama, lalu pilih karyawan dari daftar.</p>
                            <input type="text" wire:model.live.debounce.300ms="employeeSearch" autocomplete="off" placeholder="Contoh: Budi Santoso" class="mt-1 h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                            @error('employeeId')<span class="mt-1 block text-xs text-danger-600">Karyawan wajib dipilih dari daftar.</span>@enderror
                            @if (count($employees) && $employeeSearch !== $employeeName)
                                <div class="absolute z-20 mt-1 max-h-52 w-full overflow-y-auto rounded-lg border bg-white shadow-lg dark:bg-gray-800">
                                    @foreach ($employees as $employee)
                                        <button type="button" wire:click="selectEmployee({{ $employee->id }})" class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700">{{ $employee->name }}</button>
                                    @endforeach
                                </div>
                            @endif
                            @if ($employeeId && $employeeName)<p class="mt-2 text-xs font-medium text-success-600">Karyawan dipilih: {{ $employeeName }}</p>@endif
                        </div>
                        <fieldset>
                            <legend class="mb-1 text-sm font-semibold text-gray-800 dark:text-gray-100">Jenis Izin</legend>
                            <p class="mb-2 text-xs text-gray-500">Pilih satu jenis izin.</p>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                @foreach (['izin' => 'Izin', 'sakit' => 'Sakit', 'cuti' => 'Cuti', 'dinas' => 'Dinas Luar'] as $value => $label)
                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-3 text-sm {{ $kind === $value ? 'border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-950 dark:text-primary-200' : 'border-gray-300 text-gray-700 dark:border-gray-600 dark:text-gray-200' }}">
                                        <input type="checkbox" wire:click="$set('kind', '{{ $value }}')" @checked($kind === $value) class="rounded border-gray-400 text-primary-600 focus:ring-primary-500">
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('kind')<span class="mt-1 block text-xs text-danger-600">{{ $message }}</span>@enderror
                        </fieldset>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Tanggal Mulai <span class="text-danger-600">*</span><input type="date" wire:model="dateFrom" class="mt-1 h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">@error('dateFrom')<span class="text-xs text-danger-600">{{ $message }}</span>@enderror</label>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Tanggal Selesai<input type="date" wire:model="dateTo" class="mt-1 h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"><span class="mt-1 block text-xs font-normal text-gray-500">Kosongkan jika izin hanya satu hari.</span>@error('dateTo')<span class="text-xs text-danger-600">{{ $message }}</span>@enderror</label>
                        </div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Keterangan / Alasan <span class="font-normal text-gray-500">(opsional)</span><textarea rows="3" wire:model="note" class="mt-1 min-h-24 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea></label>
                        <p class="rounded-lg bg-primary-50 px-3 py-2 text-xs text-primary-800 dark:bg-primary-950 dark:text-primary-200">Catatan: pada tanggal izin ini, karyawan tidak dihitung ALPA meskipun terdapat data scan absensi.</p>
                        <div class="flex justify-end gap-2"><x-filament::button type="button" color="gray" wire:click="closeLeaveModal">Batal</x-filament::button><x-filament::button type="submit">{{ $leaveId ? 'Simpan Perubahan' : 'Simpan Izin' }}</x-filament::button></div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
