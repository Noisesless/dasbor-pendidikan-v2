@extends('layouts.app')

@section('content')
@php
    $f0 = fn ($v) => number_format((float) $v, 0, ',', '.');
    $f1 = fn ($v) => number_format((float) $v, 1, ',', '.');
    $rp = fn ($jt) => $jt >= 1_000
        ? 'Rp ' . number_format($jt / 1_000, 2, ',', '.') . ' miliar'
        : 'Rp ' . number_format($jt, 1, ',', '.') . ' juta';

    $gaya = [
        'Kritis' => ['chip' => 'bg-rose-50 text-rose-700 ring-rose-200', 'bar' => 'bg-rose-500', 'teks' => 'text-rose-600'],
        'Tinggi' => ['chip' => 'bg-amber-50 text-amber-700 ring-amber-200', 'bar' => 'bg-amber-500', 'teks' => 'text-amber-600'],
        'Sedang' => ['chip' => 'bg-sky-50 text-sky-700 ring-sky-200', 'bar' => 'bg-sky-500', 'teks' => 'text-sky-600'],
    ];

    $kelompok = collect($rekomendasi)->groupBy('prioritas');
    $jumlahKritis = collect($rekomendasi)->where('prioritas', 'Kritis')->count();
@endphp

<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Rekomendasi untuk Kepala Dinas</h1>
        <p class="text-sm text-slate-500 mt-1">Disusun dari Dapodik {{ $fakta['semesterLabel'] }}, Walidata 2020&ndash;2024, dan BPS 2018&ndash;2024</p>
    </div>
    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
        {{ count($rekomendasi) }} rekomendasi &middot; {{ $jumlahKritis }} kritis
    </span>
</div>

