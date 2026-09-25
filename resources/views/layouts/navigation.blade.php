@php
    $user = auth()->user();

    $logo = \App\Models\Setting::get('logo');

    $hasRoute = fn (string $name): bool => \Illuminate\Support\Facades\Route::has($name);
@endphp

<!-- Global Sidebar (Drawer Overlay) -->
<div x-show="sidebarOpen" class="fixed inset-0 flex z-50" role="dialog" aria-modal="true" style="display: none;">
    <!-- Backdrop Overlay -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="sidebarOpen = false"
         class="fixed inset-0 bg-zinc-900/60 dark:bg-[#09090b]/80 backdrop-blur-sm" aria-hidden="true"></div>

    <!-- Sidebar Drawer Panel -->
    <div x-show="sidebarOpen"
         x-transition:enter="transition ease-in-out duration-300 transform"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in-out duration-300 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="relative flex-1 flex flex-col max-w-xs w-full h-[100dvh] max-h-[100dvh] pt-[max(1.25rem,calc(0.75rem+env(safe-area-inset-top)))] pb-[max(1.25rem,calc(0.75rem+env(safe-area-inset-bottom)))] pl-[max(0rem,env(safe-area-inset-left))] bg-white/95 dark:bg-[#09090b]/95 backdrop-blur-xl border-r border-zinc-200 dark:border-white/5 shadow-2xl transition-colors duration-200">
         
         <!-- Close Button -->
         <div class="absolute top-0 right-0 -mr-12 pt-3">
             <button type="button" @click="sidebarOpen = false" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none cursor-pointer bg-black/20 text-white backdrop-blur-xs">
                 <span class="sr-only">Tutup sidebar</span>
                 <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                 </svg>
             </button>
         </div>

         <!-- Logo -->
         <div class="flex-shrink-0 flex items-center px-4">
             <a href="{{ route('dashboard') }}" class="flex items-center gap-2 shrink-0 select-none">
                 @if ($logo)
                     <img src="{{ asset('storage/' . $logo) }}" alt="Logo SMA Islam Al Azhar 7" class="h-8 w-8 object-contain shrink-0 drop-shadow-xs">
                 @else
                     <img src="{{ asset('images/logo_alazhar7.png') }}" alt="Logo SMA Islam Al Azhar 7" class="h-8 w-8 object-contain shrink-0 drop-shadow-xs">
                 @endif
                 <img src="{{ asset('images/logo-gemilang-banner.png') }}" alt="Logo Gemilang" class="h-6 max-w-[130px] sm:max-w-[150px] object-contain drop-shadow-xs shrink-0">
             </a>
         </div>

         <!-- Menu List -->
         <div class="mt-4 flex-1 h-0 overflow-y-auto overscroll-contain touch-scroll">
             <nav class="px-2 space-y-1">
                 @include('layouts.navigation-links')
             </nav>
         </div>

         <!-- Profile Footer -->
         <div class="flex-shrink-0 border-t border-zinc-200 dark:border-white/5 p-3 bg-zinc-50/50 dark:bg-[#09090b]/40 transition-colors duration-200">
             <!-- Row 1: Avatar + Name + Role -->
             <div class="flex items-center gap-2.5 min-w-0">
                 <div class="flex-shrink-0">
                     @if ($user?->avatar)
                         <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="w-8 h-8 rounded-full object-cover shadow-inner border border-zinc-200 dark:border-white/10">
                     @else
                         <div class="w-8 h-8 rounded-full bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20 flex items-center justify-center font-bold text-xs shadow-inner uppercase flex-shrink-0">
                             {{ substr($user?->name ?? 'U', 0, 1) }}
                         </div>
                     @endif
                 </div>
                 <div class="min-w-0 flex-1">
                     <p class="text-xs font-semibold text-zinc-800 dark:text-zinc-200 truncate leading-tight">
                         {{ $user?->name }}
                     </p>
                     <p class="text-[10px] text-zinc-500 dark:text-zinc-400 truncate leading-tight mt-0.5 font-bold">
                         {{ $user?->currentRole()?->display_name ?? $user?->role?->display_name ?? '-' }}
                     </p>
                 </div>
             </div>

             {{-- Multi-Role Switcher in Sidebar Drawer --}}
             @if ($user && $user->assignedRoles()->count() > 1)
                 <div class="mt-2 pt-2 border-t border-zinc-200/60 dark:border-white/5" x-data="{ switchOpen: false }">
                     <div class="flex items-center justify-between">
                         <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider flex items-center gap-1">
                             <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                             </svg>
                             Ganti Peran:
                         </span>
                         <button type="button" @click="switchOpen = !switchOpen" class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 hover:text-indigo-600 dark:hover:text-indigo-400 flex items-center gap-0.5 cursor-pointer">
                             <span x-text="switchOpen ? 'Tutup' : 'Pilih'"></span>
                             <svg class="w-3 h-3 transition-transform" :class="switchOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                             </svg>
                         </button>
                     </div>
                     <div x-show="switchOpen" class="mt-1.5 space-y-1" style="display: none;">
                         @foreach ($user->assignedRoles() as $ar)
                             @php $isCurrent = ($user->currentRole()?->id === $ar->id); @endphp
                             @if (! $isCurrent)
                                 <form method="POST" action="{{ route('role.switch') }}">
                                     @csrf
                                     <input type="hidden" name="role_id" value="{{ $ar->id }}">
                                     <button type="submit" class="w-full flex items-center justify-between px-2 py-1 rounded-md text-[11px] font-medium bg-white dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-indigo-50 dark:hover:bg-indigo-950 hover:text-indigo-600 dark:hover:text-indigo-300 border border-zinc-200 dark:border-zinc-700 transition cursor-pointer">
                                         <span>{{ $ar->display_name }}</span>
                                         <span class="inline-flex items-center gap-0.5 text-[9px] text-indigo-500 font-bold">Pilih <x-heroicon-m-arrow-right class="w-2.5 h-2.5" /></span>
                                     </button>
                                 </form>
                             @else
                                 <div class="px-2 py-1 rounded-md text-[11px] font-bold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 flex items-center justify-between">
                                     <span>{{ $ar->display_name }}</span>
                                     <span class="inline-flex items-center gap-1 text-[9px] font-bold text-emerald-600 dark:text-emerald-400"><x-heroicon-m-check class="w-3 h-3 stroke-[2.5]" /> Aktif</span>
                                 </div>
                             @endif
                         @endforeach
                     </div>
                 </div>
             @endif
             <!-- Row 2: Action Buttons -->
             <div class="flex items-center gap-1 mt-2 pt-2 border-t border-zinc-200/60 dark:border-white/5">
                 @if ($hasRoute('profile.edit'))
                     <a href="{{ route('profile.edit') }}" class="flex-1 inline-flex items-center justify-center gap-1.5 px-2 py-1.5 rounded-lg text-[10px] font-medium text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-white/5 border border-transparent hover:border-zinc-200 dark:hover:border-white/5 transition-all duration-150">
                         <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                         </svg>
                         Profil
                     </a>
                 @endif
                 <form method="POST" action="{{ route('logout') }}" class="flex-1">
                     @csrf
                     <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 px-2 py-1.5 rounded-lg text-[10px] font-medium text-zinc-500 dark:text-zinc-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-red-55/10 dark:hover:bg-red-500/10 border border-transparent hover:border-red-100 dark:hover:border-red-500/10 transition-all duration-150">
                         <svg class="h-3.5 w-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                         </svg>
                         Keluar
                     </button>
                 </form>
             </div>
         </div>
    </div>
    <!-- Dummy spacer to prevent overlay from closing immediately on tap close to edges -->
    <div class="flex-shrink-0 w-14"></div>
