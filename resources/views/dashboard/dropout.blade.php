@extends('layouts.app')

@section('content')
    @php
        $avgRasio = $summary['guru'] > 0 ? $summary['murid'] / $summary['guru'] : 0;
        $totalIdeal = 0;
        foreach ($byLevel as $lv) {
            $totalIdeal += $lv['ideal'];
        }
        $totalGap = $totalIdeal - $summary['guru'];
    @endphp

    <div class="mb-5 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Angka Keluar &amp; Kesenjangan</h2>
            <p class="text-sm text-slate-500 font-medium mt-1">Indikasi angka keluar sekolah dan rasio guru terhadap murid per jenjang.</p>
        </div>
        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider bg-slate-100 rounded-full px-3 py-1.5 w-fit">
            Tahun Ajaran Terbaru
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Murid</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($summary['murid'], 0, ',', '.') }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.14-.786-3.07M15 19.128v.008h-6c-1.105 0-2.1-.424-2.86-1.118M9 6.75a3 3 0 116 0m-9 8.25a3 3 0 100-6 3 3 0 000 6zm0 0v1c0 1.105.895 2 2 2h4.5"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Guru</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($summary['guru'], 0, ',', '.') }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347M7.5 17.25h.484a60.47 60.47 0 006.316-1.026M12 3a75.408 75.408 0 014.865 5.184M19.5 9.75c.365 1.31.631 2.651.795 4.015M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.14-.786-3.07M15 19.128v.008h-6c-1.105 0-2.1-.424-2.86-1.118M9 6.75a3 3 0 116 0m-9 8.25a3 3 0 100-6 3 3 0 000 6zm0 0v1c0 1.105.895 2 2 2h4.5"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Rasio Nasional Murid : Guru</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">1 : {{ number_format($avgRasio, 1, ',', '.') }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Indikasi Kekurangan Guru</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($totalGap, 0, ',', '.') }}</p>
                    <p class="text-[11px] font-semibold mt-1 {{ $totalGap > 0 ? 'text-red-600' : 'text-emerald-600' }}">berdasarkan rasio ideal</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center shrink-0 border border-red-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4 sm:gap-6 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 xl:col-span-3">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Rasio Murid : Guru per Jenjang</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Rasio aktual dibanding rasio ideal per jenjang</p>
                </div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Semua Jenjang</div>
            </div>
            <div class="relative h-80"><canvas id="rasioJenjangChart"></canvas></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 xl:col-span-2">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Distribusi Guru</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Porsi guru per jenjang pendidikan</p>
                </div>
            </div>
            <div class="relative h-80"><canvas id="guruDistChart"></canvas></div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 mb-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">Ringkasan per Jenjang</h3>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Perbandingan sekolah, murid, guru, dan rasio ideal</p>
            </div>
        </div>
        <div class="overflow-x-auto -mx-5 sm:-mx-6 px-5 sm:px-6">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] font-bold uppercase tracking-wider text-slate-400 border-b border-slate-200">
                        <th class="py-3 pr-4">Jenjang</th>
                        <th class="py-3 pr-4 text-right">Sekolah</th>
                        <th class="py-3 pr-4 text-right">Guru</th>
                        <th class="py-3 pr-4 text-right">Murid</th>
                        <th class="py-3 pr-4 text-right">Kebutuhan Ideal</th>
                        <th class="py-3 text-right">Rasio</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($byLevel as $lv)
                        <tr class="border-b border-slate-100 hover:bg-slate-50/60 transition-colors">
                            <td class="py-3 pr-4 font-semibold text-slate-800">{{ strtoupper($lv['level']) }}</td>
                            <td class="py-3 pr-4 text-right text-slate-600 font-medium">{{ number_format($lv['sekolah'], 0, ',', '.') }}</td>
                            <td class="py-3 pr-4 text-right text-slate-600 font-medium">{{ number_format($lv['guru'], 0, ',', '.') }}</td>
                            <td class="py-3 pr-4 text-right text-slate-600 font-medium">{{ number_format($lv['murid'], 0, ',', '.') }}</td>
                            <td class="py-3 pr-4 text-right text-slate-600 font-medium">{{ number_format($lv['ideal'], 0, ',', '.') }}</td>
                            <td class="py-3 text-right font-bold text-slate-800">1 : {{ number_format($lv['rasio'], 1, ',', '.') }}</td>
                        </tr>
                    @endforeach
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

        const palette = ['#2563eb', '#10b981', '#f59e0b', '#94a3b8', '#8b5cf6', '#ef4444'];
        const byLevel = @json($byLevel);

        const ctx1 = document.getElementById('rasioJenjangChart');
        if (ctx1) {
            new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: byLevel.map(d => d.level.toUpperCase()),
                    datasets: [
                        { label: 'Rasio Aktual', data: byLevel.map(d => d.rasio), backgroundColor: '#2563eb', borderRadius: 6, maxBarThickness: 40 },
                        { label: 'Rasio Ideal', data: byLevel.map(d => d.ideal > 0 ? (d.guru > 0 ? d.murid / d.guru : 0) : 0), backgroundColor: '#e2e8f0', borderRadius: 6, maxBarThickness: 40 },
                        { label: 'Batas Ideal (rasio maks)', data: byLevel.map(d => d.ideal), type: 'line', borderColor: '#ef4444', backgroundColor: '#ef4444', borderWidth: 2, pointRadius: 3, pointBackgroundColor: '#ef4444' }
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
                        y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.2)' } }
                    }
                }
            });
        }

        const ctx2 = document.getElementById('guruDistChart');
        if (ctx2) {
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: byLevel.map(d => d.level.toUpperCase()),
                    datasets: [{ data: byLevel.map(d => d.guru), backgroundColor: palette, borderWidth: 2, borderColor: '#ffffff' }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: { position: 'bottom', usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 16 },
                        tooltip: {
                            backgroundColor: '#0f172a', padding: 12, cornerRadius: 10,
                            callbacks: { label: c => ` ${c.label}: ${c.parsed.toLocaleString('id-ID')} guru` }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush