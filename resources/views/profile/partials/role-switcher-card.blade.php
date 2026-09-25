@php
    $assignedRoles = $user->assignedRoles();
    $currentRole = $user->currentRole();
@endphp

@if ($assignedRoles->count() > 1)
    <section class="space-y-4">
        <header>
            <div class="flex items-center gap-2">
                <span class="p-1.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-base font-bold text-zinc-900 dark:text-white">
                        {{ __('Ganti Peran Aktif (Role Switcher)') }}
                    </h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Akun Anda memiliki beberapa peran/tugas rangkap. Pilih peran yang ingin Anda aktifkan saat ini.') }}
                    </p>
                </div>
            </div>
        </header>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
            @foreach ($assignedRoles as $r)
                @php
                    $isActive = ($currentRole?->id === $r->id);
                @endphp
                <div class="p-4 rounded-xl border {{ $isActive ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/20 shadow-sm' : 'border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/30' }} flex flex-col justify-between gap-3 transition">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="font-bold text-sm text-zinc-900 dark:text-white">
                                    {{ $r->display_name }}
                                </h4>
                                @if ($r->id === $user->role_id)
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-zinc-200/80 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
                                        Peran Utama
                                    </span>
                                @endif
                            </div>
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                                Akses menu &amp; dashboard khusus {{ $r->display_name }}
                            </p>
                        </div>

                        @if ($isActive)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span> Aktif
                            </span>
                        @endif
                    </div>

                    @if (! $isActive)
                        <form method="POST" action="{{ route('role.switch') }}">
                            @csrf
                            <input type="hidden" name="role_id" value="{{ $r->id }}">
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition shadow-sm cursor-pointer">
                                <span>Beralih ke Peran Ini</span>
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </form>
                    @else
                        <div class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-400 flex items-center gap-1.5">
                            <x-heroicon-m-check class="w-4 h-4 shrink-0" />
                            <span>Anda sedang bekerja sebagai {{ $r->display_name }}</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
@endif
