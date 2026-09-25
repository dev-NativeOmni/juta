<x-app-layout>
    <!-- Google Fonts for premium Quran Typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400;1,700&family=Scheherazade+New:wght@400;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <div class="py-6" x-data="tahfizhExamApp()" x-init="initApp()">
        <div class="max-w-[1600px] mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-800 pb-4 mb-4">
                <div>
                    <h2 class="font-bold text-xl text-zinc-900 dark:text-white leading-tight">
                        Edit Ujian Tahfizh
                    </h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Gunakan panel Al-Qur'an interaktif di sebelah kiri sebagai panduan saat menguji kelancaran hafalan murid di panel kanan.
                    </p>
                </div>
                <a href="{{ route('tahfizh-exams.index') }}" class="px-4 py-2 border border-zinc-300 dark:border-zinc-700 rounded-lg text-xs font-semibold text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 shadow-sm transition">
                    Kembali ke Daftar
                </a>
            </div>

            <!-- Main Layout Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- ================= LEFT PANEL: INTERACTIVE MUSHAF ================= -->
                <div class="lg:col-span-7 bg-[#fdfbf7] text-[#4a3c31] border border-[#e6dcbf] rounded-2xl p-5 shadow-sm space-y-4 flex flex-col h-[780px]">
                    <!-- Toolbar -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-[#f5ebd3] p-3 rounded-xl border border-[#e1d5b7]">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider opacity-70 mb-1">Pilih Surah</label>
                            <select x-model="surah" @change="surahChanged()" class="block w-full text-xs rounded-lg py-1.5 px-2 bg-white border border-[#d6caa2] focus:outline-none focus:ring-1 focus:ring-amber-500 font-semibold text-[#4a3c31]">
                                <template x-for="s in surahList" :key="s.id">
                                    <option :value="s.id" x-text="`${s.id}. ${s.name} (${s.ar})`"></option>
                                </template>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider opacity-70 mb-1">Pilih Juz</label>
                            <select x-model="juzSelect" @change="juzChanged()" class="block w-full text-xs rounded-lg py-1.5 px-2 bg-white border border-[#d6caa2] focus:outline-none focus:ring-1 focus:ring-amber-500 font-semibold text-[#4a3c31]">
                                <template x-for="j in 30" :key="j">
                                    <option :value="j" x-text="`Juz ${j}`"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <!-- Page Navigation -->
                    <div class="flex items-center justify-between px-2 text-xs py-1 border-b border-[#ebdcb3]/60">
                        <button @click="prevPage()" :disabled="page <= 1" class="px-3 py-1 bg-[#eae0c5] hover:bg-[#decfa7] disabled:opacity-40 rounded font-bold transition">
                            Sebelumnya
                        </button>
                        <div>
                            <span class="opacity-75">Halaman</span>
                            <input type="number" x-model.number="page" @change="pageInputChanged()" min="1" max="604" class="w-14 text-center font-bold bg-white border border-[#d2c49b] rounded px-1 py-0.5 mx-1" />
                            <span class="opacity-75">/ 604</span>
                        </div>
                        <button @click="nextPage()" :disabled="page >= 604" class="px-3 py-1 bg-[#eae0c5] hover:bg-[#decfa7] disabled:opacity-40 rounded font-bold transition">
                            Berikutnya
                        </button>
                    </div>

                    <!-- Verses List View -->
                    <div id="verse-list-container" class="flex-1 overflow-y-auto space-y-4 pr-1 scrollbar-thin">
                        <template x-if="loading">
                            <div class="flex flex-col items-center justify-center h-full py-12 space-y-3 opacity-60">
                                <svg class="animate-spin h-8 w-8 text-amber-700" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-xs font-semibold">Memuat teks Al-Qur'an...</span>
                            </div>
                        </template>

                        <template x-if="!loading && verses.length === 0">
                            <div class="text-center py-12 opacity-60 text-xs">
                                Gagal memuat teks Al-Qur'an. Pastikan koneksi internet aktif.
                            </div>
                        </template>

                        <template x-if="!loading && verses.length > 0">
                            <div class="space-y-4">
                                <template x-for="verse in verses" :key="verse.id">
                                    <div class="bg-[#fcf8ee] border border-[#eadebe] rounded-xl p-4 space-y-2 shadow-xs">
                                        <div class="flex items-center justify-between text-[10px] border-b border-[#eae1c8] pb-1 opacity-75">
                                            <span class="font-bold" x-text="getSurahNameAndAyah(verse.verse_key)"></span>
                                            <span class="bg-[#e9dec1] px-1.5 py-0.5 rounded" x-text="`Juz ${getJuzFromPage(page)}`"></span>
                                        </div>
                                        <div class="text-right text-2xl font-arabic py-2 select-all leading-loose text-amber-950 font-scheherazade" x-html="verse.text_uthmani"></div>
                                        <div class="text-[11px] text-[#6b5847] leading-relaxed italic border-t border-[#eae1c8]/50 pt-2" x-html="getIndonesianTranslation(verse)"></div>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- ================= RIGHT PANEL: EXAM ASSESSMENT FORM ================= -->
                <div class="lg:col-span-5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-sm lg:sticky lg:top-6 lg:max-h-[780px] lg:overflow-y-auto">
                    <div class="p-6 space-y-5">
                    <h3 class="font-bold text-sm text-zinc-700 dark:text-zinc-300 uppercase tracking-wide border-b border-zinc-200 dark:border-zinc-800 pb-2 mb-3">
                        Formulir Penilaian Ujian
                    </h3>

                    <form action="{{ route('tahfizh-exams.update', $exam->id) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <!-- Student Selector -->
                        <div>
                            <label for="student_id" class="block text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase mb-1.5">Murid yang Diuji *</label>
                            <select id="student_id" name="student_id" required class="block w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm focus:border-indigo-500 focus:ring-indigo-500 transition">
                                <option value="">-- Pilih Murid --</option>
                                @foreach ($students as $student)
                                    <option value="{{ $student->id }}" @selected(old('student_id', $exam->student_id) == $student->id)>
                                        {{ $student->name }} (Kelas: {{ $student->classRoom?->name ?: '-' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('student_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Teacher / Evaluator -->
                        <div>
                            <label for="teacher_id" class="block text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase mb-1.5">Ustadz / Penguji *</label>
                            <select id="teacher_id" name="teacher_id" required class="block w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm focus:border-indigo-500 focus:ring-indigo-500 transition">
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" @selected(old('teacher_id', $exam->teacher_id) == $teacher->id)>
                                        {{ $teacher->user?->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('teacher_id')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Exam Date -->
                        <div>
                            <label for="exam_date" class="block text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase mb-1.5">Tanggal Ujian *</label>
                            <input type="date" id="exam_date" name="exam_date" value="{{ old('exam_date', $exam->exam_date?->format('Y-m-d')) }}" required class="block w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm focus:border-indigo-500 focus:ring-indigo-500 transition" />
                            @error('exam_date')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Exam Type -->
                        <div>
                            <label class="block text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase mb-1.5">Kategori Ujian *</label>
                            <div class="flex items-center gap-4 mt-1 bg-zinc-50 dark:bg-zinc-800/50 p-2.5 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                <label class="inline-flex items-center text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                    <input type="radio" name="type" value="juz" x-model="examType" class="text-indigo-600 focus:ring-indigo-500" />
                                    <span class="ml-2">Per Juz</span>
                                </label>
                                <label class="inline-flex items-center text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                    <input type="radio" name="type" value="surah" x-model="examType" class="text-indigo-600 focus:ring-indigo-500" />
                                    <span class="ml-2">Per Surah / Ayat</span>
                                </label>
                            </div>
                        </div>

                        <!-- Juz Selector (conditional) -->
                        <div x-show="examType === 'juz'" x-cloak class="bg-indigo-50/50 dark:bg-indigo-950/20 p-3 rounded-lg border border-indigo-100 dark:border-indigo-900/40">
                            <label for="juz" class="block text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase mb-1.5">Pilih Juz *</label>
                            <select id="juz" name="juz" :required="examType === 'juz'" class="block w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm focus:border-indigo-500 focus:ring-indigo-500 transition">
                                <option value="">-- Pilih Juz --</option>
                                @for ($j = 1; $j <= 30; $j++)
                                    <option value="{{ $j }}" @selected(old('juz', $exam->juz) == $j)>
                                        Juz {{ $j }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        <!-- Surah / Ayah Selector (conditional) -->
                        <div x-show="examType === 'surah'" x-cloak class="bg-amber-50/30 dark:bg-amber-950/20 p-3 rounded-lg border border-amber-100 dark:border-amber-900/30 space-y-3">
                            <div>
                                <label for="surah_id" class="block text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase mb-1.5">Pilih Surah *</label>
                                <select id="surah_id" name="surah_id" :required="examType === 'surah'" class="block w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm focus:border-indigo-500 focus:ring-indigo-500 transition">
                                    <option value="">-- Pilih Surah --</option>
                                    @foreach ($surahs as $s)
                                        <option value="{{ $s->id }}" @selected(old('surah_id', $exam->surah_id) == $s->id)>
                                            {{ $s->number }}. {{ $s->name_latin }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label for="ayah_start" class="block text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase mb-1.5">Ayat Mulai *</label>
                                    <input type="number" id="ayah_start" name="ayah_start" :required="examType === 'surah'" min="1" value="{{ old('ayah_start', $exam->ayah_start) }}" class="block w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm focus:border-indigo-500 focus:ring-indigo-500 transition" />
                                </div>
                                <div>
                                    <label for="ayah_end" class="block text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase mb-1.5">Ayat Selesai *</label>
                                    <input type="number" id="ayah_end" name="ayah_end" :required="examType === 'surah'" min="1" value="{{ old('ayah_end', $exam->ayah_end) }}" class="block w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm focus:border-indigo-500 focus:ring-indigo-500 transition" />
                                </div>
                            </div>
                        </div>

                        <!-- Assessment Score -->
                        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4 space-y-3">
                            <h4 class="text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider mb-2">Nilai Ujian Tahfizh (Skala 0-{{ $maxScore }})</h4>

                            @if ($exam->total_score > $maxScore)
                                <p class="text-[10px] text-amber-600 dark:text-amber-400 font-semibold">
                                    Nilai lama ({{ $exam->total_score }}) menggunakan skala rata-rata 5 soal (0-100) sebelum penyederhanaan. Sesuaikan ke skala baru saat menyimpan.
                                </p>
                            @endif

                            <input type="number" id="score" name="score" x-model.number="score" min="0" max="{{ $maxScore }}" step="0.5" required
                                   class="block w-full text-center text-2xl font-black rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white focus:border-indigo-500 focus:ring-indigo-500 transition" />
                            @error('score')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror

                            <!-- Contribution Preview -->
                            <div class="bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200 dark:border-zinc-700 p-3 rounded-xl flex items-center justify-between mt-3">
                                <div>
                                    <div class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase">Kontribusi ke Nilai Akhir Tahfizh</div>
                                    <div class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-0.5">Digabung dengan nilai ketuntasan target hafalan di rapor</div>
                                </div>
                                <div class="text-right">
                                    <span class="text-3xl font-black transition-colors" :class="score >= passThreshold ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'" x-text="score"></span>
                                    <span class="text-xs font-semibold text-zinc-400 dark:text-zinc-500">/ {{ $maxScore }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Notes / Deskripsi Ujian -->
                        <div>
                            <label for="notes" class="block text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase mb-1.5">Catatan Ujian / Deskripsi</label>
                            <textarea id="notes" name="notes" rows="2" placeholder="Tuliskan evaluasi kelancaran bacaan murid..." class="block w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm focus:border-indigo-500 focus:ring-indigo-500 transition placeholder-zinc-400 dark:placeholder-zinc-600">{{ old('notes', $exam->notes) }}</textarea>
                            <p class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-1">Catatan ini akan ditampilkan pada kolom deskripsi laporan rapor digital.</p>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-sm hover:shadow-md uppercase tracking-wider transition-all duration-150">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                    </div>{{-- end .p-6 inner wrapper --}}
                </div>
            </div>
        </div>
    </div>

    <!-- Script and state for interactive features -->
    <script>
        function tahfizhExamApp() {
            return {
                theme: 'sepia',
                page: 1,
                juzSelect: 1,
                surah: 1,
                loading: false,
                verses: [],
                surahList: [],
                playingAudio: null,
                playingVerseId: null,

                // Exam Form state
                examType: '{{ $exam->juz ? "juz" : "surah" }}',
                score: {{ min((float) $exam->total_score, $maxScore) }},
                passThreshold: {{ $passThreshold }},

                initApp() {
                    this.loadSurahList()
                        .then(() => {
                            // Default page to exam surah page or juz page if possible
                            @if ($exam->surah)
                                this.surah = {{ $exam->surah_id }};
                                const selected = this.surahList.find(s => s.id == this.surah);
                                if (selected) {
                                    this.page = selected.page;
                                }
                            @elseif ($exam->juz)
                                this.juzSelect = {{ $exam->juz }};
                                const juzPages = [1, 22, 42, 62, 82, 102, 122, 142, 162, 182, 202, 222, 242, 262, 282, 302, 322, 342, 362, 382, 402, 422, 442, 462, 482, 502, 522, 542, 562, 582];
                                this.page = juzPages[this.juzSelect - 1];
                            @else
                                const lastPage = localStorage.getItem('mushaf-page');
                                this.page = lastPage ? parseInt(lastPage) : 1;
                            @endif

                            this.pageChanged();
                        });
                },

                loadSurahList() {
                    return fetch('https://api.quran.com/api/v4/chapters?language=id')
                        .then(res => res.json())
                        .then(data => {
                            this.surahList = (data.chapters || []).map(c => ({
                                id: c.id,
                                name: c.name_simple,
                                ar: c.name_arabic,
                                page: c.pages[0]
                            }));
                        })
                        .catch(err => console.error("Gagal memuat daftar surah:", err));
                },

                pageChanged() {
                    this.loading = true;
                    localStorage.setItem('mushaf-page', this.page);
                    
                    this.juzSelect = this.getJuzFromPage(this.page);

                    // Fetch Quran.com API v4
                    fetch(`https://api.quran.com/api/v4/verses/by_page/${this.page}?language=id&words=false&translations=33&fields=text_uthmani`)
                        .then(res => {
                            if (!res.ok) throw new Error("Gagal mengambil data ayat");
                            return res.json();
                        })
                        .then(data => {
                            this.verses = data.verses || [];
                            
                            if (this.verses.length > 0) {
                                const firstVerse = this.verses[0];
                                const currentSurahId = parseInt(firstVerse.verse_key.split(':')[0]);
                                this.surah = currentSurahId;
                            }
                            
                            this.loading = false;
                            
                            const el = document.getElementById('verse-list-container');
                            if (el) el.scrollTop = 0;
                        })
                        .catch(err => {
                            console.error(err);
                            this.loading = false;
                            this.verses = [];
                        });
                },

                prevPage() {
                    if (this.page > 1) {
                        this.page--;
                        this.pageChanged();
                    }
                },

                nextPage() {
                    if (this.page < 604) {
                        this.page++;
                        this.pageChanged();
                    }
                },

                pageInputChanged() {
                    let pageNum = parseInt(this.page);
                    if (isNaN(pageNum) || pageNum < 1) pageNum = 1;
                    if (pageNum > 604) pageNum = 604;
                    this.page = pageNum;
                    this.pageChanged();
                },

                surahChanged() {
                    const selected = this.surahList.find(s => s.id == this.surah);
                    if (selected) {
                        this.page = selected.page;
                        this.pageChanged();
                    }
                },

                juzChanged() {
                    const juzPages = [1, 22, 42, 62, 82, 102, 122, 142, 162, 182, 202, 222, 242, 262, 282, 302, 322, 342, 362, 382, 402, 422, 442, 462, 482, 502, 522, 542, 562, 582];
                    const selectedPage = juzPages[this.juzSelect - 1];
                    this.page = selectedPage;
                    this.pageChanged();
                },

                getJuzFromPage(page) {
                    const juzPages = [1, 22, 42, 62, 82, 102, 122, 142, 162, 182, 202, 222, 242, 262, 282, 302, 322, 342, 362, 382, 402, 422, 442, 462, 482, 502, 522, 542, 562, 582];
                    for (let i = 29; i >= 0; i--) {
                        if (page >= juzPages[i]) return i + 1;
                    }
                    return 1;
                },

                getSurahNameAndAyah(key) {
                    if (!key) return '';
                    const parts = key.split(':');
                    const surahNum = parseInt(parts[0]);
                    const ayahNum = parseInt(parts[1]);
                    const surahObj = this.surahList.find(s => s.id === surahNum);
                    const surahName = surahObj ? surahObj.name : 'Surah';
                    return `${surahName}: ${ayahNum}`;
                },

                getIndonesianTranslation(verse) {
                    if (verse.translations && verse.translations.length > 0) {
                        return verse.translations[0].text;
                    }
                    return 'Terjemahan tidak tersedia.';
                }
            };
        }
    </script>

    <style>
        .font-arabic {
            font-family: 'Scheherazade New', 'Amiri', serif;
            line-height: 2.8 !important;
            word-spacing: normal !important;
            letter-spacing: normal !important;
        }
        .scrollbar-thin::-webkit-scrollbar {
            width: 4px;
        }
        .scrollbar-thin::-webkit-scrollbar-track {
            background: transparent;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background-color: rgba(139, 92, 26, 0.2);
            border-radius: 10px;
        }
        [x-cloak] { display: none !important; }
    </style>
</x-app-layout>
