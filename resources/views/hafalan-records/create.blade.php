<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-200 leading-tight">
                Input Setoran Hafalan
            </h2>
            <a href="{{ route('hafalan-records.index') }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 rounded-lg text-xs font-semibold hover:bg-gray-200 transition">
                <x-heroicon-m-arrow-left class="w-3.5 h-3.5 shrink-0" />
                <span>Kembali ke List</span>
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        method: '{{ old('method', request('method', 'reguler')) }}',
        selectedClass: '',
        selectedStudent: '{{ old('student_id') }}',
        selectedDate: '{{ now()->format('Y-m-d') }}',
        tatapMuka: {{ old('tatap_muka', 1) }},
        tatapMukaLoading: false,
        refreshTatapMuka() {
            if (!this.selectedClass || !this.selectedDate) {
                return;
            }
            this.tatapMukaLoading = true;
            fetch(`{{ route('ummi-records.tatap-muka-suggestion') }}?class_room_id=${this.selectedClass}&date=${this.selectedDate}`)
                .then(res => res.json())
                .then(data => {
                    if (typeof data.tatap_muka === 'number') {
                        this.tatapMuka = data.tatap_muka;
                    }
                })
                .catch(err => console.error('Gagal memuat saran Tatap Muka:', err))
                .finally(() => { this.tatapMukaLoading = false; });
        },
        attendances: {},
        isLoadingAttendance: false,
        fetchAttendances() {
            if (!this.selectedClass) {
                this.attendances = {};
                return;
            }
            this.isLoadingAttendance = true;
            axios.get('/attendances/check', {
                params: {
                    class_room_id: this.selectedClass,
                    date: this.selectedDate
                }
            })
            .then(res => {
                if (res.data && res.data.attendances) {
                    let map = {};
                    res.data.attendances.forEach(att => {
                        map[att.id] = att.status;
                    });
                    this.attendances = map;
                }
            })
            .catch(err => console.error('Gagal mengambil presensi:', err))
            .finally(() => {
                this.isLoadingAttendance = false;
            });
        },
        saveAttendance(studentId, status) {
            this.attendances[studentId] = status;
            axios.post('/attendances/save', {
                student_id: studentId,
                class_room_id: this.selectedClass,
                tanggal: this.selectedDate,
                status: status
            })
            .then(res => {
                if (!res.data.success) {
                    alert('Gagal menyimpan presensi: ' + res.data.message);
                }
            })
            .catch(err => {
                console.error('Gagal menyimpan presensi:', err);
                alert('Gagal menyimpan presensi. Pastikan koneksi internet aktif.');
            });
        },
        hafalans: [
            @if(old('surah_ids'))
                @foreach(old('surah_ids') as $index => $oldSurahId)
                    {
                        surah_id: '{{ $oldSurahId }}',
                        ayah_start: '{{ old("ayah_starts")[$index] ?? "" }}',
                        ayah_end: '{{ old("ayah_ends")[$index] ?? "" }}',
                        submission_type: '{{ old("submission_types")[$index] ?? "new" }}',
                        score: '{{ old("scores")[$index] ?? "" }}',
                        status: '{{ old("statuses")[$index] ?? "passed" }}',
                        baris: '{{ old("baris")[$index] ?? "" }}'
                    },
                @endforeach
            @else
                {
                    surah_id: '{{ request('surah_id', '') }}',
                    ayah_start: '{{ request('ayah_start', '') }}',
                    ayah_end: '{{ request('ayah_end', '') }}',
                    submission_type: 'new',
                    score: '',
                    status: 'passed',
                    baris: ''
                }
            @endif
        ],
        ummiHafalans: [
            { surah_id: '', ayah: '', baris: '' }
        ],
        allStudents: [
            @foreach($students as $student)
                { id: {{ $student->id }}, name: '{{ addslashes($student->name) }}', nis: '{{ $student->student_number ?? '' }}', classId: '{{ $student->class_room_id }}', className: '{{ $student->classRoom?->name ?? '' }}', level: '{{ $student->tahfizh_level }}' },
            @endforeach
        ],
        surahDetails: {
            @foreach ($surahs as $surah)
                '{{ $surah->id }}': { id: {{ $surah->id }}, number: {{ $surah->number }}, totalAyah: {{ $surah->total_ayah }}, name: '{{ addslashes($surah->name_latin) }}' },
            @endforeach
        },
        calculateLines(surahId, startAyah, endAyah) {
            if (!surahId || !startAyah || !endAyah) return 0;
            const details = this.surahDetails[surahId];
            if (!details) return 0;
            const surahNumber = details.number;
            const totalAyah = details.totalAyah;

            const start = parseInt(startAyah);
            const end = parseInt(endAyah);
            if (isNaN(start) || isNaN(end) || start > end) return 0;

            if (window.quranVerseLines) {
                const keyStart = surahNumber + ':' + start;
                const keyEnd = surahNumber + ':' + end;

                const startInfo = window.quranVerseLines[keyStart];
                const endInfo = window.quranVerseLines[keyEnd];

                if (startInfo && endInfo) {
                    const pageStart = startInfo.page;
                    const pageEnd = endInfo.page;

                    const lineStart = startInfo.start;
                    const lineEnd = endInfo.end;

                    if (pageStart === pageEnd) {
                        const lines = lineEnd - lineStart + 1;
                        return Math.max(0, lines);
                    } else {
                        // Start Page
                        const startPageCapacity = (pageStart === 1 || pageStart === 2) ? 7 : 15;
                        const startPageLines = startPageCapacity - lineStart + 1;

                        // End Page
                        const endPageLines = lineEnd;

                        // Middle Pages
                        let middleLines = 0;
                        for (let p = pageStart + 1; p < pageEnd; p++) {
                            const pageCapacity = (p === 1 || p === 2) ? 7 : 15;
                            middleLines += pageCapacity;
                        }

                        return Math.max(0, startPageLines + middleLines + endPageLines);
                    }
                }
            }

            const pages = {
                1: 1.0, 2: 48.0, 3: 27.0, 4: 29.0, 5: 22.0, 6: 23.0, 7: 26.0, 8: 10.0, 9: 21.0, 10: 13.0,
                11: 14.0, 12: 12.0, 13: 7.0, 14: 7.0, 15: 6.0, 16: 15.0, 17: 12.0, 18: 12.0, 19: 7.0, 20: 10.0,
                21: 10.0, 22: 10.0, 23: 8.0, 24: 10.0, 25: 6.0, 26: 11.0, 27: 9.0, 28: 11.0, 29: 7.0, 30: 6.0,
                31: 4.0, 32: 3.0, 33: 9.0, 34: 6.0, 35: 6.0, 36: 6.0, 37: 7.0, 38: 5.0, 39: 8.0, 40: 9.0,
                41: 6.0, 42: 6.0, 43: 7.0, 44: 3.0, 45: 3.0, 46: 4.0, 47: 4.0, 48: 4.0, 49: 2.5, 50: 3.0,
                51: 2.5, 52: 2.5, 53: 2.5, 54: 2.5, 55: 3.0, 56: 3.0, 57: 4.0, 58: 3.0, 59: 3.0, 60: 2.5,
                61: 1.5, 62: 1.5, 63: 1.5, 64: 2.0, 65: 2.0, 66: 2.0, 67: 2.5, 68: 2.0, 69: 2.0, 70: 2.0,
                71: 1.5, 72: 2.0, 73: 1.5, 74: 2.0, 75: 2.0, 76: 2.0, 77: 2.0, 78: 2.0, 79: 2.0, 80: 1.5,
                81: 1.0, 82: 1.0, 83: 2.0, 84: 1.0, 85: 1.0, 86: 1.0, 87: 1.0, 88: 1.0, 89: 1.5, 90: 1.0,
                91: 1.0, 92: 1.0, 93: 0.5, 94: 0.5, 95: 0.5, 96: 1.0, 97: 0.5, 98: 1.0, 99: 0.5, 100: 0.5,
                101: 0.5, 102: 0.5, 103: 0.3, 104: 0.5, 105: 0.3, 106: 0.3, 107: 0.5, 108: 0.3, 109: 0.5, 110: 0.3,
                111: 0.3, 112: 0.3, 113: 0.3, 114: 0.3
            };
            const pageCount = pages[surahNumber] || 1.0;
            const totalLines = pageCount * 15.0;
            const versesCount = Math.max(1, end - start + 1);
            const ratio = Math.min(1.0, versesCount / totalAyah);
            return Math.round(ratio * totalLines * 10) / 10;
        },
        parseAyahRange(ayahStr) {
            if (!ayahStr) return null;
            const clean = ayahStr.toString().replace(/\s+/g, '');
            const matchRange = clean.match(/^(\d+)-(\d+)$/);
            if (matchRange) {
                return { start: parseInt(matchRange[1]), end: parseInt(matchRange[2]) };
            }
            const matchSingle = clean.match(/^(\d+)$/);
            if (matchSingle) {
                return { start: parseInt(matchSingle[1]), end: parseInt(matchSingle[1]) };
            }
            return null;
        },
        calculateUmmiLines(surahId, ayahStr) {
            if (!surahId || !ayahStr) return 0;
            const range = this.parseAyahRange(ayahStr);
            if (!range) return 0;
            return this.calculateLines(surahId, range.start, range.end);
        },
        get filteredStudents() {
            if (!this.selectedClass) return this.allStudents;
            return this.allStudents.filter(s => {
                if (s.classId != this.selectedClass) return false;
                return this.attendances[s.id] === 'hadir';
            });
        },
        isDirty: false,
        isSaving: false,
        submitCurrentForm() {
            if (this.isSaving) return;
            this.isSaving = true;
            this.isDirty = false;
            const formId = this.method === 'ummi' ? 'form-ummi' : 'form-reguler';
            const form = document.getElementById(formId);
            if (form) {
                if (form.reportValidity && !form.reportValidity()) {
                    this.isSaving = false;
                    this.isDirty = true;
                    return;
                }
                form.submit();
            } else {
                this.isSaving = false;
            }
        }
    }" x-init="
        const cachedPageMapping = localStorage.getItem('quran_page_mapping');
        if (cachedPageMapping) {
            try { window.quranPageMapping = JSON.parse(cachedPageMapping); } catch (e) { localStorage.removeItem('quran_page_mapping'); }
        }
        if (!window.quranPageMapping) {
            fetch('{{ asset('quran_page_mapping.json') }}')
                .then(res => res.json())
                .then(data => {
                    window.quranPageMapping = data;
                    try { localStorage.setItem('quran_page_mapping', JSON.stringify(data)); } catch (e) {}
                })
                .catch(err => console.error('Gagal memuat peta halaman Quran:', err));
        }

        const cachedVerseLines = localStorage.getItem('quran_verse_lines');
        if (cachedVerseLines) {
            try { window.quranVerseLines = JSON.parse(cachedVerseLines); } catch (e) { localStorage.removeItem('quran_verse_lines'); }
        }
        if (!window.quranVerseLines) {
            fetch('{{ asset('quran_verse_lines.json') }}')
                .then(res => res.json())
                .then(data => {
                    window.quranVerseLines = data;
                    try { localStorage.setItem('quran_verse_lines', JSON.stringify(data)); } catch (e) {}
                })
                .catch(err => console.error('Gagal memuat batas baris ayat Quran:', err));
        }

        if (selectedStudent) {
            let s = allStudents.find(x => x.id == selectedStudent);
            if (s) {
                selectedClass = s.classId;
            }
        }
        this.refreshTatapMuka();

        $watch('selectedStudent', (val) => {
            if (val) {
                let s = allStudents.find(x => x.id == val);
                if (s) {
                    selectedClass = s.classId;
                }
            }
        });

        $watch('selectedClass', (val) => {
            fetchAttendances();
            this.refreshTatapMuka();
        });

        $watch('hafalans', (val) => {
            val.forEach(item => {
                if (item.surah_id && item.ayah_start && item.ayah_end && parseInt(item.ayah_start) <= parseInt(item.ayah_end)) {
                    if (item.baris === undefined || item.baris === '') {
                        item.baris = this.calculateLines(item.surah_id, item.ayah_start, item.ayah_end);
                    }
                }
            });
        }, { deep: true });

        $watch('ummiHafalans', (val) => {
            val.forEach(item => {
                if (item.surah_id && item.ayah) {
                    if (item.baris === undefined || item.baris === '') {
                        item.baris = this.calculateUmmiLines(item.surah_id, item.ayah);
                    }
                }
            });
        }, { deep: true });

        $watch('selectedDate', (val) => {
            fetchAttendances();
            this.refreshTatapMuka();
        });

        if (selectedClass) {
            fetchAttendances();
        }

        window.addEventListener('beforeunload', (e) => {
            if (this.isDirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        this.$nextTick(() => {
            let isReady = true;
            this.$watch('selectedStudent', () => { if (isReady && this.selectedStudent) this.isDirty = true; });
            this.$watch('hafalans', () => { if (isReady) this.isDirty = true; }, { deep: true });
            this.$watch('ummiHafalans', () => { if (isReady) this.isDirty = true; }, { deep: true });
        });
    ">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- UNIFIED INPUT HUB TABS -->
            @include('partials.tahfizh-input-nav-tabs', ['activeTab' => 'single'])

            <!-- Tab Switcher Metode Setoran -->
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm rounded-xl p-3 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-xs font-bold uppercase text-gray-500 dark:text-zinc-400 px-2 tracking-wider">
                    Pilih Metode Setoran:
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button type="button"
                            @click="method = 'reguler'"
                            :class="method === 'reguler' ? 'bg-indigo-600 text-white shadow' : 'bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 hover:bg-gray-200'"
                            class="flex-1 sm:flex-none px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <x-heroicon-o-book-open class="w-4 h-4" /> Reguler (Al-Qur'an)
                    </button>
                    <button type="button"
                            @click="method = 'ummi'"
                            :class="method === 'ummi' ? 'bg-emerald-600 text-white shadow' : 'bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 hover:bg-gray-200'"
                            class="flex-1 sm:flex-none px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <x-heroicon-o-sparkles class="w-4 h-4" /> Metode UMMI
                    </button>
                </div>
            </div>

            <!-- Error list jika ada -->
            @if ($errors->any())
                <div class="p-4 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-lg text-xs text-red-600 dark:text-red-300 space-y-1">
                    <p class="font-bold">Ada beberapa kesalahan input setoran:</p>
                    <ul class="list-disc pl-4 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- TABEL PRESENSI HARIAN -->
            <div x-show="selectedClass" class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm sm:rounded-xl p-6" style="display: none;">
                <div class="border-b dark:border-zinc-800 pb-3 mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-base">Tabel Presensi Kelas</h3>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">Isi kehadiran murid terlebih dahulu untuk hari ini. Hanya murid yang ditandai <strong>Hadir</strong> yang dapat dipilih untuk setoran hafalan.</p>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <span>Tanggal Presensi:</span>
                        <span class="font-bold text-gray-800 dark:text-zinc-200" x-text="selectedDate"></span>
                    </div>
                </div>

                <!-- Loading State -->
                <div x-show="isLoadingAttendance" class="py-8 text-center text-sm text-gray-500">
                    <svg class="animate-spin h-5 w-5 text-indigo-655 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Memuat data presensi...
                </div>

                <!-- Attendance Table -->
                <div x-show="!isLoadingAttendance" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-250 dark:divide-zinc-800">
                        <thead class="bg-gray-50 dark:bg-zinc-850">
                            <tr>
                                <th scope="col" class="px-4 py-2.5 text-left text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider w-12">No</th>
                                <th scope="col" class="px-4 py-2.5 text-left text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Nama Murid</th>
                                <th scope="col" class="px-4 py-2.5 text-center text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider w-20">Hadir</th>
                                <th scope="col" class="px-4 py-2.5 text-center text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider w-20">Sakit</th>
                                <th scope="col" class="px-4 py-2.5 text-center text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider w-20">Izin</th>
                                <th scope="col" class="px-4 py-2.5 text-center text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider w-20">Alpa</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-150 dark:divide-zinc-800">
                            <template x-for="(student, index) in allStudents.filter(s => s.classId == selectedClass)" :key="student.id">
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-zinc-850/20">
                                    <td class="px-4 py-2 text-xs font-bold text-gray-400 dark:text-zinc-600" x-text="index + 1"></td>
                                    <td class="px-4 py-2 text-xs font-bold text-gray-900 dark:text-zinc-200" x-text="student.name"></td>
                                    <td class="px-4 py-2 text-center">
                                        <input type="radio" 
                                               :name="'attendance_' + student.id" 
                                               value="hadir" 
                                               :checked="attendances[student.id] === 'hadir'"
                                               @change="saveAttendance(student.id, 'hadir')"
                                               class="h-4 w-4 text-indigo-600 border-gray-300 dark:border-zinc-700 focus:ring-indigo-500 cursor-pointer">
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <input type="radio" 
                                               :name="'attendance_' + student.id" 
                                               value="sakit" 
                                               :checked="attendances[student.id] === 'sakit'"
                                               @change="saveAttendance(student.id, 'sakit')"
                                               class="h-4 w-4 text-yellow-600 border-gray-300 dark:border-zinc-700 focus:ring-yellow-500 cursor-pointer">
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <input type="radio" 
                                               :name="'attendance_' + student.id" 
                                               value="izin" 
                                               :checked="attendances[student.id] === 'izin'"
                                               @change="saveAttendance(student.id, 'izin')"
                                               class="h-4 w-4 text-blue-600 border-gray-300 dark:border-zinc-700 focus:ring-blue-500 cursor-pointer">
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <input type="radio" 
                                               :name="'attendance_' + student.id" 
                                               value="alpa" 
                                               :checked="attendances[student.id] === 'alpa'"
                                               @change="saveAttendance(student.id, 'alpa')"
                                               class="h-4 w-4 text-red-650 border-gray-300 dark:border-zinc-700 focus:ring-red-500 cursor-pointer">
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FORM 1: METODE REGULER -->
            <div x-show="method === 'reguler'" class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm sm:rounded-xl p-6">
                <div class="border-b dark:border-zinc-800 pb-4 mb-6 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-lg">Input Setoran Hafalan Reguler</h3>
                        <p class="text-xs text-gray-500 dark:text-zinc-400">Setoran Al-Qur'an per Surah & Ayat untuk kelompok reguler / tahfizh.</p>
                    </div>
                    <span class="px-2.5 py-1 rounded bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400 font-bold text-xs border border-indigo-100 dark:border-indigo-900">
                        Reguler
                    </span>
                </div>

                <form id="form-reguler" method="POST" action="{{ route('hafalan-records.store') }}" @input="isDirty = true" @change="isDirty = true" class="space-y-6">
                    @csrf
                    <input type="hidden" name="method" value="reguler">

                    <!-- Saring & Murid & Tanggal -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label for="class_room_filter_reguler" class="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                                Saring Berdasarkan Kelas
                            </label>
                            <select id="class_room_filter_reguler"
                                    x-model="selectedClass"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                <option value="" class="dark:bg-zinc-900">Semua Kelas</option>
                                @foreach ($classRooms as $class)
                                    <option value="{{ $class->id }}" class="dark:bg-zinc-900">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="student_id_reguler" class="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                                Murid
                            </label>
                            <select id="student_id_reguler"
                                    name="student_id"
                                    x-model="selectedStudent"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white"
                                    required>
                                <option value="" class="dark:bg-zinc-900">Pilih Murid</option>
                                <template x-for="student in filteredStudents" :key="student.id">
                                    <option :value="student.id" x-text="student.name + (student.nis ? ' - ' + student.nis : '') + (student.className ? ' - ' + student.className : '')" :selected="student.id == selectedStudent" class="dark:bg-zinc-900"></option>
                                </template>
                            </select>
                            <template x-if="selectedClass && filteredStudents.length === 0">
                                <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400 font-semibold leading-relaxed inline-flex items-center gap-1.5">
                                    <x-heroicon-o-exclamation-triangle class="w-4 h-4 shrink-0 text-amber-500" />
                                    <span>Belum ada murid yang ditandai Hadir hari ini. Silakan tandai kehadiran 'Hadir' pada tabel presensi di atas.</span>
                                </p>
                            </template>
                            @error('student_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="submitted_at_reguler" class="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                                Tanggal Setoran
                            </label>
                            <input id="submitted_at_reguler"
                                   name="submitted_at"
                                   type="date"
                                   x-model="selectedDate"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white"
                                   required>
                            @error('submitted_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Dynamic Setoran List -->
                    <div class="mt-6 border border-gray-200 dark:border-zinc-800 rounded-lg p-5 bg-gray-50/50 dark:bg-zinc-900/50 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase text-gray-600 dark:text-zinc-400 tracking-wider">
                                Daftar Setoran Hafalan
                            </h3>
                            <button type="button"
                                    @click="hafalans.push({ surah_id: '', ayah_start: '', ayah_end: '', submission_type: 'new', score: '', status: 'passed' })"
                                    class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-semibold shadow-sm transition-colors cursor-pointer">
                                + Tambah Baris Setoran
                            </button>
                        </div>

                        <div class="space-y-4">
                            <template x-for="(item, index) in hafalans" :key="index">
                                <div class="bg-white dark:bg-zinc-900 p-4 rounded-lg border border-gray-200 dark:border-zinc-800 shadow-sm relative space-y-4">
                                    <div class="flex items-center justify-between border-b dark:border-zinc-800 pb-2">
                                        <span class="text-xs font-bold text-gray-500 dark:text-zinc-400" x-text="'Setoran #' + (index + 1)"></span>
                                        <button type="button"
                                                @click="if (hafalans.length > 1) { hafalans.splice(index, 1); } else { item.surah_id = ''; item.ayah_start = ''; item.ayah_end = ''; item.submission_type = 'new'; item.score = ''; item.status = 'passed'; }"
                                                class="text-xs text-red-600 hover:text-red-800 font-medium cursor-pointer">
                                            Hapus
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-4">
                                        <div class="col-span-1 md:col-span-2">
                                            <label class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                                Surah
                                            </label>
                                            <div x-data="{ open: false, search: '' }" @click.outside="open = false" class="relative">
                                                <button type="button" 
                                                        @click="open = !open" 
                                                        class="flex items-center justify-between w-full rounded-md border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-left text-xs px-3 py-2 text-gray-700 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                                                    <span x-text="item.surah_id && surahDetails[item.surah_id] ? surahDetails[item.surah_id].number + '. ' + surahDetails[item.surah_id].name + ' — ' + surahDetails[item.surah_id].totalAyah + ' ayat' : 'Pilih Surah'"></span>
                                                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                    </svg>
                                                </button>
                                                
                                                <div x-show="open" 
                                                     class="absolute z-50 mt-1 w-full bg-white dark:bg-zinc-900 rounded-md shadow-lg border border-gray-200 dark:border-zinc-800"
                                                     style="display: none;">
                                                    <div class="p-2 border-b border-gray-200 dark:border-zinc-800 bg-gray-50 dark:bg-zinc-900">
                                                        <input type="text" 
                                                               x-model="search" 
                                                               placeholder="Cari nama surat..." 
                                                               class="w-full rounded border border-gray-300 dark:border-zinc-700 bg-transparent text-xs px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white">
                                                    </div>
                                                    <ul class="max-h-[180px] overflow-y-auto py-1 text-xs">
                                                        <template x-for="surah in Object.values(surahDetails).filter(s => s.name.toLowerCase().includes(search.toLowerCase()))" :key="surah.id">
                                                            <li @click="item.surah_id = surah.id; open = false; search = ''" 
                                                                class="px-3 py-2 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-700 cursor-pointer transition-colors"
                                                                x-text="surah.number + '. ' + surah.name + ' — ' + surah.totalAyah + ' ayat'">
                                                            </li>
                                                        </template>
                                                        <li x-show="Object.values(surahDetails).filter(s => s.name.toLowerCase().includes(search.toLowerCase())).length === 0" 
                                                            class="px-3 py-2 text-gray-500 text-center">
                                                            Surah tidak ditemukan
                                                        </li>
                                                    </ul>
                                                </div>
                                                <input type="hidden" :name="'surah_ids['+index+']'" x-model="item.surah_id" required>
                                            </div>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                                Ayat Mulai
                                            </label>
                                            <input type="number"
                                                   min="1"
                                                   :name="'ayah_starts['+index+']'"
                                                   x-model="item.ayah_start"
                                                   class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-xs focus:border-indigo-500 focus:ring-indigo-500 dark:text-white"
                                                   required>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                                Ayat Akhir
                                            </label>
                                            <input type="number"
                                                   min="1"
                                                   :name="'ayah_ends['+index+']'"
                                                   x-model="item.ayah_end"
                                                   class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-xs focus:border-indigo-500 focus:ring-indigo-500 dark:text-white"
                                                   required>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                                Baris (Manual)
                                            </label>
                                            <input type="number"
                                                   step="0.1"
                                                   min="0"
                                                   :name="'baris['+index+']'"
                                                   x-model="item.baris"
                                                   placeholder="0"
                                                   class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-xs focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                                Jenis Setoran
                                            </label>
                                            <select :name="'submission_types['+index+']'"
                                                    x-model="item.submission_type"
                                                    class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-xs focus:border-indigo-500 focus:ring-indigo-500 dark:text-white"
                                                    required>
                                                <option value="new" class="dark:bg-zinc-900">Baru</option>
                                                <option value="continuation" class="dark:bg-zinc-900">Lanjutan</option>
                                                <option value="revision" class="dark:bg-zinc-900">Perbaikan</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                                Nilai (Skala A - E)
                                            </label>
                                            <select :name="'scores['+index+']'"
                                                    x-model="item.score"
                                                    class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-xs focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                                <option value="" class="dark:bg-zinc-900">Pilih Nilai</option>
                                                <option value="95" class="dark:bg-zinc-900">A (Sangat Baik)</option>
                                                <option value="85" class="dark:bg-zinc-900">B (Baik)</option>
                                                <option value="75" class="dark:bg-zinc-900">C (Cukup)</option>
                                                <option value="65" class="dark:bg-zinc-900">D (Kurang)</option>
                                                <option value="55" class="dark:bg-zinc-900">E (Sangat Kurang)</option>
                                            </select>
                                        </div>

                                        <div class="col-span-1 md:col-span-2">
                                            <label class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                                Status Setoran
                                            </label>
                                            <select :name="'statuses['+index+']'"
                                                    x-model="item.status"
                                                    class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-xs focus:border-indigo-500 focus:ring-indigo-500 dark:text-white"
                                                    required>
                                                <option value="passed" class="dark:bg-zinc-900">Lulus</option>
                                                <option value="repeat" class="dark:bg-zinc-900">Ulang</option>
                                                <option value="needs_improvement" class="dark:bg-zinc-900">Perlu Perbaikan</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-right text-xs text-gray-500 font-semibold" x-show="item.surah_id && item.ayah_start && item.ayah_end && parseInt(item.ayah_start) <= parseInt(item.ayah_end)">
                                        <span>Taksiran Capaian:</span>
                                        <span class="px-2 py-0.5 rounded bg-zinc-150 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 font-extrabold" x-text="calculateLines(item.surah_id, item.ayah_start, item.ayah_end) + ' Baris'"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Catatan Guru -->
                    <div class="mt-6">
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                            Catatan Guru (Berlaku untuk semua setoran di atas)
                        </label>
                        <textarea id="notes"
                                  name="notes"
                                  rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white"
                                  placeholder="Contoh: Lancar, tajwid masih perlu diperbaiki pada mad.">{{ old('notes') }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t dark:border-zinc-800">
                        <a href="{{ route('hafalan-records.index') }}" class="px-4 py-2 text-sm text-gray-600 dark:text-zinc-400 hover:underline">
                            Batal
                        </a>
                        <button type="submit"
                                class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-sm transition">
                            Simpan Setoran Reguler
                        </button>
                    </div>
                </form>
            </div>

            <!-- FORM 2: METODE UMMI -->
            <div x-show="method === 'ummi'" x-cloak class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm sm:rounded-xl p-6">
                <div class="border-b dark:border-zinc-800 pb-4 mb-6 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-lg">Input Catatan Hafalan Metode UMMI</h3>
                        <p class="text-xs text-gray-500 dark:text-zinc-400">Pencatatan tatap muka, jilid UMMI, materi, serta nilai evaluasi murid.</p>
                    </div>
                    <span class="px-2.5 py-1 rounded bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 font-bold text-xs border border-emerald-100 dark:border-emerald-900">
                        Metode UMMI
                    </span>
                </div>

                <form id="form-ummi" method="POST" action="{{ route('ummi-records.store') }}" @input="isDirty = true" @change="isDirty = true" class="space-y-6">
                    @csrf
                    <input type="hidden" name="method" value="ummi">
                    <input type="hidden" name="redirect_to" value="hafalan">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div class="space-y-4">
                            <div>
                                <label for="class_room_filter_ummi" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                    Saring Berdasarkan Kelas
                                </label>
                                <select id="class_room_filter_ummi"
                                        name="class_room_id"
                                        x-model="selectedClass"
                                        class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                    <option value="" class="dark:bg-zinc-900">Semua Kelas</option>
                                    @foreach ($classRooms as $class)
                                        <option value="{{ $class->id }}" class="dark:bg-zinc-900">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="student_id_ummi" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                    Murid
                                </label>
                                <select id="student_id_ummi"
                                        name="student_id"
                                        x-model="selectedStudent"
                                        class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                    <option value="" class="dark:bg-zinc-900">Semua Murid (Input Kelas/Bulk)</option>
                                    <template x-for="student in filteredStudents" :key="student.id">
                                        <option :value="student.id" x-text="student.name + (student.className ? ' — ' + student.className : '')" :selected="student.id == selectedStudent" class="dark:bg-zinc-900"></option>
                                    </template>
                                </select>
                                <template x-if="selectedClass && filteredStudents.length === 0">
                                    <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400 font-semibold leading-relaxed inline-flex items-center gap-1.5">
                                        <x-heroicon-o-exclamation-triangle class="w-4 h-4 shrink-0 text-amber-500" />
                                        <span>Belum ada murid yang ditandai Hadir hari ini. Silakan tandai kehadiran 'Hadir' pada tabel presensi di atas.</span>
                                    </p>
                                </template>
                            </div>

                            <div class="grid grid-cols-1 xs:grid-cols-2 gap-4">
                                <div>
                                    <label for="ummi_tatap_muka" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                        Tatap Muka (Ke-)
                                        <span x-show="tatapMukaLoading" class="text-xs text-gray-400 font-normal">(menghitung...)</span>
                                    </label>
                                    <input id="ummi_tatap_muka"
                                           type="number"
                                           name="tatap_muka"
                                           min="1"
                                           required
                                           x-model.number="tatapMuka"
                                           class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                    <p class="mt-1 text-[11px] text-gray-400 dark:text-zinc-500">Otomatis dihitung dari hari efektif kelas, bisa diubah manual bila perlu.</p>
                                </div>
                                <div>
                                    <label for="ummi_tanggal" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                        Tanggal
                                    </label>
                                    <input id="ummi_tanggal"
                                           type="date"
                                           name="tanggal"
                                           required
                                           x-model="selectedDate"
                                           class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                </div>
                            </div>

                            <!-- Dynamic Hafalan List UMMI -->
                            <div class="space-y-3 border border-gray-200 dark:border-zinc-800 rounded-lg p-4 bg-gray-50/50 dark:bg-zinc-900/50">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold uppercase text-gray-500 dark:text-zinc-400 tracking-wider">
                                        Setoran Hafalan UMMI
                                    </label>
                                    <button type="button"
                                            @click="ummiHafalans.push({ surah_id: '', ayah: '', baris: '' })"
                                            class="inline-flex items-center px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 rounded text-xs font-semibold hover:bg-emerald-100 transition cursor-pointer">
                                        + Tambah Surah
                                    </button>
                                </div>

                                <template x-for="(item, index) in ummiHafalans" :key="index">
                                    <div class="grid grid-cols-12 gap-3 items-end bg-white dark:bg-zinc-900 p-3 rounded-lg border border-gray-200 dark:border-zinc-800 relative">
                                        <div class="col-span-6">
                                            <label :for="'ummi_hafalan_surah_' + index" class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                                Surah
                                            </label>
                                            <div x-data="{ open: false, search: '' }" @click.outside="open = false" class="relative">
                                                 <button type="button" 
                                                         @click="open = !open" 
                                                         class="flex items-center justify-between w-full rounded-md border border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-900 text-left text-xs px-3 py-2 text-gray-700 dark:text-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                                                     <span x-text="item.surah_id && surahDetails[item.surah_id] ? surahDetails[item.surah_id].number + '. ' + surahDetails[item.surah_id].name + ' — ' + surahDetails[item.surah_id].totalAyah + ' ayat' : 'Pilih Surah'"></span>
                                                     <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                     </svg>
                                                 </button>
                                                 
                                                 <div x-show="open" 
                                                      class="absolute z-50 mt-1 w-full bg-white dark:bg-zinc-900 rounded-md shadow-lg border border-gray-200 dark:border-zinc-800"
                                                      style="display: none;">
                                                     <div class="p-2 border-b border-gray-200 dark:border-zinc-800 bg-gray-50 dark:bg-zinc-900">
                                                         <input type="text" 
                                                                x-model="search" 
                                                                placeholder="Cari nama surat..." 
                                                                class="w-full rounded border border-gray-300 dark:border-zinc-700 bg-transparent text-xs px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white">
                                                     </div>
                                                     <ul class="max-h-[180px] overflow-y-auto py-1 text-xs">
                                                         <template x-for="surah in Object.values(surahDetails).filter(s => s.name.toLowerCase().includes(search.toLowerCase()))" :key="surah.id">
                                                             <li @click="item.surah_id = surah.id; open = false; search = ''" 
                                                                 class="px-3 py-2 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-700 cursor-pointer transition-colors"
                                                                 x-text="surah.number + '. ' + surah.name + ' — ' + surah.totalAyah + ' ayat'">
                                                             </li>
                                                         </template>
                                                         <li x-show="Object.values(surahDetails).filter(s => s.name.toLowerCase().includes(search.toLowerCase())).length === 0" 
                                                             class="px-3 py-2 text-gray-500 text-center">
                                                             Surah tidak ditemukan
                                                         </li>
                                                     </ul>
                                                 </div>
                                                 <input type="hidden" :id="'ummi_hafalan_surah_' + index" :name="'hafalan_surah_ids['+index+']'" x-model="item.surah_id">
                                             </div>
                                        </div>
                                        <div class="col-span-3">
                                            <label :for="'ummi_hafalan_ayah_' + index" class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                                Ayat
                                            </label>
                                            <input type="text"
                                                   :id="'ummi_hafalan_ayah_' + index"
                                                   :name="'hafalan_ayahs['+index+']'"
                                                   x-model="item.ayah"
                                                   placeholder="e.g. 1-10"
                                                   class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-xs focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                        </div>
                                        <div class="col-span-2">
                                            <label :for="'ummi_hafalan_baris_' + index" class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                                Baris (Manual)
                                            </label>
                                            <input type="number"
                                                   step="0.1"
                                                   min="0"
                                                   :id="'ummi_hafalan_baris_' + index"
                                                   :name="'hafalan_baris['+index+']'"
                                                   x-model="item.baris"
                                                   placeholder="0"
                                                   class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-xs focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                        </div>
                                        <div class="col-span-2 text-right">
                                            <button type="button"
                                                    @click="if(ummiHafalans.length > 1) { ummiHafalans.splice(index, 1); } else { item.surah_id = ''; item.ayah = ''; item.baris = ''; }"
                                                    class="inline-flex items-center px-2 py-1.5 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 hover:bg-rose-100 rounded text-[11px] font-bold border border-rose-200 dark:border-rose-800 cursor-pointer">
                                                Hapus
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="grid grid-cols-1 xs:grid-cols-2 gap-4">
                                <div>
                                    <label for="ummi_jilid" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                        UMMI (Jilid)
                                    </label>
                                    <input id="ummi_jilid"
                                           type="text"
                                           name="ummi_jilid"
                                           value="{{ old('ummi_jilid') }}"
                                           placeholder="e.g. Jilid 4"
                                           class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                </div>
                                <div>
                                    <label for="ummi_halaman" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                        Halaman
                                    </label>
                                    <input id="ummi_halaman"
                                           type="text"
                                           name="ummi_halaman"
                                           value="{{ old('ummi_halaman') }}"
                                           placeholder="e.g. Hal 12"
                                           class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-4">
                            <div>
                                <label for="ummi_materi" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                    Materi Pembelajaran UMMI
                                </label>
                                <input id="ummi_materi"
                                       type="text"
                                       name="materi"
                                       value="{{ old('materi') }}"
                                       placeholder="e.g. Mad Thabi'i"
                                       class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                            </div>

                            <div class="grid grid-cols-1 xs:grid-cols-2 gap-4">
                                <div>
                                    <label for="ummi_nilai" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                        Nilai Evaluasi
                                    </label>
                                    <select id="ummi_nilai"
                                            name="nilai"
                                            class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                        <option value="" class="dark:bg-zinc-900">Pilih Nilai</option>
                                        <option value="A+" @selected(old('nilai') === 'A+') class="dark:bg-zinc-900">A+ (Kesalahan 0)</option>
                                        <option value="A" @selected(old('nilai') === 'A') class="dark:bg-zinc-900">A (Kesalahan 0)</option>
                                        <option value="B+" @selected(old('nilai') === 'B+') class="dark:bg-zinc-900">B+ (Kesalahan -1)</option>
                                        <option value="B" @selected(old('nilai') === 'B') class="dark:bg-zinc-900">B (Kesalahan -2)</option>
                                        <option value="B-" @selected(old('nilai') === 'B-') class="dark:bg-zinc-900">B- (Kesalahan -3)</option>
                                        <option value="C+" @selected(old('nilai') === 'C+') class="dark:bg-zinc-900">C+ (Kesalahan -4)</option>
                                        <option value="C" @selected(old('nilai') === 'C') class="dark:bg-zinc-900">C (Kesalahan -5)</option>
                                        <option value="C-" @selected(old('nilai') === 'C-') class="dark:bg-zinc-900">C- (Kesalahan -6)</option>
                                        <option value="D" @selected(old('nilai') === 'D') class="dark:bg-zinc-900">D (Kesalahan -7)</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <div>
                                        <label for="ummi_disimak_guru" class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                            Simak Guru
                                        </label>
                                        <select id="ummi_disimak_guru"
                                                name="disimak_guru"
                                                required
                                                class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-xs focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                            <option value="Ya" @selected(old('disimak_guru', 'Ya') === 'Ya') class="dark:bg-zinc-900">Ya</option>
                                            <option value="Tidak" @selected(old('disimak_guru') === 'Tidak') class="dark:bg-zinc-900">Tidak</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="ummi_disimak_ortu" class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                            Simak Ortu
                                        </label>
                                        <select id="ummi_disimak_ortu"
                                                name="disimak_ortu"
                                                required
                                                class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-xs focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                            <option value="Ya" @selected(old('disimak_ortu', 'Ya') === 'Ya') class="dark:bg-zinc-900">Ya</option>
                                            <option value="Tidak" @selected(old('disimak_ortu') === 'Tidak') class="dark:bg-zinc-900">Tidak</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="ummi_keterangan" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                    Catatan / Keterangan
                                </label>
                                <textarea id="ummi_keterangan"
                                          name="keterangan"
                                          rows="3"
                                          class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white"
                                          placeholder="Catatan perkembangan atau arahan tajwid dari guru.">{{ old('keterangan') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Student Checklist & Override Section (Only shown if selectedClass is chosen AND no single selectedStudent is chosen) -->
                    <!-- Empty State Checklist warning -->
                    <template x-if="selectedClass && !selectedStudent && filteredStudents.length === 0">
                        <div class="border-t border-gray-200 dark:border-zinc-800 pt-5 mt-4">
                            <div class="p-4 bg-rose-50 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-800 rounded-xl text-center">
                                <p class="text-xs text-rose-600 dark:text-rose-400 font-semibold inline-flex items-center justify-center gap-1.5">
                                    <x-heroicon-o-exclamation-triangle class="w-4 h-4 shrink-0 text-amber-500" />
                                    <span>Tidak ada murid yang ditandai Hadir di kelas ini untuk hari ini. Silakan tandai kehadiran 'Hadir' pada tabel presensi di atas.</span>
                                </p>
                            </div>
                        </div>
                    </template>

                    <div x-show="selectedClass && !selectedStudent && filteredStudents.length > 0" class="border-t border-gray-200 dark:border-zinc-800 pt-5 mt-4 space-y-4 student-checklist-container" style="display: none;">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b dark:border-zinc-800">
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white text-sm">Daftar Murid & Penyesuaian Nilai Individu</h4>
                                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">Daftar murid aktif di kelas halaqoh terpilih. Anda dapat mengecualikan murid yang absen dan menyesuaikan nilai/catatan mereka secara individual jika dibutuhkan.</p>
                            </div>
                            <div class="flex items-center gap-3 text-xs shrink-0 mt-1 sm:mt-0">
                                <button type="button" @click="$el.closest('.student-checklist-container').querySelectorAll('input[type=checkbox]').forEach(el => { el.checked = true; el.dispatchEvent(new Event('change')) })" class="text-indigo-600 dark:text-indigo-400 hover:underline font-semibold transition">Centang Semua</button>
                                <span class="text-gray-300 dark:text-zinc-700">|</span>
                                <button type="button" @click="$el.closest('.student-checklist-container').querySelectorAll('input[type=checkbox]').forEach(el => { el.checked = false; el.dispatchEvent(new Event('change')) })" class="text-red-650 dark:text-red-400 hover:underline font-semibold transition">Hapus Semua</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[350px] overflow-y-auto pr-1">
                            <template x-for="student in filteredStudents" :key="student.id">
                                <div class="flex flex-col p-3.5 bg-gray-50/70 dark:bg-zinc-800/30 rounded-xl border border-gray-250 dark:border-zinc-850 gap-3 hover:bg-gray-100 dark:hover:bg-zinc-800/50 transition duration-150">
                                    <!-- Top Row: Checkbox and Name -->
                                    <div class="flex items-start gap-3">
                                        <input type="checkbox" 
                                               name="student_ids[]" 
                                               :id="'checkbox_std_' + student.id"
                                               :value="student.id" 
                                               checked 
                                               class="mt-0.5 rounded border-gray-350 dark:border-zinc-700 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                                        <label :for="'checkbox_std_' + student.id" class="cursor-pointer select-none min-w-0 flex-1">
                                            <span class="font-bold text-xs text-gray-900 dark:text-zinc-200 block" x-text="student.name"></span>
                                            <span class="text-[10px] text-gray-500 dark:text-zinc-400 block mt-0.5" x-text="student.className ? student.className : '-'"></span>
                                        </label>
                                    </div>
                                    <!-- Bottom Row: Inputs -->
                                    <div class="flex items-center gap-2 pt-2.5 border-t border-gray-200/60 dark:border-zinc-800">
                                        <!-- Individual Score -->
                                        <div class="flex-1 min-w-0">
                                            <select :name="'student_scores[' + student.id + ']'" 
                                                    class="block w-full rounded-lg border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] py-1.5 pl-2 pr-7 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="" class="dark:bg-zinc-900">Nilai Default</option>
                                                <option value="A+" class="dark:bg-zinc-900">A+</option>
                                                <option value="A" class="dark:bg-zinc-900">A</option>
                                                <option value="B+" class="dark:bg-zinc-900">B+</option>
                                                <option value="B" class="dark:bg-zinc-900">B</option>
                                                <option value="B-" class="dark:bg-zinc-900">B-</option>
                                                <option value="C+" class="dark:bg-zinc-900">C+</option>
                                                <option value="C" class="dark:bg-zinc-900">C</option>
                                                <option value="C-" class="dark:bg-zinc-900">C-</option>
                                                <option value="D" class="dark:bg-zinc-900">D</option>
                                            </select>
                                        </div>
                                        <!-- Individual Note -->
                                        <div class="flex-[1.5] min-w-0">
                                            <input type="text" 
                                                   :name="'student_notes[' + student.id + ']'" 
                                                   placeholder="Catatan khusus" 
                                                   class="block w-full rounded-lg border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] py-1.5 px-2.5 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t dark:border-zinc-800">
                        <a href="{{ route('hafalan-records.index', ['category' => 'ummi']) }}" class="px-4 py-2 text-sm text-gray-600 dark:text-zinc-400 hover:underline">
                            Batal
                        </a>
                        <button type="submit"
                                class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold shadow-sm transition">
                            Simpan Catatan UMMI
                        </button>
                    </div>
                </form>
            </div>

            <!-- Floating Unsaved Changes Save Bar -->
            <div x-show="isDirty"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="translate-y-12 opacity-0 scale-95"
                 x-transition:enter-end="translate-y-0 opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="translate-y-0 opacity-100 scale-100"
                 x-transition:leave-end="translate-y-12 opacity-0 scale-95"
                 class="fixed bottom-24 xl:bottom-6 left-1/2 -translate-x-1/2 z-[60] bg-zinc-900/95 text-white dark:bg-white/95 dark:text-zinc-900 px-5 py-3 rounded-2xl shadow-2xl backdrop-blur-md border border-zinc-700/80 dark:border-zinc-300 flex items-center gap-4 text-xs font-bold"
                 style="display: none;">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-ping"></span>
                    <span>Ada data setoran hafalan yang belum disimpan!</span>
                </div>
                <button type="button" @click="submitCurrentForm()" :disabled="isSaving" :class="method === 'ummi' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-indigo-600 hover:bg-indigo-700'" class="disabled:bg-gray-400 text-white px-4 py-2 rounded-xl font-bold transition shadow-lg cursor-pointer flex items-center gap-1.5">
                    <template x-if="!isSaving">
                        <span class="inline-flex items-center gap-1.5">
                            <x-heroicon-o-check class="w-4 h-4" />
                            <span>Simpan Sekarang</span>
                        </span>
                    </template>
                    <template x-if="isSaving">
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg> Menyimpan...
                        </span>
                    </template>
                </button>
            </div>

        </div>
    </div>
</x-app-layout>