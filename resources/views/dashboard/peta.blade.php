@extends('layouts.app')

@section('title', 'Peta Sebaran - Dasbor Pendidikan Banjarnegara')

@section('content')
{{-- Page title --}}
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Peta Sebaran Sekolah</h1>
        <p class="mt-1 text-sm text-slate-500">Distribusi geografis sekolah dan statistik pendidikan per kecamatan di Kabupaten Banjarnegara</p>
    </div>
    <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-700">
        <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
        {{ \App\Support\Filters::semesterLabel($filters['semester']) }}
    </span>
</div>

@include('dashboard.partials.filters')

{{-- KPI Cards --}}
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-200">
        <div class="flex items-center">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50">
                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m9-14v14"/></svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-slate-500">Sekolah Terpetakan</p>
                <p class="text-2xl font-bold text-slate-900">{{ number_format($sekolah->count()) }}</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-200">
        <div class="flex items-center">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50">
                <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M18 10a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-slate-500">Total Siswa</p>
                <p class="text-2xl font-bold text-slate-900">{{ number_format(collect($kecamatanStats)->sum('siswa')) }}</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-200">
        <div class="flex items-center">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50">
                <svg class="h-6 w-6 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.765M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-slate-500">Total Guru</p>
                <p class="text-2xl font-bold text-slate-900">{{ number_format(collect($kecamatanStats)->sum('guru')) }}</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-200">
        <div class="flex items-center">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50">
                <svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3v-3m-3 3v-3m3-3V7m-3 3h6"/></svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-slate-500">Rasio Siswa : Guru</p>
                <p class="text-2xl font-bold text-slate-900">{{ number_format(collect($kecamatanStats)->sum('guru') > 0 ? collect($kecamatanStats)->sum('siswa') / collect($kecamatanStats)->sum('guru') : 0, 1) }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Map + Legend --}}
<div class="mb-6 grid grid-cols-1 gap-6">
    <div class="rounded-2xl bg-white p-5 shadow-xs ring-1 ring-slate-200">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Peta Interaktif</h3>
                <p id="mapModeDesc" class="text-sm text-slate-500">Choropleth kecamatan berdasarkan jumlah sekolah dan titik lokasi sekolah</p>
            </div>
            <div class="inline-flex shrink-0 rounded-lg bg-slate-100 p-1" role="group" aria-label="Mode peta">
                <button type="button" data-map-mode="choropleth" aria-pressed="true"
                        class="map-mode-btn rounded-md px-3 py-1.5 text-sm font-semibold transition bg-white text-slate-900 shadow-sm">
                    Choropleth
                </button>
                <button type="button" data-map-mode="heatmap" aria-pressed="false"
                        class="map-mode-btn rounded-md px-3 py-1.5 text-sm font-semibold transition text-slate-500 hover:text-slate-700">
                    Heatmap
                </button>
            </div>
        </div>
        <div id="sekolahMap" class="h-[640px] w-full overflow-hidden rounded-xl border border-slate-200"></div>
        <div id="legendChoropleth" class="mt-3 flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-slate-500">
            <span class="inline-flex items-center gap-1.5"><span class="inline-block h-3 w-3 rounded-full bg-[#2563eb]"></span> Negeri</span>
            <span class="inline-flex items-center gap-1.5"><span class="inline-block h-3 w-3 rounded-full bg-[#ef4444]"></span> Swasta</span>
            <span class="inline-flex items-center gap-1.5"><span class="inline-block h-3 w-6 rounded border border-slate-300 bg-[#1e40af]"></span> Banyak Sekolah</span>
            <span class="inline-flex items-center gap-1.5"><span class="inline-block h-3 w-6 rounded border border-slate-300 bg-[#bfdbfe]"></span> Sedikit Sekolah</span>
        </div>
        <div id="legendHeatmap" class="mt-3 hidden flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-slate-500">
            <span class="inline-flex items-center gap-1.5">
                <span class="inline-block h-3 w-24 rounded" style="background:linear-gradient(90deg,#1d4ed8,#22d3ee,#22c55e,#facc15,#ef4444)"></span>
                Kepadatan rendah &rarr; tinggi
            </span>
            <span>Intensitas dihitung dari jumlah siswa tiap sekolah</span>
        </div>
    </div>
