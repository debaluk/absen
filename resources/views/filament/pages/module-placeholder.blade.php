<x-filament-panels::page>
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
            {{ $this->getHeading() }}
        </h2>

        @if ($this->getSubheading())
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ $this->getSubheading() }}
            </p>
        @endif

        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
            Kerangka halaman modul sudah tersedia. Fitur pengelolaan pada bagian ini akan dibangun pada tahap berikutnya.
        </p>
    </div>
</x-filament-panels::page>
