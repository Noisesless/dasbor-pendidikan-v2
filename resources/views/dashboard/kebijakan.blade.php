@extends('layouts.app')

@section('content')
@php
    $f1 = fn ($v) => number_format((float) $v, 1, ',', '.');
    $top = $priorityRows[0] ?? null;
    $bottom = end($priorityRows) ?: null;
    $overageNow = $overageTrend ? end($overageTrend)['overage'] : 0;
@endphp

<div class="mb-5 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Analisis Kebijakan</h1>
        <p class="text-sm text-slate-500 mt-1">
            Indeks prioritas, tren historis, dan konteks makro &mdash; semester {{ $semester }}
        </p>
    </div>
    <form method="GET" class="flex items-center gap-2 shrink-0">
        <label for="semester" class="text-sm font-medium text-slate-500">Semester</label>
        <select name="semester" id="semester" onchange="this.form.submit()"
                class="rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-200">
            @foreach ($availableSemesters as $s)
                <option value="{{ $s }}" @selected($s === $semester)>{{ $s }}</option>
            @endforeach
        </select>
    </form>
</div>

{{-- ============ KARTU RINGKAS ============ --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5 mb-6">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <p class="text-sm font-semibold text-slate-500">Kecamatan Prioritas Utama</p>
        <p class="text-2xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ $top['kecamatan'] ?? '-' }}</p>
        <p class="text-sm text-slate-500 mt-1">Skor {{ $top ? $f1($top['skor']) : 0 }} / 100 &middot; {{ $faktorLabel[$top['faktor_utama']] ?? '' }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <p class="text-sm font-semibold text-slate-500">Kecamatan Kondisi Terbaik</p>
        <p class="text-2xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ $bottom['kecamatan'] ?? '-' }}</p>
        <p class="text-sm text-slate-500 mt-1">Skor {{ $bottom ? $f1($bottom['skor']) : 0 }} / 100 &middot; prioritas terendah</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <p class="text-sm font-semibold text-slate-500">Siswa di Luar Usia Standar</p>
        <p class="text-2xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ $f1($overageNow) }}%</p>
        <p class="text-sm text-slate-500 mt-1">Proxy risiko putus sekolah</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <p class="text-sm font-semibold text-slate-500">Harapan Lama Sekolah</p>
        <p class="text-2xl font-extrabold text-slate-900 mt-2 tracking-tight">
            {{ isset($bpsSeries['hls']) ? number_format(max($bpsSeries['hls']), 2, ',', '.') : '-' }}
            <span class="text-sm font-semibold text-slate-400">tahun</span>
        </p>
        <p class="text-sm text-slate-500 mt-1">BPS {{ $bpsYears ? max($bpsYears) : '' }}</p>
    </div>
</div>

{{-- ============ INDEKS PRIORITAS ============ --}}
<div class="grid grid-cols-1 xl:grid-cols-5 gap-4 sm:gap-5 mb-6">
    <div class="xl:col-span-3 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <h2 class="text-base font-bold text-slate-900">Indeks Prioritas Kecamatan</h2>
        <p class="text-sm text-slate-500 mt-1 mb-4">Skor komposit 0&ndash;100, makin tinggi makin perlu diintervensi lebih dulu</p>
        <div class="h-[420px]"><canvas id="chartPrioritas"></canvas></div>
        <p class="text-xs text-slate-400 mt-3">
            Bobot: rasio siswa/guru {{ $weights['rasio'] }}% &middot; siswa di luar usia {{ $weights['overage'] }}% &middot;
            akreditasi C/belum {{ $weights['akreditasi_rendah'] }}% &middot; guru belum sertifikasi {{ $weights['belum_sertifikasi'] }}% &middot;
            rombel overload {{ $weights['rombel_padat'] }}% &middot; sekolah kecil {{ $weights['sekolah_kecil'] }}%
        </p>
    </div>

    <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <h2 class="text-base font-bold text-slate-900">Tiga Kecamatan Teratas</h2>
        <p class="text-sm text-slate-500 mt-1 mb-4">Faktor penyumbang skor terbesar</p>
        <div class="space-y-3">
            @foreach (array_slice($priorityRows, 0, 3) as $row)
                <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-3.5">
                    <div class="flex items-center justify-between gap-2">
                        <p class="font-bold text-slate-900 text-sm">{{ $row['peringkat'] }}. {{ $row['kecamatan'] }}</p>
                        <span class="text-sm font-extrabold text-blue-700">{{ $f1($row['skor']) }}</span>
                    </div>
                    <div class="mt-2 h-1.5 rounded-full bg-slate-200 overflow-hidden">
                        <div class="h-full rounded-full bg-blue-600" style="width: {{ $row['skor'] }}%"></div>
                    </div>
                    <p class="text-xs text-slate-500 mt-2">{{ $faktorLabel[$row['faktor_utama']] ?? '-' }}</p>
                    <div class="grid grid-cols-2 gap-x-3 gap-y-1 mt-2 text-xs text-slate-500">
                        <span>Rasio <b class="text-slate-700">{{ $f1($row['rasio']) }}</b></span>
                        <span>Overage <b class="text-slate-700">{{ $f1($row['overage']) }}%</b></span>
                        <span>Akred. rendah <b class="text-slate-700">{{ $f1($row['akreditasi_rendah']) }}%</b></span>
                        <span>Belum sert. <b class="text-slate-700">{{ $f1($row['belum_sertifikasi']) }}%</b></span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ============ TABEL INDEKS ============ --}}
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 mb-6">
    <h2 class="text-base font-bold text-slate-900">Rincian Indikator per Kecamatan</h2>
    <p class="text-sm text-slate-500 mt-1 mb-4">Semester {{ $semester }} &middot; diurutkan dari prioritas tertinggi</p>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <th class="py-3 pr-3">#</th>
                    <th class="py-3 px-3">Kecamatan</th>
                    <th class="py-3 px-3 text-right">Skor</th>
                    <th class="py-3 px-3 text-right">Siswa</th>
                    <th class="py-3 px-3 text-right">Rasio</th>
                    <th class="py-3 px-3 text-right">Overage</th>
                    <th class="py-3 px-3 text-right">Akred. C/Belum</th>
                    <th class="py-3 px-3 text-right">Belum Sertifikasi</th>
                    <th class="py-3 px-3 text-right">Rombel Padat</th>
                    <th class="py-3 px-3 text-right">Sekolah Kecil</th>
                    <th class="py-3 pl-3">Faktor Utama</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($priorityRows as $row)
                    <tr class="hover:bg-slate-50/60 {{ $row['peringkat'] <= 3 ? 'bg-red-50/40' : '' }}">
                        <td class="py-2.5 pr-3 font-semibold text-slate-400">{{ $row['peringkat'] }}</td>
                        <td class="py-2.5 px-3 font-semibold text-slate-900">{{ $row['kecamatan'] }}</td>
                        <td class="py-2.5 px-3 text-right font-extrabold text-slate-900">{{ $f1($row['skor']) }}</td>
                        <td class="py-2.5 px-3 text-right text-slate-600">{{ number_format($row['siswa'], 0, ',', '.') }}</td>
                        <td class="py-2.5 px-3 text-right text-slate-600">{{ $f1($row['rasio']) }}</td>
                        <td class="py-2.5 px-3 text-right {{ $row['overage'] >= 15 ? 'text-red-600 font-semibold' : 'text-slate-600' }}">{{ $f1($row['overage']) }}%</td>
                        <td class="py-2.5 px-3 text-right text-slate-600">{{ $f1($row['akreditasi_rendah']) }}%</td>
                        <td class="py-2.5 px-3 text-right text-slate-600">{{ $f1($row['belum_sertifikasi']) }}%</td>
                        <td class="py-2.5 px-3 text-right text-slate-600">{{ $f1($row['rombel_padat']) }}%</td>
                        <td class="py-2.5 px-3 text-right text-slate-600">{{ $f1($row['sekolah_kecil']) }}%</td>
                        <td class="py-2.5 pl-3 text-xs text-slate-500">{{ $faktorLabel[$row['faktor_utama']] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- ============ TREN HISTORIS ============ --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-4 sm:gap-5 mb-6">
    <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <h2 class="text-base font-bold text-slate-900">Tren Jumlah Murid per Jenjang</h2>
        <p class="text-sm text-slate-500 mt-1 mb-4">Sumber Dindikpora &amp; Kemenag, {{ $histYears[0] ?? '-' }}&ndash;{{ $histYears ? end($histYears) : '-' }}</p>
        <div class="h-[360px]"><canvas id="chartHistoris"></canvas></div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <h2 class="text-base font-bold text-slate-900">Siswa di Luar Usia Standar</h2>
        <p class="text-sm text-slate-500 mt-1 mb-4">Persentase kabupaten per semester</p>
        <div class="h-[360px]"><canvas id="chartOverage"></canvas></div>
    </div>
</div>

{{-- ============ PERUBAHAN PER KECAMATAN ============ --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5 mb-6">
    @foreach (['SD', 'SMP'] as $jenjang)
        @php
            $data = $changeByKecamatan[$jenjang] ?? [];
            $years = $data['__years'] ?? ['-', '-'];
            unset($data['__years']);
            uasort($data, fn ($a, $b) => $a['delta'] <=> $b['delta']);
        @endphp
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
            <h2 class="text-base font-bold text-slate-900">Perubahan Murid {{ $jenjang }}</h2>
            <p class="text-sm text-slate-500 mt-1 mb-4">{{ $years[0] }} &rarr; {{ $years[1] }} (urut dari penurunan terbesar)</p>
            <div class="overflow-y-auto max-h-[380px]">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-white">
                        <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                            <th class="py-2 pr-3">Kecamatan</th>
                            <th class="py-2 px-3 text-right">{{ $years[0] }}</th>
                            <th class="py-2 px-3 text-right">{{ $years[1] }}</th>
                            <th class="py-2 pl-3 text-right">Delta</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($data as $kec => $d)
                            <tr>
                                <td class="py-2 pr-3 text-slate-700">{{ $kec }}</td>
                                <td class="py-2 px-3 text-right text-slate-500">{{ number_format($d['awal'], 0, ',', '.') }}</td>
                                <td class="py-2 px-3 text-right text-slate-600">{{ number_format($d['akhir'], 0, ',', '.') }}</td>
                                <td class="py-2 pl-3 text-right font-semibold {{ $d['delta'] < -10 ? 'text-red-600' : ($d['delta'] > 10 ? 'text-emerald-600' : 'text-slate-500') }}">
                                    {{ $d['delta'] > 0 ? '+' : '' }}{{ $f1($d['delta']) }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</div>

{{-- ============ KONTEKS MAKRO BPS ============ --}}
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 mb-6">
    <h2 class="text-base font-bold text-slate-900">Konteks Makro Kabupaten (BPS)</h2>
    <p class="text-sm text-slate-500 mt-1 mb-4">Indikator pendidikan &amp; sosial-ekonomi {{ $bpsYears[0] ?? '-' }}&ndash;{{ $bpsYears ? end($bpsYears) : '-' }}</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 mb-5">
        @foreach ($bpsCards as $card)
            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-4">
                <p class="text-xs font-semibold text-slate-500">{{ $card['label'] }}</p>
                <p class="text-2xl font-extrabold text-slate-900 mt-1">
                    {{ number_format($card['nilai'], 2, ',', '.') }}
                    <span class="text-xs font-semibold text-slate-400">{{ $card['satuan'] }}</span>
                </p>
                <p class="text-xs mt-1 {{ $card['delta'] !== null && $card['delta'] < 0 ? 'text-red-600' : 'text-emerald-600' }}">
                    @if ($card['delta'] !== null)
                        {{ $card['delta'] > 0 ? '+' : '' }}{{ number_format($card['delta'], 2, ',', '.') }} vs tahun sebelumnya
                    @else
                        &nbsp;
                    @endif
                </p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="h-[300px]"><canvas id="chartBpsPendidikan"></canvas></div>
        <div class="h-[300px]"><canvas id="chartBpsSosial"></canvas></div>
    </div>
</div>

{{-- ============ CATATAN ============ --}}
<div class="bg-amber-50/70 border border-amber-200 rounded-2xl p-5">
    <h2 class="text-sm font-bold text-amber-900 mb-2">Catatan metodologi &amp; kualitas data</h2>
    <ul class="text-sm text-amber-900/80 space-y-1.5 list-disc pl-5">
        <li>Indeks prioritas memakai data Dapodik per sekolah (semester {{ $semester }}), bukan opini; skor bersifat <b>relatif antar kecamatan</b>, bukan nilai absolut.</li>
        <li>Tahun ajaran <b>2024/2025</b> dari berkas Dindikpora tidak dipakai karena kolom &ldquo;Murid Swasta&rdquo; pada konversi CSV bergeser; tahun tersebut sudah tercakup data Dapodik.</li>
        <li>Berkas <b>MI</b> dari Kemenag rusak pada 7 dari 9 tahun ajaran (isinya duplikat MTs), sehingga hanya tahun yang valid yang dipakai.</li>
        <li><b>APK per kecamatan tidak disajikan</b>: data penduduk 2026 belum konsisten dengan enrolmen, dan pencatatan siswa berbasis lokasi sekolah, bukan domisili.</li>
        <li>Overage = siswa di luar usia standar; dipakai sebagai <b>proxy</b> risiko putus sekolah karena data putus sekolah per sekolah belum tersedia.</li>
        <li>Cakupan data: {{ number_format($coverage['total_sekolah'], 0, ',', '.') }} sekolah, {{ number_format($coverage['total_ptk'], 0, ',', '.') }} PTK;
            {{ $coverage['sekolah_tanpa_siswa'] }} sekolah tanpa data siswa semester ini,
            {{ $coverage['guru_tanpa_nip'] }} PTK tanpa NIP.</li>
    </ul>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const idn = (v) => new Intl.NumberFormat('id-ID').format(v);

    // --- Indeks prioritas: bar horizontal ---
    const priority = @json($priorityRows);
    const priorityCtx = document.getElementById('chartPrioritas');
    if (priorityCtx && priority.length) {
        new Chart(priorityCtx, {
            type: 'bar',
            data: {
                labels: priority.map(r => r.kecamatan),
                datasets: [{
                    label: 'Skor prioritas',
                    data: priority.map(r => r.skor),
                    backgroundColor: priority.map(r => r.skor >= 60 ? '#dc2626' : (r.skor >= 45 ? '#f59e0b' : '#2563eb')),
                    borderRadius: 4,
                    maxBarThickness: 18,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (c) => 'Skor ' + c.parsed.x.toFixed(1) + ' dari 100'
                        }
                    }
                },
                scales: {
                    x: { beginAtZero: true, max: 100, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8' } },
                    y: { grid: { display: false }, ticks: { color: '#475569', font: { size: 11 } } }
                }
            }
        });
    }

    // --- Tren historis murid per jenjang ---
    const histYears = @json($histYears);
    const histDatasets = @json($histDatasets);
    const palette = ['#2563eb', '#7c3aed', '#0891b2', '#16a34a', '#f59e0b', '#ef4444', '#db2777', '#475569'];
    const histCtx = document.getElementById('chartHistoris');
    if (histCtx && histYears.length) {
        const datasets = Object.keys(histDatasets).map(function (jenjang, i) {
            return {
                label: jenjang,
                data: histDatasets[jenjang],
                borderColor: palette[i % palette.length],
                backgroundColor: palette[i % palette.length] + '22',
                borderWidth: 2,
                tension: 0.3,
                spanGaps: true,
                pointRadius: 3,
            };
        });
        new Chart(histCtx, {
            type: 'line',
            data: { labels: histYears, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, font: { size: 11 } } },
                    tooltip: { callbacks: { label: (c) => c.dataset.label + ': ' + idn(c.parsed.y ?? 0) + ' murid' } }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', callback: (v) => idn(v) } },
                    x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 10 } } }
                }
            }
        });
    }

    // --- Overage per semester ---
    const overage = @json($overageTrend);
    const overageCtx = document.getElementById('chartOverage');
    if (overageCtx && overage.length) {
        new Chart(overageCtx, {
            type: 'line',
            data: {
                labels: overage.map(r => r.semester),
                datasets: [{
                    label: 'Overage (%)',
                    data: overage.map(r => r.overage),
                    borderColor: '#ef4444',
                    backgroundColor: '#ef444422',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', callback: (v) => v + '%' } },
                    x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 10 } } }
                }
            }
        });
    }

    // --- BPS: pendidikan (HLS/RLS) & sosial (IPM/miskin/gini) ---
    const bpsYears = @json($bpsYears);
    const bpsSeries = @json($bpsSeries);
    const bpsMeta = @json($bpsMeta);

    function bpsDatasets(keys) {
        return keys.filter(k => bpsSeries[k]).map(k => ({
            label: bpsMeta[k].label + ' (' + bpsMeta[k].satuan + ')',
            data: bpsYears.map(y => bpsSeries[k][y] ?? null),
            borderColor: bpsMeta[k].warna,
            backgroundColor: bpsMeta[k].warna + '22',
            borderWidth: 2,
            tension: 0.3,
            spanGaps: true,
            pointRadius: 3,
            yAxisID: bpsMeta[k].satuan === 'indeks' || bpsMeta[k].satuan === '%' ? 'y1' : 'y',
        }));
    }

    const bpsOpts = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, font: { size: 10 } } } },
        scales: {
            y: { position: 'left', grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8' } },
            y1: { position: 'right', grid: { display: false }, ticks: { color: '#94a3b8' } },
            x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
        }
    };

    const bpsPendidikan = document.getElementById('chartBpsPendidikan');
    if (bpsPendidikan && bpsYears.length) {
        new Chart(bpsPendidikan, { type: 'line', data: { labels: bpsYears, datasets: bpsDatasets(['hls', 'rls']) }, options: bpsOpts });
    }

    const bpsSosial = document.getElementById('chartBpsSosial');
    if (bpsSosial && bpsYears.length) {
        new Chart(bpsSosial, { type: 'line', data: { labels: bpsYears, datasets: bpsDatasets(['ipm', 'miskin_pct', 'gini']) }, options: bpsOpts });
    }
});
</script>
@endpush
