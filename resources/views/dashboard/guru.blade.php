@extends('layouts.app')

@section('content')
    @php
        $persenSert = $totalGuru > 0 ? round($totalSertifikasi / $totalGuru * 100, 1) : 0;
        $belumSert = max(0, $totalGuru - $totalSertifikasi);
        $guruAsgn = collect($komposisiJenjang)->sum('pns');
        $guruPppk = collect($komposisiJenjang)->sum('pppk');
        $guruAsn = $guruAsgn + $guruPppk;
        $genderL = $gender['L'] ?? 0;
        $genderP = $gender['P'] ?? 0;
        $totalGender = $genderL + $genderP;
    @endphp

    {{-- Page Title & Period --}}
    <div class="mb-5 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Guru &amp; Tenaga Kependidikan</h2>
            <p class="text-sm text-slate-500 mt-1 font-medium">
                Profil, sertifikasi, dan rasio guru &middot;
                <span class="text-blue-600 font-semibold">{{ App\Support\Filters::semesterLabel($filters['semester']) }}</span>
            </p>
        </div>
    </div>

    @include('dashboard.partials.filters')

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5 mb-6">
        {{-- Total Guru --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Guru</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($totalGuru, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-500 font-medium mt-1">Guru aktif pada semester ini</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zM12 14l9-5M12 14v7m0-7l-9 5m9-5v7"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Tersertifikasi --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Tersertifikasi</p>
                    <p class="text-3xl font-extrabold text-emerald-600 mt-2 tracking-tight">{{ number_format($totalSertifikasi, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-500 font-medium mt-1"><span class="font-bold text-emerald-600">{{ number_format($persenSert, 1, ',', '.') }}%</span> dari total guru</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7 8h10M7 16h10M4 4h16v16H4z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Guru ASN --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Guru ASN</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($guruAsn, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-500 font-medium mt-1">
                        <span class="font-bold text-slate-700">{{ number_format($guruPppk, 0, ',', '.') }}</span> PNS &middot;
                        <span class="font-bold text-slate-700">{{ number_format($guruAsgn, 0, ',', '.') }}</span> PPPK
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center shrink-0 border border-violet-100">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Belum Sertifikasi --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Belum Sertifikasi</p>
                    <p class="text-3xl font-extrabold text-rose-600 mt-2 tracking-tight">{{ number_format($belumSert, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-500 font-medium mt-1">Guru belum tersertifikasi</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 border border-rose-100">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Komposisi per Jenjang + Gender --}}
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4 sm:gap-6 mb-6">
        <div class="xl:col-span-3 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Komposisi Guru per Jenjang</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Status kepegawaian guru per jenjang pendidikan</p>
                </div>
            </div>
            <div class="h-[380px]">
                <canvas id="komposisiChart"></canvas>
            </div>
        </div>

        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
            <h3 class="text-sm font-extrabold text-slate-900">Komposisi Gender</h3>
            <p class="text-xs text-slate-500 font-medium mt-0.5 mb-4">Sebaran jenis kelamin guru</p>
            <div class="h-64 sm:h-72 flex items-center justify-center relative">
                <canvas id="genderChart"></canvas>
            </div>
            <div id="genderLegend" class="grid grid-cols-2 gap-x-4 gap-y-2 mt-4 text-xs font-semibold"></div>
        </div>
    </div>

    {{-- Tren + Sertifikasi --}}
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4 sm:gap-6 mb-6">
        <div class="xl:col-span-3 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Tren Jumlah Guru</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Perkembangan status kepegawaian antar semester</p>
                </div>
            </div>
            <div class="h-[360px]">
                <canvas id="trendGuruChart"></canvas>
            </div>
        </div>

        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Sertifikasi per Status</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Kepemilikan sertifikat pendidik</p>
                </div>
            </div>
            <div class="h-[360px]">
                <canvas id="sertifikasiChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Rasio + Bidang Studi --}}
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4 sm:gap-6 mb-6">
        <div class="xl:col-span-3 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Rasio Siswa per Guru</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Indikasi krisis atau kelebihan guru per kecamatan</p>
                </div>
            </div>
            <div class="flex items-center gap-4 text-xs font-semibold mb-3">
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-emerald-500"></span> &le; 10 (ideal)</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-amber-500"></span> 10&ndash;15</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-rose-500"></span> &gt; 15</span>
            </div>
            <div class="h-[520px]">
                <canvas id="rasioChart"></canvas>
            </div>
        </div>

        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
            <h3 class="text-sm font-extrabold text-slate-900">10 Bidang Studi Teratas</h3>
            <p class="text-xs text-slate-500 font-medium mt-0.5 mb-4">Berdasarkan jumlah guru</p>
            <div class="h-[520px]">
                <canvas id="bidangStudiChart"></canvas>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const fontStack = "'Plus Jakarta Sans', sans-serif";

        Chart.defaults.font.family = fontStack;
        Chart.defaults.font.size = 12;
        Chart.defaults.color = '#64748b';

        // ---- Komposisi Status Kepegawaian per Jenjang (stacked) ----
        const komposisiCtx = document.getElementById('komposisiChart');
        if (komposisiCtx) {
            const komposisi = @json($komposisiJenjang);
            const statusColors = { pns: '#2563eb', pppk: '#8b5cf6', gty: '#10b981', gtt: '#f59e0b', honor: '#ef4444' };
            new Chart(komposisiCtx, {
                type: 'bar',
                data: {
                    labels: komposisi.map(k => k.jenjang),
                    datasets: [
                        { label: 'PNS', data: komposisi.map(k => k.pns), backgroundColor: statusColors.pns, borderRadius: 4, maxBarThickness: 26 },
                        { label: 'PPPK', data: komposisi.map(k => k.pppk), backgroundColor: statusColors.pppk, borderRadius: 4, maxBarThickness: 26 },
                        { label: 'GTY', data: komposisi.map(k => k.gty), backgroundColor: statusColors.gty, borderRadius: 4, maxBarThickness: 26 },
                        { label: 'GTT', data: komposisi.map(k => k.gtt), backgroundColor: statusColors.gtt, borderRadius: 4, maxBarThickness: 26 },
                        { label: 'Honor', data: komposisi.map(k => k.honor), backgroundColor: statusColors.honor, borderRadius: 4, maxBarThickness: 26 },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 16, font: { family: fontStack, size: 11 } },
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 12,
                            cornerRadius: 10,
                            titleFont: { weight: 'bold' },
                            callbacks: {
                                label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y.toLocaleString('id-ID')} guru`,
                            },
                        },
                    },
                    scales: {
                        x: { stacked: true, grid: { display: false } },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.2)' },
                            ticks: {
                                precision: 0,
                                callback: v => v >= 1000 ? (v / 1000).toLocaleString('id-ID') + ' rb' : v,
                            },
                        },
                    },
                },
            });
        }

        // ---- Gender Donut ----
        const genderCtx = document.getElementById('genderChart');
        if (genderCtx) {
            const gender = @json($gender);
            const l = gender['L'] ?? 0;
            const p = gender['P'] ?? 0;
            const entries = [];
            if (l > 0) entries.push(['Laki-laki', l, '#2563eb']);
            if (p > 0) entries.push(['Perempuan', p, '#ec4899']);

            new Chart(genderCtx, {
                type: 'doughnut',
                data: {
                    labels: entries.map(e => e[0]),
                    datasets: [{
                        data: entries.map(e => e[1]),
                        backgroundColor: entries.map(e => e[2]),
                        borderWidth: 3,
                        borderColor: '#fff',
                        hoverOffset: 8,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '68%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 12,
                            cornerRadius: 10,
                            callbacks: {
                                label: ctx => {
                                    const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                    const pct = total > 0 ? (ctx.parsed / total * 100).toFixed(1) : 0;
                                    return ` ${ctx.label}: ${ctx.parsed.toLocaleString('id-ID')} guru (${pct}%)`;
                                },
                            },
                        },
                    },
                },
            });

            const legendEl = document.getElementById('genderLegend');
            const totalG = entries.reduce((a, e) => a + e[1], 0);
            legendEl.innerHTML = entries.map(e => `
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:${e[2]}"></span>
                    <span class="text-slate-600 truncate">${e[0]}</span>
                    <span class="ml-auto text-slate-900 font-bold">${totalG > 0 ? (e[1] / totalG * 100).toFixed(0) : 0}%</span>
                </div>
            `).join('');
        }

        // ---- Tren Guru per Semester (stacked) ----
        const trendCtx = document.getElementById('trendGuruChart');
        if (trendCtx) {
            const trend = @json($trendStatus);
            new Chart(trendCtx, {
                type: 'bar',
                data: {
                    labels: trend.map(t => t.label),
                    datasets: [
                        { label: 'PNS', data: trend.map(t => t.pns), backgroundColor: '#2563eb', borderRadius: 4, maxBarThickness: 40 },
                        { label: 'PPPK', data: trend.map(t => t.pppk), backgroundColor: '#8b5cf6', borderRadius: 4, maxBarThickness: 40 },
                        { label: 'GTY', data: trend.map(t => t.gty), backgroundColor: '#10b981', borderRadius: 4, maxBarThickness: 40 },
                        { label: 'GTT', data: trend.map(t => t.gtt), backgroundColor: '#f59e0b', borderRadius: 4, maxBarThickness: 40 },
                        { label: 'Honor', data: trend.map(t => t.honor), backgroundColor: '#ef4444', borderRadius: 4, maxBarThickness: 40 },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 16, font: { family: fontStack, size: 11 } },
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 12,
                            cornerRadius: 10,
                            titleFont: { weight: 'bold' },
                            callbacks: {
                                label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y.toLocaleString('id-ID')} guru`,
                            },
                        },
                    },
                    scales: {
                        x: { stacked: true, grid: { display: false } },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.2)' },
                            ticks: {
                                precision: 0,
                                callback: v => v >= 1000 ? (v / 1000).toLocaleString('id-ID') + ' rb' : v,
                            },
                        },
                    },
                },
            });
        }

        // ---- Sertifikasi per Status Kepegawaian (horizontal stacked) ----
        const sertCtx = document.getElementById('sertifikasiChart');
        if (sertCtx) {
            const sert = @json($sertifikasi);
            new Chart(sertCtx, {
                type: 'bar',
                data: {
                    labels: sert.map(s => s.status_kepegawaian),
                    datasets: [
                        { label: 'Sudah', data: sert.map(s => Number(s.sudah)), backgroundColor: '#10b981', borderRadius: 4, maxBarThickness: 22 },
                        { label: 'Belum', data: sert.map(s => Number(s.belum)), backgroundColor: '#ef4444', borderRadius: 4, maxBarThickness: 22 },
                    ],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 16, font: { family: fontStack, size: 11 } },
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 12,
                            cornerRadius: 10,
                            callbacks: {
                                label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.x.toLocaleString('id-ID')} guru`,
                            },
                        },
                    },
                    scales: {
                        x: {
                            stacked: true,
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.2)' },
                            ticks: {
                                precision: 0,
                                callback: v => v >= 1000 ? (v / 1000).toLocaleString('id-ID') + ' rb' : v,
                            },
                        },
                        y: { stacked: true, grid: { display: false } },
                    },
                },
            });
        }

        // ---- Rasio Siswa per Guru ----
        const rasioCtx = document.getElementById('rasioChart');
        if (rasioCtx) {
            const rasio = @json($rasioKecamatan);
            new Chart(rasioCtx, {
                type: 'bar',
                data: {
                    labels: rasio.map(r => r.kecamatan),
                    datasets: [{
                        data: rasio.map(r => r.rasio),
                        backgroundColor: rasio.map(r => r.rasio > 15 ? '#ef4444' : (r.rasio > 10 ? '#f59e0b' : '#10b981')),
                        borderRadius: 4,
                        maxBarThickness: 16,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 12,
                            cornerRadius: 10,
                            callbacks: {
                                label: ctx => {
                                    const d = rasio[ctx.dataIndex];
                                    return ` ${d.siswa.toLocaleString('id-ID')} siswa / ${d.guru.toLocaleString('id-ID')} guru`;
                                },
                            },
                        },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.2)' },
                            ticks: { precision: 0 },
                        },
                        y: { grid: { display: false } },
                    },
                },
            });
        }

        // ---- Top 10 Bidang Studi (horizontal) ----
        const bidCtx = document.getElementById('bidangStudiChart');
        if (bidCtx) {
            const bidang = @json($bidangStudi);
            const labels = Object.keys(bidang);
            const values = Object.values(bidang);
            new Chart(bidCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: '#8b5cf6',
                        borderRadius: 4,
                        maxBarThickness: 16,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 12,
                            cornerRadius: 10,
                            callbacks: {
                                label: ctx => ` ${ctx.parsed.x.toLocaleString('id-ID')} guru`,
                            },
                        },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.2)' },
                            ticks: { precision: 0 },
                        },
                        y: { grid: { display: false } },
                    },
                },
            });
        }
    });
</script>
@endpush
