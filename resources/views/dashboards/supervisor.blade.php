<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-0.5">
            <h2 class="text-lg sm:text-xl font-bold leading-tight text-gray-900 dark:text-zinc-100">
                Dashboard Koordinator Keagamaan
            </h2>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-zinc-400">
                Pemantauan progres pengisian kuisioner adab & akhlak murid harian.
            </p>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto space-y-5 sm:space-y-6 px-3 sm:px-6 lg:px-8">
            
            <!-- Hari & Tanggal Hero Widget (Frosted Liquid Mesh) -->
            <div class="glass-liquid-card rounded-2xl p-4 sm:p-6 text-zinc-900 dark:text-white shadow-sm relative overflow-hidden border border-teal-500/20">
                <div class="absolute -right-6 -bottom-6 w-40 h-40 bg-teal-500/10 dark:bg-teal-400/10 rounded-full blur-2xl pointer-events-none"></div>
                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-teal-500/10 text-teal-700 dark:text-teal-300 border border-teal-500/20">
                                <x-heroicon-o-calendar-days class="w-3.5 h-3.5" /> Evaluasi Harian
                            </span>
                            <span class="text-xs text-zinc-400 dark:text-zinc-500">•</span>
                            <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">{{ \Carbon\Carbon::parse($today)->translatedFormat('l, d F Y') }}</span>
                        </div>
                        <h3 class="text-lg sm:text-2xl font-black mt-1.5 tracking-tight">Monitoring Adab & Akhlak Santri</h3>
                        <p class="text-xs sm:text-sm text-zinc-650 dark:text-zinc-400 mt-1 max-w-2xl leading-relaxed">
                            Pantau kedisiplinan dan pembiasaan ibadah harian santri secara real-time untuk membangun generasi unggul berkarakter Qur'ani.
                        </p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <div class="p-3 rounded-2xl bg-teal-500/10 dark:bg-teal-400/10 border border-teal-500/20 text-center min-w-[90px]">
                            <span class="text-[10px] uppercase tracking-wider font-bold text-zinc-400 dark:text-zinc-400 block">Kepatuhan</span>
                            <span class="text-xl sm:text-2xl font-black text-teal-600 dark:text-teal-400">{{ $totalStudents > 0 ? round(($filledCount / $totalStudents) * 100) : 0 }}%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kartu Statistik (Bento Modern Grid) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4.5">
                
                <!-- Total Murid -->
                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] sm:text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Total Santri</p>
                        <h4 class="text-2xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight mt-1">{{ $totalStudents }}</h4>
                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1 font-medium">Seluruh kelas aktif</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-teal-500/10 text-teal-600 dark:text-teal-400 flex items-center justify-center text-xl shadow-xs">
                        <x-heroicon-o-users class="w-6 h-6" />
                    </div>
                </div>

                <!-- Sudah Mengisi -->
                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] sm:text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Sudah Mengisi</p>
                        <div class="flex items-baseline gap-2 mt-1">
                            <h4 class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">{{ $filledCount }}</h4>
                            <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                ({{ $totalStudents > 0 ? round(($filledCount / $totalStudents) * 100) : 0 }}%)
                            </span>
                        </div>
                        <p class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-1 font-medium flex items-center gap-1">
                            <x-heroicon-o-check-circle class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" /> Telah dievaluasi
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-xl shadow-xs">
                        <x-heroicon-o-check-circle class="w-6 h-6" />
                    </div>
                </div>

                <!-- Belum Mengisi -->
                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-between">
                    <div>
                        <p class="text-[10px] sm:text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Belum Mengisi</p>
                        <div class="flex items-baseline gap-2 mt-1">
                            <h4 class="text-2xl sm:text-3xl font-black text-rose-600 dark:text-rose-400 tracking-tight">{{ $notFilledCount }}</h4>
                            <span class="text-xs font-bold text-rose-600 dark:text-rose-400">
                                ({{ $totalStudents > 0 ? round(($notFilledCount / $totalStudents) * 100) : 0 }}%)
                            </span>
                        </div>
                        <p class="text-[11px] text-rose-600 dark:text-rose-400 mt-1 font-medium flex items-center gap-1">
                            <x-heroicon-o-exclamation-triangle class="w-3.5 h-3.5 text-rose-500 shrink-0" /> Perlu diingatkan
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-rose-500/10 text-rose-600 dark:text-rose-400 flex items-center justify-center text-xl shadow-xs">
                        <x-heroicon-o-clock class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <!-- Progress Bar Card -->
            <div class="glass-liquid-card rounded-2xl p-4 sm:p-5">
                <div class="flex justify-between items-center mb-2.5">
                    <h3 class="text-xs sm:text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <x-heroicon-o-chart-bar class="w-4 h-4 text-teal-600 dark:text-teal-400" /> <span>Progres Pengisian Seluruh Murid</span>
                    </h3>
                    @php
                        $percent = $totalStudents > 0 ? ($filledCount / $totalStudents) * 100 : 0;
                    @endphp
                    <span class="text-xs font-bold text-teal-600 dark:text-teal-400">{{ round($percent, 1) }}% Terisi ({{ $filledCount }}/{{ $totalStudents }})</span>
                </div>
                <div class="w-full bg-zinc-200/70 dark:bg-zinc-800 rounded-full h-3 overflow-hidden">
                    <div class="bg-gradient-to-r from-teal-500 to-emerald-500 h-full rounded-full transition-all duration-500 shadow-sm" style="width: {{ $percent }}%"></div>
                </div>
            </div>

            <!-- Daftar Progres Pengisian Hari Ini -->
            <div class="glass-liquid-card rounded-2xl overflow-hidden shadow-sm">
                <div class="px-4 sm:px-6 py-3.5 sm:py-4 border-b border-zinc-200/70 dark:border-white/10 flex justify-between items-center bg-zinc-50/50 dark:bg-zinc-900/50">
                    <h3 class="text-xs sm:text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-clipboard-document-list class="w-4 h-4 text-indigo-600 dark:text-indigo-400" /> <span>Status Pengisian Murid Hari Ini</span>
                    </h3>
                    <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Total: {{ $totalStudents }} Murid</span>
                </div>

                {{-- Mobile Card List --}}
                <div class="block sm:hidden divide-y divide-zinc-100 dark:divide-zinc-800/60 p-2 space-y-2">
                    @forelse ($students as $student)
                        @php
                            $hasRecord = $student->adabRecords->isNotEmpty();
                            $score = $hasRecord ? $student->adabRecords->first()->total_score : null;
                        @endphp
                        <div class="p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-800/40 space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h4 class="font-bold text-xs text-zinc-900 dark:text-white">{{ $student->name }}</h4>
                                    <p class="text-[10px] text-zinc-400">Kelas: {{ $student->classRoom?->name ?: '-' }} · NIS: {{ $student->student_number ?: '-' }}</p>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    @if ($hasRecord)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30">
                                            <x-heroicon-o-check class="w-3 h-3 stroke-[2.5]" /> Sudah
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-100 dark:border-rose-900/30">
                                            <x-heroicon-o-x-mark class="w-3 h-3 stroke-[2.5]" /> Belum
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center justify-between pt-1 border-t border-zinc-100 dark:border-zinc-800/60 text-xs">
                                <div>
                                    @if ($hasRecord)
                                        <span class="text-[11px] font-bold {{ $score >= 85 ? 'text-emerald-600 dark:text-emerald-400' : ($score >= 70 ? 'text-indigo-600 dark:text-indigo-400' : 'text-zinc-700 dark:text-zinc-200') }}">
                                            Skor: {{ $score }} <span class="text-[10px] text-zinc-400 font-normal">/ 100</span>
                                        </span>
                                    @else
                                        <span class="text-[10px] text-zinc-400 italic">Belum ada skor</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('adab.show', $student) }}" class="px-2 py-1 text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 rounded-lg shadow-2xs">
                                        Rincian
                                    </a>
                                    @if (!$hasRecord)
                                        <a href="{{ route('adab.create', $student) }}" class="px-2 py-1 text-[11px] font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg shadow-2xs">
                                            Bantu Isi
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-zinc-400">Tidak ada data murid ditemukan.</div>
                    @endforelse
                </div>

                {{-- Desktop Table View --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-250 dark:divide-zinc-800">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/50 text-left text-xs font-semibold text-zinc-550 dark:text-zinc-400 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Nama Murid</th>
                                <th class="px-6 py-4">Kelas</th>
                                <th class="px-6 py-4 text-center">Status Pengisian</th>
                                <th class="px-6 py-4 text-center">Skor Adab Hari Ini</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($students as $student)
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-white/[0.01] transition duration-150">
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-zinc-900 dark:text-white">{{ $student->name }}</div>
                                        <div class="text-xs text-zinc-400 dark:text-zinc-550 mt-0.5">NIS: {{ $student->student_number ?: '-' }}</div>
                                    </td>
                                    
                                    <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200">
                                            {{ $student->classRoom?->name ?: '-' }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        @if ($student->adabRecords->isNotEmpty())
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Sudah Mengisi
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-455 border border-rose-100 dark:border-rose-900/30">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                Belum Mengisi
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        @if ($student->adabRecords->isNotEmpty())
                                            @php
                                                $score = $student->adabRecords->first()->total_score;
                                                $badgeColor = 'text-zinc-800 dark:text-zinc-200';
                                                if ($score >= 85) {
                                                    $badgeColor = 'text-emerald-600 dark:text-emerald-450';
                                                } elseif ($score >= 70) {
                                                    $badgeColor = 'text-indigo-600 dark:text-indigo-400';
                                                }
                                            @endphp
                                            <span class="font-extrabold text-sm {{ $badgeColor }}">
                                                {{ $score }} <span class="text-xs text-zinc-400 font-normal">/ 100</span>
                                            </span>
                                        @else
                                            <span class="text-xs text-zinc-400 dark:text-zinc-600 italic">-</span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-right space-x-1">
                                        <a href="{{ route('adab.show', $student) }}" class="btn-action-detail">
                                            Rincian & Riwayat
                                        </a>
                                        @if ($student->adabRecords->isEmpty())
                                            <a href="{{ route('adab.create', $student) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg shadow-sm hover:scale-[1.02] transition duration-150">
                                                Bantu Isi
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-zinc-400 dark:text-zinc-500">
                                        Tidak ada data murid ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
