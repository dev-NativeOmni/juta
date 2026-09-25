<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=5">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'TAD SMAIA 7') }}</title>

        <!-- PWA & Apple iOS Metadata -->
        @include('partials.app-icons')

        <!-- iOS Safari BFCache & PWA Service Worker -->
        <script>
            window.addEventListener('pageshow', function(event) {
                if (event.persisted) {
                    window.location.reload();
                }
            });

            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('/sw.js').catch(function(err) {});
                });
            }
        </script>

        <!-- Theme Initialization Script -->
        <script>
            if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark')
            } else {
                document.documentElement.classList.remove('dark')
            }
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    @php
        $bgSetting = \App\Models\Setting::get('background');
        $bgUrl = $bgSetting ? asset('storage/' . $bgSetting) : (file_exists(public_path('images/school_sunset_bg.jpg')) ? asset('images/school_sunset_bg.jpg') : null);
    @endphp
    <body class="font-sans antialiased bg-[#f8fafc] text-zinc-800 dark:bg-[#09090b] dark:text-zinc-100 transition-colors duration-200 selection:bg-orange-500 selection:text-white min-h-screen min-h-[100dvh]"
          x-data="{ sidebarOpen: false, dark: document.documentElement.classList.contains('dark'), toggleTheme() { this.dark = !this.dark; if (this.dark) { document.documentElement.classList.add('dark'); localStorage.setItem('theme', 'dark'); } else { document.documentElement.classList.remove('dark'); localStorage.setItem('theme', 'light'); } } }">
        <!-- Ambient Sunset & Gradient Background Layer -->
        <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden bg-cover bg-center bg-no-repeat transition-all duration-300"
             @if($bgUrl) style="background-image: url('{{ $bgUrl }}');" @endif>
            <!-- Frosted Contrast Overlay: Light Mode Soft Crystal & Dark Mode Deep Glass -->
            <div class="absolute inset-0 bg-slate-100/90 dark:bg-[#09090b]/92 backdrop-blur-2xl transition-colors duration-300"></div>
            
            <!-- Glow background mesh blobs matching landing page -->
            <div class="glow-blob bg-teal-500/15 w-[550px] h-[550px] -top-60 -left-60 blur-[140px] opacity-70 dark:opacity-25 transition-opacity duration-300"></div>
            <div class="glow-blob bg-orange-500/15 w-[500px] h-[500px] top-[25%] -right-40 blur-[150px] opacity-60 dark:opacity-20 transition-opacity duration-300"></div>
            <div class="glow-blob bg-emerald-500/15 w-[450px] h-[450px] -bottom-40 left-[20%] blur-[130px] opacity-60 dark:opacity-20 transition-opacity duration-300"></div>
        </div>

        <div class="min-h-screen min-h-[100dvh] relative z-10 flex flex-col">
            @if (session()->has('impersonated_by'))
                <div class="bg-amber-600 text-white px-4 py-2.5 shadow-md flex items-center justify-between z-50 text-xs sm:text-sm font-medium sticky top-0 pl-[max(1rem,env(safe-area-inset-left))] pr-[max(1rem,env(safe-area-inset-right))] pt-[max(0.625rem,env(safe-area-inset-top))]">
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-amber-200 animate-ping"></span>
                        <span class="inline-flex items-center gap-1.5"><x-heroicon-o-exclamation-triangle class="w-4 h-4 text-amber-100 shrink-0" /> <span><strong>Mode Impersonasi:</strong> Anda sedang meninjau sistem sebagai <strong>{{ auth()->user()?->name }}</strong> ({{ auth()->user()?->role?->display_name ?? auth()->user()?->role?->name }}).</span></span>
                    </div>
                    <form method="POST" action="{{ route('impersonate.stop') }}" class="inline">
                        @csrf
                        <button type="submit" class="bg-white/20 hover:bg-white/30 text-white px-3 py-1 rounded-md text-xs font-semibold backdrop-blur transition cursor-pointer">
                            Kembali ke Super Admin &rarr;
                        </button>
                    </form>
                </div>
            @endif

            @include('layouts.navigation')

            <div class="flex-grow flex flex-col">
                <!-- Page Heading -->
                @isset($header)
                    <header class="bg-white/75 dark:bg-[#18181b]/70 backdrop-blur-xl border-b border-zinc-200/70 dark:border-white/10 transition-colors duration-200 pl-[max(0.75rem,env(safe-area-inset-left))] pr-[max(0.75rem,env(safe-area-inset-right))]">
                        <div class="max-w-7xl mx-auto py-3.5 px-3 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="app-main-layout flex-1 py-4 sm:py-6 px-3 sm:px-6 lg:px-8 pl-[max(0.75rem,env(safe-area-inset-left))] pr-[max(0.75rem,env(safe-area-inset-right))]">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <!-- Mobile & Tablet Bottom Quick Navigation (Direct child of <body> for true fixed viewport positioning) -->
        @include('layouts.mobile-bottom-nav')

        <!-- Global Network Connection Toast -->
        <div x-data="{ isOnline: navigator.onLine, showToast: false }"
             x-init="
                 window.addEventListener('online', () => { isOnline = true; showToast = true; setTimeout(() => showToast = false, 4000); });
                 window.addEventListener('offline', () => { isOnline = false; showToast = true; });
             "
             x-show="showToast"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="translate-y-8 opacity-0"
             x-transition:enter-end="translate-y-0 opacity-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-y-0 opacity-100"
             x-transition:leave-end="translate-y-8 opacity-0"
             class="fixed bottom-24 xl:bottom-4 right-4 z-50 px-4 py-2.5 rounded-lg shadow-xl text-xs sm:text-sm font-semibold flex items-center gap-2 border"
             :class="isOnline ? 'bg-emerald-800 text-emerald-100 border-emerald-600' : 'bg-red-800 text-red-100 border-red-600'"
             style="display: none;">
            <template x-if="isOnline">
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Koneksi internet kembali terhubung
                </span>
            </template>
            <template x-if="!isOnline">
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-red-300 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    Koneksi terputus (Offline). Pengisian data tetap aman.
                </span>
            </template>
        </div>

        @stack('scripts')
    </body>
</html>
