<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-0.5">
            <div class="flex items-center justify-between gap-2">
                <h2 class="font-bold text-base sm:text-xl text-zinc-900 dark:text-white leading-tight">
                    Dashboard Orang Tua
                </h2>
                <span class="sm:hidden inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-teal-500/15 border border-teal-500/20 text-teal-700 dark:text-teal-300 text-[10px] font-bold uppercase tracking-wider">
                    <x-heroicon-o-sparkles class="w-3 h-3 text-teal-600 dark:text-teal-400" /> Portal Khusus Wali Murid
                </span>
            </div>
            <p class="text-[11px] sm:text-sm text-zinc-500 dark:text-zinc-400">
                Pusat monitoring terpadu perkembangan Tahfizh, Adab, dan Kedisiplinan Ananda.
            </p>
        </div>
    </x-slot>

    @php
        $parent = data_get($stats, 'parent');
        $children = collect(data_get($stats, 'children', []));
        $childrenProgress = collect(data_get($stats, 'children_progress', []));
        $childrenMotivation = collect(data_get($stats, 'children_motivation', []));
    @endphp

    <div class="py-2.5 sm:py-6">
        <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8 space-y-3 sm:space-y-6">

            @if (! $parent)
                <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-3.5 text-xs sm:text-sm text-amber-800 dark:text-amber-300 font-semibold">
                    Profil orang tua belum terhubung dengan akun ini. Silakan hubungi admin sekolah.
                </div>
            @elseif ($childrenProgress->isEmpty())
                <div class="glass-liquid-card rounded-2xl p-6 text-center">
                    <p class="text-zinc-500 dark:text-zinc-400 font-medium text-xs sm:text-sm">
                        Belum ada data murid yang ditautkan ke akun Anda.
                    </p>
                </div>
            @else
                {{-- DESKTOP ONLY: APPRECIATION BANNER (HIDDEN ON MOBILE TO PREVENT CLUTTER) --}}
                <div class="hidden sm:block glass-liquid-card rounded-2xl sm:rounded-[1.75rem] p-5 sm:p-6 shadow-sm relative overflow-hidden">
                    <div class="relative z-10 flex items-center justify-between gap-4">
                        <div class="space-y-1 min-w-0">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-teal-500/15 border border-teal-500/20 text-teal-700 dark:text-teal-300 text-xs font-bold uppercase tracking-wider">
                                <x-heroicon-o-sparkles class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" /> Portal Khusus Wali Murid
                            </span>
                            <h3 class="text-lg lg:text-xl font-black text-zinc-900 dark:text-white truncate">
                                Assalamu'alaikum, Ayah / Bunda {{ $parent->user?->name ?? '' }}!
                            </h3>
                            <p class="text-xs text-zinc-600 dark:text-zinc-400 font-medium">
                                Ruang sinergi pemantauan ananda dalam hafalan Al-Qur'an, adab islami, dan kedisiplinan sekolah.
                            </p>
                        </div>

                        <div class="shrink-0">
                            <div class="rounded-2xl glass-liquid-inner px-4 py-2.5 text-center">
                                <p class="text-[10px] uppercase font-bold text-teal-700 dark:text-teal-300 tracking-wider">Total Ananda</p>
                                <p class="text-xl font-black text-zinc-900 dark:text-white">{{ $childrenProgress->count() }} Murid</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ═══════════════ MULTI-CHILD MONITORING HUB ═══════════════ --}}
                <div x-data="{ activeChild: 0 }" class="space-y-3 sm:space-y-5">

                    @if ($childrenProgress->count() > 1)
                        {{-- Sleek, Flat Multi-Child Switcher without heavy card borders --}}
                        <div class="flex items-center gap-1.5 p-1 bg-zinc-100 dark:bg-zinc-800/80 rounded-xl overflow-x-auto scrollbar-none">
                            @foreach ($childrenProgress as $idx => $row)
                                <button @click="activeChild = {{ $idx }}"
                                        :class="activeChild === {{ $idx }} ? 'bg-white dark:bg-zinc-900 text-teal-700 dark:text-teal-300 shadow-xs font-black' : 'text-zinc-600 dark:text-zinc-400 font-semibold hover:text-zinc-900 dark:hover:text-white'"
                                        class="flex-1 min-w-[140px] sm:min-w-0 flex items-center justify-center gap-1.5 rounded-lg py-1.5 px-3 text-xs transition-all cursor-pointer truncate">
                                    <x-heroicon-o-user class="w-3.5 h-3.5 shrink-0" />
                                    <span class="truncate">{{ data_get($row, 'student_name', 'Ananda '.($idx+1)) }}</span>
                                    <span class="text-[10px] opacity-75 shrink-0">({{ data_get($row, 'class_room_name', '-') }})</span>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    {{-- Individual Profile & Data per Child --}}
                    @foreach ($childrenProgress as $idx => $row)
                        @php
                            $student = data_get($row, 'student');
                            $className = data_get($row, 'class_room_name', $student?->classRoom?->name ?? '');
                            $classLevel = $student?->classRoom?->level ?? '';
                            $isGrade10Class = (bool) (
                                (preg_match('/\bX\b/i', $className) && !preg_match('/\b(XI|XII)\b/i', $className))
                                || preg_match('/\b10\b/i', $className)
                                || preg_match('/^X[-_\s]?E/i', $className)
                                || preg_match('/kelas\s*(X|10)/i', $className)
                                || (preg_match('/\bX\b/i', $classLevel) && !preg_match('/\b(XI|XII)\b/i', $classLevel))
                                || preg_match('/\b10\b/i', $classLevel)
                            ) && !preg_match('/\b(XI|XII|11|12)\b/i', $className);

                            $isUmmi = data_get($row, 'is_ummi_program', false) || $isGrade10Class;
                            $statusColor = data_get($row, 'status_color', 'emerald');
                            $statusLabel = data_get($row, 'status_label', 'On-Track / Tuntas');

                            $adabData = data_get($row, 'adab', []);
                            $tanseData = data_get($row, 'tanse', []);
                            $studentHafalan = collect(data_get($row, 'student_hafalan', []));
                            $studentMurajaah = collect(data_get($row, 'student_murajaah', []));
                            $studentTargets = collect(data_get($row, 'student_targets', []));

                            $pct = min(100, max(0, (float) data_get($row, 'progress_percent', 0)));
                            $strokeDash = 2 * 3.14159 * 44;
                            $offset = $strokeDash - ($pct / 100) * $strokeDash;
                        @endphp

                        <div x-show="activeChild === {{ $idx }}" x-transition class="space-y-3 sm:space-y-5" x-data="{ childTab: 'tahfizh' }">
                            
                            {{-- Header Profil Ananda (Sleek & Un-nested) --}}
                            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-2xl p-3 sm:p-4 shadow-xs">
                                <div class="flex items-center justify-between gap-2.5">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-xl bg-teal-100 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 flex items-center justify-center font-black text-sm sm:text-base shrink-0">
                                            {{ substr(data_get($row, 'student_name', 'A'), 0, 1) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <h3 class="text-sm sm:text-base font-black text-zinc-900 dark:text-white truncate">
                                                    {{ data_get($row, 'student_name', $student?->name ?? '-') }}
                                                </h3>
                                                <span class="px-1.5 py-0.2 rounded-md text-[10px] font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                                    Kelas {{ data_get($row, 'class_room_name', '-') }}
                                                </span>
                                            </div>
                                            <p class="text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 truncate">
                                                NIS: <strong class="text-zinc-700 dark:text-zinc-300">{{ data_get($row, 'student_number', '-') }}</strong> · 
                                                <span>{{ $isUmmi ? 'Metode Ummi' : 'Reguler Tahfizh' }}</span>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            <span class="w-2 h-2 rounded-full {{ $statusColor === 'emerald' ? 'bg-emerald-500 animate-pulse' : ($statusColor === 'amber' ? 'bg-amber-500' : 'bg-rose-500') }}"></span> <span class="hidden xs:inline">{{ $statusLabel }}</span>
                                        </span>
                                        <a href="{{ route('quran.mushaf') }}" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 font-bold text-[11px] transition border border-zinc-200 dark:border-zinc-700">
                                            <x-heroicon-o-book-open class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" /> <span class="hidden sm:inline">Mushaf</span>
                                        </a>
                                        <a href="{{ route('progress.show', $student) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] transition shadow-xs">
                                            <x-heroicon-o-chart-bar class="w-3.5 h-3.5" /> <span class="hidden sm:inline">Rapor</span>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {{-- INTERACTIVE 3-HAL SEGMENTED STRIP (KONTROL UTAMA MOBILE & TABLET) --}}
                            <div class="grid grid-cols-3 gap-2">
                                {{-- Hal 1: Tahfizh Tab Card --}}
                                <button @click="childTab = 'tahfizh'"
                                        :class="childTab === 'tahfizh' 
                                            ? 'bg-teal-600 text-white shadow-sm ring-2 ring-teal-600/30' 
                                            : 'bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 border border-zinc-200/80 dark:border-zinc-800 hover:border-teal-500/40'"
                                        class="p-2.5 rounded-xl text-left flex flex-col justify-between transition-all cursor-pointer">
                                    <div>
                                        <span class="text-[11px] font-bold uppercase tracking-wider opacity-90 flex items-center gap-1 truncate">
                                            <x-heroicon-o-book-open class="w-3.5 h-3.5 shrink-0" /> Tahfizh
                                        </span>
                                        <div class="mt-1">
                                            <span class="text-[9px] font-black px-1.5 py-0.5 rounded inline-block"
                                                  :class="childTab === 'tahfizh' ? 'bg-white/20 text-white' : 'bg-teal-500/15 text-teal-700 dark:text-teal-300'">
                                                {{ round($pct) }}%
                                            </span>
                                        </div>
                                    </div>
                                    <div class="my-1">
                                        <p class="text-sm font-black leading-tight truncate">
                                            {{ $isUmmi ? data_get($row, 'ummi_jilid_str', 'Jilid 1') : data_get($row, 'completed_juz_count', 0).' Juz' }}
                                        </p>
                                    </div>
                                    <div class="w-full rounded-full h-1 mt-0.5 overflow-hidden"
                                         :class="childTab === 'tahfizh' ? 'bg-white/30' : 'bg-zinc-200 dark:bg-zinc-700'">
                                        <div class="h-1 rounded-full"
                                             :class="childTab === 'tahfizh' ? 'bg-white' : 'bg-teal-500'"
                                             style="width: {{ $pct }}%"></div>
                                    </div>
                                </button>

                                {{-- Hal 2: Adab Tab Card --}}
                                <button @click="childTab = 'adab'"
                                        :class="childTab === 'adab' 
                                            ? 'bg-amber-600 text-white shadow-sm ring-2 ring-amber-600/30' 
                                            : 'bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 border border-zinc-200/80 dark:border-zinc-800 hover:border-amber-500/40'"
                                        class="p-2.5 rounded-xl text-left flex flex-col justify-between transition-all cursor-pointer">
                                    <div>
                                        <span class="text-[11px] font-bold uppercase tracking-wider opacity-90 flex items-center gap-1 truncate">
                                            <x-heroicon-o-sparkles class="w-3.5 h-3.5 shrink-0" /> Adab
                                        </span>
                                        <div class="mt-1">
                                            <span class="text-[9px] font-black px-1.5 py-0.5 rounded inline-block"
                                                  :class="childTab === 'adab' ? 'bg-white/20 text-white' : 'bg-amber-500/15 text-amber-700 dark:text-amber-300'">
                                                Grade {{ data_get($adabData, 'grade', 'A') }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="my-1">
                                        <p class="text-sm font-black leading-tight truncate">
                                            {{ data_get($adabData, 'final_score', 0) }} <span class="text-[10px] font-normal opacity-70">/ 100</span>
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-1 mt-0.5">
                                        @php $isFilled = (bool) data_get($adabData, 'today_record'); @endphp
                                        <span class="inline-flex items-center gap-1 text-[8px] font-bold px-1.5 py-0.5 rounded"
                                              :class="childTab === 'adab' ? 'bg-white/20 text-white' : '{{ $isFilled ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' : 'bg-amber-500/15 text-amber-700 dark:text-amber-400' }}'">
                                            <span class="w-1.5 h-1.5 rounded-full {{ $isFilled ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                            {{ $isFilled ? 'Angket Terisi' : 'Belum Diisi' }}
                                        </span>
                                    </div>
                                </button>

                                {{-- Hal 3: Kedisiplinan Tab Card --}}
                                <button @click="childTab = 'tanse'"
                                        :class="childTab === 'tanse' 
                                            ? 'bg-purple-600 text-white shadow-sm ring-2 ring-purple-600/30' 
                                            : 'bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 border border-zinc-200/80 dark:border-zinc-800 hover:border-purple-500/40'"
                                        class="p-2.5 rounded-xl text-left flex flex-col justify-between transition-all cursor-pointer">
                                    <div>
                                        <span class="text-[11px] font-bold uppercase tracking-wider opacity-90 flex items-center gap-1 truncate">
                                            <x-heroicon-o-shield-check class="w-3.5 h-3.5 shrink-0" /> Disiplin
                                        </span>
                                        <div class="mt-1">
                                            <span class="text-[9px] font-black px-1.5 py-0.5 rounded inline-block"
                                                  :class="childTab === 'tanse' ? 'bg-white/20 text-white' : 'bg-purple-500/15 text-purple-700 dark:text-purple-300'">
                                                {{ data_get($tanseData, 'violation_points', 0) > 0 ? '-'.data_get($tanseData, 'violation_points', 0) : 'Tertib' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="my-1">
                                        <p class="text-sm font-black leading-tight truncate">
                                            +{{ data_get($tanseData, 'reward_points', 0) }} <span class="text-[10px] font-normal opacity-70">Poin</span>
                                        </p>
                                    </div>
                                    <div class="w-full rounded-full h-1 mt-0.5 overflow-hidden"
                                         :class="childTab === 'tanse' ? 'bg-white/30' : 'bg-zinc-200 dark:bg-zinc-700'">
                                        <div class="h-1 rounded-full"
                                             :class="childTab === 'tanse' ? 'bg-white' : 'bg-purple-500'"
                                             style="width: {{ min(100, max(20, (int) data_get($tanseData, 'reward_points', 0) * 2)) }}%"></div>
                                    </div>
                                </button>
                            </div>

                            {{-- DESKTOP ONLY DETAILS (BENTO GRID 3 HAL) --}}
                            <div class="hidden sm:grid sm:grid-cols-3 gap-3 sm:gap-4">
                                <div class="glass-liquid-card rounded-2xl p-4 flex flex-col justify-between border border-teal-500/20 shadow-xs">
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <span class="text-xs font-bold uppercase text-teal-700 dark:text-teal-400 bg-teal-500/10 px-2.5 py-1 rounded-lg flex items-center gap-1.5">
                                            <x-heroicon-o-book-open class="w-4 h-4 text-teal-600 dark:text-teal-400" /> {{ "Tahfizh Al-Qur'an" }}
                                        </span>
                                        <span class="text-xs font-bold text-teal-700 dark:text-teal-300">{{ number_format($pct, 1) }}%</span>
                                    </div>
                                    <p class="text-lg font-black text-zinc-900 dark:text-white">
                                        {{ $isUmmi ? data_get($row, 'ummi_jilid_str', 'Jilid 1') : data_get($row, 'completed_juz_count', 0).' Juz Lengkap' }}
                                    </p>
                                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1">Status: {{ $statusLabel }}</p>
                                </div>

                                <div class="glass-liquid-card rounded-2xl p-4 flex flex-col justify-between border border-amber-500/20 shadow-xs">
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <span class="text-xs font-bold uppercase text-amber-700 dark:text-amber-400 bg-amber-500/10 px-2.5 py-1 rounded-lg flex items-center gap-1.5">
                                            <x-heroicon-o-sparkles class="w-4 h-4 text-amber-600 dark:text-amber-400" /> Karakter &amp; Adab
                                        </span>
                                        <span class="text-xs font-bold text-amber-700 dark:text-amber-300">Grade {{ data_get($adabData, 'grade', 'A') }}</span>
                                    </div>
                                    <p class="text-lg font-black text-zinc-900 dark:text-white">
                                        Skor {{ data_get($adabData, 'final_score', '-') }} <span class="text-xs font-normal text-zinc-400">/ 100</span>
                                    </p>
                                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1">Ibadah shalat &amp; pembiasaan santun</p>
                                </div>

                                <div class="glass-liquid-card rounded-2xl p-4 flex flex-col justify-between border border-purple-500/20 shadow-xs">
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <span class="text-xs font-bold uppercase text-purple-700 dark:text-purple-400 bg-purple-500/10 px-2.5 py-1 rounded-lg flex items-center gap-1.5">
                                            <x-heroicon-o-shield-check class="w-4 h-4 text-purple-600 dark:text-purple-400" /> Kedisiplinan &amp; Prestasi
                                        </span>
                                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">+{{ data_get($tanseData, 'reward_points', 0) }} Poin</span>
                                    </div>
                                    <p class="text-lg font-black text-zinc-900 dark:text-white">
                                        {{ data_get($tanseData, 'violation_points', 0) }} Pelanggaran
                                    </p>
                                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1">Status: Sangat Tertib</p>
                                </div>
                            </div>

                            {{-- ═══════════════ DETAIL KONTEN SESUAI TAB AKTIF ═══════════════ --}}

                            {{-- 1. DETAIL TAHFIZH & TARGET --}}
                            <div x-show="childTab === 'tahfizh'" x-transition class="space-y-3 sm:space-y-4">

                                {{-- ═══════════════ DAFTAR SURAH TERAKHIR YANG DIHAFAL ANANDA (BAGIAN ATAS) ═══════════════ --}}
                                <div class="rounded-2xl border border-emerald-500/25 bg-gradient-to-br from-emerald-500/[0.06] via-white to-teal-500/[0.03] dark:from-emerald-950/30 dark:via-zinc-900 dark:to-teal-950/20 p-3.5 sm:p-5 shadow-xs space-y-3">
                                    <div class="flex items-center justify-between gap-2 border-b border-zinc-200/70 dark:border-white/10 pb-2.5 sm:pb-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-500/20 shadow-2xs">
                                                <x-heroicon-o-book-open class="w-4 h-4 sm:w-5 sm:h-5" />
                                            </div>
                                            <div>
                                                <h4 class="text-xs sm:text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                                                    <span>Daftar Surah Terakhir yang Dihafal Ananda</span>
                                                    @if ($studentHafalan->isNotEmpty())
                                                        <span class="inline-flex items-center px-2 py-0.2 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                                                            {{ $studentHafalan->count() }} Terakhir
                                                        </span>
                                                    @endif
                                                </h4>
                                                <p class="text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400">
                                                    Rekam jejak capaian hafalan Al-Qur'an terbaru beserta keterangan tanggal setorannya.
                                                </p>
                                            </div>
                                        </div>

                                        <a href="{{ route('progress.show', $student) }}" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 dark:text-emerald-300 hover:underline shrink-0">
                                            <span>Detail Rapor</span>
                                            <x-heroicon-o-arrow-right class="w-3.5 h-3.5" />
                                        </a>
                                    </div>

                                    @if ($studentHafalan->isNotEmpty())
                                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 sm:gap-3">
                                            @foreach ($studentHafalan as $h)
                                                @php
                                                    $surahName = $h->surah?->name_latin ?? ($h->surah?->name ?? 'Surah');
                                                    $surahNumber = $h->surah?->number ?? null;
                                                    $submittedDate = $h->submitted_at ? \Carbon\Carbon::parse($h->submitted_at) : null;
                                                    $hStatusLabel = match ($h->status) {
                                                        'passed' => 'Lulus',
                                                        'repeat' => 'Ulang',
                                                        'needs_improvement' => 'Perbaikan',
                                                        default => $h->status ?? 'Lulus',
                                                    };
                                                @endphp
                                                <div class="rounded-xl border border-zinc-200/80 dark:border-zinc-800 bg-white/90 dark:bg-zinc-900/90 p-3 hover:border-emerald-500/40 transition flex flex-col justify-between gap-2 shadow-2xs group">
                                                    <div class="space-y-1.5">
                                                        <div class="flex items-start justify-between gap-2">
                                                            <div class="flex items-center gap-2 min-w-0">
                                                                @if($surahNumber)
                                                                    <span class="w-6 h-6 rounded-md bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 text-[10px] font-bold flex items-center justify-center shrink-0 border border-emerald-500/20">
                                                                        {{ $surahNumber }}
                                                                    </span>
                                                                @else
                                                                    <span class="w-6 h-6 rounded-md bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 text-[10px] font-bold flex items-center justify-center shrink-0 border border-emerald-500/20">
                                                                        <x-heroicon-o-bookmark class="w-3.5 h-3.5" />
                                                                    </span>
                                                                @endif
                                                                <div class="min-w-0">
                                                                    <h5 class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition" title="QS. {{ $surahName }}">
                                                                        QS. {{ $surahName }}
                                                                    </h5>
                                                                    <p class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-400">
                                                                        Ayat {{ $h->ayah_start }} - {{ $h->ayah_end }}
                                                                    </p>
                                                                </div>
                                                            </div>

                                                            <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $h->status === 'passed' ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20' : 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/20' }}">
                                                                {{ $hStatusLabel }}
                                                            </span>
                                                        </div>

                                                        {{-- KETERANGAN TANGGAL DI BAWAHNYA --}}
                                                        <div class="pt-2 border-t border-zinc-100 dark:border-zinc-800 flex flex-col gap-0.5">
                                                            <div class="flex items-center gap-1.5 text-xs text-zinc-700 dark:text-zinc-300">
                                                                <x-heroicon-o-calendar class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                                                <span class="font-semibold text-zinc-800 dark:text-zinc-200">
                                                                    {{ $submittedDate ? $submittedDate->locale('id')->translatedFormat('d F Y') : '-' }}
                                                                </span>
                                                            </div>
                                                            @if ($h->teacher?->user?->name)
                                                                <div class="flex items-center gap-1 text-[10px] text-zinc-500 dark:text-zinc-400">
                                                                    <x-heroicon-o-user class="w-3 h-3 text-zinc-400 shrink-0" />
                                                                    <span class="truncate">Disimak: {{ $h->teacher->user->name }}</span>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    @if ($h->score !== null)
                                                        <div class="flex items-center justify-between pt-1 border-t border-zinc-100 dark:border-zinc-800 text-[11px]">
                                                            <span class="text-zinc-500 dark:text-zinc-400">Nilai:</span>
                                                            <span class="font-black text-emerald-600 dark:text-emerald-400">{{ number_format((float) $h->score, 1) }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="py-4 px-3 text-center text-xs text-zinc-500 dark:text-zinc-400 bg-white/50 dark:bg-zinc-900/50 rounded-xl border border-dashed border-zinc-200 dark:border-zinc-800">
                                            Belum ada rekam jejak hafalan yang disetorkan ananda.
                                        </div>
                                    @endif
                                </div>

                                @if ($isUmmi)
                                    <div class="grid grid-cols-3 gap-2">
                                        <div class="rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-2.5 sm:p-3 shadow-xs">
                                            <p class="text-[9px] font-bold text-teal-700 dark:text-teal-400 uppercase truncate">Jilid &amp; Hal</p>
                                            <p class="text-sm sm:text-base font-black text-zinc-900 dark:text-white truncate">{{ data_get($row, 'ummi_jilid_str', 'Jilid 1') }}</p>
                                            <p class="text-[10px] text-zinc-500 truncate">Hal {{ data_get($row, 'ummi_halaman', '-') }}</p>
                                        </div>
                                        <div class="rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-2.5 sm:p-3 shadow-xs">
                                            <p class="text-[9px] font-bold text-zinc-600 dark:text-zinc-400 uppercase truncate">Target Ummi</p>
                                            <p class="text-xs sm:text-sm font-black text-zinc-900 dark:text-white truncate">
                                                {{ data_get($row, 'ummi_target.ummi_jilid') ?? data_get($row, 'ummi_target.surah.name_latin') ?? 'Sesuai Jilid' }}
                                            </p>
                                            <p class="text-[10px] text-zinc-500 truncate">Tahsin Ummi</p>
                                        </div>
                                        <div class="rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-2.5 sm:p-3 shadow-xs">
                                            <p class="text-[9px] font-bold text-emerald-700 dark:text-emerald-400 uppercase truncate">Munaqasyah</p>
                                            <p class="text-sm sm:text-base font-black text-zinc-900 dark:text-white truncate">
                                                {{ data_get($row, 'ummi_munaqasyah_score') !== null ? number_format((float) data_get($row, 'ummi_munaqasyah_score'), 1) : '-' }}
                                            </p>
                                            <p class="text-[10px] text-zinc-500 truncate">Tajwid</p>
                                        </div>
                                    </div>
                                @else
                                    <div class="grid grid-cols-3 gap-2">
                                        <div class="rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-2.5 sm:p-3 shadow-xs">
                                            <p class="text-[9px] font-bold text-indigo-700 dark:text-indigo-400 uppercase truncate">Target</p>
                                            <p class="text-sm sm:text-base font-black text-zinc-900 dark:text-white truncate">{{ data_get($row, 'level_baris', 5) }} Baris</p>
                                            <p class="text-[10px] text-zinc-500 truncate">Per hari</p>
                                        </div>
                                        <div class="rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-2.5 sm:p-3 shadow-xs">
                                            <p class="text-[9px] font-bold text-emerald-700 dark:text-emerald-400 uppercase truncate">Bulan Ini</p>
                                            <p class="text-sm sm:text-base font-black text-zinc-900 dark:text-white truncate">{{ data_get($row, 'capaian_baris_month', 0) }} Baris</p>
                                            <p class="text-[10px] text-emerald-600 truncate">{{ data_get($row, 'reguler_baris_percent', 0) }}% target</p>
                                        </div>
                                        <div class="rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-2.5 sm:p-3 shadow-xs">
                                            <p class="text-[9px] font-bold text-purple-700 dark:text-purple-400 uppercase truncate">Juz Lengkap</p>
                                            <p class="text-sm sm:text-base font-black text-zinc-900 dark:text-white truncate">{{ data_get($row, 'completed_juz_count', 0) }} Juz</p>
                                            <p class="text-[10px] text-purple-600 truncate">{{ data_get($row, 'completed_juz_list', 'Belum ada') }}</p>
                                        </div>
                                    </div>
                                @endif

                                {{-- Kurikulum Progress Bar (Compact) --}}
                                <div class="rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-3 shadow-xs space-y-1.5">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-1.5 min-w-0">
                                            <span class="text-xs font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                                                <x-heroicon-o-check-circle class="w-4 h-4 text-emerald-600 dark:text-emerald-400" /> Target Kurikulum
                                            </span>
                                            @if (data_get($row, 'target_juz_label'))
                                                <span class="px-1.5 py-0.2 rounded text-[10px] font-black bg-emerald-600 text-white truncate">
                                                    {{ data_get($row, 'target_juz_label') }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="text-sm font-black text-emerald-600 dark:text-emerald-400">{{ number_format($pct, 1) }}%</span>
                                            <span class="text-[10px] text-zinc-400 ml-1">({{ number_format(data_get($row, 'memorized_ayahs', 0)) }} ayat)</span>
                                        </div>
                                    </div>
                                    <div class="h-1.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>

                                {{-- Peta Perjalanan Milestone (Swipeable on mobile) --}}
                                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl p-3 shadow-xs">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="text-[11px] font-black uppercase text-zinc-500 tracking-wider flex items-center gap-1.5">
                                            <x-heroicon-o-map class="w-4 h-4 text-indigo-500" /> Peta Perjalanan Target (4 Term)
                                        </h4>
                                        <span class="text-[10px] text-zinc-400 sm:hidden">Geser &rarr;</span>
                                    </div>
                                    <x-student-hafalan-journey :milestones="data_get($row, 'term_milestones', [])" />
                                </div>

                                {{-- Riwayat Terakhir Hafalan & Murajaah (Compact 2 Col) --}}
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl p-3 shadow-xs space-y-1.5">
                                        <h4 class="text-xs font-black text-zinc-900 dark:text-white flex items-center gap-1.5">
                                            <x-heroicon-o-book-open class="w-4 h-4 text-teal-600 dark:text-teal-400" /> Setoran Hafalan Terakhir
                                        </h4>
                                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800 text-xs">
                                            @forelse ($studentHafalan as $h)
                                                <div class="py-2 first:pt-0 last:pb-0 flex items-center justify-between gap-1.5">
                                                    <div class="min-w-0">
                                                        <span class="font-bold text-xs text-zinc-900 dark:text-white truncate block">
                                                            {{ $h->surah?->name_latin }} ({{ $h->ayah_start }}-{{ $h->ayah_end }})
                                                        </span>
                                                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400 flex items-center gap-1 mt-0.5">
                                                            <x-heroicon-o-calendar class="w-3 h-3 text-teal-600 dark:text-teal-400 shrink-0" />
                                                            <span>{{ $h->submitted_at ? \Carbon\Carbon::parse($h->submitted_at)->locale('id')->translatedFormat('d F Y') : '-' }}</span>
                                                        </p>
                                                    </div>
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold {{ $h->status === 'passed' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-50 text-rose-700' }} shrink-0">
                                                        {{ $h->status === 'passed' ? 'Lulus' : 'Ulang' }}
                                                    </span>
                                                </div>
                                            @empty
                                                <p class="text-[11px] text-zinc-400 py-1 text-center">Belum ada data.</p>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl p-3 shadow-xs space-y-1.5">
                                        <h4 class="text-xs font-black text-zinc-900 dark:text-white flex items-center gap-1.5">
                                            <x-heroicon-o-arrow-path class="w-4 h-4 text-indigo-600 dark:text-indigo-400" /> Setoran Muraja'ah Terakhir
                                        </h4>
                                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800 text-xs">
                                            @forelse ($studentMurajaah as $m)
                                                <div class="py-2 first:pt-0 last:pb-0 flex items-center justify-between gap-1.5">
                                                    <div class="min-w-0">
                                                        <span class="font-bold text-xs text-zinc-900 dark:text-white truncate block">
                                                            {{ $m->surah?->name_latin }} ({{ $m->ayah_start }}-{{ $m->ayah_end }})
                                                        </span>
                                                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400 flex items-center gap-1 mt-0.5">
                                                            <x-heroicon-o-calendar class="w-3 h-3 text-indigo-600 dark:text-indigo-400 shrink-0" />
                                                            <span>{{ $m->reviewed_at ? \Carbon\Carbon::parse($m->reviewed_at)->locale('id')->translatedFormat('d F Y') : '-' }}</span>
                                                        </p>
                                                    </div>
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 shrink-0">
                                                        {{ $m->overall_score ?? '-' }}
                                                    </span>
                                                </div>
                                            @empty
                                                <p class="text-[11px] text-zinc-400 py-1 text-center">Belum ada data.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 2. DETAIL ADAB & KARAKTER --}}
                            <div x-show="childTab === 'adab'" x-transition class="space-y-2.5 sm:space-y-4">
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl p-2.5 sm:p-3 shadow-xs">
                                        <p class="text-[9px] font-bold text-amber-700 dark:text-amber-400 uppercase truncate">Skor Adab</p>
                                        <p class="text-sm sm:text-base font-black text-zinc-900 dark:text-white">{{ data_get($adabData, 'final_score', 0) }}</p>
                                        <p class="text-[10px] text-amber-600 truncate">Grade {{ data_get($adabData, 'grade', '-') }}</p>
                                    </div>
                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl p-2.5 sm:p-3 shadow-xs">
                                        <p class="text-[9px] font-bold text-zinc-600 uppercase truncate">Kehadiran</p>
                                        <p class="text-sm sm:text-base font-black text-zinc-900 dark:text-white">{{ data_get($adabData, 'attendance_rate', 0) }}%</p>
                                        <p class="text-[10px] text-zinc-500 truncate">Pengisian angket</p>
                                    </div>
                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl p-2.5 sm:p-3 shadow-xs">
                                        <p class="text-[9px] font-bold text-zinc-600 uppercase truncate">Pembimbing</p>
                                        <p class="text-sm sm:text-base font-black text-zinc-900 dark:text-white">{{ data_get($adabData, 'mentor_score') ?? '-' }}</p>
                                        <p class="text-[10px] text-zinc-500 truncate">Nilai musyrif</p>
                                    </div>
                                </div>

                                {{-- Status Kuisioner Adab Hari Ini & 4 Pilar Karakter --}}
                                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl p-3 shadow-xs space-y-2">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-xs font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                                            <x-heroicon-o-clipboard-document-check class="w-4 h-4 text-amber-600 dark:text-amber-400" /> Kuisioner Adab Harian Santri
                                        </h4>
                                        @php $isFilled = (bool) data_get($adabData, 'today_record'); @endphp
                                        <span class="text-[9px] font-bold px-2 py-0.5 rounded-full inline-flex items-center gap-1 {{ $isFilled ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' : 'bg-amber-500/15 text-amber-700 dark:text-amber-400' }}">
                                            @if($isFilled)
                                                <x-heroicon-o-check-circle class="w-3 h-3 stroke-[2.5]" /> Terisi Hari Ini
                                            @else
                                                <x-heroicon-o-clock class="w-3 h-3 stroke-[2.5]" /> Belum Diisi Hari Ini
                                            @endif
                                        </span>
                                    </div>
                                    <p class="text-[10px] text-zinc-500 dark:text-zinc-400">
                                        Penilaian pembiasaan karakter mencakup 4 pilar adab harian santri:
                                    </p>
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5 text-[9px] font-bold">
                                        <div class="p-1.5 rounded-lg border border-zinc-100 dark:border-zinc-800 bg-zinc-50/70 dark:bg-zinc-800/50 flex items-center gap-1 text-zinc-700 dark:text-zinc-300">
                                            <span>🕋</span> <span class="truncate">Kepada Allah</span>
                                        </div>
                                        <div class="p-1.5 rounded-lg border border-zinc-100 dark:border-zinc-800 bg-zinc-50/70 dark:bg-zinc-800/50 flex items-center gap-1 text-zinc-700 dark:text-zinc-300">
                                            <span>👥</span> <span class="truncate">Sesama Teman</span>
                                        </div>
                                        <div class="p-1.5 rounded-lg border border-zinc-100 dark:border-zinc-800 bg-zinc-50/70 dark:bg-zinc-800/50 flex items-center gap-1 text-zinc-700 dark:text-zinc-300">
                                            <span>📚</span> <span class="truncate">Ketika Belajar</span>
                                        </div>
                                        <div class="p-1.5 rounded-lg border border-zinc-100 dark:border-zinc-800 bg-zinc-50/70 dark:bg-zinc-800/50 flex items-center gap-1 text-zinc-700 dark:text-zinc-300">
                                            <span>🌿</span> <span class="truncate">Lingkungan</span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Link Lembar Adab --}}
                                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl p-3 shadow-xs flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <h4 class="text-xs font-bold text-zinc-900 dark:text-white truncate">Buku Mutaba'ah Adab Ananda</h4>
                                        <p class="text-[10px] text-zinc-500 truncate">Detail penilaian adab harian santri, catatan musyrif, dan grafik perkembangan.</p>
                                    </div>
                                    <a href="{{ route('adab.show', $student) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs shrink-0 transition shadow-xs">
                                        <span>Buka</span> <x-heroicon-m-arrow-right class="w-3 h-3" />
                                    </a>
                                </div>
                            </div>

                            {{-- 3. DETAIL KEDISIPLINAN & PRESTASI --}}
                            <div x-show="childTab === 'tanse'" x-transition class="space-y-2.5 sm:space-y-4">
                                <div class="grid grid-cols-3 gap-2">
                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl p-2.5 sm:p-3 shadow-xs">
                                        <p class="text-[9px] font-bold text-emerald-700 dark:text-emerald-400 uppercase truncate">Reward</p>
                                        <p class="text-sm sm:text-base font-black text-emerald-600 dark:text-emerald-400">+{{ data_get($tanseData, 'reward_points', 0) }}</p>
                                        <p class="text-[10px] text-zinc-500 truncate">Apresiasi</p>
                                    </div>
                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl p-2.5 sm:p-3 shadow-xs">
                                        <p class="text-[9px] font-bold text-rose-700 dark:text-rose-400 uppercase truncate">Pelanggaran</p>
                                        <p class="text-sm sm:text-base font-black text-rose-600 dark:text-rose-400">-{{ data_get($tanseData, 'violation_points', 0) }}</p>
                                        <p class="text-[10px] text-zinc-500 truncate">Tata tertib</p>
                                    </div>
                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl p-2.5 sm:p-3 shadow-xs">
                                        <p class="text-[9px] font-bold text-indigo-700 dark:text-indigo-400 uppercase truncate">Net Poin</p>
                                        <p class="text-sm sm:text-base font-black {{ data_get($tanseData, 'net_points', 0) >= 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-amber-600' }}">
                                            {{ data_get($tanseData, 'net_points', 0) > 0 ? '+' : '' }}{{ data_get($tanseData, 'net_points', 0) }}
                                        </p>
                                        <p class="text-[10px] text-zinc-500 truncate">Skor akhir</p>
                                    </div>
                                </div>

                                {{-- Catatan Terkini --}}
                                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-xl p-3 shadow-xs space-y-2">
                                    <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-1.5">
                                        <h4 class="text-xs font-black text-zinc-800 dark:text-zinc-200">
                                            Catatan Kedisiplinan Terkini
                                        </h4>
                                        <a href="{{ route('student-points.index') }}" class="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                            <span>Semua</span> <x-heroicon-m-arrow-right class="w-3 h-3" />
                                        </a>
                                    </div>
                                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800 text-xs">
                                        @forelse (data_get($tanseData, 'recent_points', []) as $pt)
                                             @php $isV = \App\Models\StudentPoint::isViolationType($pt->type); @endphp
                                            <div class="py-1.5 first:pt-0 last:pb-0 flex items-center justify-between gap-2">
                                                <div class="min-w-0">
                                                    <p class="font-bold text-zinc-900 dark:text-white truncate">{{ $pt->title }}</p>
                                                    <p class="text-[10px] text-zinc-400">{{ $pt->date ? $pt->date->format('d M Y') : '-' }}</p>
                                                </div>
                                                <span class="text-xs font-black {{ $isV ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }} shrink-0">
                                                    {{ $isV ? '-' : '+' }}{{ $pt->points }}
                                                </span>
                                            </div>
                                        @empty
                                            <p class="text-[11px] text-zinc-400 py-1 text-center">Belum ada catatan pelanggaran.</p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            {{-- ═══════════════ FITUR PENDUKUNG (MUSHAF & RAPOR) ═══════════════ --}}
                            <div class="grid grid-cols-2 gap-2 pt-1">
                                <a href="{{ route('quran.mushaf') }}" class="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-950/30 dark:to-teal-950/20 border border-emerald-200/80 dark:border-emerald-800/50 rounded-xl p-2.5 shadow-xs flex items-center justify-between gap-1.5 hover:border-emerald-500 transition">
                                    <div class="min-w-0 flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-emerald-500/15 flex items-center justify-center text-emerald-700 dark:text-emerald-300 shrink-0">
                                            <x-heroicon-o-book-open class="w-4 h-4" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-bold text-emerald-800 dark:text-emerald-300 truncate">Mushaf Digital</p>
                                            <p class="text-[10px] text-emerald-900/70 dark:text-emerald-300/70 truncate">Simak tilawah</p>
                                        </div>
                                    </div>
                                    <x-heroicon-m-chevron-right class="w-4 h-4 text-emerald-600 shrink-0" />
                                </a>

                                <a href="{{ route('progress.show', $student) }}" class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/20 border border-indigo-200/80 dark:border-indigo-800/50 rounded-xl p-2.5 shadow-xs flex items-center justify-between gap-1.5 hover:border-indigo-500 transition">
                                    <div class="min-w-0 flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-indigo-500/15 flex items-center justify-center text-indigo-700 dark:text-indigo-300 shrink-0">
                                            <x-heroicon-o-chart-bar class="w-4 h-4" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-bold text-indigo-800 dark:text-indigo-300 truncate">Rapor Digital</p>
                                            <p class="text-[10px] text-indigo-900/70 dark:text-indigo-300/70 truncate">Arsip capaian</p>
                                        </div>
                                    </div>
                                    <x-heroicon-m-chevron-right class="w-4 h-4 text-indigo-600 shrink-0" />
                                </a>
                            </div>

                        </div>
                    @endforeach

                </div>
            @endif

        </div>
    </div>
</x-app-layout>