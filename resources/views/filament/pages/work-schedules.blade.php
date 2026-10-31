<x-filament-panels::page>
    <x-filament::tabs label="Pengaturan jam kerja">
        <x-filament::tabs.item :active="$activeTab === 'master-shift'" wire:click="setTab('master-shift')">
            Master Shift
        </x-filament::tabs.item>
        <x-filament::tabs.item :active="$activeTab === 'penugasan-jadwal'" wire:click="setTab('penugasan-jadwal')">
            Penugasan Jadwal
        </x-filament::tabs.item>
    </x-filament::tabs>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        @if ($activeTab === 'master-shift')
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Master Shift</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Pengelolaan pola jam kerja dan definisi shift.</p>
        @else
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Penugasan Jadwal</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Penetapan jadwal kerja kepada karyawan.</p>
        @endif
        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
            Struktur tab sudah tersedia. Form dan proses pengelolaan akan dibangun pada tahap modul berikutnya.
        </p>
    </div>
</x-filament-panels::page>
