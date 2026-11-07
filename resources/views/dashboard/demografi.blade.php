@extends('layouts.app')

@section('content')
    {{-- Page Title & Period --}}
    <div class="mb-5 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Demografi &amp; Akses</h2>
            <p class="text-sm text-slate-500 mt-1 font-medium">
                Analisis partisipasi pendidikan &amp; struktur demografis &middot;
                <span class="text-blue-600 font-semibold">{{ App\Support\Filters::semesterLabel($filters['semester']) }}</span>
            </p>
        </div>
    </div>

    @include('dashboard.partials.filters')

    {{-- APK KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5 mb-6">
        @foreach ($apkApm as $row)
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $row['grup'] }}</p>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 text-[10px] font-bold border border-blue-100">APK</span>
                </div>
                <p class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ number_format($row['apk'], 1, ',', '.') }}<span class="text-lg text-slate-400">%</span></p>
                <div class="mt-2 pt-3 border-t border-slate-100 space-y-1">
                    <p class="text-xs text-slate-500 font-medium">
                        Siswa <span class="font-bold text-slate-700">{{ number_format($row['siswa'], 0, ',', '.') }}</span>
                        &middot; Penduduk <span class="font-bold text-slate-700">{{ number_format($row['penduduk'], 0, ',', '.') }}</span>
                    </p>
                    <p class="text-xs text-slate-500 font-medium">
                        APM <span class="font-bold text-emerald-600">{{ number_format($row['apm'], 1, ',', '.') }}%</span>
                        &middot; IPG <span class="font-bold text-violet-600">{{ number_format($row['ipg'], 2, ',', '.') }}</span>
                    </p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Tren Siswa per Kelompok Jenjang --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 mb-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">Tren Siswa per Kelompok Jenjang</h3>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Perkembangan jumlah siswa 5 semester terakhir</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-semibold">
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-600"></span> SD/MI</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-violet-500"></span> SMP/MTS</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-amber-500"></span> SMA/SMK/MA</span>
            </div>
        </div>
        <div class="h-72 sm:h-80">
            <canvas id="trendJenjangChart"></canvas>
        </div>
    </div>

    {{-- Tabel APK/APM/IPG + Piramida --}}
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4 sm:gap-6 mb-6">
        <div class="xl:col-span-3 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
            <h3 class="text-sm font-extrabold text-slate-900">Indikator Partisipasi Pendidikan</h3>
            <p class="text-xs text-slate-500 font-medium mt-0.5 mb-4">
                APK (Angka Partisipasi Kasar) &middot; APM (Angka Partisipasi Murni) &middot; IPG (Indeks Paritas Gender)
            </p>
            <div class="overflow-x-auto -mx-2 px-2">
                <table class="w-full text-sm min-w-[520px]">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-200">
                            <th class="py-2.5 pr-3 font-bold">Kelompok</th>
                            <th class="py-2.5 px-3 text-right font-bold">Penduduk</th>
                            <th class="py-2.5 px-3 text-right font-bold">Siswa</th>
                            <th class="py-2.5 px-3 text-right font-bold">APK</th>
                            <th class="py-2.5 px-3 text-right font-bold">APM</th>
                            <th class="py-2.5 pl-3 text-right font-bold">IPG</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($apkApm as $row)
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td class="py-2.5 pr-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-xs font-bold border border-blue-100">{{ $row['grup'] }}</span>
                                </td>
                                <td class="py-2.5 px-3 text-right font-semibold text-slate-700">{{ number_format($row['penduduk'], 0, ',', '.') }}</td>
                                <td class="py-2.5 px-3 text-right font-bold text-slate-900">{{ number_format($row['siswa'], 0, ',', '.') }}</td>
                                <td class="py-2.5 px-3 text-right">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold
                                        {{ $row['apk'] >= 100 ? 'bg-emerald-50 text-emerald-700' : ($row['apk'] >= 90 ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700') }}">
                                        {{ number_format($row['apk'], 1, ',', '.') }}%
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-right font-semibold text-slate-700">{{ number_format($row['apm'], 1, ',', '.') }}%</td>
                                <td class="py-2.5 pl-3 text-right font-semibold text-slate-700">{{ number_format($row['ipg'], 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-[11px] text-slate-400 font-medium mt-3 leading-relaxed">
                Sumber data penduduk: Proyeksi BPS tahun 2026. IPG mendekati 1,00 menandakan kesetaraan gender semakin baik.
            </p>
        </div>

        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
            <h3 class="text-sm font-extrabold text-slate-900">Piramida Penduduk Usia Sekolah</h3>
            <p class="text-xs text-slate-500 font-medium mt-0.5 mb-4">Struktur penduduk berdasarkan kelompok usia</p>
            <div class="h-80">
                <canvas id="piramidaChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Siswa & Guru per Kecamatan --}}
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4 sm:gap-6 mb-6">
        <div class="xl:col-span-3 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Siswa per Kecamatan</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Distribusi siswa berdasarkan kelompok jenjang</p>
                </div>
                <div class="flex items-center gap-3 text-xs font-semibold">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-blue-600"></span> SD/MI</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-violet-500"></span> SMP/MTS</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-amber-500"></span> SMA/SMK/MA</span>
                </div>
            </div>
            <div class="h-[420px]">
                <canvas id="siswaKecamatanChart"></canvas>
            </div>
        </div>

        <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
            <h3 class="text-sm font-extrabold text-slate-900">Guru per Kecamatan</h3>
            <p class="text-xs text-slate-500 font-medium mt-0.5 mb-4">Distribusi guru sebagai indikator pemerataan akses</p>
            <div class="h-[420px]">
                <canvas id="guruKecamatanChart"></canvas>
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

        // ---- Tren Siswa per Kelompok Jenjang ----
        const trendCtx = document.getElementById('trendJenjangChart');
        if (trendCtx) {
            const trend = @json($apkTrend);
            const makeLine = (label, key, color) => ({
                label: label,
                data: trend.map(t => t[key]),
                borderColor: color,
                backgroundColor: color.replace(')', ', 0.08)').replace('rgb', 'rgba'),
                fill: true,
                tension: 0.4,
                borderWidth: 2.5,
                pointBackgroundColor: color,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
            });

            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: trend.map(t => t.label),
                    datasets: [
                        makeLine('SD/MI', 'sd', '#2563eb'),
                        makeLine('SMP/MTS', 'smp', '#8b5cf6'),
                        makeLine('SMA/SMK/MA', 'sma', '#f59e0b'),
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
                                label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y.toLocaleString('id-ID')} siswa`,
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

        // ---- Piramida Penduduk ----
        const piramidaCtx = document.getElementById('piramidaChart');
        if (piramidaCtx) {
            const piramida = @json($piramida);
            new Chart(piramidaCtx, {
                type: 'bar',
                data: {
                    labels: piramida.map(p => p.label),
                    datasets: [
                        {
                            label: 'Laki-laki',
                            data: piramida.map(p => -p.laki),
                            backgroundColor: '#2563eb',
                            borderRadius: { topLeft: 6, bottomLeft: 6 },
                            borderSkipped: false,
                            maxBarThickness: 20,
                        },
                        {
                            label: 'Perempuan',
                            data: piramida.map(p => p.perempuan),
                            backgroundColor: '#ec4899',
                            borderRadius: { topRight: 6, bottomRight: 6 },
                            borderSkipped: false,
                            maxBarThickness: 20,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: { position: 'top', labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 16 } },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 12,
                            cornerRadius: 10,
                            titleFont: { weight: 'bold' },
                            callbacks: {
                                label: ctx => ` ${ctx.dataset.label}: ${Math.abs(ctx.parsed.x).toLocaleString('id-ID')} jiwa`,
                            },
                        },
                    },
                    scales: {
                        x: {
                            stacked: true,
                            grid: { color: 'rgba(148, 163, 184, 0.2)' },
                            ticks: {
                                callback: v => Math.abs(v) >= 1000 ? (Math.abs(v) / 1000).toLocaleString('id-ID') + ' rb' : Math.abs(v),
                            },
                        },
                        y: { stacked: true, grid: { display: false } },
                    },
                },
            });
        }

        // ---- Siswa per Kecamatan (stacked) ----
        const siswaKecCtx = document.getElementById('siswaKecamatanChart');
        if (siswaKecCtx) {
            const kec = @json($siswaKecamatan);
            new Chart(siswaKecCtx, {
                type: 'bar',
                data: {
                    labels: kec.map(k => k.kecamatan),
                    datasets: [
                        { label: 'SD/MI', data: kec.map(k => k.sd), backgroundColor: '#2563eb', borderRadius: 4, maxBarThickness: 18 },
                        { label: 'SMP/MTS', data: kec.map(k => k.smp), backgroundColor: '#8b5cf6', borderRadius: 4, maxBarThickness: 18 },
                        { label: 'SMA/SMK/MA', data: kec.map(k => k.sma), backgroundColor: '#f59e0b', borderRadius: 4, maxBarThickness: 18 },
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
                                label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y.toLocaleString('id-ID')} siswa`,
                            },
                        },
                    },
                    scales: {
                        x: { stacked: true, grid: { display: false }, ticks: { maxRotation: 45, minRotation: 45, font: { size: 10 } } },
                        y: {
                            stacked: true,
                            beginAtZero: true,
                            grid: { color: 'rgba(148, 163, 184, 0.2)' },
                            ticks: {
                                callback: v => v >= 1000 ? (v / 1000).toLocaleString('id-ID') + ' rb' : v,
                            },
                        },
                    },
                },
            });
        }

        // ---- Guru per Kecamatan ----
        const guruKecCtx = document.getElementById('guruKecamatanChart');
        if (guruKecCtx) {
            const kec = @json($guruKecamatan);
            new Chart(guruKecCtx, {
                type: 'bar',
                data: {
                    labels: kec.map(k => k.kecamatan),
                    datasets: [{
                        label: 'Guru',
                        data: kec.map(k => k.guru),
                        backgroundColor: kec.map((_, i) =>
                            i === 0 ? '#10b981' : `rgba(16, 185, 129, ${Math.max(0.25, 1 - i * 0.05)})`),
                        borderRadius: 8,
                        borderSkipped: false,
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
                            titleFont: { weight: 'bold' },
                            callbacks: {
                                label: ctx => ` ${ctx.parsed.x.toLocaleString('id-ID')} guru`,
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
                        y: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    },
                },
            });
        }
    });
</script>
@endpush>

