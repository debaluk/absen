<x-filament-panels::page>
    <div class="space-y-6 fs-dashboard">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-slate-500">Ringkasan operasional kehadiran</p>
                <h2 class="mt-1 text-xl font-bold tracking-tight text-slate-800">{{ $todayLabel }}</h2>
            </div>
            <div class="flex items-center gap-2">
                <label for="dashboard-location" class="text-sm font-medium text-slate-600">Lokasi kerja</label>
                <select id="dashboard-location" wire:model.live="workLocationId" class="fs-dashboard-select">
                    <option value="">Semua cabang</option>
                    @foreach ($workLocations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="fs-kpi fs-kpi-blue">
                <div class="flex items-start justify-between">
                    <div><p>Total Karyawan</p><strong>{{ number_format($totalEmployees, 0, ',', '.') }}</strong><span>Seluruh karyawan terdaftar</span></div>
                    <div class="fs-kpi-icon"><x-filament::icon icon="heroicon-o-users" class="h-6 w-6" /></div>
                </div>
                <div class="fs-kpi-bottom"><span>Data master</span><span class="fs-kpi-link">Karyawan</span></div>
            </div>
            <div class="fs-kpi fs-kpi-green">
                <div class="flex items-start justify-between">
                    <div><p>Karyawan Aktif</p><strong>{{ number_format($activeEmployees, 0, ',', '.') }}</strong><span>Berstatus aktif</span></div>
                    <div class="fs-kpi-icon"><x-filament::icon icon="heroicon-o-user-circle" class="h-6 w-6" /></div>
                </div>
                <div class="fs-kpi-bottom"><span>Status kepegawaian</span><span class="fs-kpi-link">Aktif</span></div>
            </div>
            <div class="fs-kpi fs-kpi-rose">
                <div class="flex items-start justify-between">
                    <div><p>Karyawan Nonaktif</p><strong>{{ number_format($inactiveEmployees, 0, ',', '.') }}</strong><span>Berstatus tidak aktif</span></div>
                    <div class="fs-kpi-icon"><x-filament::icon icon="heroicon-o-user-minus" class="h-6 w-6" /></div>
                </div>
                <div class="fs-kpi-bottom"><span>Status kepegawaian</span><span class="fs-kpi-link">Nonaktif</span></div>
            </div>
            <div class="fs-kpi fs-kpi-violet">
                <div class="flex items-start justify-between">
                    <div><p>Scan Hari Ini</p><strong>{{ number_format($todayScans, 0, ',', '.') }}</strong><span>Log scan mentah diterima</span></div>
                    <div class="fs-kpi-icon"><x-filament::icon icon="heroicon-o-finger-print" class="h-6 w-6" /></div>
                </div>
                <div class="fs-kpi-bottom"><span>Aktivitas mesin</span><span class="fs-kpi-link">Hari ini</span></div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
            <section class="fs-dashboard-panel xl:col-span-2">
                <div class="fs-panel-heading">
                    <div><h3>Aktivitas Scan Hari Ini</h3><p>Jumlah log mesin berdasarkan jam scan</p></div>
                    <span class="fs-panel-tag"><span></span> Data aktual</span>
                </div>
                <div class="fs-hourly-chart">
                    @foreach ($hourlyScans as $item)
                        <div class="fs-hour-column" title="{{ $item['hour'] }}:00 — {{ number_format($item['total'], 0, ',', '.') }} scan">
                            <div class="fs-hour-bar-wrap"><div class="fs-hour-bar" style="height: {{ max(3, ($item['total'] / $maxHourlyScans) * 100) }}%"></div></div>
                            @if ($loop->first || $loop->last || $loop->index % 3 === 0)
                                <span>{{ $item['hour'] }}</span>
                            @else
                                <span class="fs-hour-hidden">·</span>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="fs-chart-foot"><span><i></i> Jumlah scan masuk ke server</span><strong>{{ number_format($todayScans, 0, ',', '.') }} scan</strong></div>
            </section>

            <section class="fs-dashboard-panel">
                <div class="fs-panel-heading"><div><h3>Status Kehadiran</h3><p>Persentase status kehadiran hari ini</p></div></div>
                <div class="fs-attendance-pie-wrap">
                    <div class="fs-attendance-pie">
                        <div><strong>—</strong><span>Belum tersedia</span></div>
                    </div>
                </div>
                <div class="fs-attendance-legend">
                    <div><span class="fs-attendance-dot fs-dot-present"></span><span>Hadir</span><strong>—%</strong></div>
                    <div><span class="fs-attendance-dot fs-dot-permission"></span><span>Izin</span><strong>—%</strong></div>
                    <div><span class="fs-attendance-dot fs-dot-sick"></span><span>Sakit</span><strong>—%</strong></div>
                    <div><span class="fs-attendance-dot fs-dot-leave"></span><span>Cuti</span><strong>—%</strong></div>
                    <div><span class="fs-attendance-dot fs-dot-absent"></span><span>Alpa</span><strong>—%</strong></div>
                </div>
                <p class="mt-3 text-center text-xs text-slate-400">Perhitungan status kehadiran belum diimplementasikan.</p>
            </section>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
            <section class="fs-dashboard-panel">
                <div class="fs-panel-heading"><div><h3>Karyawan per Lokasi</h3><p>Jumlah karyawan dan yang berstatus aktif</p></div></div>
                <div class="space-y-4 pt-2">
                    @forelse ($locationStats as $location)
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3 text-sm">
                                <span class="font-semibold text-slate-700">{{ $location->name }}</span>
                                <span class="text-slate-500">{{ number_format($location->active_employees, 0, ',', '.') }} / {{ number_format($location->total_employees, 0, ',', '.') }} aktif</span>
                            </div>
                            <div class="fs-location-track"><div class="fs-location-fill" style="width: {{ $location->total_employees > 0 ? ($location->active_employees / $location->total_employees) * 100 : 0 }}%"></div></div>
                        </div>
                    @empty
                        <div class="fs-dashboard-empty">Belum ada data lokasi kerja.</div>
                    @endforelse
                </div>
            </section>

            <section class="fs-dashboard-panel">
                <div class="fs-panel-heading"><div><h3>Status Mesin Absensi</h3><p>Informasi perangkat dan aktivitas terakhir</p></div><span class="fs-count-pill">{{ $devices->count() }} mesin</span></div>
                <div class="fs-device-list">
                    @forelse ($devices as $device)
                        <div class="fs-device-row">
                            <div class="fs-device-icon"><x-filament::icon icon="heroicon-o-cpu-chip" class="h-5 w-5" /></div>
                            <div class="min-w-0 flex-1"><strong>{{ $device->name ?: $device->cloud_id }}</strong><span>{{ $device->location_name ?: 'Lokasi belum ditentukan' }} · ID {{ $device->cloud_id }}</span></div>
                            <div class="text-right"><span class="fs-device-state">{{ $device->last_activity ? 'Ada aktivitas' : 'Belum ada data' }}</span><small>{{ $device->last_activity ? \Illuminate\Support\Carbon::parse($device->last_activity)->format('d/m H:i') : '—' }}</small></div>
                        </div>
                    @empty
                        <div class="fs-dashboard-empty">Belum ada mesin terdaftar.</div>
                    @endforelse
                </div>
                <p class="mt-3 text-xs text-slate-400">Status online/offline belum disimpulkan karena batas waktu heartbeat mesin belum ditetapkan.</p>
            </section>
        </div>

        <section class="fs-dashboard-panel !p-0">
            <div class="fs-panel-heading border-b border-slate-100 px-5 py-4 sm:px-6">
                <div><h3>Aktivitas Absensi Terbaru</h3><p>Delapan log scan terakhir dari mesin</p></div>
                <span class="fs-count-pill">{{ $recentLogs->count() }} log</span>
            </div>
            <div class="overflow-x-auto">
                <table class="fs-dashboard-table">
                    <thead><tr><th>Waktu Scan</th><th>Nama Karyawan / PIN</th><th>Mesin</th><th>Kode Scan</th></tr></thead>
                    <tbody>
                        @forelse ($recentLogs as $log)
                            <tr>
                                <td class="whitespace-nowrap font-medium">{{ \Illuminate\Support\Carbon::parse($log->scan_date)->format('d-m-Y H:i:s') }}</td>
                                <td><strong>{{ $log->employee_name ?: ($log->machine_user_name ?: 'Pengguna belum dikenal') }}</strong><small>PIN: {{ $log->pin }}</small></td>
                                <td><strong>{{ $log->device_name ?: 'Mesin tidak dikenal' }}</strong><small>{{ $log->cloud_id }}</small></td>
                                <td><span class="fs-scan-code">{{ $log->status_scan === null ? '—' : 'Kode ' . $log->status_scan }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-10 text-center text-slate-400">Belum ada log scan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <style>
        .fs-dashboard { color: #253247; }
        .fs-dashboard-select { min-width: 12rem; height: 2.55rem; border: 1px solid #d7e0ea; border-radius: .65rem; background: #fff; padding: 0 .8rem; color: #334155; font-size: .85rem; box-shadow: 0 1px 2px rgb(15 23 42 / 4%); }
        .fs-kpi { position: relative; overflow: hidden; border: 1px solid transparent; border-radius: 1rem; padding: 1.25rem; box-shadow: 0 5px 16px rgb(15 23 42 / 4%); }
        .fs-kpi > div:first-child { min-height: 6.2rem; }
        .fs-kpi p { margin: 0; color: #526176; font-size: .82rem; font-weight: 650; }
        .fs-kpi strong { display: block; margin-top: .55rem; color: #1e293b; font-size: 2rem; font-weight: 800; line-height: 1.05; letter-spacing: -.04em; }
        .fs-kpi > div:first-child span { display: block; margin-top: .4rem; color: #718096; font-size: .72rem; }
        .fs-kpi-icon { display: grid; width: 2.65rem; height: 2.65rem; place-items: center; border-radius: .8rem; }
        .fs-kpi-bottom { display: flex; justify-content: space-between; border-top: 1px solid rgb(100 116 139 / 13%); margin-top: .85rem; padding-top: .7rem; color: #718096; font-size: .7rem; }
        .fs-kpi-link { font-weight: 700; }
        .fs-kpi-blue { background: linear-gradient(135deg,#eff6ff,#dbeafe); border-color: #dbeafe; } .fs-kpi-blue .fs-kpi-icon { background: #bfdbfe; color: #1d4ed8; } .fs-kpi-blue .fs-kpi-link { color: #1d4ed8; }
        .fs-kpi-green { background: linear-gradient(135deg,#ecfdf5,#d1fae5); border-color: #bbf7d0; } .fs-kpi-green .fs-kpi-icon { background: #a7f3d0; color: #047857; } .fs-kpi-green .fs-kpi-link { color: #047857; }
        .fs-kpi-rose { background: linear-gradient(135deg,#fff1f2,#ffe4e6); border-color: #fecdd3; } .fs-kpi-rose .fs-kpi-icon { background: #fecdd3; color: #be123c; } .fs-kpi-rose .fs-kpi-link { color: #be123c; }
        .fs-kpi-violet { background: linear-gradient(135deg,#f5f3ff,#ede9fe); border-color: #ddd6fe; } .fs-kpi-violet .fs-kpi-icon { background: #ddd6fe; color: #6d28d9; } .fs-kpi-violet .fs-kpi-link { color: #6d28d9; }
        .fs-dashboard-panel { min-width: 0; border: 1px solid #e2e8f0; border-radius: 1rem; background: #fff; padding: 1.25rem; box-shadow: 0 4px 15px rgb(15 23 42 / 3%); }
        .fs-panel-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; margin-bottom: 1.1rem; }
        .fs-panel-heading h3 { margin: 0; color: #243247; font-size: .95rem; font-weight: 750; }
        .fs-panel-heading p { margin: .25rem 0 0; color: #94a3b8; font-size: .72rem; }
        .fs-panel-tag { display: inline-flex; align-items: center; gap: .4rem; white-space: nowrap; border-radius: 999px; background: #ecfdf5; padding: .35rem .6rem; color: #047857; font-size: .68rem; font-weight: 700; }
        .fs-panel-tag span { width: .4rem; height: .4rem; border-radius: 50%; background: #10b981; }
        .fs-hourly-chart { display: grid; height: 12.5rem; grid-template-columns: repeat(24,minmax(0,1fr)); align-items: end; gap: .35rem; padding: .5rem 0 0; border-bottom: 1px solid #e8edf3; background: repeating-linear-gradient(to top,transparent 0,transparent 24%,#f1f5f9 25%,transparent 26%); }
        .fs-hour-column { display: flex; height: 100%; min-width: 0; flex-direction: column; align-items: center; justify-content: end; gap: .45rem; }
        .fs-hour-bar-wrap { display: flex; width: 100%; height: calc(100% - 1.2rem); align-items: end; }
        .fs-hour-bar { width: 100%; min-height: 3px; border-radius: .35rem .35rem .1rem .1rem; background: linear-gradient(180deg,#60a5fa,#2563eb); transition: height .2s ease; }
        .fs-hour-column span { color: #94a3b8; font-size: .58rem; }
        .fs-hour-hidden { visibility: hidden; }
        .fs-chart-foot { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-top: .9rem; color: #94a3b8; font-size: .7rem; }
        .fs-chart-foot span { display: inline-flex; align-items: center; gap: .4rem; } .fs-chart-foot i { width: .45rem; height: .45rem; border-radius: 50%; background: #3b82f6; } .fs-chart-foot strong { color: #334155; }
        .fs-attendance-pie-wrap { display: flex; justify-content: center; padding: .15rem 0 1rem; }
        .fs-attendance-pie { display: grid; width: 9.5rem; aspect-ratio: 1; place-items: center; border-radius: 50%; background: conic-gradient(#e2e8f0 0 100%); }
        .fs-attendance-pie::before { content: ''; grid-area: 1/1; width: 6.4rem; aspect-ratio: 1; border-radius: 50%; background: #fff; }
        .fs-attendance-pie > div { z-index: 1; grid-area: 1/1; display: flex; flex-direction: column; align-items: center; }
        .fs-attendance-pie strong { color: #64748b; font-size: 1.35rem; font-weight: 800; } .fs-attendance-pie span { color: #94a3b8; font-size: .62rem; }
        .fs-attendance-legend { display: grid; gap: .55rem; }
        .fs-attendance-legend > div { display: flex; align-items: center; gap: .5rem; color: #64748b; font-size: .75rem; }
        .fs-attendance-legend strong { margin-left: auto; color: #334155; font-weight: 750; }
        .fs-attendance-dot { width: .58rem; height: .58rem; flex: 0 0 auto; border-radius: 50%; }
        .fs-dot-present { background: #10b981; } .fs-dot-permission { background: #3b82f6; } .fs-dot-sick { background: #f59e0b; } .fs-dot-leave { background: #8b5cf6; } .fs-dot-absent { background: #f43f5e; }
        .fs-location-track { height: .65rem; overflow: hidden; border-radius: 999px; background: #f1f5f9; } .fs-location-fill { height: 100%; border-radius: inherit; background: linear-gradient(90deg,#34d399,#059669); }
        .fs-count-pill { border-radius: 999px; background: #f1f5f9; padding: .35rem .65rem; color: #64748b; font-size: .68rem; font-weight: 700; white-space: nowrap; }
        .fs-device-list { display: grid; gap: .15rem; } .fs-device-row { display: flex; align-items: center; gap: .75rem; border-bottom: 1px solid #f1f5f9; padding: .7rem 0; } .fs-device-row:last-child { border-bottom: 0; }
        .fs-device-icon { display: grid; width: 2.4rem; height: 2.4rem; flex: 0 0 auto; place-items: center; border-radius: .7rem; background: #eff6ff; color: #2563eb; }
        .fs-device-row strong,.fs-dashboard-table td strong { display: block; overflow: hidden; color: #334155; font-size: .78rem; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
        .fs-device-row span,.fs-dashboard-table td small { display: block; overflow: hidden; margin-top: .2rem; color: #94a3b8; font-size: .66rem; text-overflow: ellipsis; white-space: nowrap; }
        .fs-device-state { color: #047857 !important; font-weight: 700; } .fs-device-row small { display: block; margin-top: .2rem; color: #94a3b8; font-size: .65rem; }
        .fs-dashboard-empty { padding: 2rem .5rem; text-align: center; color: #94a3b8; font-size: .8rem; }
        .fs-dashboard-table { width: 100%; min-width: 42rem; border-collapse: collapse; text-align: left; font-size: .78rem; }
        .fs-dashboard-table th { background: #f8fafc; padding: .75rem 1.25rem; color: #94a3b8; font-size: .65rem; font-weight: 750; letter-spacing: .06em; text-transform: uppercase; }
        .fs-dashboard-table td { border-top: 1px solid #f1f5f9; padding: .85rem 1.25rem; color: #64748b; }
        .fs-scan-code { display: inline-flex; border-radius: 999px; background: #eff6ff; padding: .3rem .55rem; color: #1d4ed8; font-size: .67rem; font-weight: 700; }
        @media (max-width: 640px) { .fs-dashboard-select { min-width: 0; flex: 1; } .fs-hourly-chart { gap: .15rem; } .fs-hour-column span { font-size: .5rem; } .fs-dashboard-panel { padding: 1rem; } }
    </style>
</x-filament-panels::page>