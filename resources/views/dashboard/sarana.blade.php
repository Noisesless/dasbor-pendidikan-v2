@extends('layouts.app')

@section('content')
@php
    $f0 = fn ($v) => number_format((float) $v, 0, ',', '.');
    $f1 = fn ($v) => number_format((float) $v, 1, ',', '.');
    $rp = fn ($jt) => $jt >= 1_000
        ? 'Rp ' . number_format($jt / 1_000, 2, ',', '.') . ' miliar'
        : 'Rp ' . number_format($jt, 1, ',', '.') . ' juta';
@endphp

<div class="mb-5">
    <h1 class="text-2xl font-bold text-slate-900">Sarana &amp; Estimasi Anggaran</h1>
    <p class="text-sm text-slate-500 mt-1">Backlog ruang kelas dari data Walidata, kebutuhan dihitung dari Dapodik {{ $semester }}</p>
</div>

{{-- Parameter --}}
<form method="GET" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 mb-6">
    <h2 class="text-sm font-bold text-slate-900 mb-3">Parameter perhitungan</h2>
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div>
            <label for="ringan" class="block text-xs font-semibold text-slate-500 mb-1">Rehab ringan (jt/ruang)</label>
            <input type="number" step="1" min="0" name="ringan" id="ringan" value="{{ (int) $unit['ringan'] }}"
                   class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-200">
        </div>
        <div>
            <label for="sedang" class="block text-xs font-semibold text-slate-500 mb-1">Rehab sedang (jt/ruang)</label>
            <input type="number" step="1" min="0" name="sedang" id="sedang" value="{{ (int) $unit['sedang'] }}"
                   class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-200">
        </div>
        <div>
            <label for="berat" class="block text-xs font-semibold text-slate-500 mb-1">Rehab berat (jt/ruang)</label>
            <input type="number" step="1" min="0" name="berat" id="berat" value="{{ (int) $unit['berat'] }}"
                   class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-200">
        </div>
        <div>
            <label for="baru" class="block text-xs font-semibold text-slate-500 mb-1">Bangun baru (jt/ruang)</label>
            <input type="number" step="1" min="0" name="baru" id="baru" value="{{ (int) $unit['baru'] }}"
                   class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-200">
        </div>
        <div>
            <label for="rasio" class="block text-xs font-semibold text-slate-500 mb-1">Target siswa/rombel</label>
            <input type="number" step="1" min="20" max="40" name="rasio" id="rasio" value="{{ (int) $targetRombel }}"
                   class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-200">
        </div>
    </div>
    <div class="flex items-center gap-3 mt-4">
        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 transition">Hitung ulang</button>
        <a href="{{ route('sarana') }}" class="text-sm font-medium text-slate-500 hover:text-slate-800">Reset</a>
    </div>
