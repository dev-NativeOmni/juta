<x-app-layout>
    @php
        $thisYear = $year ?? (int) date('Y');
        $thisMonth = $month ?? (int) date('n');
    @endphp
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
            {{ __('Laporan Perkembangan Adab') }}
        </h2>
    </x-slot>

    <div class="py-3 sm:py-6">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-3.5 sm:space-y-5">

            @if (session('success'))
                <div class="p-3 sm:p-4 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-800 dark:text-emerald-300 text-xs sm:text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="p-3 sm:p-4 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 rounded-xl text-rose-800 dark:text-rose-300 text-xs sm:text-sm">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Header Profil --}}
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-3.5 sm:p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-3 sm:gap-4">
                <div>
                    <span class="text-[10px] sm:text-xs font-semibold text-indigo-500 dark:text-indigo-400 uppercase tracking-wider block mb-0.5 sm:mb-1">Rincian Perkembangan Adab & Karakter</span>
                    <h3 class="text-base sm:text-2xl font-bold text-zinc-900 dark:text-white">{{ $student->name }}</h3>
                    <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mt-0.5 sm:mt-1">
                        Kelas: {{ $student->classRoom?->name ?: '-' }} | NIS: {{ $student->student_number ?: '-' }} | Guru: {{ $student->teacher?->user?->name ?: '-' }}
                    </p>
                </div>
                <div class="flex gap-2 flex-wrap w-full sm:w-auto">
                    @if (!auth()->user()->hasRole('student'))
                        <a href="{{ route('adab.index') }}" class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-1.5 sm:py-2 border border-zinc-300 dark:border-zinc-700 text-xs sm:text-sm font-semibold text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
                            <x-heroicon-m-arrow-left class="w-4 h-4 shrink-0" />
                            <span>Kembali</span>
                        </a>
                    @endif

                    @php
                        $today = now()->toDateString();
                        $filledToday = \App\Models\AdabRecord::where('student_id', $student->id)->where('assessment_date', $today)->exists();
                        $isOwn = auth()->user()->hasRole('student') && $student->user_id === auth()->id();
                    @endphp

                    @if (($isOwn || $isMentor) && !$filledToday)
                        <a href="{{ route('adab.create', $student) }}" class="inline-flex items-center px-3 sm:px-4 py-1.5 sm:py-2 text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition shadow-sm gap-1.5">
                            <x-heroicon-o-pencil-square class="w-3.5 h-3.5 sm:w-4 sm:h-4" /> Isi Kuisioner Hari Ini
                        </a>
                    @endif
                </div>
            </div>

            {{-- Scorecard --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 sm:gap-5">

                {{-- Nilai Total (Kiri) --}}
                <div class="bg-gradient-to-br from-teal-600 via-indigo-700 to-indigo-900 text-white rounded-xl shadow-md p-3.5 sm:p-5 flex flex-col justify-between relative overflow-hidden">
                    <div class="absolute -right-6 -bottom-6 opacity-10">
                        <svg class="h-40 w-40" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H7c0-2.76 2.24-5 5-5s5 2.24 5 5c0 1.04-.42 1.99-1.07 2.75z"/>
                        </svg>
                    </div>
                    <div class="relative z-10">
                        <h4 class="text-xs sm:text-sm font-semibold uppercase text-teal-200 tracking-wider">Nilai Adab Kumulatif</h4>
                        <div class="flex items-baseline gap-2.5 sm:gap-3 mt-2 sm:mt-4">
                            <span class="text-3xl sm:text-5xl font-black tracking-tight">{{ round($combinedScore) }}</span>
                            <div>
                                <span class="text-sm sm:text-xl text-teal-200">/ 100</span>
                                <div class="text-2xl sm:text-4xl font-black text-yellow-300 leading-tight">{{ $grade }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="relative z-10 mt-3 sm:mt-4">
                        <div class="text-xs sm:text-sm font-semibold text-teal-100">{{ $gradeLabel }}</div>
                        <div class="w-full bg-indigo-950/50 rounded-full h-2 mt-1.5 sm:mt-2 border border-indigo-700">
                            <div class="bg-gradient-to-r from-emerald-400 to-teal-300 h-full rounded-full" style="width: {{ $combinedScore }}%"></div>
                        </div>
                        @if ($latestMentor)
                            <div class="text-[10px] sm:text-xs text-teal-300 mt-1.5 sm:mt-2">Termasuk nilai pendamping: {{ $latestMentor->mentor_score }}/100 ({{ $latestMentor->period }})</div>
                        @else
                            <div class="text-[10px] sm:text-xs text-teal-300 mt-1.5 sm:mt-2 italic">Nilai pendamping belum diisi bulan ini</div>
                        @endif
                    </div>
                </div>

                {{-- Per-Kategori Breakdown (Kanan) --}}
                <div class="lg:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-3.5 sm:p-5 shadow-sm">
                    <h4 class="text-xs sm:text-sm font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wide mb-3 sm:mb-5 flex items-center gap-1.5">
                        <x-heroicon-o-chart-bar class="w-4 h-4 sm:w-5 sm:h-5 text-indigo-500" /> Capaian per Kategori Adab
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
                        @foreach ($categories as $catIdx => $cat)
                            @php
                                $pct   = $catAverages[$catIdx] ?? 0;
                                $grd   = \App\Models\Setting::getAdabGrade($pct);
                                $color = match($grd) {
                                    'A'     => 'bg-emerald-500',
                                    'B'     => 'bg-teal-500',
                                    'C'     => 'bg-amber-500',
                                    'D'     => 'bg-orange-500',
                                    default => 'bg-rose-500',
                                };
                            @endphp
                            <div class="bg-zinc-50 dark:bg-zinc-800/40 rounded-xl p-2.5 sm:p-3.5 border border-zinc-100 dark:border-zinc-800">
                                <div class="flex justify-between items-center mb-1.5 sm:mb-2">
                                    <span class="text-[11px] sm:text-xs font-semibold text-zinc-600 dark:text-zinc-400">{{ $cat['title'] }}</span>
                                    <span class="text-sm sm:text-base font-black text-zinc-800 dark:text-zinc-100">{{ $grd }}</span>
                                </div>
                                <div class="w-full bg-zinc-200 dark:bg-zinc-700 rounded-full h-1.5 sm:h-2">
                                    <div class="{{ $color }} h-full rounded-full transition-all" style="width: {{ $pct }}%"></div>
                                </div>
                                <div class="text-right text-[10px] sm:text-xs text-zinc-400 mt-1">{{ $pct }}%</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-3 sm:mt-5 pt-3 sm:pt-4 border-t border-zinc-100 dark:border-zinc-800 grid grid-cols-3 gap-2 sm:gap-4 text-center text-[10px] sm:text-xs">
                        <div>
                            <div class="font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-0.5 sm:mb-1">Nilai Mandiri</div>
                            <div class="text-base sm:text-2xl font-black text-indigo-600 dark:text-indigo-400">{{ round($attendanceRate) }}<span class="text-xs font-medium">/100</span></div>
                        </div>
                        <div>
                            <div class="font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-0.5 sm:mb-1">Nilai Pendamping</div>
                            <div class="text-base sm:text-2xl font-black text-purple-600 dark:text-purple-400">
                                {{ $latestMentor ? $latestMentor->mentor_score : '-' }}<span class="text-xs font-medium">{{ $latestMentor ? '/100' : '' }}</span>
                            </div>
                        </div>
                        <div>
                            <div class="font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-0.5 sm:mb-1">Nilai Akhir</div>
                            <div class="text-base sm:text-2xl font-black text-teal-600 dark:text-teal-400">{{ round($combinedScore) }}<span class="text-xs font-medium">/100</span></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Form Penilaian Pendamping (Bulanan) --}}
            @if ($isMentor)
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-3.5 sm:px-6 py-2.5 sm:py-4 border-b border-zinc-200 dark:border-zinc-800 bg-purple-50/50 dark:bg-purple-950/10">
                        <h4 class="text-xs sm:text-sm font-bold text-purple-800 dark:text-purple-300 uppercase tracking-wide flex items-center gap-1.5">
                            <x-heroicon-o-key class="w-4 h-4 sm:w-5 sm:h-5 text-purple-600 dark:text-purple-400" /> Penilaian Pendamping Adab (Bulanan)
                        </h4>
                        <p class="text-[11px] sm:text-xs text-purple-600 dark:text-purple-400 mt-0.5 sm:mt-1">
                            Isi nilai pendamping sekali per bulan. Nilai ini dikombinasikan 50/50 dengan nilai mandiri murid.
                            @if ($mentorAlreadyScoredThisMonth)
                                <strong class="text-amber-600 dark:text-amber-400">Bulan ini sudah diisi — Anda bisa memperbarui nilainya.</strong>
                            @endif
                        </p>
                    </div>
                    <form method="POST" action="{{ route('adab.store-mentor-score', $student) }}" class="p-3.5 sm:p-6">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 sm:gap-4 items-end">
                            <div>
                                <label class="block text-[11px] sm:text-xs font-bold uppercase text-zinc-500 dark:text-zinc-400 mb-1">Bulan</label>
                                <select name="month" class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-xs sm:text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    @php
                                        $bulanList = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',
                                                      7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
                                    @endphp
                                    @foreach ($bulanList as $num => $nama)
                                        <option value="{{ $num }}" @selected($num === $thisMonth)>{{ $nama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] sm:text-xs font-bold uppercase text-zinc-500 dark:text-zinc-400 mb-1">Tahun</label>
                                <select name="year" class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-xs sm:text-sm focus:ring-indigo-500 focus:border-indigo-500">
                                    @for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                                        <option value="{{ $y }}" @selected($y === $thisYear)>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] sm:text-xs font-bold uppercase text-zinc-500 dark:text-zinc-400 mb-1">Nilai Pendamping (0–100)</label>
                                <input type="number" name="mentor_score" min="0" max="100" required
                                    value="{{ $mentorAlreadyScoredThisMonth ? ($mentorAssessments->where('year', $thisYear)->where('month', $thisMonth)->first()?->mentor_score ?? '') : '' }}"
                                    placeholder="0 – 100"
                                    class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-xs sm:text-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div>
                                <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 sm:py-2.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs sm:text-sm font-bold transition shadow-sm gap-1.5">
                                    @if ($mentorAlreadyScoredThisMonth)
                                        <x-heroicon-o-arrow-path class="w-4 h-4" /> Perbarui Nilai
                                    @else
                                        <x-heroicon-o-arrow-down-on-square class="w-4 h-4" /> Simpan Nilai
                                    @endif
                                </button>
                            </div>
                        </div>
                        <div class="mt-2.5 sm:mt-3">
                            <label class="block text-[11px] sm:text-xs font-bold uppercase text-zinc-500 dark:text-zinc-400 mb-1">Catatan Pendamping (Opsional)</label>
                            <textarea name="notes" rows="2" placeholder="Catatan observasi pendamping..."
                                class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-xs sm:text-sm focus:ring-indigo-500 focus:border-indigo-500 placeholder-zinc-400"></textarea>
                        </div>
                    </form>
                </div>

                {{-- Riwayat Penilaian Pendamping --}}
                @if ($mentorAssessments->isNotEmpty())
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-3.5 sm:px-6 py-2.5 sm:py-4 border-b border-zinc-200 dark:border-zinc-800">
                            <h4 class="text-xs sm:text-sm font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wide flex items-center gap-1.5">
                                <x-heroicon-o-clipboard-document-list class="w-4 h-4 sm:w-5 sm:h-5 text-purple-600 dark:text-purple-400" /> Riwayat Nilai Pendamping
                            </h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-zinc-100 dark:divide-zinc-800 text-xs sm:text-sm">
                                <thead class="bg-zinc-50 dark:bg-zinc-900/50 text-[10px] sm:text-xs font-semibold text-zinc-500 uppercase tracking-wider">
                                    <tr>
                                        <th class="px-3.5 sm:px-6 py-2.5 sm:py-3 text-left">Periode</th>
                                        <th class="px-3.5 sm:px-6 py-2.5 sm:py-3 text-center">Nilai Pendamping</th>
                                        <th class="px-3.5 sm:px-6 py-2.5 sm:py-3 text-center">Nilai Huruf</th>
                                        <th class="px-3.5 sm:px-6 py-2.5 sm:py-3 text-left">Dinilai Oleh</th>
                                        <th class="px-3.5 sm:px-6 py-2.5 sm:py-3 text-left">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @foreach ($mentorAssessments as $ma)
                                        @php $mg = \App\Models\Setting::getAdabGrade($ma->mentor_score); @endphp
                                        <tr class="hover:bg-zinc-50/50 dark:hover:bg-white/[0.01]">
                                            <td class="px-3.5 sm:px-6 py-2 sm:py-3 font-medium text-zinc-800 dark:text-zinc-200">{{ $ma->period }}</td>
                                            <td class="px-3.5 sm:px-6 py-2 sm:py-3 text-center font-bold text-purple-600 dark:text-purple-400">{{ $ma->mentor_score }}/100</td>
                                            <td class="px-3.5 sm:px-6 py-2 sm:py-3 text-center">
                                                <span class="inline-flex items-center justify-center h-6 w-6 sm:h-7 sm:w-7 rounded-full text-xs sm:text-sm font-black
                                                    {{ match($mg) { 'A' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400', 'B' => 'bg-teal-100 text-teal-700 dark:bg-teal-950/30 dark:text-teal-400', 'C' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400', 'D' => 'bg-orange-100 text-orange-700 dark:bg-orange-950/30 dark:text-orange-400', default => 'bg-rose-100 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400' } }}">
                                                    {{ $mg }}
                                                </span>
                                            </td>
                                            <td class="px-3.5 sm:px-6 py-2 sm:py-3 text-zinc-600 dark:text-zinc-400">{{ $ma->mentor?->name ?: '-' }}</td>
                                            <td class="px-3.5 sm:px-6 py-2 sm:py-3 text-zinc-500 dark:text-zinc-500 text-[10px] sm:text-xs italic">{{ $ma->notes ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            @endif

            {{-- Riwayat Kuisioner Harian --}}
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl shadow-sm overflow-hidden">
                <div class="px-3.5 sm:px-6 py-2.5 sm:py-4 border-b border-zinc-200 dark:border-zinc-800 flex justify-between items-center">
                    <h4 class="text-xs sm:text-sm font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wide flex items-center gap-1.5">
                        <x-heroicon-o-calendar class="w-4 h-4 sm:w-5 sm:h-5 text-indigo-600 dark:text-indigo-400" /> Riwayat Kuisioner Harian
                    </h4>
                    <span class="text-[11px] sm:text-xs text-zinc-400 dark:text-zinc-500">{{ $adabRecords->total() }} entri</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-100 dark:divide-zinc-800 text-xs sm:text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/50 text-[10px] sm:text-xs font-semibold text-zinc-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-3.5 sm:px-6 py-2.5 sm:py-3 text-left">Tanggal</th>
                                <th class="px-3.5 sm:px-6 py-2.5 sm:py-3 text-center">Nilai Mandiri</th>
                                <th class="px-3.5 sm:px-6 py-2.5 sm:py-3 text-center">Nilai Huruf</th>
                                <th class="px-3.5 sm:px-6 py-2.5 sm:py-3 text-left">Pengisi</th>
                                <th class="px-3.5 sm:px-6 py-2.5 sm:py-3 text-left">Catatan</th>
                                @if (auth()->user()->hasAnyRole(['super_admin', 'admin', 'supervisor']))
                                    <th class="px-3.5 sm:px-6 py-2.5 sm:py-3 text-right">Aksi</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($adabRecords as $record)
                                @php
                                    $sc  = $record->student_score ?? $record->total_score ?? 0;
                                    $rg  = \App\Models\Setting::getAdabGrade($sc);
                                    $badgeClass = match($rg) {
                                        'A'     => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400',
                                        'B'     => 'bg-teal-100 text-teal-700 dark:bg-teal-950/30 dark:text-teal-400',
                                        'C'     => 'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-400',
                                        'D'     => 'bg-orange-100 text-orange-700 dark:bg-orange-950/30 dark:text-orange-400',
                                        default => 'bg-rose-100 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400',
                                    };
                                @endphp
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-white/[0.01] transition">
                                    <td class="px-3.5 sm:px-6 py-2 sm:py-3 font-medium text-zinc-800 dark:text-zinc-200">
                                        {{ $record->assessment_date?->translatedFormat('d F Y') }}
                                    </td>
                                    <td class="px-3.5 sm:px-6 py-2 sm:py-3 text-center font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ round($sc) }}/100
                                    </td>
                                    <td class="px-3.5 sm:px-6 py-2 sm:py-3 text-center">
                                        <span class="inline-flex items-center justify-center h-6 w-6 sm:h-7 sm:w-7 rounded-full text-xs sm:text-sm font-black {{ $badgeClass }}">
                                            {{ $rg }}
                                        </span>
                                    </td>
                                    <td class="px-3.5 sm:px-6 py-2 sm:py-3 text-zinc-600 dark:text-zinc-400">
                                        {{ $record->evaluator?->name ?: 'Murid Sendiri' }}
                                    </td>
                                    <td class="px-3.5 sm:px-6 py-2 sm:py-3 text-zinc-500 dark:text-zinc-500 text-[10px] sm:text-xs italic max-w-xs truncate">
                                        {{ $record->notes ?: '-' }}
                                    </td>
                                    @if (auth()->user()->hasAnyRole(['super_admin', 'admin', 'supervisor']))
                                        <td class="px-3.5 sm:px-6 py-2 sm:py-3 text-right">
                                            <form method="POST" action="{{ route('adab.destroy', $record) }}" onsubmit="return confirm('Hapus penilaian ini?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-xs text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300 font-semibold transition">
                                                    Hapus
                                                </button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-3.5 sm:px-6 py-6 sm:py-8 text-center text-zinc-400 dark:text-zinc-500 italic text-xs sm:text-sm">
                                        Belum ada riwayat pengisian kuisioner.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($adabRecords->hasPages())
                    <div class="px-3.5 sm:px-6 py-2.5 sm:py-4 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                        {{ $adabRecords->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
