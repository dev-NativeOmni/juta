@php
    $user = auth()->user();
    $currentRoleName = $user?->currentRole()?->name ?? $user?->role?->name;

    $routeIs = fn($patterns) => request()->routeIs($patterns);

    // Build role-specific left 2 items and right 1 item (+ 1 drawer "Lainnya" at far right)
    // Beranda/Dashboard is ALWAYS positioned in the center (Position 3) with prominent elevated styling!
    $leftItems = [];
    $rightItems = [];

    switch ($currentRoleName) {
        case 'teacher':
            $leftItems = [
                [
                    'label' => 'Hafalan',
                    'route' => 'hafalan-records.index',
                    'active' => $routeIs('hafalan-records.*'),
                    'icon' => 'book',
                ],
                [
                    'label' => 'Input',
                    'route' => 'spreadsheet-input.index',
                    'active' => $routeIs('spreadsheet-input.*'),
                    'icon' => 'table',
                ],
            ];
            $rightItems = [
                [
                    'label' => 'Adab',
                    'route' => 'adab.index',
                    'active' => $routeIs('adab.*'),
                    'icon' => 'shield',
                ],
            ];
            break;

        case 'parent':
            $leftItems = [
                [
                    'label' => 'Tahfizh',
                    'route' => 'progress.index',
                    'active' => $routeIs('progress.*'),
                    'icon' => 'book',
                ],
                [
                    'label' => 'Adab',
                    'route' => 'adab.index',
                    'active' => $routeIs('adab.*'),
                    'icon' => 'shield',
                ],
            ];
            $rightItems = [
                [
                    'label' => 'Mushaf',
                    'route' => 'quran.mushaf',
                    'active' => $routeIs('quran.mushaf'),
                    'icon' => 'quran',
                ],
            ];
            break;

        case 'student':
            $leftItems = [
                [
                    'label' => 'Hafalan',
                    'route' => 'progress.index',
                    'active' => $routeIs('progress.*'),
                    'icon' => 'book',
                ],
                [
                    'label' => 'Adab',
                    'route' => 'adab.index',
                    'active' => $routeIs('adab.*'),
                    'icon' => 'shield',
                ],
            ];
            $rightItems = [
                [
                    'label' => 'Mushaf',
                    'route' => 'quran.mushaf',
                    'active' => $routeIs('quran.mushaf'),
                    'icon' => 'quran',
                ],
            ];
            break;

        case 'coordinator_tahfizh':
            $leftItems = [
                [
                    'label' => 'Hafalan',
                    'route' => 'hafalan-records.index',
                    'active' => $routeIs('hafalan-records.*'),
                    'icon' => 'book',
                ],
                [
                    'label' => 'Grafik',
                    'route' => 'reports.periodic',
                    'active' => $routeIs('reports.periodic*'),
                    'icon' => 'chart',
                ],
            ];
            $rightItems = [
                [
                    'label' => 'Progres',
                    'route' => 'progress.index',
                    'active' => $routeIs('progress.*'),
                    'icon' => 'chart',
                ],
            ];
            break;

        case 'supervisor':
        case 'pendamping_adab':
            $leftItems = [
                [
                    'label' => 'Adab',
                    'route' => 'adab.index',
                    'active' => $routeIs('adab.index') || $routeIs('adab.show'),
                    'icon' => 'shield',
                ],
                [
                    'label' => 'Grafik',
                    'route' => 'adab.chart',
                    'active' => $routeIs('adab.chart'),
                    'icon' => 'chart',
                ],
            ];
            $rightItems = [
                [
                    'label' => 'Materi',
                    'route' => 'adab-materials.index',
                    'active' => $routeIs('adab-materials.*'),
                    'icon' => 'book-open',
                ],
            ];
            break;

        case 'wali_kelas':
            $leftItems = [
                [
                    'label' => 'Tahfizh',
                    'route' => 'reports.periodic',
                    'active' => $routeIs('reports.periodic*') || $routeIs('progress.*'),
                    'icon' => 'chart',
                ],
                [
                    'label' => 'Adab',
                    'route' => 'adab.chart',
                    'active' => $routeIs('adab.*'),
                    'icon' => 'shield',
                ],
            ];
            $rightItems = [
                [
                    'label' => 'Disiplin',
                    'route' => 'student-points.chart',
                    'active' => $routeIs('student-points.*'),
                    'icon' => 'star',
                ],
            ];
            break;

        case 'headmaster':
            $leftItems = [
                [
                    'label' => 'Tahfizh',
                    'route' => 'reports.periodic',
                    'active' => $routeIs('reports.periodic*'),
                    'icon' => 'chart',
                ],
                [
                    'label' => 'Adab',
                    'route' => 'adab.chart',
                    'active' => $routeIs('adab.chart'),
                    'icon' => 'shield',
                ],
            ];
            $rightItems = [
                [
                    'label' => 'Tanse',
                    'route' => 'student-points.chart',
                    'active' => $routeIs('student-points.chart'),
                    'icon' => 'star',
                ],
            ];
            break;

        case 'tanse':
            $leftItems = [
                [
                    'label' => 'Poin',
                    'route' => 'student-points.index',
                    'active' => $routeIs('student-points.*') && !$routeIs('student-points.chart'),
                    'icon' => 'star',
                ],
                [
                    'label' => 'Grafik',
                    'route' => 'student-points.chart',
                    'active' => $routeIs('student-points.chart'),
                    'icon' => 'chart',
                ],
            ];
            $rightItems = [
                [
                    'label' => 'Pesan',
                    'route' => 'system-notifications.index',
                    'active' => $routeIs('system-notifications.*'),
                    'icon' => 'bell',
                ],
            ];
            break;

        default: // super_admin, admin
            $leftItems = [
                [
                    'label' => 'Murid',
                    'route' => 'students.index',
                    'active' => $routeIs('students.*'),
                    'icon' => 'users',
                ],
                [
                    'label' => 'Tahfizh',
                    'route' => 'hafalan-records.index',
                    'active' => $routeIs('hafalan-records.*'),
                    'icon' => 'book',
                ],
            ];
            $rightItems = [
                [
                    'label' => 'Grafik',
                    'route' => 'reports.periodic',
                    'active' => $routeIs('reports.periodic*'),
                    'icon' => 'chart',
                ],
            ];
            break;
    }

    $isDashboardActive = $routeIs('dashboard') || $routeIs('*.dashboard') || request()->is('*/dashboard*') || request()->is('dashboard');
