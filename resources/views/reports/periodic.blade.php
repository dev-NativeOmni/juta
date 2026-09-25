<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                    Laporan & Grafik Perkembangan Berkala
                </h2>
                <p class="text-sm text-gray-600 dark:text-zinc-400">
                    Pantau tren grafik setoran hafalan & murajaah serta capaian target kelas secara berkala.
                </p>
            </div>
            
            @if ($selectedClass)
                <a href="{{ route('reports.periodic.print', request()->query()) }}" target="_blank" class="no-print inline-flex items-center gap-2 rounded-xl bg-teal-600 hover:bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-150 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.82l-.24.24c-1.316 1.316-3.484 1.316-4.8 0L1 13.38V9.25a2.25 2.25 0 012.25-2.25h15.5A2.25 2.25 0 0121 9.25v4.13l-.68.68c-1.316 1.316-3.484 1.316-4.8 0l-.24-.24M6.72 13.82A4.488 4.488 0 005.25 17v3.25h13.5V17c0-1.28-.52-2.438-1.37-3.18M6.72 13.82h10.56M9 11.25h.008v.008H9v-.008z" />
                    </svg>
                    <span>Cetak Laporan</span>
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto space-y-5 sm:space-y-6">

            <!-- Filter & Class Selector Card -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-4 sm:p-5 shadow-sm space-y-4">
                
                <!-- Top Row: Date & Period Filter Dropdowns -->
                <form id="periodicFilterForm" method="GET" action="{{ route('reports.periodic') }}" 
                      x-data="{ periodType: '{{ $periodType }}' }"
                      class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 items-end">
                    
                    <input type="hidden" name="class_room_id" id="filter_class_room_id" value="{{ $selectedClassId }}">

                    <!-- Period Type Selector -->
                    <div>
                        <label for="period_type" class="block text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Rentang Waktu</label>
                        <select name="period_type" id="period_type" x-model="periodType" @change="$nextTick(() => $el.form.submit())"
                                class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-xs focus:border-teal-500 focus:ring-teal-500 text-xs sm:text-sm py-2 px-3 font-medium cursor-pointer">
                            <option value="monthly">Bulanan</option>
                            <option value="quarterly">Tiga Bulanan (Term)</option>
                        </select>
                    </div>

                    <!-- Month Selector / Quarter Selector -->
                    <div>
                        <div x-show="periodType === 'monthly'">
                            <label for="month" class="block text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Bulan</label>
                            <select name="month" id="month" @change="$el.form.submit()"
                                    class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-xs focus:border-teal-500 focus:ring-teal-500 text-xs sm:text-sm py-2 px-3 font-medium cursor-pointer">
                                @foreach ($monthsList as $key => $name)
                                    <option value="{{ $key }}" {{ $selectedMonth == $key ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div x-show="periodType === 'quarterly'" style="display: none;">
                            <label for="quarter" class="block text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Term (Triwulan)</label>
                            <select name="quarter" id="quarter" @change="$el.form.submit()"
                                    class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-xs focus:border-teal-500 focus:ring-teal-500 text-xs sm:text-sm py-2 px-3 font-medium cursor-pointer">
                                <option value="1" {{ $selectedQuarter == 1 ? 'selected' : '' }}>Term 1 (Jul - Sep)</option>
                                <option value="2" {{ $selectedQuarter == 2 ? 'selected' : '' }}>Term 2 (Okt - Des)</option>
                                <option value="3" {{ $selectedQuarter == 3 ? 'selected' : '' }}>Term 3 (Jan - Mar)</option>
                                <option value="4" {{ $selectedQuarter == 4 ? 'selected' : '' }}>Term 4 (Apr - Jun)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Year Selector -->
                    <div>
                        <label for="year" class="block text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Tahun</label>
                        <select name="year" id="year" @change="$el.form.submit()"
                                class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-xs focus:border-teal-500 focus:ring-teal-500 text-xs sm:text-sm py-2 px-3 font-medium cursor-pointer">
                            @for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                                <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <!-- Filter Apply Button -->
                    <div class="col-span-2 sm:col-span-3 lg:col-span-1 flex items-center justify-end">
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold text-white bg-teal-600 hover:bg-teal-700 active:scale-95 shadow-sm transition-all duration-150 cursor-pointer min-h-[38px]">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                            <span>Terapkan Filter</span>
                        </button>
                    </div>
                </form>

                <!-- Bottom Row: Class Toggles (Interactive Pills for Accessible Classes) -->
                <div class="border-t border-gray-150 dark:border-zinc-800/80 pt-3">
                    <div class="flex items-center justify-between gap-2 mb-2.5">
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                Toggle Kelas:
                            </span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 font-semibold border border-teal-200/60 dark:border-teal-800/60">
                                {{ $classRooms->count() }} Kelas Tersedia
                            </span>
                        </div>
                        @if ($selectedClass)
                            <span class="text-[11px] text-gray-400 dark:text-zinc-500 hidden sm:inline">
                                Aktif: <strong class="text-teal-600 dark:text-teal-400 font-semibold">{{ $selectedClass->name }}</strong>
                            </span>
                        @endif
                    </div>

                    <!-- Scrollable on mobile, flex-wrap on tablet/desktop -->
                    <div class="flex items-center gap-2 overflow-x-auto pb-2 pt-1 no-scrollbar sm:flex-wrap -mx-1 px-1">
                        @forelse ($classRooms as $class)
                            @php
                                $isActive = ($selectedClassId == $class->id);
                            @endphp
                            <a href="{{ route('reports.periodic', [
                                    'class_room_id' => $class->id,
                                    'period_type' => $periodType,
                                    'month' => $selectedMonth,
                                    'quarter' => $selectedQuarter,
                                    'year' => $selectedYear
                                ]) }}"
                               class="group relative inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all duration-200 shrink-0 cursor-pointer select-none active:scale-95
                                      {{ $isActive 
                                         ? 'bg-teal-600 text-white shadow-md shadow-teal-600/25 ring-2 ring-teal-500/40 font-bold' 
                                         : 'bg-gray-50 dark:bg-zinc-800/80 text-gray-700 dark:text-zinc-300 hover:bg-gray-150 dark:hover:bg-zinc-700 border border-gray-200/90 dark:border-zinc-700/80 hover:border-gray-300 dark:hover:border-zinc-600' }}">
                                
                                @if ($isActive)
                                    <!-- Active Pulsing Dot -->
                                    <span class="flex h-2 w-2 relative">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-200 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                                    </span>
                                @else
                                    <!-- Inactive Dot -->
                                    <span class="h-1.5 w-1.5 rounded-full bg-gray-300 dark:bg-zinc-600 group-hover:bg-teal-500 transition-colors"></span>
                                @endif

                                <span>{{ $class->name }}</span>

                                @if ($class->program)
                                    <span class="text-[10px] px-1.5 py-0.5 rounded-md font-medium
                                                 {{ $isActive 
                                                    ? 'bg-teal-700/60 text-teal-100' 
                                                    : 'bg-gray-200/70 dark:bg-zinc-700/70 text-gray-500 dark:text-zinc-400 group-hover:text-gray-700 dark:group-hover:text-zinc-200' }}">
                                        {{ $class->program->name }}
                                    </span>
                                @endif
                            </a>
                        @empty
                            <div class="text-xs text-gray-400 dark:text-zinc-500 italic py-1">
                                Tidak ada kelas yang dapat diakses untuk akun Anda.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            @if ($selectedClass)
                <!-- Metrics Summary Cards (2-cols on mobile, 4-cols on iPad/Desktop) -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    <!-- Total Murid -->
                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-3.5 sm:p-5 shadow-sm transition hover:border-gray-300 dark:hover:border-zinc-700">
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-[10px] sm:text-xs font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-wider truncate">Total Murid</span>
                            <div class="w-6 h-6 rounded-lg bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-2 text-2xl sm:text-3xl font-black text-gray-900 dark:text-white leading-none font-display">{{ $summary['total_students'] }}</div>
                        <div class="mt-1 text-[10px] sm:text-xs text-gray-500 dark:text-zinc-400 truncate">Murid aktif</div>
                    </div>
                    
                    <!-- Total Setoran -->
                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-3.5 sm:p-5 shadow-sm transition hover:border-teal-300 dark:hover:border-teal-800">
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-[10px] sm:text-xs font-bold text-teal-600 dark:text-teal-400 uppercase tracking-wider truncate">Total Setoran</span>
                            <div class="w-6 h-6 rounded-lg bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-2 text-2xl sm:text-3xl font-black text-teal-600 dark:text-teal-400 leading-none font-display">{{ $summary['total_hafalan'] }}</div>
                        <div class="mt-1 text-[10px] sm:text-xs text-gray-500 dark:text-zinc-400 truncate">Hafalan baru lulus</div>
                    </div>

                    <!-- Total Murajaah -->
                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-3.5 sm:p-5 shadow-sm transition hover:border-amber-300 dark:hover:border-amber-800">
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-[10px] sm:text-xs font-bold text-amber-600 dark:text-amber-450 uppercase tracking-wider truncate">Total Murajaah</span>
                            <div class="w-6 h-6 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-2 text-2xl sm:text-3xl font-black text-amber-600 dark:text-amber-450 leading-none font-display">{{ $summary['total_murajaah'] }}</div>
                        <div class="mt-1 text-[10px] sm:text-xs text-gray-500 dark:text-zinc-400 truncate">Pengulangan lulus</div>
                    </div>

                    <!-- Rerata Nilai -->
                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-3.5 sm:p-5 shadow-sm transition hover:border-indigo-300 dark:hover:border-indigo-800">
                        <div class="flex items-center justify-between gap-1">
                            <span class="text-[10px] sm:text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider truncate">Rerata Nilai</span>
                            <div class="w-6 h-6 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                </svg>
                            </div>
                        </div>
                        <div class="mt-2 text-2xl sm:text-3xl font-black text-indigo-600 dark:text-indigo-400 leading-none font-display">{{ $summary['avg_hafalan_score'] }}</div>
                        <div class="mt-1 text-[10px] sm:text-xs text-gray-500 dark:text-zinc-400 truncate">Skala nilai 100</div>
                    </div>
                </div>

                @php
                    $names = [];
                    $capaians = [];
                    $targets = [];
                    foreach ($studentReports as $rep) {
                        $parts = explode(' ', $rep['student']->name);
                        $shortName = count($parts) > 2 ? $parts[0] . ' ' . $parts[1] . '..' : $rep['student']->name;
                        $names[] = $shortName;
                        $capaians[] = (int) $rep['capaian_baris'];
                        $targets[] = (int) $rep['target_baris'];
                    }
                    $monthName = $monthsList[$selectedMonth] ?? '';
                    $className = $selectedClass?->name ?? '';
                    $titleCapaian = "GRAFIK CAPAIAN BULAN " . strtoupper($monthName) . " KELAS " . strtoupper($className);
                    $titleKetuntasan = "KETUNTASAN BULAN KELAS " . strtoupper($className);
                @endphp

                @if (!empty($isGrade10))
                    <!-- Grade 10 Ummi Capaian Tahfidz Card View -->
                    <div class="space-y-4">
                        <div class="flex justify-between items-center bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 p-4 rounded-2xl shadow-sm flex-wrap gap-3">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                    Laporan Capaian Tahfidz — Pembelajaran UMMI {{ $selectedClass?->name }}
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Tampilan khusus Kelas 10 menyajikan Jilid, Halaman, Capaian Hafalan UMMI, dan Ziyadah.
                                </p>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <button type="button" onclick="downloadUmmiCard()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow transition cursor-pointer">
                                    <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                                    <span>Unduh PNG</span>
                                </button>
                                <button type="button" onclick="printUmmiCard('landscape')" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow transition cursor-pointer">
                                    <x-heroicon-o-printer class="w-4 h-4" />
                                    <span>Cetak Landscape</span>
                                </button>
                                <button type="button" onclick="printUmmiCard('portrait')" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow transition cursor-pointer">
                                    <x-heroicon-o-printer class="w-4 h-4" />
                                    <span>Cetak Portrait</span>
                                </button>
                            </div>
                        </div>

                        @include('reports.partials.ummi-grade10-card')
                    </div>
                @else
                    <!-- Standard Grade 11 & 12 Charts Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left: Capaian & Target Chart (w-2/3) -->
                        <div class="lg:col-span-2 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
                                    <div>
                                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                            {{ $titleCapaian }}
                                        </h3>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" onclick="downloadChart('capaianChart', '{{ $titleCapaian }}')" class="inline-flex items-center gap-1 px-3 py-1.5 bg-teal-50 hover:bg-teal-100 dark:bg-zinc-800 text-teal-700 dark:text-teal-400 text-xs font-bold rounded-lg border border-teal-200 dark:border-zinc-700 transition cursor-pointer">
                                            <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5" />
                                            <span>Unduh PNG</span>
                                        </button>
                                        <button type="button" onclick="printChart('capaianChart', '{{ $titleCapaian }}')" class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-zinc-800 text-indigo-700 dark:text-indigo-400 text-xs font-bold rounded-lg border border-indigo-200 dark:border-zinc-700 transition cursor-pointer">
                                            <x-heroicon-o-printer class="w-3.5 h-3.5" />
                                            <span>Cetak F4</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="relative w-full overflow-x-auto touch-scroll" style="height: 380px;">
                                    <canvas id="capaianChart" style="min-width: 500px; height: 350px;"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Ketuntasan Pie Chart (w-1/3) -->
                        <div class="lg:col-span-1 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
                                    <div>
                                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                            {{ $titleKetuntasan }}
                                        </h3>
                                        <p class="text-xs text-gray-550 dark:text-zinc-400 mt-1">
                                            Persentase ketuntasan target bulanan kelas.
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" onclick="downloadChart('ketuntasanChart', '{{ $titleKetuntasan }}')" class="inline-flex items-center gap-1 px-3 py-1.5 bg-teal-50 hover:bg-teal-100 dark:bg-zinc-800 text-teal-700 dark:text-teal-400 text-xs font-bold rounded-lg border border-teal-200 dark:border-zinc-700 transition cursor-pointer">
                                            <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5" />
                                            <span>Unduh PNG</span>
                                        </button>
                                        <button type="button" onclick="printChart('ketuntasanChart', '{{ $titleKetuntasan }}')" class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-zinc-800 text-indigo-700 dark:text-indigo-400 text-xs font-bold rounded-lg border border-indigo-200 dark:border-zinc-700 transition cursor-pointer">
                                            <x-heroicon-o-printer class="w-3.5 h-3.5" />
                                            <span>Cetak F4</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="relative w-full flex justify-center items-center" style="height: 380px;">
                                    <div style="width: min(280px, 100%); aspect-ratio: 1 / 1;" class="relative flex items-center justify-center">
                                        <canvas id="ketuntasanChart"></canvas>
                                        <!-- Center Metric Summary Overlay inside Donut Hole -->
                                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none text-center pb-4">
                                            @php
                                                $totK = $tuntasCount + $tidakTuntasCount;
                                                $pctK = $totK > 0 ? round(($tuntasCount / $totK) * 100, 1) : 0;
                                            @endphp
                                            <span class="text-3xl font-black text-gray-900 dark:text-white leading-none font-display tracking-tight">
                                                {{ $pctK }}%
                                            </span>
                                            <span class="text-[10px] font-extrabold text-teal-600 dark:text-teal-400 uppercase tracking-widest mt-1">
                                                TUNTAS
                                            </span>
                                            <span class="text-[10px] text-gray-400 dark:text-zinc-500 font-semibold mt-0.5">
                                                {{ $tuntasCount }} dari {{ $totK }} Murid
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Detailed table per student grouped by Teacher & Halaqah -->
                <div class="space-y-8">
                    @forelse ($groupedReports as $teacherName => $halaqahs)
                        <div class="space-y-6">
                            <!-- Teacher Header Bar -->
                            <div class="bg-teal-600 dark:bg-teal-700 text-white px-6 py-3.5 rounded-2xl shadow-sm flex justify-between items-center">
                                <h3 class="text-sm font-extrabold tracking-wider uppercase">Pembimbing: {{ $teacherName }}</h3>
                                <span class="text-xs bg-white/20 px-3 py-1 rounded-full font-bold">
                                    {{ collect($halaqahs)->flatten(1)->count() }} Murid
                                </span>
                            </div>

                            @foreach ($halaqahs as $halaqahLabel => $reports)
                                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
                                    <!-- Halaqah Header -->
                                    <div class="px-6 py-4 border-b border-gray-150 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 flex justify-between items-center">
                                        <div>
                                            <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Halaqah: {{ $halaqahLabel }}</h4>
                                            <p class="text-[11px] text-gray-500 dark:text-zinc-400 mt-0.5">Kelompok halaqah di bawah asuhan {{ $teacherName }}</p>
                                        </div>
                                    </div>

                                    <div class="overflow-x-auto touch-scroll">
                                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800 text-xs">
                                            <thead class="bg-zinc-50 dark:bg-zinc-900/30 text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-wider text-center">
                                                <!-- Row 1: Main Headers -->
                                                <tr class="border-b border-zinc-200 dark:border-zinc-800">
                                                    <th rowspan="2" class="px-4 py-3 text-left w-12 align-middle">No</th>
                                                    <th rowspan="2" class="px-4 py-3 text-left align-middle min-w-[200px]">Nama Murid</th>
                                                    <th rowspan="2" class="px-4 py-3 align-middle">Halaqah</th>
                                                    <th colspan="2" class="px-4 py-2 border-b border-zinc-200 dark:border-zinc-800 text-center">Target</th>
                                                    <th colspan="2" class="px-4 py-2 border-b border-zinc-200 dark:border-zinc-800 text-center">Capaian</th>
                                                    <th rowspan="2" class="px-4 py-3 align-middle">Ketercapaian</th>
                                                    <th colspan="3" class="px-4 py-2 border-b border-zinc-200 dark:border-zinc-800 text-center">Kehadiran</th>
                                                    <th rowspan="2" class="px-4 py-3 align-middle w-24">Pelanggaran</th>
                                                </tr>
                                                <!-- Row 2: Sub-headers -->
                                                <tr class="border-b border-zinc-200 dark:border-zinc-800">
                                                    <th class="px-3 py-1.5 border-r border-zinc-200 dark:border-zinc-800 font-medium">Surat</th>
                                                    <th class="px-3 py-1.5 font-medium">Ayat</th>
                                                    <th class="px-3 py-1.5 border-r border-zinc-200 dark:border-zinc-800 font-medium">Surat</th>
                                                    <th class="px-3 py-1.5 font-medium">Ayat</th>
                                                    <th class="px-2 py-1.5 font-semibold text-rose-600">A</th>
                                                    <th class="px-2 py-1.5 font-semibold text-amber-500">I</th>
                                                    <th class="px-2 py-1.5 font-semibold text-blue-500">S</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800/60 text-center text-gray-700 dark:text-zinc-300">
                                                @foreach ($reports as $index => $row)
                                                    <tr class="hover:bg-zinc-550/[0.01] dark:hover:bg-white/[0.01]">
                                                        <td class="px-4 py-3 text-left font-medium text-gray-500 w-12">{{ $index + 1 }}</td>
                                                        <td class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">
                                                            <div>{{ $row['student']->name }}</div>
                                                            <div class="text-[10px] text-gray-400 font-normal mt-0.5">NIS: {{ $row['student']->student_number ?: '-' }}</div>
                                                        </td>
                                                        <td class="px-4 py-3 text-gray-500 dark:text-zinc-450">{{ $row['halaqah_label'] }}</td>
                                                        <td class="px-3 py-3 border-r border-zinc-150 dark:border-zinc-800 font-medium">{{ $row['target_surah'] }}</td>
                                                        <td class="px-3 py-3 font-semibold">{{ $row['target_ayat'] }}</td>
                                                        <td class="px-3 py-3 border-r border-zinc-150 dark:border-zinc-800 font-medium text-teal-600 dark:text-teal-400">{{ $row['capaian_surah'] }}</td>
                                                        <td class="px-3 py-3 font-bold text-teal-600 dark:text-teal-400">{{ $row['capaian_ayat'] }}</td>
                                                        <td class="px-4 py-3">
                                                            @if ($row['is_tuntas'])
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/30 uppercase">Tuntas</span>
                                                            @else
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-450 border border-rose-200 dark:border-rose-900/30 uppercase">Tidak Tuntas</span>
                                                            @endif
                                                        </td>
                                                         <td class="px-2 py-3 font-semibold {{ $row['alpa'] > 0 ? 'text-rose-650' : 'text-gray-400' }}">{{ $row['alpa'] ?: '-' }}</td>
                                                         <td class="px-2 py-3 font-semibold {{ $row['izin'] > 0 ? 'text-amber-500' : 'text-gray-400' }}">{{ $row['izin'] ?: '-' }}</td>
                                                         <td class="px-2 py-3 font-semibold {{ $row['sakit'] > 0 ? 'text-blue-500' : 'text-gray-400' }}">{{ $row['sakit'] ?: '-' }}</td>
                                                        <!-- Pelanggaran -->
                                                        <td class="px-4 py-3 font-bold {{ $row['violations_count'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-400' }}">
                                                            {{ $row['violations_count'] }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Table Footer with Completeness summary matching spreadsheet -->
                                    <div class="px-6 py-3.5 bg-zinc-50 dark:bg-zinc-900/40 border-t border-gray-150 dark:border-zinc-800 text-[11px] font-bold text-gray-650 dark:text-zinc-400 flex justify-end gap-6">
                                        @php
                                            $totalGroup = count($reports);
                                            $tuntasGroup = collect($reports)->where('is_tuntas', true)->count();
                                            $tidakTuntasGroup = $totalGroup - $tuntasGroup;
                                            $tuntasPct = $totalGroup > 0 ? round(($tuntasGroup / $totalGroup) * 100, 1) : 0;
                                            $tidakTuntasPct = $totalGroup > 0 ? round(($tidakTuntasGroup / $totalGroup) * 100, 1) : 0;
                                        @endphp
                                        <span class="flex items-center gap-1">
                                            Tuntas: <span class="text-teal-600 font-extrabold">{{ $tuntasPct }}%</span> ({{ $tuntasGroup }} murid)
                                        </span>
                                        <span class="flex items-center gap-1">
                                            Tidak Tuntas: <span class="text-rose-600 font-extrabold">{{ $tidakTuntasPct }}%</span> ({{ $tidakTuntasGroup }} murid)
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-8 text-center text-sm text-gray-500 dark:text-zinc-500 shadow-sm">
                            Tidak ada data perkembangan murid pada rentang waktu ini.
                        </div>
                    @endforelse
                </div>
            @else
                <div class="bg-yellow-50 dark:bg-yellow-950/20 border border-yellow-100 dark:border-yellow-900/30 rounded-2xl p-6 text-center text-yellow-800 dark:text-yellow-400">
                     Belum ada kelas yang dapat Anda akses atau tidak ada data murid terdaftar.
                </div>
            @endif

        </div>
    </div>

    @if ($selectedClass)
        @push('scripts')
        <!-- Fallback CDN in case local bundle is delayed or blocked -->
        <script>
            if (typeof Chart === 'undefined') {
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                document.head.appendChild(s);
            }
            if (typeof htmlToImage === 'undefined') {
                const s = document.createElement('script');
                s.src = 'https://cdnjs.cloudflare.com/ajax/libs/html-to-image/1.11.11/html-to-image.min.js';
                document.head.appendChild(s);
            }
        </script>
        <script>
            function downloadUmmiCard() {
                const cardEl = document.getElementById('ummiGrade10ReportCard');
                if (!cardEl) {
                    alert('Elemen laporan tidak ditemukan.');
                    return;
                }
                
                const executeDownload = () => {
                    const width = cardEl.scrollWidth || cardEl.offsetWidth;
                    const height = cardEl.scrollHeight || cardEl.offsetHeight;

                    htmlToImage.toPng(cardEl, {
                        pixelRatio: 2,
                        backgroundColor: '#ffffff',
                        width: width + 24,
                        height: height + 24,
                        style: {
                            margin: '0 auto',
                            padding: '12px',
                            boxSizing: 'border-box',
                            left: '0',
                            top: '0',
                            transform: 'none'
                        }
                    }).then(dataUrl => {
                        const a = document.createElement('a');
                        a.download = 'Laporan_Capaian_Ummi_{{ $selectedClass?->name }}_{{ $monthName }}.png';
                        a.href = dataUrl;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    }).catch(err => {
                        console.error('Gagal mengunduh gambar:', err);
                        alert('Gagal membuat gambar PNG: ' + err.message);
                    });
                };

                if (typeof htmlToImage === 'undefined') {
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html-to-image/1.11.11/html-to-image.min.js';
                    script.onload = executeDownload;
                    document.head.appendChild(script);
                } else {
                    executeDownload();
                }
            }

            function printUmmiCard(orientation = 'landscape') {
                const printUrl = "{!! route('reports.periodic.print', array_merge(request()->query(), ['class_room_id' => $selectedClassId])) !!}&orientation=" + orientation;
                window.open(printUrl, '_blank');
            }

            // Global functions for download and print
            function downloadChart(chartId, title) {
                const canvas = document.getElementById(chartId);
                if (!canvas) return;
                const tempCanvas = document.createElement('canvas');

                const bannerHeight = 70;
                tempCanvas.width = canvas.width;
                tempCanvas.height = canvas.height + bannerHeight;

                const tempCtx = tempCanvas.getContext('2d');
                
                // Fill white background
                tempCtx.fillStyle = '#ffffff';
                tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
                
                // Draw Title Header Banner
                tempCtx.textAlign = 'center';
                tempCtx.fillStyle = '#111827';
                tempCtx.font = 'bold 18px Arial, sans-serif';
                tempCtx.fillText(title, tempCanvas.width / 2, 32);

                tempCtx.fillStyle = '#6B7280';
                tempCtx.font = '13px Arial, sans-serif';
                tempCtx.fillText("TAD-SMAIA7", tempCanvas.width / 2, 54);

                // Divider line
                tempCtx.strokeStyle = '#E5E7EB';
                tempCtx.lineWidth = 1;
                tempCtx.beginPath();
                tempCtx.moveTo(20, 62);
                tempCtx.lineTo(tempCanvas.width - 20, 62);
                tempCtx.stroke();

                // Draw original chart below banner
                tempCtx.drawImage(canvas, 0, bannerHeight);
                
                const url = tempCanvas.toDataURL('image/png');
                const a = document.createElement('a');
                a.href = url;
                a.download = title + '.png';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }

            function printChart(chartId, title) {
                const canvas = document.getElementById(chartId);
                if (!canvas) return;
                
                // Draw on a temp white canvas with 2x resolution
                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = canvas.width * 2;
                tempCanvas.height = canvas.height * 2;
                const tempCtx = tempCanvas.getContext('2d');
                tempCtx.scale(2, 2);
                
                // Fill white
                tempCtx.fillStyle = '#ffffff';
                tempCtx.fillRect(0, 0, canvas.width, canvas.height);
                tempCtx.drawImage(canvas, 0, 0);
                
                const url = tempCanvas.toDataURL('image/png');
                
                const win = window.open('', '_blank');
                win.document.write(`
                    <html>
                        <head>
                            <title>${title}</title>
                            <style>
                                @page {
                                    size: 330mm 215mm; /* F4 Landscape */
                                    margin: 1.5cm;
                                }
                                body {
                                    font-family: Arial, sans-serif;
                                    text-align: center;
                                    margin: 0;
                                    padding: 0;
                                    background: white;
                                }
                                .header-title {
                                    font-size: 24px;
                                    font-weight: bold;
                                    text-transform: uppercase;
                                    margin-top: 15px;
                                    margin-bottom: 25px;
                                    color: #111;
                                }
                                .chart-frame {
                                    width: 100%;
                                    height: 165mm;
                                    display: flex;
                                    justify-content: center;
                                    align-items: center;
                                }
                                img {
                                    max-width: 100%;
                                    max-height: 100%;
                                    object-fit: contain;
                                }
                            </style>
                        </head>
                        <body onload="setTimeout(() => { window.print(); window.close(); }, 500)">
                            <div class="header-title">${title}</div>
                            <div class="chart-frame">
                                <img src="${url}">
                            </div>
                        </body>
                    </html>
                `);
                win.document.close();
            }

            // Expose action handlers to window for inline onclick attributes
            window.downloadUmmiCard = downloadUmmiCard;
            window.printUmmiCard = printUmmiCard;
            window.downloadChart = downloadChart;
            window.printChart = printChart;

            function initPeriodicCharts() {
                if (typeof Chart === 'undefined') {
                    setTimeout(initPeriodicCharts, 100);
                    return;
                }

                // Register datalabels plugin if loaded
                if (typeof ChartDataLabels !== 'undefined') {
                    try {
                        Chart.register(ChartDataLabels);
                    } catch (e) {}
                }

                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.05)';
                const labelColor = isDark ? '#a1a1aa' : '#64748b';

                // --- CAPAIAN vs TARGET CHART (MODERN SAAS GLASSMORPHIC STYLE) ---
                const capaianCanvas = document.getElementById('capaianChart');
                if (capaianCanvas) {
                    const capaianCtx = capaianCanvas.getContext('2d');

                    // Create sleek vertical gradient for bars
                    const barGradient = capaianCtx.createLinearGradient(0, 0, 0, 350);
                    barGradient.addColorStop(0, '#0ea5e9'); // Sky / Cyan top
                    barGradient.addColorStop(1, '#0284c7'); // Ocean Blue bottom

                    // Subtle orange amber area glow for target curve
                    const targetAreaGradient = capaianCtx.createLinearGradient(0, 0, 0, 350);
                    targetAreaGradient.addColorStop(0, 'rgba(249, 115, 22, 0.2)');
                    targetAreaGradient.addColorStop(1, 'rgba(249, 115, 22, 0.0)');

                    new Chart(capaianCtx, {
                        type: 'bar',
                        data: {
                            labels: @json($names),
                            datasets: [
                                {
                                    label: 'CAPAIAN BARIS',
                                    type: 'bar',
                                    data: @json($capaians),
                                    backgroundColor: barGradient,
                                    hoverBackgroundColor: '#38bdf8',
                                    borderWidth: 0,
                                    borderRadius: {
                                        topLeft: 8,
                                        topRight: 8,
                                        bottomLeft: 0,
                                        bottomRight: 0
                                    },
                                    borderSkipped: 'bottom',
                                    barPercentage: 0.65,
                                    categoryPercentage: 0.85,
                                    order: 2,
                                    datalabels: {
                                        anchor: 'end',
                                        align: 'top',
                                        offset: 2,
                                        color: isDark ? '#38bdf8' : '#0284c7',
                                        font: {
                                            family: 'Outfit, Inter, sans-serif',
                                            weight: 'bold',
                                            size: 10
                                        }
                                    }
                                },
                                {
                                    label: 'TARGET BARIS',
                                    type: 'line',
                                    data: @json($targets),
                                    borderColor: '#f97316', // Sunset Amber
                                    borderWidth: 3,
                                    tension: 0.38, // Smooth spline bezier curve
                                    pointBackgroundColor: '#ffffff',
                                    pointBorderColor: '#ea580c',
                                    pointBorderWidth: 2.5,
                                    pointRadius: 4.5,
                                    pointHoverRadius: 7,
                                    pointHoverBackgroundColor: '#ea580c',
                                    pointHoverBorderColor: '#ffffff',
                                    pointHoverBorderWidth: 2,
                                    fill: true,
                                    backgroundColor: targetAreaGradient,
                                    order: 1,
                                    datalabels: {
                                        display: false
                                    }
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    labels: {
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        boxWidth: 8,
                                        padding: 20,
                                        color: labelColor,
                                        font: {
                                            family: 'Inter, sans-serif',
                                            weight: 'bold',
                                            size: 11
                                        }
                                    }
                                },
                                tooltip: {
                                    backgroundColor: isDark ? 'rgba(24, 24, 27, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                                    titleColor: isDark ? '#ffffff' : '#0f172a',
                                    bodyColor: isDark ? '#cbd5e1' : '#334155',
                                    borderColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)',
                                    borderWidth: 1,
                                    padding: 12,
                                    boxPadding: 6,
                                    usePointStyle: true,
                                    titleFont: {
                                        family: 'Outfit, Inter, sans-serif',
                                        weight: 'bold',
                                        size: 12
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    grid: {
                                        color: gridColor,
                                        borderDash: [4, 4]
                                    },
                                    ticks: {
                                        color: labelColor,
                                        stepSize: 10,
                                        font: {
                                            family: 'Inter, sans-serif',
                                            size: 10
                                        }
                                    },
                                    beginAtZero: true
                                },
                                x: {
                                    grid: {
                                        display: false
                                    },
                                    ticks: {
                                        color: labelColor,
                                        font: {
                                            family: 'Inter, sans-serif',
                                            size: 9,
                                            weight: '600'
                                        },
                                        minRotation: 90,
                                        maxRotation: 90
                                    }
                                }
                            }
                        }
                    });
                }

                // --- KETUNTASAN MODERN DONUT CHART ---
                const ketuntasanCanvas = document.getElementById('ketuntasanChart');
                if (ketuntasanCanvas) {
                    const ketuntasanCtx = ketuntasanCanvas.getContext('2d');
                    new Chart(ketuntasanCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['TUNTAS', 'BELUM TUNTAS'],
                            datasets: [{
                                data: [{{ $tuntasCount }}, {{ $tidakTuntasCount }}],
                                backgroundColor: [
                                    '#0d9488', // Emerald Teal
                                    '#f43f5e'  // Coral Rose
                                ],
                                hoverBackgroundColor: [
                                    '#14b8a6',
                                    '#fb7185'
                                ],
                                borderWidth: 4,
                                borderColor: isDark ? '#18181b' : '#ffffff',
                                borderRadius: 6,
                                spacing: 2
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '74%', // Clean donut hole for central metric badge
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'bottom',
                                    labels: {
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        boxWidth: 8,
                                        padding: 16,
                                        color: labelColor,
                                        font: {
                                            family: 'Inter, sans-serif',
                                            weight: '600',
                                            size: 11
                                        }
                                    }
                                },
                                datalabels: {
                                    display: false
                                },
                                tooltip: {
                                    backgroundColor: isDark ? 'rgba(24, 24, 27, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                                    titleColor: isDark ? '#ffffff' : '#0f172a',
                                    bodyColor: isDark ? '#cbd5e1' : '#334155',
                                    borderColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)',
                                    borderWidth: 1,
                                    padding: 10,
                                    boxPadding: 4,
                                    usePointStyle: true,
                                    callbacks: {
                                        label: function(context) {
                                            const total = {{ $tuntasCount + $tidakTuntasCount }};
                                            const val = context.raw || 0;
                                            const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                            return ` ${context.label}: ${val} Murid (${pct}%)`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }

            // Safe DOM Ready execution
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initPeriodicCharts);
            } else {
                initPeriodicCharts();
            }
        </script>
        @endpush
    @endif
</x-app-layout>

