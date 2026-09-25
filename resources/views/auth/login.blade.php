@php
    try {
        $logo = class_exists(\App\Models\Setting::class) && \Illuminate\Support\Facades\Schema::hasTable('settings') 
            ? \App\Models\Setting::get('logo') 
            : null;
        $namaInstansi = class_exists(\App\Models\Setting::class) && \Illuminate\Support\Facades\Schema::hasTable('settings') 
            ? \App\Models\Setting::get('nama_instansi') 
            : null;
        $loginBg = class_exists(\App\Models\Setting::class) && \Illuminate\Support\Facades\Schema::hasTable('settings') 
            ? \App\Models\Setting::get('login_bg') 
            : null;
    } catch (\Throwable $e) {
        $logo = null;
        $namaInstansi = null;
        $loginBg = null;
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'TAD SMAIA 7') }} - {{ __('Masuk') }}</title>

    <!-- PWA & Apple iOS Metadata -->
    @include('partials.app-icons', ['themeColor' => '#ea580c'])

    <!-- iOS Safari BFCache & Session Expiry Safeguard -->
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
        @media (min-height: 520px) {
            body {
                overflow-y: hidden !important;
            }
        }
        @media (max-height: 519px) {
            body {
                overflow-y: auto !important;
            }
        }
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
    </style>
</head>
<body class="min-h-[100dvh] w-full flex flex-col items-center justify-center p-3 sm:p-6 antialiased relative selection:bg-orange-500 selection:text-white bg-cover bg-center bg-no-repeat bg-fixed"
      style="background-image: url('{{ $loginBg ? asset('storage/' . $loginBg) : asset('images/school_sunset_bg.jpg') }}');">

    <!-- Backdrop Overlay: Moderate Blur (Not too thick) & Soft Dimming -->
    <div class="absolute inset-0 pointer-events-none z-0 overflow-hidden">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-[6px]"></div>
    </div>

    <!-- Main Container (Fixed, Non-scrollable) -->
    <div class="w-full max-w-[390px] sm:max-w-[420px] relative z-10 flex flex-col items-center justify-center">

        <!-- Glassmorphic Card Container (Clean Frosted Glass, No Glow) -->
        <div class="relative w-full rounded-[2rem] p-[1px] bg-gradient-to-br from-blue-400/35 via-white/15 to-orange-400/35 shadow-[0_20px_50px_rgba(0,0,0,0.45)] transition-all duration-300">
            
            <!-- Inner Pure Glass Card (Translucent, Non-scrollable) -->
            <div class="w-full rounded-[calc(2rem-1px)] bg-[#0a0f1d]/50 backdrop-blur-md p-5 sm:p-7 text-white relative overflow-hidden">

                <!-- Subtle Top Glass Specular Reflection -->
                <div class="absolute top-0 inset-x-0 h-[1px] bg-gradient-to-r from-transparent via-white/25 to-transparent pointer-events-none"></div>

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

                    <h1 class="text-2xl sm:text-[1.75rem] font-black font-display text-white tracking-tight leading-tight">
                        LOGIN TAD
                    </h1>
                    
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
                                   autofocus 
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

        <!-- Back to Welcome Page Link -->
        <div class="text-center mt-4">
            <a href="{{ url('/') }}" class="text-xs font-semibold text-zinc-400 hover:text-white transition-colors inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-black/40 hover:bg-black/60 backdrop-blur-md border border-white/10 hover:border-orange-500/40 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
                <span>Kembali ke Halaman Utama</span>
            </a>
        </div>

    </div>

</body>
</html>
