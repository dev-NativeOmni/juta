<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                    Kalender Akademik & Hari Libur
                </h2>
                <p class="text-sm text-gray-600 dark:text-zinc-400">
                    @if ($permissions['is_admin'])
                        Klik tanggal untuk mengatur libur Tahfizh (semua atau sebagian kelas) dan libur Adab secara terpisah.
                    @else
                        Klik tanggal untuk mengatur libur pengisian Adab. Kalender Tahfizh hanya dapat diubah Admin.
                    @endif
                </p>
            </div>
            @if ($permissions['edit_tahfizh'] || $permissions['edit_adab'])
                <div>
                    <button type="submit" form="calendar-form" class="inline-flex items-center gap-2 rounded-xl bg-teal-600 hover:bg-teal-700 px-5 py-3 text-sm font-semibold text-white shadow shadow-teal-500/20 transition cursor-pointer">
                        <x-heroicon-o-check class="w-4 h-4" />
                        <span>Simpan Kalender</span>
                    </button>
                </div>
            @endif
        </div>
    </x-slot>

    @php
        $monthsList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $currentYear = (int)date('Y');
        $yearsList = range($currentYear - 2, $currentYear + 3);
        $todayStr = date('Y-m-d');
    @endphp

    <div class="py-8" x-data="{
        days: {{ json_encode((object) collect($globalDays)->map(fn ($d) => ['t' => $d['tahfizh_off'], 'a' => $d['adab_off']])->all()) }},
        classDays: {{ json_encode((object) $classHolidays) }},
        canTahfizh: @js($permissions['edit_tahfizh']),
        canAdab: @js($permissions['edit_adab']),
        adabDays: @js($adabDays),

        modal: { isOpen: false, dateStr: '', dayNum: '', isoDay: 1, tahfizh: 'on', adab: 'on', selectedClasses: [] },

        tahfizhOff(d) { return !! (this.days[d] && this.days[d].t); },
        adabOff(d) { return !! (this.days[d] && this.days[d].a); },
        partialCount(d) { return (this.classDays[d] || []).length; },

        openModal(dateStr, dayNum, isoDay) {
            if (! this.canTahfizh && ! this.canAdab) return;
            this.modal.dateStr = dateStr;
            this.modal.dayNum = dayNum;
            this.modal.isoDay = isoDay;
            this.modal.tahfizh = this.tahfizhOff(dateStr) ? 'off' : (this.partialCount(dateStr) > 0 ? 'partial' : 'on');
            this.modal.adab = this.adabOff(dateStr) ? 'off' : 'on';
            this.modal.selectedClasses = [...(this.classDays[dateStr] || [])];
            this.modal.isOpen = true;
        },

        saveModal() {
            const d = this.modal.dateStr;
            const t = this.canTahfizh ? this.modal.tahfizh === 'off' : this.tahfizhOff(d);
            const a = this.canAdab ? this.modal.adab === 'off' : this.adabOff(d);
            if (t || a) { this.days[d] = { t, a }; } else { delete this.days[d]; }
            if (this.canTahfizh) {
                if (this.modal.tahfizh === 'partial' && this.modal.selectedClasses.length > 0) {
                    this.classDays[d] = [...this.modal.selectedClasses];
                } else {
                    delete this.classDays[d];
                }
            }
            this.modal.isOpen = false;
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Success Alert Notification -->
            @if (session('success'))
                <div class="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/30 rounded-xl p-4 flex items-center gap-3">
                    <x-heroicon-o-check-circle class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                    <span class="text-sm text-emerald-800 dark:text-emerald-300 font-semibold">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Google Calendar Top Control Toolbar -->
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-4 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                
                <!-- Left Section: Navigation & Title -->
                <div class="flex items-center gap-3">
                    <!-- Today Button -->
                    <a href="{{ route('academic-calendar.index', ['year' => date('Y'), 'month' => date('m')]) }}" class="px-4 py-2 border border-gray-300 dark:border-zinc-700 text-gray-700 dark:text-zinc-200 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-xl text-xs font-bold transition inline-flex items-center gap-1.5 shadow-xs">
                        <x-heroicon-o-calendar-days class="w-4 h-4 text-gray-600 dark:text-zinc-300 shrink-0" />
                        <span>Hari Ini</span>
                    </a>

                    <!-- Prev/Next Month Arrows -->
                    <div class="flex items-center bg-gray-100 dark:bg-zinc-800/80 rounded-xl p-0.5 border border-gray-200 dark:border-zinc-700/60">
                        <a href="{{ route('academic-calendar.index', ['year' => $prevYear, 'month' => $prevMonth]) }}" title="Bulan Sebelumnya" class="w-8 h-8 flex items-center justify-center text-gray-700 dark:text-zinc-300 hover:bg-white dark:hover:bg-zinc-700 rounded-lg text-base font-bold transition">
                            ‹
                        </a>
                        <a href="{{ route('academic-calendar.index', ['year' => $nextYear, 'month' => $nextMonth]) }}" title="Bulan Berikutnya" class="w-8 h-8 flex items-center justify-center text-gray-700 dark:text-zinc-300 hover:bg-white dark:hover:bg-zinc-700 rounded-lg text-base font-bold transition">
                            ›
                        </a>
                    </div>

                    <!-- Current Month & Year Display Title -->
                    <h3 class="text-lg md:text-xl font-black text-gray-900 dark:text-white tracking-tight ml-2">
                        {{ $monthsList[$month] }} {{ $year }}
                    </h3>
                </div>

                <!-- Right Section: Quick Month/Year Dropdown Selectors -->
                <form method="GET" action="{{ route('academic-calendar.index') }}" class="flex items-center gap-2">
                    <select name="month" id="month" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white text-xs font-semibold py-2 px-3 shadow-xs focus:ring-indigo-500">
                        @foreach ($monthsList as $num => $name)
                            <option value="{{ $num }}" {{ $num === $month ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>

                    <select name="year" id="year" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white text-xs font-semibold py-2 px-3 shadow-xs focus:ring-indigo-500">
                        @foreach ($yearsList as $yr)
                            <option value="{{ $yr }}" {{ $yr === $year ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow transition cursor-pointer">
                        <x-heroicon-o-magnifying-glass class="w-3.5 h-3.5" />
                        <span>Filter</span>
                    </button>
                </form>
            </div>

            <!-- Kunci Kalender Bulan Ini -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @foreach ([\App\Services\SchoolCalendar::SCOPE_TAHFIZH => 'Tahfizh', \App\Services\SchoolCalendar::SCOPE_ADAB => 'Adab'] as $scope => $scopeLabel)
                    @php $isLocked = in_array($scope, $locks, true); @endphp
                    <div class="bg-white dark:bg-zinc-900 border {{ $isLocked ? 'border-amber-300 dark:border-amber-800/60' : 'border-gray-200 dark:border-zinc-800' }} rounded-2xl p-4 flex items-center justify-between gap-3 shadow-xs">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 {{ $isLocked ? 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400' : 'bg-gray-100 text-gray-500 dark:bg-zinc-800 dark:text-zinc-400' }}">
                                @if ($isLocked)
                                    <x-heroicon-o-lock-closed class="w-5 h-5" />
                                @else
                                    <x-heroicon-o-lock-open class="w-5 h-5" />
                                @endif
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-gray-900 dark:text-white">Kalender {{ $scopeLabel }}</p>
                                <p class="text-xs text-gray-500 dark:text-zinc-400">
                                    {{ $isLocked ? 'Terkunci -- status libur '.$scopeLabel.' bulan ini tidak bisa diubah.' : 'Belum dikunci.' }}
                                </p>
                            </div>
                        </div>
                        @php
                            $canToggle = $isLocked ? $permissions['unlock'] : $permissions['lock_'.$scope];
                        @endphp
                        @if ($canToggle)
                            <form method="POST" action="{{ route('academic-calendar.lock') }}" onsubmit="return confirm('{{ $isLocked ? 'Buka kunci' : 'Kunci' }} kalender {{ $scopeLabel }} bulan ini?')">
                                @csrf
                                <input type="hidden" name="year" value="{{ $year }}">
                                <input type="hidden" name="month" value="{{ $month }}">
                                <input type="hidden" name="scope" value="{{ $scope }}">
                                <input type="hidden" name="action" value="{{ $isLocked ? 'unlock' : 'lock' }}">
                                <button type="submit" class="shrink-0 inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold transition cursor-pointer {{ $isLocked ? 'border border-gray-300 dark:border-zinc-700 text-gray-700 dark:text-zinc-200 hover:bg-gray-100 dark:hover:bg-zinc-800' : 'bg-amber-600 hover:bg-amber-700 text-white shadow-sm' }}">
                                    {{ $isLocked ? 'Buka Kunci' : 'Kunci Bulan Ini' }}
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Hari pengisian Adab -->
            <form method="POST" action="{{ route('academic-calendar.adab-days') }}" class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-4 shadow-xs flex flex-col md:flex-row md:items-center gap-3">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <div class="md:w-56 shrink-0">
                    <p class="text-sm font-bold text-gray-900 dark:text-white">Hari Pengisian Adab</p>
                    <p class="text-[11px] text-gray-500 dark:text-zinc-400">Berlaku untuk perhitungan kehadiran kuisioner semua bulan.</p>
                </div>
                <div class="flex flex-wrap gap-2 flex-1">
                    @foreach (\App\Services\SchoolCalendar::DAY_NAMES as $dayNumber => $dayName)
                        <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-zinc-700 text-xs font-semibold text-gray-700 dark:text-zinc-300 {{ $permissions['edit_adab_days'] ? 'cursor-pointer' : 'opacity-70' }}">
                            <input type="checkbox" name="adab_days[]" value="{{ $dayNumber }}" @checked(in_array($dayNumber, $adabDays, true)) @disabled(! $permissions['edit_adab_days']) class="rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                            {{ $dayName }}
                        </label>
                    @endforeach
                </div>
                @if ($permissions['edit_adab_days'])
                    <button type="submit" onclick="return confirm('Ubah hari pengisian Adab? Perhitungan kehadiran kuisioner semua bulan ikut menyesuaikan.')" class="shrink-0 px-4 py-2 rounded-xl bg-violet-600 hover:bg-violet-700 text-white text-xs font-bold shadow-sm cursor-pointer">Simpan Hari Adab</button>
                @endif
            </form>
            @error('adab_days') <p class="text-xs text-red-600 -mt-3">{{ $message }}</p> @enderror

            <!-- Academic Calendar Form -->
            <form id="calendar-form" method="POST" action="{{ route('academic-calendar.update') }}">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">

                <!-- Status libur semua kelas: days[tanggal][tahfizh|adab] -->
                <template x-for="(flags, dateStr) in days" :key="dateStr">
                    <span>
                        <template x-if="flags.t"><input type="hidden" :name="'days[' + dateStr + '][tahfizh]'" value="1"></template>
                        <template x-if="flags.a"><input type="hidden" :name="'days[' + dateStr + '][adab]'" value="1"></template>
                    </span>
                </template>

                <!-- Libur Tahfizh sebagian kelas -->
                <template x-for="(classIds, dateStr) in classDays" :key="dateStr">
                    <template x-for="classId in classIds" :key="classId">
                        <input type="hidden" :name="'class_holidays[' + dateStr + '][]'" :value="classId">
                    </template>
                </template>

                <!-- Main Calendar Grid Container (Explicit 7-Column Layout Inline Style for 100% Compatibility) -->
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-3xl overflow-hidden shadow-sm">
                    
                    <!-- 7-Column Day Names Header (Sunday to Saturday) -->
                    <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); text-align: center;" class="border-b border-gray-200 dark:border-zinc-800 bg-gray-50/80 dark:bg-zinc-850/60">
                        @foreach (['MINGGU', 'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU'] as $idx => $dayName)
                            <div class="py-3 text-[11px] font-black tracking-wider uppercase {{ $idx === 0 || $idx === 6 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-500 dark:text-zinc-400' }}">
                                {{ $dayName }}
                            </div>
                        @endforeach
                    </div>

                    <!-- 7-Column Date Cells Grid -->
                    <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); border-left: 1px solid #e5e7eb; border-top: 1px solid #e5e7eb;" class="dark:border-zinc-800 bg-gray-200 dark:bg-zinc-800 gap-[1px]">
                        @foreach ($gridDates as $day)
                            @php
                                $dateStr = $day['date']->toDateString();
                                $dayNum = $day['date']->day;
                                $dayOfWeek = $day['date']->dayOfWeek; // 0=Sunday, 6=Saturday
                                $isWeekend = ($dayOfWeek === 0 || $dayOfWeek === 6);
                                $isToday = ($dateStr === $todayStr);
                            @endphp

                            @if ($day['isCurrentMonth'])
                                <!-- Current Month Active Date Cell -->
                                @php
                                    $isoDay = $day['date']->dayOfWeekIso;
                                    $isAdabWeekday = in_array($isoDay, $adabDays, true);
                                @endphp
                                <div
                                    @click="openModal('{{ $dateStr }}', {{ $dayNum }}, {{ $isoDay }})"
                                    :class="tahfizhOff('{{ $dateStr }}') && adabOff('{{ $dateStr }}')
                                        ? 'bg-rose-50/80 dark:bg-rose-950/20'
                                        : (tahfizhOff('{{ $dateStr }}') || adabOff('{{ $dateStr }}') || partialCount('{{ $dateStr }}') > 0)
                                            ? 'bg-amber-50/80 dark:bg-amber-950/20'
                                            : '{{ $isWeekend ? 'bg-gray-50/80 dark:bg-zinc-900/90' : 'bg-white dark:bg-zinc-900' }} hover:bg-indigo-50/50 dark:hover:bg-zinc-800/60'"
                                    style="min-height: 120px;"
                                    class="p-2.5 flex flex-col justify-between select-none transition duration-150 group {{ $permissions['edit_tahfizh'] || $permissions['edit_adab'] ? 'cursor-pointer' : 'cursor-default' }}"
                                >
                                    <!-- Top Row: Date Number & Today Circle Badge -->
                                    <div class="flex justify-between items-start">
                                        @if ($isToday)
                                            <span class="w-6 h-6 rounded-full bg-blue-600 text-white font-black flex items-center justify-center text-xs shadow-md shadow-blue-500/30">
                                                {{ $dayNum }}
                                            </span>
                                        @else
                                            <span class="font-bold text-xs {{ $isWeekend ? 'text-rose-600 dark:text-rose-400' : 'text-gray-900 dark:text-zinc-100' }} px-1 py-0.5">
                                                {{ $dayNum }}
                                            </span>
                                        @endif

                                        @if ($isWeekend)
                                            <span class="text-[9px] text-rose-500/80 dark:text-rose-400/80 font-bold uppercase tracking-wider">
                                                Off
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Status libur -->
                                    <div class="mt-2 space-y-1">
                                        <template x-if="tahfizhOff('{{ $dateStr }}') && adabOff('{{ $dateStr }}')">
                                            <div class="bg-rose-600 text-white rounded-md px-2 py-1 text-[11px] font-medium truncate shadow-xs flex items-center gap-1">
                                                <x-heroicon-o-no-symbol class="w-3.5 h-3.5 shrink-0" />
                                                <span>Libur Total</span>
                                            </div>
                                        </template>
                                        <template x-if="tahfizhOff('{{ $dateStr }}') && ! adabOff('{{ $dateStr }}')">
                                            <div class="bg-emerald-700 text-white rounded-md px-2 py-1 text-[11px] font-medium truncate shadow-xs">Libur Tahfizh</div>
                                        </template>
                                        <template x-if="adabOff('{{ $dateStr }}') && ! tahfizhOff('{{ $dateStr }}')">
                                            <div class="bg-violet-600 text-white rounded-md px-2 py-1 text-[11px] font-medium truncate shadow-xs">Libur Adab</div>
                                        </template>
                                        <template x-if="partialCount('{{ $dateStr }}') > 0 && ! tahfizhOff('{{ $dateStr }}')">
                                            <div class="bg-amber-600 text-white rounded-md px-2 py-1 text-[11px] font-medium truncate shadow-xs flex items-center gap-1">
                                                <x-heroicon-o-exclamation-triangle class="w-3.5 h-3.5 shrink-0" />
                                                <span>Libur <span x-text="partialCount('{{ $dateStr }}')"></span> Kelas</span>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Hari aktif: Tahfizh (Senin-Jumat, mengikuti jadwal kelas) & Adab (hari pengisian Adab) -->
                                    @unless ($isWeekend)
                                        <div class="mt-1 flex flex-wrap gap-1 text-[9px] font-bold uppercase tracking-wide">
                                            <span x-show="! tahfizhOff('{{ $dateStr }}')" class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400">Tahfizh</span>
                                            @if ($isAdabWeekday)
                                                <span x-show="! adabOff('{{ $dateStr }}')" class="px-1.5 py-0.5 rounded bg-violet-100 text-violet-700 dark:bg-violet-950/50 dark:text-violet-400">Adab</span>
                                            @endif
                                        </div>
                                    @endunless
                                </div>
                             @else
                                 <!-- Non-Current Month Padded Date Cell (Muted Gray) -->
                                 <div style="min-height: 120px;" class="p-2.5 bg-gray-50/50 dark:bg-zinc-950/40 opacity-40 select-none flex flex-col justify-between">
                                     <span class="font-semibold text-xs text-gray-400 dark:text-zinc-600 px-1 py-0.5">
                                         {{ $dayNum }}
                                     </span>
                                 </div>
                             @endif
                         @endforeach
                     </div>

                 </div>
             </form>

         </div>

         <!-- Alpine.js Date Configuration Modal Popup -->
         <div x-show="modal.isOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
             <!-- Modal Backdrop Blur -->
             <div class="fixed inset-0 bg-black/60 backdrop-blur-xs"></div>

             <!-- Modal Content Wrapper -->
             <div class="relative min-h-screen flex items-center justify-center p-4">
                 <div class="relative bg-white dark:bg-zinc-900 rounded-3xl max-w-md w-full border border-gray-200 dark:border-zinc-800 p-6 shadow-2xl space-y-5" @click.away="modal.isOpen = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="scale-100 opacity-100" x-transition:leave-end="scale-95 opacity-0">
                     
                     <!-- Header -->
                     <div class="flex justify-between items-center border-b dark:border-zinc-800 pb-3">
                         <div>
                             <h3 class="text-base font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                                 <x-heroicon-o-calendar class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                                 <span>Atur Tanggal <span x-text="modal.dayNum" class="text-blue-600 dark:text-blue-400"></span></span>
                             </h3>
                             <p class="text-xs text-gray-500 mt-0.5" x-text="modal.dateStr"></p>
                         </div>
                         <button type="button" @click="modal.isOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-zinc-300 font-bold text-xl cursor-pointer">
                             &times;
                         </button>
                     </div>

                     <!-- Tahfizh -->
                     <div class="space-y-2">
                         <div class="flex items-center justify-between">
                             <label class="block text-xs font-bold text-gray-400 dark:text-zinc-400 uppercase tracking-wider">Tahfizh</label>
                             @if (in_array(\App\Services\SchoolCalendar::SCOPE_TAHFIZH, $locks, true))
                                 <span class="text-[10px] font-bold text-amber-600 inline-flex items-center gap-1"><x-heroicon-o-lock-closed class="w-3 h-3" /> Terkunci</span>
                             @endif
                         </div>
                         <template x-if="canTahfizh">
                             <div class="grid grid-cols-3 gap-2">
                                 @foreach (['on' => ['Aktif', 'border-emerald-500 bg-emerald-50/40 text-emerald-800 dark:text-emerald-400 dark:border-emerald-800'], 'off' => ['Libur Semua', 'border-rose-500 bg-rose-50/40 text-rose-800 dark:text-rose-400 dark:border-rose-800'], 'partial' => ['Libur Sebagian', 'border-amber-500 bg-amber-50/40 text-amber-800 dark:text-amber-400 dark:border-amber-800']] as $value => [$label, $color])
                                     <label class="border border-gray-200 dark:border-zinc-800 rounded-xl p-2.5 text-center cursor-pointer transition select-none text-[11px] font-bold"
                                            :class="modal.tahfizh === '{{ $value }}' ? '{{ $color }}' : 'text-gray-700 dark:text-zinc-300 hover:bg-gray-50 dark:hover:bg-zinc-800/50'">
                                         <input type="radio" x-model="modal.tahfizh" value="{{ $value }}" class="sr-only">
                                         {{ $label }}
                                     </label>
                                 @endforeach
                             </div>
                         </template>
                         <template x-if="! canTahfizh">
                             <p class="text-xs text-gray-600 dark:text-zinc-400 bg-gray-50 dark:bg-zinc-800/50 rounded-xl px-3 py-2">
                                 <span x-text="tahfizhOff(modal.dateStr) ? 'Libur semua kelas' : (partialCount(modal.dateStr) > 0 ? 'Libur ' + partialCount(modal.dateStr) + ' kelas' : 'Aktif (sesuai jadwal kelas)')"></span>
                                 <span class="block text-[10px] text-gray-400 mt-0.5">Hanya bisa diubah Admin{{ in_array(\App\Services\SchoolCalendar::SCOPE_TAHFIZH, $locks, true) ? ' setelah kunci dibuka' : '' }}.</span>
                             </p>
                         </template>
                     </div>

                     <!-- Kelas yang libur Tahfizh (Libur Sebagian) -->
                     <div x-show="canTahfizh && modal.tahfizh === 'partial'" x-transition class="space-y-3 pt-3 border-t dark:border-zinc-800 max-h-56 overflow-y-auto">
                         <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400">Centang kelas yang LIBUR Tahfizh pada hari ini:</label>
                         <div class="grid grid-cols-2 gap-2">
                             @foreach ($classRooms as $class)
                                 <label class="flex items-center gap-2.5 p-2 rounded-xl border border-gray-100 dark:border-zinc-800/80 bg-gray-50/40 hover:bg-gray-50 dark:hover:bg-zinc-850 cursor-pointer transition select-none text-xs">
                                     <input
                                         type="checkbox"
                                         value="{{ $class->id }}"
                                         :checked="modal.selectedClasses.includes({{ $class->id }})"
                                         @change="if ($el.checked) { if (!modal.selectedClasses.includes({{ $class->id }})) modal.selectedClasses.push({{ $class->id }}) } else { modal.selectedClasses = modal.selectedClasses.filter(id => id !== {{ $class->id }}) }"
                                         class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                     >
                                     <div>
                                         <span class="font-bold text-gray-800 dark:text-zinc-200 block">{{ $class->name }}</span>
                                         <span class="text-[9px] text-gray-400 dark:text-zinc-500 uppercase font-semibold">{{ $class->program?->name }}</span>
                                     </div>
                                 </label>
                             @endforeach
                         </div>
                     </div>

                     <!-- Adab -->
                     <div class="space-y-2 pt-3 border-t dark:border-zinc-800">
                         <div class="flex items-center justify-between">
                             <label class="block text-xs font-bold text-gray-400 dark:text-zinc-400 uppercase tracking-wider">Adab <span class="normal-case font-semibold">({{ collect($adabDays)->map(fn ($d) => \App\Services\SchoolCalendar::DAY_NAMES[$d])->implode(', ') }})</span></label>
                             @if (in_array(\App\Services\SchoolCalendar::SCOPE_ADAB, $locks, true))
                                 <span class="text-[10px] font-bold text-amber-600 inline-flex items-center gap-1"><x-heroicon-o-lock-closed class="w-3 h-3" /> Terkunci</span>
                             @endif
                         </div>
                         <template x-if="! adabDays.includes(modal.isoDay)">
                             <p class="text-xs text-gray-500 dark:text-zinc-400 bg-gray-50 dark:bg-zinc-800/50 rounded-xl px-3 py-2">Bukan hari pengisian Adab.</p>
                         </template>
                         <template x-if="adabDays.includes(modal.isoDay) && canAdab">
                             <div class="grid grid-cols-2 gap-2">
                                 @foreach (['on' => ['Aktif', 'border-violet-500 bg-violet-50/40 text-violet-800 dark:text-violet-400 dark:border-violet-800'], 'off' => ['Libur Adab', 'border-rose-500 bg-rose-50/40 text-rose-800 dark:text-rose-400 dark:border-rose-800']] as $value => [$label, $color])
                                     <label class="border border-gray-200 dark:border-zinc-800 rounded-xl p-2.5 text-center cursor-pointer transition select-none text-[11px] font-bold"
                                            :class="modal.adab === '{{ $value }}' ? '{{ $color }}' : 'text-gray-700 dark:text-zinc-300 hover:bg-gray-50 dark:hover:bg-zinc-800/50'">
                                         <input type="radio" x-model="modal.adab" value="{{ $value }}" class="sr-only">
                                         {{ $label }}
                                     </label>
                                 @endforeach
                             </div>
                         </template>
                         <template x-if="adabDays.includes(modal.isoDay) && ! canAdab">
                             <p class="text-xs text-gray-600 dark:text-zinc-400 bg-gray-50 dark:bg-zinc-800/50 rounded-xl px-3 py-2">
                                 <span x-text="adabOff(modal.dateStr) ? 'Libur Adab' : 'Aktif'"></span>
                                 <span class="block text-[10px] text-gray-400 mt-0.5">Terkunci &mdash; buka kunci Adab untuk mengubah.</span>
                             </p>
                         </template>
                     </div>

                    <!-- Footer Actions -->
                    <div class="flex justify-end gap-2.5 border-t dark:border-zinc-800 pt-4 mt-6">
                        <button type="button" @click="modal.isOpen = false" class="px-4 py-2 border border-gray-300 dark:border-zinc-700 hover:bg-gray-100 dark:hover:bg-zinc-800 text-gray-700 dark:text-zinc-300 rounded-xl text-xs font-bold transition cursor-pointer">
                            Batal
                        </button>
                        <button type="button" @click="saveModal()" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow transition cursor-pointer">
                            Terapkan
                        </button>
                    </div>

                </div>
            </div>
        </div>

    </div>
</x-app-layout>