</form>

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5 mb-6">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <p class="text-sm font-semibold text-slate-500">Estimasi Kebutuhan Anggaran</p>
        <p class="text-2xl font-extrabold text-slate-900 mt-2">{{ $rp($totalBiaya) }}</p>
        <p class="text-sm text-slate-500 mt-1">Rehab + bangun baru</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <p class="text-sm font-semibold text-slate-500">Ruang Kelas Rusak Sedang/Berat</p>
        <p class="text-2xl font-extrabold text-red-600 mt-2">{{ $f0(array_sum(array_column($sarana, 'rusak_sedang_berat'))) }}</p>
        <p class="text-sm text-slate-500 mt-1">Ruang dari {{ $f0(array_sum(array_column($sarana, 'total'))) }} total</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <p class="text-sm font-semibold text-slate-500">Defisit Ruang Kelas</p>
        <p class="text-2xl font-extrabold text-amber-600 mt-2">{{ $f0(array_sum(array_column($sarana, 'defisit'))) }}</p>
        <p class="text-sm text-slate-500 mt-1">Target {{ (int) $targetRombel }} siswa/rombel</p>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <p class="text-sm font-semibold text-slate-500">Rasio Ruang Tidak Layak</p>
        <p class="text-2xl font-extrabold text-slate-900 mt-2">
            {{ $f1(array_sum(array_column($sarana, 'total')) > 0 ? array_sum(array_map(fn ($r) => $r['ringan'] + $r['sedang'] + $r['berat'], $sarana)) / array_sum(array_column($sarana, 'total')) * 100 : 0) }}%
        </p>
        <p class="text-sm text-slate-500 mt-1">Rusak ringan + sedang + berat</p>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 mb-6">
    <h2 class="text-base font-bold text-slate-900">Kondisi &amp; Kebutuhan Ruang Kelas per Jenjang</h2>
    <p class="text-sm text-slate-500 mt-1 mb-4">Sumber kondisi: Walidata 2023 &middot; siswa/rombel: Dapodik {{ $semester }}</p>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs font-bold text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <th class="py-3 pr-3">Jenjang</th>
                    <th class="py-3 px-3 text-right">Total Ruang</th>
                    <th class="py-3 px-3 text-right">Baik</th>
                    <th class="py-3 px-3 text-right">Rusak Ringan</th>
                    <th class="py-3 px-3 text-right">Rusak Sedang</th>
                    <th class="py-3 px-3 text-right">Rusak Berat</th>
                    <th class="py-3 px-3 text-right">% Tidak Layak</th>
                    <th class="py-3 px-3 text-right">Siswa</th>
                    <th class="py-3 px-3 text-right">Kebutuhan Ruang</th>
                    <th class="py-3 px-3 text-right">Defisit</th>
                    <th class="py-3 pl-3 text-right">Estimasi Biaya</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($sarana as $r)
                    <tr class="hover:bg-slate-50/60">
                        <td class="py-2.5 pr-3 font-semibold text-slate-900">{{ $r['jenjang'] }}</td>
                        <td class="py-2.5 px-3 text-right text-slate-700">{{ $f0($r['total']) }}</td>
                        <td class="py-2.5 px-3 text-right text-slate-600">{{ $f0($r['baik']) }}</td>
                        <td class="py-2.5 px-3 text-right text-slate-600">{{ $f0($r['ringan']) }}</td>
                        <td class="py-2.5 px-3 text-right font-semibold text-amber-600">{{ $f0($r['sedang']) }}</td>
                        <td class="py-2.5 px-3 text-right font-semibold text-red-600">{{ $f0($r['berat']) }}</td>
                        <td class="py-2.5 px-3 text-right text-slate-600">{{ $f1($r['persen_rusak']) }}%</td>
                        <td class="py-2.5 px-3 text-right text-slate-600">{{ $f0($r['siswa']) }}</td>
                        <td class="py-2.5 px-3 text-right text-slate-600">{{ $f0($r['kebutuhan_ruang']) }}</td>
                        <td class="py-2.5 px-3 text-right font-semibold {{ $r['defisit'] > 0 ? 'text-amber-600' : 'text-emerald-600' }}">{{ $f0($r['defisit']) }}</td>
                        <td class="py-2.5 pl-3 text-right font-semibold text-slate-900">{{ $rp($r['total_biaya']) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-slate-200">
                    <td class="py-3 pr-3 font-bold text-slate-900">Total</td>
                    <td class="py-3 px-3 text-right font-bold">{{ $f0(array_sum(array_column($sarana, 'total'))) }}</td>
                    <td class="py-3 px-3 text-right font-bold">{{ $f0(array_sum(array_column($sarana, 'baik'))) }}</td>
                    <td class="py-3 px-3 text-right font-bold">{{ $f0(array_sum(array_column($sarana, 'ringan'))) }}</td>
                    <td class="py-3 px-3 text-right font-bold text-amber-700">{{ $f0(array_sum(array_column($sarana, 'sedang'))) }}</td>
                    <td class="py-3 px-3 text-right font-bold text-red-700">{{ $f0(array_sum(array_column($sarana, 'berat'))) }}</td>
                    <td class="py-3 px-3"></td>
                    <td class="py-3 px-3 text-right font-bold">{{ $f0(array_sum(array_column($sarana, 'siswa'))) }}</td>
                    <td class="py-3 px-3 text-right font-bold">{{ $f0(array_sum(array_column($sarana, 'kebutuhan_ruang'))) }}</td>
                    <td class="py-3 px-3 text-right font-bold text-amber-700">{{ $f0(array_sum(array_column($sarana, 'defisit'))) }}</td>
                    <td class="py-3 pl-3 text-right font-extrabold text-slate-900">{{ $rp($totalBiaya) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-4 sm:gap-5 mb-6">
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <h2 class="text-base font-bold text-slate-900">Komposisi Kondisi Ruang Kelas</h2>
        <p class="text-sm text-slate-500 mt-1 mb-4">Walidata 2023 per jenjang</p>
        <div class="h-[320px]"><canvas id="chartKondisi"></canvas></div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <h2 class="text-base font-bold text-slate-900">Tren Ketersediaan &amp; Kerusakan</h2>
        <p class="text-sm text-slate-500 mt-1 mb-4">Ruang kelas kabupaten 2020&ndash;2024</p>
        <div class="h-[320px]"><canvas id="chartTren"></canvas></div>
    </div>
</div>

<div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
    <h2 class="text-base font-bold text-slate-900">Rincian Estimasi Biaya</h2>
    <p class="text-sm text-slate-500 mt-1 mb-4">Mengikuti parameter di halaman ini</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
        @foreach ([
            ['Rehab Ringan', $biaya['ringan'], array_sum(array_column($sarana, 'ringan')), $unit['ringan']],
            ['Rehab Sedang', $biaya['sedang'], array_sum(array_column($sarana, 'sedang')), $unit['sedang']],
            ['Rehab Berat', $biaya['berat'], array_sum(array_column($sarana, 'berat')), $unit['berat']],
            ['Bangun Baru', $biaya['baru'], array_sum(array_column($sarana, 'defisit')), $unit['baru']],
        ] as [$label, $total, $qty, $satuan])
            <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-4">
                <p class="text-xs font-semibold text-slate-500">{{ $label }}</p>
                <p class="text-xl font-extrabold text-slate-900 mt-1">{{ $rp($total) }}</p>
                <p class="text-xs text-slate-500 mt-1">{{ $f0($qty) }} ruang &times; Rp {{ $f0($satuan) }} jt</p>
            </div>
        @endforeach
    </div>
</div>

<div class="bg-amber-50/70 border border-amber-200 rounded-2xl p-5 mt-6">
    <h2 class="text-sm font-bold text-amber-900 mb-2">Catatan</h2>
    <ul class="text-sm text-amber-900/80 space-y-1.5 list-disc pl-5">
        <li>Kondisi ruang kelas bersumber dari Walidata (agregat kabupaten) &mdash; <b>tidak tersedia per sekolah</b>, sehingga tidak bisa dipetakan.</li>
        <li>Kebutuhan ruang kelas = siswa &divide; target siswa per rombel; defisit = kebutuhan &minus; ruang kelas tersedia.</li>
        <li>Unit cost adalah <b>parameter yang dapat diubah</b>, bukan angka resmi RAB. Ganti sesuai standar harga setempat sebelum dipakai untuk penganggaran.</li>
    </ul>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sarana = @json($sarana);
    const ctx = document.getElementById('chartKondisi');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: sarana.map(r => r.jenjang),
                datasets: [
                    { label: 'Baik', data: sarana.map(r => r.baik), backgroundColor: '#16a34a', borderRadius: 3 },
                    { label: 'Rusak Ringan', data: sarana.map(r => r.ringan), backgroundColor: '#facc15', borderRadius: 3 },
                    { label: 'Rusak Sedang', data: sarana.map(r => r.sedang), backgroundColor: '#f97316', borderRadius: 3 },
                    { label: 'Rusak Berat', data: sarana.map(r => r.berat), backgroundColor: '#dc2626', borderRadius: 3 },
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, font: { size: 11 } } } },
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { color: '#64748b' } },
                    y: { stacked: true, beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8' } }
                }
            }
        });
    }

    const trendLabels = @json($trendLabels);
    const tren = @json($tren);
    const ctx2 = document.getElementById('chartTren');
    if (ctx2) {
        const meta = {
            tersedia: ['Tersedia', '#2563eb'],
            layak: ['Layak', '#16a34a'],
            rusak: ['Rusak Sedang/Berat', '#ef4444'],
            perlu_rehab: ['Perlu Rehabilitasi', '#f59e0b'],
        };
        new Chart(ctx2, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: Object.keys(tren).map(function (key) {
                    return {
                        label: meta[key][0],
                        data: tren[key],
                        borderColor: meta[key][1],
                        backgroundColor: meta[key][1] + '22',
                        borderWidth: 2,
                        tension: 0.3,
                        pointRadius: 3,
                    };
                })
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, font: { size: 11 } } } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8' } },
                    x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                }
            }
        });
    }
});
</script>
@endpush
