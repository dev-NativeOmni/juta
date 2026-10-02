<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'JUTA') }} - {{ __('Gerbang Akses Lembaga') }}</title>

    <!-- PWA & Apple iOS Metadata -->
    @include('partials.app-icons', ['themeColor' => '#ea580c'])

    <!-- Google Fonts: Inter & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Tailwind CSS & JS (via Vite) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * {
            scrollbar-width: none !important;
            -ms-overflow-style: none !important;
        }
        *::-webkit-scrollbar {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }
        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden !important;
        }
        .font-display {
            font-family: 'Outfit', sans-serif;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col justify-between selection:bg-orange-500 selection:text-white relative overflow-y-auto">
    <!-- Ambient Background Glow -->
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
        <div class="absolute -top-40 -left-40 w-96 h-96 bg-orange-600/15 rounded-full blur-3xl"></div>
        <div class="absolute top-1/3 -right-40 w-96 h-96 bg-amber-600/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 left-1/3 w-96 h-96 bg-emerald-600/10 rounded-full blur-3xl"></div>
    </div>

    <!-- Top Navigation Header -->
    <header class="relative z-10 w-full px-4 sm:px-8 py-4 flex items-center justify-between border-b border-slate-800/80 bg-slate-950/60 backdrop-blur-md">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-orange-600 to-amber-500 flex items-center justify-center shadow-lg shadow-orange-500/20 ring-1 ring-white/20">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <div>
                <h1 class="font-display font-bold text-lg text-white leading-tight">Portal Multi-Lembaga</h1>
                <p class="text-xs text-slate-400">JUTA Management System</p>
            </div>
        </div>

        <div>
            <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-400 hover:text-orange-400 transition-colors px-3 py-1.5 rounded-lg hover:bg-slate-900 border border-transparent hover:border-slate-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span>Halaman Utama</span>
            </a>
        </div>
    </header>

    <!-- Main Content Body -->
    <main class="relative z-10 flex-1 flex items-center justify-center p-4 sm:p-6 my-auto">
        <div class="w-full max-w-md">
            
            <!-- Session Status & Alert Messages -->
            @if (session('status'))
                <div class="mb-4 p-4 rounded-xl bg-emerald-950/60 border border-emerald-800/80 text-emerald-200 text-sm flex items-start gap-3 backdrop-blur-sm shadow-lg">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>{{ session('status') }}</div>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 p-4 rounded-xl bg-rose-950/60 border border-rose-800/80 text-rose-200 text-sm flex items-start gap-3 backdrop-blur-sm shadow-lg">
                    <svg class="w-5 h-5 text-rose-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Portal Card -->
            <div class="bg-slate-900/80 border border-slate-800/90 rounded-2xl p-6 sm:p-8 backdrop-blur-xl shadow-2xl ring-1 ring-white/5">
                
                <div class="text-center mb-6">
                    <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-orange-500/20 to-amber-500/10 border border-orange-500/30 flex items-center justify-center text-orange-400 shadow-inner">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                    </div>
                    <h2 class="font-display font-bold text-2xl text-white tracking-tight">Gerbang Akses Lembaga</h2>
                    <p class="text-sm text-slate-400 mt-1">Masukkan Kode Sekolah atau Pesantren Anda untuk mengakses portal tahfizh & akademik.</p>
                </div>

                <!-- Existing Active Institution Banner (If Already in Session) -->
                @if ($currentInstitution)
                    <div class="mb-6 p-4 rounded-xl bg-orange-950/40 border border-orange-800/50 text-slate-200 text-sm">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs uppercase tracking-wider font-semibold text-orange-400">Lembaga Terpilih</span>
                            <span class="inline-flex items-center gap-1 text-[11px] font-mono font-medium px-2 py-0.5 rounded-full bg-orange-500/20 text-orange-300 border border-orange-500/30">
                                {{ $currentInstitution->code }}
                            </span>
                        </div>
                        <div class="font-semibold text-base text-white truncate mb-3">
                            {{ $currentInstitution->name }}
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('login') }}" class="flex-1 text-center py-2 px-3 rounded-lg bg-orange-600 hover:bg-orange-500 text-white font-medium text-xs shadow-md shadow-orange-600/30 transition-all flex items-center justify-center gap-1.5">
                                <span>Lanjut ke Login</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                            <form action="{{ route('portal.exit') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="py-2 px-3 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 font-medium text-xs border border-slate-700 transition-colors">
                                    Ganti
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="relative flex items-center justify-center my-6">
                        <div class="border-t border-slate-800 w-full"></div>
                        <span class="bg-slate-900 px-3 text-xs text-slate-500 uppercase tracking-widest font-mono">Atau Masukkan Kode Lain</span>
                    </div>
                @endif

                <!-- Form Input Kode Lembaga -->
                <form action="{{ route('portal.verify') }}" method="POST" class="space-y-5">
                    @csrf
                    <div>
                        <label for="school_code" class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-2">
                            Kode Lembaga / Sekolah
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <input 
                                type="text" 
                                name="school_code" 
                                id="school_code" 
                                value="{{ old('school_code') }}"
                                required 
                                autofocus 
                                placeholder="Contoh: SMAIT01 atau DEFAULT"
                                class="w-full pl-11 pr-4 py-3 bg-slate-950/70 border border-slate-700/80 rounded-xl text-white placeholder-slate-500 text-sm uppercase tracking-wider font-mono focus:outline-none focus:ring-2 focus:ring-orange-500/50 focus:border-orange-500 transition-all"
                                style="text-transform: uppercase;"
                                oninput="this.value = this.value.toUpperCase();"
                            />
                        </div>
                        <p class="mt-1.5 text-[11px] text-slate-500">
                            Dapatkan kode lembaga dari pihak administrator sekolah/pesantren Anda.
                        </p>
                    </div>

                    <button 
                        type="submit" 
                        class="w-full py-3.5 px-4 rounded-xl bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-500 hover:to-amber-500 text-white font-semibold text-sm shadow-lg shadow-orange-600/30 hover:shadow-orange-600/50 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 focus:ring-offset-slate-900 transition-all flex items-center justify-center gap-2 group cursor-pointer"
                    >
                        <span>Verifikasi & Masuk Portal</span>
                        <svg class="w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </form>

            </div>

            <!-- Footer Info -->
            <div class="mt-6 text-center text-xs text-slate-500">
                &copy; {{ date('Y') }} JUTA (Jurnal Tahfizh & Adab). Multi-Tenancy Architecture.
            </div>
        </div>
    </main>
</body>
</html>
