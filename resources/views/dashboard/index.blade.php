@extends('layouts.app')

@section('content')
    {{-- Page Title & Period --}}
    <div class="mb-5 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Ringkasan Eksekutif</h2>
            <p class="text-sm text-slate-500 mt-1 font-medium">
                Gambaran umum pendidikan Kabupaten Banjarnegara &middot;
                <span class="text-blue-600 font-semibold">{{ App\Support\Filters::semesterLabel($filters['semester']) }}</span>
            </p>
        </div>
    </div>

    @include('dashboard.partials.filters')

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5 mb-6">
        {{-- Total Sekolah --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Sekolah</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($totalSekolah, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-500 font-medium mt-1">Satuan pendidikan aktif</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14m6-2h2m-2 0v-2m8 2v6m-8-6v6m8-6a2 2 0 002 2m-10-2a2 2 0 01-2 2m0 0v6m10-6v6"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Total Siswa --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Siswa</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($totalSiswa, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-500 font-medium mt-1">
                        <span class="text-sky-600 font-bold">{{ number_format($totalLaki, 0, ',', '.') }}</span> L &middot;
                        <span class="text-pink-600 font-bold">{{ number_format($totalPerempuan, 0, ',', '.') }}</span> P
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Total Guru --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Guru</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($totalGuru, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-500 font-medium mt-1">
                        <span class="text-emerald-600 font-bold">{{ number_format($totalSertifikasi, 0, ',', '.') }}</span> tersertifikasi
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center shrink-0 border border-violet-100">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.579A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.579L12 14z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Rombel & Rasio --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Rombongan Belajar</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($totalRombel, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-500 font-medium mt-1">
                        Rasio <span class="text-blue-600 font-bold">{{ $rasioSiswaGuru }}</span> siswa/guru
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Trend Chart --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 mb-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">Tren Siswa &amp; Guru per Semester</h3>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Perkembangan jumlah siswa dan guru</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-semibold">
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-600"></span> Siswa</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-500"></span> Guru</span>
            </div>
        </div>
        <div class="h-72 sm:h-80">
            <canvas id="trendChart"></canvas>
        </div>
    </div>

    {{-- Akreditasi + Per Jenjang --}}
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4 sm:gap-6 mb-6">
        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
            <h3 class="text-sm font-extrabold text-slate-900">Distribusi Akreditasi</h3>
            <p class="text-xs text-slate-500 font-medium mt-0.5 mb-4">Status akreditasi sekolah</p>
            <div class="h-64 sm:h-72 flex items-center justify-center relative">
                <canvas id="akreditasiChart"></canvas>
            </div>
            <div id="akreditasiLegend" class="grid grid-cols-2 gap-x-4 gap-y-2 mt-4 text-xs font-semibold"></div>
        </div>

        <div class="xl:col-span-3 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
            <h3 class="text-sm font-extrabold text-slate-900">Rincian per Jenjang</h3>
            <p class="text-xs text-slate-500 font-medium mt-0.5 mb-4">Jumlah sekolah, siswa, guru, dan rombel</p>
            <div class="overflow-x-auto -mx-2 px-2">
                <table class="w-full text-sm min-w-[520px]">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-200">
                            <th class="py-2.5 pr-3 font-bold">Jenjang</th>
                            <th class="py-2.5 px-3 text-right font-bold">Sekolah</th>
                            <th class="py-2.5 px-3 text-right font-bold">Siswa</th>
                            <th class="py-2.5 px-3 text-right font-bold">Guru</th>
                            <th class="py-2.5 pl-3 text-right font-bold">Rombel</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($perJenjangData as $row)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-2.5 pr-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-xs font-bold border border-blue-100">{{ $row['jenjang'] }}</span>
                                </td>
                                <td class="py-2.5 px-3 text-right font-semibold text-slate-700">{{ number_format($row['sekolah'], 0, ',', '.') }}</td>
                                <td class="py-2.5 px-3 text-right font-bold text-slate-900">{{ number_format($row['siswa'], 0, ',', '.') }}</td>
                                <td class="py-2.5 px-3 text-right font-semibold text-slate-700">{{ number_format($row['guru'], 0, ',', '.') }}</td>
                                <td class="py-2.5 pl-3 text-right font-semibold text-slate-700">{{ number_format($row['rombel'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Top Kecamatan --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
        <h3 class="text-sm font-extrabold text-slate-900">Sebaran Siswa per Kecamatan</h3>
        <p class="text-xs text-slate-500 font-medium mt-0.5 mb-5">Kecamatan dengan jumlah siswa terbanyak</p>
        <div class="h-[480px]">
            <canvas id="kecamatanChart"></canvas>
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

        // ---- Trend Chart ----
        const trendCtx = document.getElementById('trendChart');
        if (trendCtx) {
            const trend = @json($trend);
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: trend.map(t => t.label),
                    datasets: [
                        {
                            label: 'Siswa',
                            data: trend.map(t => t.siswa),
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.08)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 2.5,
                            pointBackgroundColor: '#2563eb',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                        },
                        {
                            label: 'Guru',
                            data: trend.map(t => t.guru),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.08)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 2.5,
                            pointBackgroundColor: '#10b981',
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 12,
                            cornerRadius: 10,
                            titleFont: { weight: 'bold' },
                            callbacks: {
                                label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y.toLocaleString('id-ID')}`,
                            },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.2)' },
                            ticks: {
                                callback: v => v >= 1000 ? (v / 1000).toLocaleString('id-ID') + ' rb' : v,
                            },
                        },
                        x: { grid: { display: false } },
                    },
                },
            });
        }

        // ---- Akreditasi Donut ----
        const akredCtx = document.getElementById('akreditasiChart');
        if (akredCtx) {
            const akreditasi = @json($akreditasi);
            const order = ['A', 'B', 'C', 'Belum Terakreditasi'];
            const labelOf = k => k.length === 1 ? `Akreditasi ${k}` : k;
            const entries = order
                .filter(k => (akreditasi[k] ?? 0) > 0)
                .map(k => [k, akreditasi[k]]);
            Object.entries(akreditasi).forEach(([k, v]) => {
                if (!order.includes(k)) entries.push([k, v]);
            });
            const palette = ['#2563eb', '#10b981', '#f59e0b', '#94a3b8', '#8b5cf6', '#ef4444'];
            const colors = entries.map((_, i) => palette[i % palette.length]);

            new Chart(akredCtx, {
                type: 'doughnut',
                data: {
                    labels: entries.map(e => labelOf(e[0])),
                    datasets: [{
                        data: entries.map(e => e[1]),
                        backgroundColor: colors,
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
                                    return ` ${ctx.label}: ${ctx.parsed.toLocaleString('id-ID')} (${pct}%)`;
                                },
                            },
                        },
                    },
                },
            });

            const legendEl = document.getElementById('akreditasiLegend');
            const total = entries.reduce((a, [, v]) => a + v, 0);
            legendEl.innerHTML = entries.map((e, i) => `
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:${palette[i % palette.length]}"></span>
                    <span class="text-slate-600 truncate">${labelOf(e[0])}</span>
                    <span class="ml-auto text-slate-900 font-bold">${total > 0 ? (e[1] / total * 100).toFixed(0) : 0}%</span>
                </div>
            `).join('');
        }

        // ---- Top Kecamatan Horizontal Bar ----
        const kecCtx = document.getElementById('kecamatanChart');
        if (kecCtx) {
            const kec = @json($kecamatanSiswa);
            new Chart(kecCtx, {
                type: 'bar',
                data: {
                    labels: kec.map(k => k.kecamatan),
                    datasets: [{
                        label: 'Siswa',
                        data: kec.map(k => k.total),
                        backgroundColor: kec.map((_, i) =>
                            i === 0 ? '#2563eb' : `rgba(37, 99, 235, ${Math.max(0.25, 1 - i * 0.05)})`),
                        borderRadius: 8,
                        borderSkipped: false,
                        maxBarThickness: 22,
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
                                label: ctx => ` ${ctx.parsed.x.toLocaleString('id-ID')} siswa`,
                            },
                        },
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.2)' },
                            ticks: {
                                callback: v => v >= 1000 ? (v / 1000).toLocaleString('id-ID') + ' rb' : v,
                            },
                        },
                        y: { grid: { display: false } },
                    },
                },
            });
        }
    });
</script>
@endpush
