<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-bold text-xl sm:text-2xl text-gray-900 dark:text-white leading-tight flex items-center gap-2">
                    <x-heroicon-o-bolt class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                    <span>Input Murajaah Cepat per-Halaqah</span>
                </h2>
                <p class="text-xs sm:text-sm text-gray-600 dark:text-zinc-400">
                    Input data murajaah harian murid secara efisien dalam 1 layar per-kelas.
                </p>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('murajaah-records.index') }}"
                   class="inline-flex items-center gap-1.5 justify-center px-3.5 py-2 bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 rounded-xl font-bold text-xs hover:bg-gray-200 dark:hover:bg-zinc-700 transition">
                    <x-heroicon-o-arrow-left class="w-3.5 h-3.5" />
                    <span>Kembali ke List</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div x-data="fastMurajaahManager()" x-init="init()" class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            <!-- UNIFIED INPUT HUB TABS -->
            @include('partials.tahfizh-input-nav-tabs', ['activeTab' => 'murajaah'])

            <!-- Filter Bar & Action Header -->
            <div class="bg-white dark:bg-zinc-900 p-4 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-800 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400 mb-1">Pilih Kelas / Halaqah</label>
                        <select x-model="selectedClassId" @change="changeClass()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-xs font-semibold px-3 py-2">
                            @foreach ($classRooms as $class)
                                <option value="{{ $class->id }}" {{ $selectedClassId == $class->id ? 'selected' : '' }}>
                                    {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400 mb-1">Tanggal Penilaian</label>
                        <input type="date" x-model="reviewedAt" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-xs font-semibold px-3 py-2">
                    </div>
                </div>

                <div class="flex items-center gap-3 justify-between md:justify-end border-t md:border-t-0 pt-3 md:pt-0 border-gray-100 dark:border-zinc-800">
                    <div class="text-xs font-semibold text-gray-600 dark:text-zinc-400">
                        Terisi: <strong class="text-indigo-600 dark:text-indigo-400 text-sm" x-text="filledCount">0</strong> / <span x-text="rows.length">0</span> Murid
                    </div>

                    <button
                        type="button"
                        @click="submitAll()"
                        :disabled="isSubmitting || filledCount === 0"
                        :class="filledCount > 0 ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-md cursor-pointer' : 'bg-gray-200 dark:bg-zinc-800 text-gray-400 dark:text-zinc-600 cursor-not-allowed'"
                        class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all"
                    >
                        <span x-show="!isSubmitting" class="inline-flex items-center gap-1.5"><x-heroicon-o-check class="w-4 h-4" /> Simpan Semua (<span x-text="filledCount">0</span>)</span>
                        <span x-show="isSubmitting" x-cloak>Menyimpan...</span>
                    </button>
                </div>
            </div>

            <!-- Main Table Grid -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800">
                        <thead>
                            <tr class="bg-gray-50/80 dark:bg-zinc-800/50 text-left text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                                <th class="px-3.5 py-3 min-w-[180px]">Murid</th>
                                <th class="px-3.5 py-3 min-w-[150px]">Riwayat Terakhir</th>
                                <th class="px-3.5 py-3 min-w-[160px]">Surah Awal</th>
                                <th class="px-3.5 py-3 min-w-[160px]">Surah Akhir</th>
                                <th class="px-3.5 py-3 min-w-[140px]">Ayat (Awal - Akhir)</th>
                                <th class="px-3.5 py-3 min-w-[250px]">Penilaian Cepat</th>
                                <th class="px-3.5 py-3 min-w-[140px]">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800/80 text-xs">
                            <template x-for="(row, index) in rows" :key="row.student_id">
                                <tr :class="row.score ? 'bg-indigo-50/30 dark:bg-indigo-950/20' : 'hover:bg-gray-50/60 dark:hover:bg-zinc-800/40'" class="transition-colors">
                                    
                                    <!-- Student Column -->
                                    <td class="px-3.5 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-bold flex items-center justify-center text-xs flex-shrink-0" x-text="row.student_name.substring(0, 2).toUpperCase()"></div>
                                            <div>
                                                <div class="font-bold text-gray-900 dark:text-white" x-text="row.student_name"></div>
                                                <div class="text-[10px] text-gray-400 dark:text-zinc-500" x-text="row.class_name"></div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Last History Badge -->
                                    <td class="px-3.5 py-3 whitespace-nowrap">
                                        <template x-if="row.last_history">
                                            <div class="inline-flex flex-col px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700/60">
                                                <span class="font-bold text-gray-800 dark:text-zinc-200 text-[11px]" x-text="row.last_history.surah_name + ' (' + row.last_history.ayah_start + '-' + row.last_history.ayah_end + ')'"></span>
                                                <span class="text-[9px] text-gray-400 dark:text-zinc-500" x-text="row.last_history.date"></span>
                                            </div>
                                        </template>
                                        <template x-if="!row.last_history">
                                            <span class="text-gray-400 dark:text-zinc-500 text-[11px] italic">Belum ada riwayat</span>
                                        </template>
                                    </td>

                                    <!-- Surah Awal Selection -->
                                    <td class="px-3.5 py-3">
                                        <select
                                            x-model="row.surah_id"
                                            @change="onSurahStartChange(row)"
                                            class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-xs font-semibold py-1.5 focus:ring-indigo-500"
                                        >
                                            <option value="">-- Surah Awal --</option>
                                            <template x-for="surah in surahs" :key="surah.id">
                                                <option :value="surah.id" x-text="surah.number + '. ' + surah.name_latin + ' (' + surah.total_ayah + ')'"></option>
                                            </template>
                                        </select>
                                    </td>

                                    <!-- Surah Akhir Selection -->
                                    <td class="px-3.5 py-3">
                                        <select
                                            x-model="row.surah_end_id"
                                            @change="onSurahEndChange(row)"
                                            class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-xs font-semibold py-1.5 focus:ring-indigo-500"
                                        >
                                            <option value="">-- Surah Akhir --</option>
                                            <template x-for="surah in surahs" :key="surah.id">
                                                <option :value="surah.id" x-text="surah.number + '. ' + surah.name_latin + ' (' + surah.total_ayah + ')'"></option>
                                            </template>
                                        </select>
                                    </td>

                                    <!-- Ayah Range Inputs -->
                                    <td class="px-3.5 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5">
                                            <input
                                                type="number"
                                                min="1"
                                                x-model="row.ayah_start"
                                                placeholder="Awal"
                                                class="w-16 rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-xs font-semibold px-2 py-1 text-center"
                                            >
                                            <span class="text-gray-400 font-bold">-</span>
                                            <input
                                                type="number"
                                                min="1"
                                                x-model="row.ayah_end"
                                                placeholder="Akhir"
                                                class="w-16 rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-xs font-semibold px-2 py-1 text-center"
                                            >
                                        </div>
                                    </td>

                                    <!-- Quick Score Pills -->
                                    <td class="px-3.5 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5">
                                            <!-- 90 Score -->
                                            <button
                                                type="button"
                                                @click="setScore(row, 90)"
                                                :class="row.score == 90 ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 border-gray-200 dark:border-zinc-700 hover:bg-gray-200 dark:hover:bg-zinc-700'"
                                                class="px-2.5 py-1 rounded-lg text-[11px] font-bold border transition-all cursor-pointer flex items-center gap-1"
                                            >
                                                <x-heroicon-o-trophy class="w-3.5 h-3.5 shrink-0" />
                                                <span>90</span>
                                                <span class="text-[9px] opacity-80 hidden sm:inline">Lancar</span>
                                            </button>

                                            <!-- 80 Score -->
                                            <button
                                                type="button"
                                                @click="setScore(row, 80)"
                                                :class="row.score == 80 ? 'bg-amber-600 text-white border-amber-600 shadow-sm' : 'bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 border-gray-200 dark:border-zinc-700 hover:bg-gray-200 dark:hover:bg-zinc-700'"
                                                class="px-2.5 py-1 rounded-lg text-[11px] font-bold border transition-all cursor-pointer flex items-center gap-1"
                                            >
                                                <x-heroicon-o-star class="w-3.5 h-3.5 shrink-0" />
                                                <span>80</span>
                                                <span class="text-[9px] opacity-80 hidden sm:inline">Cukup</span>
                                            </button>

                                            <!-- 70 Score -->
                                            <button
                                                type="button"
                                                @click="setScore(row, 70)"
                                                :class="row.score == 70 ? 'bg-rose-600 text-white border-rose-600 shadow-sm' : 'bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 border-gray-200 dark:border-zinc-700 hover:bg-gray-200 dark:hover:bg-zinc-700'"
                                                class="px-2.5 py-1 rounded-lg text-[11px] font-bold border transition-all cursor-pointer flex items-center gap-1"
                                            >
                                                <x-heroicon-o-arrow-path class="w-3.5 h-3.5 shrink-0" />
                                                <span>70</span>
                                                <span class="text-[9px] opacity-80 hidden sm:inline">Ulang</span>
                                            </button>

                                            <!-- Custom Score Input Box -->
                                            <input
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.1"
                                                x-model="row.score"
                                                placeholder="Skor"
                                                class="w-14 rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-xs font-bold px-1.5 py-1 text-center"
                                            >
                                        </div>
                                    </td>

                                    <!-- Notes Column -->
                                    <td class="px-3.5 py-3">
                                        <input
                                            type="text"
                                            x-model="row.notes"
                                            placeholder="Catatan..."
                                            class="w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-xs px-2.5 py-1"
                                        >
                                    </td>

                                </tr>
                            </template>

                            <template x-if="rows.length === 0">
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center text-gray-400 dark:text-zinc-500">
                                        Belum ada data murid aktif pada kelas ini.
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script>
    function fastMurajaahManager() {
        return {
            selectedClassId: {{ $selectedClassId }},
            reviewedAt: '{{ now()->toDateString() }}',
            students: @json($students),
            latestRecords: @json($latestRecords),
            surahs: @json($surahs),
            surahMap: {},
            rows: [],
            isSubmitting: false,

            get filledCount() {
                return this.rows.filter(r => r.surah_id && r.score).length;
            },

            changeClass() {
                window.location.href = '{{ route('murajaah-records.fast-input') }}?class_room_id=' + this.selectedClassId;
            },

            onSurahStartChange(row) {
                if (!row.surah_id) {
                    row.surah_end_id = '';
                    return;
                }
                const surahStart = this.surahMap[row.surah_id];
                const surahEnd = this.surahMap[row.surah_end_id];

                if (!row.surah_end_id || (surahEnd && surahEnd.number < surahStart.number)) {
                    row.surah_end_id = row.surah_id;
                }

                const currentEndSurah = this.surahMap[row.surah_end_id] || surahStart;
                row.ayah_start = 1;
                row.ayah_end = currentEndSurah ? currentEndSurah.total_ayah : 1;
            },

            onSurahEndChange(row) {
                if (!row.surah_end_id) {
                    if (row.surah_id) {
                        row.surah_end_id = row.surah_id;
                    }
                }
                const surahStart = this.surahMap[row.surah_id];
                const surahEnd = this.surahMap[row.surah_end_id];

                if (surahStart && surahEnd && surahEnd.number < surahStart.number) {
                    alert('Surah akhir tidak boleh mendahului surah awal.');
                    row.surah_end_id = row.surah_id;
                }

                const currentEndSurah = this.surahMap[row.surah_end_id];
                if (currentEndSurah) {
                    row.ayah_end = currentEndSurah.total_ayah;
                }
            },

            setScore(row, val) {
                if (!row.surah_id) {
                    if (row.default_surah_id) {
                        row.surah_id = row.default_surah_id;
                        row.surah_end_id = row.default_surah_id;
                        this.onSurahStartChange(row);
                    } else {
                        alert('Silakan pilih Surah terlebih dahulu untuk murid ' + row.student_name + '.');
                        return;
                    }
                }
                if (!row.surah_end_id) {
                    row.surah_end_id = row.surah_id;
                }
                row.score = val;
            },

            submitAll() {
                const invalidRow = this.rows.find(r => r.score && !r.surah_id);
                if (invalidRow) {
                    alert('Harap pilih Surah terlebih dahulu untuk murid "' + invalidRow.student_name + '".');
                    return;
                }

                const invalidSequenceRow = this.rows.find(r => {
                    if (r.surah_id && r.surah_end_id && r.score) {
                        const s1 = this.surahMap[r.surah_id];
                        const s2 = this.surahMap[r.surah_end_id];
                        return s1 && s2 && s2.number < s1.number;
                    }
                    return false;
                });

                if (invalidSequenceRow) {
                    alert('Surah akhir tidak boleh mendahului Surah awal untuk murid "' + invalidSequenceRow.student_name + '".');
                    return;
                }

                const filledEntries = this.rows.filter(r => r.surah_id && r.score).map(r => ({
                    student_id: r.student_id,
                    surah_id: parseInt(r.surah_id),
                    surah_end_id: parseInt(r.surah_end_id || r.surah_id),
                    ayah_start: parseInt(r.ayah_start) || 1,
                    ayah_end: parseInt(r.ayah_end) || 1,
                    score: parseFloat(r.score),
                    notes: r.notes || ''
                }));

                if (filledEntries.length === 0) {
                    alert('Belum ada data murajaah yang terisi. Pilih Surah dan Nilai untuk minimal 1 murid.');
                    return;
                }

                this.isSubmitting = true;
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                fetch('{{ route('murajaah-records.fast-store') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        reviewed_at: this.reviewedAt,
                        entries: filledEntries
                    })
                })
                .then(async r => {
                    this.isSubmitting = false;
                    const res = await r.json().catch(() => ({}));
                    if (r.ok && res.success) {
                        alert(res.message || 'Data murajaah berhasil disimpan.');
                        window.location.href = '{{ route('murajaah-records.index') }}';
                    } else {
                        const errMsg = res.message || (res.errors ? Object.values(res.errors).flat().join('\n') : 'Gagal menyimpan data pada server.');
                        alert(errMsg);
                    }
                })
                .catch(err => {
                    this.isSubmitting = false;
                    alert('Terjadi kesalahan koneksi atau server.');
                });
            },

            init() {
                // Map surah by ID
                this.surahs.forEach(s => {
                    this.surahMap[s.id] = s;
                });

                // Build initial rows
                this.rows = this.students.map(student => {
                    const lastRec = this.latestRecords[student.id] || null;
                    let defaultSurahId = '';
                    let defaultAyahStart = 1;
                    let defaultAyahEnd = 1;
                    let lastHistoryInfo = null;

                    if (lastRec) {
                        lastHistoryInfo = {
                            surah_name: lastRec.surah ? lastRec.surah.name_latin : '-',
                            ayah_start: lastRec.ayah_start,
                            ayah_end: lastRec.ayah_end,
                            date: lastRec.reviewed_at ? lastRec.reviewed_at.split('T')[0] : ''
                        };

                        // Auto predict next surah or continuation
                        if (lastRec.surah) {
                            if (lastRec.ayah_end >= lastRec.surah.total_ayah) {
                                // Recommend next surah in sequence
                                const nextSurahNum = lastRec.surah.number + 1;
                                const nextSurah = this.surahs.find(s => s.number === nextSurahNum);
                                if (nextSurah) {
                                    defaultSurahId = nextSurah.id;
                                    defaultAyahStart = 1;
                                    defaultAyahEnd = nextSurah.total_ayah;
                                } else {
                                    defaultSurahId = lastRec.surah_id;
                                    defaultAyahStart = 1;
                                    defaultAyahEnd = lastRec.surah.total_ayah;
                                }
                            } else {
                                defaultSurahId = lastRec.surah_id;
                                defaultAyahStart = lastRec.ayah_end + 1;
                                defaultAyahEnd = lastRec.surah.total_ayah;
                            }
                        }
                    }

                    return {
                        student_id: student.id,
                        student_name: student.name,
                        class_name: student.class_room ? student.class_room.name : (student.classRoom ? student.classRoom.name : ''),
                        last_history: lastHistoryInfo,
                        default_surah_id: defaultSurahId,
                        surah_id: defaultSurahId,
                        surah_end_id: defaultSurahId,
                        ayah_start: defaultAyahStart,
                        ayah_end: defaultAyahEnd,
                        score: '',
                        notes: ''
                    };
                });
            }
        };
    }
    </script>
</x-app-layout>
