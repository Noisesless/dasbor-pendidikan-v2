@extends('layouts.app')

@section('content')
@php
    $fmt = function ($v) {
        if ($v === null) {
            return null;
        }
        $s = number_format((float) $v, 2, ',', '.');
        return str_ends_with($s, ',00') ? substr($s, 0, -3) : $s;
    };

    $trendYears = collect($flatTrend)->pluck('year')->all();
    $trendValues = collect($flatTrend)->pluck('value')->all();
    $lastTrend = $flatTrend ? end($flatTrend) : null;
    $prevTrend = count($flatTrend) >= 2 ? $flatTrend[count($flatTrend) - 2] : null;
    $latestYear = $lastTrend['year'] ?? null;
    $deltaTrend = ($lastTrend && $prevTrend && $prevTrend['value'] > 0)
        ? round(($lastTrend['value'] - $prevTrend['value']) / $prevTrend['value'] * 100, 1)
        : null;
@endphp

<div class="mb-5 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ $title }}</h1>
        <p class="text-sm text-slate-500 mt-1">Sumber data pendidikan Kabupaten Banjarnegara tahun {{ $years[0] }}&ndash;{{ end($years) }}</p>
    </div>
    <span class="inline-flex items-center gap-2 bg-blue-50 text-blue-700 text-sm font-semibold px-3.5 py-1.5 rounded-full border border-blue-100 shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
        {{ number_format($totalVariabel) }} variabel
    </span>
