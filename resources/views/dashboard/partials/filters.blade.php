{{-- Bar filter global: semester, kecamatan, jenjang (auto-submit GET) --}}
<form method="GET"
      class="flex flex-wrap items-end gap-3 mb-5 px-4 sm:px-5 py-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs"
      id="filter-form">
    <div class="min-w-[170px]">
        <label for="semester" class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1">Semester</label>
        <select name="semester" id="semester"
                onchange="document.getElementById('filter-form').submit();"
                class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-sm text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition cursor-pointer">
            @foreach ($semesters as $value => $label)
                <option value="{{ $value }}" @selected($filters['semester'] === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="min-w-[170px]">
        <label for="kecamatan" class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1">Kecamatan</label>
        <select name="kecamatan" id="kecamatan"
                onchange="document.getElementById('filter-form').submit();"
                class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-sm text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition cursor-pointer">
            <option value="">Semua Kecamatan</option>
            @foreach ($kecamatanList as $kec)
                <option value="{{ $kec }}" @selected($filters['kecamatan'] === $kec)>{{ $kec }}</option>
            @endforeach
        </select>
    </div>

    @if (!($hideJenjang ?? false))
    <div class="min-w-[130px]">
        <label for="jenjang" class="block text-[11px] font-bold uppercase tracking-wide text-slate-400 mb-1">Jenjang</label>
        <select name="jenjang" id="jenjang"
                onchange="document.getElementById('filter-form').submit();"
                class="w-full px-3 py-2 rounded-xl border border-slate-300 bg-white text-sm text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition cursor-pointer">
            <option value="">Semua Jenjang</option>
            @foreach ($jenjangList as $jen)
                <option value="{{ $jen }}" @selected($filters['jenjang'] === $jen)>{{ $jen }}</option>
            @endforeach
        </select>
    </div>
    @endif

    <div>
        <button type="submit"
                class="flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold shadow-sm transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V15l-4 4v-4H5.414a1 1 0 01-.707-.293l-1-1A1 1 0 013 17V4z"/>
            </svg>
            Terapkan
        </button>
    </div>
</form>
