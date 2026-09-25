<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-200 leading-tight">
                Input Spreadsheet Perkembangan Kelas
            </h2>
            <a href="{{ route('hafalan-records.index') }}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 rounded-lg text-xs font-semibold hover:bg-gray-200 transition">
                <x-heroicon-m-arrow-left class="w-3.5 h-3.5 shrink-0" />
                <span>Kembali ke List</span>
            </a>
        </div>
    </x-slot>

    <!-- Alpine.js Component Script Definition (Safe from HTML parsing errors) -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('spreadsheetData', () => ({
                tab: 'hafalan',
                selectedClass: '{{ $selectedClassId }}',
                selectedMonth: '{{ $selectedMonth }}',
                selectedMonthNum: '{{ explode('-', $selectedMonth)[1] ?? date('m') }}',
                selectedYearNum: '{{ explode('-', $selectedMonth)[0] ?? date('Y') }}',
                todayDate: '{{ now()->setTimezone(config('app.timezone', 'Asia/Jakarta'))->toDateString() }}',
                currentMonth: '{{ now()->setTimezone(config('app.timezone', 'Asia/Jakarta'))->format('Y-m') }}',
                selectedMobileDate: '{{ in_array(now()->setTimezone(config('app.timezone', 'Asia/Jakarta'))->toDateString(), $dates) ? now()->setTimezone(config('app.timezone', 'Asia/Jakarta'))->toDateString() : ($dates[0] ?? '') }}',
                students: @json($students),
                surahs: @json($surahs),
                dates: @json($dates),
                columns: @json($columns),
                isWeekly: {{ json_encode($isWeekly) }},
                attendancesMap: @json($attendancesMap),
                hafalanRecordsMap: @json($hafalanRecordsMap),
                ummiRecordsMap: @json($ummiRecordsMap),
                lastHafalanMap: @json($lastHafalanMap),
                gridData: {},
                surahDetails: {},
                isDirty: false,
                isSaving: false,
                isMobileView: window.innerWidth < 768,
                init() {
                    window.addEventListener('resize', () => {
                        this.isMobileView = window.innerWidth < 768;
                    });

                    window.addEventListener('beforeunload', (e) => {
                        if (this.isDirty && !this.isSaving) {
                            e.preventDefault();
                            e.returnValue = '';
                        }
                    });

                    // Warn on internal link click navigation if unsaved changes exist
                    document.addEventListener('click', (e) => {
                        const link = e.target.closest('a');
                        if (link && link.href && !link.target && !link.hasAttribute('download') && this.isDirty && !this.isSaving) {
                            if (!confirm('Peringatan: Ada perubahan nilai/presensi di spreadsheet yang belum Anda simpan. Jika Anda meninggalkan halaman ini, perubahan tersebut akan hilang. Apakah Anda yakin ingin keluar?')) {
                                e.preventDefault();
                                e.stopPropagation();
                            }
                        }
                    });

                    // Index surah details for fast lookup
                    this.surahs.forEach(s => {
                        this.surahDetails[s.id] = { id: s.id, number: s.number, totalAyah: s.total_ayah, name: s.name_latin };
                    });

                    // Initialize reactive grid data
                    this.students.forEach(s => {
                        this.gridData[s.id] = { dates: {} };
                        this.dates.forEach(d => {
                            let att = (this.attendancesMap[s.id] && this.attendancesMap[s.id][d]) ? this.attendancesMap[s.id][d] : '';
                            
                            // Hafalan tab
                            let hList = [];
                            if (this.hafalanRecordsMap[s.id] && this.hafalanRecordsMap[s.id][d]) {
                                hList = JSON.parse(JSON.stringify(this.hafalanRecordsMap[s.id][d]));
                            }
                            if (hList.length === 0) {
                                hList.push({ id: null, surah_id: '', ayah_start: '', ayah_end: '', score: '', status: 'passed', submission_type: 'new' });
                            }

                            // UMMI tab
                            let uData = (this.ummiRecordsMap[s.id] && this.ummiRecordsMap[s.id][d]) ? this.ummiRecordsMap[s.id][d] : null;
                            let uHafalans = [];
                            if (uData && uData.hafalans) {
                                uHafalans = JSON.parse(JSON.stringify(uData.hafalans));
                            }
                            if (uHafalans.length === 0) {
                                uHafalans.push({ id: null, surah_id: '', ayah: '' });
                            }

                            // Auto-default attendance to 'hadir' if hafalan or UMMI records exist for this cell
                            if (!att && ((hList.length > 0 && hList[0].surah_id) || uData)) {
                                att = 'hadir';
                            }

                            this.gridData[s.id].dates[d] = {
                                attendance: att,
                                hafalans: hList,
                                ummi_jilid: uData ? uData.ummi_jilid || '' : '',
                                ummi_halaman: uData ? uData.ummi_halaman || '' : '',
                                materi: uData ? uData.materi || '' : '',
                                nilai: uData ? uData.nilai || '' : '',
                                tatap_muka: uData ? uData.tatap_muka || 1 : 1,
                                ummiHafalans: uHafalans
                            };
                        });
                    });

                    this.$nextTick(() => {
                        let isReady = true;
                        this.checkDraft();
                        this.$watch('gridData', () => {
                            if (isReady) {
                                this.isDirty = true;
                                window._hasUnsavedDraft = true;
                                this.saveDraftDebounced();
                            }
                        }, { deep: true });

                        // Auto-scroll to today's column on initial view if present
                        if (this.dates.includes(this.todayDate)) {
                            setTimeout(() => {
                                const col = document.getElementById('col-header-' + this.todayDate);
                                if (col) {
                                    col.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                                }
                            }, 200);
                        }
                    });
                },
                draftKey: 'tad_draft_spreadsheet_{{ $selectedClassId }}_{{ $selectedMonth }}',
                hasDraftAvailable: false,
                draftTimestamp: '',
                saveDraftTimer: null,

                saveDraftDebounced() {
                    clearTimeout(this.saveDraftTimer);
                    this.saveDraftTimer = setTimeout(() => {
                        try {
                            const payload = {
                                timestamp: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
                                rawTime: Date.now(),
                                gridData: this.gridData,
                                tab: this.tab
                            };
                            localStorage.setItem(this.draftKey, JSON.stringify(payload));
                        } catch (e) {}
                    }, 1000);
                },

                checkDraft() {
                    try {
                        const raw = localStorage.getItem(this.draftKey);
                        if (!raw) return;
                        const parsed = JSON.parse(raw);
                        if (parsed && parsed.gridData && (Date.now() - (parsed.rawTime || 0) < 7 * 24 * 60 * 60 * 1000)) {
                            this.hasDraftAvailable = true;
                            this.draftTimestamp = parsed.timestamp || 'sebelumnya';
                        }
                    } catch (e) {}
                },

                restoreDraft() {
                    try {
                        const raw = localStorage.getItem(this.draftKey);
                        if (!raw) return;
                        const parsed = JSON.parse(raw);
                        if (parsed && parsed.gridData) {
                            this.gridData = parsed.gridData;
                            if (parsed.tab) this.tab = parsed.tab;
                            this.isDirty = true;
                            window._hasUnsavedDraft = true;
                            this.hasDraftAvailable = false;
                            alert('Data draf berhasil dipulihkan ke formulir spreadsheet!');
                        }
                    } catch (e) {
                        alert('Gagal memulihkan draf.');
                    }
                },

                dismissDraft() {
                    localStorage.removeItem(this.draftKey);
                    this.hasDraftAvailable = false;
                },
                getNextHafalan(studentId, cellHafalans = []) {
                    let validPrevious = (cellHafalans || []).filter(h => h.surah_id && h.ayah_end);
                    if (validPrevious.length > 0) {
                        let lastH = validPrevious[validPrevious.length - 1];
                        let surahId = parseInt(lastH.surah_id);
                        let ayahEnd = parseInt(lastH.ayah_end);
                        let details = this.surahDetails[surahId];
                        let totalAyah = details ? details.totalAyah : 1;

                        if (ayahEnd < totalAyah) {
                            return { id: null, surah_id: surahId, ayah_start: ayahEnd + 1, ayah_end: ayahEnd + 1, score: '', status: 'passed', submission_type: 'new' };
                        } else {
                            let nextSurahId = surahId < 114 ? surahId + 1 : 1;
                            return { id: null, surah_id: nextSurahId, ayah_start: 1, ayah_end: 1, score: '', status: 'passed', submission_type: 'new' };
                        }
                    }

                    let lastInfo = this.lastHafalanMap[studentId];
                    if (lastInfo) {
                        return { id: null, surah_id: lastInfo.next_surah_id, ayah_start: lastInfo.next_ayah_start, ayah_end: lastInfo.next_ayah_start, score: '', status: 'passed', submission_type: 'new' };
                    }

                    return { id: null, surah_id: '', ayah_start: '', ayah_end: '', score: '', status: 'passed', submission_type: 'new' };
                },
                autoMarkHadir(studentId, date) {
                    this.isDirty = true;
                    if (!studentId || !date) return;
                    let cell = this.gridData[studentId]?.dates[date];
                    if (cell && (!cell.attendance || cell.attendance === '')) {
                        cell.attendance = 'hadir';
                    }
                },
                syncAyahLimits(hafalan, studentId = null, date = null) {
                    if (studentId && date) {
                        this.autoMarkHadir(studentId, date);
                    }
                    if (!hafalan.surah_id) return;
                    const details = this.surahDetails[hafalan.surah_id];
                    if (details) {
                        if (!hafalan.ayah_start) hafalan.ayah_start = 1;
                        hafalan.ayah_end = details.totalAyah;
                    }
                },
                syncUmmiAyahLimits(hafalan, studentId = null, date = null) {
                    if (studentId && date) {
                        this.autoMarkHadir(studentId, date);
                    }
                    if (!hafalan.surah_id) return;
                    const details = this.surahDetails[hafalan.surah_id];
                    if (details) {
                        hafalan.ayah = '1-' + details.totalAyah;
                    }
                },
                handleAttendanceChange(studentId, date, value) {
                    this.isDirty = true;
                    let cell = this.gridData[studentId].dates[date];
                    cell.attendance = value;
                    if (value === 'hadir') {
                        if (cell.hafalans.length === 1 && !cell.hafalans[0].surah_id) {
                            let autoNext = this.getNextHafalan(studentId, []);
                            if (autoNext.surah_id) {
                                cell.hafalans[0].surah_id = autoNext.surah_id;
                                cell.hafalans[0].ayah_start = autoNext.ayah_start;
                                cell.hafalans[0].ayah_end = autoNext.ayah_end;
                            }
                        }
                    } else {
                        // Clear fields if absent
                        cell.hafalans.forEach(h => {
                            h.surah_id = '';
                            h.ayah_start = '';
                            h.ayah_end = '';
                            h.score = '';
                        });
                        cell.ummi_jilid = '';
                        cell.ummi_halaman = '';
                        cell.materi = '';
                        cell.nilai = '';
                        cell.ummiHafalans.forEach(uh => {
                            uh.surah_id = '';
                            uh.ayah = '';
                        });
                    }
                },
                jumpToToday() {
                    if (this.dates.includes(this.todayDate)) {
                        this.scrollToColumn(this.todayDate);
                    } else {
                        if (confirm('Tanggal hari ini (' + this.formatDateIndo(this.todayDate) + ') tidak ada dalam filter aktif saat ini. Buka lembar kerja bulan ini?')) {
                            window.location.href = "{{ route('spreadsheet-input.index') }}?class_room_id=" + this.selectedClass + "&month=" + this.currentMonth + "&week=all";
                        }
                    }
                },
                scrollToColumn(dateStr) {
                    this.selectedMobileDate = dateStr;
                    this.$nextTick(() => {
                        const col = document.getElementById('col-header-' + dateStr);
                        if (col) {
                            col.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                            col.classList.add('ring-2', 'ring-teal-500');
                            setTimeout(() => col.classList.remove('ring-2', 'ring-teal-500'), 1500);
                        }
                    });
                },
                formatDateIndo(dateStr) {
                    if (!dateStr) return '';
                    const parts = dateStr.split('-');
                    if (parts.length !== 3) return dateStr;
                    return `${parts[2]}/${parts[1]}/${parts[0]}`;
                },
                submitForm() {
                    if (this.isSaving) return;
                    this.isSaving = true;
                    this.isDirty = false;
                    window._hasUnsavedDraft = false;
                    localStorage.removeItem(this.draftKey);

                    this.$nextTick(() => {
                        const form = document.getElementById('spreadsheet-form');
                        if (form) {
                            form.submit();
                        } else {
                            this.isSaving = false;
                        }
                    });
                }
            }));
        });
    </script>

    <div class="py-4 sm:py-6" x-data="spreadsheetData">
        <div class="max-w-7xl mx-auto space-y-5 sm:space-y-6">

            <!-- UNIFIED INPUT HUB TABS -->
            @include('partials.tahfizh-input-nav-tabs', ['activeTab' => 'spreadsheet'])

            <!-- FILTER PANEL -->
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm rounded-2xl p-4 sm:p-5">
                <form method="GET" action="{{ route('spreadsheet-input.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4 items-end">
                    <div>
                        <label for="class_room_id" class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-zinc-300 mb-1.5">
                            Kelas Halaqoh
                        </label>
                        <select id="class_room_id" name="class_room_id" x-model="selectedClass" onchange="this.form.submit()" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 bg-transparent text-xs sm:text-sm py-2.5 px-3 focus:border-teal-500 focus:ring-teal-500 dark:text-white font-medium cursor-pointer shadow-xs">
                            @foreach ($classRooms as $class)
                                <option value="{{ $class->id }}" class="dark:bg-zinc-900">{{ $class->name }} ({{ $class->program?->name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-zinc-300 mb-1.5">
                            Pilih Bulan & Tahun
                        </label>
                        {{-- Dropdown Bulan/Tahun biasa dipakai, bukan <input type="month"> -- rendering
                             input type="month" tidak konsisten (kadang jadi kotak teks kosong tanpa
                             picker) di sebagian browser HP, walau tampil normal di iPad/laptop. --}}
                        <div class="grid grid-cols-2 gap-2">
                            <select
                                name="month_num"
                                x-model="selectedMonthNum"
                                @change="selectedMonth = selectedYearNum + '-' + selectedMonthNum; $nextTick(() => $el.form.submit())"
                                class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 bg-transparent text-xs sm:text-sm py-2.5 px-3 focus:border-teal-500 focus:ring-teal-500 dark:text-white font-medium cursor-pointer shadow-xs"
                            >
                                @foreach (['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'] as $num => $label)
                                    <option value="{{ $num }}" class="dark:bg-zinc-900">{{ $label }}</option>
                                @endforeach
                            </select>
                            <select
                                name="year_num"
                                x-model="selectedYearNum"
                                @change="selectedMonth = selectedYearNum + '-' + selectedMonthNum; $nextTick(() => $el.form.submit())"
                                class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 bg-transparent text-xs sm:text-sm py-2.5 px-3 focus:border-teal-500 focus:ring-teal-500 dark:text-white font-medium cursor-pointer shadow-xs"
                            >
                                @foreach (range((int) date('Y') - 1, (int) date('Y') + 1) as $y)
                                    <option value="{{ $y }}" class="dark:bg-zinc-900">{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="month" x-model="selectedMonth">
                    </div>
                    @if (!$isWeekly)
                    <div>
                        <label for="week" class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-zinc-300 mb-1.5">
                            Pilih Pekan
                        </label>
                        <select id="week" name="week" onchange="this.form.submit()" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 bg-transparent text-xs sm:text-sm py-2.5 px-3 focus:border-teal-500 focus:ring-teal-500 dark:text-white font-medium cursor-pointer shadow-xs">
                            <option value="all" {{ $selectedWeek === 'all' ? 'selected' : '' }} class="dark:bg-zinc-900">Semua Pekan (Scroll)</option>
                            @foreach ($weeksList as $index => $w)
                                <option value="{{ $index }}" {{ $selectedWeek == $index ? 'selected' : '' }} class="dark:bg-zinc-900">
                                    {{ $w['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div>
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-teal-600 hover:bg-teal-700 active:scale-95 text-white rounded-xl text-xs sm:text-sm font-bold shadow-sm transition cursor-pointer min-h-[42px]">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <span>Tampilkan Kelas</span>
                        </button>
                    </div>
                </form>

                <!-- Active Dates Number Toggles -->
                @if (count($dates) > 0)
                @php
                    $todayDate = now()->setTimezone(config('app.timezone', 'Asia/Jakarta'))->toDateString();
                @endphp
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-zinc-800">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2.5">
                        <div class="flex items-center gap-2 flex-wrap">
                            <label class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-zinc-300">
                                <x-heroicon-o-calendar class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" />
                                <span>Tanggal Aktif ({{ $selectedWeek === 'all' ? 'Semua Pekan' : 'Pekan ' . $selectedWeek }}):</span>
                            </label>
                            <button
                                type="button"
                                @click="jumpToToday()"
                                title="Lompat ke tanggal hari ini"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-teal-50 hover:bg-teal-100 dark:bg-teal-950/50 dark:hover:bg-teal-900/60 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-700/60 rounded-xl text-[11px] font-bold shadow-xs transition cursor-pointer active:scale-95"
                            >
                                <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                                <span>Lompat ke Hari Ini</span>
                            </button>
                        </div>
                        <span class="text-[11px] text-gray-500 dark:text-zinc-400 hidden sm:inline">
                            Klik tanggal untuk fokus ke kolom/hari tersebut
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-1.5">
                        @foreach ($dates as $d)
                            @php
                                $dDayNum = date('j', strtotime($d));
                                $dDayName = \Carbon\Carbon::parse($d)->translatedFormat('D');
                                $isToday = ($d === $todayDate);
                            @endphp
                            <button
                                type="button"
                                @click="scrollToColumn('{{ $d }}')"
                                :class="selectedMobileDate === '{{ $d }}' ? 'bg-indigo-600 text-white font-black ring-2 ring-indigo-500 scale-105 shadow-sm' : '{{ $isToday ? 'bg-teal-50 dark:bg-teal-950/40 text-teal-800 dark:text-teal-200 font-bold border-2 border-teal-400 dark:border-teal-600' : 'bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-700 border border-gray-200 dark:border-zinc-700/60' }}'"
                                class="px-2.5 py-1 rounded-xl text-xs transition cursor-pointer flex items-center gap-1 min-w-[40px] justify-center relative"
                                title="{{ $isToday ? 'Hari Ini (' . $dDayNum . ' ' . $dDayName . ')' : '' }}"
                            >
                                <span class="font-bold text-xs">{{ $dDayNum }}</span>
                                <span class="text-[9px] opacity-75 uppercase">{{ $dDayName }}</span>
                                @if ($isToday)
                                    <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-teal-500 rounded-full border-2 border-white dark:border-zinc-900" title="Hari Ini"></span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- DRAFT RECOVERY BANNER -->
            <div x-show="hasDraftAvailable"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 class="bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-700/60 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm"
                 style="display: none;">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-amber-900 dark:text-amber-200">Ditemukan Draf Belum Tersimpan</h4>
                        <p class="text-[11px] text-amber-700 dark:text-amber-300/80">Ada perubahan nilai / presensi dari sesi sebelumnya (<span x-text="draftTimestamp" class="font-bold"></span>) yang belum tersimpan ke server. Pulihkan data ini?</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" @click="restoreDraft()" class="px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 active:scale-95 text-white rounded-xl text-xs font-bold transition shadow-xs cursor-pointer flex items-center gap-1.5">
                        <x-heroicon-m-arrow-path class="w-3.5 h-3.5" />
                        <span>Pulihkan Data Draf</span>
                    </button>
                    <button type="button" @click="dismissDraft()" class="px-3.5 py-1.5 bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 border border-zinc-200 dark:border-zinc-700 rounded-xl text-xs font-semibold transition cursor-pointer">
                        Abaikan
                    </button>
                </div>
            </div>

            <!-- TABS & SAVE ACTION -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-4 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 p-3 sm:p-4 rounded-2xl shadow-sm">
                <!-- Worksheet Tabs -->
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button type="button" @click="tab = 'hafalan'" :class="tab === 'hafalan' ? 'bg-teal-600 text-white shadow-md shadow-teal-500/20' : 'bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 hover:bg-gray-200 dark:hover:bg-zinc-700'" class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition cursor-pointer flex items-center justify-center gap-1.5">
                        <x-heroicon-o-book-open class="w-4 h-4" /> Lembar Setoran Al-Qur'an
                    </button>
                    <button type="button" @click="tab = 'ummi'" :class="tab === 'ummi' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-500/20' : 'bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 hover:bg-gray-200 dark:hover:bg-zinc-700'" class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider transition cursor-pointer flex items-center justify-center gap-1.5">
                        <x-heroicon-o-sparkles class="w-4 h-4" /> Lembar Progres UMMI
                    </button>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button
                        type="button"
                        @click="jumpToToday()"
                        class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 bg-gray-100 hover:bg-teal-50 dark:bg-zinc-800 dark:hover:bg-teal-950/40 text-gray-700 hover:text-teal-700 dark:text-zinc-300 dark:hover:text-teal-300 border border-gray-200 dark:border-zinc-700 rounded-xl text-xs font-bold transition cursor-pointer min-h-[42px] active:scale-95"
                        title="Fokus / Lompat ke kolom hari ini"
                    >
                        <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
                        <x-heroicon-o-calendar class="w-4 h-4 text-teal-600 dark:text-teal-400" />
                        <span>Hari Ini</span>
                    </button>
                    <button type="button" @click="submitForm()" :disabled="isSaving" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 active:scale-95 disabled:bg-gray-400 text-white rounded-xl text-xs sm:text-sm font-bold shadow-md shadow-emerald-500/20 transition cursor-pointer gap-2 min-h-[42px]">
                        <template x-if="!isSaving">
                            <span class="inline-flex items-center gap-2">
                                <x-heroicon-o-arrow-down-on-square class="w-4 h-4" /> Simpan Perubahan Kelas
                            </span>
                        </template>
                        <template x-if="isSaving">
                            <span class="inline-flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg> Menyimpan Perubahan...
                            </span>
                        </template>
                    </button>
                </div>
            </div>

            @if ($students->isEmpty())
                <div class="p-8 text-center bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl">
                    <p class="text-sm text-gray-500 dark:text-zinc-400">Tidak ada murid aktif di kelas halaqoh terpilih.</p>
                </div>
            @else
                <!-- FORM UTAMA -->
                <form id="spreadsheet-form" method="POST" action="{{ route('spreadsheet-input.save') }}" @input="isDirty = true" @change="isDirty = true">
                    @csrf
                    <input type="hidden" name="class_room_id" :value="selectedClass">
                    <input type="hidden" name="month" :value="selectedMonth">
                    <input type="hidden" name="type" :value="tab">
                    <input type="hidden" name="week" value="{{ $selectedWeek }}">

                    <!-- ========================================== -->
                    <!-- DESKTOP / TABLET SPREADSHEET VIEW          -->
                    <!-- ========================================== -->
                    <div class="hidden md:block bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl overflow-x-auto overflow-y-auto touch-scroll max-h-[calc(100dvh-14rem)] overscroll-contain shadow-sm">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 table-fixed border-collapse">
                            <thead class="sticky top-0 z-30 bg-gray-100 dark:bg-zinc-800 shadow-sm">
                                <tr>
                                    <th class="sticky top-0 left-0 z-40 bg-gray-100 dark:bg-zinc-800 px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider w-48 border-r-2 border-b border-gray-300 dark:border-zinc-700 shadow-[4px_0_8px_-2px_rgba(0,0,0,0.08)] dark:shadow-[4px_0_8px_-2px_rgba(0,0,0,0.4)]">
                                        Nama Murid
                                    </th>
                                    <template x-for="col in columns" :key="col.date">
                                        <th
                                            :id="'col-header-' + col.date"
                                            class="sticky top-0 z-30 px-4 py-3 text-center text-xs font-bold uppercase tracking-wider w-64 border-r border-b transition-colors"
                                            :class="col.date === todayDate ? 'bg-teal-100/90 dark:bg-teal-950/80 text-teal-900 dark:text-teal-200 border-teal-300 dark:border-teal-700' : 'bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 border-gray-200 dark:border-zinc-700'"
                                        >
                                            <div class="flex items-center justify-center gap-1.5">
                                                <span x-text="col.label" class="block"></span>
                                                <template x-if="col.date === todayDate">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[9px] font-extrabold bg-teal-600 text-white shadow-xs tracking-normal">
                                                        HARI INI
                                                    </span>
                                                </template>
                                            </div>
                                            <span x-text="col.sub_label" class="block text-[10px] font-medium normal-case mt-0.5" :class="col.date === todayDate ? 'text-teal-700 dark:text-teal-300 font-bold' : 'text-gray-400'"></span>
                                        </th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-800 bg-white dark:bg-zinc-900">
                                <template x-for="student in students" :key="student.id">
                                    <tr class="group hover:bg-gray-50/60 dark:hover:bg-zinc-850/40 transition-colors">
                                        <!-- Sticky Name Column with Distinct Freeze Line & Shadow -->
                                        <td class="sticky left-0 z-20 bg-white dark:bg-zinc-900 group-hover:bg-gray-50 dark:group-hover:bg-zinc-850 px-4 py-3 border-r-2 border-b border-gray-300 dark:border-zinc-700 font-bold text-xs text-gray-900 dark:text-zinc-200 shadow-[4px_0_8px_-2px_rgba(0,0,0,0.08)] dark:shadow-[4px_0_8px_-2px_rgba(0,0,0,0.4)] transition-colors">
                                            <span x-text="student.name"></span>
                                            <span class="block text-[10px] text-gray-400 font-medium mt-0.5" x-text="student.tahfizh_level === 'ummi' ? 'Level: UMMI' : 'Level: ' + student.tahfizh_level"></span>
                                        </td>

                                        <!-- Date Columns -->
                                        <template x-for="date in dates" :key="date">
                                            <td
                                                class="p-3 border-r border-b dark:border-zinc-800 align-top transition-colors"
                                                :class="date === todayDate ? 'bg-teal-50/25 dark:bg-teal-950/15' : ''"
                                            >
                                                <div class="space-y-2" x-data="{ cell: gridData[student.id].dates[date] }">
                                                    <!-- PRESENSI PILLS (ATAS) -->
                                                    <div class="flex items-center justify-between border-b dark:border-zinc-800 pb-2">
                                                        <div class="flex items-center gap-1 w-full justify-between">
                                                            <button type="button" @click="handleAttendanceChange(student.id, date, 'hadir')" :class="cell.attendance === 'hadir' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-transparent text-gray-400 border-gray-300 dark:border-zinc-700'" class="px-1.5 py-0.5 text-[10px] font-extrabold border rounded cursor-pointer transition-colors w-8 text-center">H</button>
                                                            <button type="button" @click="handleAttendanceChange(student.id, date, 'sakit')" :class="cell.attendance === 'sakit' ? 'bg-amber-500 text-white border-amber-500' : 'bg-transparent text-gray-400 border-gray-300 dark:border-zinc-700'" class="px-1.5 py-0.5 text-[10px] font-extrabold border rounded cursor-pointer transition-colors w-8 text-center">S</button>
                                                            <button type="button" @click="handleAttendanceChange(student.id, date, 'izin')" :class="cell.attendance === 'izin' ? 'bg-blue-500 text-white border-blue-500' : 'bg-transparent text-gray-400 border-gray-300 dark:border-zinc-700'" class="px-1.5 py-0.5 text-[10px] font-extrabold border rounded cursor-pointer transition-colors w-8 text-center">I</button>
                                                            <button type="button" @click="handleAttendanceChange(student.id, date, 'alpa')" :class="cell.attendance === 'alpa' ? 'bg-rose-600 text-white border-rose-600' : 'bg-transparent text-gray-400 border-gray-300 dark:border-zinc-700'" class="px-1.5 py-0.5 text-[10px] font-extrabold border rounded cursor-pointer transition-colors w-8 text-center">A</button>
                                                        </div>
                                                        <input type="hidden" :name="isMobileView ? '' : 'records[' + student.id + '][dates][' + date + '][attendance]'" :value="cell.attendance" :disabled="isMobileView">
                                                    </div>

                                                    <!-- INPUT FIELDS (DENGAN LOGIKA ACTIVE/DISABLED) -->
                                                    <div :class="(cell.attendance && cell.attendance !== 'hadir') ? 'opacity-30 pointer-events-none' : ''" class="transition-opacity space-y-2">
                                                        
                                                        <!-- TAB 1: SETORAN AL-QUR'AN (Sama untuk semua murid) -->
                                                        <div x-show="tab === 'hafalan'" class="space-y-2">
                                                            <template x-for="(h, hIndex) in cell.hafalans" :key="hIndex">
                                                                <div class="p-2 bg-gray-50/50 dark:bg-zinc-800/40 border border-gray-255 dark:border-zinc-800 rounded-lg relative space-y-1.5">
                                                                    <!-- Surah select -->
                                                                    <select :name="isMobileView ? '' : 'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][surah_id]'" x-model="h.surah_id" @change="syncAyahLimits(h, student.id, date)" :disabled="isMobileView || tab !== 'hafalan' || (cell.attendance && cell.attendance !== 'hadir')" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-2 py-1 dark:text-white">
                                                                        <option value="" class="dark:bg-zinc-900">Pilih Surah</option>
                                                                        @foreach ($surahs as $s)
                                                                            <option value="{{ $s->id }}" class="dark:bg-zinc-900">{{ $s->number }}. {{ $s->name_latin }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                    <!-- Ayat range -->
                                                                    <div class="grid grid-cols-2 gap-1">
                                                                        <input type="number" :name="isMobileView ? '' : 'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][ayah_start]'" x-model.number="h.ayah_start" @input="autoMarkHadir(student.id, date)" placeholder="Awal" :disabled="isMobileView || tab !== 'hafalan' || (cell.attendance && cell.attendance !== 'hadir')" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-2 py-0.5 dark:text-white">
                                                                        <input type="number" :name="isMobileView ? '' : 'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][ayah_end]'" x-model.number="h.ayah_end" @input="autoMarkHadir(student.id, date)" placeholder="Akhir" :disabled="isMobileView || tab !== 'hafalan' || (cell.attendance && cell.attendance !== 'hadir')" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-2 py-0.5 dark:text-white">
                                                                    </div>
                                                                    <!-- Score & Status -->
                                                                    <div class="grid grid-cols-2 gap-1">
                                                                        <select :name="isMobileView ? '' : 'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][score]'" x-model="h.score" :disabled="isMobileView || tab !== 'hafalan' || (cell.attendance && cell.attendance !== 'hadir')" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-1 py-0.5 dark:text-white">
                                                                            <option value="" class="dark:bg-zinc-900">Nilai</option>
                                                                            <option value="95" class="dark:bg-zinc-900">A</option>
                                                                            <option value="85" class="dark:bg-zinc-900">B</option>
                                                                            <option value="75" class="dark:bg-zinc-900">C</option>
                                                                            <option value="65" class="dark:bg-zinc-900">D</option>
                                                                        </select>
                                                                        <select :name="isMobileView ? '' : 'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][status]'" x-model="h.status" :disabled="isMobileView || tab !== 'hafalan' || (cell.attendance && cell.attendance !== 'hadir')" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-1 py-0.5 dark:text-white">
                                                                            <option value="passed" class="dark:bg-zinc-900">Lulus</option>
                                                                            <option value="repeat" class="dark:bg-zinc-900">Ulang</option>
                                                                            <option value="needs_improvement" class="dark:bg-zinc-900">Revisi</option>
                                                                        </select>
                                                                    </div>
                                                                    <!-- Hidden tracking fields -->
                                                                    <input type="hidden" :name="isMobileView ? '' : 'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][id]'" :value="h.id" :disabled="isMobileView || tab !== 'hafalan'">
                                                                    <input type="hidden" :name="isMobileView ? '' : 'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][submission_type]'" :value="h.submission_type" :disabled="isMobileView || tab !== 'hafalan'">
                                                                    <!-- Remove button -->
                                                                    <template x-if="cell.hafalans.length > 1">
                                                                        <button type="button" @click="isDirty = true; cell.hafalans.splice(hIndex, 1)" class="absolute -top-1.5 -right-1.5 bg-red-100 hover:bg-red-200 dark:bg-zinc-800 text-red-650 rounded-full w-4 h-4 flex items-center justify-center text-[10px] font-bold border border-red-200 dark:border-zinc-700 cursor-pointer">×</button>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                            <!-- Add Surah Button -->
                                                            <button type="button" @click="isDirty = true; cell.hafalans.push(getNextHafalan(student.id, cell.hafalans))" :disabled="tab !== 'hafalan' || cell.attendance !== 'hadir'" class="w-full inline-flex items-center justify-center py-1 bg-indigo-50 hover:bg-indigo-100 dark:bg-zinc-800 dark:hover:bg-zinc-750 text-indigo-650 dark:text-indigo-400 rounded-md text-[10px] font-bold border border-indigo-200 dark:border-zinc-800 transition cursor-pointer">
                                                                + Tambah Surat
                                                            </button>
                                                        </div>

                                                        <!-- TAB 2: PROGRES UMMI LENGKAP (Kondisional berdasarkan level murid) -->
                                                        <div x-show="tab === 'ummi'" class="space-y-2">
                                                            <!-- JIKA MURID ADALAH LEVEL UMMI -->
                                                            <template x-if="student.tahfizh_level === 'ummi'">
                                                                <div class="space-y-2">
                                                                    <!-- Jilid & Halaman -->
                                                                    <div class="grid grid-cols-2 gap-1">
                                                                        <select :name="'records[' + student.id + '][dates][' + date + '][ummi_jilid]'" x-model="cell.ummi_jilid" :disabled="tab !== 'ummi' || cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-1 py-1 dark:text-white">
                                                                            <option value="" class="dark:bg-zinc-900">Buku/Jilid</option>
                                                                            <option value="Jilid 1" class="dark:bg-zinc-900">Jilid 1</option>
                                                                            <option value="Jilid 2" class="dark:bg-zinc-900">Jilid 2</option>
                                                                            <option value="Jilid 3" class="dark:bg-zinc-900">Jilid 3</option>
                                                                            <option value="Al-Qur'an" class="dark:bg-zinc-900">Al-Qur'an</option>
                                                                            <option value="Ghoroib" class="dark:bg-zinc-900">Ghoroib</option>
                                                                            <option value="Tajwid" class="dark:bg-zinc-900">Tajwid</option>
                                                                        </select>
                                                                        <input type="text" :name="'records[' + student.id + '][dates][' + date + '][ummi_halaman]'" x-model="cell.ummi_halaman" placeholder="Halaman" :disabled="tab !== 'ummi' || cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-2 py-1 dark:text-white">
                                                                    </div>
                                                                    <!-- Materi & Nilai -->
                                                                    <div class="grid grid-cols-2 gap-1">
                                                                        <input type="text" :name="'records[' + student.id + '][dates][' + date + '][materi]'" x-model="cell.materi" placeholder="Materi" :disabled="tab !== 'ummi' || cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-2 py-1 dark:text-white">
                                                                        <select :name="'records[' + student.id + '][dates][' + date + '][nilai]'" x-model="cell.nilai" :disabled="tab !== 'ummi' || cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-1 py-1 dark:text-white">
                                                                            <option value="" class="dark:bg-zinc-900">Nilai</option>
                                                                            <option value="A+" class="dark:bg-zinc-900">A+</option>
                                                                            <option value="A" class="dark:bg-zinc-900">A</option>
                                                                            <option value="B+" class="dark:bg-zinc-900">B+</option>
                                                                            <option value="B" class="dark:bg-zinc-900">B</option>
                                                                            <option value="B-" class="dark:bg-zinc-900">B-</option>
                                                                            <option value="C+" class="dark:bg-zinc-900">C+</option>
                                                                            <option value="C" class="dark:bg-zinc-900">C</option>
                                                                            <option value="D" class="dark:bg-zinc-900">D</option>
                                                                        </select>
                                                                    </div>
                                                                    <!-- Hafalan list in UMMI cell -->
                                                                    <div class="border-t border-gray-150 dark:border-zinc-800 pt-2 space-y-1.5">
                                                                        <span class="text-[9px] font-bold text-gray-400 dark:text-zinc-500 block">SETORAN HAFALAN UMMI:</span>
                                                                        <template x-for="(h, hIndex) in cell.ummiHafalans" :key="hIndex">
                                                                            <div class="p-1.5 bg-gray-50/50 dark:bg-zinc-850 border border-gray-200 dark:border-zinc-800 rounded relative space-y-1">
                                                                                <select :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][surah_id]'" x-model="h.surah_id" @change="syncUmmiAyahLimits(h)" :disabled="tab !== 'ummi' || cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[10px] px-1 py-0.5 dark:text-white">
                                                                                    <option value="" class="dark:bg-zinc-900">Pilih Surah</option>
                                                                                    @foreach ($surahs as $s)
                                                                                        <option value="{{ $s->id }}" class="dark:bg-zinc-900">{{ $s->number }}. {{ $s->name_latin }}</option>
                                                                                    @endforeach
                                                                                </select>
                                                                                <input type="text" :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][ayah]'" x-model="h.ayah" placeholder="Cth: 1-5" :disabled="tab !== 'ummi' || cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[10px] px-2 py-0.5 dark:text-white">
                                                                                <input type="hidden" :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][id]'" :value="h.id" :disabled="tab !== 'ummi'">
                                                                                <!-- Remove button -->
                                                                                <template x-if="cell.ummiHafalans.length > 1">
                                                                                    <button type="button" @click="isDirty = true; cell.ummiHafalans.splice(hIndex, 1)" class="absolute -top-1.5 -right-1.5 bg-red-150 text-red-650 rounded-full w-3.5 h-3.5 flex items-center justify-center text-[9px] font-bold border border-red-200 dark:border-zinc-800 cursor-pointer">×</button>
                                                                                </template>
                                                                            </div>
                                                                        </template>
                                                                        <button type="button" @click="isDirty = true; cell.ummiHafalans.push({ id: null, surah_id: '', ayah: '' })" :disabled="tab !== 'ummi' || cell.attendance !== 'hadir'" class="w-full py-0.5 bg-emerald-50 hover:bg-emerald-100 dark:bg-zinc-800 dark:hover:bg-zinc-750 text-emerald-650 dark:text-emerald-450 border border-emerald-200 dark:border-zinc-800 rounded text-[9px] font-extrabold cursor-pointer transition">
                                                                            + Hafalan
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </template>

                                                            <!-- JIKA MURID BUKAN LEVEL UMMI (HANYA HAFALAN BIASA) -->
                                                            <template x-if="student.tahfizh_level !== 'ummi'">
                                                                <div class="space-y-2">
                                                                    <template x-for="(h, hIndex) in cell.hafalans" :key="hIndex">
                                                                        <div class="p-2 bg-gray-50/50 dark:bg-zinc-800/40 border border-gray-255 dark:border-zinc-800 rounded-lg relative space-y-1.5">
                                                                            <!-- Surah select -->
                                                                            <select :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][surah_id]'" x-model="h.surah_id" @change="syncAyahLimits(h)" :disabled="tab !== 'hafalan' || cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-2 py-1 dark:text-white">
                                                                                <option value="" class="dark:bg-zinc-900">Pilih Surah</option>
                                                                                @foreach ($surahs as $s)
                                                                                    <option value="{{ $s->id }}" class="dark:bg-zinc-900">{{ $s->number }}. {{ $s->name_latin }}</option>
                                                                                @endforeach
                                                                            </select>
                                                                            <!-- Ayat range -->
                                                                            <div class="grid grid-cols-2 gap-1">
                                                                                <input type="number" :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][ayah_start]'" x-model.number="h.ayah_start" placeholder="Awal" :disabled="tab !== 'hafalan' || cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-2 py-0.5 dark:text-white">
                                                                                <input type="number" :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][ayah_end]'" x-model.number="h.ayah_end" placeholder="Akhir" :disabled="tab !== 'hafalan' || cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-2 py-0.5 dark:text-white">
                                                                            </div>
                                                                            <!-- Score & Status -->
                                                                            <div class="grid grid-cols-2 gap-1">
                                                                                <select :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][score]'" x-model="h.score" :disabled="tab !== 'hafalan' || cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-1 py-0.5 dark:text-white">
                                                                                    <option value="" class="dark:bg-zinc-900">Nilai</option>
                                                                                    <option value="95" class="dark:bg-zinc-900">A</option>
                                                                                    <option value="85" class="dark:bg-zinc-900">B</option>
                                                                                    <option value="75" class="dark:bg-zinc-900">C</option>
                                                                                    <option value="65" class="dark:bg-zinc-900">D</option>
                                                                                </select>
                                                                                <select :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][status]'" x-model="h.status" :disabled="tab !== 'hafalan' || cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-1 py-0.5 dark:text-white">
                                                                                    <option value="passed" class="dark:bg-zinc-900">Lulus</option>
                                                                                    <option value="repeat" class="dark:bg-zinc-900">Ulang</option>
                                                                                    <option value="needs_improvement" class="dark:bg-zinc-900">Revisi</option>
                                                                                </select>
                                                                            </div>
                                                                            <input type="hidden" :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][id]'" :value="h.id" :disabled="tab !== 'hafalan'">
                                                                            <input type="hidden" :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][submission_type]'" :value="h.submission_type" :disabled="tab !== 'hafalan'">
                                                                            <!-- Remove button -->
                                                                            <template x-if="cell.hafalans.length > 1">
                                                                                <button type="button" @click="isDirty = true; cell.hafalans.splice(hIndex, 1)" class="absolute -top-1.5 -right-1.5 bg-red-100 hover:bg-red-200 dark:bg-zinc-800 text-red-650 rounded-full w-4 h-4 flex items-center justify-center text-[10px] font-bold border border-red-200 dark:border-zinc-700 cursor-pointer">×</button>
                                                                            </template>
                                                                        </div>
                                                                    </template>
                                                                    <button type="button" @click="isDirty = true; cell.hafalans.push(getNextHafalan(student.id, cell.hafalans))" :disabled="tab !== 'hafalan' || cell.attendance !== 'hadir'" class="w-full inline-flex items-center justify-center py-1 bg-indigo-50 hover:bg-indigo-100 dark:bg-zinc-800 dark:hover:bg-zinc-750 text-indigo-650 dark:text-indigo-400 rounded-md text-[10px] font-bold border border-indigo-200 dark:border-zinc-800 transition cursor-pointer">
                                                                        + Tambah Surat
                                                                    </button>
                                                                </div>
                                                            </template>
                                                        </div>

                                                    </div>
                                                </div>
                                            </td>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- ========================================== -->
                    <!-- MOBILE VIEW (Layar HP / Tegak)             -->
                    <!-- ========================================== -->
                    <div class="md:hidden space-y-4">

                        <!-- Cards per Student -->
                        <div class="space-y-4">
                            <template x-for="student in students" :key="student.id + '-' + selectedMobileDate">
                                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl p-4 shadow-sm space-y-4" x-data="{ get cell() { return gridData[student.id].dates[selectedMobileDate] } }">
                                    <!-- Name Header -->
                                    <div class="flex items-center justify-between border-b dark:border-zinc-800 pb-2.5">
                                        <div>
                                            <h4 class="font-extrabold text-sm text-gray-900 dark:text-white" x-text="student.name"></h4>
                                            <p class="text-[10px] text-gray-400 mt-0.5" x-text="student.className ? student.className : '-'"></p>
                                        </div>
                                        <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 font-bold text-[10px]" x-text="student.tahfizh_level === 'ummi' ? 'Level: UMMI' : 'Level: ' + student.tahfizh_level"></span>
                                    </div>

                                    <!-- Attendance Selection -->
                                    <div class="space-y-1.5">
                                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Status Kehadiran</span>
                                        <div class="grid grid-cols-4 gap-2">
                                            <button type="button" @click="handleAttendanceChange(student.id, selectedMobileDate, 'hadir')" :class="cell.attendance === 'hadir' ? 'bg-emerald-600 text-white border-emerald-600 font-extrabold' : 'bg-transparent text-gray-500 border-gray-300 dark:border-zinc-700'" class="py-2 text-xs border rounded-lg cursor-pointer text-center transition">Hadir</button>
                                            <button type="button" @click="handleAttendanceChange(student.id, selectedMobileDate, 'sakit')" :class="cell.attendance === 'sakit' ? 'bg-amber-500 text-white border-amber-500 font-extrabold' : 'bg-transparent text-gray-500 border-gray-300 dark:border-zinc-700'" class="py-2 text-xs border rounded-lg cursor-pointer text-center transition">Sakit</button>
                                            <button type="button" @click="handleAttendanceChange(student.id, selectedMobileDate, 'izin')" :class="cell.attendance === 'izin' ? 'bg-blue-500 text-white border-blue-500 font-extrabold' : 'bg-transparent text-gray-500 border-gray-300 dark:border-zinc-700'" class="py-2 text-xs border rounded-lg cursor-pointer text-center transition">Izin</button>
                                            <button type="button" @click="handleAttendanceChange(student.id, selectedMobileDate, 'alpa')" :class="cell.attendance === 'alpa' ? 'bg-rose-600 text-white border-rose-600 font-extrabold' : 'bg-transparent text-gray-500 border-gray-300 dark:border-zinc-700'" class="py-2 text-xs border rounded-lg cursor-pointer text-center transition">Alpa</button>
                                        </div>
                                        <input type="hidden" :name="!isMobileView ? '' : 'records[' + student.id + '][dates][' + selectedMobileDate + '][attendance]'" :value="cell.attendance" :disabled="!isMobileView">
                                    </div>

                                    <!-- Form Inputs (Locked if absent) -->
                                    <div :class="(cell.attendance && cell.attendance !== 'hadir') ? 'opacity-30 pointer-events-none' : ''" class="transition-opacity space-y-4">
                                        
                                        <!-- MOBILE TAB 1: SETORAN AL-QUR'AN -->
                                        <div x-show="tab === 'hafalan'" class="space-y-3">
                                            <template x-for="(h, hIndex) in cell.hafalans" :key="hIndex">
                                                <div class="bg-gray-50/50 dark:bg-zinc-800/40 p-3 rounded-lg border border-gray-255 dark:border-zinc-800 relative space-y-3">
                                                    <div>
                                                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Surah</label>
                                                        <select :name="!isMobileView ? '' : 'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][surah_id]'" x-model="h.surah_id" @change="syncAyahLimits(h, student.id, selectedMobileDate)" :disabled="!isMobileView || tab !== 'hafalan' || (cell.attendance && cell.attendance !== 'hadir')" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1.5 dark:text-white">
                                                            <option value="" class="dark:bg-zinc-900">Pilih Surah</option>
                                                            @foreach ($surahs as $s)
                                                                <option value="{{ $s->id }}" class="dark:bg-zinc-900">{{ $s->number }}. {{ $s->name_latin }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Ayat Mulai</label>
                                                            <input type="number" :name="!isMobileView ? '' : 'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][ayah_start]'" x-model.number="h.ayah_start" @input="autoMarkHadir(student.id, selectedMobileDate)" placeholder="Awal" :disabled="!isMobileView || tab !== 'hafalan' || (cell.attendance && cell.attendance !== 'hadir')" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1 dark:text-white">
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Ayat Akhir</label>
                                                            <input type="number" :name="!isMobileView ? '' : 'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][ayah_end]'" x-model.number="h.ayah_end" @input="autoMarkHadir(student.id, selectedMobileDate)" placeholder="Akhir" :disabled="!isMobileView || tab !== 'hafalan' || (cell.attendance && cell.attendance !== 'hadir')" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1 dark:text-white">
                                                        </div>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Nilai</label>
                                                            <select :name="!isMobileView ? '' : 'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][score]'" x-model="h.score" :disabled="!isMobileView || tab !== 'hafalan' || (cell.attendance && cell.attendance !== 'hadir')" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1 dark:text-white">
                                                                <option value="" class="dark:bg-zinc-900">Pilih Nilai</option>
                                                                <option value="95" class="dark:bg-zinc-900">A</option>
                                                                <option value="85" class="dark:bg-zinc-900">B</option>
                                                                <option value="75" class="dark:bg-zinc-900">C</option>
                                                                <option value="65" class="dark:bg-zinc-900">D</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Status</label>
                                                            <select :name="!isMobileView ? '' : 'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][status]'" x-model="h.status" :disabled="!isMobileView || tab !== 'hafalan' || (cell.attendance && cell.attendance !== 'hadir')" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1 dark:text-white">
                                                                <option value="passed" class="dark:bg-zinc-900">Lulus</option>
                                                                <option value="repeat" class="dark:bg-zinc-900">Ulang</option>
                                                                <option value="needs_improvement" class="dark:bg-zinc-900">Revisi</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <!-- Hidden variables -->
                                                    <input type="hidden" :name="!isMobileView ? '' : 'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][id]'" :value="h.id" :disabled="!isMobileView || tab !== 'hafalan'">
                                                    <!-- Delete button -->
                                                    <template x-if="cell.hafalans.length > 1">
                                                        <button type="button" @click="isDirty = true; cell.hafalans.splice(hIndex, 1)" class="absolute top-2 right-2 text-rose-650 text-xs font-bold bg-white dark:bg-zinc-800 border border-gray-255 dark:border-zinc-700 rounded-full w-5 h-5 flex items-center justify-center cursor-pointer shadow-sm">×</button>
                                                    </template>
                                                </div>
                                            </template>
                                            <button type="button" @click="isDirty = true; cell.hafalans.push({ id: null, surah_id: '', ayah_start: '', ayah_end: '', score: '', status: 'passed', submission_type: 'new' })" :disabled="tab !== 'hafalan' || cell.attendance !== 'hadir'" class="w-full py-2 bg-indigo-50 dark:bg-zinc-800 text-indigo-650 dark:text-indigo-400 border border-indigo-200 dark:border-zinc-700 rounded-lg text-xs font-bold transition cursor-pointer">
                                                + Tambah Surat Setoran
                                            </button>
                                        </div>

                                        <!-- MOBILE TAB 2: PROGRES UMMI LENGKAP -->
                                        <div x-show="tab === 'ummi'" class="space-y-3">
                                            <!-- JIKA LEVEL UMMI -->
                                            <template x-if="student.tahfizh_level === 'ummi'">
                                                <div class="space-y-3">
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Buku/Jilid</label>
                                                            <select :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][ummi_jilid]'" x-model="cell.ummi_jilid" :disabled="tab !== 'ummi' || cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1.5 dark:text-white">
                                                                <option value="" class="dark:bg-zinc-900">Buku/Jilid</option>
                                                                <option value="Jilid 1" class="dark:bg-zinc-900">Jilid 1</option>
                                                                <option value="Jilid 2" class="dark:bg-zinc-900">Jilid 2</option>
                                                                <option value="Jilid 3" class="dark:bg-zinc-900">Jilid 3</option>
                                                                <option value="Al-Qur'an" class="dark:bg-zinc-900">Al-Qur'an</option>
                                                                <option value="Ghoroib" class="dark:bg-zinc-900">Ghoroib</option>
                                                                <option value="Tajwid" class="dark:bg-zinc-900">Tajwid</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Halaman</label>
                                                            <input type="text" :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][ummi_halaman]'" x-model="cell.ummi_halaman" placeholder="Hal" :disabled="tab !== 'ummi' || cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1.5 dark:text-white">
                                                        </div>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Materi</label>
                                                            <input type="text" :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][materi]'" x-model="cell.materi" placeholder="Materi" :disabled="tab !== 'ummi' || cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1.5 dark:text-white">
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Nilai</label>
                                                            <select :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][nilai]'" x-model="cell.nilai" :disabled="tab !== 'ummi' || cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1.5 dark:text-white">
                                                                <option value="" class="dark:bg-zinc-900">Pilih Nilai</option>
                                                                <option value="A+" class="dark:bg-zinc-900">A+</option>
                                                                <option value="A" class="dark:bg-zinc-900">A</option>
                                                                <option value="B+" class="dark:bg-zinc-900">B+</option>
                                                                <option value="B" class="dark:bg-zinc-900">B</option>
                                                                <option value="B-" class="dark:bg-zinc-900">B-</option>
                                                                <option value="C+" class="dark:bg-zinc-900">C+</option>
                                                                <option value="C" class="dark:bg-zinc-900">C</option>
                                                                <option value="D" class="dark:bg-zinc-900">D</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <!-- UMMI hafalan on mobile -->
                                                    <div class="border-t border-gray-150 dark:border-zinc-800 pt-3.5 space-y-2">
                                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Setoran Hafalan UMMI</span>
                                                        <template x-for="(h, hIndex) in cell.ummiHafalans" :key="hIndex">
                                                            <div class="bg-gray-50/50 dark:bg-zinc-855 p-3 rounded-lg border border-gray-200 dark:border-zinc-800 relative space-y-2">
                                                                <select :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][surah_id]'" x-model="h.surah_id" @change="syncUmmiAyahLimits(h)" :disabled="tab !== 'ummi' || cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1 dark:text-white">
                                                                    <option value="" class="dark:bg-zinc-900">Pilih Surah</option>
                                                                    @foreach ($surahs as $s)
                                                                        <option value="{{ $s->id }}" class="dark:bg-zinc-900">{{ $s->number }}. {{ $s->name_latin }}</option>
                                                                    @endforeach
                                                                </select>
                                                                <input type="text" :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][ayah]'" x-model="h.ayah" placeholder="Ayat (cth: 1-5)" :disabled="tab !== 'ummi' || cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1 dark:text-white">
                                                                <input type="hidden" :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][id]'" :value="h.id" :disabled="tab !== 'ummi'">
                                                                <!-- Remove button -->
                                                                <template x-if="cell.ummiHafalans.length > 1">
                                                                    <button type="button" @click="isDirty = true; cell.ummiHafalans.splice(hIndex, 1)" class="absolute top-2 right-2 text-rose-655 text-xs font-bold bg-white dark:bg-zinc-800 border border-gray-255 dark:border-zinc-700 rounded-full w-5 h-5 flex items-center justify-center cursor-pointer shadow-sm">×</button>
                                                                </template>
                                                            </div>
                                                        </template>
                                                        <button type="button" @click="isDirty = true; cell.ummiHafalans.push({ id: null, surah_id: '', ayah: '' })" :disabled="tab !== 'ummi' || cell.attendance !== 'hadir'" class="w-full py-1.5 bg-emerald-50 hover:bg-emerald-100 dark:bg-zinc-800 text-emerald-650 dark:text-emerald-455 border border-emerald-200 dark:border-zinc-700 rounded-lg text-xs font-bold transition cursor-pointer">
                                                            + Tambah Hafalan
                                                        </button>
                                                    </div>
                                                </div>
                                            </template>

                                            <!-- JIKA BUKAN LEVEL UMMI -->
                                            <template x-if="student.tahfizh_level !== 'ummi'">
                                                <div class="space-y-3">
                                                    <template x-for="(h, hIndex) in cell.hafalans" :key="hIndex">
                                                        <div class="bg-gray-50/50 dark:bg-zinc-800/40 p-3 rounded-lg border border-gray-255 dark:border-zinc-800 relative space-y-3">
                                                            <div>
                                                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Surah</label>
                                                                <select :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][surah_id]'" x-model="h.surah_id" @change="syncAyahLimits(h)" :disabled="tab !== 'hafalan' || cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1.5 dark:text-white">
                                                                    <option value="" class="dark:bg-zinc-900">Pilih Surah</option>
                                                                    @foreach ($surahs as $s)
                                                                        <option value="{{ $s->id }}" class="dark:bg-zinc-900">{{ $s->number }}. {{ $s->name_latin }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="grid grid-cols-2 gap-2">
                                                                <div>
                                                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Ayat Mulai</label>
                                                                    <input type="number" :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][ayah_start]'" x-model.number="h.ayah_start" placeholder="Awal" :disabled="tab !== 'hafalan' || cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1 dark:text-white">
                                                                </div>
                                                                <div>
                                                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Ayat Akhir</label>
                                                                    <input type="number" :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][ayah_end]'" x-model.number="h.ayah_end" placeholder="Akhir" :disabled="tab !== 'hafalan' || cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1 dark:text-white">
                                                                </div>
                                                            </div>
                                                            <div class="grid grid-cols-2 gap-2">
                                                                <div>
                                                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Nilai</label>
                                                                    <select :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][score]'" x-model="h.score" :disabled="tab !== 'hafalan' || cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1 dark:text-white">
                                                                        <option value="" class="dark:bg-zinc-900">Pilih Nilai</option>
                                                                        <option value="95" class="dark:bg-zinc-900">A</option>
                                                                        <option value="85" class="dark:bg-zinc-900">B</option>
                                                                        <option value="75" class="dark:bg-zinc-900">C</option>
                                                                        <option value="65" class="dark:bg-zinc-900">D</option>
                                                                    </select>
                                                                </div>
                                                                <div>
                                                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Status</label>
                                                                    <select :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][status]'" x-model="h.status" :disabled="tab !== 'hafalan' || cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1 dark:text-white">
                                                                        <option value="passed" class="dark:bg-zinc-900">Lulus</option>
                                                                        <option value="repeat" class="dark:bg-zinc-900">Ulang</option>
                                                                        <option value="needs_improvement" class="dark:bg-zinc-900">Revisi</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <input type="hidden" :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][id]'" :value="h.id" :disabled="tab !== 'hafalan'">
                                                            <!-- Delete button -->
                                                            <template x-if="cell.hafalans.length > 1">
                                                                <button type="button" @click="isDirty = true; cell.hafalans.splice(hIndex, 1)" class="absolute top-2 right-2 text-rose-650 text-xs font-bold bg-white dark:bg-zinc-800 border border-gray-255 dark:border-zinc-700 rounded-full w-5 h-5 flex items-center justify-center cursor-pointer shadow-sm">×</button>
                                                            </template>
                                                        </div>
                                                    </template>
                                                    <button type="button" @click="isDirty = true; cell.hafalans.push(getNextHafalan(student.id, cell.hafalans))" :disabled="tab !== 'hafalan' || cell.attendance !== 'hadir'" class="w-full py-2 bg-indigo-50 dark:bg-zinc-800 text-indigo-650 dark:text-indigo-400 border border-indigo-200 dark:border-zinc-700 rounded-lg text-xs font-bold transition cursor-pointer">
                                                        + Tambah Surat Setoran
                                                    </button>
                                                </div>
                                            </template>
                                        </div>

                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </form>
            @endif

        </div>
    </div>
</x-app-layout>