</div>

{{-- Kecamatan Table --}}
<div class="mb-6 rounded-2xl bg-white shadow-xs ring-1 ring-slate-200">
    <div class="border-b border-slate-200 px-5 py-4">
        <h3 class="text-base font-semibold text-slate-900">Statistik per Kecamatan</h3>
        <p class="text-sm text-slate-500">Data sekolah, siswa, guru, dan rasio siswa:guru</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <th class="px-5 py-3 font-semibold">Kecamatan</th>
                    <th class="px-5 py-3 text-right font-semibold">Sekolah</th>
                    <th class="px-5 py-3 text-right font-semibold">Siswa</th>
                    <th class="px-5 py-3 text-right font-semibold">Guru</th>
                    <th class="px-5 py-3 text-right font-semibold">Rombel</th>
                    <th class="px-5 py-3 text-right font-semibold">Rasio Siswa : Guru</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($kecamatanStats as $k)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 font-medium text-slate-900">{{ $k['kecamatan'] }}</td>
                    <td class="px-5 py-3 text-right text-slate-600">{{ number_format($k['sekolah']) }}</td>
                    <td class="px-5 py-3 text-right text-slate-600">{{ number_format($k['siswa']) }}</td>
                    <td class="px-5 py-3 text-right text-slate-600">{{ number_format($k['guru']) }}</td>
                    <td class="px-5 py-3 text-right text-slate-600">{{ number_format($k['rombel']) }}</td>
                    <td class="px-5 py-3 text-right">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ ($k['rasio'] ?? 0) <= 10 ? 'bg-emerald-50 text-emerald-700' : (($k['rasio'] ?? 0) <= 15 ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700') }}">
                            {{ number_format($k['rasio'] ?? 0, 1) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-8 text-center text-sm text-slate-400">Tidak ada data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Legend --}}
<div id="mapLegend" class="hidden"></div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sekolah = @json($sekolah);
    const kecamatanStats = @json($kecamatanStats);
    const geojson = JSON.parse(@json($geojson));

    const jenjangColors = {
        'PAUD': '#f59e0b', 'TK': '#f97316', 'SD': '#2563eb', 'SMP': '#8b5cf6',
        'SMA': '#10b981', 'SMK': '#ec4899', 'MI': '#3b82f6', 'MTS': '#a855f7',
        'MA': '#14b8a6', 'PKBM': '#64748b'
    };

    // Initialize map centered on Banjarnegara
    const map = L.map('sekolahMap').setView([-7.3833, 109.6667], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    // Choropleth colors based on school count
    function getChoroplethColor(count, maxCount) {
        if (maxCount === 0 || count === 0) return '#f1f5f9';
        const ratio = count / maxCount;
        if (ratio > 0.8) return '#1e40af';
        if (ratio > 0.6) return '#2563eb';
        if (ratio > 0.4) return '#3b82f6';
        if (ratio > 0.2) return '#60a5fa';
        return '#bfdbfe';
    }

    const statsMap = {};
    kecamatanStats.forEach(function (k) {
        statsMap[k.kecamatan] = k;
    });

    const maxSekolah = Math.max.apply(null, kecamatanStats.map(function (k) { return k.sekolah; }).concat([0]));

    // Layer groups
    const markerLayer = L.layerGroup();
    let choroplethLayer = null;

    // Choropleth layer
    if (geojson && geojson.features) {
        choroplethLayer = L.geoJSON(geojson, {
            style: function (feature) {
                const name = feature.properties.WADMKC;
                const stat = statsMap[name];
                const count = stat ? stat.sekolah : 0;
                return {
                    color: '#ffffff',
                    weight: 2,
                    fillColor: getChoroplethColor(count, maxSekolah),
                    fillOpacity: 0.35
                };
            },
            onEachFeature: function (feature, layer) {
                const name = feature.properties.WADMKC;
                const stat = statsMap[name];
                let popup = '<div style="font-family:sans-serif"><strong>' + name + '</strong>';
                if (stat) {
                    popup += '<br>Sekolah: ' + stat.sekolah + '<br>Siswa: ' + stat.siswa.toLocaleString('id-ID') +
                             '<br>Guru: ' + stat.guru + '<br>Rasio: ' + Number(stat.rasio).toFixed(1);
                }
                popup += '</div>';
                layer.bindPopup(popup);
            }
        }).addTo(map);
    }

    // Circle markers for each school
    const validSchools = sekolah.filter(function (s) {
        return s.latitude && s.longitude && parseFloat(s.latitude) !== 0;
    });

    validSchools.forEach(function (s) {
        const color = s.status === 'Swasta' ? '#ef4444' : '#2563eb';
        L.circleMarker([parseFloat(s.latitude), parseFloat(s.longitude)], {
            radius: 5,
            fillColor: color,
            color: '#ffffff',
            weight: 1.5,
            fillOpacity: 0.9
        }).bindPopup(
            '<div style="font-family:sans-serif"><strong>' + s.nama_sekolah + '</strong><br>' +
            s.jenjang + ' - ' + s.status + '<br>' + s.kecamatan +
            (s.siswa ? '<br>Siswa: ' + Number(s.siswa).toLocaleString('id-ID') : '') + '</div>'
        ).addTo(markerLayer);
    });
    markerLayer.addTo(map);

    // Heatmap layer: intensitas = jumlah siswa tiap sekolah
    const maxSiswaSekolah = Math.max.apply(null, validSchools.map(function (s) {
        return s.siswa || 0;
    }).concat([0]));

    function heatWeight(s) {
        const v = s.siswa || 0;
        if (maxSiswaSekolah <= 0) return 0.6;
        return 0.2 + 0.8 * Math.min(1, v / maxSiswaSekolah);
    }

    const heatPoints = validSchools.map(function (s) {
        return [parseFloat(s.latitude), parseFloat(s.longitude), heatWeight(s)];
    });

    const heatLayer = L.heatLayer(heatPoints, {
        radius: 28,
        blur: 20,
        maxZoom: 15,
        minOpacity: 0.25,
        max: 1.0,
        gradient: { 0.2: '#1d4ed8', 0.4: '#22d3ee', 0.6: '#22c55e', 0.8: '#facc15', 1.0: '#ef4444' }
    });

    // Mode switching: choropleth <-> heatmap
    const modeButtons = document.querySelectorAll('.map-mode-btn');
    const legendChoropleth = document.getElementById('legendChoropleth');
    const legendHeatmap = document.getElementById('legendHeatmap');
    const modeDesc = document.getElementById('mapModeDesc');

    const modeDescriptions = {
        choropleth: 'Choropleth kecamatan berdasarkan jumlah sekolah dan titik lokasi sekolah',
        heatmap: 'Heatmap kepadatan siswa — makin merah, makin padat jumlah siswa di wilayah tersebut'
    };

    function setMapMode(mode) {
        if (map.hasLayer(markerLayer)) map.removeLayer(markerLayer);
        if (choroplethLayer && map.hasLayer(choroplethLayer)) map.removeLayer(choroplethLayer);
        if (map.hasLayer(heatLayer)) map.removeLayer(heatLayer);

        if (mode === 'heatmap') {
            heatLayer.addTo(map);
            legendChoropleth.classList.add('hidden');
            legendHeatmap.classList.remove('hidden');
        } else {
            if (choroplethLayer) choroplethLayer.addTo(map);
            markerLayer.addTo(map);
            legendHeatmap.classList.add('hidden');
            legendChoropleth.classList.remove('hidden');
        }

        modeDesc.textContent = modeDescriptions[mode];

        modeButtons.forEach(function (btn) {
            const active = btn.dataset.mapMode === mode;
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
            btn.classList.toggle('bg-white', active);
            btn.classList.toggle('text-slate-900', active);
            btn.classList.toggle('shadow-sm', active);
            btn.classList.toggle('text-slate-500', !active);
        });
    }

    modeButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            setMapMode(btn.dataset.mapMode);
        });
    });
});
</script>
@endpush
