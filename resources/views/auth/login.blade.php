<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Dasbor Pendidikan Banjarnegara</title>

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="icon" href="{{ asset('favicon-32.png') }}" sizes="32x32" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <meta name="theme-color" content="#2563eb">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen antialiased bg-slate-100 text-slate-800 relative overflow-hidden flex items-center justify-center p-4 sm:p-6">

    <!-- Ambient gradient blobs -->
    <div class="absolute -top-32 -left-32 w-96 h-96 rounded-full bg-blue-200/50 blur-3xl"></div>
    <div class="absolute -bottom-32 -right-32 w-[28rem] h-[28rem] rounded-full bg-indigo-200/50 blur-3xl"></div>
    <div class="absolute top-1/3 right-1/4 w-64 h-64 rounded-full bg-sky-100/60 blur-3xl hidden md:block"></div>

    <div class="relative z-10 w-full max-w-md">

        <!-- Brand -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-200 mb-4">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z M12 14l6.16-3.422a12.083 12.083 0 01.665 6.579A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.579L12 14z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Dasbor Pendidikan</h1>
            <p class="text-sm text-slate-500 font-medium mt-1">Kabupaten Banjarnegara</p>
        </div>

        <!-- Login card -->
        <div class="bg-white/95 backdrop-blur-md rounded-2xl shadow-xl shadow-slate-200/70 border border-slate-200/80 p-7 sm:p-9">
            <div class="mb-6">
                <h2 class="text-lg font-bold text-slate-900">Selamat Datang</h2>
                <p class="text-[13px] text-slate-500 mt-1">Masuk untuk mengakses dasbor analitik pendidikan.</p>
            </div>

            @if (session('error') || isset($throttleError))
                <div class="mb-5 flex items-center gap-2.5 px-4 py-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-semibold">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ $throttleError ?? session('error') }}
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-bold text-slate-600 mb-1.5">Alamat Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                           placeholder="admin@banjarnegara.go.id"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 bg-white text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition
                           @error('email') border-rose-400 @enderror">
                    @error('email')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $errors->first('email') }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-600 mb-1.5">Kata Sandi</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password"
                           placeholder="••••••••"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 bg-white text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 transition
                           @error('password') border-rose-400 @enderror">
                    @error('password')
                        <p class="mt-1.5 text-xs text-rose-600 font-medium">{{ $errors->first('password') }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2.5 cursor-pointer select-none">
                    <input type="checkbox" name="remember" value="1"
                           class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500/30">
                    <span class="text-[13px] text-slate-600 font-medium">Ingat saya</span>
                </label>

                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 focus:ring-2 focus:ring-blue-500/40 text-white text-sm font-bold shadow-sm transition">
                    <span>Masuk</span>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>

        <p class="text-center text-[11px] text-slate-400 font-medium mt-6">
            &copy; {{ date('Y') }} Dinas Pendidikan, Kepemudaan dan Olahraga Kab. Banjarnegara
        </p>
    </div>
</body>
</html>
