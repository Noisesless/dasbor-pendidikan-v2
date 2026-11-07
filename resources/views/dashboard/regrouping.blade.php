@extends('layouts.app')

@section('content')
@php
    $f0 = fn ($v) => number_format((float) $v, 0, ',', '.');
@endphp

<div class="mb-5">
    <h1 class="text-2xl font-bold text-slate-900">Kandidat Regrouping Sekolah</h1>
    <p class="text-sm text-slate-500 mt-1">
        Pasangan sekolah berdekatan yang berpotensi digabung &mdash; semester {{ $semester }}
    </p>
</div>

<form method="GET" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 mb-6">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
        <div>
            <label for="jenjang" class="block text-xs font-semibold text-slate-500 mb-1">Jenjang</label>
            <select name="jenjang" id="jenjang" onchange="this.form.submit()"
                    class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-200">
                <option value="SD" @selected($jenjang === 'SD')>SD</option>
                <option value="SMP" @selected($jenjang === 'SMP')>SMP</option>
            </select>
        </div>
        <div>
            <label for="radius" class="block text-xs font-semibold text-slate-500 mb-1">Jarak maksimum (km): <span class="font-bold text-slate-800">{{ $radius }}</span></label>
            <input type="range" name="radius" id="radius" min="0.3" max="3" step="0.1" value="{{ $radius }}"
                   onchange="this.form.submit()" class="w-full accent-blue-600">
        </div>
        <div>
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition">Terapkan</button>
        </div>
    </div>
