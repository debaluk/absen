<x-filament-panels::page>
    <x-filament::tabs label="Pengaturan sistem">
        <x-filament::tabs.item :active="$activeTab === 'umum'" wire:click="setTab('umum')">Umum</x-filament::tabs.item>
        <x-filament::tabs.item :active="$activeTab === 'organisasi'" wire:click="setTab('organisasi')">Organisasi</x-filament::tabs.item>
        <x-filament::tabs.item :active="$activeTab === 'pengguna'" wire:click="setTab('pengguna')">Pengguna</x-filament::tabs.item>
        <x-filament::tabs.item :active="$activeTab === 'role'" wire:click="setTab('role')">Role</x-filament::tabs.item>
        <x-filament::tabs.item :active="$activeTab === 'permission'" wire:click="setTab('permission')">Permission</x-filament::tabs.item>
    </x-filament::tabs>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        @switch($activeTab)
            @case('umum')
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Umum</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Pengaturan umum aplikasi.</p>
                @break
            @case('organisasi')
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Organisasi</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Data perusahaan dan cabang.</p>
                @break
            @case('pengguna')
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Pengguna</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Pengelolaan akun pengguna aplikasi.</p>
                @break
            @case('role')
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Role</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Pengelolaan peran pengguna.</p>
                @break
            @case('permission')
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Permission</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Pengaturan hak akses setiap peran.</p>
                @break
        @endswitch
        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
            Struktur tab sudah tersedia. Form dan proses pengelolaan akan dibangun pada tahap modul berikutnya.
        </p>
    </div>
</x-filament-panels::page>
