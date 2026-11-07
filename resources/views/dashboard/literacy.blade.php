@extends('layouts.app')

@section('content')
    @php
        $maxVisitor = 0;
        foreach ($monthlyVisitors as $mv) {
            $maxVisitor = max($maxVisitor, $mv['total']);
        }
    @endphp

    <div class="mb-5 flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">Literasi &amp; Perpustakaan</h2>
            <p class="text-sm text-slate-500 font-medium mt-1">Kunjungan, keanggotaan, dan koleksi Perpustakaan Daerah Kabupaten Banjarnegara.</p>
        </div>
        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider bg-slate-100 rounded-full px-3 py-1.5 w-fit">
            Disarpus Kabupaten Banjarnegara
        </div>
    </div>

    <form method="GET" class="flex flex-wrap items-end gap-3 mb-5 px-4 sm:px-5 py-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs">
        <div class="min-w-40">
            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1">Tahun</label>
            <select name="tahun" onchange="this.form.submit()" class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-sm text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition cursor-pointer">
                <option value="">Semua Tahun</option>
                @foreach ($availableYears as $yr)
                    <option value="{{ $yr }}" @selected(request('tahun') == $yr)>{{ $yr }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 sm:gap-5 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Kunjungan</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($totalVisitors, 0, ',', '.') }}</p>
                    @if ($maxVisitorMonth)
                        <p class="text-[11px] font-semibold mt-1 text-slate-500">Tertinggi: {{ $maxVisitorMonth['bulan'] }}</p>
                    @endif
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 border border-blue-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 11V10.33"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Anggota Baru</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($totalMembers, 0, ',', '.') }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0 border border-emerald-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 00-3.741-.479c-.71-.353-1.48-.634-2.28-.834m0 0a9.067 9.067 0 01-2.596-1.375m2.596 1.375c-.692.255-1.44.415-2.22.415a5.663 5.663 0 01-2.687-.663m2.687.663c0-1.048.265-2.046.735-2.918M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm3.75 3a.75.75 0 100-1.5.75.75 0 000 1.5zm0 1.5a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5zm3 1.5a.75.75 0 100-1.5.75.75 0 000 1.5zm0 1.5a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Koleksi Perpustakaan</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($totalCollections, 0, ',', '.') }}</p>
                    <p class="text-[11px] font-semibold mt-1 text-slate-500">{{ count($categoryData) }} kategori koleksi</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Puncak Kunjungan</p>
                    @if ($maxVisitorMonth)
                        <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">{{ number_format($maxVisitorMonth['total'], 0, ',', '.') }}</p>
                        <p class="text-[11px] font-semibold mt-1 text-slate-500">{{ $maxVisitorMonth['bulan'] }}</p>
                    @else
                        <p class="text-3xl font-extrabold text-slate-900 mt-2 tracking-tight">-</p>
                    @endif
                </div>
                <div class="w-12 h-12 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center shrink-0 border border-violet-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 011.689-1.979l2.203-2.203L21.75 15.75M3.75 21h16.5a.75.75 0 00.75-.75V5.25a.75.75 0 00-.75-.75H3.75a.75.75 0 00-.75.75v15c0 .414.336.75.75.75z"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4 sm:gap-6 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 xl:col-span-3">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Tren Kunjungan Bulanan</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Jumlah pengunjung perpustakaan per bulan</p>
                </div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Pelajar &middot; Mahasiswa &middot; Karyawan &middot; Umum</div>
            </div>
            <div class="relative h-80"><canvas id="visitorChart"></canvas></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 xl:col-span-2">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Keanggotaan Baru</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Anggota baru yang terdaftar per bulan</p>
                </div>
            </div>
            <div class="relative h-80"><canvas id="memberChart"></canvas></div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-4 sm:gap-6 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 xl:col-span-2">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Komposisi Koleksi</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Distribusi koleksi per kategori</p>
                </div>
            </div>
            <div class="relative h-80"><canvas id="collectionChart"></canvas></div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 xl:col-span-3">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-extrabold text-slate-900">Detail Kategori Koleksi</h3>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">Jumlah judul koleksi per kategori</p>
                </div>
            </div>
            <div class="space-y-3 max-h-80 overflow-y-auto pr-1">
                @php $idx = 0; @endphp
                @foreach ($categoryData as $cat => $val)
                    @php $idx++; $pct = $totalCollections > 0 ? round($val / $totalCollections * 100, 1) : 0; @endphp
                    <div class="flex items-center justify-between gap-3 px-4 py-3 rounded-xl bg-slate-50/70 border border-slate-100">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: {{ ['#2563eb', '#10b981', '#f59e0b', '#94a3b8', '#8b5cf6', '#ef4444', '#0ea5e9', '#f97316', '#84cc16', '#e11d48', '#7c3aed', '#0d9488'][$idx - 1 % 12] }}"></span>
                            <span class="text-sm font-semibold text-slate-700 truncate">{{ $cat }}</span>
                        </div>
                        <div class="flex items-center gap-4 shrink-0">
                            <span class="text-sm font-bold text-slate-900">{{ number_format($val, 0, ',', '.') }}</span>
                            <span class="text-xs font-bold text-slate-400">{{ $pct }}%</span>
                        </div>
                    </div>
                @endforeach
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

        const palette = ['#2563eb', '#10b981', '#f59e0b', '#94a3b8', '#8b5cf6', '#ef4444', '#0ea5e9', '#f97316', '#84cc16', '#e11d48', '#7c3aed', '#0d9488'];
        const monthlyVisitors = @json($monthlyVisitors);
        const monthlyMembers = @json($monthlyMembers);
        const categoryData = @json($categoryData);
        const totalCollections = {{ $totalCollections }};

        const ctx1 = document.getElementById('visitorChart');
        if (ctx1) {
            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: monthlyVisitors.map(d => d.bulan),
                    datasets: [
                        { label: 'Pelajar', data: monthlyVisitors.map(d => d.pelajar), borderColor: '#2563eb', backgroundColor: 'rgba(37, 99, 235, 0.1)', borderWidth: 2, pointRadius: 3, pointBackgroundColor: '#2563eb', tension: 0.35, fill: true },
                        { label: 'Mahasiswa', data: monthlyVisitors.map(d => d.mahasiswa), borderColor: '#10b981', backgroundColor: 'rgba(16, 185, 129, 0.1)', borderWidth: 2, pointRadius: 3, pointBackgroundColor: '#10b981', tension: 0.35, fill: true },
                        { label: 'Karyawan', data: monthlyVisitors.map(d => d.karyawan), borderColor: '#f59e0b', backgroundColor: 'rgba(245, 158, 11, 0.1)', borderWidth: 2, pointRadius: 3, pointBackgroundColor: '#f59e0b', tension: 0.35, fill: true },
                        { label: 'Umum', data: monthlyVisitors.map(d => d.umum), borderColor: '#8b5cf6', backgroundColor: 'rgba(139, 92, 246, 0.1)', borderWidth: 2, pointRadius: 3, pointBackgroundColor: '#8b5cf6', tension: 0.35, fill: true }
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
                        y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.2)' }, ticks: { callback: v => v >= 1000 ? (v / 1000).toLocaleString('id-ID') + ' rb' : v } }
                    }
                }
            });
        }

        const ctx2 = document.getElementById('memberChart');
        if (ctx2) {
            new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: monthlyMembers.map(d => d.bulan),
                    datasets: [{ label: 'Anggota Baru', data: monthlyMembers.map(d => d.anggota), backgroundColor: '#2563eb', borderRadius: 6, maxBarThickness: 24 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a', padding: 12, cornerRadius: 10,
                            callbacks: { label: c => ` ${c.parsed.y.toLocaleString('id-ID')} anggota baru` }
                        }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.2)' } }
                    }
                }
            });
        }

        const ctx3 = document.getElementById('collectionChart');
        if (ctx3) {
            new Chart(ctx3, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(categoryData),
                    datasets: [{ data: Object.values(categoryData), backgroundColor: palette, borderWidth: 2, borderColor: '#ffffff' }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: { position: 'bottom', usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 12 },
                        tooltip: {
                            backgroundColor: '#0f172a', padding: 12, cornerRadius: 10,
                            callbacks: {
                                label: c => {
                                    const pct = totalCollections > 0 ? (c.parsed / totalCollections * 100).toFixed(1) : 0;
                                    return ` ${c.label}: ${c.parsed.toLocaleString('id-ID')} (${pct}%)`;
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