</div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-500">Total Variabel</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($totalVariabel) }}</p>
                    <p class="text-sm text-slate-500 mt-1">Indikator tersimpan</p>
                </div>
                <span class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"/></svg>
                </span>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-500">Titik Data Terisi</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($totalDataPoints) }}</p>
                    <p class="text-sm text-slate-500 mt-1">Nilah valid lintas {{ count($years) }} tahun</p>
                </div>
                <span class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>
                </span>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-500">Total Nilai {{ $latestYear }}</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ $lastTrend ? $fmt($lastTrend['value']) : '0' }}</p>
                    <p class="text-sm text-slate-500 mt-1">Agregasi seluruh variabel</p>
                </div>
                @if ($deltaTrend !== null)
                    <span class="shrink-0 inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold {{ $deltaTrend >= 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-red-50 text-red-700 border border-red-100' }}">
                        <svg class="w-3.5 h-3.5 {{ $deltaTrend >= 0 ? '' : 'rotate-180' }}" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75 12 9l7.5 6.75"/></svg>
                        {{ abs($deltaTrend) }}%
                    </span>
                @endif
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-500">Cakupan Kategori</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ count($pieData) }}</p>
                    <p class="text-sm text-slate-500 mt-1">Kelompok indikator utama</p>
                </div>
                <span class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6Z"/></svg>
                </span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-5 mb-6">
        <div class="xl:col-span-3 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
            <h3 class="font-bold text-slate-900 mb-1">Tren Agregasi Nilai Indikator</h3>
            <p class="text-xs text-slate-500 mb-4">Total nilai seluruh variabel per tahun</p>
            <div class="h-72"><canvas id="chartTren"></canvas></div>
        </div>
        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
            <h3 class="font-bold text-slate-900 mb-1">Distribusi Kategori</h3>
            <p class="text-xs text-slate-500 mb-4">Jumlah variabel per kelompok</p>
            <div class="h-72"><canvas id="chartKategori"></canvas></div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 mb-6">
        <h3 class="font-bold text-slate-900 mb-4">Statistik per Kategori</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach ($categoryStats as $stat)
                @php
                    $delta = null;
                    if ($stat['latest'] && $stat['prev'] && $stat['prev']['value'] > 0) {
                        $delta = round(($stat['latest']['value'] - $stat['prev']['value']) / $stat['prev']['value'] * 100, 1);
                    }
                @endphp
                <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full" style="background: {{ $stat['color'] }}"></span>
                        <p class="text-sm font-bold text-slate-800">{{ $stat['name'] }}</p>
                    </div>
                    <p class="text-2xl font-extrabold text-slate-900 mt-3 tracking-tight">{{ $stat['latest'] ? $fmt($stat['latest']['value']) : '0' }}</p>
                    <p class="text-xs text-slate-500 mt-1">{{ $stat['count'] }} variabel &middot; nilai {{ $stat['latest'] ? $stat['latest']['year'] : '-' }}</p>
                    @if ($delta !== null)
                        <span class="inline-block mt-2 px-2 py-0.5 rounded-md text-xs font-bold {{ $delta >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                            {{ $delta >= 0 ? '+' : '' }}{{ $delta }}% YoY
                        </span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 mb-6">
        <h3 class="font-bold text-slate-900 mb-1">Tren Indikator Kunci</h3>
        <p class="text-xs text-slate-500 mb-4">Agregasi nilai variabel yang memuat kata kunci tertentu</p>
        <div class="h-80"><canvas id="chartIndikator"></canvas></div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div>
                <h3 class="font-bold text-slate-900">Tabel Variabel</h3>
                <p class="text-xs text-slate-500 mt-0.5">{{ number_format(count($tableRows)) }} baris ditampilkan (maks. 200)</p>
            </div>
            <form method="GET" action="{{ route('walidata') }}" class="flex gap-2">
                <input
                    type="text"
                    name="q"
                    value="{{ $q }}"
                    placeholder="Cari kode atau uraian..."
                    class="w-full sm:w-64 border border-slate-300 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/40 focus:border-blue-400 transition"
                >
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition-colors shrink-0">Cari</button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3 pr-4">Kode</th>
                        <th class="py-3 px-4">Uraian</th>
                        <th class="py-3 px-4">Satuan</th>
                        @foreach ($recentYears as $y)
                            <th class="py-3 px-4 text-right">{{ $y }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($tableRows as $row)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3 pr-4 font-mono text-xs text-slate-500 whitespace-nowrap">{{ $row['kode'] }}</td>
                            <td class="py-3 px-4 font-semibold text-slate-800">{{ $row['uraian'] }}</td>
                            <td class="py-3 px-4 text-slate-500 whitespace-nowrap">{{ $row['satuan'] }}</td>
                            @foreach ($recentYears as $y)
                                @php
                                    $v = $row['recent'][$y] ?? null;
                                    $isMax = $v !== null && $row['recent_max'] > 0 && $v === $row['recent_max'];
                                @endphp
                                <td class="py-3 px-4 text-right tabular-nums {{ $isMax ? 'font-bold text-blue-700' : ($v !== null ? 'text-slate-700' : 'text-slate-300') }}">
                                    {{ $v !== null ? $fmt($v) : '&mdash;' }}
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ 3 + count($recentYears) }}" class="py-6 text-center text-slate-400">Tidak ada variabel yang cocok dengan pencarian "{{ $q }}".</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
        Chart.defaults.font.size = 12;
        Chart.defaults.color = '#64748b';

        const palette = ['#2563eb', '#10b981', '#f59e0b', '#94a3b8', '#8b5cf6', '#ef4444', '#06b6d4'];

        const trendCtx = document.getElementById('chartTren');
        if (trendCtx) {
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: @json($trendYears),
                    datasets: [{
                        label: 'Total Nilai',
                        data: @json($trendValues),
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.08)',
                        pointBackgroundColor: '#2563eb',
                        pointRadius: 3,
                        borderWidth: 2.5,
                        tension: 0.35,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { grid: { color: 'rgba(148, 163, 184, 0.2)' }, ticks: { callback: function (v) { return v >= 1000 ? (v / 1000).toLocaleString('id-ID') + ' rb' : v; } } },
                        x: { grid: { display: false } }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: { backgroundColor: '#0f172a', padding: 12, cornerRadius: 10 }
                    }
                }
            });
        }

        const kategoriCtx = document.getElementById('chartKategori');
        if (kategoriCtx) {
            const pieData = @json($pieData);
            new Chart(kategoriCtx, {
                type: 'doughnut',
                data: {
                    labels: pieData.map(function (k) { return k.name; }),
                    datasets: [{ data: pieData.map(function (k) { return k.value; }), backgroundColor: pieData.map(function (k) { return k.color; }), borderWidth: 2, borderColor: '#ffffff' }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: { position: 'bottom', usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 16 },
                        tooltip: {
                            backgroundColor: '#0f172a', padding: 12, cornerRadius: 10,
                            callbacks: {
                                label: function (ctx) {
                                    const total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                    const pct = total > 0 ? (ctx.parsed / total * 100).toFixed(1) : 0;
                                    return ' ' + ctx.label + ': ' + Number(ctx.parsed).toLocaleString('id-ID') + ' variabel (' + pct + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        const indikatorCtx = document.getElementById('chartIndikator');
        if (indikatorCtx) {
            const indicatorTrends = @json($indicatorTrends);
            new Chart(indikatorCtx, {
                type: 'line',
                data: {
                    labels: @json($years),
                    datasets: indicatorTrends.map(function (s, i) {
                        return {
                            label: s.label,
                            data: s.data,
                            borderColor: palette[i % palette.length],
                            backgroundColor: palette[i % palette.length],
                            pointRadius: 2,
                            borderWidth: 2,
                            tension: 0.35
                        };
                    })
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: { grid: { color: 'rgba(148, 163, 184, 0.2)' }, ticks: { callback: function (v) { return v >= 1000 ? (v / 1000).toLocaleString('id-ID') + ' rb' : v; } } },
                        x: { grid: { display: false } }
                    },
                    plugins: {
                        legend: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 16 },
                        tooltip: { backgroundColor: '#0f172a', padding: 12, cornerRadius: 10 }
                    }
                }
            });
        }
    });
</script>
@endpush
