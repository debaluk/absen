<x-filament-panels::page>
    <div class="space-y-5">
        <section class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <div class="mb-4">
                <h2 class="text-sm font-semibold text-gray-950 dark:text-white">{{ $holidayId ? 'Ubah Hari Libur' : 'Tambah Hari Libur' }}</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pilih satu atau beberapa cabang. Jika tidak memilih cabang, hari libur berlaku untuk semua cabang.</p>
            </div>
            <form wire:submit="saveHoliday" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <label class="block text-sm">
                    <span class="mb-1.5 block font-medium text-gray-700 dark:text-gray-300">Tanggal Libur</span>
                    <input type="date" wire:model="date" class="block h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                    @error('date') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                </label>
                <label class="block text-sm">
                    <span class="mb-1.5 block font-medium text-gray-700 dark:text-gray-300">Keterangan Libur</span>
                    <input type="text" wire:model="note" maxlength="255" placeholder="Contoh: Hari Raya Idul Fitri" class="block h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500">
                    @error('note') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                </label>
                <div class="block text-sm md:col-span-2">
                    <span class="mb-2 block font-medium text-gray-700 dark:text-gray-300">Berlaku untuk</span>
                    <div class="flex max-h-40 flex-wrap gap-x-6 gap-y-3 overflow-y-auto rounded-lg border border-gray-300 p-3 dark:border-gray-600 dark:bg-gray-800">
                        @foreach ($workLocations as $location)
                            <label class="inline-flex min-w-36 items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <input type="checkbox" wire:model="workLocationIds" value="{{ $location->id }}" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <span>{{ $location->name }}{{ $location->is_active ? '' : ' (Nonaktif)' }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Centang lokasi yang berlaku. Kosongkan semua pilihan untuk Semua Cabang.</p>
                    @error('workLocationIds') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                    @error('workLocationIds.*') <span class="mt-1 block text-xs text-danger-600">{{ $message }}</span> @enderror
                </div>
                <div class="flex items-end gap-2 md:col-span-2">
                    <x-filament::button type="submit" size="sm">{{ $holidayId ? 'Simpan Perubahan' : 'Tambah Hari Libur' }}</x-filament::button>
                    @if ($holidayId)
                        <x-filament::button type="button" color="gray" size="sm" wire:click="resetForm">Batal</x-filament::button>
                    @endif
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col gap-3 border-b border-gray-200 px-4 py-3 dark:border-gray-700 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-gray-950 dark:text-white">Daftar Hari Libur</h2>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Filter awal otomatis menggunakan bulan berjalan.</p>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:w-auto">
                    <label class="text-xs">
                        <span class="mb-1 block font-medium text-gray-600 dark:text-gray-300">Bulan</span>
                        <input type="month" wire:model.live="filterMonth" class="w-full rounded-lg border border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                    </label>
                    <label class="text-xs">
                        <span class="mb-1 block font-medium text-gray-600 dark:text-gray-300">Cabang</span>
                        <select wire:model.live="filterWorkLocationId" class="w-full rounded-lg border border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                            <option value="">Semua Cabang</option>
                            @foreach ($workLocations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                <div class="flex items-center gap-1 rounded-lg bg-gray-100 p-1 dark:bg-gray-800">
                    <button type="button" wire:click="$set('viewMode', 'list')" class="rounded-md px-3 py-1.5 text-xs font-medium {{ $viewMode === 'list' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white' : 'text-gray-600 dark:text-gray-300' }}">
                        <x-heroicon-m-list-bullet class="mr-1 inline h-4 w-4" /> List
                    </button>
                    <button type="button" wire:click="$set('viewMode', 'calendar')" class="rounded-md px-3 py-1.5 text-xs font-medium {{ $viewMode === 'calendar' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white' : 'text-gray-600 dark:text-gray-300' }}">
                        <x-heroicon-m-calendar-days class="mr-1 inline h-4 w-4" /> Kalender
                    </button>
                </div>
                @if ($viewMode === 'calendar')
                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $calendarMonthLabel }}</div>
                @else
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($holidays->total(), 0, ',', '.') }} data</div>
                @endif
            </div>

            @if ($viewMode === 'list')
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            <tr><th class="whitespace-nowrap px-4 py-3">Tanggal</th><th class="px-4 py-3">Cakupan</th><th class="px-4 py-3">Keterangan</th><th class="w-24 px-4 py-3 text-center">Aksi</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($holidays as $holiday)
                                <tr class="text-gray-700 dark:text-gray-200">
                                    <td class="whitespace-nowrap px-4 py-3 font-medium">{{ \Illuminate\Support\Carbon::parse($holiday->date)->translatedFormat('d M Y') }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        @if (blank($holiday->work_location_names))
                                            <span class="inline-flex rounded-full bg-primary-50 px-2.5 py-1 text-xs font-medium text-primary-700 dark:bg-primary-950 dark:text-primary-300">Semua Cabang</span>
                                        @else
                                            {{ $holiday->work_location_names }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">{{ $holiday->note ?: '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <div class="flex items-center justify-center gap-3">
                                            <button type="button" wire:click="editHoliday({{ $holiday->id }})" title="Ubah" aria-label="Ubah" class="text-primary-600 hover:text-primary-500 dark:text-primary-400"><x-heroicon-m-pencil-square class="h-5 w-5" /></button>
                                            <button type="button" wire:click="deleteHoliday({{ $holiday->id }})" wire:confirm="Hapus hari libur ini?" title="Hapus" aria-label="Hapus" class="text-danger-600 hover:text-danger-500 dark:text-danger-400"><x-heroicon-m-trash class="h-5 w-5" /></button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">Tidak ada hari libur pada bulan dan cabang yang dipilih.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-4 py-3 dark:border-gray-800">{{ $holidays->links() }}</div>
            @else
                <div class="grid grid-cols-7 border-b border-gray-200 bg-gray-50 text-center text-xs font-semibold text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    @foreach (['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $weekday)<div class="px-1 py-3">{{ $weekday }}</div>@endforeach
                </div>
                <div class="grid grid-cols-7">
                    @foreach ($calendarDays as $calendarDay)
                        <div class="min-h-28 border-b border-r border-gray-100 p-1.5 dark:border-gray-800 sm:min-h-32 sm:p-2 {{ $calendarDay['inMonth'] ? '' : 'bg-gray-50/70 dark:bg-gray-950/40' }}">
                            <div class="mb-1 flex justify-end">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full text-xs {{ $calendarDay['isToday'] ? 'bg-primary-600 font-bold text-white' : ($calendarDay['inMonth'] ? 'text-gray-700 dark:text-gray-200' : 'text-gray-400') }}">{{ $calendarDay['day'] }}</span>
                            </div>
                            <div class="space-y-1">
                                @foreach ($calendarDay['holidays'] as $calendarHoliday)
                                    <div class="rounded px-1.5 py-1 text-[10px] leading-tight sm:text-xs {{ blank($calendarHoliday->work_location_names) ? 'bg-primary-100 text-primary-800 dark:bg-primary-950 dark:text-primary-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200' }}" title="{{ $calendarHoliday->note }}{{ $calendarHoliday->work_location_names ? ' - '.$calendarHoliday->work_location_names : ' - Semua Cabang' }}">
                                        <div class="truncate font-semibold">{{ $calendarHoliday->note ?: 'Hari Libur' }}</div>
                                        <div class="truncate opacity-80">{{ $calendarHoliday->work_location_names ?: 'Semua Cabang' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