@endphp

<!-- Bottom Floating Navigation Bar in SAPA SMAIA 7 Style (Mobile & Tablet / iPad) -->
<nav x-data="{
         lastScrollY: 0,
         hideNav: false,
         inputFocused: false,
         touchStartY: 0,
         init() {
             // 1. Scroll detection on window
             window.addEventListener('scroll', () => {
                 const currentY = window.scrollY || window.pageYOffset || 0;
                 
                 // If near top or bottom of page, always show
                 if (currentY < 40) {
                     this.hideNav = false;
                     this.lastScrollY = currentY;
                     return;
                 }
                 
                 const isAtBottom = (window.innerHeight + currentY) >= (document.documentElement.scrollHeight - 30);
                 if (isAtBottom) {
                     this.hideNav = false;
                     this.lastScrollY = currentY;
                     return;
                 }
                 
                 const diff = currentY - this.lastScrollY;
                 if (diff > 12) {
                     // Scrolling DOWN -> smoothly hide nav so content is not blocked
                     this.hideNav = true;
                 } else if (diff < -10) {
                     // Scrolling UP -> smoothly restore nav
                     this.hideNav = false;
                 }
                 
                 this.lastScrollY = currentY;
             }, { passive: true });

             // 2. Touch gesture detection (works inside overflow containers & tables too)
             window.addEventListener('touchstart', (e) => {
                 if (e.touches && e.touches[0]) {
                     this.touchStartY = e.touches[0].clientY;
                 }
             }, { passive: true });

             window.addEventListener('touchmove', (e) => {
                 if (e.touches && e.touches[0]) {
                     const currentTouchY = e.touches[0].clientY;
                     const diff = this.touchStartY - currentTouchY;
                     
                     if (diff > 25 && (window.scrollY || 0) > 30) {
                         // Dragging finger up (scrolling down page) -> hide
                         this.hideNav = true;
                         this.touchStartY = currentTouchY;
                     } else if (diff < -20) {
                         // Dragging finger down (scrolling up page) -> show
                         this.hideNav = false;
                         this.touchStartY = currentTouchY;
                     }
                 }
             }, { passive: true });

             // 3. Virtual keyboard detection (hide when entering inputs so it never floats above keyboard)
             document.addEventListener('focusin', (e) => {
                 if (e.target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) {
                     this.inputFocused = true;
                 }
             });
             document.addEventListener('focusout', (e) => {
                 if (e.target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) {
                     this.inputFocused = false;
                 }
             });

             // 4. Orientation & resize reset
             window.addEventListener('resize', () => {
                 this.hideNav = false;
             });
         }
     }"
     x-show="!sidebarOpen && !inputFocused"
     x-transition:enter="transition-opacity ease-out duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-in duration-100"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     :class="hideNav ? 'translate-y-full opacity-0 pointer-events-none' : 'translate-y-0 opacity-100'"
     class="xl:hidden fixed inset-x-0 bottom-0 z-50 mobile-bottom-bar-fixed bg-white/95 dark:bg-[#09090b]/95 backdrop-blur-xl border-t border-zinc-200/80 dark:border-white/10 shadow-[0_-4px_25px_rgba(0,0,0,0.06)] dark:shadow-[0_-4px_25px_rgba(0,0,0,0.4)] transition-all duration-300 ease-in-out"
     style="position: fixed !important; bottom: 0 !important; left: 0 !important; right: 0 !important; z-index: 50 !important;"
     aria-label="Navigasi Bawah">
    
    <div class="flex items-center justify-around h-14 sm:h-16 md:h-16 landscape:h-13 md:landscape:h-16 max-w-md sm:max-w-lg md:max-w-xl lg:max-w-2xl mx-auto px-1.5 sm:px-3 relative">

        <!-- 1. LEFT ITEM 1 -->
        @if (isset($leftItems[0]) && \Illuminate\Support\Facades\Route::has($leftItems[0]['route']))
            <a href="{{ route($leftItems[0]['route']) }}"
               class="flex-1 flex flex-col items-center justify-center h-full py-1 text-center select-none group transition-colors duration-150 {{ $leftItems[0]['active'] ? 'text-teal-600 dark:text-teal-400 font-bold' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 font-medium' }}">
                <div class="p-1 rounded-xl transition-all duration-200 {{ $leftItems[0]['active'] ? 'scale-110' : 'group-active:scale-95' }}">
                    @if ($leftItems[0]['icon'] === 'book')
                        <svg class="w-5 h-5 md:w-5.5 md:h-5.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $leftItems[0]['active'] ? '2.4' : '1.8' }}" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    @elseif ($leftItems[0]['icon'] === 'users')
                        <svg class="w-5 h-5 md:w-5.5 md:h-5.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $leftItems[0]['active'] ? '2.4' : '1.8' }}" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    @elseif ($leftItems[0]['icon'] === 'shield')
                        <svg class="w-5 h-5 md:w-5.5 md:h-5.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $leftItems[0]['active'] ? '2.4' : '1.8' }}" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    @elseif ($leftItems[0]['icon'] === 'star')
                        <svg class="w-5 h-5 md:w-5.5 md:h-5.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $leftItems[0]['active'] ? '2.4' : '1.8' }}" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    @elseif ($leftItems[0]['icon'] === 'chart')
                        <svg class="w-5 h-5 md:w-5.5 md:h-5.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $leftItems[0]['active'] ? '2.4' : '1.8' }}" d="M11 3.055A9.003 9.003 0 1020.945 13H11V3.055z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $leftItems[0]['active'] ? '2.4' : '1.8' }}" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                        </svg>
                    @endif
                </div>
                <span class="text-[9px] sm:text-[10px] md:text-xs mt-0.5 tracking-tight leading-none truncate max-w-full px-0.5">{{ $leftItems[0]['label'] }}</span>
            </a>
        @endif

        <!-- 2. LEFT ITEM 2 -->
        @if (isset($leftItems[1]) && \Illuminate\Support\Facades\Route::has($leftItems[1]['route']))
            <a href="{{ route($leftItems[1]['route']) }}"
               class="flex-1 flex flex-col items-center justify-center h-full py-1 text-center select-none group transition-colors duration-150 {{ $leftItems[1]['active'] ? 'text-teal-600 dark:text-teal-400 font-bold' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 font-medium' }}">
                <div class="p-1 rounded-xl transition-all duration-200 {{ $leftItems[1]['active'] ? 'scale-110' : 'group-active:scale-95' }}">
                    @if ($leftItems[1]['icon'] === 'table')
                        <svg class="w-5 h-5 md:w-5.5 md:h-5.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $leftItems[1]['active'] ? '2.4' : '1.8' }}" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    @elseif ($leftItems[1]['icon'] === 'book')
                        <svg class="w-5 h-5 md:w-5.5 md:h-5.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $leftItems[1]['active'] ? '2.4' : '1.8' }}" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    @elseif ($leftItems[1]['icon'] === 'shield')
                        <svg class="w-5 h-5 md:w-5.5 md:h-5.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $leftItems[1]['active'] ? '2.4' : '1.8' }}" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    @elseif ($leftItems[1]['icon'] === 'chart')
                        <svg class="w-5 h-5 md:w-5.5 md:h-5.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $leftItems[1]['active'] ? '2.4' : '1.8' }}" d="M11 3.055A9.003 9.003 0 1020.945 13H11V3.055z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $leftItems[1]['active'] ? '2.4' : '1.8' }}" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                        </svg>
                    @endif
                </div>
                <span class="text-[9px] sm:text-[10px] md:text-xs mt-0.5 tracking-tight leading-none truncate max-w-full px-0.5">{{ $leftItems[1]['label'] }}</span>
            </a>
        @endif

        <!-- 3. CENTER ELEVATED HOME / DASHBOARD BUTTON (BLUE-TO-ORANGE LIQUID GLASS WITH STATIC UPRIGHT ICON) -->
        <a href="{{ route('dashboard') }}"
           class="flex-1 flex flex-col items-center justify-center relative -top-3 sm:-top-3.5 landscape:-top-2 md:landscape:-top-3.5 z-20 select-none group">
            
            <!-- Outer Button Container -->
            <div class="relative w-12 h-12 sm:w-14 sm:h-14 landscape:w-11 landscape:h-11 md:landscape:w-14 md:landscape:h-14 flex items-center justify-center transition-transform duration-200 ease-out group-hover:scale-105 group-active:scale-90">
                
                <!-- 1. Ambient Halo Glow (Rotating + Breathing Aura behind the button) -->
                <div class="absolute inset-[-2px] rounded-full liquid-blue-orange-ring liquid-halo-pulse pointer-events-none"></div>

                <!-- 2. Sharp Rotating Glass Ring (Background Only) -->
                <div class="absolute inset-0 rounded-full liquid-blue-orange-ring liquid-ring-spin liquid-blue-orange-glow pointer-events-none"></div>
                
                <!-- 3. Static Dark Glass Concave Core (NEVER ROTATES) -->
                <div class="relative z-10 w-[calc(100%-4px)] h-[calc(100%-4px)] sm:w-[calc(100%-5px)] sm:h-[calc(100%-5px)] rounded-full liquid-dial-concave p-1.5 sm:p-2 flex items-center justify-center border border-white/20 backdrop-blur-md">
                    
                    <!-- 3D Silver Metallic Embossed Home Icon (ALWAYS STATIC & UPRIGHT) -->
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 metallic-icon-gradient liquid-icon-shimmer" viewBox="0 0 24 24">
                        <defs>
                            <linearGradient id="silverMetallicHome" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#ffffff" />
                                <stop offset="45%" stop-color="#e2e8f0" />
                                <stop offset="100%" stop-color="#94a3b8" />
                            </linearGradient>
                        </defs>
                        <path fill="url(#silverMetallicHome)" d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z" />
                        <path fill="url(#silverMetallicHome)" d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.432z" />
                    </svg>

                </div>
            </div>

            <span class="text-[9px] sm:text-[10px] md:text-xs font-bold tracking-tight mt-0.5 sm:mt-1 leading-none {{ $isDashboardActive ? 'text-teal-600 dark:text-teal-400 font-extrabold' : 'text-zinc-600 dark:text-zinc-300 font-medium' }} group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors">
                Beranda
            </span>
        </a>

        <!-- 4. RIGHT ITEM 1 -->
        @if (isset($rightItems[0]) && \Illuminate\Support\Facades\Route::has($rightItems[0]['route']))
            <a href="{{ route($rightItems[0]['route']) }}"
               class="flex-1 flex flex-col items-center justify-center h-full py-1 text-center select-none group transition-colors duration-150 {{ $rightItems[0]['active'] ? 'text-teal-600 dark:text-teal-400 font-bold' : 'text-zinc-500 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 font-medium' }}">
                <div class="p-1 rounded-xl transition-all duration-200 {{ $rightItems[0]['active'] ? 'scale-110' : 'group-active:scale-95' }}">
                    @if ($rightItems[0]['icon'] === 'shield')
                        <svg class="w-5 h-5 md:w-5.5 md:h-5.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $rightItems[0]['active'] ? '2.4' : '1.8' }}" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    @elseif ($rightItems[0]['icon'] === 'quran')
                        <svg class="w-5 h-5 md:w-5.5 md:h-5.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $rightItems[0]['active'] ? '2.4' : '1.8' }}" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    @elseif ($rightItems[0]['icon'] === 'chart')
                        <svg class="w-5 h-5 md:w-5.5 md:h-5.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $rightItems[0]['active'] ? '2.4' : '1.8' }}" d="M11 3.055A9.003 9.003 0 1020.945 13H11V3.055z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $rightItems[0]['active'] ? '2.4' : '1.8' }}" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                        </svg>
                    @elseif ($rightItems[0]['icon'] === 'book-open')
                        <svg class="w-5 h-5 md:w-5.5 md:h-5.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $rightItems[0]['active'] ? '2.4' : '1.8' }}" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    @elseif ($rightItems[0]['icon'] === 'star')
                        <svg class="w-5 h-5 md:w-5.5 md:h-5.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $rightItems[0]['active'] ? '2.4' : '1.8' }}" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    @elseif ($rightItems[0]['icon'] === 'bell')
                        <svg class="w-5 h-5 md:w-5.5 md:h-5.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="{{ $rightItems[0]['active'] ? '2.4' : '1.8' }}" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    @endif
                </div>
                <span class="text-[9px] sm:text-[10px] md:text-xs mt-0.5 tracking-tight leading-none truncate max-w-full px-0.5">{{ $rightItems[0]['label'] }}</span>
            </a>
        @endif

        <!-- 5. RIGHT ITEM 2: "Menu Lainnya" Drawer Trigger -->
        <button type="button"
                @click="sidebarOpen = true"
                class="flex-1 flex flex-col items-center justify-center h-full py-1 text-center select-none group transition-colors duration-150 text-zinc-500 dark:text-zinc-400 hover:text-teal-600 dark:hover:text-teal-400 font-medium cursor-pointer">
            <div class="p-1 rounded-xl transition-all duration-200 group-active:scale-95">
                <svg class="w-5 h-5 md:w-5.5 md:h-5.5 shrink-0 text-zinc-500 dark:text-zinc-400 group-hover:text-teal-600 dark:group-hover:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </div>
            <span class="text-[9px] sm:text-[10px] md:text-xs mt-0.5 tracking-tight leading-none">Lainnya</span>
        </button>

    </div>
</nav>
