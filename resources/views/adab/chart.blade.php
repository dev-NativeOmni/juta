<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                    Grafik Perkembangan Pengisian Kuisioner Adab
                </h2>
                <p class="text-sm text-gray-600 dark:text-zinc-400">
                    Persentase keterisian kuisioner harian murid per kelas pada Hari Kerja Efektif (Senin–Jumat).
                </p>
            </div>
            {{-- Filter Periode Bulan & Tahun --}}
            <form method="GET" action="{{ route('adab.chart') }}" class="flex flex-wrap items-center gap-2">
                <select name="month" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-sm font-semibold dark:text-white">
                    @foreach ($monthsList as $mNum => $mName)
                        <option value="{{ $mNum }}" {{ $month === $mNum ? 'selected' : '' }}>{{ $mName }}</option>
                    @endforeach
                </select>
                <select name="year" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-sm font-semibold dark:text-white">
                    @for ($y = (int) now()->format('Y'); $y >= 2024; $y--)
                        <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition-all">
                    Filter
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto space-y-5 sm:space-y-6">

            {{-- Metric Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-5 sm:p-6 shadow-sm flex items-center gap-4">
                    <div class="p-3 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xl">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400">Rata-rata Pengisian Sekolah</span>
                        <div class="text-3xl font-black text-indigo-600 dark:text-indigo-400 mt-1">{{ $overallAttendanceRate }}%</div>
                        <span class="text-[11px] text-gray-400">Periode {{ $monthsList[$month] }} {{ $year }}</span>
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-5 sm:p-6 shadow-sm flex items-center gap-4">
                    <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400">Hari Kerja Efektif</span>
                        <div class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $effectiveDaysTotal }} Hari</div>
                        <span class="text-[11px] text-gray-400">Senin–Jumat (Potong Tanggal Merah)</span>
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-5 sm:p-6 shadow-sm flex items-center gap-4">
                    <div class="p-3 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-xl">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400">Total Kelas Terdaftar</span>
                        <div class="text-3xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ $classReport->count() }} Kelas</div>
                        <span class="text-[11px] text-gray-400">{{ $classReport->sum('total_students') }} Murid Aktif</span>
                    </div>
                </div>
            </div>

            {{-- 12-Month Historical Trend Chart --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-5 sm:p-6 shadow-sm space-y-6">
                <div class="flex items-center justify-between border-b pb-4 dark:border-zinc-800">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Grafik Tren Perkembangan Pengisian Adab (Januari – Desember {{ $year }})</h3>
                        <p class="text-xs text-gray-500">Persentase rata-rata keterisian kuisioner adab murid di seluruh sekolah dari bulan ke bulan.</p>
                    </div>
                </div>

                <div class="overflow-x-auto touch-scroll">
                    <div class="grid grid-cols-12 gap-2 h-44 items-end pt-6 pb-2 px-2 border-b border-gray-100 dark:border-zinc-800 min-w-[560px]">
                    @foreach ($monthlyTrends as $mNum => $tData)
                        @php
                            $tRate = $tData['rate'];
                            $isSel = ($mNum === $month);
                            $barBg = $isSel ? 'bg-indigo-600 dark:bg-indigo-500' : 'bg-indigo-200 dark:bg-indigo-950/60 hover:bg-indigo-300';
                        @endphp
                        <div class="flex flex-col items-center gap-1.5 h-full justify-end group">
                            <span class="text-[10px] font-extrabold text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform">{{ $tRate }}%</span>
                            <div class="w-full max-w-[28px] bg-gray-100 dark:bg-zinc-800 rounded-t-lg overflow-hidden flex items-end h-full">
                                <div class="w-full rounded-t-lg {{ $barBg }} transition-all duration-300" style="height: {{ max(4, $tRate) }}%"></div>
                            </div>
                            <span class="text-[10px] font-bold uppercase {{ $isSel ? 'text-indigo-600 dark:text-indigo-400 font-extrabold' : 'text-gray-400' }}">{{ $tData['month_name'] }}</span>
                        </div>
                    @endforeach
                    </div>
                </div>
            </div>
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-6 shadow-sm space-y-6">
                <div class="flex items-center justify-between border-b pb-4 dark:border-zinc-800">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Visualisasi Grafik Keterisian Kuisioner Adab per Kelas</h3>
                        <p class="text-xs text-gray-500">Keterisian diukur dari persentase hari mengisi terhadap {{ $effectiveDaysTotal }} hari efektif bulan {{ $monthsList[$month] }} {{ $year }}.</p>
                    </div>
                </div>

                <div class="space-y-4">
                    @forelse ($classReport as $item)
                        @php
                            $rate = $item['attendance_rate'];
                            $colorClass = $rate >= 80 ? 'bg-emerald-500' : ($rate >= 60 ? 'bg-indigo-500' : ($rate >= 40 ? 'bg-amber-500' : 'bg-rose-500'));
                            $textClass = $rate >= 80 ? 'text-emerald-600 dark:text-emerald-400' : ($rate >= 60 ? 'text-indigo-600 dark:text-indigo-400' : ($rate >= 40 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400'));
                        @endphp
                        <div class="space-y-1.5">
                            <div class="flex justify-between items-center text-sm font-semibold">
                                <span class="text-gray-800 dark:text-zinc-200 flex items-center gap-2">
                                    <span class="font-bold">{{ $item['class_room']->name }}</span>
                                    <span class="text-xs font-normal text-gray-400">({{ $item['total_students'] }} Murid | Rerata {{ $item['avg_filled_days'] }} Hari Terisi)</span>
                                </span>
                                <span class="font-black text-base {{ $textClass }}">{{ $rate }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-zinc-800 h-4 rounded-full overflow-hidden flex items-center p-0.5">
                                <div class="h-3 rounded-full {{ $colorClass }} transition-all duration-500" style="width: {{ max(2, $rate) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 text-gray-400">Belum ada data kelas yang terdaftar.</div>
                    @endforelse
                </div>
            </div>

            {{-- Detail Tabel Rekap per Kelas --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-sm overflow-hidden" x-data="{ openClass: null }">
                <div class="p-6 border-b border-gray-200 dark:border-zinc-800">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Rekapitulasi Detail per Murid dalam Kelas</h3>
                </div>

                <div class="divide-y divide-gray-200 dark:divide-zinc-800">
                    @foreach ($classReport as $cIdx => $item)
                        <div class="p-6">
                            <div class="flex items-center justify-between cursor-pointer" @click="openClass = (openClass === {{ $cIdx }} ? null : {{ $cIdx }})">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-sm">
                                        {{ $cIdx + 1 }}
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-base text-gray-900 dark:text-white">{{ $item['class_room']->name }}</h4>
                                        <p class="text-xs text-gray-500">Pendamping Adab: {{ $item['class_room']->pendampingAdab?->name ?? 'Belum ditentukan' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <span class="block text-xs font-bold uppercase text-gray-400">Keterisian Kelas</span>
                                        <span class="text-lg font-black text-indigo-600 dark:text-indigo-400">{{ $item['attendance_rate'] }}%</span>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-400 transform transition-transform" :class="openClass === {{ $cIdx }} ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </div>

                            {{-- Accordion Body: Murid Details --}}
                            <div x-show="openClass === {{ $cIdx }}" x-collapse class="mt-4 pt-4 border-t border-gray-100 dark:border-zinc-800">
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left text-xs">
                                        <thead class="bg-gray-50 dark:bg-zinc-800/50 text-gray-500 uppercase font-bold">
                                            <tr>
                                                <th class="p-3">No</th>
                                                <th class="p-3">Nama Murid</th>
                                                <th class="p-3 text-center">Hari Terisi (dari {{ $effectiveDaysTotal }} Efektif)</th>
                                                <th class="p-3 text-center">Kerajinan Kuisioner (40%)</th>
                                                <th class="p-3 text-center">Skor Akhir Adab</th>
                                                <th class="p-3 text-center">Predikat</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                                            @forelse ($item['students_detail'] as $sIdx => $sDet)
                                                <tr class="hover:bg-gray-50/50 dark:hover:bg-zinc-800/30">
                                                    <td class="p-3 font-semibold text-gray-500">{{ $sIdx + 1 }}</td>
                                                    <td class="p-3 font-bold text-gray-900 dark:text-white">
                                                        <a href="{{ route('adab.show', $sDet['student']) }}" class="hover:text-indigo-600 hover:underline">
                                                            {{ $sDet['student']->name }}
                                                        </a>
                                                    </td>
                                                    <td class="p-3 text-center font-bold text-gray-800 dark:text-zinc-200">
                                                        {{ $sDet['filled_days'] }} / {{ $effectiveDaysTotal }} Hari
                                                    </td>
                                                    <td class="p-3 text-center font-extrabold text-indigo-600 dark:text-indigo-400">
                                                        {{ $sDet['attendance_rate'] }}%
                                                    </td>
                                                    <td class="p-3 text-center font-extrabold text-teal-600 dark:text-teal-400">
                                                        {{ $sDet['final_score'] }} / 100
                                                    </td>
                                                    <td class="p-3 text-center font-black">
                                                        <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300">
                                                            {{ $sDet['grade'] }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="p-4 text-center text-gray-400 italic">Belum ada murid aktif di kelas ini.</td>
                                                </tr>
                                            @endforelse
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
</x-app-layout>