{{-- Ringkasan --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5 mb-6">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <p class="text-sm font-semibold text-slate-500">Rekomendasi Kritis</p>
        <p class="text-2xl font-extrabold text-rose-600 mt-2">{{ $jumlahKritis }}</p>
        <p class="text-sm text-slate-500 mt-1">Perlu keputusan tahun ini</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <p class="text-sm font-semibold text-slate-500">Kebutuhan Anggaran Sarana</p>
        <p class="text-2xl font-extrabold text-slate-900 mt-2">{{ $rp($fakta['sarana']['totalBiaya']) }}</p>
        <p class="text-sm text-slate-500 mt-1">Rehab + bangun ruang kelas</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <p class="text-sm font-semibold text-slate-500">Siswa Belajar di Luar Usia</p>
        <p class="text-2xl font-extrabold text-amber-600 mt-2">{{ $f0($fakta['siswaOverage']) }}</p>
        <p class="text-sm text-slate-500 mt-1">{{ $f1($fakta['pctOverage']) }}% dari {{ $f0($fakta['totalSiswa']) }} siswa</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <p class="text-sm font-semibold text-slate-500">Kecamatan Prioritas</p>
        <p class="text-2xl font-extrabold text-slate-900 mt-2">{{ count($fakta['peringkatKecamatan']) }}</p>
        <p class="text-sm text-slate-500 mt-1">Melampaui minimal dua ambang</p>
    </div>
</div>

{{-- Tiga keputusan --}}
<h2 class="text-base font-bold text-slate-900 mb-3">Tiga keputusan yang perlu diambil tahun ini</h2>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-5 mb-8">
    @foreach ($sorotan as $r)
        @php $g = $gaya[$r['prioritas']]; @endphp
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="h-1 {{ $g['bar'] }}"></div>
            <div class="p-5">
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 {{ $g['chip'] }}">{{ $r['prioritas'] }}</span>
                <h3 class="text-sm font-bold text-slate-900 mt-3 leading-snug">{{ $r['judul'] }}</h3>
                <p class="text-xs text-slate-500 mt-2 leading-relaxed">{{ $r['temuan'][0] }}</p>
                @if ($r['biaya'])
                    <p class="text-sm font-extrabold text-slate-900 mt-3">{{ $r['biaya'] }}</p>
                @endif
            </div>
        </div>
    @endforeach
</div>

{{-- Daftar lengkap --}}
@php $no = 1; @endphp
@foreach (['Kritis', 'Tinggi', 'Sedang'] as $tingkat)
    @continue (! $kelompok->has($tingkat))
    @php $g = $gaya[$tingkat]; @endphp

    <div class="flex items-center gap-3 mb-3 mt-8">
        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 {{ $g['chip'] }}">{{ $tingkat }}</span>
        <h2 class="text-base font-bold text-slate-900">{{ $kelompok[$tingkat]->count() }} rekomendasi</h2>
        <div class="flex-1 h-px bg-slate-200"></div>
    </div>

    <div class="space-y-4">
        @foreach ($kelompok[$tingkat] as $r)
            <article class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="p-5 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="inline-flex items-center justify-center rounded-lg bg-slate-900 text-white text-xs font-bold w-6 h-6">{{ $no++ }}</span>
                                <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ $r['kategori'] }}</span>
                                <span class="rounded-md px-2 py-0.5 text-xs font-bold ring-1 {{ $g['chip'] }}">{{ $r['prioritas'] }}</span>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 leading-snug">{{ $r['judul'] }}</h3>
                        </div>
                        <a href="{{ $r['tautan'] }}"
                           class="shrink-0 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition">
                            {{ $r['labelTautan'] }} &rarr;
                        </a>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-5">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400 mb-2">Temuan</p>
                            <ul class="space-y-1.5">
                                @foreach ($r['temuan'] as $t)
                                    <li class="flex gap-2 text-sm text-slate-600 leading-relaxed">
                                        <span class="mt-1.5 h-1.5 w-1.5 rounded-full bg-slate-300 shrink-0"></span>
                                        <span>{{ $t }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400 mb-2">Yang perlu dilakukan</p>
                            <ol class="space-y-1.5">
                                @foreach ($r['tindakan'] as $i => $t)
                                    <li class="flex gap-2 text-sm text-slate-700 leading-relaxed">
                                        <span class="shrink-0 mt-0.5 inline-flex items-center justify-center rounded-full bg-blue-50 text-blue-700 text-xs font-bold w-5 h-5">{{ $i + 1 }}</span>
                                        <span>{{ $t }}</span>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    </div>

                    <div class="mt-5 rounded-xl bg-slate-50 px-4 py-3">
                        <p class="text-sm text-slate-700"><span class="font-semibold text-slate-900">Dampak yang diharapkan:</span> {{ $r['dampak'] }}</p>
                    </div>

                    <dl class="grid grid-cols-2 lg:grid-cols-4 gap-4 mt-4 pt-4 border-t border-slate-100">
                        <div>
                            <dt class="text-xs font-semibold text-slate-400">Kebutuhan anggaran</dt>
                            <dd class="text-sm font-semibold text-slate-800 mt-0.5">{{ $r['biaya'] ?? 'Tidak ada biaya tambahan' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-400">Tenggat</dt>
                            <dd class="text-sm font-semibold text-slate-800 mt-0.5">{{ $r['tenggat'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-400">Penanggung jawab</dt>
                            <dd class="text-sm font-semibold text-slate-800 mt-0.5">{{ $r['unit'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold text-slate-400">Dasar data</dt>
                            <dd class="text-sm text-slate-600 mt-0.5">{{ $r['dasar'] }}</dd>
                        </div>
                    </dl>
                </div>
            </article>
        @endforeach
    </div>
@endforeach

{{-- Matriks --}}
<h2 class="text-base font-bold text-slate-900 mt-10 mb-3">Matriks ringkas</h2>
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold w-10">No</th>
                    <th class="px-4 py-3 text-left font-semibold">Rekomendasi</th>
                    <th class="px-4 py-3 text-left font-semibold">Kategori</th>
                    <th class="px-4 py-3 text-left font-semibold">Prioritas</th>
                    <th class="px-4 py-3 text-right font-semibold">Anggaran</th>
                    <th class="px-4 py-3 text-left font-semibold">Tenggat</th>
                    <th class="px-4 py-3 text-left font-semibold">Unit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($rekomendasi as $i => $r)
                    @php $g = $gaya[$r['prioritas']]; @endphp
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-4 py-3 text-slate-400 font-semibold">{{ $i + 1 }}</td>
                        <td class="px-4 py-3 font-semibold text-slate-800 max-w-md">{{ $r['judul'] }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $r['kategori'] }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-md px-2 py-0.5 text-xs font-bold ring-1 {{ $g['chip'] }}">{{ $r['prioritas'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-800 whitespace-nowrap">{{ $r['biaya'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-500 whitespace-nowrap">{{ $r['tenggat'] }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $r['unit'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- Prioritas kecamatan --}}
<h2 class="text-base font-bold text-slate-900 mt-10 mb-1">Kecamatan yang perlu didahulukan</h2>
<p class="text-sm text-slate-500 mb-3">{{ count($fakta['peringkatKecamatan']) }} kecamatan melampaui minimal dua ambang batas; enam teratas ditampilkan di bawah. Satu kecamatan bisa memenuhi beberapa ambang sekaligus, jadi penanganannya perlu lintas bidang.</p>
<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold">Kecamatan</th>
                    <th class="px-4 py-3 text-right font-semibold">Siswa</th>
                    <th class="px-4 py-3 text-right font-semibold">Rasio</th>
                    <th class="px-4 py-3 text-right font-semibold">Sertifikasi</th>
                    <th class="px-4 py-3 text-right font-semibold">Overage</th>
                    <th class="px-4 py-3 text-left font-semibold">Ambang terlampaui</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse (array_slice($fakta['peringkatKecamatan'], 0, 6, true) as $k)
                    <tr class="hover:bg-slate-50/70">
                        <td class="px-4 py-3 font-semibold text-slate-800">{{ $k['nama'] }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ $f0($k['siswa']) }}</td>
                        <td class="px-4 py-3 text-right font-semibold {{ ($k['rasio'] ?? 0) > 20 ? 'text-rose-600' : 'text-slate-600' }}">
                            {{ $k['rasio'] !== null ? $f1($k['rasio']) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ $k['sertifikasi'] !== null ? $f1($k['sertifikasi']) . '%' : '—' }}</td>
                        <td class="px-4 py-3 text-right text-slate-600">{{ $k['overage'] !== null ? $f1($k['overage']) . '%' : '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($k['faktor'] as $f)
                                    <span class="rounded-md bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700 ring-1 ring-rose-200">{{ $f }}</span>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-slate-400">Tidak ada kecamatan yang melewati ambang batas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Metodologi --}}
<div class="mt-8 rounded-2xl border border-slate-200/80 bg-white p-5">
    <h3 class="text-sm font-bold text-slate-900 mb-2">Cara membaca &amp; keterbatasan</h3>
    <ul class="space-y-1.5 text-sm text-slate-600 list-disc pl-5">
        <li>Semua angka dihitung ulang dari basis data aplikasi ini, bukan diketik manual. Membuka halaman terkait lewat tautan di tiap kartu akan menampilkan rincian yang sama.</li>
        <li>Prioritas ditentukan oleh besarnya dampak pada layanan belajar, bukan oleh nilai uang semata. Kritis berarti membiarkan keadaan berjalan menimbulkan risiko keselamatan atau kegagalan layanan.</li>
        <li>Estimasi anggaran sarana memakai unit cost yang dapat diubah di halaman Sarana &amp; Anggaran; angka ini belum RAB dan belum direkonsiliasi dengan SIMU-DIK.</li>
        <li>Rasio siswa per guru memakai seluruh jenjang, termasuk PAUD dan PKBM, sehingga tidak sepenuhnya setara dengan rasio resmi per jenjang.</li>
        <li>Data guru madrasah belum tersedia di dinas, sehingga rekomendasi untuk MI/MTs/MA baru sebatas penetapan kebutuhan data.</li>
    </ul>
</div>
@endsection