</form>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5 mb-6">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <p class="text-sm font-semibold text-slate-500">Pasangan Berdekatan</p>
        <p class="text-2xl font-extrabold text-slate-900 mt-2">{{ $f0($totalPairs) }}</p>
        <p class="text-sm text-slate-500 mt-1">dalam radius {{ $radius }} km</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <p class="text-sm font-semibold text-slate-500">Sekolah &lt; 60 Siswa</p>
        <p class="text-2xl font-extrabold text-amber-600 mt-2">{{ $f0($sekolahKecil) }}</p>
        <p class="text-sm text-slate-500 mt-1">dari {{ $f0($totalSekolah) }} {{ $jenjang }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <p class="text-sm font-semibold text-slate-500">Potensi Hemat Rombel</p>
        <p class="text-2xl font-extrabold text-emerald-600 mt-2">{{ $f0($hematRombelRencana) }}</p>
        <p class="text-sm text-slate-500 mt-1">Dari {{ $pasanganTerpilih }} pasangan terpilih (tanpa tumpang tindih)</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <p class="text-sm font-semibold text-slate-500">Potensi Hemat Guru</p>
        <p class="text-2xl font-extrabold text-slate-900 mt-2">{{ $f0($hematGuruRencana) }}</p>
        <p class="text-sm text-slate-500 mt-1">Berdasarkan rasio 24 siswa/guru</p>
    </div>
</div>

@if (count($identik) > 0)
    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 mb-6">
        <p class="text-sm font-semibold text-slate-800">
            {{ count($identik) }} pasangan tercatat pada koordinat yang persis sama &mdash; dipisahkan dari daftar karena merupakan masalah kualitas data, bukan kandidat regrouping:
        </p>
        <ul class="mt-2 text-sm text-slate-600 space-y-1 list-disc pl-5">
            @foreach (array_slice($identik, 0, 6) as $d)
                <li>{{ $d['a']['nama'] }} &amp; {{ $d['b']['nama'] }} <span class="text-slate-400">({{ $d['a']['kecamatan'] }})</span></li>
            @endforeach
        </ul>
    </div>
@endif

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 mb-6">
    <h2 class="text-base font-bold text-slate-900">Pasangan Sekolah Terdekat (40 teratas)</h2>
    <p class="text-sm text-slate-500 mt-1 mb-4">Diurutkan dari jumlah siswa gabungan terkecil &mdash; kandidat penggabungan paling efisien lebih dulu</p>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <th class="py-3 pr-3">Jarak</th>
                    <th class="py-3 px-3">Sekolah A</th>
                    <th class="py-3 px-3 text-right">Siswa</th>
                    <th class="py-3 px-3">Sekolah B</th>
                    <th class="py-3 px-3 text-right">Siswa</th>
                    <th class="py-3 px-3 text-right">Gabungan</th>
                    <th class="py-3 px-3 text-right">Hemat Rombel</th>
                    <th class="py-3 px-3 text-right">Hemat Guru</th>
                    <th class="py-3 pl-3">Kecamatan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($pairs as $p)
                    <tr class="hover:bg-slate-50/60 {{ $p['satu_kecamatan'] ? '' : 'bg-amber-50/40' }}">
                        <td class="py-2.5 pr-3 font-semibold text-slate-700 whitespace-nowrap">{{ number_format($p['jarak'], 2, ',', '.') }} km</td>
                        <td class="py-2.5 px-3">
                            <p class="font-semibold text-slate-800">{{ $p['a']['nama'] }}</p>
                            <p class="text-xs text-slate-500">{{ $p['a']['status'] }} &middot; {{ $p['a']['guru'] }} guru &middot; {{ $p['a']['rombel'] }} rombel</p>
                        </td>
                        <td class="py-2.5 px-3 text-right text-slate-600">{{ $p['a']['siswa'] }}</td>
                        <td class="py-2.5 px-3">
                            <p class="font-semibold text-slate-800">{{ $p['b']['nama'] }}</p>
                            <p class="text-xs text-slate-500">{{ $p['b']['status'] }} &middot; {{ $p['b']['guru'] }} guru &middot; {{ $p['b']['rombel'] }} rombel</p>
                        </td>
                        <td class="py-2.5 px-3 text-right text-slate-600">{{ $p['b']['siswa'] }}</td>
                        <td class="py-2.5 px-3 text-right font-semibold text-slate-900">{{ $p['siswa_gabung'] }}</td>
                        <td class="py-2.5 px-3 text-right {{ $p['hemat_rombel'] > 0 ? 'text-emerald-600 font-semibold' : 'text-slate-400' }}">{{ $p['hemat_rombel'] }}</td>
                        <td class="py-2.5 px-3 text-right {{ $p['hemat_guru'] > 0 ? 'text-emerald-600 font-semibold' : 'text-slate-400' }}">{{ $p['hemat_guru'] }}</td>
                        <td class="py-2.5 pl-3 text-xs text-slate-500">
                            {{ $p['a']['kecamatan'] }}
                            @if (!$p['satu_kecamatan'])
                                <span class="block text-amber-600 font-semibold">beda kecamatan</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-8 text-center text-slate-500">Tidak ada pasangan sekolah dalam radius ini. Coba perbesar jarak maksimum.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="bg-amber-50/70 border border-amber-200 rounded-2xl p-5">
    <h2 class="text-sm font-bold text-amber-900 mb-2">Cara membaca</h2>
    <ul class="text-sm text-amber-900/80 space-y-1.5 list-disc pl-5">
        <li>Analisis ini menandai <b>kandidat</b>, bukan keputusan. Regrouping tetap wajib mempertimbangkan akses jalan, jarak tempuh siswa, kepadatan penduduk, dan status kepemilikan tanah.</li>
        <li>&ldquo;Hemat rombel&rdquo; = rombel A + rombel B &minus; rombel hasil penggabungan (dasar 28 siswa/rombel, minimal {{ $jenjang === 'SD' ? 6 : 3 }} rombel sesuai jumlah tingkat); &ldquo;hemat guru&rdquo; memakai rasio 24 siswa/guru.</li>
        <li>Baris berlatar kuning menandakan pasangan yang <b>berada di kecamatan berbeda</b> &mdash; butuh koordinasi kebijakan lintas wilayah.</li>
        <li>Jarak dihitung lurus (haversine sederhana), bukan jarak tempuh jalan.</li>
    </ul>
</div>
@endsection
