@extends('layouts.app')

@section('content')
@php
    $f1 = fn ($v) => number_format((float) $v, 1, ',', '.');
    $warna = fn ($s) => $s >= 90 ? 'text-emerald-600' : ($s >= 70 ? 'text-amber-600' : 'text-red-600');
@endphp

<div class="mb-5 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Kualitas &amp; Kepatuhan Data</h1>
        <p class="text-sm text-slate-500 mt-1">Skor kelengkapan tiap sekolah &mdash; semester {{ $semester }}</p>
    </div>
    <form method="GET" class="flex items-center gap-2 shrink-0">
        <label for="semester" class="text-sm font-medium text-slate-500">Semester</label>
        <select name="semester" id="semester" onchange="this.form.submit()"
                class="rounded-lg border border-slate-200 bg-white text-sm font-semibold text-slate-700 px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-200">
            @foreach ($semesters as $s)
                <option value="{{ $s }}" @selected($s === $semester)>{{ $s }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5 mb-6">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <p class="text-sm font-semibold text-slate-500">Rata-rata Skor Data</p>
        <p class="text-2xl font-extrabold {{ $warna($rataSkor) }} mt-2">{{ $f1($rataSkor) }}<span class="text-sm text-slate-400"> / 100</span></p>
        <p class="text-sm text-slate-500 mt-1">{{ number_format($totalRows, 0, ',', '.') }} sekolah</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <p class="text-sm font-semibold text-slate-500">Sekolah Data Lengkap</p>
        <p class="text-2xl font-extrabold text-emerald-600 mt-2">{{ number_format($sehat, 0, ',', '.') }}</p>
        <p class="text-sm text-slate-500 mt-1">{{ $f1($totalRows > 0 ? $sehat / $totalRows * 100 : 0) }}% dari total</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <p class="text-sm font-semibold text-slate-500">Sekolah Bermasalah</p>
        <p class="text-2xl font-extrabold text-red-600 mt-2">{{ number_format($bermasalah, 0, ',', '.') }}</p>
        <p class="text-sm text-slate-500 mt-1">Perlu tindak lanjut verifikasi</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <p class="text-sm font-semibold text-slate-500">Masalah Terbanyak</p>
        <p class="text-lg font-extrabold text-slate-900 mt-2 leading-tight">{{ array_key_first($problemCount) ? ($labelMasalah[array_key_first($problemCount)] ?? '-') : '-' }}</p>
        <p class="text-sm text-slate-500 mt-1">{{ array_key_first($problemCount) ? number_format($problemCount[array_key_first($problemCount)], 0, ',', '.') . ' sekolah' : '' }}</p>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4 sm:gap-5 mb-6">
    <div class="xl:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <h2 class="text-base font-bold text-slate-900">Sebaran Masalah Data</h2>
        <p class="text-sm text-slate-500 mt-1 mb-4">Jumlah sekolah yang terdampak tiap jenis masalah</p>
        <div class="h-[320px]"><canvas id="chartMasalah"></canvas></div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <h2 class="text-base font-bold text-slate-900">Skor per Jenjang</h2>
        <p class="text-sm text-slate-500 mt-1 mb-4">Urut dari terendah</p>
        <div class="overflow-y-auto max-h-[320px]">
            <table class="w-full text-sm">
                <tbody class="divide-y divide-slate-100">
                    @foreach ($perJenjang as $j)
                        <tr>
                            <td class="py-2 pr-3 font-semibold text-slate-800">{{ $j['jenjang'] }}</td>
                            <td class="py-2 px-3 text-right text-xs text-slate-500">{{ $j['sekolah'] }} sekolah</td>
                            <td class="py-2 pl-3 text-right font-bold {{ $warna($j['rata_skor']) }}">{{ $f1($j['rata_skor']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 mb-6">
    <h2 class="text-base font-bold text-slate-900">Skor per Kecamatan</h2>
    <p class="text-sm text-slate-500 mt-1 mb-4">Urut dari kualitas data terendah &mdash; sasaran pembinaan Dapodik</p>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <th class="py-3 pr-3">Kecamatan</th>
                    <th class="py-3 px-3 text-right">Sekolah</th>
                    <th class="py-3 px-3 text-right">Bermasalah</th>
                    <th class="py-3 pl-3 text-right">Rata Skor</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($perKecamatan as $k)
                    <tr class="hover:bg-slate-50/60">
                        <td class="py-2 pr-3 font-semibold text-slate-800">{{ $k['kecamatan'] }}</td>
                        <td class="py-2 px-3 text-right text-slate-600">{{ $k['sekolah'] }}</td>
                        <td class="py-2 px-3 text-right text-slate-600">{{ $k['bermasalah'] }}</td>
                        <td class="py-2 pl-3 text-right font-bold {{ $warna($k['rata_skor']) }}">{{ $f1($k['rata_skor']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 mb-6">
    <p class="text-sm text-slate-700">
        <b>{{ $madrasahTanpaGuru }} madrasah</b> (MI/MTs/MA) belum memiliki data guru pada feed yang tersedia.
        Ini <b>tidak dihitung sebagai pelanggaran</b> kualitas data karena kewenangan pendataan berada di Kemenag &mdash;
        angka guru madrasah dapat dilihat di halaman Sarana atau data Kemenag.
    </p>
</div>

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 mb-6">
    <h2 class="text-base font-bold text-slate-900">Rekonsiliasi Antar Sumber</h2>
    <p class="text-sm text-slate-500 mt-1 mb-4">Selisih jumlah murid Dapodik vs sumber lain &mdash; makin kecil, makin layak dipercaya</p>
    @if ($rekon === [])
        <p class="text-sm text-slate-500">Tidak ada data pembanding yang dapat dipasangkan.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                        <th class="py-3 pr-3">Jenjang</th>
                        <th class="py-3 px-3 text-right">Dapodik</th>
                        <th class="py-3 px-3 text-right">Pembanding</th>
                        <th class="py-3 px-3">Sumber Pembanding</th>
                        <th class="py-3 pl-3 text-right">Selisih</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($rekon as $r)
                        <tr>
                            <td class="py-2 pr-3 font-semibold text-slate-800">{{ $r['jenjang'] }}</td>
                            <td class="py-2 px-3 text-right text-slate-600">{{ number_format($r['dapodik'], 0, ',', '.') }}</td>
                            <td class="py-2 px-3 text-right text-slate-600">{{ number_format($r['pembanding'], 0, ',', '.') }}</td>
                            <td class="py-2 px-3 text-xs text-slate-500">{{ $r['sumber'] }}</td>
                            <td class="py-2 pl-3 text-right font-semibold {{ abs($r['selisih']) > 10 ? 'text-red-600' : 'text-slate-600' }}">
                                {{ $r['selisih'] > 0 ? '+' : '' }}{{ $f1($r['selisih']) }}%
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
    <h2 class="text-base font-bold text-slate-900">100 Sekolah dengan Skor Terendah</h2>
    <p class="text-sm text-slate-500 mt-1 mb-4">Daftar kerja verifikasi lapangan</p>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="sticky top-0 bg-white">
                <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <th class="py-3 pr-3">NPSN</th>
                    <th class="py-3 px-3">Sekolah</th>
                    <th class="py-3 px-3">Jenjang</th>
                    <th class="py-3 px-3">Kecamatan</th>
                    <th class="py-3 px-3 text-right">Siswa</th>
                    <th class="py-3 px-3 text-right">Guru</th>
                    <th class="py-3 px-3 text-right">Skor</th>
                    <th class="py-3 pl-3">Masalah</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($rows as $r)
                    <tr class="hover:bg-slate-50/60">
                        <td class="py-2 pr-3 font-mono text-xs text-slate-500">{{ $r['npsn'] }}</td>
                        <td class="py-2 px-3 font-semibold text-slate-800">{{ $r['nama_sekolah'] }}</td>
                        <td class="py-2 px-3 text-slate-600">{{ $r['jenjang'] }}</td>
                        <td class="py-2 px-3 text-slate-600">{{ $r['kecamatan'] }}</td>
                        <td class="py-2 px-3 text-right text-slate-600">{{ $r['siswa'] }}</td>
                        <td class="py-2 px-3 text-right text-slate-600">{{ $r['guru'] }}</td>
                        <td class="py-2 px-3 text-right font-bold {{ $warna($r['skor']) }}">{{ $r['skor'] }}</td>
                        <td class="py-2 pl-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach ($r['masalah'] as $m)
                                    <span class="inline-flex items-center rounded-md bg-red-50 px-1.5 py-0.5 text-[11px] font-medium text-red-700 border border-red-100">
                                        {{ $labelMasalah[$m] ?? $m }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
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
    const labels = @json(array_values(array_map(fn ($k) => $labelMasalah[$k] ?? $k, array_keys($problemCount))));
    const values = @json(array_values($problemCount));
    const ctx = document.getElementById('chartMasalah');
    if (ctx && labels.length) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah sekolah',
                    data: values,
                    backgroundColor: '#ef4444',
                    borderRadius: 4,
                    maxBarThickness: 26,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8' } },
                    y: { grid: { display: false }, ticks: { color: '#475569', font: { size: 11 } } }
                }
            }
        });
    }
});
</script>
@endpush
