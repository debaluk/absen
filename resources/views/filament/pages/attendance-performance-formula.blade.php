<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-warning-300 bg-warning-50 p-4 text-sm text-warning-900 dark:border-warning-700 dark:bg-warning-950 dark:text-warning-100">
            <div class="font-semibold">Parameter awal masih contoh</div>
            <p class="mt-1">Bobot, toleransi, pengurangan menit, dan batas kategori terisi sebagai simulasi dari dokumen rancangan. Formula harus disimpan sebagai Draft dan hanya diaktifkan setelah kebijakannya disahkan.</p>
        </div>

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">{{ $formulaId ? 'Ubah Formula Draft' : 'Buat Formula Performa' }}</h2>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Satu formula berlaku untuk seluruh perusahaan. Periode penilaian bulanan.</p>
                </div>
                @if ($formulaId)
                    <x-filament::button color="gray" size="sm" outlined wire:click="newFormula">Formula Baru</x-filament::button>
                @endif
            </div>

            <form wire:submit="saveDraft" class="space-y-5">
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="space-y-1">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Nama Formula</span>
                        <input type="text" wire:model="name" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        @error('name')<span class="text-xs text-danger-600">{{ $message }}</span>@enderror
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="space-y-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Mulai Berlaku</span>
                            <input type="date" wire:model="effectiveFrom" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                            @error('effectiveFrom')<span class="text-xs text-danger-600">{{ $message }}</span>@enderror
                        </label>
                        <label class="space-y-1">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Akhir Berlaku</span>
                            <input type="date" wire:model="effectiveUntil" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                            <span class="text-xs text-gray-500">Kosong = tetap berlaku.</span>
                            @error('effectiveUntil')<span class="text-xs text-danger-600">{{ $message }}</span>@enderror
                        </label>
                    </div>
                    <label class="space-y-1 md:col-span-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Keterangan</span>
                        <textarea wire:model="description" rows="2" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white"></textarea>
                    </label>
                </div>

                <div class="border-t border-gray-200 pt-4 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Komponen dan Bobot</h3>
                    <p class="mt-1 text-xs text-gray-500">Total bobot harus tepat 100%.</p>
                    <div class="mt-3 grid gap-4 md:grid-cols-3">
                        <label class="space-y-1">
                            <span class="text-sm text-gray-700 dark:text-gray-200">Tingkat Kehadiran (%)</span>
                            <input type="number" min="0" max="100" step="0.01" wire:model="attendanceWeight" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        </label>
                        <label class="space-y-1">
                            <span class="text-sm text-gray-700 dark:text-gray-200">Ketepatan Waktu (%)</span>
                            <input type="number" min="0" max="100" step="0.01" wire:model="punctualityWeight" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        </label>
                        <label class="space-y-1">
                            <span class="text-sm text-gray-700 dark:text-gray-200">Kepatuhan Jam Kerja (%)</span>
                            <input type="number" min="0" max="100" step="0.01" wire:model="worktimeWeight" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        </label>
                    </div>
                    @error('attendanceWeight')<p class="mt-2 text-xs text-danger-600">{{ $message }}</p>@enderror
                </div>

                <div class="border-t border-gray-200 pt-4 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Keterlambatan</h3>
                    <div class="mt-3 grid gap-4 md:grid-cols-3">
                        <label class="space-y-1">
                            <span class="text-sm text-gray-700 dark:text-gray-200">Toleransi (menit)</span>
                            <input type="number" min="0" max="240" wire:model="lateToleranceMinutes" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        </label>
                        <label class="space-y-1">
                            <span class="text-sm text-gray-700 dark:text-gray-200">Satuan Pengurangan (menit)</span>
                            <input type="number" min="1" wire:model="latePenaltyUnitMinutes" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        </label>
                        <label class="space-y-1">
                            <span class="text-sm text-gray-700 dark:text-gray-200">Poin Pengurangan per Satuan</span>
                            <input type="number" min="0" max="100" step="0.01" wire:model="latePenaltyPoints" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        </label>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-4 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Pulang Lebih Awal</h3>
                    <div class="mt-3 grid gap-4 md:grid-cols-3">
                        <label class="space-y-1">
                            <span class="text-sm text-gray-700 dark:text-gray-200">Toleransi (menit)</span>
                            <input type="number" min="0" max="240" wire:model="earlyLeaveToleranceMinutes" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        </label>
                        <label class="space-y-1">
                            <span class="text-sm text-gray-700 dark:text-gray-200">Satuan Pengurangan (menit)</span>
                            <input type="number" min="1" wire:model="earlyLeavePenaltyUnitMinutes" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        </label>
                        <label class="space-y-1">
                            <span class="text-sm text-gray-700 dark:text-gray-200">Poin Pengurangan per Satuan</span>
                            <input type="number" min="0" max="100" step="0.01" wire:model="earlyLeavePenaltyPoints" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        </label>
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-4 dark:border-gray-800">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Kategori Nilai</h3>
                    <div class="mt-3 grid gap-4 md:grid-cols-3">
                        <label class="space-y-1">
                            <span class="text-sm text-gray-700 dark:text-gray-200">Sangat Baik · mulai</span>
                            <input type="number" min="0" max="100" step="0.01" wire:model="veryGoodMin" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        </label>
                        <label class="space-y-1">
                            <span class="text-sm text-gray-700 dark:text-gray-200">Baik · mulai</span>
                            <input type="number" min="0" max="100" step="0.01" wire:model="goodMin" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950 dark:bg-gray-950 dark:text-white">
                        </label>
                        <label class="space-y-1">
                            <span class="text-sm text-gray-700 dark:text-gray-200">Cukup · mulai</span>
                            <input type="number" min="0" max="100" step="0.01" wire:model="fairMin" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-950 dark:text-white">
                        </label>
                    </div>
                    @error('veryGoodMin')<p class="mt-2 text-xs text-danger-600">{{ $message }}</p>@enderror
                    <p class="mt-2 text-xs text-gray-500">Nilai di bawah batas Cukup masuk kategori Perlu Evaluasi.</p>
                </div>

                <div class="rounded-lg bg-gray-50 p-4 text-sm dark:bg-gray-800/60">
                    <h3 class="font-semibold text-gray-900 dark:text-white">Aturan yang sudah disepakati</h3>
                    <ul class="mt-2 list-inside list-disc space-y-1 text-xs text-gray-600 dark:text-gray-300">
                        <li>Izin disetujui: dikecualikan dari hari efektif.</li>
                        <li>Sakit disetujui: dikecualikan dari hari efektif dan dilaporkan terpisah.</li>
                        <li>Dinas luar disetujui: dihitung hadir.</li>
                        <li>ALPA tetap dalam denominator dan tidak mendapat penalti ganda.</li>
                        <li>Cakupan formula: satu formula perusahaan; periode: bulanan.</li>
                    </ul>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 pt-4 dark:border-gray-800">
                    <p class="text-xs text-gray-500">Simpan sebagai Draft tidak mengubah formula yang sedang berlaku.</p>
                    <div class="flex gap-2">
                        @if ($formulaId)
                            <x-filament::button color="gray" outlined type="button" wire:click="newFormula">Batal</x-filament::button>
                        @endif
                        <x-filament::button type="submit">Simpan Draft</x-filament::button>
                    </div>
                </div>
            </form>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="mb-4">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Riwayat Formula</h2>
                <p class="mt-1 text-xs text-gray-500">Formula aktif berlaku sampai tanggal akhir ditetapkan atau digantikan oleh formula berikutnya.</p>
            </div>

            @if ($formulas->isEmpty())
                <div class="rounded-lg border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500 dark:border-gray-700">
                    Belum ada formula. Isi konfigurasi di atas lalu simpan sebagai Draft.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-sm">
                        <thead class="border-b border-gray-200 text-xs text-gray-500 dark:border-gray-700">
                            <tr>
                                <th class="px-3 py-3 font-medium">Formula</th>
                                <th class="px-3 py-3 font-medium">Masa Berlaku</th>
                                <th class="px-3 py-3 font-medium">Bobot K / T / J</th>
                                <th class="px-3 py-3 font-medium">Status</th>
                                <th class="px-3 py-3 text-right font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($formulas as $formula)
                                <tr>
                                    <td class="px-3 py-3">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $formula->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $formula->code }}</div>
                                    </td>
                                    <td class="px-3 py-3 text-xs text-gray-600 dark:text-gray-300">
                                        {{ \Illuminate\Support\Carbon::parse($formula->effective_from)->format('d/m/Y') }}
                                        –
                                        {{ $formula->effective_until ? \Illuminate\Support\Carbon::parse($formula->effective_until)->format('d/m/Y') : 'Belum berakhir' }}
                                    </td>
                                    <td class="px-3 py-3 text-xs text-gray-600 dark:text-gray-300">
                                        {{ number_format($formula->attendance_weight, 2, ',', '.') }} /
                                        {{ number_format($formula->punctuality_weight, 2, ',', '.') }} /
                                        {{ number_format($formula->worktime_weight, 2, ',', '.') }}
                                    </td>
                                    <td class="px-3 py-3">
                                        @php
                                            $statusClasses = [
                                                'draft' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200',
                                                'active' => 'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-300',
                                                'ended' => 'bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-300',
                                                'scheduled' => 'bg-info-100 text-info-700 dark:bg-info-900/30 dark:text-info-300',
                                            ];
                                            $displayStatus = $formula->status;
                                            if ($formula->status === 'active' && $formula->effective_from > today()->toDateString()) {
                                                $displayStatus = 'scheduled';
                                            } elseif ($formula->status === 'active' && $formula->effective_until && $formula->effective_until < today()->toDateString()) {
                                                $displayStatus = 'ended';
                                            }
                                        @endphp
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClasses[$displayStatus] ?? $statusClasses['draft'] }}">
                                            {{ ['draft' => 'Draft', 'active' => 'Berlaku', 'ended' => 'Berakhir', 'scheduled' => 'Terjadwal'][$displayStatus] ?? $displayStatus }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-3">
                                        <div class="flex justify-end gap-2">
                                            @if ($formula->status === 'draft')
                                                <x-filament::button size="xs" color="gray" outlined wire:click="editFormula({{ $formula->id }})">Ubah</x-filament::button>
                                                <label class="flex items-center gap-1 text-xs text-gray-600 dark:text-gray-300">
                                                    <input type="checkbox" wire:model="confirmPolicy" class="rounded border-gray-300">
                                                    Disahkan
                                                </label>
                                                <x-filament::button size="xs" wire:click="activateFormula({{ $formula->id }})">Aktifkan</x-filament::button>
                                            @elseif ($formula->status === 'active' && $formula->effective_from <= today()->toDateString() && (! $formula->effective_until || $formula->effective_until >= today()->toDateString()))
                                                <x-filament::button size="xs" color="warning" outlined wire:click="endFormula({{ $formula->id }})" wire:confirm="Akhiri masa berlaku formula ini hari ini?">Akhiri</x-filament::button>
                                            @elseif ($displayStatus === 'scheduled')
                                                <span class="text-xs text-gray-400">Menunggu tanggal mulai</span>
                                            @else
                                                <span class="text-xs text-gray-400">Riwayat</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
