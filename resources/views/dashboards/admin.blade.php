<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-0.5">
            <h2 class="font-bold text-lg sm:text-xl text-gray-800 dark:text-zinc-100 leading-tight">
                {{ $title ?? 'Admin Dashboard' }}
            </h2>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-zinc-400">
                {{ $subtitle ?? 'Monitoring operasional TAD.' }}
            </p>
        </div>
    </x-slot>

    @php
        $studentsProgress = collect(data_get($stats, 'students_progress', []));
        $latestTargets = collect(data_get($stats, 'latest_targets', []));
        $latestHafalanRecords = collect(data_get($stats, 'latest_hafalan_records', []));
        $latestMurajaahRecords = collect(data_get($stats, 'latest_murajaah_records', []));
    @endphp

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-5 sm:space-y-6">

            {{-- 1. Stat Cards (Frosted Liquid Glass Bento) --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4.5">
                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 group relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Murid</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/10 dark:bg-emerald-400/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-sm shadow-xs">
                            <x-heroicon-o-users class="w-4 h-4" />
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight">{{ data_get($stats, 'total_students', 0) }}</p>
                    <div class="flex items-center gap-1.5 mt-2.5">
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                        <p class="text-[10px] sm:text-xs font-semibold text-emerald-700 dark:text-emerald-400">Aktif: {{ data_get($stats, 'active_students', 0) }} santri</p>
                    </div>
                </div>

                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 group relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Tenaga Pendidik</span>
                        <div class="w-8 h-8 rounded-xl bg-teal-500/10 dark:bg-teal-400/15 text-teal-600 dark:text-teal-400 flex items-center justify-center text-sm shadow-xs">
                            <x-heroicon-o-academic-cap class="w-4 h-4" />
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight">{{ data_get($stats, 'total_teachers', 0) }}</p>
                    <div class="flex items-center gap-1.5 mt-2.5">
                        <span class="inline-block w-2 h-2 rounded-full bg-teal-500"></span>
                        <p class="text-[10px] sm:text-xs font-semibold text-zinc-600 dark:text-zinc-300">Orangtua: {{ data_get($stats, 'total_parents', 0) }}</p>
                    </div>
                </div>

                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 group relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Setoran Hari Ini</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/10 dark:bg-emerald-400/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-sm shadow-xs">
                            <x-heroicon-o-book-open class="w-4 h-4" />
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">{{ data_get($stats, 'hafalan_today', 0) }}</p>
                    <div class="flex items-center gap-1.5 mt-2.5">
                        <span class="inline-block w-2 h-2 rounded-full bg-amber-500"></span>
                        <p class="text-[10px] sm:text-xs font-semibold text-amber-700 dark:text-amber-400">Murajaah: {{ data_get($stats, 'murajaah_today', 0) }}</p>
                    </div>
                </div>

                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 group relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Target Aktif</span>
                        <div class="w-8 h-8 rounded-xl bg-amber-500/10 dark:bg-amber-400/15 text-amber-600 dark:text-amber-400 flex items-center justify-center text-sm shadow-xs">
                            <x-heroicon-o-check-circle class="w-4 h-4" />
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight">{{ data_get($stats, 'active_targets', 0) }}</p>
                    <div class="flex items-center gap-1.5 mt-2.5">
                        <span class="inline-block w-2 h-2 rounded-full bg-rose-500"></span>
                        <p class="text-[10px] sm:text-xs font-bold text-rose-600 dark:text-rose-400">Terlambat: {{ data_get($stats, 'overdue_targets', 0) }}</p>
                    </div>
                </div>

                <div class="col-span-2 md:col-span-1 glass-liquid-card rounded-2xl p-4 sm:p-5 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 group relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Adab Hari Ini</span>
                        <div class="w-8 h-8 rounded-xl bg-teal-500/10 dark:bg-teal-400/15 text-teal-600 dark:text-teal-400 flex items-center justify-center text-sm shadow-xs">
                            <x-heroicon-o-sparkles class="w-4 h-4" />
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl font-black text-teal-600 dark:text-teal-400 tracking-tight">
                        {{ data_get($stats, 'adab_filled_today', 0) }}<span class="text-sm font-semibold text-zinc-400 dark:text-zinc-500">/{{ data_get($stats, 'adab_total_students', 0) }}</span>
                    </p>
                    <div class="flex items-center gap-1.5 mt-2.5">
                        <span class="inline-block w-2 h-2 rounded-full bg-teal-500"></span>
                        <p class="text-[10px] sm:text-xs font-semibold text-zinc-600 dark:text-zinc-400">Pengisian Angket</p>
                    </div>
                </div>
            </div>

            {{-- 2. Quick Action Links (Frosted Glass Buttons) --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2.5 sm:gap-4">
                <a href="{{ url('/students') }}" class="glass-liquid-card rounded-2xl p-3.5 sm:p-4 hover:border-emerald-500/40 hover:shadow-md active:scale-98 transition flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center flex-shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition shadow-2xs">
                        <x-heroicon-o-users class="w-5 h-5" />
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">Kelola Murid</h4>
                        <p class="text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 truncate hidden sm:block">Data murid & kelas</p>
                    </div>
                </a>

                <a href="{{ url('/hafalan-targets') }}" class="glass-liquid-card rounded-2xl p-3.5 sm:p-4 hover:border-amber-500/40 hover:shadow-md active:scale-98 transition flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400 flex items-center justify-center flex-shrink-0 group-hover:bg-amber-600 group-hover:text-white transition shadow-2xs">
                        <x-heroicon-o-check-badge class="w-5 h-5" />
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">Target Hafalan</h4>
                        <p class="text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 truncate hidden sm:block">Pantau target aktif</p>
                    </div>
                </a>

                <a href="{{ route('adab.index') }}" class="glass-liquid-card rounded-2xl p-3.5 sm:p-4 hover:border-teal-500/40 hover:shadow-md active:scale-98 transition flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-teal-500/15 text-teal-600 dark:text-teal-400 flex items-center justify-center flex-shrink-0 group-hover:bg-teal-500 group-hover:text-white transition shadow-2xs">
                        <x-heroicon-o-clipboard-document-list class="w-5 h-5" />
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">Monitoring Adab</h4>
                        <p class="text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 truncate hidden sm:block">Penilaian adab</p>
                    </div>
                </a>

                <a href="{{ route('adab-materials.index') }}" class="glass-liquid-card rounded-2xl p-3.5 sm:p-4 hover:border-indigo-500/40 hover:shadow-md active:scale-98 transition flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 flex items-center justify-center flex-shrink-0 group-hover:bg-indigo-500 group-hover:text-white transition shadow-2xs">
                        <x-heroicon-o-book-open class="w-5 h-5" />
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">Materi Adab</h4>
                        <p class="text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 truncate hidden sm:block">Panduan & berkas</p>
                    </div>
                </a>

                <a href="{{ url('/reports') }}" class="col-span-2 sm:col-span-1 glass-liquid-card rounded-2xl p-3.5 sm:p-4 hover:border-rose-500/40 hover:shadow-md active:scale-98 transition flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-rose-500/15 text-rose-600 dark:text-rose-400 flex items-center justify-center flex-shrink-0 group-hover:bg-rose-500 group-hover:text-white transition shadow-2xs">
                        <x-heroicon-o-document-chart-bar class="w-5 h-5" />
                    </div>
                    <div class="min-w-0">
                        <h4 class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">Laporan & Rapor</h4>
                        <p class="text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 truncate hidden sm:block">Ekspor data rapor</p>
                    </div>
                </a>
            </div>

            {{-- 3. Progress Murid Aktif --}}
            <div class="glass-liquid-card rounded-[1.75rem] overflow-hidden">
                <div class="px-4 sm:px-6 py-4 border-b border-zinc-200/70 dark:border-white/10 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-base text-gray-900 dark:text-white">Progress Murid Aktif</h3>
                        <p class="text-xs text-gray-500 dark:text-zinc-400">Diurutkan dari capaian target tertinggi.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if (Route::has('settings.hafalan-targets'))
                            <a href="{{ route('settings.hafalan-targets') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-emerald-300 dark:border-emerald-700/60 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 text-xs font-bold hover:bg-emerald-500/20 transition shadow-2xs">
                                <x-heroicon-o-adjustments-horizontal class="w-3.5 h-3.5" /> <span class="hidden sm:inline">Sesuaikan Target Progres</span><span class="sm:hidden">Target</span>
                            </a>
                        @endif
                        <a href="{{ url('/students') }}" class="text-xs sm:text-sm font-semibold text-emerald-600 dark:text-emerald-400 hover:underline">Lihat semua &rarr;</a>
                    </div>
                </div>

                {{-- Mobile Card List --}}
                <div class="block sm:hidden divide-y divide-zinc-200/50 dark:divide-white/5 p-3 space-y-2.5">
                    @forelse ($studentsProgress as $item)
                        @php
                            $student = $item['student'];
                            $percentage = $item['progress_percentage'] ?? 0;
                        @endphp
                        <div class="p-3 rounded-xl glass-liquid-inner space-y-2.5">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h4 class="font-semibold text-xs text-zinc-900 dark:text-white">{{ $student->name }}</h4>
                                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ $student->classRoom?->name ?? 'Kelas -' }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ $percentage }}%</span>
                                </div>
                            </div>
                            <div class="w-full bg-zinc-200/80 dark:bg-zinc-800 rounded-full h-2 overflow-hidden shadow-inner">
                                <div class="bg-gradient-to-r from-teal-500 to-emerald-500 h-2 rounded-full transition-all duration-500" style="width: {{ min($percentage, 100) }}%"></div>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-zinc-500 dark:text-zinc-400 pt-0.5">
                                <span>Target Aktif: <strong class="text-zinc-700 dark:text-zinc-200">{{ $item['active_target_count'] ?? 0 }}</strong></span>
                                @if(($item['overdue_target_count'] ?? 0) > 0)
                                    <span class="text-rose-500 font-semibold">Terlambat: {{ $item['overdue_target_count'] }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-gray-400">Belum ada data progress.</div>
                    @endforelse
                </div>

                {{-- Desktop Table View --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-zinc-100/50 dark:bg-white/[0.02] text-zinc-600 dark:text-zinc-400 border-b border-zinc-200/70 dark:border-white/10">
                            <tr>
                                <th class="px-6 py-3.5 text-left font-semibold">Murid</th>
                                <th class="px-6 py-3.5 text-left font-semibold">Kelas</th>
                                <th class="px-6 py-3.5 text-left font-semibold">Progress</th>
                                <th class="px-6 py-3.5 text-left font-semibold">Target Aktif</th>
                                <th class="px-6 py-3.5 text-left font-semibold">Terlambat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200/60 dark:divide-white/5">
                            @forelse ($studentsProgress as $item)
                                @php
                                    $student = $item['student'];
                                    $percentage = $item['progress_percentage'] ?? 0;
                                @endphp
                                <tr class="hover:bg-white/40 dark:hover:bg-white/[0.04] transition">
                                    <td class="px-6 py-3.5 font-semibold text-gray-900 dark:text-white">{{ $student->name }}</td>
                                    <td class="px-6 py-3.5 text-gray-600 dark:text-zinc-400">
                                        {{ $student->classRoom?->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <div class="w-48 bg-zinc-200/80 dark:bg-zinc-800 rounded-full h-2 overflow-hidden shadow-inner">
                                                <div class="bg-gradient-to-r from-teal-500 to-emerald-500 h-2 rounded-full transition-all duration-500" style="width: {{ min($percentage, 100) }}%"></div>
                                            </div>
                                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 min-w-[35px]">{{ $percentage }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3.5 text-gray-700 dark:text-zinc-300 font-medium">{{ $item['active_target_count'] ?? 0 }}</td>
                                    <td class="px-6 py-3.5 text-rose-600 dark:text-rose-400 font-semibold">{{ $item['overdue_target_count'] ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-zinc-400">Belum ada data progress.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 4. Target & Setoran Dua Kolom --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                <div class="glass-liquid-card rounded-[1.75rem] overflow-hidden">
                    <div class="px-5 sm:px-6 py-4 border-b border-zinc-200/70 dark:border-white/10 flex items-center justify-between">
                        <h3 class="font-bold text-base text-gray-900 dark:text-white">Target Terdekat</h3>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/20">Terbaru</span>
                    </div>
                    <div class="divide-y divide-zinc-200/60 dark:divide-white/5">
                        @forelse ($latestTargets as $target)
                            <div class="px-5 sm:px-6 py-3.5 hover:bg-white/40 dark:hover:bg-white/[0.04] transition">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-bold text-xs sm:text-sm text-gray-900 dark:text-white truncate">{{ $target->student?->name ?? '-' }}</p>
                                        <p class="text-xs text-gray-600 dark:text-zinc-300 mt-0.5">
                                            {{ $target->surah?->name_latin ?? '-' }} ayat {{ $target->ayah_range }}
                                        </p>
                                        <p class="text-[10px] sm:text-xs text-gray-400 dark:text-zinc-500 mt-0.5">
                                            Guru: {{ $target->teacher?->user?->name ?? '-' }}
                                        </p>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <p class="text-xs font-semibold text-gray-900 dark:text-zinc-200">
                                            {{ $target->target_date?->format('d M Y') }}
                                        </p>
                                        <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $target->is_overdue ? 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/20' : 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/20' }}">
                                            {{ $target->status_label }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-8 text-center text-xs text-gray-400">Belum ada target.</div>
                        @endforelse
                    </div>
                </div>

                <div class="glass-liquid-card rounded-[1.75rem] overflow-hidden">
                    <div class="px-5 sm:px-6 py-4 border-b border-zinc-200/70 dark:border-white/10 flex items-center justify-between">
                        <h3 class="font-bold text-base text-gray-900 dark:text-white">Setoran Hafalan Terbaru</h3>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">Riwayat</span>
                    </div>
                    <div class="divide-y divide-zinc-200/60 dark:divide-white/5">
                        @forelse ($latestHafalanRecords as $record)
                            <div class="px-5 sm:px-6 py-3.5 hover:bg-white/40 dark:hover:bg-white/[0.04] transition">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-bold text-xs sm:text-sm text-gray-900 dark:text-white truncate">{{ $record->student?->name ?? '-' }}</p>
                                        <p class="text-xs text-gray-600 dark:text-zinc-300 mt-0.5">
                                            {{ $record->surah?->name_latin ?? '-' }} ayat {{ $record->ayah_range }}
                                        </p>
                                        <p class="text-[10px] sm:text-xs text-gray-400 dark:text-zinc-500 mt-0.5">
                                            {{ $record->submitted_at?->format('d M Y') }}
                                        </p>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                                            {{ $record->status_label }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-8 text-center text-xs text-gray-400">Belum ada setoran.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- 5. Murajaah Terbaru --}}
            <div class="glass-liquid-card rounded-[1.75rem] overflow-hidden">
                <div class="px-5 sm:px-6 py-4 border-b border-zinc-200/70 dark:border-white/10 flex items-center justify-between">
                    <h3 class="font-bold text-base text-gray-900 dark:text-white">Murajaah Terbaru</h3>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-teal-500/10 text-teal-700 dark:text-teal-300 border border-teal-500/20">Riwayat</span>
                </div>
                <div class="divide-y divide-zinc-200/60 dark:divide-white/5">
                    @forelse ($latestMurajaahRecords as $record)
                        <div class="px-5 sm:px-6 py-3.5 hover:bg-white/40 dark:hover:bg-white/[0.04] transition">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-bold text-xs sm:text-sm text-gray-900 dark:text-white truncate">{{ $record->student?->name ?? '-' }}</p>
                                    <p class="text-xs text-gray-600 dark:text-zinc-300 mt-0.5">
                                        {{ $record->surah?->name_latin ?? '-' }} ayat {{ $record->ayah_range }}
                                    </p>
                                    <p class="text-[10px] sm:text-xs text-gray-400 dark:text-zinc-500 mt-0.5">
                                        {{ $record->reviewed_at?->format('d M Y') }}
                                    </p>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-teal-500/15 text-teal-700 dark:text-teal-300 border border-teal-500/20">
                                        {{ $record->status_label }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-8 text-center text-xs text-gray-400">Belum ada murajaah.</div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>