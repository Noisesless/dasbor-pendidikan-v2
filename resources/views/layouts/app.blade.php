<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50 overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Dashboard' }} - Dasbor Pendidikan Banjarnegara</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Leaflet CSS for Peta Sebaran -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>

    <!-- Alpine.js for lightweight UI interactivity -->
    <script defer src="https://unpkg.com/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <!-- Chart.js for beautiful analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        html, body {
            max-width: 100%;
            overflow-x: hidden;
        }
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="min-h-full antialiased text-slate-800 flex flex-col lg:flex-row bg-slate-50/80 w-full max-w-full overflow-x-hidden" x-data="{ sidebarOpen: false, profileOpen: false }">

    <!-- Mobile Sidebar Backdrop -->
    <div x-show="sidebarOpen"
         x-cloak
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-40 lg:hidden"
         @click="sidebarOpen = false"></div>

    <!-- Light Sidebar Navigation -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed inset-y-0 left-0 z-50 w-72 bg-white text-slate-700 flex flex-col transition-transform duration-300 ease-in-out border-r border-slate-200 shadow-sm">

        <!-- Brand Header (72px) -->
        <a href="{{ route('dashboard') }}" class="h-[72px] flex items-center px-5 border-b border-slate-100 bg-white gap-3.5 hover:bg-slate-50/60 transition group">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center shadow-sm shrink-0 group-hover:scale-105 transition-transform">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.579A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.579L12 14z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <div class="flex items-center gap-1.5">
                    <span class="font-extrabold text-lg tracking-tight text-slate-900 group-hover:text-blue-700 transition">Dasbor Pendidikan</span>
                </div>
                <p class="text-[11px] text-slate-400 truncate max-w-[155px] font-medium leading-tight">Kab. Banjarnegara</p>
            </div>
        </a>

        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto px-4 py-5 space-y-6 text-sm">
            <!-- Ringkasan -->
            <div>
                <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Ringkasan</p>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('dashboard') ? 'bg-blue-50 text-blue-700 font-semibold border border-blue-100/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('dashboard') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                </div>
            </div>

            <!-- Analisis -->
            <div>
                <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Analisis</p>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('demografi') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('demografi') ? 'bg-blue-50 text-blue-700 font-semibold border border-blue-100/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('demografi') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span>Demografi &amp; Akses</span>
                    </a>
                    <a href="{{ route('infrastruktur') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('infrastruktur') ? 'bg-blue-50 text-blue-700 font-semibold border border-blue-100/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('infrastruktur') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14m6-2h2m-2 0v-2m8 2v6m-8-6v6m8-6a2 2 0 002 2m-10-2a2 2 0 01-2 2m0 0v6m10-6v6"/>
                        </svg>
                        <span>Infrastruktur &amp; Efisiensi</span>
                    </a>
                    <a href="{{ route('guru') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('guru') ? 'bg-blue-50 text-blue-700 font-semibold border border-blue-100/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('guru') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>Profil Guru</span>
                    </a>
                </div>
            </div>

            <!-- Analisis Lanjutan -->
            <div>
                <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Analisis Lanjutan</p>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('gap') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('gap') ? 'bg-blue-50 text-blue-700 font-semibold border border-blue-100/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('gap') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-5m-3 3l3-3-3-3m-3 8H5v-2a3 3 0 00-3-3h1a3 3 0 00-3-3h1m0 0a2 2 0 104 0 2 2 0 01-4 0zm14-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span>Kesenjangan Guru</span>
                    </a>
                    <a href="{{ route('keluar') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('keluar') ? 'bg-blue-50 text-blue-700 font-semibold border border-blue-100/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('keluar') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Angka Keluar</span>
                    </a>
                    <a href="{{ route('literasi') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('literasi') ? 'bg-blue-50 text-blue-700 font-semibold border border-blue-100/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('literasi') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5c-1.746 0-3.332.477-4.5 1.253v13C4.168 18.477 5.754 18 7.5 18c1.746 0 3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <span>Literasi &amp; Perpustakaan</span>
                    </a>
                    <a href="{{ route('madrasah') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('madrasah') ? 'bg-blue-50 text-blue-700 font-semibold border border-blue-100/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('madrasah') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H15V20H19M8 20V13m4 7V9m4 11V5M3 21h18"/>
                        </svg>
                        <span>Pendidikan Madrasah</span>
                    </a>
                    <a href="{{ route('indikator') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('indikator') ? 'bg-blue-50 text-blue-700 font-semibold border border-blue-100/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('indikator') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6-2a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span>Indikator APK/APM/APS</span>
                    </a>
                    <a href="{{ route('paud') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('paud') ? 'bg-blue-50 text-blue-700 font-semibold border border-blue-100/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('paud') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1m-6-1a6 6 0 019 0v1h-3"/>
                        </svg>
                        <span>Akreditasi PAUD</span>
                    </a>
                    <a href="{{ route('walidata') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('walidata') ? 'bg-blue-50 text-blue-700 font-semibold border border-blue-100/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('walidata') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
                        </svg>
                        <span>Data Walidata</span>
                    </a>
                    <a href="{{ route('kebijakan') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('kebijakan') ? 'bg-blue-50 text-blue-700 font-semibold border border-blue-100/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('kebijakan') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/>
                        </svg>
                        <span>Analisis Kebijakan</span>
                    </a>
                    <a href="{{ route('data-health') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('data-health') ? 'bg-blue-50 text-blue-700 font-semibold border border-blue-100/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('data-health') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                        <span>Kualitas Data</span>
                    </a>
                    <a href="{{ route('sarana') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('sarana') ? 'bg-blue-50 text-blue-700 font-semibold border border-blue-100/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('sarana') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 21h19.5M3.75 21V9.75l8.25-6 8.25 6V21M9 21v-5.25h6V21"/>
                        </svg>
                        <span>Sarana &amp; Anggaran</span>
                    </a>
                    <a href="{{ route('regrouping') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('regrouping') ? 'bg-blue-50 text-blue-700 font-semibold border border-blue-100/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('regrouping') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-9L21 3m0 0-4.5 4.5M21 3H7.5"/>
                        </svg>
                        <span>Kandidat Regrouping</span>
                    </a>
                </div>
            </div>

            <!-- Peta -->
            <div>
                <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">Peta</p>
                <div class="mt-2 space-y-1">
                    <a href="{{ route('peta') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-medium transition {{ request()->routeIs('peta') ? 'bg-blue-50 text-blue-700 font-semibold border border-blue-100/80 shadow-xs' : 'text-slate-600 hover:bg-slate-100/80 hover:text-slate-900' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('peta') ? 'text-blue-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Peta Sebaran</span>
                    </a>
                </div>
            </div>
        </nav>

        <!-- User Profile & Logout -->
        <div class="p-4 border-t border-slate-100 bg-slate-50/70">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 overflow-hidden">
                    <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-sm shrink-0 border border-blue-200">
                        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                    </div>
                    <div class="truncate">
                        <p class="text-xs font-bold text-slate-900 truncate">{{ auth()->user()->name ?? 'Admin' }}</p>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-100 text-blue-700">Administrator</span>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                            title="Keluar Sistem"
                            class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 lg:pl-72 flex flex-col min-h-screen w-full max-w-full min-w-0 overflow-x-hidden">

        <!-- Top Navbar Header (72px) -->
        <header class="h-[72px] bg-white/95 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-30 px-3 sm:px-6 lg:px-8 flex items-center justify-between transition-all w-full max-w-full min-w-0 gap-2">
            <div class="flex items-center gap-2 sm:gap-3.5 min-w-0 flex-1">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-xl text-slate-600 hover:bg-slate-100 transition cursor-pointer shrink-0">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div class="min-w-0 flex-1">
                    <h1 class="text-sm sm:text-base md:text-lg font-bold text-slate-900 tracking-tight leading-tight truncate">{{ $title ?? 'Dasbor Pendidikan' }}</h1>
                    <p class="text-[11px] text-slate-500 hidden sm:block font-medium truncate">Dinas Pendidikan, Kepemudaan dan Olahraga Kab. Banjarnegara</p>
                </div>
            </div>

            <!-- User Profile Dropdown -->
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                <div class="relative" @click.away="profileOpen = false">
                    <button type="button"
                            @click="profileOpen = !profileOpen"
                            aria-haspopup="true"
                            :aria-expanded="profileOpen"
                            class="flex items-center gap-2.5 pl-1.5 pr-2.5 py-1.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20 transition shadow-2xs group cursor-pointer">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-blue-600 via-indigo-600 to-sky-500 text-white flex items-center justify-center font-bold text-xs shadow-xs ring-1 ring-blue-100 group-hover:scale-105 transition-transform shrink-0">
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="hidden md:flex flex-col text-left">
                            <span class="text-xs font-bold text-slate-800 truncate max-w-[130px] leading-tight">
                                {{ auth()->user()->name ?? 'Admin' }}
                            </span>
                            <span class="text-[10px] font-medium text-blue-600">Administrator</span>
                        </div>
                        <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-slate-600 transition-transform duration-200 shrink-0 ml-0.5"
                             :class="profileOpen ? 'rotate-180 text-blue-600' : ''"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>

                    <!-- Dropdown Menu Card -->
                    <div x-show="profileOpen"
                         x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                         class="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-200/90 py-2 z-50 divide-y divide-slate-100">

                        <!-- Header Detail Akun -->
                        <div class="px-4 py-3 bg-slate-50/70">
                            <div class="flex items-center gap-3">
                                <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center font-bold text-base shadow-sm ring-2 ring-white shrink-0">
                                    {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                                </div>
                                <div class="overflow-hidden min-w-0 flex-1">
                                    <p class="text-xs font-bold text-slate-900 truncate leading-snug">
                                        {{ auth()->user()->name ?? 'Admin' }}
                                    </p>
                                    <p class="text-[11px] text-slate-500 truncate font-medium">
                                        {{ auth()->user()->email ?? 'admin@banjarnegara.go.id' }}
                                    </p>
                                    <span class="inline-flex items-center px-1.5 py-0.5 mt-1 rounded text-[9px] font-bold bg-blue-100 text-blue-700">
                                        Administrator
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Logout -->
                        <div class="p-1.5">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit"
                                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-left text-rose-600 hover:bg-rose-50 transition group cursor-pointer">
                                    <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center transition group-hover:bg-rose-200 shrink-0">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-rose-600">Keluar Sistem</p>
                                        <p class="text-[11px] text-slate-400 truncate">Akhiri sesi saat ini</p>
                                    </div>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 p-3 sm:p-6 lg:p-8">
            @if (session('success'))
                <div class="mb-4 flex items-center gap-3 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm font-semibold">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 px-4 py-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm font-semibold">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="px-3 sm:px-6 lg:px-8 py-5 border-t border-slate-200/70 bg-white/60">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-2 text-[11px] text-slate-400 font-medium">
                <p>&copy; {{ date('Y') }} Dinas Pendidikan, Kepemudaan dan Olahraga Kab. Banjarnegara</p>
                <p class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                    Data Dapodik &amp; BPS &middot; Semester 2024/2025 &ndash; 2026/2027
                </p>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
