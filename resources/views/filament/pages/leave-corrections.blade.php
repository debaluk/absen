<x-filament-panels::page>
    <div class="space-y-5">
        <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
            <button type="button" wire:click="$set('activeTab', 'leaves')" class="border-b-2 px-4 py-3 text-sm font-medium {{ $activeTab === 'leaves' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500' }}">Data Izin</button>
            <button type="button" wire:click="$set('activeTab', 'corrections')" class="border-b-2 px-4 py-3 text-sm font-medium {{ $activeTab === 'corrections' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500' }}">Data Koreksi Absen</button>
        </div>
        @if ($activeTab === 'leaves')
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex flex-col gap-3 border-b border-gray-200 p-4 dark:border-gray-700 lg:flex-row lg:items-end lg:justify-between">
                    <div><h2 class="text-sm font-semibold text-gray-950 dark:text-white">Daftar Data Izin</h2><p class="mt-1 text-xs text-gray-500">Hari kerja dengan catatan ini tidak dihitung alpa, walau ada scan.</p></div>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                        <label class="text-xs">Dari tanggal<input type="date" wire:model.live="filterFrom" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:bg-gray-800"></label>
                        <label class="text-xs">Sampai tanggal<input type="date" wire:model.live="filterTo" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:bg-gray-800"></label>
                        <label class="col-span-2 text-xs sm:col-span-1">Jenis<select wire:model.live="filterKind" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:bg-gray-800"><option value="">Semua Jenis</option>@foreach ($leaveKinds as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                    </div>
                </div>
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                    <span class="text-xs text-gray-500">{{ number_format($leaves->total(), 0, ',', '.') }} data</span>
                    <div class="flex gap-2"><x-filament::button size="sm" color="gray" wire:click="exportExcel">Export Excel</x-filament::button><x-filament::button size="sm" wire:click="openCreateLeave">Tambah Izin</x-filament::button></div>
                </div>
                <div class="overflow-x-auto"><table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase text-gray-600 dark:bg-gray-800"><tr><th class="px-4 py-3">Karyawan</th><th class="px-4 py-3">Jenis</th><th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">Keterangan / Alasan</th><th class="px-4 py-3">Aging</th><th class="px-4 py-3 text-center">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($leaves as $leave)
                        <tr class="text-gray-700 dark:text-gray-200">
                            <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $leave->employee_name ?: 'Karyawan belum ditautkan' }}</td>
                            <td class="px-4 py-3">{{ $leaveKinds[$leave->kind] ?? ucfirst($leave->kind) }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $leave->display_date }}</td>
                            <td class="px-4 py-3">{{ $leave->note ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $leave->aging_days }} hari</td>
                            <td class="px-4 py-3"><div class="flex justify-center gap-3"><button type="button" wire:click="editLeave('{{ $leave->id }}')" title="Ubah"><x-heroicon-m-pencil-square class="h-5 w-5 text-primary-600" /></button>
                            @if ($leave->can_delete)<button type="button" wire:click="deleteLeave('{{ $leave->id }}')" wire:confirm="Hapus data izin ini?" title="Hapus"><x-heroicon-m-trash class="h-5 w-5 text-danger-600" /></button>@else<span title="Batas penghapusan sudah lewat"><x-heroicon-m-lock-closed class="h-5 w-5 text-gray-400" /></span>@endif</div></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Belum ada data izin pada filter yang dipilih.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
                <div class="border-t border-gray-100 px-4 py-3 dark:border-gray-800">{{ $leaves->links() }}</div>
            </section>
        @else
            <section class="rounded-xl border border-dashed border-gray-300 bg-white p-8 text-center dark:bg-gray-900"><h2 class="text-sm font-semibold">Data Koreksi Absen</h2><p class="mt-1 text-sm text-gray-500">Modul ini belum diaktifkan.</p></section>
        @endif
        @if ($showLeaveModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-950/50 p-4">
                <div class="w-full max-w-2xl rounded-xl bg-white p-5 shadow-xl dark:bg-gray-900">
                    <div class="mb-5 flex items-center justify-between"><h2 class="text-base font-semibold">{{ $leaveId ? 'Ubah Data Izin' : 'Tambah Data Izin' }}</h2><button type="button" wire:click="closeLeaveModal">✕</button></div>
                    <form wire:submit="saveLeave" class="space-y-4">
                        <div class="relative"><label class="mb-1 block text-sm font-medium">Karyawan</label><input type="text" wire:model.live.debounce.300ms="employeeSearch" autocomplete="off" placeholder="Cari nama karyawan..." class="h-10 w-full rounded-lg border-gray-300 text-sm dark:bg-gray-800">
                            @error('employeeId')<span class="text-xs text-danger-600">Pilih karyawan dari daftar.</span>@enderror
                            @if (count($employees) && $employeeSearch !== $employeeName)<div class="absolute z-20 mt-1 max-h-52 w-full overflow-y-auto rounded-lg border bg-white shadow-lg dark:bg-gray-800">@foreach ($employees as $employee)<button type="button" wire:click="selectEmployee({{ $employee->id }})" class="block w-full px-3 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-gray-700">{{ $employee->name }}</button>@endforeach</div>@endif
                            @if ($employeeId && $employeeName)<p class="mt-1 text-xs text-success-600">Dipilih: {{ $employeeName }}</p>@endif
                        </div>
                        <label class="block text-sm font-medium">Jenis<select wire:model="kind" class="mt-1 h-10 w-full rounded-lg border-gray-300 text-sm dark:bg-gray-800"><option value="izin">Izin</option><option value="sakit">Sakit</option><option value="cuti">Cuti</option><option value="dinas">Dinas Luar</option></select></label>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <label class="block text-sm font-medium">Dari tanggal<input type="date" wire:model="dateFrom" class="mt-1 h-10 w-full rounded-lg border-gray-300 text-sm dark:bg-gray-800">@error('dateFrom')<span class="text-xs text-danger-600">{{ $message }}</span>@enderror</label>
                            <label class="block text-sm font-medium">Sampai tanggal (opsional)<input type="date" wire:model="dateTo" class="mt-1 h-10 w-full rounded-lg border-gray-300 text-sm dark:bg-gray-800">@error('dateTo')<span class="text-xs text-danger-600">{{ $message }}</span>@enderror</label>
                        </div>
                        <label class="block text-sm font-medium">Keterangan / Alasan<textarea rows="3" wire:model="note" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:bg-gray-800"></textarea></label>
                        <p class="rounded-lg bg-primary-50 px-3 py-2 text-xs text-primary-800 dark:bg-primary-950 dark:text-primary-200">Hari kerja dengan catatan ini tidak dihitung alpa, walau ada scan.</p>
                        <div class="flex justify-end gap-2"><x-filament::button type="button" color="gray" wire:click="closeLeaveModal">Batal</x-filament::button><x-filament::button type="submit">{{ $leaveId ? 'Simpan Perubahan' : 'Simpan Izin' }}</x-filament::button></div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