</div>

<!-- Global Top Bar -->
<div class="sticky top-0 z-30 flex h-14 sm:h-16 bg-white/75 dark:bg-[#18181b]/70 backdrop-blur-xl border-b border-zinc-200/70 dark:border-white/10 flex-shrink-0 transition-colors duration-200 pl-[max(0rem,env(safe-area-inset-left))] pr-[max(0rem,env(safe-area-inset-right))] pt-[env(safe-area-inset-top)]">
    <button type="button" @click="sidebarOpen = true" class="px-3 sm:px-4 border-r border-zinc-200/70 dark:border-white/10 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 focus:outline-none cursor-pointer">
        <span class="sr-only">Buka sidebar</span>
        <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>
    <div class="flex-1 flex justify-between px-3 sm:px-4 items-center gap-2 min-w-0">
        <div class="flex items-center gap-2 sm:gap-3 shrink-0 min-w-0">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-1.5 sm:gap-2.5 shrink-0 select-none">
                @if ($logo)
                    <img src="{{ asset('storage/' . $logo) }}" alt="Logo SMA Islam Al Azhar 7" class="h-6 w-6 sm:h-8 sm:w-8 object-contain shrink-0 drop-shadow-xs">
                @else
                    <img src="{{ asset('images/logo_alazhar7.png') }}" alt="Logo SMA Islam Al Azhar 7" class="h-6 w-6 sm:h-8 sm:w-8 object-contain shrink-0 drop-shadow-xs">
                @endif
                <img src="{{ asset('images/logo-gemilang-banner.png') }}" alt="Logo Gemilang" class="h-4.5 sm:h-6 max-w-[95px] xs:max-w-[130px] sm:max-w-[160px] object-contain drop-shadow-xs shrink-0">
            </a>

            <!-- Theme Toggle -->
            <button @click="toggleTheme()" class="p-1 sm:p-2 rounded-xl bg-white/60 dark:bg-white/5 border border-zinc-200/70 dark:border-white/10 text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white transition-all duration-150 shadow-xs cursor-pointer shrink-0" title="Ubah Tema">
                <svg x-show="dark" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="display: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z" />
                </svg>
                <svg x-show="!dark" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </button>
        </div>

        <!-- Role badge & avatar pill button -->
        <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
            @if ($user)
                <button type="button" @click="sidebarOpen = true" class="hidden sm:flex items-center gap-2 py-1 px-2.5 rounded-full bg-white/70 dark:bg-white/5 border border-zinc-200/70 dark:border-white/10 hover:border-teal-500/30 transition-all text-left shadow-2xs cursor-pointer">
                    <span class="inline-block w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                    <span class="text-xs font-semibold text-zinc-700 dark:text-zinc-200 max-w-[130px] truncate">{{ $user->name }}</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200/50 dark:border-teal-500/20">{{ $user->currentRole()?->display_name ?? $user->role?->display_name ?? 'User' }}</span>
                </button>
            @endif

            <!-- Small visual avatar on the right -->
            @if ($user?->avatar)
                <button type="button" @click="sidebarOpen = true" class="w-8 h-8 sm:w-9 sm:h-9 rounded-full overflow-hidden border border-zinc-200/80 dark:border-white/10 shadow-sm cursor-pointer hover:ring-2 hover:ring-teal-500/30 transition-all shrink-0">
                    <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                </button>
            @else
                <button type="button" @click="sidebarOpen = true" class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200/60 dark:border-teal-500/20 flex items-center justify-center font-bold text-xs uppercase shadow-sm cursor-pointer hover:ring-2 hover:ring-teal-500/30 transition-all shrink-0">
                    {{ substr($user?->name ?? 'U', 0, 1) }}
                </button>
            @endif
        </div>
    </div>
</div>