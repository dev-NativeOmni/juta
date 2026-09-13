<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-lg sm:text-xl text-zinc-900 dark:text-white leading-tight">
            {{ __('Autentikasi Dua Faktor') }}
        </h2>
    </x-slot>

    <div class="py-3 sm:py-6">
        <div class="max-w-2xl mx-auto space-y-4 sm:space-y-6">

            @if (session('success'))
                <div class="p-4 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 rounded-xl text-sm font-bold">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="p-4 bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 rounded-xl text-sm font-bold">
                    {{ session('warning') }}
                </div>
            @endif

            @if ($required && ! $enabled)
                <div class="p-4 bg-red-50 dark:bg-red-950/60 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 rounded-xl text-sm font-bold">
                    Role Anda ({{ auth()->user()->role?->display_name ?? auth()->user()->role?->name }}) mewajibkan autentikasi dua faktor aktif sebelum bisa mengakses fitur lain.
                </div>
            @endif

            <div class="p-4 sm:p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl">
                <header class="mb-4">
                    <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Status</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        Autentikasi dua faktor menambahkan lapisan keamanan tambahan: selain password, Anda perlu memasukkan kode dari aplikasi authenticator (Google Authenticator, Authy, dll) setiap kali login.
                    </p>
                </header>

                @if ($enabled)
                    <div class="flex items-center gap-2 text-emerald-700 dark:text-emerald-400 font-bold text-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Aktif</span>
                    </div>
                @else
                    <div class="flex items-center gap-2 text-zinc-500 dark:text-zinc-400 font-bold text-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                        </svg>
                        <span>Belum Aktif</span>
                    </div>
                @endif
            </div>

            @if ($recoveryCodes)
                <div class="p-4 sm:p-6 bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 shadow-sm rounded-xl">
                    <h3 class="text-sm font-bold text-amber-900 dark:text-amber-200">Simpan Kode Pemulihan Ini</h3>
                    <p class="mt-1 text-xs text-amber-800 dark:text-amber-300">
                        Setiap kode hanya bisa dipakai satu kali untuk masuk jika Anda kehilangan akses ke aplikasi authenticator. Simpan di tempat aman &mdash; kode ini tidak akan ditampilkan lagi.
                    </p>
                    <div class="mt-3 grid grid-cols-2 gap-2 font-mono text-sm">
                        @foreach ($recoveryCodes as $recoveryCode)
                            <div class="px-3 py-2 bg-white dark:bg-zinc-900 border border-amber-200 dark:border-amber-800 rounded-lg text-zinc-800 dark:text-zinc-200">{{ $recoveryCode }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (! $enabled)
                @if (! $pendingSecret)
                    <div class="p-4 sm:p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl">
                        <form method="POST" action="{{ route('two-factor.enable') }}">
                            @csrf
                            <x-primary-button type="submit">Aktifkan Autentikasi Dua Faktor</x-primary-button>
                        </form>
                    </div>
                @else
                    <div class="p-4 sm:p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl space-y-4">
                        <div>
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-white">1. Pindai kode QR ini</h3>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Gunakan aplikasi authenticator seperti Google Authenticator atau Authy.</p>
                            <div class="mt-3 inline-block bg-white p-3 rounded-xl border border-zinc-200">
                                {!! $qrCodeSvg !!}
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Atau masukkan kode manual ini</h3>
                            <p class="mt-1 font-mono text-sm tracking-widest bg-zinc-100 dark:bg-zinc-800 px-3 py-2 rounded-lg inline-block text-zinc-800 dark:text-zinc-200 select-all">{{ $pendingSecret }}</p>
                        </div>

                        <div>
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-white mb-2">2. Masukkan kode 6 digit untuk konfirmasi</h3>
                            <form method="POST" action="{{ route('two-factor.confirm') }}" class="flex flex-col sm:flex-row gap-3">
                                @csrf
                                <x-text-input type="text" inputmode="numeric" autocomplete="one-time-code" name="code" placeholder="123456" class="sm:max-w-[160px]" required autofocus />
                                <x-primary-button type="submit">Konfirmasi &amp; Aktifkan</x-primary-button>
                            </form>
                            <x-input-error :messages="$errors->get('code')" class="mt-2" />
                        </div>
                    </div>
                @endif
            @else
                <div class="p-4 sm:p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-white">Buat Ulang Kode Pemulihan</h3>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Kode pemulihan lama akan langsung tidak berlaku setelah ini.</p>
                    <form method="POST" action="{{ route('two-factor.recovery-codes') }}" class="mt-3 flex flex-col sm:flex-row gap-3">
                        @csrf
                        <x-password-input name="password" placeholder="Password saat ini" class="sm:max-w-[220px]" required autocomplete="current-password" />
                        <x-secondary-button type="submit">Buat Ulang Kode</x-secondary-button>
                    </form>
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                @unless ($required)
                    <div class="p-4 sm:p-6 bg-white dark:bg-zinc-900 border border-red-200 dark:border-red-900 shadow-sm rounded-xl">
                        <h3 class="text-sm font-bold text-red-700 dark:text-red-400">Nonaktifkan Autentikasi Dua Faktor</h3>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Ini akan mengurangi keamanan akun Anda.</p>
                        <form method="POST" action="{{ route('two-factor.disable') }}" class="mt-3 flex flex-col sm:flex-row gap-3">
                            @csrf
                            @method('delete')
                            <x-password-input name="password" placeholder="Password saat ini" class="sm:max-w-[220px]" required autocomplete="current-password" />
                            <x-danger-button type="submit">Nonaktifkan</x-danger-button>
                        </form>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                @endunless
            @endif

        </div>
    </div>
</x-app-layout>
