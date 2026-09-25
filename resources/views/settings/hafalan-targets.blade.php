<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-2">
                    <a href="{{ route('settings.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-zinc-400 dark:hover:text-zinc-200 transition">
                        Pengaturan
                    </a>
                    <span class="text-gray-400 dark:text-zinc-600">/</span>
                    <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                        Target Progres Hafalan
                    </h2>
                </div>
                <p class="text-sm text-gray-600 dark:text-zinc-400">
                    Sesuaikan patokan target jumlah juz dan spesifikasi juz per tingkat kelas dan program pembelajaran.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <form method="POST" action="{{ route('settings.hafalan-targets.reset') }}" onsubmit="return confirm('Apakah Anda yakin ingin me-reset target ke standar kurikulum default?');">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-2 border border-gray-300 dark:border-zinc-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-750 transition cursor-pointer shadow-sm">
                        <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reset ke Standar
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="hafalanTargetsForm(@js($config))">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800 dark:bg-emerald-950/40 dark:border-emerald-800/60 dark:text-emerald-300 shadow-sm flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm font-medium text-rose-800 dark:bg-rose-950/40 dark:border-rose-800/60 dark:text-rose-300 shadow-sm">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Aturan target otomatis (dipakai Target Bulanan, Target Triwulan & semua laporan) --}}
            <form method="POST" action="{{ route('settings.target-rules.update') }}" class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm space-y-4"
                  onsubmit="return confirm('Simpan aturan dan hitung ulang semua target otomatis triwulan berjalan?')">
                @csrf
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Aturan Target Otomatis</h3>
                    <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                        Target = pertemuan aktif × baris per pertemuan. Urutan hafalan: Juz 30 sampai juz wajib, lalu murid memilih terus ke belakang
                        atau pindah ke Juz 1 (paling lambat setelah batas pindah). Setelah disimpan, target otomatis triwulan berjalan dihitung ulang;
                        target yang diatur guru tidak berubah.
                    </p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                    @foreach (\App\Support\TargetRules::LEVELS as $level => $label)
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-zinc-300 mb-1">{{ $label }} <span class="font-normal">(baris/pertemuan)</span></label>
                            <input type="number" min="1" max="60" name="level_lines[{{ $level }}]" value="{{ old('level_lines.'.$level, $levelLines[$level]) }}" @disabled(! $canEditTargetRules)
                                   class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm">
                        </div>
                    @endforeach
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-300 mb-1">Juz wajib: 30 sampai</label>
                        <select name="mandatory_until" @disabled(! $canEditTargetRules) class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm">
                            @for ($juz = 30; $juz >= 2; $juz--)
                                <option value="{{ $juz }}" @selected((int) old('mandatory_until', $mandatoryUntil) === $juz)>Juz {{ $juz }}</option>
                            @endfor
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-600 dark:text-zinc-300 mb-1">Batas pindah paling akhir</label>
                        <select name="latest_switch" @disabled(! $canEditTargetRules) class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm">
                            @for ($juz = 30; $juz >= 2; $juz--)
                                <option value="{{ $juz }}" @selected((int) old('latest_switch', $latestSwitch) === $juz)>Setelah Juz {{ $juz }}</option>
                            @endfor
                        </select>
                    </div>
                </div>
                @if ($errors->hasAny(['level_lines.*', 'mandatory_until', 'latest_switch']))
                    <p class="text-xs text-red-600">{{ $errors->first('latest_switch') ?: $errors->first('mandatory_until') ?: $errors->first('level_lines.*') }}</p>
                @endif
                <p class="text-xs text-gray-500">
                    Pilihan murid saat ini: terus ke belakang, atau pindah ke depan setelah
                    {{ collect(\App\Support\TargetRules::switchOptions())->map(fn ($j) => 'Juz '.$j)->implode(', ') }}.
                </p>
                @if ($canEditTargetRules)
                    <div class="flex justify-end">
                        <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold shadow-sm cursor-pointer">Simpan &amp; Hitung Ulang</button>
                    </div>
                @endif
            </form>

            <form method="POST" action="{{ route('settings.hafalan-targets.update') }}" class="space-y-6">
                @csrf

                <!-- Tab Pemilihan Tingkat Kelas -->
                <div class="flex gap-2 p-1.5 bg-gray-100 dark:bg-zinc-800/60 rounded-2xl border border-gray-200 dark:border-zinc-700/60">
                    <button type="button"
                            @click="activeGrade = 'grade_10'"
                            :class="activeGrade === 'grade_10' ? 'bg-white dark:bg-zinc-900 text-gray-900 dark:text-white shadow-sm font-bold' : 'text-gray-600 dark:text-zinc-400 hover:text-gray-900 font-medium'"
                            class="flex-1 py-2.5 px-4 rounded-xl text-sm transition-all duration-150 cursor-pointer flex items-center justify-center gap-2">
                        <x-heroicon-o-academic-cap class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                        <span>Kelas 10 (Fase E)</span>
                    </button>
                    <button type="button"
                            @click="activeGrade = 'grade_11'"
                            :class="activeGrade === 'grade_11' ? 'bg-white dark:bg-zinc-900 text-gray-900 dark:text-white shadow-sm font-bold' : 'text-gray-600 dark:text-zinc-400 hover:text-gray-900 font-medium'"
                            class="flex-1 py-2.5 px-4 rounded-xl text-sm transition-all duration-150 cursor-pointer flex items-center justify-center gap-2">
                        <x-heroicon-o-academic-cap class="w-4 h-4 text-teal-600 dark:text-teal-400" />
                        <span>Kelas 11 (Fase F)</span>
                    </button>
                    <button type="button"
                            @click="activeGrade = 'grade_12'"
                            :class="activeGrade === 'grade_12' ? 'bg-white dark:bg-zinc-900 text-gray-900 dark:text-white shadow-sm font-bold' : 'text-gray-600 dark:text-zinc-400 hover:text-gray-900 font-medium'"
                            class="flex-1 py-2.5 px-4 rounded-xl text-sm transition-all duration-150 cursor-pointer flex items-center justify-center gap-2">
                        <x-heroicon-o-academic-cap class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                        <span>Kelas 12 (Fase F Akhir)</span>
                    </button>
                </div>

                <!-- Card Form Per Grade -->
                <template x-for="gradeKey in ['grade_10', 'grade_11', 'grade_12']" :key="gradeKey">
                    <div x-show="activeGrade === gradeKey" class="space-y-6">

                        <!-- SECTION 1: PROGRAM TAHFIZH -->
                        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-sm overflow-hidden transition">
                            <div class="border-b border-gray-200 dark:border-zinc-800 px-6 py-4 bg-emerald-50/50 dark:bg-emerald-950/20 flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-300">
                                            Tahfizh
                                        </span>
                                        <h3 class="text-base font-bold text-gray-900 dark:text-white" x-text="getGradeLabel(gradeKey) + ' - Program Tahfizh'"></h3>
                                    </div>
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-zinc-400">
                                        Pengaturan target hafalan untuk rombel/murid peminatan program Tahfizh intensif.
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-semibold text-gray-500 dark:text-zinc-400">Total Target:</span>
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-600 text-white text-xs font-bold" x-text="targets[gradeKey].tahfizh.target_juz_count + ' Juz'"></span>
                                </div>
                            </div>

                            <div class="p-6 space-y-5">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <!-- Jumlah Target Juz -->
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-zinc-300">
                                            Jumlah Target Juz
                                        </label>
                                        <div class="relative">
                                            <input type="number"
                                                   min="1"
                                                   max="30"
                                                   :name="'targets[' + gradeKey + '][tahfizh][target_juz_count]'"
                                                   x-model.number="targets[gradeKey].tahfizh.target_juz_count"
                                                   class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-850 dark:text-white shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm font-bold px-3 py-2"
                                                   required />
                                             <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-xs font-bold text-gray-400">
                                                Juz
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Mode Penentuan Juz -->
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-zinc-300">
                                            Metode Penentuan Juz
                                        </label>
                                        <select :name="'targets[' + gradeKey + '][tahfizh][mode]'"
                                                x-model="targets[gradeKey].tahfizh.mode"
                                                class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-850 dark:text-white shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm font-semibold px-3 py-2">
                                            <option value="specific">Spesifik (Wajib Juz Tertentu)</option>
                                            <option value="any">Bebas (Menghitung Kuota N Juz Acak)</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Checkbox Pilihan Juz Spesifik -->
                                <div x-show="targets[gradeKey].tahfizh.mode === 'specific'" class="space-y-3 pt-2 border-t border-gray-150 dark:border-zinc-800">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-zinc-300">
                                            Pilih Daftar Juz Wajib:
                                        </label>
                                        <div class="flex items-center gap-1.5">
                                            <button type="button" @click="setSpecificJuz(gradeKey, 'tahfizh', [30, 29, 28, 1])" class="px-2 py-1 bg-gray-100 dark:bg-zinc-800 hover:bg-gray-200 dark:hover:bg-zinc-700 text-gray-700 dark:text-zinc-300 rounded text-[11px] font-semibold transition cursor-pointer">
                                                Set 30, 29, 28, 1
                                            </button>
                                            <button type="button" @click="selectAllJuz(gradeKey, 'tahfizh')" class="px-2 py-1 bg-gray-100 dark:bg-zinc-800 hover:bg-gray-200 dark:hover:bg-zinc-700 text-gray-700 dark:text-zinc-300 rounded text-[11px] font-semibold transition cursor-pointer">
                                                Semua
                                            </button>
                                            <button type="button" @click="clearJuz(gradeKey, 'tahfizh')" class="px-2 py-1 bg-gray-100 dark:bg-zinc-800 hover:bg-gray-200 dark:hover:bg-zinc-700 text-gray-700 dark:text-zinc-300 rounded text-[11px] font-semibold transition cursor-pointer">
                                                Bersihkan
                                            </button>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-5 sm:grid-cols-6 md:grid-cols-10 gap-1.5 p-3 bg-gray-50/70 dark:bg-zinc-850/60 rounded-xl border border-gray-200 dark:border-zinc-800">
                                        <template x-for="juzNum in 30" :key="juzNum">
                                            <label :class="isJuzSelected(gradeKey, 'tahfizh', juzNum) ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'bg-white dark:bg-zinc-900 text-gray-700 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-800'"
                                                   class="flex flex-col items-center justify-center p-2 rounded-lg border border-gray-200 dark:border-zinc-750 text-xs cursor-pointer transition select-none">
                                                <input type="checkbox"
                                                       :name="'targets[' + gradeKey + '][tahfizh][specific_juz][]'"
                                                       :value="juzNum"
                                                       :checked="isJuzSelected(gradeKey, 'tahfizh', juzNum)"
                                                       @change="toggleJuz(gradeKey, 'tahfizh', juzNum)"
                                                       class="sr-only" />
                                                <span class="text-[10px] text-opacity-80 uppercase tracking-tighter">Juz</span>
                                                <span class="text-sm font-bold" x-text="juzNum"></span>
                                            </label>
                                        </template>
                                    </div>

                                    <p class="text-xs text-gray-500 dark:text-zinc-400">
                                        * Murid akan dihitung persentase progresnya khusus dari capaian hafalan pada juz yang dipilih di atas.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION 2: PROGRAM REGULER -->
                        <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-sm overflow-hidden transition">
                            <div class="border-b border-gray-200 dark:border-zinc-800 px-6 py-4 bg-sky-50/50 dark:bg-sky-950/20 flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-sky-100 dark:bg-sky-900/50 text-sky-800 dark:text-sky-300">
                                            Reguler
                                        </span>
                                        <h3 class="text-base font-bold text-gray-900 dark:text-white" x-text="getGradeLabel(gradeKey) + ' - Program Reguler'"></h3>
                                    </div>
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-zinc-400">
                                        Pengaturan target hafalan untuk kelas/murid program reguler standar.
                                    </p>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-semibold text-gray-500 dark:text-zinc-400">Total Target:</span>
                                    <span class="px-2.5 py-1 rounded-lg bg-sky-600 text-white text-xs font-bold" x-text="targets[gradeKey].reguler.target_juz_count + ' Juz'"></span>
                                </div>
                            </div>

                            <div class="p-6 space-y-5">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <!-- Jumlah Target Juz -->
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-zinc-300">
                                            Jumlah Target Juz
                                        </label>
                                        <div class="relative">
                                            <input type="number"
                                                   min="1"
                                                   max="30"
                                                   :name="'targets[' + gradeKey + '][reguler][target_juz_count]'"
                                                   x-model.number="targets[gradeKey].reguler.target_juz_count"
                                                   class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-850 dark:text-white shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm font-bold px-3 py-2"
                                                   required />
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-xs font-bold text-gray-400">
                                                Juz
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Mode Penentuan Juz -->
                                    <div class="space-y-1.5">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-zinc-300">
                                            Metode Penentuan Juz
                                        </label>
                                        <select :name="'targets[' + gradeKey + '][reguler][mode]'"
                                                x-model="targets[gradeKey].reguler.mode"
                                                class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-850 dark:text-white shadow-sm focus:border-sky-500 focus:ring-sky-500 text-sm font-semibold px-3 py-2">
                                            <option value="specific">Spesifik (Wajib Juz Tertentu)</option>
                                            <option value="any">Bebas (Menghitung Kuota N Juz Acak)</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Checkbox Pilihan Juz Spesifik -->
                                <div x-show="targets[gradeKey].reguler.mode === 'specific'" class="space-y-3 pt-2 border-t border-gray-150 dark:border-zinc-800">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-zinc-300">
                                            Pilih Daftar Juz Wajib:
                                        </label>
                                        <div class="flex items-center gap-1.5">
                                            <button type="button" @click="setSpecificJuz(gradeKey, 'reguler', [30, 29])" class="px-2 py-1 bg-gray-100 dark:bg-zinc-800 hover:bg-gray-200 dark:hover:bg-zinc-700 text-gray-700 dark:text-zinc-300 rounded text-[11px] font-semibold transition cursor-pointer">
                                                Set 30, 29
                                            </button>
                                            <button type="button" @click="selectAllJuz(gradeKey, 'reguler')" class="px-2 py-1 bg-gray-100 dark:bg-zinc-800 hover:bg-gray-200 dark:hover:bg-zinc-700 text-gray-700 dark:text-zinc-300 rounded text-[11px] font-semibold transition cursor-pointer">
                                                Semua
                                            </button>
                                            <button type="button" @click="clearJuz(gradeKey, 'reguler')" class="px-2 py-1 bg-gray-100 dark:bg-zinc-800 hover:bg-gray-200 dark:hover:bg-zinc-700 text-gray-700 dark:text-zinc-300 rounded text-[11px] font-semibold transition cursor-pointer">
                                                Bersihkan
                                            </button>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-5 sm:grid-cols-6 md:grid-cols-10 gap-1.5 p-3 bg-gray-50/70 dark:bg-zinc-855/60 rounded-xl border border-gray-200 dark:border-zinc-800">
                                        <template x-for="juzNum in 30" :key="juzNum">
                                            <label :class="isJuzSelected(gradeKey, 'reguler', juzNum) ? 'bg-sky-600 text-white font-bold shadow-sm' : 'bg-white dark:bg-zinc-900 text-gray-700 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-800'"
                                                   class="flex flex-col items-center justify-center p-2 rounded-lg border border-gray-200 dark:border-zinc-750 text-xs cursor-pointer transition select-none">
                                                <input type="checkbox"
                                                       :name="'targets[' + gradeKey + '][reguler][specific_juz][]'"
                                                       :value="juzNum"
                                                       :checked="isJuzSelected(gradeKey, 'reguler', juzNum)"
                                                       @change="toggleJuz(gradeKey, 'reguler', juzNum)"
                                                       class="sr-only" />
                                                <span class="text-[10px] text-opacity-80 uppercase tracking-tighter">Juz</span>
                                                <span class="text-sm font-bold" x-text="juzNum"></span>
                                            </label>
                                        </template>
                                    </div>

                                    <p class="text-xs text-gray-500 dark:text-zinc-400">
                                        * Murid akan dihitung persentase progresnya khusus dari capaian hafalan pada juz yang dipilih di atas.
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>
                </template>

                <!-- Action Button -->
                <div class="flex items-center justify-end gap-3 pt-4">
                    <a href="{{ route('settings.index') }}" class="px-5 py-2.5 rounded-xl border border-gray-300 dark:border-zinc-700 text-sm font-semibold text-gray-700 dark:text-zinc-300 hover:bg-gray-50 dark:hover:bg-zinc-800 transition">
                        Batal
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-bold transition cursor-pointer shadow-sm flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Simpan Pengaturan Target
                    </button>
                </div>

            </form>
        </div>
    </div>

    <script>
        function hafalanTargetsForm(initialConfig) {
            return {
                activeGrade: 'grade_10',
                targets: initialConfig,

                getGradeLabel(gradeKey) {
                    const labels = {
                        'grade_10': 'Kelas 10 (Fase E)',
                        'grade_11': 'Kelas 11 (Fase F)',
                        'grade_12': 'Kelas 12 (Fase F Akhir)',
                    };
                    return labels[gradeKey] || gradeKey;
                },

                isJuzSelected(gradeKey, progKey, juzNum) {
                    const list = this.targets[gradeKey]?.[progKey]?.specific_juz || [];
                    return list.includes(juzNum);
                },

                toggleJuz(gradeKey, progKey, juzNum) {
                    if (!this.targets[gradeKey][progKey].specific_juz) {
                        this.targets[gradeKey][progKey].specific_juz = [];
                    }
                    const idx = this.targets[gradeKey][progKey].specific_juz.indexOf(juzNum);
                    if (idx > -1) {
                        this.targets[gradeKey][progKey].specific_juz.splice(idx, 1);
                    } else {
                        this.targets[gradeKey][progKey].specific_juz.push(juzNum);
                        this.targets[gradeKey][progKey].specific_juz.sort((a, b) => a - b);
                    }
                    // Auto-sync target_juz_count if in specific mode
                    if (this.targets[gradeKey][progKey].mode === 'specific' && this.targets[gradeKey][progKey].specific_juz.length > 0) {
                        this.targets[gradeKey][progKey].target_juz_count = this.targets[gradeKey][progKey].specific_juz.length;
                    }
                },

                setSpecificJuz(gradeKey, progKey, juzArray) {
                    this.targets[gradeKey][progKey].specific_juz = [...juzArray];
                    this.targets[gradeKey][progKey].target_juz_count = juzArray.length;
                },

                selectAllJuz(gradeKey, progKey) {
                    const all = [];
                    for (let i = 1; i <= 30; i++) all.push(i);
                    this.targets[gradeKey][progKey].specific_juz = all;
                    this.targets[gradeKey][progKey].target_juz_count = 30;
                },

                clearJuz(gradeKey, progKey) {
                    this.targets[gradeKey][progKey].specific_juz = [];
                }
            };
        }
    </script>
</x-app-layout>
