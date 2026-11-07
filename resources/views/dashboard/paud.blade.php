@extends('layouts.app')

@section('content')
@php
    $persenA = $ak['total_sekolah'] > 0 ? round($ak['total_a'] / $ak['total_sekolah'] * 100, 1) : 0;
    $persenB = $ak['total_sekolah'] > 0 ? round($ak['total_b'] / $ak['total_sekolah'] * 100, 1) : 0;
    $persenC = $ak['total_sekolah'] > 0 ? round($ak['total_c'] / $ak['total_sekolah'] * 100, 1) : 0;
    $persenBelum = $ak['total_sekolah'] > 0 ? round($ak['total_belum'] / $ak['total_sekolah'] * 100, 1) : 0;

    $kartu = [
        ['Akreditasi A', $ak['total_a'], $persenA, '#10b981', 'PAUD terakreditasi A'],
        ['Akreditasi B', $ak['total_b'], $persenB, '#2563eb', 'PAUD terakreditasi B'],
        ['Akreditasi C', $ak['total_c'], $persenC, '#f59e0b', 'PAUD terakreditasi C'],
        ['Belum Terakreditasi', $ak['total_belum'], $persenBelum, '#ef4444', 'Belum mengantongi akreditasi'],
    ];

    $perKecamatan = collect($ak['per_kecamatan'] ?? [])->map(function ($r) {
        return [
            'nama' => $r['kecamatan'],
            'a' => (int) $r['a'],
            'b' => (int) $r['b'],
            'c' => (int) $r['c'],
            'belum' => (int) $r['belum'],
            'total' => (int) $r['total'],
            'persen_a' => (float) $r['persen_a'],
        ];
    })->values()->all();

    $terakreditasi = $ak['total_a'] + $ak['total_b'] + $ak['total_c'];
    $persenTerakreditasi = $ak['total_sekolah'] > 0 ? round($terakreditasi / $ak['total_sekolah'] * 100, 1) : 0;
@endphp

<div class="mb-5 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">{{ $title }}</h1>
        <p class="text-sm text-slate-500 mt-1">Peta sebaran dan mutu akreditasi PAUD se-Kabupaten Banjarnegara</p>
    </div>
    <span class="inline-flex items-center gap-2 bg-blue-50 text-blue-700 text-sm font-semibold px-3.5 py-1.5 rounded-full border border-blue-100 shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253m0 0A2.25 2.25 0 1 0 12 1.75a2.25 2.25 0 0 0 0 4.503ZM4.5 21.75h15m-15 0a2.25 2.25 0 0 1-2.25-2.25v-6.752m17.25 0v6.752A2.25 2.25 0 0 1 19.5 21.75"/></svg>
        Total {{ number_format($ak['total_sekolah']) }} PAUD
    </span>
</div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5 mb-6">
        @foreach ($kartu as [$label, $nilai, $persen, $warna, $sub])
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-500">{{ $label }}</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($nilai) }}</p>
                    <p class="text-sm text-slate-500 mt-1">{{ $persen }}% dari total PAUD</p>
                </div>
                <span class="w-2.5 h-2.5 rounded-full mt-2 shrink-0" style="background: {{ $warna }}"></span>
            </div>
            <div class="mt-4 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all" style="width: {{ $persen }}%; background: {{ $warna }}"></div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-5 mb-6">
        <div class="xl:col-span-3 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
            <h3 class="font-bold text-slate-900 mb-4">Akreditasi PAUD per Kecamatan</h3>
            <div class="h-96"><canvas id="chartKecamatan"></canvas></div>
        </div>
        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
            <h3 class="font-bold text-slate-900 mb-1">Komposisi Akreditasi</h3>
            <p class="text-xs text-slate-500 mb-4">{{ $persenTerakreditasi }}% PAUD sudah terakreditasi</p>
            <div class="h-72"><canvas id="chartKomposisi"></canvas></div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 mb-6">
        <h3 class="font-bold text-slate-900 mb-4">Rincian per Kecamatan</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3 pr-4">Kecamatan</th>
                        <th class="py-3 px-4 text-center">Akreditasi A</th>
                        <th class="py-3 px-4 text-center">Akreditasi B</th>
                        <th class="py-3 px-4 text-center">Akreditasi C</th>
                        <th class="py-3 px-4 text-center">Belum</th>
                        <th class="py-3 px-4 text-center">Total</th>
                        <th class="py-3 pl-4 text-right">Persen A</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($perKecamatan as $row)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 pr-4 font-semibold text-slate-800">{{ $row['nama'] }}</td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">{{ $row['a'] }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100">{{ $row['b'] }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-700 border border-amber-100">{{ $row['c'] }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-50 text-slate-600 border border-slate-200">{{ $row['belum'] }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-center font-semibold text-slate-900">{{ $row['total'] }}</td>
                            <td class="py-3.5 pl-4 text-right w-40">
                                <div class="flex items-center justify-end gap-2">
                                    <span class="text-xs font-bold text-slate-700">{{ number_format($row['persen_a'], 1) }}%</span>
                                    <div class="h-1.5 w-16 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ min(100, $row['persen_a']) }}%"></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-slate-400">Belum ada data</td></tr>
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

        const kecamatanCtx = document.getElementById('chartKecamatan');
        if (kecamatanCtx) {
            const data = @json($perKecamatan);
            new Chart(kecamatanCtx, {
                type: 'bar',
                data: {
                    labels: data.map(function (r) { return r.nama; }),
                    datasets: [
                        { label: 'Akreditasi A', data: data.map(function (r) { return r.a; }), backgroundColor: '#10b981', borderRadius: 4, maxBarThickness: 14 },
                        { label: 'Akreditasi B', data: data.map(function (r) { return r.b; }), backgroundColor: '#2563eb', borderRadius: 4, maxBarThickness: 14 },
                        { label: 'Akreditasi C', data: data.map(function (r) { return r.c; }), backgroundColor: '#f59e0b', borderRadius: 4, maxBarThickness: 14 },
                        { label: 'Belum Terakreditasi', data: data.map(function (r) { return r.belum; }), backgroundColor: '#94a3b8', borderRadius: 4, maxBarThickness: 14 }
                    ]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: true, grid: { color: 'rgba(148, 163, 184, 0.2)' }, ticks: { precision: 0 } },
                        y: { stacked: true, grid: { display: false } }
                    },
                    plugins: {
                        legend: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 16 },
                        tooltip: { backgroundColor: '#0f172a', padding: 12, cornerRadius: 10 }
                    }
                }
            });
        }

        const komposisiCtx = document.getElementById('chartKomposisi');
        if (komposisiCtx) {
            const komposisi = @json($komposisi);
            new Chart(komposisiCtx, {
                type: 'doughnut',
                data: {
                    labels: komposisi.map(function (k) { return k.name; }),
                    datasets: [{ data: komposisi.map(function (k) { return k.value; }), backgroundColor: komposisi.map(function (k) { return k.color; }), borderWidth: 2, borderColor: '#ffffff' }]
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
                                    return ' ' + ctx.label + ': ' + Number(ctx.parsed).toLocaleString('id-ID') + ' PAUD (' + pct + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
