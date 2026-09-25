<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-0.5">
            <h2 class="font-bold text-lg sm:text-xl text-zinc-900 dark:text-white leading-tight">
                Dashboard Murid
            </h2>
            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400">
                Ringkasan progres hafalan, target, murajaah, dan motivasi.
            </p>
        </div>
    </x-slot>

    @php
        $student = data_get($stats, 'student');
        $progress = data_get($stats, 'progress', data_get($stats, 'summary', []));
        $motivation = data_get($stats, 'motivation', []);
        $activeTargets = collect(data_get($stats, 'active_targets', []));
        $overdueTargets = collect(data_get($stats, 'overdue_targets', []));
        $latestTargets = collect(data_get($stats, 'latest_targets', []));
        $latestHafalanRecords = collect(data_get($stats, 'latest_hafalan_records', []));
        $latestMurajaahRecords = collect(data_get($stats, 'latest_murajaah_records', []));

        $progressPercent = (float) data_get($progress, 'progress_percent', data_get($progress, 'progress_percentage', 0));
        $progressWidth = min(100, max(0, $progressPercent));
    @endphp

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-5 sm:space-y-6">

            @if (session('success'))
                <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-xs sm:text-sm text-emerald-800 dark:text-emerald-300 font-semibold shadow-2xs">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-2xl border border-rose-500/20 bg-rose-500/10 p-4 text-xs sm:text-sm text-rose-800 dark:text-rose-300 font-semibold shadow-2xs">
                    {{ session('error') }}
                </div>
            @endif

            @if (! $student)
                <div class="rounded-2xl border border-amber-500/20 bg-amber-500/10 p-5 text-xs sm:text-sm text-amber-800 dark:text-amber-300 font-semibold">
                    Profil murid belum terhubung dengan akun ini. Silakan hubungi Administrator untuk menghubungkan data murid Anda.
                </div>
            @else
                @php
                    $className = data_get($progress, 'class_room_name', $student?->classRoom?->name ?? '');
                    $classLevel = $student?->classRoom?->level ?? '';
                    $isGrade10Class = (bool) (
                        (preg_match('/\bX\b/i', $className) && !preg_match('/\b(XI|XII)\b/i', $className))
                        || preg_match('/\b10\b/i', $className)
                        || preg_match('/^X[-_\s]?E/i', $className)
                        || preg_match('/kelas\s*(X|10)/i', $className)
                        || (preg_match('/\bX\b/i', $classLevel) && !preg_match('/\b(XI|XII)\b/i', $classLevel))
                        || preg_match('/\b10\b/i', $classLevel)
                    ) && !preg_match('/\b(XI|XII|11|12)\b/i', $className);

                    $isUmmi = data_get($progress, 'is_ummi_program', false) || $isGrade10Class;
                    $statusColor = data_get($progress, 'status_color', 'emerald');
                    $statusLabel = data_get($progress, 'status_label', 'On-Track / Tuntas');
                @endphp

                {{-- ═══════════════ DAFTAR SURAH TERAKHIR YANG DIHAFAL (BAGIAN ATAS) ═══════════════ --}}
                <div class="glass-liquid-card rounded-2xl sm:rounded-[1.75rem] p-4 sm:p-6 relative overflow-hidden border border-emerald-500/25 shadow-sm bg-gradient-to-br from-emerald-500/[0.05] via-transparent to-teal-500/[0.03]">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-200/70 dark:border-white/10 pb-3 sm:pb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 border border-emerald-500/20 shadow-xs">
                                <x-heroicon-o-book-open class="w-6 h-6 sm:w-7 sm:h-7" />
                            </div>
                            <div>
                                <h3 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                    <span>Daftar Surah Terakhir yang Dihafal</span>
                                    @if ($latestHafalanRecords->isNotEmpty())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                                            {{ $latestHafalanRecords->count() }} Terakhir
                                        </span>
                                    @endif
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Daftar capaian surah terbaru yang berhasil disetorkan beserta keterangan tanggal setorannya.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('progress.index') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 transition shadow-2xs">
                                <span>Lihat Semua Riwayat</span>
                                <x-heroicon-o-arrow-right class="w-3.5 h-3.5" />
                            </a>
                        </div>
                    </div>

                    @if ($latestHafalanRecords->isNotEmpty())
                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                            @foreach ($latestHafalanRecords as $record)
                                @php
                                    $surahName = $record->surah?->name_latin ?? ($record->surah?->name ?? 'Surah');
                                    $surahNumber = $record->surah?->number ?? null;
                                    $submittedDate = $record->submitted_at ? \Carbon\Carbon::parse($record->submitted_at) : null;
                                    $recordStatusLabel = match ($record->status) {
                                        'passed' => 'Lulus',
                                        'repeat' => 'Ulang',
                                        'needs_improvement' => 'Perbaikan',
                                        default => $record->status ?? 'Lulus',
                                    };
                                @endphp
                                <div class="rounded-xl sm:rounded-2xl glass-liquid-inner p-3.5 sm:p-4 hover:border-emerald-500/40 transition flex flex-col justify-between gap-3 group relative overflow-hidden">
                                    <div class="space-y-2">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex items-center gap-2 min-w-0">
                                                @if($surahNumber)
                                                    <span class="w-7 h-7 rounded-lg bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 text-xs font-bold flex items-center justify-center shrink-0 border border-emerald-500/20">
                                                        {{ $surahNumber }}
                                                    </span>
                                                @else
                                                    <span class="w-7 h-7 rounded-lg bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 text-xs font-bold flex items-center justify-center shrink-0 border border-emerald-500/20">
                                                        <x-heroicon-o-bookmark class="w-4 h-4" />
                                                    </span>
                                                @endif
                                                <div class="min-w-0">
                                                    <h4 class="font-bold text-sm sm:text-base text-zinc-900 dark:text-white truncate group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition" title="QS. {{ $surahName }}">
                                                        QS. {{ $surahName }}
                                                    </h4>
                                                    <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                                                        Ayat {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                                    </p>
                                                </div>
                                            </div>

                                            <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                                                {{ $recordStatusLabel }}
                                            </span>
                                        </div>

                                        {{-- ─── KETERANGAN TANGGAL DI BAWAHNYA ─── --}}
                                        <div class="pt-2 border-t border-zinc-200/50 dark:border-white/5 flex flex-col gap-1">
                                            <div class="flex items-center gap-1.5 text-xs text-zinc-700 dark:text-zinc-300">
                                                <x-heroicon-o-calendar class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                                <span class="font-semibold text-zinc-800 dark:text-zinc-200">
                                                    {{ $submittedDate ? $submittedDate->locale('id')->translatedFormat('d F Y') : '-' }}
                                                </span>
                                            </div>
                                            @if ($record->teacher?->user?->name)
                                                <div class="flex items-center gap-1.5 text-[11px] text-zinc-500 dark:text-zinc-400">
                                                    <x-heroicon-o-user class="w-3.5 h-3.5 text-zinc-400 shrink-0" />
                                                    <span class="truncate">Disimak: {{ $record->teacher->user->name }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($record->score !== null)
                                        <div class="flex items-center justify-between pt-1.5 border-t border-zinc-200/40 dark:border-white/5 text-xs">
                                            <span class="text-zinc-500 dark:text-zinc-400 text-[11px]">Nilai:</span>
                                            <span class="font-black text-emerald-600 dark:text-emerald-400">{{ number_format((float) $record->score, 1) }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-4 p-6 sm:p-8 rounded-xl sm:rounded-2xl glass-liquid-inner text-center">
                            <x-heroicon-o-book-open class="w-10 h-10 mx-auto text-zinc-400 dark:text-zinc-500 mb-2 opacity-60" />
                            <p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Belum ada riwayat setoran hafalan tercatat.</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Setoran hafalan terbaru Anda akan tampil di sini setelah disimak dan diverifikasi oleh guru pembimbing.</p>
                        </div>
                    @endif
                </div>

                {{-- ═══════════════ TARGET & CAPAIAN PROGRAM HERO CARD ═══════════════ --}}
                <div class="glass-liquid-card rounded-2xl sm:rounded-[1.75rem] p-3.5 sm:p-6 relative overflow-hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-200/70 dark:border-white/10 pb-3 sm:pb-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-teal-500/15 text-teal-600 dark:text-teal-400 flex items-center justify-center shrink-0 border border-teal-500/20 shadow-xs">
                                <x-heroicon-o-book-open class="w-6 h-6 sm:w-7 sm:h-7" />
                            </div>
                            <div>
                                <h3 class="text-sm sm:text-lg font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                    <span>Program {{ $isUmmi ? 'Ummi (Kelas X)' : 'Reguler (Kelas XI/XII)' }}</span>
                                </h3>
                                <p class="text-[11px] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    {{ $isUmmi ? 'Tahsin Ummi & Halaman Hafalan' : 'Target Baris & Hafalan Periodik' }}
                                </p>
                            </div>
                        </div>

                        <div>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $statusColor === 'emerald' ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20' : ($statusColor === 'amber' ? 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/20' : 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/20') }}">
                                <span class="w-2 h-2 rounded-full {{ $statusColor === 'emerald' ? 'bg-emerald-500 animate-pulse' : ($statusColor === 'amber' ? 'bg-amber-500' : 'bg-rose-500') }}"></span> {{ $statusLabel }}
                            </span>
                        </div>
                    </div>

                    @if ($isUmmi)
                        {{-- ─── PROGRAM UMMI UI (KELAS 10) ─── --}}
                        <div class="mt-3 sm:mt-5 grid grid-cols-3 gap-2 sm:gap-4">
                            <div class="rounded-xl sm:rounded-2xl glass-liquid-inner p-2.5 sm:p-4">
                                <p class="text-[9px] sm:text-xs font-bold text-teal-800 dark:text-teal-300 uppercase tracking-wider mb-0.5 truncate flex items-center gap-1">
                                    <x-heroicon-o-book-open class="w-3.5 h-3.5" /> Jilid Ummi
                                </p>
                                <p class="text-sm sm:text-2xl font-black text-teal-900 dark:text-teal-100 truncate">
                                    {{ data_get($progress, 'current_jilid', '-') }}
                                </p>
                            </div>
                            <div class="rounded-xl sm:rounded-2xl glass-liquid-inner p-2.5 sm:p-4">
                                <p class="text-[9px] sm:text-xs font-bold text-teal-800 dark:text-teal-300 uppercase tracking-wider mb-0.5 truncate flex items-center gap-1">
                                    <x-heroicon-o-document-text class="w-3.5 h-3.5" /> Halaman
                                </p>
                                <p class="text-sm sm:text-2xl font-black text-teal-900 dark:text-teal-100 truncate">
                                    {{ data_get($progress, 'current_halaman', '-') }}
                                </p>
                            </div>
                            <div class="rounded-xl sm:rounded-2xl glass-liquid-inner p-2.5 sm:p-4">
                                <p class="text-[9px] sm:text-xs font-bold text-teal-800 dark:text-teal-300 uppercase tracking-wider mb-0.5 truncate flex items-center gap-1">
                                    <x-heroicon-o-trophy class="w-3.5 h-3.5" /> Status
                                </p>
                                <p class="text-xs sm:text-base font-bold text-teal-900 dark:text-teal-100 mt-0.5 truncate">
                                    {{ data_get($progress, 'ummi_notes', 'Sedang Bimbingan') }}
                                </p>
                            </div>
                        </div>
                    @else
                        {{-- ─── PROGRAM REGULER UI (KELAS 11 & 12) ─── --}}
                        <div class="mt-3 sm:mt-5 grid grid-cols-3 gap-2 sm:gap-4">
                            <div class="rounded-xl sm:rounded-2xl glass-liquid-inner p-2.5 sm:p-4">
                                <p class="text-[9px] sm:text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-0.5 truncate">Ayat</p>
                                <p class="text-sm sm:text-2xl font-black text-zinc-900 dark:text-white truncate">
                                    {{ number_format(data_get($progress, 'memorized_ayahs', 0)) }}
                                    <span class="text-[10px] sm:text-xs font-normal text-zinc-400">/ {{ number_format(data_get($progress, 'target_total_ayahs', 6236)) }}</span>
                                </p>
                            </div>
                            <div class="rounded-xl sm:rounded-2xl glass-liquid-inner p-2.5 sm:p-4">
                                <p class="text-[9px] sm:text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-0.5 truncate">Target</p>
                                <p class="text-sm sm:text-2xl font-black text-zinc-900 dark:text-white truncate">
                                    {{ data_get($progress, 'target_juz_label', 'Juz 30') }}
                                </p>
                            </div>
                            <div class="rounded-xl sm:rounded-2xl glass-liquid-inner p-2.5 sm:p-4">
                                <p class="text-[9px] sm:text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-0.5 truncate">Progres</p>
                                <p class="text-sm sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 truncate">
                                    {{ number_format($progressPercent, 1) }}%
                                </p>
                            </div>
                        </div>
                    @endif
                </div>

                <x-student-hafalan-journey :milestones="data_get($progress, 'term_milestones', [])" />

                {{-- ═══════════════ PROFIL & PROGRESS HAFALAN GRID ═══════════════ --}}
                <div class="grid grid-cols-1 gap-3.5 sm:gap-4 lg:grid-cols-3">
                    {{-- Profil Singkat --}}
                    <div class="glass-liquid-card rounded-2xl sm:rounded-[1.75rem] p-4 sm:p-6 space-y-3 sm:space-y-4">
                        <div class="flex items-center gap-3 border-b border-zinc-200/70 dark:border-white/10 pb-3 sm:pb-4">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-teal-500/15 text-teal-700 dark:text-teal-300 font-black text-base sm:text-lg flex items-center justify-center shrink-0 border border-teal-500/20 shadow-xs">
                                {{ substr($student->name, 0, 1) }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-white truncate">{{ $student->name }}</h3>
                                <p class="text-[11px] sm:text-xs text-zinc-500 dark:text-zinc-400">NIS: {{ $student->student_number ?? '-' }}</p>
                            </div>
                        </div>

                        <dl class="space-y-2 text-xs">
                            <div class="flex justify-between items-center py-0.5 border-b border-zinc-200/40 dark:border-white/5">
                                <dt class="text-zinc-500 dark:text-zinc-400">Kelas</dt>
                                <dd class="font-bold text-zinc-900 dark:text-white">{{ $student->classRoom?->name ?? '-' }}</dd>
                            </div>
                            <div class="flex justify-between items-center py-0.5 border-b border-zinc-200/40 dark:border-white/5">
                                <dt class="text-zinc-500 dark:text-zinc-400">Program</dt>
                                <dd class="font-bold text-zinc-900 dark:text-white">{{ $student->classRoom?->program?->name ?? '-' }}</dd>
                            </div>
                            <div class="flex justify-between items-center py-0.5">
                                <dt class="text-zinc-500 dark:text-zinc-400">Guru Bimbingan</dt>
                                <dd class="font-bold text-zinc-900 dark:text-white truncate max-w-[170px]">{{ $student->teacher?->user?->name ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Progress Hafalan --}}
                    <div class="glass-liquid-card rounded-2xl sm:rounded-[1.75rem] p-4 sm:p-6 lg:col-span-2 space-y-3 sm:space-y-4 border border-teal-500/20 shadow-sm">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-zinc-200/70 dark:border-white/10 pb-3 sm:pb-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-white">Progress Hafalan Al-Qur'an</h3>
                                    @if (!empty($progress['target_juz_label']))
                                        <span class="inline-flex items-center rounded-full bg-teal-500/15 px-2.5 py-0.5 text-[10px] sm:text-xs font-bold text-teal-700 dark:text-teal-300 border border-teal-500/20">
                                            Target: {{ $progress['target_juz_label'] }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-[11px] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Setoran lulus sesuai target kurikulum program.
                                </p>
                            </div>

                            <div>
                                <span class="px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-lg sm:rounded-xl text-[10px] sm:text-xs font-black bg-emerald-500 text-white shadow-xs">
                                    {{ $statusLabel }}
                                </span>
                            </div>
                        </div>

                        {{-- Circular Ring & Key Stats --}}
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 sm:gap-4 items-center">
                            <div class="sm:col-span-5 flex items-center justify-center py-1 sm:py-2">
                                <div class="relative w-28 h-28 sm:w-36 sm:h-36 flex items-center justify-center">
                                    @php
                                        $pctStudent = min(100, max(0, (float) $progressPercent));
                                        $dashStudent = 2 * 3.14159 * 44;
                                        $offsetStudent = $dashStudent - ($pctStudent / 100) * $dashStudent;
                                    @endphp
                                    <svg class="w-full h-full transform -rotate-90 glow-teal-ring" viewBox="0 0 100 100">
                                        <circle cx="50" cy="50" r="44" stroke="currentColor" stroke-width="8" class="text-zinc-200 dark:text-zinc-800" fill="none" />
                                        <circle cx="50" cy="50" r="44" stroke="url(#studentTealGrad)" stroke-width="8" stroke-dasharray="{{ $dashStudent }}" stroke-dashoffset="{{ $offsetStudent }}" stroke-linecap="round" fill="none" />
                                        <defs>
                                            <linearGradient id="studentTealGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#0d9488" />
                                                <stop offset="100%" stop-color="#10b981" />
                                            </linearGradient>
                                        </defs>
                                    </svg>
                                    <div class="absolute flex flex-col items-center justify-center text-center">
                                        <span class="text-2xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight">{{ round($pctStudent) }}%</span>
                                        <span class="text-[9px] sm:text-[10px] font-bold text-teal-600 dark:text-teal-400 uppercase">Tuntas</span>
                                    </div>
                                </div>
                            </div>

                            <div class="sm:col-span-7 space-y-2.5 sm:space-y-3">
                                <div class="grid grid-cols-3 gap-2.5">
                                    <div class="rounded-2xl glass-liquid-inner p-3 text-center">
                                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400 font-semibold">Setoran</p>
                                        <p class="text-lg sm:text-xl font-black text-zinc-900 dark:text-white mt-0.5">
                                            {{ number_format(data_get($progress, 'total_hafalan_records', 0)) }}
                                        </p>
                                    </div>

                                    <div class="rounded-2xl glass-liquid-inner p-3 text-center">
                                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400 font-semibold">Murajaah</p>
                                        <p class="text-lg sm:text-xl font-black text-zinc-900 dark:text-white mt-0.5">
                                            {{ number_format(data_get($progress, 'total_murajaah_records', 0)) }}
                                        </p>
                                    </div>

                                    <div class="rounded-2xl glass-liquid-inner p-3 text-center">
                                        <p class="text-[10px] text-rose-600 dark:text-rose-400 font-semibold">Terlambat</p>
                                        <p class="text-lg sm:text-xl font-black text-rose-600 dark:text-rose-400 mt-0.5">
                                            {{ number_format(data_get($progress, 'overdue_targets', $overdueTargets->count())) }}
                                        </p>
                                    </div>
                                </div>

                                <p class="text-xs text-zinc-600 dark:text-zinc-400 font-medium">
                                    Tercatat: <strong class="text-zinc-900 dark:text-white">{{ number_format(data_get($progress, 'memorized_ayahs', 0)) }}</strong> dari {{ number_format(data_get($progress, 'target_total_ayahs', data_get($progress, 'total_quran_ayahs', 6236))) }} ayat target.
                                </p>

                                @if (Route::has('progress.show'))
                                    <a href="{{ route('progress.show', $student) }}"
                                       class="w-full inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 px-4 py-2.5 text-xs font-bold text-white transition-all shadow-md shadow-emerald-600/20">
                                        Lihat Detail Rapor &amp; Progres Lengkap &rarr;
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                @include('dashboards.partials.motivation-card', [
                    'student' => $student,
                    'progress' => $progress,
                    'motivation' => $motivation,
                    'showStudentName' => false,
                ])

                @php
                    $todayDate = now()->toDateString();
                    $adabFilledToday = \App\Models\AdabRecord::where('student_id', $student->id)->where('assessment_date', $todayDate)->exists();
                @endphp

                {{-- ═══════════════ QUICK ACCESS KUISIONER ADAB ═══════════════ --}}
                <div class="glass-liquid-card rounded-[1.75rem] p-5 sm:p-7 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-teal-500/15 text-teal-600 dark:text-teal-400 flex items-center justify-center shrink-0 shadow-2xs border border-teal-500/20">
                                <x-heroicon-o-sparkles class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-zinc-900 dark:text-white">
                                    Kuisioner Adab Harian
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Pengisian mandiri adab &amp; karakter harian santri.
                                </p>
                            </div>
                        </div>

                        <div>
                            @if ($adabFilledToday)
                                <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                                    <x-heroicon-o-check-circle class="w-4 h-4 text-emerald-600 dark:text-emerald-400" /> Sudah Diisi Hari Ini
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-bold bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/20 animate-pulse">
                                    <x-heroicon-o-exclamation-triangle class="w-4 h-4 text-amber-600 dark:text-amber-400" /> Belum Diisi Hari Ini
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 pt-3.5 border-t border-zinc-200/70 dark:border-white/10 flex flex-wrap items-center gap-2.5 sm:gap-3">
                        @if (! $adabFilledToday)
                            <a href="{{ route('adab.create', $student) }}" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2.5 text-xs sm:text-sm font-bold text-white transition shadow-sm">
                                <x-heroicon-o-pencil-square class="w-4 h-4" /> Isi Kuisioner Sekarang
                            </a>
                        @endif

                        <a href="{{ route('adab.show', $student) }}" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 rounded-xl glass-liquid-inner hover:bg-white/60 dark:hover:bg-white/10 px-4 py-2.5 text-xs sm:text-sm font-semibold text-zinc-700 dark:text-zinc-300 transition">
                            <x-heroicon-o-chart-bar class="w-4 h-4" /> Laporan &amp; Grafik Adab &rarr;
                        </a>
                    </div>
                </div>

                {{-- ═══════════════ TARGET AKTIF & TERLAMBAT ═══════════════ --}}
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="glass-liquid-card rounded-[1.75rem] overflow-hidden">
                        <div class="border-b border-zinc-200/70 dark:border-white/10 px-5 py-4 flex items-center justify-between">
                            <h3 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                                <x-heroicon-o-check-circle class="w-4 h-4 text-teal-600 dark:text-teal-400" /> Target Aktif
                            </h3>
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-teal-500/10 text-teal-700 dark:text-teal-300 border border-teal-500/20">{{ $activeTargets->count() }} Target</span>
                        </div>

                        <div class="divide-y divide-zinc-200/60 dark:divide-white/5">
                            @forelse ($activeTargets as $target)
                                <div class="p-4 hover:bg-white/40 dark:hover:bg-white/[0.04] transition">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            @if ($target->ummi_jilid || $isUmmi)
                                                <p class="font-bold text-xs sm:text-sm text-teal-800 dark:text-teal-300 flex items-center gap-1">
                                                    <x-heroicon-o-book-open class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400 shrink-0" /> {{ $target->ummi_jilid ?? 'Target Ummi' }}
                                                </p>
                                                @if($target->surah)
                                                    <p class="text-xs text-teal-700 dark:text-teal-400 mt-0.5">
                                                        QS. {{ $target->surah->name_latin }} (Ayat {{ $target->ayah_start ?? 1 }}-{{ $target->ayah_end ?? '-' }})
                                                    </p>
                                                @endif
                                            @else
                                                <p class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate flex items-center gap-1">
                                                    <x-heroicon-o-book-open class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400 shrink-0" /> QS. {{ $target->surah?->name_latin ?? '-' }} : {{ $target->ayah_start }} - {{ $target->ayah_end }}
                                                </p>
                                            @endif
                                            <p class="mt-0.5 text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400">
                                                Tenggat: {{ $target->target_date ? \Carbon\Carbon::parse($target->target_date)->format('d M Y') : '-' }}
                                            </p>
                                        </div>

                                        <span class="rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 border border-emerald-500/20 shrink-0">
                                            Aktif
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="p-6 text-center text-xs text-zinc-500">Belum ada target aktif.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="glass-liquid-card rounded-[1.75rem] overflow-hidden">
                        <div class="border-b border-zinc-200/70 dark:border-white/10 px-5 py-4 flex items-center justify-between">
                            <h3 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                                <x-heroicon-o-exclamation-triangle class="w-4 h-4 text-rose-600 dark:text-rose-400" /> Target Terlambat
                            </h3>
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/20">{{ $overdueTargets->count() }} Target</span>
                        </div>

                        <div class="divide-y divide-zinc-200/60 dark:divide-white/5">
                            @forelse ($overdueTargets as $target)
                                <div class="p-4 hover:bg-white/40 dark:hover:bg-white/[0.04] transition">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            @if ($target->ummi_jilid || $isUmmi)
                                                <p class="font-bold text-xs sm:text-sm text-teal-800 dark:text-teal-300 flex items-center gap-1">
                                                    <x-heroicon-o-book-open class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400 shrink-0" /> {{ $target->ummi_jilid ?? 'Target Ummi' }}
                                                </p>
                                            @else
                                                <p class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate flex items-center gap-1">
                                                    <x-heroicon-o-book-open class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400 shrink-0" /> QS. {{ $target->surah?->name_latin ?? '-' }} : {{ $target->ayah_start }} - {{ $target->ayah_end }}
                                                </p>
                                            @endif
                                            <p class="mt-0.5 text-[10px] sm:text-xs font-semibold text-rose-600 dark:text-rose-400">
                                                Lewat dari {{ $target->target_date ? \Carbon\Carbon::parse($target->target_date)->format('d M Y') : '-' }}
                                            </p>
                                        </div>
                                        <span class="rounded-full bg-rose-500/15 px-2.5 py-0.5 text-[10px] font-bold text-rose-700 dark:text-rose-300 border border-rose-500/20 shrink-0">
                                            Terlambat
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="p-6 text-center text-xs text-zinc-500">Tidak ada target terlambat.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- ═══════════════ HAFALAN & MURAJAAH TERBARU ═══════════════ --}}
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="glass-liquid-card rounded-[1.75rem] overflow-hidden">
                        <div class="border-b border-zinc-200/70 dark:border-white/10 px-5 py-4 flex items-center justify-between">
                            <h3 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                                <x-heroicon-o-book-open class="w-4 h-4 text-emerald-600 dark:text-emerald-400" /> Hafalan Terbaru
                            </h3>
                            <a href="{{ route('progress.index') }}" class="text-xs text-emerald-600 dark:text-emerald-400 font-bold hover:underline">Semua &rarr;</a>
                        </div>

                        <div class="divide-y divide-zinc-200/60 dark:divide-white/5">
                            @forelse ($latestHafalanRecords as $record)
                                <div class="p-4 hover:bg-white/40 dark:hover:bg-white/[0.04] transition">
                                    <div class="flex justify-between items-start gap-2">
                                        <div class="min-w-0">
                                            <p class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">
                                                QS. {{ $record->surah?->name_latin ?? '-' }} : {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                            </p>
                                            <p class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5 flex items-center gap-1">
                                                <x-heroicon-o-calendar class="w-3 h-3 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                                <span>{{ $record->submitted_at ? \Carbon\Carbon::parse($record->submitted_at)->locale('id')->translatedFormat('d F Y') : '-' }}</span>
                                            </p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            @php
                                                $recHafalanStatus = match ($record->status) {
                                                    'passed' => 'Lulus',
                                                    'repeat' => 'Ulang',
                                                    'needs_improvement' => 'Perbaikan',
                                                    default => $record->status ?? 'Lulus',
                                                };
                                            @endphp
                                            <span class="px-2.5 py-0.5 rounded-full text-[9px] sm:text-[10px] font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                                                {{ $recHafalanStatus }}
                                            </span>
                                            @if ($record->score !== null)
                                                <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 mt-1">Nilai: {{ number_format((float) $record->score, 1) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="p-6 text-center text-xs text-zinc-500">Belum ada hafalan.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="glass-liquid-card rounded-[1.75rem] overflow-hidden">
                        <div class="border-b border-zinc-200/70 dark:border-white/10 px-5 py-4 flex items-center justify-between">
                            <h3 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                                <x-heroicon-o-arrow-path class="w-4 h-4 text-amber-600 dark:text-amber-400" /> Murajaah Terbaru
                            </h3>
                            <a href="{{ route('progress.index') }}" class="text-xs text-amber-600 dark:text-amber-400 font-bold hover:underline">Semua &rarr;</a>
                        </div>

                        <div class="divide-y divide-zinc-200/60 dark:divide-white/5">
                            @forelse ($latestMurajaahRecords as $record)
                                <div class="p-4 hover:bg-white/40 dark:hover:bg-white/[0.04] transition">
                                    <div class="flex justify-between items-start gap-2">
                                        <div class="min-w-0">
                                            <p class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">
                                                QS. {{ $record->surah?->name_latin ?? '-' }} : {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                            </p>
                                            <p class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5 flex items-center gap-1">
                                                <x-heroicon-o-calendar class="w-3 h-3 text-amber-600 dark:text-amber-400 shrink-0" />
                                                <span>{{ $record->reviewed_at ? \Carbon\Carbon::parse($record->reviewed_at)->locale('id')->translatedFormat('d F Y') : '-' }}</span>
                                            </p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            @php
                                                $recMurajaahStatus = match ($record->status) {
                                                    'passed' => 'Lulus',
                                                    'repeat' => 'Ulang',
                                                    'needs_improvement' => 'Perbaikan',
                                                    default => $record->status ?? 'Lulus',
                                                };
                                            @endphp
                                            <span class="px-2.5 py-0.5 rounded-full text-[9px] sm:text-[10px] font-bold bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/20">
                                                {{ $recMurajaahStatus }}
                                            </span>
                                            @if ($record->overall_score !== null)
                                                <p class="text-[10px] font-bold text-amber-600 dark:text-amber-400 mt-1">Nilai: {{ number_format((float) $record->overall_score, 1) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="p-6 text-center text-xs text-zinc-500">Belum ada murajaah.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>