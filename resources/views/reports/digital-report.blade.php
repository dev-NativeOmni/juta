<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-200 leading-tight">
                    Rapor Digital Terpadu Murid
                </h2>
                <p class="text-sm text-gray-500">
                    Kompilasi perkembangan Tahfizh, Adab, dan Tanse dalam satu dokumen terpadu.
                </p>
            </div>
            
            <div class="flex gap-2">
                <a href="{{ route('digital-reports.index') }}" class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-zinc-700 rounded-xl text-sm font-semibold text-gray-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-700 transition">
                    Kembali
                </a>
                @if (\App\Http\Controllers\StudentReportController::canPrint(auth()->user()))
                    <a 
                        href="{{ route('digital-reports.print', [$student, 'academic_year' => $academicYear, 'semester' => $semester, 'term' => $tanseTerm['term']]) }}" 
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-sm transition"
                    >
                        <x-heroicon-o-printer class="w-4 h-4" /> Cetak / Simpan PDF
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-3 sm:space-y-6">

            @if (session('success'))
                <div class="p-4 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-emerald-800 dark:text-emerald-300 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Profile and Semester Selector -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 sm:gap-6">
                <!-- Profile Card -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-4 sm:p-6 shadow-sm flex flex-col justify-between lg:col-span-2">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Murid yang Dipantau</span>
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ $student->name }}</h3>
                        <p class="text-sm text-gray-500 mt-0.5">NIS: {{ $student->student_number ?: '-' }}</p>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6 text-sm">
                            <div>
                                <span class="block text-gray-400">Kelas</span>
                                <span class="font-semibold text-gray-900 dark:text-zinc-200">{{ $student->classRoom?->name ?: '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-gray-400">Program</span>
                                <span class="font-semibold text-gray-900 dark:text-zinc-200">{{ $student->classRoom?->program?->name ?: '-' }}</span>
                            </div>
                            <div>
                                <span class="block text-gray-400">Pembimbing</span>
                                <span class="font-semibold text-gray-900 dark:text-zinc-200">{{ $student->teacher?->user?->name ?: '-' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Semester Selector -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-4 sm:p-6 shadow-sm lg:col-span-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Filter Periode Akademik</span>
                    <form method="GET" action="{{ route('digital-reports.show', $student) }}" class="space-y-4 mt-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 mb-1">Tahun Ajaran</label>
                            <select name="academic_year" onchange="this.form.submit()" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-950/40 text-sm">
                                <option value="2025/2026" {{ $academicYear === '2025/2026' ? 'selected' : '' }}>2025/2026</option>
                                <option value="2026/2027" {{ $academicYear === '2026/2027' ? 'selected' : '' }}>2026/2027</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 mb-1">Semester</label>
                            <select name="semester" onchange="this.form.submit()" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-950/40 text-sm">
                                <option value="1" {{ $semester === 1 ? 'selected' : '' }}>1 (Ganjil)</option>
                                <option value="2" {{ $semester === 2 ? 'selected' : '' }}>2 (Genap)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 mb-1">Triwulan (Tanse)</label>
                            <select name="term" onchange="this.form.submit()" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-950/40 text-sm">
                                @foreach ($tanseTerm['terms'] as $termValue => $termLabel)
                                    <option value="{{ $termValue }}" @selected($tanseTerm['term'] === $termValue)>{{ $termLabel }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1.5 text-[11px] {{ $reportDate['is_set'] ? 'text-gray-500 dark:text-zinc-400' : 'text-amber-600 dark:text-amber-400' }}">
                                Tanggal rapor (BLP {{ $reportDate['exam'] }}): <span class="font-semibold">{{ $reportDate['date'] }}</span>
                                @unless ($reportDate['is_set'])
                                    &mdash; belum diatur, memakai tanggal hari ini.
                                @endunless
                            </p>
                        </div>
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-zinc-800 hover:bg-zinc-950 dark:bg-zinc-700 dark:hover:bg-zinc-650 text-white font-bold rounded-xl text-sm transition">
                            Terapkan Periode
                        </button>
                    </form>
                </div>
            </div>

            <!-- Unified Report Summary Sections -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 sm:gap-6">

                <!-- Module 1: Tahfizh -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 border-b pb-2 flex items-center gap-1.5">
                        <x-heroicon-o-book-open class="w-5 h-5 text-indigo-500" /> Perkembangan Tahfizh
                    </h4>
                    
                    <div class="mb-4 text-xs font-semibold text-indigo-700 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/20 px-3 py-2 rounded-xl border border-indigo-100 dark:border-indigo-950/30">
                        {{ $termTargetText }}
                    </div>

                    <div class="space-y-4">
                        <div class="flex justify-between items-baseline">
                            <span class="text-xs text-gray-500">Progress Hafalan Quran</span>
                            <span class="text-xl font-extrabold text-indigo-600 dark:text-indigo-400">{{ number_format($progress['progress_percent'] ?? 0, 2) }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-zinc-800 h-2.5 rounded-full overflow-hidden">
                            <div class="h-full bg-indigo-600 rounded-full" style="width: {{ min(100, max(0, $progress['progress_percent'] ?? 0)) }}%"></div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 pt-2 text-xs text-center">
                            <div class="bg-zinc-50 dark:bg-zinc-800/40 rounded-xl p-3 border dark:border-zinc-800">
                                <span class="block text-gray-400">Total Setoran</span>
                                <span class="text-base font-bold text-gray-900 dark:text-zinc-200">{{ $totalSetoran }}</span>
                            </div>
                            <div class="bg-zinc-50 dark:bg-zinc-800/40 rounded-xl p-3 border dark:border-zinc-800">
                                <span class="block text-gray-400">Total Murajaah</span>
                                <span class="text-base font-bold text-gray-900 dark:text-zinc-200">{{ $totalMurajaah }}</span>
                            </div>
                        </div>

                        <div class="bg-zinc-50 dark:bg-zinc-800/40 rounded-xl p-3 border dark:border-zinc-800 text-xs">
                            <span class="block text-gray-400 mb-1">Rerata Nilai Setoran</span>
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-700 dark:text-zinc-300">Skala 1-100</span>
                                <span class="text-base font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ $progress['average_hafalan_score'] > 0 ? round($progress['average_hafalan_score'], 1) : '-' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Module 2: Adab & Akhlak -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 border-b pb-2 flex items-center gap-1.5">
                        <x-heroicon-o-sparkles class="w-5 h-5 text-amber-500" /> Kepatuhan Adab Harian
                    </h4>
                    
                        @php
                            $totalGrade = \App\Models\Setting::getAdabGrade($avgTotal);
                            $totalLabel = \App\Models\Setting::getAdabGradeLabel($totalGrade);
                        @endphp
                        <div class="flex justify-between items-baseline">
                            <span class="text-xs text-gray-500">Skor Rerata Adab</span>
                            <div class="flex items-center gap-1.5">
                                <span class="text-xl font-extrabold text-teal-600 dark:text-teal-400">{{ round($avgTotal) }} / 100</span>
                                <span class="inline-flex items-center justify-center h-6 w-6 rounded-full text-xs font-black bg-teal-50 text-teal-700 dark:bg-teal-950/20 dark:text-teal-400 border border-teal-100 dark:border-teal-900/30">
                                    {{ $totalGrade }}
                                </span>
                            </div>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-zinc-800 h-2.5 rounded-full overflow-hidden">
                            <div class="h-full bg-teal-600 rounded-full" style="width: {{ $avgTotal }}%"></div>
                        </div>
                        <div class="text-[10px] text-teal-600 dark:text-teal-400 italic font-semibold">{{ $totalLabel }}</div>

                        <div class="space-y-2 text-xs pt-1">
                            @foreach ($adabCategories as $catIdx => $cat)
                                <div class="flex justify-between">
                                    <span class="text-gray-400">{{ $cat['title'] }}</span>
                                    <span class="font-bold text-gray-800 dark:text-zinc-300">{{ $adabCategoryScores[$catIdx] ?? 0 }}%</span>
                                </div>
                            @endforeach
                            <div class="flex justify-between border-t border-zinc-100 dark:border-zinc-800 pt-1.5 mt-1">
                                <span class="text-gray-500 font-medium">Kerajinan Kuisioner (40%)</span>
                                <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ $avgAttendanceRate }}%</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500 font-medium">Nilai Pendamping Adab (60%)</span>
                                <span class="font-bold text-purple-600 dark:text-purple-400">{{ $avgMentorScore !== null ? round($avgMentorScore) : '-' }}</span>
                            </div>
                        </div>
                </div>

                <!-- Module 3: Tanse Disiplin -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-sm">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 border-b pb-2 flex items-center gap-1.5">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-rose-500" /> Kedisiplinan &amp; Prestasi (Tanse)
                    </h4>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4 text-center">
                            <div class="bg-rose-50 dark:bg-rose-950/20 rounded-xl p-4 border border-rose-100 dark:border-rose-900/30">
                                <span class="block text-xs text-rose-500 uppercase font-bold tracking-wider">Pelanggaran</span>
                                <span class="text-2xl font-black text-rose-700 dark:text-rose-400 mt-1 block">{{ $violations->sum('points') }} Poin</span>
                                <span class="text-[10px] text-rose-400 dark:text-rose-500 block mt-1">{{ $violations->count() }} Kasus</span>
                            </div>
                            <div class="bg-emerald-50 dark:bg-emerald-950/20 rounded-xl p-4 border border-emerald-100 dark:border-emerald-900/30">
                                <span class="block text-xs text-emerald-500 uppercase font-bold tracking-wider">Penghargaan</span>
                                <span class="text-2xl font-black text-emerald-700 dark:text-emerald-400 mt-1 block">+{{ $rewards->sum('points') }} Poin</span>
                                <span class="text-[10px] text-emerald-400 dark:text-emerald-500 block mt-1">{{ $rewards->count() }} Penghargaan</span>
                            </div>
                        </div>

                        <!-- Balance indicator -->
                        @php $bal = $rewards->sum('points') - $violations->sum('points'); @endphp
                        <div class="bg-zinc-50 dark:bg-zinc-800/40 rounded-xl p-3 border dark:border-zinc-800 text-xs">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-400">Selisih Poin (Net)</span>
                                <span class="font-black text-sm {{ $bal >= 0 ? 'text-green-600 dark:text-emerald-400' : 'text-red-600 dark:text-rose-400' }}">
                                    {{ $bal > 0 ? '+' : '' }}{{ $bal }} Poin
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Automated Tanse Report Narrative -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b pb-3 dark:border-zinc-800 mb-4 gap-2">
                    <h4 class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <x-heroicon-o-shield-check class="w-5 h-5 text-indigo-600 dark:text-indigo-400 shrink-0" /> Catatan Evaluasi Kedisiplinan &amp; Ketahanan Sekolah (Tanse)
                        <span class="normal-case font-semibold text-gray-500 dark:text-zinc-400">· {{ $tanseTerm['label'] }}</span>
                    </h4>
                    <span class="px-2.5 py-1 rounded-full text-xs font-black self-start sm:self-auto {{ $tanseScore >= 80 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300' }}">
                        Skor Tanse: {{ $tanseScore }} (Predikat {{ $tanseGrade }})
                    </span>
                </div>
                <div class="p-3 sm:p-4 bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/40 rounded-xl text-xs text-indigo-900 dark:text-indigo-200 leading-relaxed font-medium">
                    {{ $autoTanseNotes }}
                </div>
            </div>

            <!-- Detailed Logs (Violations & Rewards) -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-6">
                <!-- Violations Log -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-xs">
                    <h4 class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 border-b pb-2 flex items-center gap-1.5">
                        <x-heroicon-o-exclamation-triangle class="w-4 h-4 sm:w-5 sm:h-5 text-red-500" /> Catatan Pelanggaran Tata Tertib
                    </h4>
                    @if($violations->isEmpty())
                        <p class="text-xs text-gray-500 dark:text-zinc-500 text-center py-6 sm:py-8 italic">Tidak ada catatan pelanggaran.</p>
                    @else
                        <div class="space-y-3 sm:space-y-4 max-h-[300px] overflow-y-auto">
                            @foreach($violations as $v)
                                <div class="bg-zinc-50 dark:bg-zinc-800/40 rounded-xl p-2.5 sm:p-3 border dark:border-zinc-800 text-xs relative">
                                    <div class="flex justify-between font-bold mb-1">
                                        <span class="text-gray-800 dark:text-zinc-250">{{ $v->title }}</span>
                                        <span class="text-red-600 dark:text-rose-400 font-extrabold">-{{ $v->points }} Poin</span>
                                    </div>
                                    <p class="text-gray-500 dark:text-zinc-400 text-[11px] mb-2">{{ $v->description ?: 'Tanpa deskripsi.' }}</p>
                                    <div class="flex justify-between items-center text-[10px] text-gray-400 font-semibold border-t dark:border-zinc-800 pt-1.5">
                                        <span class="inline-flex items-center gap-1"><x-heroicon-o-map-pin class="w-3.5 h-3.5 text-zinc-400" /> {{ $v->location ?: '-' }} · Kategori: {{ ucfirst((string) $v->category) ?: '-' }}</span>
                                        <span>{{ $v->date?->format('d M Y') }}</span>
                                    </div>
                                    @if($v->sanction)
                                        <div class="mt-2 p-1.5 bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 rounded text-[10px]">
                                            <strong>Sanksi:</strong> {{ $v->sanction }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Rewards Log -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-xs">
                    <h4 class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 border-b pb-2 flex items-center gap-1.5">
                        <x-heroicon-o-trophy class="w-4 h-4 sm:w-5 sm:h-5 text-amber-500" /> Catatan Penghargaan &amp; Prestasi
                    </h4>
                    @if($rewards->isEmpty())
                        <p class="text-xs text-gray-500 dark:text-zinc-500 text-center py-6 sm:py-8 italic">Tidak ada catatan penghargaan.</p>
                    @else
                        <div class="space-y-3 sm:space-y-4 max-h-[300px] overflow-y-auto">
                            @foreach($rewards as $r)
                                <div class="bg-zinc-50 dark:bg-zinc-800/40 rounded-xl p-2.5 sm:p-3 border dark:border-zinc-800 text-xs relative">
                                    <div class="flex justify-between font-bold mb-1">
                                        <span class="text-gray-800 dark:text-zinc-250">{{ $r->title }}</span>
                                        <span class="text-green-600 dark:text-emerald-400 font-extrabold">+{{ $r->points }} Poin</span>
                                    </div>
                                    <p class="text-gray-500 dark:text-zinc-400 text-[11px] mb-2">{{ $r->description ?: 'Tanpa deskripsi.' }}</p>
                                    <div class="flex justify-between items-center text-[10px] text-gray-400 font-semibold border-t dark:border-zinc-800 pt-1.5">
                                        <span>Tingkat: {{ ucfirst((string) $r->achievement_level) ?: '-' }} · Tipe: {{ $r->achievement_type === 'academic' ? 'Akademik' : 'Non-Akademik' }}</span>
                                        <span>{{ $r->date?->format('d M Y') }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Wali Kelas / Teacher Review & Status -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-xs">
                <h4 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 border-b pb-2 flex items-center gap-2">
                    <x-heroicon-o-pencil-square class="w-5 h-5 text-indigo-600 dark:text-indigo-400" /> Catatan Evaluasi Wali Kelas &amp; Otorisasi Rapor
                </h4>

                @if($canEditNotes)
                    <form method="POST" action="{{ route('digital-reports.update', $student) }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="academic_year" value="{{ $academicYear }}" />
                        <input type="hidden" name="semester" value="{{ $semester }}" />

                        <div>
                            <label for="tahfizh_target_term" class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 mb-2">Target Tahfizh Term Ini (Kustom)</label>
                            <input 
                                type="text"
                                name="tahfizh_target_term" 
                                id="tahfizh_target_term" 
                                value="{{ old('tahfizh_target_term', $report->tahfizh_target_term) }}"
                                placeholder="Biarkan kosong untuk menggunakan target default sesuai level murid..."
                                class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 bg-transparent text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white mb-4"
                            />
                        </div>

                        <div>
                            <label for="teacher_notes" class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 mb-2">Narasi Deskriptif / Evaluasi Karakter Wali Kelas</label>
                            <textarea 
                                name="teacher_notes" 
                                id="teacher_notes" 
                                rows="4" 
                                placeholder="Ketik catatan evaluasi perkembangan belajar dan karakter mulia murid di sini..."
                                class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 bg-transparent text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white"
                            >{{ old('teacher_notes', $report->teacher_notes) }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="status" class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 mb-2">Status Rapor</label>
                                <select name="status" id="status" class="block w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-950/40 text-sm">
                                    <option value="draft" {{ old('status', $report->status) === 'draft' ? 'selected' : '' }}>Draft (Hanya Guru)</option>
                                    <option value="published" {{ old('status', $report->status) === 'published' ? 'selected' : '' }}>Published (Dapat Dilihat Wali/Siswa)</option>
                                    <option value="locked" {{ old('status', $report->status) === 'locked' ? 'selected' : '' }}>Locked (Terkunci & Siap Cetak)</option>
                                </select>
                            </div>
                            <div class="flex items-end justify-end">
                                <button type="submit" class="inline-flex items-center justify-center px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl text-sm shadow-sm transition">
                                    Simpan Ulasan & Status
                                </button>
                            </div>
                        </div>
                    </form>
                @else
                    <div class="space-y-4 text-sm">
                        <div class="bg-zinc-50 dark:bg-zinc-800/40 p-4 border dark:border-zinc-800 rounded-xl leading-relaxed">
                            <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Target Tahfizh Term Ini:</span>
                            <p class="text-gray-800 dark:text-zinc-200 font-semibold mb-3">
                                {{ $report->tahfizh_target_term ?: 'Sesuai Target Level (' . $student->tahfizh_level_label . ')' }}
                            </p>
                            
                            <span class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Catatan Wali Kelas:</span>
                            <p class="text-gray-800 dark:text-zinc-200 italic">
                                "{{ $report->teacher_notes ?: 'Belum ada catatan dari wali kelas.' }}"
                            </p>
                        </div>
                        <div class="flex justify-between items-center text-xs border-t dark:border-zinc-800 pt-3">
                            <span>Status Rapor: <strong class="uppercase text-indigo-600 dark:text-indigo-400">{{ $report->status }}</strong></span>
                            @if($report->status === 'locked')
                                <span class="text-rose-500 font-bold inline-flex items-center gap-1"><x-heroicon-o-lock-closed class="w-3.5 h-3.5" /> Catatan Terkunci</span>
                            @else
                                <span class="text-gray-400">Hanya guru kelas yang dapat memperbarui catatan ini.</span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
