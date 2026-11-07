@extends('layouts.app')

@section('content')
    @php
        $sekolahDianalisis = $patuh['sesuai'] + $patuh['melebihi'];
        $persenSesuai = $sekolahDianalisis > 0 ? round($patuh['sesuai'] / $sekolahDianalisis * 100, 1) : 0;
        $persenMelebihi = $sekolahDianalisis > 0 ? round($patuh['melebihi'] / $sekolahDianalisis * 100, 1) : 0;
        $totSiswaRombel = array_sum(array_column($rombelPerJenjang, 'siswa'));
        $totRombel = array_sum(array_column($rombelPerJenjang, 'rombel'));
        $rataRombel = $totRombel > 0 ? round($totSiswaRombel / $totRombel, 1) : 0;
        $funnelMax = 0;
        foreach ($funnel as $f) { $funnelMax = max($funnelMax, $f['siswa']); }
    @endphp

    {{-- Page Title & Period --}}
    <div class="mb-5 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Infrastruktur &amp; Efisiensi</h2>
            <p class="text-sm text-slate-500 mt-1 font-medium">
                Kepatuhan rombel terhadap standar Permendikbud &middot;
                <span class="text-blue-600 font-semibold">{{ App\Support\Filters::semesterLabel($filters['semester']) }}</span>
            </p>
        </div>
    </div>

    @include('dashboard.partials.filters')

    {{-- KPI Kepatuhan Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5 mb-6">
        {{-- Sekolah Dianalisis --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Sekolah Dianalisis</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($sekolahDianalisis, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-500 font-medium mt-1">Sekolah dengan data rombel</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14m6-2h2m-2 0v-2m8 2v6m-8-6v6m8-6a2 2 0 002 2m-10-2a2 2 0 01-2 2m0 0v6m10-6v6"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Sesuai Standar --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Sesuai Standar</p>
                    <p class="text-3xl font-extrabold text-emerald-600 mt-2 tracking-tight">{{ number_format($patuh['sesuai'], 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-500 font-medium mt-1">
                        <span class="font-bold text-emerald-600">{{ number_format($persenSesuai, 1, ',', '.') }}%</span> dari sekolah dianalisis
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Melebihi Standar --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Melebihi Standar</p>
                    <p class="text-3xl font-extrabold text-rose-600 mt-2 tracking-tight">{{ number_format($patuh['melebihi'], 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-500 font-medium mt-1">
                        <span class="font-bold text-rose-600">{{ number_format($persenMelebihi, 1, ',', '.') }}%</span> dari sekolah dianalisis
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 border border-rose-100">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M4.93 19h14.14a2 2 0 001.73-3.01L13.72 6.99a2 2 0 00-3.44 0L4.93 15.99A2 2 0 006.66 19z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Rata-rata Rombel --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Rata-rata Isi Rombel</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($rataRombel, 1, ',', '.') }}<span class="text-lg text-slate-400"> siswa</span></p>
                    <p class="text-xs text-slate-500 font-medium mt-1">
                        <span class="font-bold text-slate-700">{{ number_format($totRombel, 0, ',', '.') }}</span> rombel &middot;
                        <span class="font-bold text-slate-700">{{ number_format($totSiswaRombel, 0, ',', '.') }}</span> siswa
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656-.126-1.283-.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel Kepatuhan + Funnel --}}
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4 sm:gap-6 mb-6">
        {{-- Tabel Kepatuhan Rombel --}}
        <div class="xl:col-span-3 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
            <h3 class="text-sm font-extrabold text-slate-900">Kepatuhan Rombling per Jenjang</h3>
            <p class="text-xs text-slate-500 font-medium mt-0.5 mb-4">
                Rata-rata siswa per rombel dibanding standar Permendikbud No. 22/2016
            </p>
            <div class="overflow-x-auto -mx-2 px-2">
                <table class="w-full text-sm min-w-[640px]">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-200">
                            <th class="py-2.5 pr-3 font-bold">Jenjang</th>
                            <th class="py-2.5 px-3 text-right font-bold">Sekolah</th>
                            <th class="py-2.5 px-3 text-right font-bold">Siswa</th>
                            <th class="py-2.5 px-3 text-right font-bold">Rombel</th>
                            <th class="py-2.5 px-3 font-bold" style="min-width:180px">Siswa/Rombel</th>
                            <th class="py-2.5 px-3 text-right font-bold">Standar</th>
                            <th class="py-2.5 pl-3 text-right font-bold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rombelPerJenjang as $row)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-3 pr-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-xs font-bold border border-blue-100">{{ $row['jenjang'] }}</span>
                                </td>
                                <td class="py-3 px-3 text-right font-semibold text-slate-700">{{ number_format($row['sekolah'], 0, ',', '.') }}</td>
                                <td class="py-3 px-3 text-right font-bold text-slate-900">{{ number_format($row['siswa'], 0, ',', '.') }}</td>
                                <td class="py-3 px-3 text-right font-semibold text-slate-700">{{ number_format($row['rombel'], 0, ',', '.') }}</td>
                                <td class="py-3 px-3">
                                    <div class="flex items-center gap-2">
                                        <div class="h-2 flex-1 rounded-full bg-slate-100 overflow-hidden">
                                            <div class="h-full rounded-full {{ $row['status'] === 'melebihi' ? 'bg-rose-500' : 'bg-emerald-500' }}"
                                                 style="width: {{ $row['standar'] > 0 ? min(100, round($row['rata_rata'] / $row['standar'] * 100)) : 0 }}%"></div>
                                        </div>
                                        <span class="font-bold text-xs w-10 text-right {{ $row['status'] === 'melebihi' ? 'text-rose-600' : 'text-slate-900' }}">
                                            {{ number_format($row['rata_rata'], 1, ',', '.') }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-right font-semibold text-slate-500">{{ $row['standar'] > 0 ? $row['standar'] . ' siswa' : '&mdash;' }}</td>
                                <td class="py-3 pl-3 text-right">
                                    @if ($row['status'] === 'sesuai')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-[11px] font-bold border border-emerald-100">Sesuai</span>
                                    @elseif ($row['status'] === 'melebihi')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 text-[11px] font-bold border border-rose-100">Melebihi</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-50 text-slate-400 text-[11px] font-bold border border-slate-100">N/A</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-sm text-slate-400 font-medium">Tidak ada data untuk filter yang dipilih.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="text-[11px] text-slate-400 font-medium mt-3 leading-relaxed">
                Standar Permendikbud No. 22/2016: SD/MI maks 32, SMP/MTS/SMA/SMK/MA maks 36, PAUD/TK maks 20 siswa per rombel.
            </p>
        </div>

        {{-- Funnel Transisi --}}
        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
            <h3 class="text-sm font-extrabold text-slate-900">Transisi Siswa Antar Jenjang</h3>
            <p class="text-xs text-slate-500 font-medium mt-0.5 mb-5">Jumlah siswa pada setiap jenjang pendidikan</p>
            <div class="space-y-3.5">
                @php
                    $funnelColors = ['PAUD' => '#38bdf8', 'TK' => '#0ea5e9', 'SD' => '#2563eb', 'SMP' => '#4f46e5', 'SMA' => '#7c3aed', 'SMK' => '#8b5cf6'];
                @endphp
                @foreach ($funnel as $f)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-slate-700">{{ $f['jenjang'] }}</span>
                            <span class="text-xs font-bold text-slate-900">{{ number_format($f['siswa'], 0, ',', '.') }} siswa</span>
                        </div>
                        <div class="h-6 rounded-lg bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-lg flex items-center justify-end pr-2 transition-all duration-500"
                                 style="width: {{ $funnelMax > 0 ? max(2, round($f['siswa'] / $funnelMax * 100)) : 0 }}%; background: linear-gradient(90deg, {{ $funnelColors[$f['jenjang']] ?? '#94a3b8' }}, {{ $funnelColors[$f['jenjang']] ?? '#94a3b8' }}cc);">
                                @if ($f['siswa'] > 0 && ($f['siswa'] / max($funnelMax, 1)) >= 0.25)
                                    <span class="text-[10px] font-extrabold text-white">{{ $funnelMax > 0 ? number_format($f['siswa'] / $funnelMax * 100, 0, ',', '.') : 0 }}%</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="text-[11px] text-slate-400 font-medium mt-4 leading-relaxed">
                Persentase relatif terhadap jenjang SD. Menunjukkan retensi siswa saat berpindah jenjang pendidikan.
            </p>
        </div>
    </div>

    {{-- Akreditasi + Status Sekolah --}}
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4 sm:gap-6 mb-6">
        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
            <h3 class="text-sm font-extrabold text-slate-900">Distribusi Akreditasi</h3>
            <p class="text-xs text-slate-500 font-medium mt-0.5 mb-4">Status akreditasi sekolah</p>
            <div class="h-64 sm:h-72 flex items-center justify-center relative">
                <canvas id="akreditasiChartInfra"></canvas>
            </div>
            <div id="akreditasiLegendInfra" class="grid grid-cols-2 gap-x-4 gap-y-2 mt-4 text-xs font-semibold"></div>
        </div>

        <div class="xl:col-span-3 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Sekolah Negeri vs Swasta</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Komposisi status sekolah per jenjang</p>
                </div>
                <div class="flex items-center gap-4 text-xs font-semibold">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-blue-600"></span> Negeri</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-amber-500"></span> Swasta</span>
                </div>
            </div>
            <div class="h-[380px]">
                <canvas id="statusChart"></canvas>
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

        // ---- Akreditasi Donut ----
        const akredCtx = document.getElementById('akreditasiChartInfra');
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
                                    return ` ${ctx.label}: ${ctx.parsed.toLocaleString('id-ID')} sekolah (${pct}%)`;
                                },
                            },
                        },
                    },
                },
            });

            const legendEl = document.getElementById('akreditasiLegendInfra');
            const total = entries.reduce((a, [, v]) => a + v, 0);
            legendEl.innerHTML = entries.map((e, i) => `
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:${palette[i % palette.length]}"></span>
                    <span class="text-slate-600 truncate">${labelOf(e[0])}</span>
                    <span class="ml-auto text-slate-900 font-bold">${total > 0 ? (e[1] / total * 100).toFixed(0) : 0}%</span>
                </div>
            `).join('');
        }

        // ---- Status Negeri vs Swasta (stacked) ----
        const statusCtx = document.getElementById('statusChart');
        if (statusCtx) {
            const status = @json($statusChart);
            new Chart(statusCtx, {
                type: 'bar',
                data: {
                    labels: status.map(s => s.jenjang),
                    datasets: [
                        { label: 'Negeri', data: status.map(s => s.negeri), backgroundColor: '#2563eb', borderRadius: 4, maxBarThickness: 26 },
                        { label: 'Swasta', data: status.map(s => s.swasta), backgroundColor: '#f59e0b', borderRadius: 4, maxBarThickness: 26 },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 12,
                            cornerRadius: 10,
                            titleFont: { weight: 'bold' },
                            callbacks: {
                                label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y.toLocaleString('id-ID')} sekolah`,
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
    });
</script>
@endpush

