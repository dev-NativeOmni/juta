@props(['milestones' => []])

@php
    $firstRecord = data_get($milestones, 'first_record');
    $journey = data_get($milestones, 'journey', []);
@endphp

<div x-data="{
    showModal: false,
    activeTerm: null,
    activeGrade: '',
    journeyData: @js(array_values($journey)),
    openTerm(gradeIdx, termNum, gradeName) {
        const gradeObj = this.journeyData[gradeIdx] || {};
        const terms = gradeObj.terms || {};
        this.activeTerm = terms[termNum] || null;
        this.activeGrade = gradeName;
        this.showModal = true;
    }
}" class="rounded-2xl bg-white dark:bg-zinc-900 p-6 shadow-md space-y-5">
    {{-- ─── HEADER & SETORAN PERTAMA BANNER ─── --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800 pb-4">
        <div>
            <h3 class="text-lg font-black text-zinc-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-map class="w-5 h-5 text-indigo-600 dark:text-indigo-400" /> Peta Perjalanan Hafalan Murid
            </h3>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                Tabel rekam jejak capaian &amp; tanggal setoran hafalan pertama yang tercatat di sistem per-Term (Kelas 10, 11, &amp; 12). Klik sel Term untuk melihat detail ringkasan.
            </p>
        </div>

        @if ($firstRecord)
            <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-900 dark:text-emerald-200 shadow-xs">
                <x-heroicon-o-sparkles class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Setoran Hafalan Pertama Sistem</p>
                    <p class="text-xs font-extrabold text-zinc-900 dark:text-white">{{ data_get($firstRecord, 'title') }}</p>
                    <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium">Tanggal: {{ data_get($firstRecord, 'date') }}</p>
                </div>
            </div>
        @else
            <div class="px-3.5 py-1.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 text-xs text-zinc-500 font-medium">
                Belum ada setoran hafalan pertama.
            </div>
        @endif
    </div>

    {{-- ─── TABEL PETA HAFALAN (KELAS 10, 11, 12 X TERM 1, 2, 3, 4) ─── --}}
    <div class="overflow-x-auto rounded-2xl border border-zinc-300 dark:border-zinc-700 shadow-xs">
        <table class="w-full border-collapse text-center text-xs">
            <thead>
                <tr class="bg-zinc-100 dark:bg-zinc-800/90 text-zinc-900 dark:text-zinc-100 font-black uppercase tracking-wider border-b border-zinc-300 dark:border-zinc-700">
                    <th class="py-3.5 px-4 border-r border-zinc-300 dark:border-zinc-700 w-28">KELAS</th>
                    <th class="py-3.5 px-4 border-r border-zinc-300 dark:border-zinc-700">TERM 1</th>
                    <th class="py-3.5 px-4 border-r border-zinc-300 dark:border-zinc-700">TERM 2</th>
                    <th class="py-3.5 px-4 border-r border-zinc-300 dark:border-zinc-700">TERM 3</th>
                    <th class="py-3.5 px-4">TERM 4</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-300 dark:divide-zinc-700 bg-white dark:bg-zinc-900">
                @forelse ($journey as $g)
                    @php
                        $gNum = data_get($g, 'grade_num');
                        $gName = data_get($g, 'grade_name', 'Kelas '.$gNum);
                        $isCurrentGrade = data_get($g, 'is_current_grade');
                        $terms = data_get($g, 'terms', []);
                    @endphp
                    <tr class="transition">
                        <!-- KELAS Column -->
                        <td class="py-4 px-3 font-black text-sm border-r border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-white bg-zinc-50/80 dark:bg-zinc-850/50 align-middle">
                            <div class="flex flex-col items-center justify-center gap-1">
                                <span class="text-base font-black">{{ $gNum }}</span>
                                @if ($isCurrentGrade)
                                    <span class="px-2 py-0.5 text-[9px] rounded-md bg-emerald-500 text-white font-extrabold uppercase tracking-wider shadow-xs">Aktif</span>
                                @endif
                            </div>
                        </td>

                        <!-- TERM 1 to 4 Columns -->
                        @foreach ([1, 2, 3, 4] as $tNum)
                            @php
                                $t = data_get($terms, $tNum, []);
                                $hasData = data_get($t, 'has_data', false);
                                $firstSetoran = data_get($t, 'first_setoran');
                                $isCurrent = data_get($t, 'is_current', false);
                            @endphp
                            <td @click="openTerm({{ $loop->parent->index }}, {{ $tNum }}, '{{ addslashes($gName) }}')"
                                class="p-3 border-r last:border-r-0 border-zinc-300 dark:border-zinc-700 text-center align-middle cursor-pointer hover:bg-indigo-50/60 dark:hover:bg-indigo-950/40 transition group relative">
                                
                                <div class="space-y-1.5">
                                    <!-- Status Pill Badge -->
                                    <div class="flex items-center justify-center gap-1">
                                        @if ($hasData)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[9px] rounded-full font-bold bg-emerald-100 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 border border-emerald-200/60">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Ada Setoran
                                            </span>
                                        @elseif ($isCurrent)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[9px] rounded-full font-bold bg-amber-100 dark:bg-amber-950/70 text-amber-700 dark:text-amber-300 border border-amber-200/60">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span> Term Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[9px] rounded-full font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">
                                                <span class="w-1.5 h-1.5 rounded-full bg-zinc-400"></span> Belum Setoran
                                            </span>
                                        @endif
                                    </div>

                                    @if ($hasData && $firstSetoran)
                                        <!-- Top Line: Surah (ayat awal-ayat akhir) -->
                                        <p class="font-black text-zinc-900 dark:text-zinc-100 text-xs group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                                            {{ data_get($firstSetoran, 'full_text') }}
                                        </p>
                                        <!-- Bottom Line: Tanggal Setoran -->
                                        <p class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 flex items-center justify-center gap-1">
                                            <x-heroicon-o-calendar class="w-3.5 h-3.5" /> {{ data_get($firstSetoran, 'date') }}
                                        </p>
                                    @elseif (data_get($t, 'target'))
                                        <!-- Target assigned for term -->
                                        <p class="font-bold text-teal-800 dark:text-teal-300 text-xs flex items-center justify-center gap-1">
                                            <x-heroicon-o-check-badge class="w-3.5 h-3.5" /> {{ data_get($t, 'target.full_text') }}
                                        </p>
                                        <p class="text-[10px] font-semibold text-zinc-500 dark:text-zinc-400">
                                            DL: {{ data_get($t, 'target.date') }}
                                        </p>
                                    @else
                                        <div class="py-1 text-zinc-400 dark:text-zinc-600 text-xs italic font-medium">
                                            -
                                        </div>
                                    @endif

                                    <div class="text-[10px] font-bold text-indigo-500 dark:text-indigo-400 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-1">
                                        <x-heroicon-o-magnifying-glass class="w-3.5 h-3.5" /> Detail Term
                                    </div>
                                </div>
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-xs text-zinc-400">
                            Belum ada data peta hafalan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ─── ALPINE QUICK DETAIL MODAL ─── --}}
    <div x-show="showModal" 
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
        
        <div @click.away="showModal = false"
             class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl shadow-xl max-w-lg w-full p-6 space-y-5 relative">
            
            <!-- Close Button -->
            <button @click="showModal = false" class="absolute top-4 right-4 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded-lg p-1 transition cursor-pointer">
                <x-heroicon-o-x-mark class="w-5 h-5" />
            </button>

            <!-- Modal Header -->
            <div class="border-b border-zinc-100 dark:border-zinc-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-extrabold text-xs">
                        <span x-text="activeGrade"></span>
                    </span>
                    <h3 class="text-base font-extrabold text-zinc-900 dark:text-white" x-text="activeTerm?.name || 'Detail Term'"></h3>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                    Periode: <span class="font-semibold text-zinc-700 dark:text-zinc-300" x-text="activeTerm?.start_date + ' s/d ' + activeTerm?.end_date"></span>
                </p>
            </div>

            <!-- Target Term Card (If Assigned) -->
            <template x-if="activeTerm && activeTerm.target">
                <div class="p-3.5 rounded-xl bg-cyan-50/80 dark:bg-cyan-950/40 border border-cyan-200 dark:border-cyan-900/40">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-cyan-900 dark:text-cyan-300 flex items-center gap-1 mb-1">
                        <x-heroicon-o-check-badge class="w-4 h-4 text-cyan-700 dark:text-cyan-400" /> Target Term Yang Ditetapkan
                    </span>
                    <p class="font-black text-zinc-900 dark:text-white text-xs" x-text="activeTerm.target.full_text || '-'"></p>
                    <p class="text-[11px] text-cyan-800 dark:text-cyan-300 mt-1 font-semibold">
                        Batas Target: <span x-text="activeTerm.target.date || '-'"></span> · Status: <span x-text="activeTerm.target.status || '-'"></span>
                    </p>
                </div>
            </template>

            <!-- Modal Content -->
            <template x-if="activeTerm && activeTerm.has_data">
                <div class="space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <!-- Setoran Pertama -->
                        <div class="p-3.5 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200/70 dark:border-emerald-900/40">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400 flex items-center gap-1 mb-1">
                                <x-heroicon-o-sparkles class="w-4 h-4 text-emerald-600 dark:text-emerald-400" /> Setoran Pertama Term
                            </span>
                            <p class="font-extrabold text-zinc-900 dark:text-white text-xs" x-text="activeTerm.first_setoran?.full_text || '-'"></p>
                            <p class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-1 font-semibold">
                                Tanggal: <span x-text="activeTerm.first_setoran?.date || '-'"></span>
                            </p>
                        </div>

                        <!-- Setoran Terakhir -->
                        <div class="p-3.5 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200/70 dark:border-indigo-900/40">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-400 flex items-center gap-1 mb-1">
                                <x-heroicon-o-flag class="w-4 h-4 text-indigo-600 dark:text-indigo-400" /> Setoran Terakhir Term
                            </span>
                            <p class="font-extrabold text-zinc-900 dark:text-white text-xs" x-text="activeTerm.last_setoran?.full_text || '-'"></p>
                            <p class="text-[11px] text-indigo-600 dark:text-indigo-400 mt-1 font-semibold">
                                Tanggal: <span x-text="activeTerm.last_setoran?.date || '-'"></span>
                            </p>
                        </div>
                    </div>

                    <!-- Statistics Summary -->
                    <div class="grid grid-cols-2 gap-3 pt-2">
                        <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/60 dark:border-zinc-700/60 text-center">
                            <span class="text-zinc-500 dark:text-zinc-400 text-[10px] uppercase font-semibold block">Total Frekuensi Setoran</span>
                            <span class="text-lg font-black text-zinc-900 dark:text-white" x-text="activeTerm.total_records || 0"></span>
                            <span class="text-xs text-zinc-500"> Kali</span>
                        </div>
                        <div class="p-3 rounded-xl bg-zinc-50 dark:bg-zinc-800/60 border border-zinc-200/60 dark:border-zinc-700/60 text-center">
                            <span class="text-zinc-500 dark:text-zinc-400 text-[10px] uppercase font-semibold block">Total Baris Setoran</span>
                            <span class="text-lg font-black text-indigo-600 dark:text-indigo-400" x-text="activeTerm.total_lines || 0"></span>
                            <span class="text-xs text-indigo-500"> Baris</span>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="activeTerm && !activeTerm.has_data">
                <div class="p-6 text-center text-zinc-500 dark:text-zinc-400 space-y-2">
                    <x-heroicon-o-inbox class="w-8 h-8 text-zinc-400 mx-auto block mb-1" />
                    <p class="text-xs font-semibold">Belum Ada Rekam Setoran pada Term Ini.</p>
                    <p class="text-[11px] text-zinc-400">Setoran hafalan yang dilakukan murid pada periode term ini akan otomatis tercatat di sini.</p>
                </div>
            </template>

            <div class="pt-2 text-right">
                <button @click="showModal = false" 
                        class="px-4 py-2 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 font-bold rounded-xl text-xs transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>
