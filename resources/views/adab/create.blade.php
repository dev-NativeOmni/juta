<x-app-layout>
    <x-slot name="header">
        <h2 class="font-bold text-lg sm:text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
            {{ __('Kuisioner Harian Adab & Akhlak') }}
        </h2>
    </x-slot>

    <div class="py-3 sm:py-6">
        <div class="max-w-4xl mx-auto space-y-3 sm:space-y-6">

            {{-- Profil Singkat Murid --}}
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-4 sm:p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-3">
                <div>
                    <span class="text-[10px] sm:text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider block mb-0.5">Pengisian Mandiri Murid</span>
                    <h3 class="text-lg sm:text-xl font-bold text-zinc-900 dark:text-white">{{ $student->name }}</h3>
                    <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                        Kelas: {{ $student->classRoom?->name ?: '-' }} | NIS: {{ $student->student_number ?: '-' }}
                    </p>
                </div>
                <a href="{{ route('adab.show', $student) }}" class="inline-flex items-center justify-center px-3.5 py-2 border border-zinc-300 dark:border-zinc-700 text-xs sm:text-sm font-semibold text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition duration-150 min-h-[36px] active:scale-95 w-full md:w-auto">
                    Kembali ke Riwayat
                </a>
            </div>

            @php
                $totalQuestionsCount = 0;
                foreach ($categories as $cat) {
                    $totalQuestionsCount += count($cat['questions'] ?? []);
                }
                $questionCounter = 0;
            @endphp

            {{-- Form Penilaian --}}
            <form method="POST" action="{{ route('adab.store', $student) }}" class="space-y-3 sm:space-y-6" id="adabForm">
                @csrf

                {{-- Header: Tanggal & Status Pengisian --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-6">
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-4 sm:p-6 flex flex-col justify-between">
                        <div>
                            <span class="block text-[10px] sm:text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-1.5">Tanggal Pengisian</span>
                            <div class="text-sm sm:text-base font-bold text-zinc-800 dark:text-zinc-200 bg-zinc-50 dark:bg-zinc-800/60 p-2.5 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}
                            </div>
                        </div>
                        <div class="mt-3 sm:mt-6 p-3 sm:p-4 bg-zinc-50 dark:bg-zinc-800/40 rounded-lg text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed border border-zinc-200 dark:border-zinc-800">
                            <strong>Petunjuk:</strong> Jawab semua pertanyaan dengan jujur. Pengisian kuisioner harian ini mencatat kehadiran dan keaktifan Adab Anda untuk hari ini.
                        </div>
                    </div>

                    {{-- Live Status Widget --}}
                    <div class="bg-gradient-to-br from-indigo-900 to-purple-900 text-white rounded-xl shadow-lg p-4 sm:p-6 md:col-span-2 flex flex-col justify-between relative overflow-hidden">
                        <div class="absolute -right-6 -bottom-6 opacity-10 pointer-events-none">
                            <svg class="h-32 sm:h-40 w-32 sm:w-40" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H7c0-2.76 2.24-5 5-5s5 2.24 5 5c0 1.04-.42 1.99-1.07 2.75z"/>
                            </svg>
                        </div>
                        <div class="relative z-10">
                            <h4 class="text-xs sm:text-sm font-semibold uppercase text-indigo-200 tracking-wider">Status Pengisian Hari Ini</h4>
                            <div class="flex items-baseline gap-2 sm:gap-3 mt-2 sm:mt-4">
                                <span class="text-4xl sm:text-6xl font-black tracking-tight" id="liveScore">0</span>
                                <div class="flex flex-col">
                                    <span class="text-sm sm:text-xl text-indigo-300">/ {{ $totalQuestionsCount }} Pertanyaan</span>
                                    <span class="text-lg sm:text-2xl font-black text-amber-300 leading-none mt-0.5" id="liveGrade">Belum Lengkap</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 sm:mt-6 relative z-10">
                            <div class="w-full bg-indigo-950/50 rounded-full h-2.5 sm:h-3 border border-indigo-800">
                                <div class="bg-gradient-to-r from-emerald-400 to-teal-400 h-full rounded-full transition-all duration-300" id="progressBar" style="width: 0%"></div>
                            </div>
                            <div class="flex justify-between items-center text-[11px] sm:text-xs text-indigo-200 mt-1.5">
                                <span id="scoreCategory">Status: Belum Lengkap</span>
                                <span id="filledCount">0 dari {{ $totalQuestionsCount }} pertanyaan terjawab</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Soal Kuisioner per Kategori --}}
                @foreach ($categories as $catIdx => $category)
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-4 sm:p-6 space-y-4">
                        <div>
                            <h3 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white">{{ $category['title'] }}</h3>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">{{ $category['desc'] }}</p>
                        </div>

                        <div class="space-y-3 sm:space-y-4 divide-y divide-zinc-100 dark:divide-zinc-800/80">
                            @foreach ($category['questions'] as $qIdx => $questionText)
                                @php
                                    $questionCounter++;
                                    $inputName = "cat_{$catIdx}_q{$qIdx}";
                                @endphp
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between pt-3 first:pt-0 gap-3">
                                    <div class="flex items-start gap-2.5 flex-1">
                                        <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-indigo-50 dark:bg-indigo-950/40 text-[10px] font-bold text-indigo-600 dark:text-indigo-400 mt-0.5 shrink-0">
                                            {{ $questionCounter }}
                                        </span>
                                        <p class="text-xs sm:text-sm font-semibold text-zinc-800 dark:text-zinc-200 leading-relaxed">{{ $questionText }}</p>
                                    </div>

                                    <div class="flex items-center gap-2 self-stretch sm:self-center shrink-0">
                                        <label class="flex-1 sm:flex-none flex items-center justify-center gap-1 px-4 py-2 sm:py-1.5 border rounded-lg cursor-pointer text-xs font-bold transition select-none border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:border-emerald-300 dark:hover:border-emerald-700 min-h-[38px] active:scale-95" data-label-type="ya">
                                            <input type="radio" name="{{ $inputName }}" value="1" class="sr-only adab-radio" data-input="{{ $inputName }}" required>
                                            <x-heroicon-o-check class="w-3.5 h-3.5 stroke-[2.5]" />
                                            <span>Ya</span>
                                        </label>
                                        <label class="flex-1 sm:flex-none flex items-center justify-center gap-1 px-4 py-2 sm:py-1.5 border rounded-lg cursor-pointer text-xs font-bold transition select-none border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:border-rose-300 dark:hover:border-rose-700 min-h-[38px] active:scale-95" data-label-type="tidak">
                                            <input type="radio" name="{{ $inputName }}" value="0" class="sr-only adab-radio" data-input="{{ $inputName }}" required>
                                            <x-heroicon-o-x-mark class="w-3.5 h-3.5 stroke-[2.5]" />
                                            <span>Tidak</span>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                {{-- Catatan --}}
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-4 sm:p-6">
                    <label for="notes" class="block text-[10px] sm:text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-2">Catatan / Refleksi Diri (Opsional)</label>
                    <textarea
                        name="notes"
                        id="notes"
                        rows="3"
                        placeholder="Tuliskan refleksi singkat Anda hari ini..."
                        class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-xs sm:text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600"
                    ></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('adab.show', $student) }}" class="flex-1 sm:flex-none inline-flex items-center justify-center px-5 py-3 border border-zinc-300 dark:border-zinc-700 text-xs sm:text-sm font-semibold text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-700 transition duration-150 active:scale-95 min-h-[44px]">
                        Batal
                    </a>
                    <button type="submit" class="flex-1 sm:flex-none inline-flex items-center justify-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs sm:text-sm font-bold shadow-md hover:shadow-lg transition duration-150 active:scale-95 min-h-[44px]">
                        Kirim Jawaban Kuisioner
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const radios        = document.querySelectorAll('.adab-radio');
            const liveScoreEl   = document.getElementById('liveScore');
            const liveGradeEl   = document.getElementById('liveGrade');
            const progressBar   = document.getElementById('progressBar');
            const catEl         = document.getElementById('scoreCategory');
            const filledCountEl = document.getElementById('filledCount');
            const totalQ        = {{ $totalQuestionsCount }};

            function updateCalc() {
                if (!liveScoreEl || !progressBar || !filledCountEl) return;
                const answered = new Set();
                let yesCount   = 0;

                radios.forEach(radio => {
                    const label     = radio.closest('label');
                    const labelType = label.getAttribute('data-label-type');

                    if (radio.checked) {
                        answered.add(radio.getAttribute('data-input'));
                        if (radio.value === '1') yesCount++;

                        label.className = labelType === 'ya'
                            ? 'flex-1 sm:flex-none flex items-center justify-center px-4 py-2 sm:py-1.5 border rounded-lg cursor-pointer text-xs font-bold transition select-none bg-emerald-500 border-emerald-500 text-white ring-4 ring-emerald-500/20 min-h-[38px] active:scale-95'
                            : 'flex-1 sm:flex-none flex items-center justify-center px-4 py-2 sm:py-1.5 border rounded-lg cursor-pointer text-xs font-bold transition select-none bg-zinc-500 border-zinc-500 text-white ring-4 ring-zinc-500/20 min-h-[38px] active:scale-95';
                    } else {
                        label.className = labelType === 'ya'
                            ? 'flex-1 sm:flex-none flex items-center justify-center px-4 py-2 sm:py-1.5 border rounded-lg cursor-pointer text-xs font-bold transition select-none border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:border-emerald-300 dark:hover:border-emerald-700 min-h-[38px] active:scale-95'
                            : 'flex-1 sm:flex-none flex items-center justify-center px-4 py-2 sm:py-1.5 border rounded-lg cursor-pointer text-xs font-bold transition select-none border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:border-rose-300 dark:hover:border-rose-700 min-h-[38px] active:scale-95';
                    }
                });

                const isComplete  = totalQ > 0 && answered.size === totalQ;
                const progressPct = totalQ > 0 ? Math.round((answered.size / totalQ) * 100) : 0;

                liveScoreEl.textContent = answered.size;
                if (isComplete) {
                    liveGradeEl.textContent = 'Lengkap';
                    liveGradeEl.className   = 'text-lg sm:text-2xl font-black text-emerald-400 leading-none mt-0.5';
                } else {
                    liveGradeEl.textContent = 'Belum Lengkap';
                    liveGradeEl.className   = 'text-lg sm:text-2xl font-black text-amber-300 leading-none mt-0.5';
                }
                progressBar.style.width = progressPct + '%';
                filledCountEl.textContent = `${answered.size} dari ${totalQ} pertanyaan terjawab`;
                catEl.textContent = isComplete ? 'Status: Kuisioner Siap Dikirim' : 'Status: Belum Lengkap';
            }

            radios.forEach(r => r.addEventListener('change', updateCalc));
            updateCalc();
        });
    </script>
</x-app-layout>
