@extends('layouts.app')

@section('content')
    <div class="mb-5 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Indikator APK/APM/APS</h2>
            <p class="text-sm text-slate-500 font-medium mt-1">Angka Partisipasi Kasar (APK), Murni (APM), dan Pure (APS) per kecamatan di Kabupaten Banjarnegara.</p>
        </div>
        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider bg-slate-100 rounded-full px-3 py-1.5 w-fit">
            Tahun {{ $ringkasan['tahun'] }}
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Sekolah</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($ringkasan['sekolah'], 0, ',', '.') }}</p>
                    <p class="text-[11px] font-semibold mt-1 text-slate-500">{{ number_format($ringkasan['akreditasi_a'], 0, ',', '.') }} terakreditasi A ({{ number_format($ringkasan['persen_akreditasi_a'], 1, ',', '.') }}%)</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 11V10.33"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Siswa</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($ringkasan['siswa'], 0, ',', '.') }}</p>
                    <p class="text-[11px] font-semibold mt-1 text-slate-500">Penduduk {{ number_format($ringkasan['penduduk'], 0, ',', '.') }} jiwa</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.436 60.436 0 00-.491 6.347M7.5 17.25h.484a60.47 60.47 0 006.316-1.026M12 3a75.408 75.408 0 014.865 5.184M19.5 9.75c.365 1.31.631 2.651.795 4.015M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.14-.786-3.07M15 19.128v.008h-6c-1.105 0-2.1-.424-2.86-1.118M9 6.75a3 3 0 116 0m-9 8.25a3 3 0 100-6 3 3 0 000 6zm0 0v1c0 1.105.895 2 2 2h4.5"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Guru</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($ringkasan['guru'], 0, ',', '.') }}</p>
                    <p class="text-[11px] font-semibold mt-1 text-slate-500">{{ number_format($ringkasan['guru_sertifikasi'], 0, ',', '.') }} bersertifikat ({{ number_format($ringkasan['persen_sertifikasi'], 1, ',', '.') }}%)</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.14-.786-3.07M15 19.128v.008h-6c-1.105 0-2.1-.424-2.86-1.118M9 6.75a3 3 0 116 0m-9 8.25a3 3 0 100-6 3 3 0 000 6zm0 0v1c0 1.105.895 2 2 2h4.5"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Rata-rata APK</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($sorotan['rataRata'], 1, ',', '.') }}<span class="text-lg text-slate-400">%</span></p>
                    <p class="text-[11px] font-semibold mt-1 text-slate-500">
                        Tertinggi: {{ $sorotan['tertinggi'] ? $sorotan['tertinggi']['name'] : '-' }}
                        &middot; Terendah: {{ $sorotan['terendah'] ? $sorotan['terendah']['name'] : '-' }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center shrink-0 border border-violet-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.281m5.94 2.28l-2.28 5.941"/></svg>
                </div>
            </div>
        </div>
    </div>

    @php
        $usiaData = collect($pendudukPerUsia)->map(fn ($v, $k) => ['name' => $k, 'value' => (int) $v])->values()->all();
        $siswaJenjang = collect($siswaPerJenjang)->map(fn ($v, $k) => ['name' => $k, 'value' => (int) $v])->values()->all();
        $sekolahJenjang = collect($sekolahPerJenjang)->map(fn ($v, $k) => ['name' => $k, 'value' => (int) $v])->values()->all();
        $guruStatus = collect($guruPerStatus)->map(fn ($v, $k) => ['name' => $k, 'value' => (int) $v])->values()->all();

        $tableRows = collect($rekap)->map(function ($r) {
            $r['nama'] = ucwords(strtolower($r['kecamatan']));
            return $r;
        })->sortByDesc('apk')->values()->all();
    @endphp

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4 sm:gap-6 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 xl:col-span-3">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Siswa per Jenjang</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Sebaran siswa tiap jenjang pendidikan</p>
                </div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total {{ number_format($ringkasan['siswa'], 0, ',', '.') }} Siswa</div>
            </div>
            <div class="relative h-80"><canvas id="jenjangChart"></canvas></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 xl:col-span-2">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Penduduk per Kelompok Usia</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Basis populasi usia sekolah</p>
                </div>
            </div>
            <div class="relative h-80"><canvas id="usiaChart"></canvas></div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4 sm:gap-6 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 xl:col-span-3">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Sekolah per Jenjang</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Jumlah satuan pendidikan tiap jenjang</p>
                </div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total {{ number_format($ringkasan['sekolah'], 0, ',', '.') }} Sekolah</div>
            </div>
            <div class="relative h-80"><canvas id="sekolahChart"></canvas></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 xl:col-span-2">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Guru per Status Kepegawaian</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Komposisi status guru</p>
                </div>
            </div>
            <div class="relative h-80"><canvas id="guruChart"></canvas></div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 mb-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">Indikator per Kecamatan</h3>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Diurutkan berdasarkan APK tertinggi. APK &ge; 100 menandakan partisipasi melebihi usia ideal.</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] uppercase tracking-wider text-slate-400 border-b border-slate-200">
                        <th class="py-3 pr-4 font-bold">Kecamatan</th>
                        <th class="py-3 pr-4 font-bold text-right">Sekolah</th>
                        <th class="py-3 pr-4 font-bold text-right">Siswa</th>
                        <th class="py-3 pr-4 font-bold text-right">Guru</th>
                        <th class="py-3 pr-4 font-bold text-right">APK (%)</th>
                        <th class="py-3 pr-4 font-bold text-right">APM (%)</th>
                        <th class="py-3 font-bold text-right">APS (%)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($tableRows as $r)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3 pr-4 font-semibold text-slate-800">{{ $r['nama'] }}</td>
                            <td class="py-3 pr-4 text-right font-semibold text-slate-600">{{ number_format($r['sekolah'], 0, ',', '.') }}</td>
                            <td class="py-3 pr-4 text-right font-semibold text-slate-600">{{ number_format($r['total_siswa'], 0, ',', '.') }}</td>
                            <td class="py-3 pr-4 text-right font-semibold text-slate-600">{{ number_format($r['guru'], 0, ',', '.') }}</td>
                            <td class="py-3 pr-4 text-right">
                                <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold {{ $r['apk'] >= 100 ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : ($r['apk'] >= 90 ? 'bg-amber-50 text-amber-700 border border-amber-100' : 'bg-red-50 text-red-700 border border-red-100') }}">{{ number_format($r['apk'], 1, ',', '.') }}</span>
                            </td>
                            <td class="py-3 pr-4 text-right">
                                <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold {{ $r['apm'] >= 90 ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : ($r['apm'] >= 75 ? 'bg-amber-50 text-amber-700 border border-amber-100' : 'bg-red-50 text-red-700 border border-red-100') }}">{{ number_format($r['apm'], 1, ',', '.') }}</span>
                            </td>
                            <td class="py-3 text-right">
                                <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold {{ $r['aps'] >= 90 ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : ($r['aps'] >= 75 ? 'bg-amber-50 text-amber-700 border border-amber-100' : 'bg-red-50 text-red-700 border border-red-100') }}">{{ number_format($r['aps'], 1, ',', '.') }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 mb-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-sm font-extrabold text-slate-900">Prestasi Siswa</h3>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Rekap prestasi siswa berdasarkan bidang, tingkat, dan peringkat</p>
            </div>
            <div class="text-xs font-bold uppercase tracking-wider text-slate-400 bg-slate-100 rounded-full px-3 py-1.5">Total {{ number_format($prestasi['total'] ?? 0, 0, ',', '.') }} Prestasi</div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-xl bg-slate-50/70 border border-slate-100 p-4">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-3">Per Bidang</p>
                <div class="flex flex-wrap gap-2">
                    @foreach (($prestasi['per_bidang'] ?? []) as $bidang => $jml)
                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 border border-blue-100">{{ $bidang }} ({{ $jml }})</span>
                    @endforeach
                    @if (empty($prestasi['per_bidang']))
                        <span class="text-xs text-slate-400 font-semibold">Belum ada data</span>
                    @endif
                </div>
            </div>
            <div class="rounded-xl bg-slate-50/70 border border-slate-100 p-4">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-3">Per Tingkat</p>
                <div class="flex flex-wrap gap-2">
                    @foreach (($prestasi['per_tingkat'] ?? []) as $tingkat => $jml)
                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-100">{{ $tingkat }} ({{ $jml }})</span>
                    @endforeach
                    @if (empty($prestasi['per_tingkat']))
                        <span class="text-xs text-slate-400 font-semibold">Belum ada data</span>
                    @endif
                </div>
            </div>
            <div class="rounded-xl bg-slate-50/70 border border-slate-100 p-4">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-3">Per Peringkat</p>
                <div class="flex flex-wrap gap-2">
                    @foreach (($prestasi['per_peringkat'] ?? []) as $peringkat => $jml)
                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-50 text-amber-700 border border-amber-100">{{ $peringkat }} ({{ $jml }})</span>
                    @endforeach
                    @if (empty($prestasi['per_peringkat']))
                        <span class="text-xs text-slate-400 font-semibold">Belum ada data</span>
                    @endif
                </div>
            </div>
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
        const siswaJenjang = @json($siswaJenjang);
        const usiaData = @json($usiaData);
        const sekolahJenjang = @json($sekolahJenjang);
        const guruStatus = @json($guruStatus);

        const ctx1 = document.getElementById('jenjangChart');
        if (ctx1) {
            new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: siswaJenjang.map(d => d.name),
                    datasets: [{ label: 'Siswa', data: siswaJenjang.map(d => d.value), backgroundColor: siswaJenjang.map((_, i) => palette[i % palette.length]), borderRadius: 4, maxBarThickness: 28 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a', padding: 12, cornerRadius: 10,
                            callbacks: { label: c => ` ${c.parsed.y.toLocaleString('id-ID')} siswa` }
                        }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.2)' }, ticks: { callback: v => v >= 1000 ? (v / 1000).toLocaleString('id-ID') + ' rb' : v } }
                    }
                }
            });
        }

        const ctx2 = document.getElementById('usiaChart');
        if (ctx2) {
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: usiaData.map(d => d.name),
                    datasets: [{ data: usiaData.map(d => d.value), backgroundColor: palette, borderWidth: 2, borderColor: '#ffffff' }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: { position: 'bottom', usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 12 },
                        tooltip: {
                            backgroundColor: '#0f172a', padding: 12, cornerRadius: 10,
                            callbacks: { label: c => ` ${c.label}: ${c.parsed.toLocaleString('id-ID')} jiwa` }
                        }
                    }
                }
            });
        }

        const ctx3 = document.getElementById('sekolahChart');
        if (ctx3) {
            new Chart(ctx3, {
                type: 'bar',
                data: {
                    labels: sekolahJenjang.map(d => d.name),
                    datasets: [{ label: 'Sekolah', data: sekolahJenjang.map(d => d.value), backgroundColor: '#2563eb', borderRadius: 4, maxBarThickness: 28 }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a', padding: 12, cornerRadius: 10,
                            callbacks: { label: c => ` ${c.parsed.x.toLocaleString('id-ID')} sekolah` }
                        }
                    },
                    scales: {
                        x: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.2)' } },
                        y: { grid: { display: false } }
                    }
                }
            });
        }

        const ctx4 = document.getElementById('guruChart');
        if (ctx4) {
            new Chart(ctx4, {
                type: 'doughnut',
                data: {
                    labels: guruStatus.map(d => d.name),
                    datasets: [{ data: guruStatus.map(d => d.value), backgroundColor: palette, borderWidth: 2, borderColor: '#ffffff' }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: { position: 'bottom', usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 12 },
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
