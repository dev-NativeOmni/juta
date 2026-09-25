@php
    $isReward = $category === 'reward';
    $noun = $isReward ? 'Penghargaan' : 'Pelanggaran';
    $unit = $isReward ? 'Prestasi' : 'Kasus';
    $typeStyles = [
        'lateness' => ['icon' => 'heroicon-o-clock', 'chip' => 'bg-amber-50 text-amber-700 border-amber-200', 'text' => 'text-amber-600'],
        'attribute' => ['icon' => 'heroicon-o-tag', 'chip' => 'bg-blue-50 text-blue-700 border-blue-200', 'text' => 'text-blue-600'],
        'violation' => ['icon' => 'heroicon-o-document-text', 'chip' => 'bg-rose-50 text-rose-700 border-rose-200', 'text' => 'text-rose-600'],
        'academic' => ['icon' => 'heroicon-o-academic-cap', 'chip' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'text' => 'text-emerald-600'],
        'non-academic' => ['icon' => 'heroicon-o-star', 'chip' => 'bg-sky-50 text-sky-700 border-sky-200', 'text' => 'text-sky-600'],
        'other' => ['icon' => 'heroicon-o-sparkles', 'chip' => 'bg-gray-50 text-gray-700 border-gray-200', 'text' => 'text-gray-600'],
    ];
    $accentText = $isReward ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400';
    $accentIconBox = $isReward ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400' : 'bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400';
    $accentBadge = $isReward ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300';
    $barSelected = $isReward ? 'bg-emerald-600 dark:bg-emerald-500' : 'bg-rose-600 dark:bg-rose-500';
    $barIdle = $isReward ? 'bg-emerald-200 dark:bg-emerald-950/60 hover:bg-emerald-300' : 'bg-rose-200 dark:bg-rose-950/60 hover:bg-rose-300';
    $barGradient = $isReward ? 'from-teal-400 to-emerald-600' : 'from-amber-500 to-rose-600';
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="font-bold text-xl sm:text-2xl text-zinc-900 dark:text-zinc-100 leading-tight flex items-center gap-2">
                    <x-heroicon-o-shield-check class="w-6 h-6 text-indigo-600 dark:text-indigo-400 shrink-0" />
                    <span>Laporan & Rekapitulasi Ketahanan Sekolah (Tanse)</span>
                </h2>
                <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                    @if ($isReward)
                        Pemantauan tren dan peringkat penghargaan & prestasi murid (Akademik dan Non-Akademik).
                    @else
                        Pemantauan tren dan peringkat pelanggaran kedisiplinan murid (Tata Tertib, Keterlambatan, dan Atribut/Seragam).
                    @endif
                </p>
            </div>

            {{-- Advanced Filter Form --}}
            <form method="GET" action="{{ route('student-points.chart') }}" class="flex flex-wrap items-center gap-2">
                <select name="time_frame" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-xs font-semibold py-2 px-3 focus:ring-indigo-500 dark:text-white">
                    <option value="month" @selected($timeFrame === 'month')>Bulan Terpilih</option>
                    <option value="all" @selected($timeFrame === 'all')>Semua Waktu (Akumulasi)</option>
                </select>

                {{-- Ganti jenis catatan: sub-jenis dikembalikan ke "Semua" supaya tidak memakai tipe jenis lain. --}}
                <select name="category" onchange="this.form.violation_type.value = 'all'; this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-xs font-bold py-2 px-3 focus:ring-indigo-500 dark:text-white">
                    <option value="violation" @selected(! $isReward)>Pelanggaran</option>
                    <option value="reward" @selected($isReward)>Penghargaan / Prestasi</option>
                </select>

                <select name="violation_type" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-xs font-semibold py-2 px-3 focus:ring-indigo-500 dark:text-white">
                    @if ($isReward)
                        <option value="all" @selected($violationType === 'all')>Semua Prestasi</option>
                        <option value="academic" @selected($violationType === 'academic')>Akademik</option>
                        <option value="non-academic" @selected($violationType === 'non-academic')>Non-Akademik</option>
                    @else
                        <option value="all" @selected($violationType === 'all')>Semua Pelanggaran</option>
                        <option value="lateness" @selected($violationType === 'lateness')>Keterlambatan</option>
                        <option value="attribute" @selected($violationType === 'attribute')>Atribut / Seragam</option>
                        <option value="violation" @selected($violationType === 'violation')>Tata Tertib</option>
                    @endif
                </select>

                <select name="sort_by" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-xs font-semibold py-2 px-3 focus:ring-indigo-500 dark:text-white">
                    <option value="count" @selected($sortBy === 'count')>Urutkan: {{ $isReward ? 'Prestasi' : 'Kasus' }} Terbanyak</option>
                    <option value="points" @selected($sortBy === 'points')>Urutkan: Poin Terbanyak</option>
                </select>

                <select name="class_room_id" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-xs font-semibold py-2 px-3 focus:ring-indigo-500 dark:text-white">
                    <option value="">Semua Kelas</option>
                    @foreach ($classRooms as $cRoom)
                        <option value="{{ $cRoom->id }}" @selected((string) $classRoomId === (string) $cRoom->id)>
                            {{ $cRoom->name }}
                        </option>
                    @endforeach
                </select>

                @if ($timeFrame === 'month')
                    <select name="month" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-xs font-semibold py-2 px-3 focus:ring-indigo-500 dark:text-white">
                        @foreach ($monthsList as $mNum => $mName)
                            <option value="{{ $mNum }}" @selected($mNum === $month)>{{ $mName }}</option>
                        @endforeach
                    </select>

                    <select name="year" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-xs font-semibold py-2 px-3 focus:ring-indigo-500 dark:text-white">
                        @for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                            <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                        @endfor
                    </select>
                @endif

                <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                    <x-heroicon-o-magnifying-glass class="w-3.5 h-3.5" />
                    <span>Filter</span>
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6" x-data="{ viewMode: 'leaderboard' }">
        <div class="max-w-7xl mx-auto space-y-5 sm:space-y-6">

            {{-- View Mode Tabs --}}
            <div class="flex items-center gap-2 border-b border-gray-200 dark:border-zinc-800 pb-3 overflow-x-auto touch-scroll">
                <button
                    type="button"
                    @click="viewMode = 'leaderboard'"
                    :class="viewMode === 'leaderboard' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-700'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-1.5 whitespace-nowrap shrink-0"
                >
                    <x-heroicon-o-trophy class="w-4 h-4 shrink-0" />
                    <span>Peringkat Murid Terbanyak (<span x-text="{{ $studentLeaderboard->count() }}"></span>)</span>
                </button>

                <button
                    type="button"
                    @click="viewMode = 'class_report'"
                    :class="viewMode === 'class_report' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-700'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-1.5 whitespace-nowrap shrink-0"
                >
                    <x-heroicon-o-academic-cap class="w-4 h-4 shrink-0" />
                    <span>Rekapitulasi per Kelas</span>
                </button>
            </div>

            {{-- Summary Metric Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                {{-- Total Pelanggaran --}}
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-3 {{ $accentIconBox }} rounded-xl">
                            <x-dynamic-component :component="$isReward ? 'heroicon-o-trophy' : 'heroicon-o-exclamation-triangle'" class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Total {{ $noun }}</p>
                            <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">{{ $monthViolationsCount }} <span class="text-xs font-normal text-gray-400">{{ $unit }}</span></h3>
                            <p class="text-[11px] text-gray-400 mt-0.5">{{ $timeFrame === 'all' ? 'Semua Waktu' : $monthsList[$month] . ' ' . $year }}</p>
                        </div>
                    </div>
                </div>

                {{-- Total Poin Pelanggaran --}}
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-xl">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Total Akumulasi Poin</p>
                            <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">{{ $monthViolationsPoints }} <span class="text-xs font-normal text-gray-400">Poin</span></h3>
                            <p class="text-[11px] text-gray-400 mt-0.5">{{ $isReward ? 'Poin Prestasi' : 'Dampak Kedisiplinan' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Murid Terbanyak Pelanggaran --}}
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-3 {{ $accentIconBox }} rounded-xl">
                            <x-heroicon-o-trophy class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-550 dark:text-zinc-400 uppercase tracking-wider">Murid Terbanyak</p>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white mt-0.5 truncate max-w-[150px]">
                                {{ $studentLeaderboard->first()['student']->name ?? '-' }}
                            </h3>
                            <p class="text-[11px] {{ $accentText }} font-semibold mt-0.5">
                                {{ $studentLeaderboard->first()['violation_count'] ?? 0 }} {{ $unit }} ({{ $studentLeaderboard->first()['violation_points'] ?? 0 }} Poin)
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Rincian Tipe Pelanggaran --}}
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
                    <p class="text-xs font-semibold text-gray-550 dark:text-zinc-400 uppercase tracking-wider mb-2">Sebaran {{ $isReward ? 'Jenis Prestasi' : 'Tipe Pelanggaran' }}</p>
                    <div class="grid grid-cols-3 gap-1.5">
                        @foreach ($typeLabels as $typeKey => $typeLabel)
                            <div class="p-2 border rounded-xl text-center flex flex-col items-center justify-center {{ $typeStyles[$typeKey]['chip'] }}">
                                <span class="text-[10px] font-bold flex flex-wrap items-center justify-center gap-1 leading-tight text-center" title="{{ $typeLabel }}">
                                    <x-dynamic-component :component="$typeStyles[$typeKey]['icon']" class="w-3 h-3 shrink-0" />
                                    <span>{{ $typeLabel }}</span>
                                </span>
                                <span class="text-base font-black mt-0.5">{{ $typeBreakdown[$typeKey] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- VIEW 1: REKAP & PERINGKAT MURID TERBANYAK (LEADERBOARD) -->
            <div x-show="viewMode === 'leaderboard'" class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-6 shadow-sm space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b pb-4 dark:border-zinc-800">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-o-trophy class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                            <span>Rekapitulasi Peringkat Murid dengan {{ $noun }} Terbanyak</span>
                        </h3>
                        <p class="text-xs text-gray-500">
                            Diurutkan berdasarkan {{ $sortBy === 'points' ? 'Total Poin Terbanyak' : 'Jumlah '.$unit.' Terbanyak' }} ({{ $timeFrame === 'all' ? 'Akumulasi Semua Waktu' : $monthsList[$month] . ' ' . $year }}).
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800">
                        <thead>
                            <tr class="bg-gray-50/80 dark:bg-zinc-800/50 text-left text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                                <th class="px-4 py-3 text-center w-16">Peringkat</th>
                                <th class="px-4 py-3">Nama Murid / NIS</th>
                                <th class="px-4 py-3">Kelas / Halaqah</th>
                                <th class="px-4 py-3 text-center">Total {{ $isReward ? 'Prestasi' : 'Kasus' }}</th>
                                <th class="px-4 py-3 text-center">Total Poin</th>
                                <th class="px-4 py-3">Rincian {{ $noun }}</th>
                                <th class="px-4 py-3">{{ $isReward ? 'Prestasi Terakhir' : 'Catatan / Sanksi Terakhir' }}</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800 text-xs">
                            @forelse ($studentLeaderboard as $rank => $item)
                                @php
                                    $st = $item['student'];
                                    $rankNum = $rank + 1;
                                    $rankBadgeClass = match(true) {
                                        $rankNum === 1 => 'bg-rose-600 text-white font-black shadow-md scale-110',
                                        $rankNum === 2 => 'bg-amber-500 text-white font-bold',
                                        $rankNum === 3 => 'bg-orange-500 text-white font-bold',
                                        default => 'bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 font-bold'
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-zinc-800/40 transition">
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs {{ $rankBadgeClass }}">
                                            #{{ $rankNum }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-bold text-gray-900 dark:text-white text-sm">
                                            {{ $st->name }}
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-mono">
                                            NIS: {{ $st->student_number ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap font-semibold text-gray-700 dark:text-zinc-300">
                                        {{ $st->classRoom?->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black {{ $accentBadge }}">
                                            {{ $item['violation_count'] }} {{ $unit }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                            {{ $item['violation_points'] }} Poin
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5">
                                            @foreach ($typeLabels as $typeKey => $typeLabel)
                                                @if ($item['type_counts'][$typeKey] > 0)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md border text-[10px] font-bold {{ $typeStyles[$typeKey]['chip'] }}">
                                                        <x-dynamic-component :component="$typeStyles[$typeKey]['icon']" class="w-3 h-3" />
                                                        <span>{{ $typeLabel }}: {{ $item['type_counts'][$typeKey] }}</span>
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if (!empty($item['recent_notes']))
                                            <span class="text-xs font-semibold {{ $accentText }} flex items-center gap-1 truncate max-w-[200px]" title="{{ implode(', ', $item['recent_notes']) }}">
                                                <x-dynamic-component :component="$isReward ? 'heroicon-o-trophy' : 'heroicon-o-exclamation-triangle'" class="w-3.5 h-3.5 shrink-0" />
                                                <span>{{ implode(', ', $item['recent_notes']) }}</span>
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400 italic">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right font-medium">
                                        <a href="{{ route('student-points.index', ['search' => $st->name]) }}" class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 rounded-lg text-[11px] font-bold hover:bg-indigo-100 transition">
                                            <x-heroicon-o-magnifying-glass class="w-3 h-3" />
                                            <span>Detail Log</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center text-gray-400 dark:text-zinc-500">
                                        Belum ada data {{ strtolower($noun) }} murid pada filter ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VIEW 2: REKAPITULASI PER KELAS -->
            <div x-show="viewMode === 'class_report'" class="space-y-6">

                {{-- 12-Month Historical Trend Chart --}}
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-6 shadow-sm space-y-6">
                    <div class="flex items-center justify-between border-b pb-4 dark:border-zinc-800">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Grafik Tren Perkembangan {{ $noun }} Sekolah (Januari – Desember {{ $year }})</h3>
                            <p class="text-xs text-gray-500">Jumlah {{ $isReward ? 'penghargaan & prestasi' : 'kasus pelanggaran kedisiplinan' }} murid di seluruh sekolah dari bulan ke bulan.</p>
                        </div>
                    </div>

                    @php
                        $maxCount = max(1, collect($monthlyTrends)->max('count'));
                    @endphp
                    <div class="grid grid-cols-12 gap-2 h-44 items-end pt-6 pb-2 px-2 border-b border-gray-100 dark:border-zinc-800">
                        @foreach ($monthlyTrends as $mNum => $tData)
                            @php
                                $tCount = $tData['count'];
                                $isSel = ($mNum === $month);
                                $heightPct = round(($tCount / $maxCount) * 100);
                                $barBg = $isSel ? $barSelected : $barIdle;
                            @endphp
                            <div class="flex flex-col items-center gap-1.5 h-full justify-end group">
                                <span class="text-[10px] font-extrabold {{ $accentText }} group-hover:scale-110 transition-transform">{{ $tCount }}</span>
                                <div class="w-full max-w-[28px] bg-gray-100 dark:bg-zinc-800 rounded-t-lg overflow-hidden flex items-end h-full">
                                    <div class="w-full rounded-t-lg {{ $barBg }} transition-all duration-300" style="height: {{ max(4, $heightPct) }}%"></div>
                                </div>
                                <span class="text-[10px] font-bold uppercase {{ $isSel ? $accentText.' font-extrabold' : 'text-gray-400' }}">{{ $tData['month_name'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Visual Bar Chart per Kelas --}}
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-6 shadow-sm space-y-6">
                    <div class="flex items-center justify-between border-b pb-4 dark:border-zinc-800">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Peringkat {{ $noun }} per Kelas (Diurutkan dari Terbanyak)</h3>
                            <p class="text-xs text-gray-500">Perbandingan jumlah dan poin {{ strtolower($noun) }} antar kelas bulan {{ $monthsList[$month] }} {{ $year }}.</p>
                        </div>
                    </div>

                    @php
                        $maxClassCount = max(1, $classReport->max('violation_count'));
                    @endphp

                    <div class="space-y-4">
                        @forelse ($classReport as $index => $item)
                            @php
                                $cRoom = $item['class_room'];
                                $vCount = $item['violation_count'];
                                $vPoints = $item['violation_points'];
                                $pct = round(($vCount / $maxClassCount) * 100);
                                $badgeColor = $index === 0 && $vCount > 0 ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300' : 'bg-gray-100 text-gray-700 dark:bg-zinc-800 dark:text-zinc-300';
                            @endphp
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-full {{ $badgeColor }} flex items-center justify-center text-xs font-bold">#{{ $index + 1 }}</span>
                                        <span class="text-gray-900 dark:text-white font-bold">{{ $cRoom->name }}</span>
                                        <span class="text-xs text-gray-400">({{ $item['total_students'] }} Murid)</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-bold {{ $accentText }}">{{ $vCount }} {{ $isReward ? 'Prestasi' : 'Kasus' }}</span>
                                        <span class="text-xs text-gray-500">({{ $vPoints }} Poin)</span>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-zinc-800 rounded-full h-3 overflow-hidden border border-gray-200/50 dark:border-zinc-700/50">
                                    <div class="bg-gradient-to-r {{ $barGradient }} h-full rounded-full transition-all duration-500" style="width: {{ max(2, $pct) }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-center py-6 text-gray-400 text-sm">Belum ada data kelas terdaftar.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Rekapitulasi Detail per Murid dalam Kelas --}}
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-6 shadow-sm space-y-6">
                    <div class="border-b pb-4 dark:border-zinc-800">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Rekapitulasi Detail {{ $noun }} Murid per Kelas</h3>
                        <p class="text-xs text-gray-500">Klik kelas untuk melihat rincian {{ $isReward ? 'prestasi akademik & non-akademik' : 'pelanggaran keterlambatan, atribut, tata tertib, dan sanksi' }} per murid.</p>
                    </div>

                    <div class="space-y-4" x-data="{ openClass: null }">
                        @foreach ($classReport as $cIndex => $item)
                            @php
                                $cRoom = $item['class_room'];
                                $stDetails = $item['students_detail'];
                            @endphp
                            <div class="border border-gray-200 dark:border-zinc-800 rounded-xl overflow-hidden transition-colors">
                                <button
                                    type="button"
                                    @click="openClass = (openClass === {{ $cIndex }} ? null : {{ $cIndex }})"
                                    class="w-full px-5 py-4 flex items-center justify-between bg-gray-50 dark:bg-zinc-800/40 hover:bg-gray-100 dark:hover:bg-zinc-800 transition text-left cursor-pointer"
                                >
                                    <div class="flex items-center gap-3">
                                        <span class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-bold text-xs flex items-center justify-center">
                                            {{ $cIndex + 1 }}
                                        </span>
                                        <div>
                                            <h4 class="font-bold text-gray-900 dark:text-white text-base">{{ $cRoom->name }}</h4>
                                            <p class="text-xs text-gray-500">Total Murid: {{ $item['total_students'] }} | Total {{ $unit }}: {{ $item['violation_count'] }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-bold {{ $accentText }}">{{ $item['violation_points'] }} Poin</span>
                                        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openClass === {{ $cIndex }} ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </button>

                                <div x-show="openClass === {{ $cIndex }}" x-cloak class="p-4 border-t border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-xs">
                                            <thead>
                                                <tr class="text-gray-500 dark:text-zinc-400 uppercase font-bold text-[10px]">
                                                    <th class="py-2 px-3 text-left">Nama Murid</th>
                                                    @foreach ($typeLabels as $typeKey => $typeLabel)
                                                        <th class="py-2 px-3 text-center">
                                                            <span class="inline-flex items-center justify-center gap-1">
                                                                <x-dynamic-component :component="$typeStyles[$typeKey]['icon']" class="w-3 h-3 {{ $typeStyles[$typeKey]['text'] }}" />
                                                                <span>{{ $typeLabel }}</span>
                                                            </span>
                                                        </th>
                                                    @endforeach
                                                    <th class="py-2 px-3 text-center">Total {{ $isReward ? 'Prestasi' : 'Kasus' }}</th>
                                                    <th class="py-2 px-3 text-center">Total Poin</th>
                                                    <th class="py-2 px-3 text-left">{{ $isReward ? 'Prestasi Terakhir' : 'Sanksi Terakhir' }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 dark:divide-zinc-800/80">
                                                @foreach ($stDetails as $sDet)
                                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-zinc-800/30">
                                                        <td class="py-2.5 px-3 font-bold text-gray-900 dark:text-white">
                                                            {{ $sDet['student']->name }}
                                                        </td>
                                                        @foreach ($typeLabels as $typeKey => $typeLabel)
                                                            <td class="py-2.5 px-3 text-center font-bold {{ $typeStyles[$typeKey]['text'] }}">{{ $sDet['type_counts'][$typeKey] }}</td>
                                                        @endforeach
                                                        <td class="py-2.5 px-3 text-center font-black text-gray-900 dark:text-white">{{ $sDet['violation_count'] }}</td>
                                                        <td class="py-2.5 px-3 text-center font-black {{ $accentText }}">{{ $sDet['violation_points'] }}</td>
                                                        <td class="py-2.5 px-3 text-gray-500 italic">
                                                            {{ implode(', ', $sDet['recent_notes']) ?: '-' }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
