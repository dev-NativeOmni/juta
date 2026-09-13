@php
    $logo = \App\Models\Setting::get('logo');
    $namaInstansi = \App\Models\Setting::get('nama_instansi');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'IMS SMAIA 7') }} - {{ __('Verifikasi Dua Faktor') }}</title>

    <link rel="icon" type="image/png" href="/images/logo_alazhar7.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-display { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body class="min-h-[100dvh] flex items-center justify-center p-4 sm:p-6 antialiased relative overflow-x-hidden selection:bg-amber-500 selection:text-white bg-cover bg-center bg-no-repeat bg-fixed bg-zinc-950"
      style="background-image: url('{{ asset('images/school_sunset_bg.jpg') }}');">

    <div class="absolute inset-0 pointer-events-none z-0">
        <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/50 to-black/80"></div>
        <div class="absolute -top-32 left-1/4 w-[500px] h-[500px] bg-orange-500/20 rounded-full blur-[140px]"></div>
        <div class="absolute top-1/3 -right-32 w-[550px] h-[550px] bg-blue-500/15 rounded-full blur-[150px]"></div>
    </div>

    <div class="w-full max-w-[400px] sm:max-w-[420px] relative z-10 my-auto py-3 sm:py-6">
        <div class="relative w-full p-5 xs:p-7 sm:p-9 rounded-[1.75rem] sm:rounded-[2.25rem] bg-gradient-to-b from-white/15 via-white/[0.08] to-white/[0.03] backdrop-blur-3xl border border-white/30 dark:border-amber-400/25 shadow-[0_25px_60px_-15px_rgba(0,0,0,0.8),inset_0_1px_1px_rgba(255,255,255,0.4)] text-white">

            <div class="flex flex-col items-center justify-center text-center mb-6 relative z-10">
                <div class="relative w-16 h-16 rounded-2xl bg-gradient-to-b from-white/20 to-black/40 backdrop-blur-xl border border-amber-400/50 p-2.5 shadow-[0_0_30px_rgba(245,158,11,0.35)] flex items-center justify-center mb-3.5">
                    <svg class="w-8 h-8 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                </div>

                <h1 class="text-xl sm:text-2xl font-black font-display text-white tracking-tight leading-tight">
                    Verifikasi <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-300 via-amber-400 to-orange-400">Dua Faktor</span>
                </h1>

                <p class="text-xs text-zinc-300/90 font-medium mt-2">
                    Masukkan kode 6 digit dari aplikasi authenticator Anda, atau salah satu kode pemulihan.
                </p>
            </div>

            <form method="POST" action="{{ route('two-factor.challenge.store') }}" class="space-y-4 relative z-10">
                @csrf

                <div>
                    <input type="text"
                           name="code"
                           inputmode="numeric"
                           autocomplete="one-time-code"
                           required
                           autofocus
                           placeholder="123456 atau kode pemulihan"
                           class="w-full text-center tracking-[0.3em] font-mono px-4 py-3.5 rounded-2xl bg-black/35 border border-amber-400/40 focus:border-amber-400 text-white placeholder-zinc-400 placeholder:tracking-normal placeholder:font-sans text-sm focus:outline-none focus:ring-2 focus:ring-amber-400/30 backdrop-blur-md transition-all duration-200">

                    @if ($errors->has('code'))
                        <p class="text-xs font-semibold text-rose-400 mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <span>{{ $errors->first('code') }}</span>
                        </p>
                    @endif
                </div>

                <div class="pt-1">
                    <button type="submit"
                            class="w-full py-3.5 px-6 rounded-2xl font-black text-sm uppercase tracking-widest text-zinc-950 bg-gradient-to-r from-amber-300 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-orange-500 shadow-[0_0_25px_rgba(245,158,11,0.45)] hover:shadow-[0_0_35px_rgba(245,158,11,0.7)] transition-all duration-300 hover:scale-[1.02] active:scale-[0.98] cursor-pointer">
                        VERIFIKASI
                    </button>
                </div>
            </form>

            <div class="text-center mt-5">
                <a href="{{ route('login') }}" class="text-xs font-semibold text-zinc-400 hover:text-white transition-colors">
                    Batalkan &amp; kembali ke login
                </a>
            </div>

        </div>
    </div>

</body>
</html>
