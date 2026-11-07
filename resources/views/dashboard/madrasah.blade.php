@extends('layouts.app')

@section('content')
    <div class="mb-5 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Pendidikan Madrasah</h2>
            <p class="text-sm text-slate-500 font-medium mt-1">Data madrasah (RA, MI, MTS, MA) beserta konteks sosial keagamaan per kecamatan.</p>
        </div>
        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider bg-slate-100 rounded-full px-3 py-1.5 w-fit">
            Kemenag Kabupaten Banjarnegara
        </div>
    </div>

    <form method="GET" class="flex flex-wrap items-end gap-3 mb-5 px-4 sm:px-5 py-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs">
        <div class="min-w-40">
            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1">Jenjang</label>
            <select name="level" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-sm text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition cursor-pointer">
                <option value="ALL">Semua Jenjang</option>
                @foreach ($levelOrder as $lv)
                    <option value="{{ $lv }}" @selected(request('level') === $lv)>{{ $lv }}</option>
                @endforeach
            </select>
        </div>
        <div class="min-w-40">
            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1">Tahun Ajaran</label>
            <select name="tahun_ajaran" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-sm text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition cursor-pointer">
                <option value="">Semua Tahun</option>
                @foreach ($years as $yr)
                    <option value="{{ $yr }}" @selected(request('tahun_ajaran') == $yr)>{{ $yr }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Madrasah</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($stats['sekolah'], 0, ',', '.') }}</p>
                    <p class="text-[11px] font-semibold mt-1 text-slate-500">{{ number_format($stats['negeri'], 0, ',', '.') }} negeri &middot; {{ number_format($stats['swasta'], 0, ',', '.') }} swasta</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 11V10.33"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Guru</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($stats['guru'], 0, ',', '.') }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347M7.5 17.25h.484a60.47 60.47 0 006.316-1.026M12 3a75.408 75.408 0 014.865 5.184M19.5 9.75c.365 1.31.631 2.651.795 4.015M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.14-.786-3.07M15 19.128v.008h-6c-1.105 0-2.1-.424-2.86-1.118M9 6.75a3 3 0 116 0m-9 8.25a3 3 0 100-6 3 3 0 000 6zm0 0v1c0 1.105.895 2 2 2h4.5"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Murid</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($stats['murid'], 0, ',', '.') }}</p>
                    @if ($stats['guru'] > 0)
                        <p class="text-[11px] font-semibold mt-1 text-slate-500">Rasio 1 : {{ number_format($stats['murid'] / $stats['guru'], 1, ',', '.') }}</p>
                    @endif
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.14-.786-3.07M15 19.128v.008h-6c-1.105 0-2.1-.424-2.86-1.118M9 6.75a3 3 0 116 0m-9 8.25a3 3 0 100-6 3 3 0 000 6zm0 0v1c0 1.105.895 2 2 2h4.5"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Nikah ({{ $nikahStats['tahun'] }})</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($nikahStats['nikah'], 0, ',', '.') }}</p>
                    <p class="text-[11px] font-semibold mt-1 text-slate-500">{{ number_format($nikahStats['cerai'], 0, ',', '.') }} perceraian</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center shrink-0 border border-violet-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.712a.75.75 0 01-.634.357h-.052a.75.75 0 01-.634-.357C11.865 4.876 10.203 3.75 8.268 3.75 5.68 3.75 3.581 5.765 3.581 8.25c0 2.43 1.682 4.922 3.518 6.882A39.832 39.832 0 0112 17.75c.97 0 1.912.114 2.817.325m3.35 0c.14-1.096.386-2.315.386-3.175 0-.907-.033-1.814-.014-2.72M6.621 12.12c.246.496.53.973.868 1.425M12 17.75v7.5"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4 sm:gap-6 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 xl:col-span-3">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Perkembangan Madrasah per Tahun</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Jumlah madrasah, guru, dan murid per tahun ajaran</p>
                </div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Tren Multi-Tahun</div>
            </div>
            <div class="relative h-80"><canvas id="trendChart"></canvas></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 xl:col-span-2">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Sebaran per Jenjang</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Proporsi madrasah tiap jenjang</p>
                </div>
            </div>
            <div class="relative h-80"><canvas id="levelChart"></canvas></div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4 sm:gap-6 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 xl:col-span-3">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Data Madrasah per Kecamatan</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Sebaran madrasah, guru, dan murid</p>
                </div>
            </div>
            <div class="relative h-96"><canvas id="kecamatanChart"></canvas></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 xl:col-span-2">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Konteks Keagamaan</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Tempat ibadah ({{ $ibadahStats['tahun'] ?? '' }})</p>
                </div>
            </div>
            <div class="space-y-3">
                @php
                    $ibadahItems = [
                        ['Masjid', $ibadahStats['masjid'] ?? 0, 'text-emerald-600 bg-emerald-50 border-emerald-100'],
                        ['Mushola', $ibadahStats['mushola'] ?? 0, 'text-blue-600 bg-blue-50 border-blue-100'],
                        ['Gereja', $ibadahStats['gereja'] ?? 0, 'text-amber-600 bg-amber-50 border-amber-100'],
                        ['Pura', $ibadahStats['pura'] ?? 0, 'text-violet-600 bg-violet-50 border-violet-100'],
                        ['Vihara', $ibadahStats['vihara'] ?? 0, 'text-red-600 bg-red-50 border-red-100'],
                    ];
                @endphp
                @foreach ($ibadahItems as [$label, $val, $cls])
                    <div class="flex items-center justify-between gap-3 px-4 py-3 rounded-xl bg-slate-50/70 border border-slate-100">
                        <span class="text-sm font-semibold text-slate-700">{{ $label }}</span>
                        <span class="text-sm font-bold text-slate-900">{{ number_format($val, 0, ',', '.') }}</span>
                    </div>
                @endforeach
                <div class="flex items-center justify-between gap-3 px-4 py-3 rounded-xl bg-slate-100 border border-slate-200">
                    <span class="text-sm font-bold text-slate-800">Total Tempat Ibadah</span>
                    <span class="text-sm font-extrabold text-slate-900">{{ number_format($ibadahStats['total'] ?? 0, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.color = '#64748b';

        const palette = ['#2563eb', '#10b981', '#f59e0b', '#94a3b8', '#8b5cf6', '#ef4444'];
        const trendData = @json($trendData);
        const levelBreakdown = @json($levelBreakdown);
        const kecamatanData = @json($kecamatanData);

        const ctx1 = document.getElementById('trendChart');
        if (ctx1) {
            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: trendData.map(d => d.tahun),
                    datasets: [
                        { label: 'Madrasah', data: trendData.map(d => d.sekolah), borderColor: '#2563eb', backgroundColor: 'rgba(37, 99, 235, 0.1)', borderWidth: 2, pointRadius: 3, pointBackgroundColor: '#2563eb', tension: 0.35, fill: true },
                        { label: 'Guru', data: trendData.map(d => d.guru), borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.1)', borderWidth: 2, pointRadius: 3, pointBackgroundColor: '#10b981', tension: 0.35, fill: true },
                        { label: 'Murid', data: trendData.map(d => d.murid), borderColor: '#f59e0b', backgroundColor: 'rgba(245, 158, 11, 0.1)', borderWidth: 2, pointRadius: 3, pointBackgroundColor: '#f59e0b', tension: 0.35, fill: true }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 16 },
                        tooltip: {
                            backgroundColor: '#0f172a', padding: 12, cornerRadius: 10,
                            callbacks: { label: c => ` ${c.dataset.label}: ${c.parsed.y.toLocaleString('id-ID')}` }
                        }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.2)' }, ticks: { callback: v => v >= 1000 ? (v / 1000).toLocaleString('id-ID') + ' rb' : v } }
                    }
                }
            });
        }

        const ctx2 = document.getElementById('levelChart');
        if (ctx2) {
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: levelBreakdown.map(d => d.level),
                    datasets: [{ data: levelBreakdown.map(d => d.sekolah), backgroundColor: palette, borderWidth: 2, borderColor: '#ffffff' }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: { position: 'bottom', usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 12 },
                        tooltip: {
                            backgroundColor: '#0f172a', padding: 12, cornerRadius: 10,
                            callbacks: { label: c => ` ${c.label}: ${c.parsed.toLocaleString('id-ID')} madrasah` }
                        }
                    }
                }
            });
        }

        const ctx3 = document.getElementById('kecamatanChart');
        if (ctx3) {
            new Chart(ctx3, {
                type: 'bar',
                data: {
                    labels: kecamatanData.map(d => d.kecamatan),
                    datasets: [
                        { label: 'Madrasah', data: kecamatanData.map(d => d.sekolah), backgroundColor: '#2563eb', borderRadius: 4, maxBarThickness: 16 },
                        { label: 'Guru', data: kecamatanData.map(d => d.guru), backgroundColor: '#10b981', borderRadius: 4, maxBarThickness: 16 },
                        { label: 'Murid', data: kecamatanData.map(d => d.murid), backgroundColor: '#f59e0b', borderRadius: 4, maxBarThickness: 16 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 16 },
                        tooltip: {
                            backgroundColor: '#0f172a', padding: 12, cornerRadius: 10,
                            callbacks: { label: c => ` ${c.dataset.label}: ${c.parsed.y.toLocaleString('id-ID')}` }
                        }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { maxRotation: 90, minRotation: 45, font: { size: 9 } } },
                        y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.2)' }, ticks: { callback: v => v >= 1000 ? (v / 1000).toLocaleString('id-ID') + ' rb' : v } }
                    }
                }
            });
        }
    });
</script>
@endpush