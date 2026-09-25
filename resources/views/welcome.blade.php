@php
    try {
        $logo = class_exists(\App\Models\Setting::class) && \Illuminate\Support\Facades\Schema::hasTable('settings') 
            ? \App\Models\Setting::get('logo') 
            : null;
        $namaInstansi = class_exists(\App\Models\Setting::class) && \Illuminate\Support\Facades\Schema::hasTable('settings') 
            ? \App\Models\Setting::get('nama_instansi') 
            : null;
    } catch (\Throwable $e) {
        $logo = null;
        $namaInstansi = null;
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=5">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>TAD Management System — Platform Pelacakan Hafalan & Murajaah Qur'an Modern</title>
        @include('partials.app-icons')

        <!-- Theme Initialization Script (Default: Light Mode unless explicitly set to dark) -->
        <script>
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>

        <!-- Google Fonts: Outfit -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .font-display {
                font-family: 'Outfit', sans-serif;
            }
            /* Autofill compatibility: keep background clean & text sharp */
            input:-webkit-autofill,
            input:-webkit-autofill:hover, 
            input:-webkit-autofill:focus {
                -webkit-text-fill-color: #0f172a !important;
                -webkit-box-shadow: 0 0 0px 1000px #f8fafc inset !important;
                transition: background-color 5000s ease-in-out 0s;
            }
            /* Bulletproof Monolith Layout & Mobile First Architecture */
            .monolith-shell {
                display: grid;
                grid-template-columns: 1fr;
                gap: 1.25rem;
                align-items: start;
                width: 100%;
            }
            @media (min-width: 1024px) {
                .monolith-shell {
                    grid-template-columns: 290px minmax(0, 1fr);
                    gap: 1.5rem;
                }
            }
            .monolith-top-cards {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.75rem;
            }
            @media (min-width: 1280px) {
                .monolith-top-cards {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                    gap: 1rem;
                }
            }
            .monolith-bento-split {
                display: grid;
                grid-template-columns: 1fr;
                gap: 1.25rem;
            }
            @media (min-width: 1024px) {
                .monolith-bento-split {
                    grid-template-columns: minmax(0, 1.85fr) minmax(0, 1fr);
                    gap: 1.5rem;
                }
            }
            .monolith-bottom-badges {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.75rem;
            }
            @media (min-width: 640px) {
                .monolith-bottom-badges {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                    gap: 1rem;
                }
            }
            .monolith-glass-panel {
                background: rgba(255, 255, 255, 0.45);
                backdrop-filter: blur(24px);
                -webkit-backdrop-filter: blur(24px);
                border: 1px solid rgba(255, 255, 255, 0.75);
                border-radius: 1.75rem;
                box-shadow: 0 20px 50px rgba(0, 0, 0, 0.04);
            }
            @media (min-width: 640px) {
                .monolith-glass-panel {
                    border-radius: 2rem;
                }
            }
            .dark .monolith-glass-panel {
                background: rgba(24, 24, 27, 0.65);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
            .monolith-card-sm {
                background: rgba(255, 255, 255, 0.55);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.85);
                border-radius: 1.25rem;
                padding: 1rem;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.03);
            }
            @media (min-width: 640px) {
                .monolith-card-sm {
                    border-radius: 1.5rem;
                    padding: 1.25rem;
                }
            /* Premium Frosted Glassmorphism with Anti-Glare Optics */
            .glass-liquid-card {
                background: rgba(255, 255, 255, 0.42);
                backdrop-filter: blur(24px) saturate(180%);
                -webkit-backdrop-filter: blur(24px) saturate(180%);
                border: 1px solid rgba(255, 255, 255, 0.65);
                box-shadow: 0 15px 35px -10px rgba(15, 23, 42, 0.06), inset 0 1px 1px 0 rgba(255, 255, 255, 0.85);
            }
            .dark .glass-liquid-card {
                background: rgba(24, 24, 27, 0.72);
                backdrop-filter: blur(24px) saturate(180%);
                -webkit-backdrop-filter: blur(24px) saturate(180%);
                border: 1px solid rgba(255, 255, 255, 0.12);
                box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.8), inset 0 1px 1px 0 rgba(255, 255, 255, 0.08);
            }

            .glass-liquid-inner {
                background: rgba(255, 255, 255, 0.48);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid rgba(255, 255, 255, 0.6);
                box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.02);
            }
            .dark .glass-liquid-inner {
                background: rgba(0, 0, 0, 0.4);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid rgba(255, 255, 255, 0.08);
                box-shadow: none;
            }

            .dark .monolith-card-sm {
                background: rgba(24, 24, 27, 0.65);
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
        </style>
    </head>
    <body class="bg-[#f1f5f9] dark:bg-[#09090b] text-zinc-800 dark:text-zinc-100 font-sans antialiased selection:bg-orange-500 selection:text-white relative overflow-x-hidden min-h-screen transition-colors duration-300">

        <!-- Full-screen Preloader / Intro Logo Reveal -->
        <div x-data="{ loading: true, logoVisible: false }" 
             x-init="setTimeout(() => logoVisible = true, 100); setTimeout(() => loading = false, 2000)"
             x-show="loading"
             x-transition:leave="transition ease-in-out duration-1000"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-105 pointer-events-none"
             class="fixed inset-0 bg-[#09090b] z-[9999] flex flex-col items-center justify-center overflow-hidden">
            <!-- Glowing Grid background in preloader -->
            <div class="absolute inset-0 bg-grid-pattern opacity-10 pointer-events-none"></div>
            
            <!-- Outer Glowing Ambient Light -->
            <div class="absolute w-[600px] h-[600px] bg-teal-500/10 rounded-full blur-[120px] animate-pulse"></div>
            
            <!-- Large Centered Ring & Logo Container -->
            <div class="relative z-10 flex flex-col items-center gap-8">
                <!-- Glowing Ring -->
                <div :class="logoVisible ? 'scale-100 opacity-100' : 'scale-75 opacity-0'"
                     class="w-64 h-64 sm:w-80 sm:h-80 rounded-full border border-teal-500/35 flex items-center justify-center shadow-[0_0_80px_rgba(13,148,136,0.3)] transition-all duration-1000 ease-out p-0 overflow-hidden">
                    <img src="{{ asset('images/logo_alazhar7.png') }}" alt="Logo SMA Islam Al Azhar 7" class="w-full h-full object-cover">
                </div>
                
                <!-- Large Text Reveal -->
                <span :class="logoVisible ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                      class="font-black text-3xl sm:text-5xl tracking-widest text-white uppercase transition-all duration-1000 delay-300 ease-out text-center">
                    Al Azhar <span class="text-amber-500">7</span>
                </span>
            </div>
        </div>

        <!-- Root Scroll Container with Alpine ScrollLayout -->
        <div x-data="scrollLayout" x-init="@if($errors->any()) openLoginModal() @endif" class="relative z-10 flex flex-col min-h-screen">
            <!-- Sticky Floating Header Navbar (When Scrolled) -->
            <header x-show="isScrolled" 
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-4"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-4"
                    class="fixed top-0 inset-x-0 z-50 bg-[#f1f5f9]/85 dark:bg-zinc-900/85 border-b border-white/60 dark:border-white/10 shadow-lg shadow-slate-900/5 py-2 sm:py-2.5 backdrop-blur-2xl transition-colors duration-300">
                <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 flex items-center justify-between gap-2">
                    <!-- Brand Logo: Pure School Logo & Pure Gemilang Banner Logo -->
                    <div class="flex items-center gap-1.5 sm:gap-2.5 shrink-0">
                        <img src="{{ asset('images/logo_alazhar7.png') }}" alt="Logo SMA Islam Al Azhar 7" class="h-7 w-7 xs:h-8 xs:w-8 sm:h-9 sm:w-9 md:h-10 md:w-10 object-contain shrink-0 drop-shadow-sm">
                        <img src="{{ asset('images/logo-gemilang-banner.png') }}" alt="Logo Gemilang" class="h-5 sm:h-7 md:h-8 max-w-[95px] xs:max-w-[125px] sm:max-w-[160px] md:max-w-[200px] object-contain drop-shadow-sm shrink-0">
                    </div>

                    <!-- Desktop Pill Menu Navigation (Visible on Widescreen Desktops >= 1280px to ensure zero collision on iPads/Tablets in landscape) -->
                    <nav class="hidden xl:flex items-center gap-1 p-1 rounded-full bg-white/40 dark:bg-black/50 border border-white/60 dark:border-white/10 text-xs font-semibold text-zinc-700 dark:text-zinc-300 backdrop-blur-xl shadow-sm shrink-0">
                        <a href="#fitur" class="px-3.5 py-1.5 rounded-full hover:text-orange-600 dark:hover:text-white hover:bg-white/60 dark:hover:bg-white/10 transition-all duration-150">Fitur Utama</a>
                        <a href="#keunggulan" class="px-3.5 py-1.5 rounded-full hover:text-orange-600 dark:hover:text-white hover:bg-white/60 dark:hover:bg-white/10 transition-all duration-150">Keunggulan</a>
                        <a href="#sebaran" class="px-3.5 py-1.5 rounded-full hover:text-orange-600 dark:hover:text-white hover:bg-white/60 dark:hover:bg-white/10 transition-all duration-150">Konektivitas</a>
                        <a href="#simulator" class="px-3.5 py-1.5 rounded-full hover:text-orange-600 dark:hover:text-white hover:bg-white/60 dark:hover:bg-white/10 transition-all duration-150">Demo Interaktif</a>
                        <a href="#faq" class="px-3.5 py-1.5 rounded-full hover:text-orange-600 dark:hover:text-white hover:bg-white/60 dark:hover:bg-white/10 transition-all duration-150">Tanya Jawab</a>
                    </nav>

                    <!-- Auth Actions & Theme Switcher -->
                    <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
                        <!-- Theme Toggle Button -->
                        <button @click="toggleTheme()" 
                                type="button"
                                aria-label="Toggle Theme"
                                class="p-1.5 sm:p-2 rounded-full bg-white/40 dark:bg-white/10 hover:bg-white/80 dark:hover:bg-white/20 border border-white/60 dark:border-white/15 text-zinc-700 dark:text-amber-300 shadow-sm backdrop-blur-xl transition-all duration-200 hover:scale-110 active:scale-95 flex items-center justify-center shrink-0">
                            <!-- Sun icon -->
                            <svg x-show="isDark" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                            </svg>
                            <!-- Moon icon -->
                            <svg x-show="!isDark" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-zinc-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                            </svg>
                        </button>

                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-bold px-3 py-1.5 sm:px-4 sm:py-2 text-[10px] sm:text-xs uppercase tracking-tight sm:tracking-wider rounded-full transition-all duration-200 shadow-md shadow-orange-500/25 hover:scale-105 active:scale-95 whitespace-nowrap shrink-0">
                                    Dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" 
                                   @click.prevent="openLoginModal()"
                                   class="bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-bold px-3 py-1.5 sm:px-4 sm:py-2 text-[10px] sm:text-xs uppercase tracking-tight sm:tracking-wider rounded-full transition-all duration-200 shadow-md shadow-orange-500/25 hover:scale-105 active:scale-95 whitespace-nowrap shrink-0">
                                    Masuk
                                </a>
                            @endauth
                        @endif
                    </div>
                </div>
            </header>

            <!-- ========================================================================= -->
            <!-- SECTION 1: CLEAN MINIMALIST HERO (CRYSTAL FROSTED GLASS & NATURAL SUNSET) -->
            <!-- ========================================================================= -->
            <section class="w-full relative min-h-[100dvh] flex flex-col justify-between py-4 sm:py-7 px-3 sm:px-6 lg:px-8 overflow-hidden bg-cover bg-center bg-no-repeat"
                     style="background-image: url('{{ asset('images/school_sunset_bg.jpg') }}');">
                
                <!-- Layer 1: Elegant Glassmorphic Ambient Tone (No Blinding White Wash) -->
                <div class="absolute inset-0 pointer-events-none z-0">
                    <!-- Subtle Contrast Gradient Overlay (Preserves Natural Photo & Depth) -->
                    <div class="absolute inset-0 bg-gradient-to-b from-black/20 via-black/10 to-[#f1f5f9] dark:from-black/50 dark:via-black/40 dark:to-[#09090b]"></div>
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,transparent_0%,rgba(15,23,42,0.18)_100%)] dark:bg-[radial-gradient(circle_at_center,transparent_0%,rgba(9,9,11,0.65)_100%)]"></div>
                    
                    <!-- Duotone Gemilang Ambient Glows -->
                    <div class="absolute -top-32 left-1/4 w-[500px] h-[500px] bg-orange-500/15 dark:bg-orange-500/18 rounded-full blur-[140px]"></div>
                    <div class="absolute top-1/3 -right-32 w-[550px] h-[550px] bg-blue-500/10 dark:bg-blue-600/20 rounded-full blur-[150px]"></div>
                </div>

                <!-- Top Navbar: Frosted Glass Pill Header -->
                <header class="max-w-6xl mx-auto w-full relative z-20">
                    <div class="p-1.5 sm:p-2.5 rounded-full bg-white/40 dark:bg-zinc-900/60 backdrop-blur-2xl border border-white/60 dark:border-white/15 shadow-xl shadow-black/5 flex items-center justify-between gap-1.5 sm:gap-3 transition-colors duration-300">
                        
                        <!-- Left Brand Logo: Pure School Logo & Pure Gemilang Banner Logo (Responsive scaling & zero collision) -->
                        <div class="flex items-center gap-1.5 sm:gap-2.5 pl-1 sm:pl-2 shrink-0">
                            <img src="{{ asset('images/logo_alazhar7.png') }}" alt="Logo SMA Islam Al Azhar 7" class="h-7 w-7 xs:h-8 xs:w-8 sm:h-9 sm:w-9 md:h-10 md:w-10 object-contain shrink-0 drop-shadow-sm">
                            <img src="{{ asset('images/logo-gemilang-banner.png') }}" alt="Logo Gemilang" class="h-5 sm:h-7 md:h-8 max-w-[95px] xs:max-w-[125px] sm:max-w-[160px] md:max-w-[200px] object-contain drop-shadow-sm brightness-105 dark:brightness-110 shrink-0">
                        </div>

                        <!-- Center Navigation Links (Visible on Widescreen Desktops >= 1280px to guarantee zero overlapping on Tablets/iPads in landscape) -->
                        <nav class="hidden xl:flex items-center gap-1 text-xs font-semibold text-zinc-800 dark:text-zinc-200 shrink-0">
                            <a href="#fitur" class="px-3.5 py-1.5 rounded-full hover:text-orange-600 dark:hover:text-white hover:bg-white/50 dark:hover:bg-white/10 transition-all duration-150">Fitur Utama</a>
                            <a href="#keunggulan" class="px-3.5 py-1.5 rounded-full hover:text-orange-600 dark:hover:text-white hover:bg-white/50 dark:hover:bg-white/10 transition-all duration-150">Keunggulan</a>
                            <a href="#sebaran" class="px-3.5 py-1.5 rounded-full hover:text-orange-600 dark:hover:text-white hover:bg-white/50 dark:hover:bg-white/10 transition-all duration-150">Konektivitas</a>
                            <a href="#simulator" class="px-3.5 py-1.5 rounded-full hover:text-orange-600 dark:hover:text-white hover:bg-white/50 dark:hover:bg-white/10 transition-all duration-150">Demo Interaktif</a>
                            <a href="#faq" class="px-3.5 py-1.5 rounded-full hover:text-orange-600 dark:hover:text-white hover:bg-white/50 dark:hover:bg-white/10 transition-all duration-150">Tanya Jawab</a>
                        </nav>

                        <!-- Right Actions & Theme Switcher -->
                        <div class="flex items-center gap-1 sm:gap-2 pr-1 sm:pr-1.5 shrink-0">
                            <!-- Theme Switcher Button -->
                            <button @click="toggleTheme()" 
                                    type="button"
                                    aria-label="Toggle Theme"
                                    class="p-1.5 sm:p-2 rounded-full bg-white/40 dark:bg-white/10 hover:bg-white/80 dark:hover:bg-white/20 border border-white/60 dark:border-white/20 text-zinc-800 dark:text-amber-300 shadow-sm backdrop-blur-xl transition-all duration-200 hover:scale-110 active:scale-95 flex items-center justify-center shrink-0">
                                <!-- Sun icon -->
                                <svg x-show="isDark" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                                </svg>
                                <!-- Moon icon -->
                                <svg x-show="!isDark" class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-zinc-800" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                                </svg>
                            </button>

                            @if (Route::has('login'))
                                @auth
                                    <a href="{{ url('/dashboard') }}" 
                                       class="inline-flex items-center justify-center px-2.5 py-1.5 sm:px-4 sm:py-2 rounded-full text-white font-bold text-[10px] sm:text-xs uppercase tracking-tight sm:tracking-wider bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-md shadow-orange-500/25 hover:scale-105 active:scale-95 transition-all duration-200 whitespace-nowrap shrink-0">
                                        <span>Dashboard</span>
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" 
                                       @click.prevent="openLoginModal()"
                                       class="inline-flex items-center justify-center px-2.5 py-1.5 sm:px-4 sm:py-2 rounded-full text-white font-bold text-[10px] sm:text-xs uppercase tracking-tight sm:tracking-wider bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-md shadow-orange-500/25 hover:scale-105 active:scale-95 transition-all duration-200 whitespace-nowrap shrink-0">
                                        <span>Masuk</span>
                                    </a>
                                @endauth
                            @endif
                        </div>
                    </div>
                </header>

                <!-- Center Hero Stage: Open & Sleek Typography -->
                <div class="max-w-4xl mx-auto w-full relative z-10 text-center flex flex-col items-center justify-center my-auto py-5 sm:py-8 md:py-12 px-2">
                    
                    <!-- Pill Badge: Generasi Mulia Islami Cemerlang -->
                    <div class="inline-flex items-center gap-1.5 sm:gap-2 px-3 py-1 sm:px-4 sm:py-1.5 rounded-full bg-orange-500/15 dark:bg-black/50 backdrop-blur-md border border-orange-500/35 dark:border-white/20 shadow-sm text-[9px] xs:text-[10px] sm:text-xs font-bold text-orange-950 dark:text-amber-300 uppercase tracking-wider mb-3 sm:mb-4 animate-pulse">
                        <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-orange-600 dark:text-amber-400 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l2.4 7.4H22l-6 4.6 2.3 7.2L12 16.8 5.7 21.2 8 14l-6-4.6h7.6z" />
                        </svg>
                        <span>Generasi Mulia Islami Cemerlang</span>
                    </div>

                    <!-- Main Hero Title -->
                    <h1 class="text-2xl xs:text-3xl sm:text-4xl lg:text-5xl font-black text-zinc-900 dark:text-white tracking-tight leading-snug sm:leading-tight drop-shadow-[0_2px_12px_rgba(255,255,255,0.9)] dark:drop-shadow-[0_4px_20px_rgba(0,0,0,0.95)] px-2">
                        TAD Management System
                    </h1>
                    <p class="text-xs sm:text-sm font-bold tracking-widest text-orange-600 dark:text-amber-400 uppercase mt-1">
                        (Tahfizh, Adab, Disiplin)
                    </p>

                    <!-- Hero Subtitle in Indonesian with Sleek Frosted Glass Container to avoid Background Sign Clashing -->
                    <div class="mt-3 sm:mt-4 p-3 sm:p-4 rounded-2xl bg-white/40 dark:bg-black/45 backdrop-blur-md border border-white/60 dark:border-white/10 shadow-sm max-w-2xl mx-auto">
                        <p class="text-xs sm:text-sm md:text-base text-zinc-900 dark:text-zinc-100 leading-relaxed font-medium">
                            Platform Digital Terpadu <strong class="text-zinc-950 dark:text-white font-bold">SMA Islam Al Azhar 7 Sukoharjo</strong> untuk pemantauan tahfizh mutqin, pembiasaan karakter adab, dan kemajuan akademik santri secara real-time.
                        </p>
                    </div>

                    <!-- Dual Action CTA Buttons (Proportionate on Mobile & Foldable) -->
                    <div class="mt-5 sm:mt-7 flex flex-col sm:flex-row items-center justify-center gap-2.5 sm:gap-3.5 w-full sm:w-auto max-w-[280px] sm:max-w-none mx-auto">
                        @auth
                            <a href="{{ url('/dashboard') }}" 
                               class="w-full sm:w-auto px-6 py-2.5 sm:px-8 sm:py-3.5 rounded-full text-white font-bold text-xs sm:text-sm tracking-wider uppercase bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 hover:from-orange-600 hover:to-amber-600 shadow-xl shadow-orange-500/30 hover:scale-105 active:scale-95 transition-all duration-200 flex items-center justify-center gap-2">
                                <span>Buka Dashboard</span>
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        @else
                            <a href="{{ route('login') }}" 
                               @click.prevent="openLoginModal()"
                               class="w-full sm:w-auto px-6 py-2.5 sm:px-8 sm:py-3.5 rounded-full text-white font-bold text-xs sm:text-sm tracking-wider uppercase bg-gradient-to-r from-orange-500 via-amber-500 to-orange-600 hover:from-orange-600 hover:to-amber-600 shadow-xl shadow-orange-500/30 hover:scale-105 active:scale-95 transition-all duration-200 flex items-center justify-center gap-2">
                                <span>Akses Portal Masuk</span>
                                <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                            </a>
                        @endauth

                        <a href="#fitur" 
                           class="w-full sm:w-auto px-6 py-2.5 sm:px-8 sm:py-3.5 rounded-full text-zinc-800 dark:text-zinc-200 hover:text-orange-600 dark:hover:text-white font-bold text-xs sm:text-sm tracking-wide bg-white/60 dark:bg-black/40 hover:bg-white/90 dark:hover:bg-white/10 backdrop-blur-xl border border-white/80 dark:border-white/20 shadow-md hover:scale-105 active:scale-95 transition-all duration-200 flex items-center justify-center gap-2">
                            <span>Jelajahi Fitur</span>
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-zinc-600 dark:text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Bottom Floating Metric Dock: Compact 3-Column Horizontal Dock (Optimized for all viewports) -->
                <div class="max-w-4xl mx-auto w-full relative z-10 pb-2">
                    <div class="grid grid-cols-3 gap-1 xs:gap-1.5 sm:gap-4 p-1.5 xs:p-2 sm:p-4 rounded-2xl sm:rounded-3xl glass-liquid-card transition-colors duration-300">
                        
                        <!-- Metric 1: Tahfizh 30 Juz -->
                        <div class="flex flex-col sm:flex-row items-center text-center sm:text-left gap-1 sm:gap-3 p-1.5 xs:p-2 sm:p-3 rounded-xl sm:rounded-2xl glass-liquid-inner hover:bg-white/90 dark:hover:bg-white/10 transition duration-200 min-w-0">
                            <div class="w-6 h-6 xs:w-7 xs:h-7 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-orange-500/15 text-orange-600 dark:text-orange-400 flex items-center justify-center shrink-0">
                                <svg class="w-3 h-3 xs:w-3.5 xs:h-3.5 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <div class="flex flex-col min-w-0 w-full">
                                <div class="text-[11px] xs:text-xs sm:text-lg font-black text-zinc-900 dark:text-white leading-tight truncate">30 Juz</div>
                                <div class="text-[8px] xs:text-[9px] sm:text-[11px] text-zinc-600 dark:text-zinc-300 font-medium leading-tight mt-0.5 truncate sm:whitespace-normal">Tahfizh Mutqin</div>
                            </div>
                        </div>

                        <!-- Metric 2: 100% Real-Time Monitoring -->
                        <div class="flex flex-col sm:flex-row items-center text-center sm:text-left gap-1 sm:gap-3 p-1.5 xs:p-2 sm:p-3 rounded-xl sm:rounded-2xl glass-liquid-inner hover:bg-white/90 dark:hover:bg-white/10 transition duration-200 min-w-0">
                            <div class="w-6 h-6 xs:w-7 xs:h-7 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-blue-500/15 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                                <svg class="w-3 h-3 xs:w-3.5 xs:h-3.5 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="flex flex-col min-w-0 w-full">
                                <div class="text-[11px] xs:text-xs sm:text-lg font-black text-zinc-900 dark:text-white leading-tight truncate">100% Live</div>
                                <div class="text-[8px] xs:text-[9px] sm:text-[11px] text-zinc-600 dark:text-zinc-300 font-medium leading-tight mt-0.5 truncate sm:whitespace-normal">Monitoring</div>
                            </div>
                        </div>

                        <!-- Metric 3: 12+ Poin Pembiasaan Adab -->
                        <div class="flex flex-col sm:flex-row items-center text-center sm:text-left gap-1 sm:gap-3 p-1.5 xs:p-2 sm:p-3 rounded-xl sm:rounded-2xl glass-liquid-inner hover:bg-white/90 dark:hover:bg-white/10 transition duration-200 min-w-0">
                            <div class="w-6 h-6 xs:w-7 xs:h-7 sm:w-10 sm:h-10 rounded-lg sm:rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                                <svg class="w-3 h-3 xs:w-3.5 xs:h-3.5 sm:w-5 sm:h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                            </div>
                            <div class="flex flex-col min-w-0 w-full">
                                <div class="text-[11px] xs:text-xs sm:text-lg font-black text-zinc-900 dark:text-white leading-tight truncate">12+ Poin</div>
                                <div class="text-[8px] xs:text-[9px] sm:text-[11px] text-zinc-600 dark:text-zinc-300 font-medium leading-tight mt-0.5 truncate sm:whitespace-normal">Adab Santri</div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <!-- SECTION 2: STATS & MUSHAF INTERACTIVE SHOWCASE (Sunset Glassmorphic Showcase) -->
            <section id="fitur" class="bg-[#e2e8f0]/40 dark:bg-[#09090b] text-zinc-900 dark:text-zinc-100 py-24 sm:py-28 relative overflow-hidden border-t border-b border-slate-300/40 dark:border-white/10 transition-colors duration-300">
                <!-- Ambient Sunset & Cobalt Glows -->
                <div class="absolute inset-0 pointer-events-none z-0">
                    <div class="absolute w-[500px] h-[500px] -top-32 -left-32 bg-orange-500/12 dark:bg-orange-500/15 rounded-full blur-[140px]"></div>
                    <div class="absolute w-[500px] h-[500px] -bottom-32 -right-32 bg-blue-600/10 dark:bg-blue-600/15 rounded-full blur-[140px]"></div>
                    <div class="absolute inset-0 bg-[radial-gradient(#64748b10_1px,transparent_1px)] dark:bg-[radial-gradient(#ffffff0a_1px,transparent_1px)] [background-size:24px_24px]"></div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <!-- Heading -->
                    <div class="text-center flex flex-col items-center gap-4 max-w-2xl mx-auto mb-16 sm:mb-20">
                        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-600 dark:text-orange-400 text-xs font-bold uppercase tracking-wider backdrop-blur-md">
                            <span class="w-1.5 h-1.5 rounded-full bg-orange-500 dark:bg-orange-400 animate-pulse"></span>
                            Fitur Unggulan TAD Management System
                        </div>
                        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-zinc-900 dark:text-white tracking-tight leading-tight">
                            Satu Platform, Semua Kebutuhan Pelacakan Tahfidz
                        </h2>
                        <p class="text-zinc-600 dark:text-zinc-400 text-sm sm:text-base leading-relaxed">
                            Pencatatan hafalan harian, murajaah multi-surat cerdas, input spreadsheet secepat kilat, dan laporan WhatsApp otomatis dalam satu sentuhan.
                        </p>
                    </div>

                    <!-- Split Columns: Glassmorphic Mockup Dashboard & Stats -->
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                        
                        <!-- Left Mockup: Glassmorphic Tahfidz Tracker Hub -->
                        <div class="lg:col-span-7 glass-liquid-card rounded-3xl p-5 sm:p-7 relative group transition-colors duration-300">
                            <!-- Subtle Mockup Glow Accent -->
                            <div class="absolute -top-10 -right-10 w-40 h-40 bg-orange-500/20 rounded-full blur-3xl pointer-events-none"></div>

                            <!-- Mockup Window Header -->
                            <div class="flex items-center justify-between pb-4 border-b border-slate-200/80 dark:border-white/10 mb-5">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-red-500/80"></span>
                                    <span class="w-3 h-3 rounded-full bg-amber-500/80"></span>
                                    <span class="w-3 h-3 rounded-full bg-emerald-500/80"></span>
                                    <span class="text-xs font-bold text-zinc-500 dark:text-zinc-400 ml-2 hidden sm:inline">TAD Tahfidz Hub • Dashboard Live</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-ping"></span>
                                        Sesi Aktif
                                    </span>
                                </div>
                            </div>

                            <!-- Mockup Body (Hafalan Baru, Murojaah Cerdas, Mushaf Preview) -->
                            <div class="space-y-3.5">
                                
                                <!-- Card 1: Hafalan Baru -->
                                <div class="glass-liquid-inner rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:border-orange-500/40 transition-all duration-200">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-10 h-10 rounded-xl bg-orange-500/15 border border-orange-500/30 flex items-center justify-center text-orange-600 dark:text-orange-400 font-bold shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-[11px] text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-wider">Hafalan Baru (Ziyadah)</div>
                                            <div class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white">QS. Al-Kahfi : 1 – 10</div>
                                            <div class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-1 mt-0.5">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                Mumtaz • 15 Baris
                                            </div>
                                        </div>
                                    </div>
                                    <div class="sm:text-right flex sm:flex-col justify-between items-center sm:items-end border-t sm:border-t-0 border-slate-200/80 dark:border-white/5 pt-2 sm:pt-0">
                                        <div class="text-[10px] text-zinc-500 dark:text-zinc-400 font-semibold uppercase">Ustadz Penguji</div>
                                        <div class="text-xs sm:text-sm font-semibold text-zinc-800 dark:text-zinc-200">Ust. Ahmad Rabbani</div>
                                    </div>
                                </div>

                                <!-- Card 2: Murajaah Multi-Surat Cerdas -->
                                <div class="glass-liquid-inner rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:border-amber-500/40 transition-all duration-200">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-10 h-10 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-600 dark:text-amber-400 font-bold shrink-0">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-[11px] text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-wider">Murajaah Multi-Surah</div>
                                            <div class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white">QS. An-Naba' – 'Abasa</div>
                                            <div class="text-[11px] text-amber-600 dark:text-amber-400 font-medium flex items-center gap-1 mt-0.5">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                Penanda Terakhir: QS. 'Abasa
                                            </div>
                                        </div>
                                    </div>
                                    <div class="sm:text-right flex sm:flex-col justify-between items-center sm:items-end border-t sm:border-t-0 border-slate-200/80 dark:border-white/5 pt-2 sm:pt-0">
                                        <div class="text-[10px] text-zinc-500 dark:text-zinc-400 font-semibold uppercase">Status Kelancaran</div>
                                        <div class="text-xs sm:text-sm font-semibold text-emerald-600 dark:text-emerald-400">Mutqin (Lancar)</div>
                                    </div>
                                </div>

                                <!-- Card 3: Interactive Mushaf Typography Preview -->
                                <div class="p-4 bg-gradient-to-r from-orange-500/10 via-amber-500/5 to-transparent rounded-2xl border border-orange-500/25 dark:border-orange-500/20 flex flex-col gap-2 backdrop-blur-md">
                                    <div class="flex items-center justify-between text-xs text-orange-700 dark:text-orange-300 font-bold">
                                        <span class="flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-orange-500 dark:bg-orange-400"></span>
                                            Tinjauan Mushaf Madinah
                                        </span>
                                        <span class="font-serif text-sm font-normal text-amber-800 dark:text-amber-200">سُورَةُ الكَهْفِ</span>
                                    </div>
                                    <p class="text-right font-serif text-base sm:text-xl text-zinc-900 dark:text-zinc-100 leading-loose pt-1">
                                        ٱلْحَمْدُ لِلَّهِ ٱلَّذِىٓ أَنزَلَ عَلَىٰ عَبْدِهِ ٱلْكِتَٰبَ وَلَمْ يَجْعَل لَّهُۥ عِوَجَا ۜ ﴿١﴾
                                    </p>
                                    <div class="flex items-center justify-between text-[11px] text-zinc-600 dark:text-zinc-400 border-t border-slate-200/80 dark:border-white/10 pt-2 mt-1">
                                        <span>Target Juz 15</span>
                                        <div class="flex items-center gap-2">
                                            <div class="w-24 sm:w-32 h-1.5 rounded-full bg-slate-300/60 dark:bg-white/10 overflow-hidden">
                                                <div class="w-4/5 h-full bg-gradient-to-r from-orange-500 to-amber-400 rounded-full"></div>
                                            </div>
                                            <span class="font-bold text-orange-600 dark:text-orange-300">80%</span>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Right Column: Glassmorphic Feature Highlights & Metrics -->
                        <div class="lg:col-span-5 flex flex-col gap-6">
                            <div class="space-y-3">
                                <h3 class="text-2xl sm:text-3xl font-bold text-zinc-900 dark:text-white leading-snug">
                                    Pantau Capaian Tahfidz dengan Akurasi Real-Time
                                </h3>
                                <p class="text-zinc-600 dark:text-zinc-400 text-sm leading-relaxed">
                                    Sistem pelacakan terpadu yang memadukan input cerdas, grafik analitik target kurikulum, evaluasi adab santri, dan transmisi otomatis ke orang tua.
                                </p>
                            </div>

                            <!-- Feature Points with Sunset Icons -->
                            <div class="space-y-3.5">
                                <div class="flex items-start gap-3.5 p-3.5 rounded-2xl glass-liquid-card transition-all hover:scale-[1.01]">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-orange-500 to-amber-600 flex items-center justify-center text-white shrink-0 shadow-md shadow-orange-500/20">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-zinc-900 dark:text-white">Input Spreadsheet & Cepat</h4>
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">Input puluhan santri per halaqah hanya dalam 30 detik tanpa reload halaman.</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3.5 p-3.5 rounded-2xl glass-liquid-card transition-all hover:scale-[1.01]">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shrink-0 shadow-md shadow-emerald-500/20">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-zinc-900 dark:text-white">Laporan Harian WhatsApp Terpadu</h4>
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">Generator pesan otomatis mencakup ziyadah, seluruh surat murajaah, dan adab santri.</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3.5 p-3.5 rounded-2xl glass-liquid-card transition-all hover:scale-[1.01]">
                                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shrink-0 shadow-md shadow-blue-500/20">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-zinc-900 dark:text-white">Portal Wali Murid 360°</h4>
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">Transparansi penuh rekam jejak hafalan, riwayat murajaah, dan evaluasi bulanan.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <!-- SECTION 3: KEY ADVANTAGES (Sunset Glassmorphic Spotlight Cards) -->
            <section id="keunggulan" class="bg-[#f1f5f9] dark:bg-[#09090b] text-zinc-900 dark:text-zinc-100 py-24 sm:py-28 relative overflow-hidden border-b border-slate-300/40 dark:border-white/10 transition-colors duration-300">
                <!-- Ambient Sunset & Cobalt Glows -->
                <div class="absolute inset-0 pointer-events-none z-0">
                    <div class="absolute w-[500px] h-[500px] top-[15%] left-[-150px] bg-orange-500/10 rounded-full blur-[140px]"></div>
                    <div class="absolute w-[500px] h-[500px] bottom-[-150px] right-[-150px] bg-blue-600/10 rounded-full blur-[140px]"></div>
                    <div class="absolute inset-0 bg-[radial-gradient(#64748b0e_1px,transparent_1px)] dark:bg-[radial-gradient(#ffffff08_1px,transparent_1px)] [background-size:24px_24px]"></div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <!-- Heading -->
                    <div class="text-center flex flex-col items-center gap-4 max-w-3xl mx-auto mb-16 sm:mb-20">
                        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 text-xs font-bold uppercase tracking-wider backdrop-blur-md">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 dark:bg-amber-400 animate-pulse"></span>
                            Keunggulan Sistem
                        </div>
                        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-zinc-900 dark:text-white tracking-tight leading-tight">
                            Mengapa Memilih TAD Management System ?
                        </h2>
                        <p class="text-zinc-600 dark:text-zinc-400 text-sm sm:text-base leading-relaxed">
                            Fitur-fitur tangguh yang dirancang spesifik untuk menyederhanakan manajemen Tahfizh di sekolah, Pondok Pesantren, dan Rumah Tahfizh.
                        </p>
                    </div>

                    <!-- 5 Spotlight Cards Grid (Balanced Glassmorphic Layout) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        
                        <!-- Card 1: Input Setoran (Sunset Orange Glow) -->
                        <div class="p-7 sm:p-8 flex flex-col gap-4 glass-liquid-card rounded-3xl hover:border-orange-500/50 dark:hover:border-orange-500/40 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-2xl group"
                             @mousemove="trackMouse">
                            <div class="w-12 h-12 rounded-2xl bg-orange-500/15 border border-orange-500/30 flex items-center justify-center text-orange-600 dark:text-orange-400 group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-white group-hover:text-orange-600 dark:group-hover:text-orange-300 transition-colors">Input Setoran</h3>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                Form input teroptimasi memudahkan ustadz merekam hasil setoran hafalan baru maupun murajaah murid dalam hitungan detik.
                            </p>
                        </div>

                        <!-- Card 2: Akses Transparan Wali (Warm Amber Glow) -->
                        <div class="p-7 sm:p-8 flex flex-col gap-4 glass-liquid-card rounded-3xl hover:border-amber-500/50 dark:hover:border-amber-500/40 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-2xl group"
                             @mousemove="trackMouse">
                            <div class="w-12 h-12 rounded-2xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-600 dark:text-amber-400 group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-300 transition-colors">Akses Transparan Wali</h3>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                Orang tua dapat masuk langsung untuk melihat log setoran, perkembangan hafalan, adab, dan catatan ustadz pembimbing dari rumah.
                            </p>
                        </div>

                        <!-- Card 3: Poin Kedisiplinan & Prestasi (Emerald / Tanse Glow) -->
                        <div class="p-7 sm:p-8 flex flex-col gap-4 glass-liquid-card rounded-3xl hover:border-emerald-500/50 dark:hover:border-emerald-500/40 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-2xl group"
                             @mousemove="trackMouse">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-300 transition-colors">Poin Kedisiplinan & Prestasi</h3>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                Catat poin pelanggaran disiplin (Tanse) serta apresiasi poin prestasi secara real-time demi membentuk karakter murid yang tangguh.
                            </p>
                        </div>

                        <!-- Card 4: Mushaf Qur'an Terpadu (Cobalt / Teal Glow) -->
                        <div class="p-7 sm:p-8 flex flex-col gap-4 glass-liquid-card rounded-3xl hover:border-teal-500/50 dark:hover:border-teal-500/40 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-2xl group md:col-span-1 lg:col-span-1"
                             @mousemove="trackMouse">
                            <div class="w-12 h-12 rounded-2xl bg-teal-500/15 border border-teal-500/30 flex items-center justify-center text-teal-600 dark:text-teal-400 group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-white group-hover:text-teal-600 dark:group-hover:text-teal-300 transition-colors">Mushaf Qur'an Terpadu</h3>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                Membaca Mushaf dan melihat tafsir secara langsung dalam sistem dengan pilihan tema kustom untuk kenyamanan mata.
                            </p>
                        </div>

                        <!-- Card 5: Cadangan & Keamanan Tinggi (Royal Blue Glow) -->
                        <div class="p-7 sm:p-8 flex flex-col gap-4 glass-liquid-card rounded-3xl hover:border-blue-500/50 dark:hover:border-blue-500/40 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-2xl group md:col-span-1 lg:col-span-2"
                             @mousemove="trackMouse">
                            <div class="w-12 h-12 rounded-2xl bg-blue-500/15 border border-blue-500/30 flex items-center justify-center text-blue-600 dark:text-blue-400 group-hover:scale-110 transition-transform duration-200">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-300 transition-colors">Cadangan & Keamanan Tinggi</h3>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                Database terlindungi dengan enkripsi terbaik, lengkap dengan fitur ekspor dan unduhan cadangan berkala guna menjamin keamanan data.
                            </p>
                        </div>

                    </div>
                </div>
            </section>

            <!-- SECTION 4: CONNECTIONS & MAP GEOLOCATION (Indonesia Archipelago Map) -->
            <section id="sebaran" class="bg-[#e2e8f0]/40 dark:bg-[#09090b] text-zinc-900 dark:text-zinc-100 py-24 sm:py-28 relative overflow-hidden border-b border-slate-300/40 dark:border-white/10 transition-colors duration-300">
                <!-- Ambient Glows -->
                <div class="absolute inset-0 pointer-events-none z-0">
                    <div class="absolute w-[500px] h-[500px] top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-orange-500/10 rounded-full blur-[160px]"></div>
                    <div class="absolute inset-0 bg-[radial-gradient(#64748b10_1px,transparent_1px)] dark:bg-[radial-gradient(#ffffff08_1px,transparent_1px)] [background-size:24px_24px]"></div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 flex flex-col items-center">
                    
                    <!-- Header -->
                    <div class="text-center flex flex-col items-center gap-4 max-w-3xl mx-auto mb-16">
                        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-600 dark:text-orange-400 text-xs font-bold uppercase tracking-wider backdrop-blur-md">
                            <span class="w-1.5 h-1.5 rounded-full bg-orange-500 dark:bg-orange-400 animate-pulse"></span>
                            Ekosistem Terintegrasi
                        </div>
                        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-zinc-900 dark:text-white leading-tight">
                            Jaringan Konektivitas Terpadu Seluruh Indonesia
                        </h2>
                        <p class="text-zinc-600 dark:text-zinc-400 text-sm sm:text-base leading-relaxed">
                            Menghubungkan berbagai Pondok Pesantren, Sekolah Dasar, Sekolah Menengah, serta Rumah Tahfizh dalam satu sistem terpusat pusat.
                        </p>
                    </div>

                    <!-- Interactive Indonesia Dotted Map Showcase Card -->
                    <div class="relative w-full max-w-4xl glass-liquid-card rounded-3xl overflow-hidden p-6 sm:p-10 flex flex-col items-center justify-center min-h-[380px] sm:min-h-[440px] transition-colors duration-300">
                        
                        <!-- Top-Right Metric: 7+ Lembaga Terhubung -->
                        <div class="sm:absolute top-5 right-5 mb-4 sm:mb-0 self-end sm:self-auto glass-liquid-inner px-4 py-2 rounded-2xl flex items-center gap-2.5 shadow-sm">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
                            </span>
                            <span class="text-xs sm:text-sm font-bold text-zinc-800 dark:text-zinc-200">7+ Lembaga Terhubung</span>
                        </div>

                        <!-- Indonesia Archipelago Dotted Matrix SVG -->
                        <div class="w-full relative my-auto py-4">
                            <svg class="w-full h-auto max-h-[260px] sm:max-h-[300px]" viewBox="0 0 1000 420" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <radialGradient id="pinGlowOrange" cx="50%" cy="50%" r="50%">
                                        <stop offset="0%" stop-color="#f97316" stop-opacity="1"/>
                                        <stop offset="100%" stop-color="#f97316" stop-opacity="0"/>
                                    </radialGradient>
                                    <radialGradient id="pinGlowAmber" cx="50%" cy="50%" r="50%">
                                        <stop offset="0%" stop-color="#f59e0b" stop-opacity="1"/>
                                        <stop offset="100%" stop-color="#f59e0b" stop-opacity="0"/>
                                    </radialGradient>
                                    <radialGradient id="pinGlowTeal" cx="50%" cy="50%" r="50%">
                                        <stop offset="0%" stop-color="#10b981" stop-opacity="1"/>
                                        <stop offset="100%" stop-color="#10b981" stop-opacity="0"/>
                                    </radialGradient>
                                </defs>

                                <!-- Archipelago Dotted Matrix Nodes (Sumatra, Jawa, Kalimantan, Sulawesi, Nusa Tenggara, Maluku, Papua) -->
                                <g class="fill-slate-400 dark:fill-zinc-600 opacity-60 dark:opacity-45">
                                    <!-- SUMATRA -->
                                    <circle cx="80" cy="110" r="4"/><circle cx="95" cy="120" r="4.5"/><circle cx="110" cy="135" r="4"/><circle cx="125" cy="150" r="5"/><circle cx="140" cy="165" r="4.5"/><circle cx="155" cy="180" r="5"/><circle cx="170" cy="195" r="4"/><circle cx="185" cy="210" r="5"/><circle cx="200" cy="225" r="4.5"/><circle cx="215" cy="240" r="5"/><circle cx="230" cy="255" r="4.5"/><circle cx="245" cy="270" r="4"/>
                                    <circle cx="95" cy="95" r="3.5"/><circle cx="125" cy="120" r="4"/><circle cx="150" cy="140" r="4"/><circle cx="175" cy="165" r="4"/><circle cx="205" cy="195" r="4.5"/><circle cx="225" cy="225" r="4"/>
                                    
                                    <!-- KALIMANTAN -->
                                    <circle cx="340" cy="130" r="4.5"/><circle cx="365" cy="120" r="4"/><circle cx="390" cy="115" r="4.5"/><circle cx="415" cy="125" r="4"/><circle cx="440" cy="135" r="4.5"/>
                                    <circle cx="330" cy="155" r="4"/><circle cx="355" cy="150" r="5"/><circle cx="380" cy="145" r="5"/><circle cx="405" cy="150" r="5"/><circle cx="430" cy="160" r="4.5"/>
                                    <circle cx="340" cy="180" r="4.5"/><circle cx="365" cy="175" r="5"/><circle cx="390" cy="175" r="5"/><circle cx="415" cy="180" r="4.5"/><circle cx="440" cy="190" r="4"/>
                                    <circle cx="360" cy="205" r="4"/><circle cx="385" cy="205" r="4.5"/><circle cx="410" cy="205" r="4"/>

                                    <!-- JAWA (Extended Detail) -->
                                    <circle cx="270" cy="295" r="4.5"/><circle cx="290" cy="298" r="4.5"/><circle cx="310" cy="300" r="5"/>
                                    <circle cx="330" cy="302" r="5"/><circle cx="350" cy="305" r="5.5"/><circle cx="370" cy="307" r="5.5"/>
                                    <circle cx="390" cy="308" r="6"/><circle cx="410" cy="310" r="6"/><circle cx="430" cy="312" r="5.5"/>
                                    <circle cx="450" cy="313" r="5.5"/><circle cx="470" cy="314" r="5"/><circle cx="490" cy="315" r="4.5"/>

                                    <!-- BALI & NUSA TENGGARA -->
                                    <circle cx="515" cy="318" r="4"/><circle cx="535" cy="319" r="4"/><circle cx="555" cy="320" r="4"/><circle cx="580" cy="321" r="3.5"/><circle cx="610" cy="322" r="4"/><circle cx="640" cy="323" r="3.5"/>

                                    <!-- SULAWESI -->
                                    <circle cx="505" cy="130" r="4"/><circle cx="525" cy="140" r="4.5"/><circle cx="545" cy="130" r="4"/><circle cx="565" cy="120" r="3.5"/>
                                    <circle cx="500" cy="160" r="4.5"/><circle cx="515" cy="180" r="5"/><circle cx="510" cy="205" r="4.5"/><circle cx="505" cy="235" r="4.5"/>
                                    <circle cx="530" cy="195" r="4"/><circle cx="550" cy="210" r="4"/><circle cx="560" cy="235" r="3.5"/>

                                    <!-- MALUKU -->
                                    <circle cx="670" cy="140" r="3.5"/><circle cx="690" cy="155" r="4"/><circle cx="665" cy="185" r="3.5"/><circle cx="685" cy="210" r="3.5"/>

                                    <!-- PAPUA -->
                                    <circle cx="780" cy="170" r="4.5"/><circle cx="810" cy="160" r="5"/><circle cx="840" cy="160" r="5"/><circle cx="870" cy="165" r="5"/><circle cx="900" cy="170" r="4.5"/>
                                    <circle cx="765" cy="195" r="4.5"/><circle cx="795" cy="190" r="5.5"/><circle cx="825" cy="190" r="6"/><circle cx="855" cy="195" r="6"/><circle cx="885" cy="200" r="5.5"/><circle cx="915" cy="205" r="4.5"/>
                                    <circle cx="810" cy="225" r="5"/><circle cx="840" cy="225" r="5.5"/><circle cx="870" cy="230" r="5.5"/><circle cx="900" cy="235" r="5"/><circle cx="925" cy="245" r="4"/>
                                </g>

                                <!-- Connection Arcs Between Nodes -->
                                <path d="M345 305 Q380 270 415 310" stroke="#f97316" stroke-width="1.5" stroke-dasharray="3 3" opacity="0.6"/>
                                <path d="M415 310 Q440 280 465 314" stroke="#f59e0b" stroke-width="1.5" stroke-dasharray="3 3" opacity="0.6"/>
                                <path d="M415 310 Q400 230 380 145" stroke="#10b981" stroke-width="1.2" stroke-dasharray="4 4" opacity="0.4"/>
                                <path d="M415 310 Q600 210 825 190" stroke="#3b82f6" stroke-width="1.2" stroke-dasharray="4 4" opacity="0.3"/>

                                <!-- ======================================================== -->
                                <!-- 1. PIN JAWA BARAT: CIREBON -->
                                <!-- ======================================================== -->
                                <g class="cursor-pointer group/pin">
                                    <circle cx="345" cy="305" r="24" fill="url(#pinGlowOrange)" class="animate-ping opacity-40"/>
                                    <circle cx="345" cy="305" r="7" fill="#f97316" stroke="#ffffff" stroke-width="2" class="drop-shadow-[0_0_8px_rgba(249,115,22,0.8)]"/>
                                    <!-- Tooltip Tag -->
                                    <g transform="translate(345, 260)">
                                        <rect x="-42" y="-12" width="84" height="22" rx="11" class="fill-white/95 dark:fill-[#18181b] stroke-orange-500 shadow-md" stroke-width="1.5"/>
                                        <text x="0" y="3" class="fill-orange-700 dark:fill-[#fdba74]" font-size="9.5" font-weight="bold" text-anchor="middle" font-family="sans-serif">Cirebon</text>
                                    </g>
                                </g>

                                <!-- ======================================================== -->
                                <!-- 2. PIN JAWA TENGAH: SOLO / SURAKARTA (PROMINENT CENTER)  -->
                                <!-- ======================================================== -->
                                <g class="cursor-pointer group/pin">
                                    <circle cx="415" cy="310" r="30" fill="url(#pinGlowAmber)" class="animate-ping opacity-60"/>
                                    <circle cx="415" cy="310" r="9" fill="#f59e0b" stroke="#ffffff" stroke-width="2.5" class="drop-shadow-[0_0_12px_rgba(245,158,11,1)]"/>
                                    <!-- Tooltip Tag -->
                                    <g transform="translate(415, 255)">
                                        <rect x="-56" y="-14" width="112" height="26" rx="13" class="fill-white/95 dark:fill-[#18181b] stroke-amber-500 shadow-lg" stroke-width="2"/>
                                        <text x="0" y="3" class="fill-amber-700 dark:fill-[#fde68a]" font-size="10.5" font-weight="900" text-anchor="middle" font-family="sans-serif">Solo (Pusat)</text>
                                    </g>
                                </g>

                                <!-- ======================================================== -->
                                <!-- 3. PIN JAWA TIMUR: MALANG -->
                                <!-- ======================================================== -->
                                <g class="cursor-pointer group/pin">
                                    <circle cx="465" cy="314" r="24" fill="url(#pinGlowTeal)" class="animate-ping opacity-40"/>
                                    <circle cx="465" cy="314" r="7" fill="#10b981" stroke="#ffffff" stroke-width="2" class="drop-shadow-[0_0_8px_rgba(16,185,129,0.8)]"/>
                                    <!-- Tooltip Tag -->
                                    <g transform="translate(465, 260)">
                                        <rect x="-40" y="-12" width="80" height="22" rx="11" class="fill-white/95 dark:fill-[#18181b] stroke-emerald-500 shadow-md" stroke-width="1.5"/>
                                        <text x="0" y="3" class="fill-emerald-700 dark:fill-[#6ee7b7]" font-size="9.5" font-weight="bold" text-anchor="middle" font-family="sans-serif">Malang</text>
                                    </g>
                                </g>
                            </svg>
                        </div>

                        <!-- Bottom-Left Metric: 250+ Murid Aktif -->
                        <div class="sm:absolute bottom-5 left-5 mt-4 sm:mt-0 self-start sm:self-auto glass-liquid-inner px-4 py-2 rounded-2xl flex items-center gap-2.5 shadow-sm">
                            <span class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                            </span>
                            <span class="text-xs sm:text-sm font-bold text-zinc-800 dark:text-zinc-200">250+ Murid Aktif</span>
                        </div>

                    </div>

                </div>
            </section>

            <!-- SECTION 5: INTERACTIVE DASHBOARD SIMULATOR PLAYGROUND -->
            <section id="simulator" class="bg-[#f1f5f9] dark:bg-[#09090b] text-zinc-900 dark:text-zinc-100 py-24 sm:py-28 relative overflow-hidden border-b border-slate-300/40 dark:border-white/10 transition-colors duration-300">
                <!-- Ambient Sunset Glows -->
                <div class="absolute inset-0 pointer-events-none z-0">
                    <div class="absolute w-[500px] h-[500px] -top-32 -right-32 bg-orange-500/10 dark:bg-orange-500/15 rounded-full blur-[150px]"></div>
                    <div class="absolute w-[500px] h-[500px] -bottom-32 -left-32 bg-blue-600/10 dark:bg-blue-600/15 rounded-full blur-[150px]"></div>
                    <div class="absolute inset-0 bg-[radial-gradient(#64748b0e_1px,transparent_1px)] dark:bg-[radial-gradient(#ffffff08_1px,transparent_1px)] [background-size:24px_24px]"></div>
                </div>

                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    
                    <!-- Head -->
                    <div class="text-center flex flex-col items-center gap-4 max-w-3xl mx-auto mb-14 sm:mb-16">
                        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-600 dark:text-orange-400 text-xs font-bold uppercase tracking-wider backdrop-blur-md">
                            <span class="w-1.5 h-1.5 rounded-full bg-orange-500 dark:bg-orange-400 animate-pulse"></span>
                            Demo Interaktif
                        </div>
                        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-zinc-900 dark:text-white leading-tight">
                            Simulasi Dasbor Interaktif Kami
                        </h2>
                        <p class="text-zinc-600 dark:text-zinc-400 text-sm sm:text-base leading-relaxed">
                            Coba dasbor interaktif sekarang. Pilih peran Anda untuk melihat visualisasi alur setoran dan kemudahan antarmuka aplikasi.
                        </p>
                    </div>

                    <!-- Role Tab buttons switcher -->
                    <div class="flex items-center justify-center gap-2 max-w-md mx-auto mb-10 p-1.5 glass-liquid-card rounded-full shadow-md">
                        <button @click="activeDashboardTab = 'siswa'" 
                                :class="activeDashboardTab === 'siswa' ? 'bg-gradient-to-r from-orange-500 to-amber-500 text-white font-bold shadow-md shadow-orange-500/25' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'"
                                class="flex-1 py-2 text-xs font-semibold rounded-full transition-all duration-200">
                            Dasbor Siswa
                        </button>
                        <button @click="activeDashboardTab = 'guru'" 
                                :class="activeDashboardTab === 'guru' ? 'bg-gradient-to-r from-orange-500 to-amber-500 text-white font-bold shadow-md shadow-orange-500/25' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'"
                                class="flex-1 py-2 text-xs font-semibold rounded-full transition-all duration-200">
                            Dasbor Ustadz
                        </button>
                        <button @click="activeDashboardTab = 'wali'" 
                                :class="activeDashboardTab === 'wali' ? 'bg-gradient-to-r from-orange-500 to-amber-500 text-white font-bold shadow-md shadow-orange-500/25' : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white'"
                                class="flex-1 py-2 text-xs font-semibold rounded-full transition-all duration-200">
                            Dasbor Wali
                        </button>
                    </div>

                    <!-- Interactive Mockup Screen (Glassmorphic Container) -->
                    <div class="glass-liquid-card rounded-3xl p-5 sm:p-7 min-h-[380px] flex flex-col justify-between transition-colors duration-300">
                        
                        <!-- Top Window Control Header -->
                        <div class="flex items-center justify-between pb-4 border-b border-slate-200/80 dark:border-white/10 mb-5">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-red-500/80"></span>
                                <span class="w-3 h-3 rounded-full bg-amber-500/80"></span>
                                <span class="w-3 h-3 rounded-full bg-emerald-500/80"></span>
                                <span class="text-xs font-bold text-zinc-500 dark:text-zinc-400 ml-2 hidden sm:inline">TAD Simulation Console</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-orange-500/10 border border-orange-500/20 text-[10px] font-bold text-orange-600 dark:text-orange-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-orange-500 dark:bg-orange-400 animate-ping"></span>
                                    Mode Preview
                                </span>
                            </div>
                        </div>

                        <!-- Inner Container transitions based on active role -->
                        <!-- 1. SISWA VIEW -->
                        <div x-show="activeDashboardTab === 'siswa'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="space-y-6">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-200/80 dark:border-white/10">
                                <div>
                                    <h4 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white">Dasbor Murid — Syamil Rabbani</h4>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium mt-0.5">Kelas: X-MIPA 1 • SMA Islam Al Azhar 7 Solo Baru</p>
                                </div>
                                <span class="inline-flex items-center gap-1.5 self-start sm:self-auto px-3.5 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                                    Target Tercapai 90%
                                </span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="p-4 glass-liquid-inner rounded-2xl hover:border-orange-500/40 transition duration-200">
                                    <div class="text-[10px] text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-wider">Hafalan Baru</div>
                                    <div class="text-xl sm:text-2xl font-extrabold text-zinc-900 dark:text-white mt-1">29 Juz</div>
                                    <div class="text-[10px] text-orange-600 dark:text-orange-400 font-bold mt-1">Sisa Target: 1 Juz</div>
                                </div>

                                <div class="p-4 glass-liquid-inner rounded-2xl hover:border-amber-500/40 transition duration-200">
                                    <div class="text-[10px] text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-wider">Lancarnya Murajaah</div>
                                    <div class="text-xl sm:text-2xl font-extrabold text-zinc-900 dark:text-white mt-1">15 Juz</div>
                                    <div class="text-[10px] text-amber-600 dark:text-amber-400 font-bold mt-1">Poin Kelancaran: Mutqin</div>
                                </div>

                                <div class="p-4 glass-liquid-inner rounded-2xl hover:border-emerald-500/40 transition duration-200">
                                    <div class="text-[10px] text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-wider">Skor Pembiasaan Adab</div>
                                    <div class="text-xl sm:text-2xl font-extrabold text-zinc-900 dark:text-white mt-1">100 Poin</div>
                                    <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold mt-1">Status: Berakhlak Mulia</div>
                                </div>
                            </div>

                            <!-- Mini Chart Simulator -->
                            <div class="p-4 glass-liquid-inner rounded-2xl flex flex-col gap-3">
                                <div class="flex items-center justify-between text-xs font-bold text-zinc-700 dark:text-zinc-300">
                                    <span>Statistik Progres Setoran Bulanan</span>
                                    <span class="text-orange-600 dark:text-orange-400">Semester Ganjil 2026</span>
                                </div>
                                <div class="h-20 flex items-end gap-2 pt-2 px-1">
                                    <div class="bg-orange-500/20 border border-orange-500/30 rounded-t w-full h-[35%]"></div>
                                    <div class="bg-orange-500/25 border border-orange-500/40 rounded-t w-full h-[55%]"></div>
                                    <div class="bg-orange-500/30 border border-orange-500/50 rounded-t w-full h-[45%]"></div>
                                    <div class="bg-orange-500/40 border border-orange-500/60 rounded-t w-full h-[78%]"></div>
                                    <div class="bg-gradient-to-t from-orange-500 to-amber-400 border border-amber-300 rounded-t w-full h-[95%] shadow-[0_0_15px_rgba(249,115,22,0.4)]"></div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. GURU VIEW -->
                        <div x-show="activeDashboardTab === 'guru'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="space-y-6" style="display: none;">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-slate-200/80 dark:border-white/10">
                                <div>
                                    <h4 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white">Dasbor Ustadz Penguji — Ust. Ahmad Rabbani</h4>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium mt-0.5">Halaqah Tahfidz Gemilang • SMA Islam Al Azhar 7</p>
                                </div>
                                <button class="self-start sm:self-auto px-4 py-2 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-xs font-bold text-white rounded-full transition-all duration-150 shadow-md shadow-orange-500/25 hover:scale-105">
                                    + Input Cepat Setoran
                                </button>
                            </div>

                            <!-- List of Students under evaluation -->
                            <div class="space-y-3">
                                <div class="p-3.5 glass-liquid-inner rounded-2xl flex items-center justify-between gap-3 hover:border-orange-500/40 transition">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-orange-500/20 border border-orange-500/30 flex items-center justify-center font-bold text-orange-600 dark:text-orange-400 text-xs">SR</div>
                                        <div>
                                            <div class="text-xs sm:text-sm font-bold text-zinc-900 dark:text-white">Syamil Rabbani</div>
                                            <div class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5">Menyetor QS. Al-Kahfi: 1–10 (15 Baris)</div>
                                        </div>
                                    </div>
                                    <span class="text-[10px] bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 dark:text-emerald-400 font-bold px-2.5 py-1 rounded-full">Mumtaz</span>
                                </div>

                                <div class="p-3.5 glass-liquid-inner rounded-2xl flex items-center justify-between gap-3 hover:border-amber-500/40 transition">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center font-bold text-amber-600 dark:text-amber-400 text-xs">AM</div>
                                        <div>
                                            <div class="text-xs sm:text-sm font-bold text-zinc-900 dark:text-white">Aisyah Muthmainnah</div>
                                            <div class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5">Murajaah QS. An-Naba' – 'Abasa</div>
                                        </div>
                                    </div>
                                    <span class="text-[10px] bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 font-bold px-2.5 py-1 rounded-full">Mutqin</span>
                                </div>
                            </div>
                        </div>

                        <!-- 3. WALI VIEW -->
                        <div x-show="activeDashboardTab === 'wali'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" class="space-y-6" style="display: none;">
                            <div class="flex items-center justify-between pb-4 border-b border-slate-200/80 dark:border-white/10">
                                <div>
                                    <h4 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white">Portal Wali Murid 360° — Bpk. Abdurrahman</h4>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 font-medium mt-0.5">Memantau Murid: Syamil Rabbani</p>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400 animate-pulse"></span>
                                    Terhubung Real-Time
                                </span>
                            </div>

                            <!-- Parents overview feed -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="p-4 glass-liquid-inner rounded-2xl flex flex-col gap-1.5 hover:border-orange-500/40 transition">
                                    <span class="text-[10px] text-orange-600 dark:text-orange-400 font-bold uppercase tracking-wider">Pembaruan Hafalan Terakhir</span>
                                    <div class="text-sm font-bold text-zinc-900 dark:text-white">Syamil menyetor QS. Al-Kahfi: 1–10</div>
                                    <span class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">Ust. Ahmad: "Bacaan sangat lancar, tajwid dan makhraj ممتاز"</span>
                                </div>

                                <div class="p-4 glass-liquid-inner rounded-2xl flex flex-col gap-1.5 hover:border-amber-500/40 transition">
                                    <span class="text-[10px] text-amber-600 dark:text-amber-400 font-bold uppercase tracking-wider">Laporan Pembiasaan Adab</span>
                                    <div class="text-sm font-bold text-zinc-900 dark:text-white">Sikap: Sangat Disiplin & Hormat Guru</div>
                                    <span class="text-xs text-zinc-600 dark:text-zinc-400 mt-1">Status: 100 Poin • Mempertahankan Nilai Karakter Gemilang</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <!-- SECTION 6: FAQ ACCORDION -->
            <section id="faq" class="bg-[#e2e8f0]/40 dark:bg-[#09090b] text-zinc-900 dark:text-zinc-100 py-24 sm:py-28 relative overflow-hidden border-b border-slate-300/40 dark:border-white/10 transition-colors duration-300">
                <!-- Ambient Sunset Glows -->
                <div class="absolute inset-0 pointer-events-none z-0">
                    <div class="absolute w-[500px] h-[500px] -top-32 -left-32 bg-orange-500/10 rounded-full blur-[150px]"></div>
                    <div class="absolute w-[500px] h-[500px] -bottom-32 -right-32 bg-blue-600/10 rounded-full blur-[150px]"></div>
                    <div class="absolute inset-0 bg-[radial-gradient(#64748b10_1px,transparent_1px)] dark:bg-[radial-gradient(#ffffff08_1px,transparent_1px)] [background-size:24px_24px]"></div>
                </div>

                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    
                    <div class="text-center flex flex-col items-center gap-4 mx-auto mb-16 max-w-2xl">
                        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-600 dark:text-orange-400 text-xs font-bold uppercase tracking-wider backdrop-blur-md">
                            <span class="w-1.5 h-1.5 rounded-full bg-orange-500 dark:bg-orange-400 animate-pulse"></span>
                            Tanya Jawab
                        </div>
                        <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-zinc-900 dark:text-white leading-tight">
                            Pertanyaan yang Sering Diajukan
                        </h2>
                        <p class="text-zinc-600 dark:text-zinc-400 text-sm sm:text-base leading-relaxed">
                            Butuh bantuan lebih lanjut? Berikut jawaban untuk beberapa pertanyaan umum mengenai penggunaan sistem kami.
                        </p>
                    </div>

                    <!-- FAQ List with Alpine state toggles -->
                    <div class="space-y-4">
                        
                        <!-- Item 1 -->
                        <div x-data="{ open: false }" class="p-5 sm:p-6 rounded-3xl glass-liquid-card hover:border-orange-500/50 dark:hover:border-orange-500/30 transition-all duration-200">
                            <button @click="open = !open" class="flex items-center justify-between w-full text-left font-bold text-sm sm:text-base text-zinc-900 dark:text-white gap-4">
                                <span class="flex items-center gap-3">
                                    <span class="w-2 h-2 rounded-full bg-orange-500 dark:bg-orange-400 shrink-0"></span>
                                    Bagaimana ustadz menginput data setoran?
                                </span>
                                <div class="w-8 h-8 rounded-full glass-liquid-inner flex items-center justify-center shrink-0">
                                    <svg :class="open ? 'rotate-180 text-orange-600 dark:text-orange-400' : 'text-zinc-500 dark:text-zinc-400'" class="w-4 h-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </button>
                            <div x-show="open" x-transition class="mt-4 pt-4 border-t border-slate-200/80 dark:border-white/10 text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed pl-5">
                                Ustadz dapat menginput data langsung melalui form "Quick Input" di dasbor mereka, cukup memilih nama siswa, surah, ayat awal/akhir, dan nilai kelancaran. Semua proses memakan waktu kurang dari 10 detik.
                            </div>
                        </div>

                        <!-- Item 2 -->
                        <div x-data="{ open: false }" class="p-5 sm:p-6 rounded-3xl glass-liquid-card hover:border-amber-500/50 dark:hover:border-amber-500/30 transition-all duration-200">
                            <button @click="open = !open" class="flex items-center justify-between w-full text-left font-bold text-sm sm:text-base text-zinc-900 dark:text-white gap-4">
                                <span class="flex items-center gap-3">
                                    <span class="w-2 h-2 rounded-full bg-amber-500 dark:bg-amber-400 shrink-0"></span>
                                    Apakah data kemajuan murid aman dari kehilangan?
                                </span>
                                <div class="w-8 h-8 rounded-full glass-liquid-inner flex items-center justify-center shrink-0">
                                    <svg :class="open ? 'rotate-180 text-amber-600 dark:text-amber-400' : 'text-zinc-500 dark:text-zinc-400'" class="w-4 h-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </button>
                            <div x-show="open" x-transition class="mt-4 pt-4 border-t border-slate-200/80 dark:border-white/10 text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed pl-5">
                                Ya. Kami menggunakan basis data terenkripsi dan sistem cadangan (database backups) otomatis yang terhubung ke penyimpanan awan. Anda juga dapat mengunduh berkas cadangan secara manual kapan saja dari dasbor Super Admin.
                            </div>
                        </div>

                        <!-- Item 3 -->
                        <div x-data="{ open: false }" class="p-5 sm:p-6 rounded-3xl glass-liquid-card hover:border-emerald-500/50 dark:hover:border-emerald-500/30 transition-all duration-200">
                            <button @click="open = !open" class="flex items-center justify-between w-full text-left font-bold text-sm sm:text-base text-zinc-900 dark:text-white gap-4">
                                <span class="flex items-center gap-3">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400 shrink-0"></span>
                                    Bagaimana wali murid memantau perkembangan putra-putrinya?
                                </span>
                                <div class="w-8 h-8 rounded-full glass-liquid-inner flex items-center justify-center shrink-0">
                                    <svg :class="open ? 'rotate-180 text-emerald-600 dark:text-emerald-400' : 'text-zinc-500 dark:text-zinc-400'" class="w-4 h-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </button>
                            <div x-show="open" x-transition class="mt-4 pt-4 border-t border-slate-200/80 dark:border-white/10 text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed pl-5">
                                Setiap wali murid akan diberikan akun akses khusus. Begitu masuk, mereka akan diarahkan langsung ke dasbor berisi riwayat setoran, persentase target hafalan yang tercapai, serta skor perilaku (adab) harian.
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <!-- SECTION 7: SUNSET HORIZON CTA (Sunset Horizon Glow & Gradient Actions) -->
            <section class="relative bg-zinc-900 dark:bg-black text-zinc-100 py-32 sm:py-36 overflow-hidden flex flex-col items-center justify-center text-center border-t border-zinc-800 dark:border-white/10">
                <!-- Massive Bottom Horizon Glow (Sunset Tangerine & Amber Core) -->
                <div class="glow-horizon"></div>
                <div class="glow-horizon-core"></div>

                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 flex flex-col items-center gap-6">
                    <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/20 text-amber-300 text-xs font-bold uppercase tracking-wider">
                        <svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l2.4 7.4H22l-6 4.6 2.3 7.2L12 16.8 5.7 21.2 8 14l-6-4.6h7.6z" />
                        </svg>
                        Mulai Langkah Digitalisasi
                    </div>

                    <h2 class="text-3xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight leading-tight">
                        Siap Mewujudkan Generasi Rabbani Rapi & Terstruktur?
                    </h2>
                    
                    <p class="text-zinc-400 text-sm sm:text-base leading-relaxed max-w-2xl mx-auto">
                        Mulai langkah digitalisasi tahfizh Anda sekarang bersama berbagai lembaga lainnya di Indonesia. Cepat, aman, dan mudah digunakan.
                    </p>

                    <!-- CTA Action stack -->
                    <div class="flex flex-col sm:flex-row items-center gap-4 mt-4">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" 
                                   class="px-8 py-3.5 text-xs sm:text-sm font-bold uppercase tracking-wider rounded-full text-white bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-xl shadow-orange-500/30 transition-all duration-200 hover:scale-105 active:scale-95">
                                    Masuk Dasbor Aplikasi
                                </a>
                            @else
                                <a href="{{ route('login') }}" 
                                   @click.prevent="openLoginModal()"
                                   class="px-8 py-3.5 text-xs sm:text-sm font-bold uppercase tracking-wider rounded-full text-white bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 shadow-xl shadow-orange-500/30 transition-all duration-200 hover:scale-105 active:scale-95">
                                    Masuk Aplikasi
                                </a>
                            @endauth
                        @endif

                        <a href="https://wa.me/628989789085" target="_blank" 
                           class="bg-white/10 hover:bg-white/15 border border-white/20 text-zinc-200 hover:text-white px-8 py-3.5 text-xs sm:text-sm font-bold uppercase tracking-wider rounded-full transition-all duration-150 hover:scale-105 active:scale-95 flex items-center gap-2.5 backdrop-blur-md shadow-lg">
                            <svg class="w-4 h-4 text-emerald-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
                            </svg>
                            HUBUNGI PENGEMBANG
                        </a>
                    </div>
                </div>
            </section>

            <!-- FOOTER -->
            <footer class="w-full bg-zinc-950 dark:bg-black border-t border-zinc-800 dark:border-white/5 py-10 text-center relative z-10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-6">
                    <p class="text-xs text-zinc-500">
                        &copy; 2026 TAD Management System. SMA Islam Al Azhar 7 Sukoharjo Developed by Native
                    </p>
                    <div class="flex items-center gap-6 text-xs text-zinc-500">
                        <a href="#" class="hover:text-zinc-300 transition-colors">Syarat Ketentuan</a>
                        <a href="#" class="hover:text-zinc-300 transition-colors">Kebijakan Privasi</a>
                    </div>
                </div>
            </footer>

            <!-- ========================================================================= -->
            <!-- POP-UP LOGIN MODAL: LUXURY CRYSTAL FROSTED GLASS (BLURS LANDING PAGE BEHIND) -->
            <!-- ========================================================================= -->
            <!-- ========================================================================= -->
            <!-- LOGIN POPUP MODAL (Blue-to-Orange Gradient Theme & Fixed Non-Scroll Card) -->
            <!-- ========================================================================= -->
            <div x-show="loginModalOpen" 
                 x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-[100] flex items-center justify-center p-3 sm:p-6 overflow-hidden"
                 role="dialog"
                 aria-modal="true"
                 aria-labelledby="login-modal-title">
                
                <!-- Moderate Blurred Glass Backdrop Overlay (Not Too Heavy) -->
                <div class="fixed inset-0 bg-black/45 backdrop-blur-[6px] transition-all duration-300"
                     @click="closeLoginModal()"></div>

                <!-- Main Container (Fixed, Non-scrollable) -->
                <div x-show="loginModalOpen"
                     x-transition:enter="transition ease-out duration-300 delay-75"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                     @click.stop
                     class="relative w-full max-w-[390px] sm:max-w-[420px] rounded-[2rem] p-[1px] bg-gradient-to-br from-blue-400/35 via-white/15 to-orange-400/35 shadow-[0_20px_50px_rgba(0,0,0,0.45)] transition-all duration-300 z-10 my-auto">
                    
                    <!-- Pure Glassmorphism Card (Translucent, Non-scrollable) -->
                    <div class="w-full rounded-[calc(2rem-1px)] bg-[#0a0f1d]/50 backdrop-blur-md p-5 sm:p-7 text-white relative overflow-hidden">

                        <!-- Subtle Top Glass Specular Reflection -->
                        <div class="absolute top-0 inset-x-0 h-[1px] bg-gradient-to-r from-transparent via-white/25 to-transparent pointer-events-none"></div>

                        <!-- Close Button (X) -->
                        <button @click="closeLoginModal()" 
                                type="button" 
                                aria-label="Tutup"
                                class="absolute top-4 right-4 w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 border border-white/15 flex items-center justify-center text-zinc-300 hover:text-white transition-all duration-200 hover:scale-110 active:scale-95 z-20 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <!-- Header Brand Emblem & Title -->
                        <div class="flex flex-col items-center justify-center text-center mb-5 relative z-10">
                            <!-- Logo (Clean without box wrapper) -->
                            <div class="relative mb-3 flex items-center justify-center">
                                @if ($logo)
                                    <img src="{{ asset('storage/' . $logo) }}" alt="Logo" class="w-16 h-16 sm:w-20 sm:h-20 object-contain drop-shadow-[0_8px_20px_rgba(0,0,0,0.55)]" />
                                @else
                                    <img src="{{ asset('images/logo_alazhar7.png') }}" alt="Logo SMA Islam Al Azhar 7 Solo Baru" class="w-16 h-16 sm:w-20 sm:h-20 object-contain drop-shadow-[0_8px_20px_rgba(0,0,0,0.55)]">
                                @endif
                            </div>

                            <h3 id="login-modal-title" class="text-2xl sm:text-[1.75rem] font-black font-display text-white tracking-tight leading-tight">
                                LOGIN TAD
                            </h3>
                            
                            <p class="text-xs sm:text-sm text-zinc-300 font-medium mt-1 tracking-wide">
                                {{ ($namaInstansi && !in_array($namaInstansi, ['SMAIA 7', 'SMAIA7', 'SMA Islam Al Azhar 7 Sukoharjo'])) ? $namaInstansi : 'SMA Islam Al Azhar 7 Solo Baru' }}
                            </p>
                        </div>

                        <!-- Session Status Alert -->
                        @if (session('status'))
                            <div class="mb-3 text-center text-xs font-bold text-amber-300 bg-amber-500/15 border border-amber-400/30 p-2.5 rounded-xl backdrop-blur-md">
                                {{ session('status') }}
                            </div>
                        @endif

                        <!-- Login Form -->
                        <form method="POST" action="{{ route('login') }}" class="space-y-3.5 relative z-10">
                            @csrf

                            <!-- Username / Email Field with Blue Accent Icon -->
                            <div>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-blue-600">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                        </svg>
                                    </div>
                                    <input type="text" 
                                           name="username" 
                                           value="{{ old('username') }}" 
                                           required 
                                           autocomplete="username"
                                           placeholder="Username"
                                           class="w-full pl-11 pr-4 py-3 rounded-2xl bg-white text-zinc-900 font-semibold placeholder-zinc-400 text-base sm:text-sm border-2 border-transparent focus:border-orange-500 focus:ring-4 focus:ring-blue-500/25 shadow-inner focus:outline-none transition-all duration-200">
                                </div>
                                @if ($errors->has('username'))
                                    <p class="text-xs font-semibold text-rose-400 mt-1 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ $errors->first('username') }}</span>
                                    </p>
                                @endif
                            </div>

                            <!-- Password Field with Lock Icon & Show/Hide Eye Toggle -->
                            <div x-data="{ showPass: false }">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-orange-500">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                        </svg>
                                    </div>
                                    <input :type="showPass ? 'text' : 'password'" 
                                           name="password" 
                                           required 
                                           autocomplete="current-password"
                                           placeholder="Password"
                                           class="w-full pl-11 pr-11 py-3 rounded-2xl bg-white text-zinc-900 font-semibold placeholder-zinc-400 text-base sm:text-sm border-2 border-transparent focus:border-orange-500 focus:ring-4 focus:ring-blue-500/25 shadow-inner focus:outline-none transition-all duration-200">
                                    
                                    <!-- Eye Toggle Icon Button -->
                                    <button type="button" 
                                            @click="showPass = !showPass" 
                                            aria-label="Toggle password visibility"
                                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-zinc-400 hover:text-orange-500 focus:outline-none transition-colors cursor-pointer">
                                        <svg x-show="!showPass" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                        </svg>
                                        <svg x-show="showPass" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </button>
                                </div>
                                @if ($errors->has('password'))
                                    <p class="text-xs font-semibold text-rose-400 mt-1 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ $errors->first('password') }}</span>
                                    </p>
                                @endif
                            </div>

                            <!-- Remember Me & Forgot Password -->
                            <div class="flex items-center justify-between text-xs text-zinc-300 pt-0.5">
                                <label class="inline-flex items-center cursor-pointer select-none hover:text-white transition-colors">
                                    <input type="checkbox" name="remember" class="rounded border-zinc-500 bg-black/40 text-orange-500 focus:ring-blue-500/30 w-4 h-4 mr-2">
                                    <span>Ingat saya</span>
                                </label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="text-orange-400 hover:text-orange-300 font-medium transition-colors">
                                        Lupa Password?
                                    </a>
                                @endif
                            </div>

                            <!-- Gradient Blue-to-Orange Submit Button (Clean Shadow, No Harsh Glow) -->
                            <div class="pt-2">
                                <button type="submit"
                                        class="w-full py-3.5 px-6 rounded-2xl font-black text-sm uppercase tracking-widest text-white bg-gradient-to-r from-blue-600 via-amber-500 to-orange-500 hover:from-blue-500 hover:via-amber-400 hover:to-orange-400 active:scale-[0.98] shadow-lg shadow-orange-500/25 hover:shadow-orange-500/35 transition-all duration-300 flex items-center justify-center gap-2 cursor-pointer">
                                    <span>LOGIN</span>
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                    </svg>
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>

        </div>
    </body>
</html>
