<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-0.5">
            <h2 class="font-bold text-lg sm:text-xl text-gray-800 dark:text-zinc-100 leading-tight flex items-center gap-2">
                <x-heroicon-o-academic-cap class="w-5 h-5 sm:w-6 sm:h-6 text-emerald-600 dark:text-emerald-400" /> Dashboard Kepala Sekolah
            </h2>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-zinc-400">
                Ringkasan Tahfizh, Adab, dan Tanse — Bulan {{ date('F Y') }}
            </p>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-5 sm:space-y-6">

            {{-- ═══════════════ TOP KPI CARDS (Frosted Liquid Glass Grid) ═══════════════ --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4.5">

                {{-- Hafalan Bulan Ini --}}
                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Hafalan/bln</span>
                        <div class="w-8 h-8 bg-teal-500/15 text-teal-600 dark:text-teal-400 rounded-xl flex items-center justify-center shadow-2xs">
                            <x-heroicon-o-book-open class="w-4 h-4" />
                        </div>
                    </div>
                    <p class="text-xl sm:text-2xl font-black text-zinc-900 dark:text-white tracking-tight">{{ number_format($hafalanThisMonth) }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-teal-500"></span>
                        <p class="text-[10px] sm:text-xs text-teal-600 dark:text-teal-400 font-semibold truncate">Hari ini: {{ $hafalanToday }}</p>
                    </div>
                </div>

                {{-- Target Selesai --}}
                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Target Tuntas</span>
                        <div class="w-8 h-8 bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 rounded-xl flex items-center justify-center shadow-2xs">
                            <x-heroicon-o-check-badge class="w-4 h-4" />
                        </div>
                    </div>
                    <p class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">{{ $targetRate }}%</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                        <p class="text-[10px] sm:text-xs text-zinc-600 dark:text-zinc-300 font-medium truncate">{{ $completedTargets }}/{{ $activeTargets + $completedTargets }} selesai</p>
                    </div>
                </div>

                {{-- Adab Diisi Hari Ini --}}
                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Adab Hari Ini</span>
                        <div class="w-8 h-8 bg-teal-500/15 text-teal-600 dark:text-teal-400 rounded-xl flex items-center justify-center shadow-2xs">
                            <x-heroicon-o-check-circle class="w-4 h-4" />
                        </div>
                    </div>
                    <p class="text-xl sm:text-2xl font-black text-teal-600 dark:text-teal-400 tracking-tight">{{ $fillPercentage }}%</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-teal-500"></span>
                        <p class="text-[10px] sm:text-xs text-zinc-600 dark:text-zinc-300 font-medium truncate">{{ $adabFilledToday }}/{{ $totalStudents }} murid</p>
                    </div>
                </div>

                {{-- Rata-rata Adab --}}
                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Rerata Adab</span>
                        <div class="w-8 h-8 bg-amber-500/15 text-amber-600 dark:text-amber-400 rounded-xl flex items-center justify-center shadow-2xs">
                            <x-heroicon-o-star class="w-4 h-4" />
                        </div>
                    </div>
                    <p class="text-xl sm:text-2xl font-black text-zinc-900 dark:text-white tracking-tight">{{ $avgAdabScore }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-amber-500"></span>
                        <p class="text-[10px] sm:text-xs text-amber-600 dark:text-amber-400 font-semibold truncate">Predikat: {{ $adabGrade }}</p>
                    </div>
                </div>

                {{-- Pelanggaran Bulan Ini --}}
                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Pelanggaran</span>
                        <div class="w-8 h-8 bg-rose-500/15 text-rose-600 dark:text-rose-400 rounded-xl flex items-center justify-center shadow-2xs">
                            <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                        </div>
                    </div>
                    <p class="text-xl sm:text-2xl font-black text-rose-600 dark:text-rose-400 tracking-tight">{{ $tanseStats['violations'] }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-rose-500"></span>
                        <p class="text-[10px] sm:text-xs text-rose-600 dark:text-rose-400 font-semibold truncate">{{ $tanseStats['violation_points'] }} poin</p>
                    </div>
                </div>

                {{-- Penghargaan Bulan Ini --}}
                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Reward</span>
                        <div class="w-8 h-8 bg-amber-500/15 text-amber-600 dark:text-amber-400 rounded-xl flex items-center justify-center shadow-2xs">
                            <x-heroicon-o-trophy class="w-4 h-4" />
                        </div>
                    </div>
                    <p class="text-xl sm:text-2xl font-black text-amber-600 dark:text-amber-400 tracking-tight">{{ $tanseStats['rewards'] }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-amber-500"></span>
                        <p class="text-[10px] sm:text-xs text-amber-600 dark:text-amber-400 font-semibold truncate">+{{ $tanseStats['reward_points'] ?? 0 }} poin</p>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ SECTION 1: TAHFIZH ═══════════════ --}}
            <div class="glass-liquid-card rounded-2xl shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-4 sm:px-6 py-3.5 sm:py-4 border-b border-zinc-200/70 dark:border-white/10 bg-zinc-50/50 dark:bg-zinc-900/50">
                    <div>
                        <h3 class="text-xs sm:text-base font-bold text-zinc-900 dark:text-white flex items-center gap-1.5 sm:gap-2">
                            <x-heroicon-o-book-open class="w-4 h-4 sm:w-5 sm:h-5 text-teal-600 dark:text-teal-400" /> Perkembangan Tahfizh
                        </h3>
                        <p class="text-[11px] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Aktivitas hafalan per tingkat kelas</p>
                    </div>
                    <a href="{{ route('reports.periodic') }}" class="inline-flex items-center gap-1 text-xs font-bold text-teal-600 dark:text-teal-400 hover:underline">
                        <span>Detail</span> <x-heroicon-m-arrow-right class="w-3.5 h-3.5" />
                    </a>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="grid grid-cols-3 gap-2.5 sm:gap-4 mb-4 sm:mb-6">
                        <div class="rounded-xl glass-liquid-inner p-3 sm:p-4 text-center border border-teal-500/20">
                            <p class="text-[10px] sm:text-xs font-bold text-teal-700 dark:text-teal-300 uppercase tracking-wider mb-0.5">Kelas X</p>
                            <p class="text-xl sm:text-3xl font-black text-teal-800 dark:text-teal-200">{{ $tahfizhByLevel['X'] }}</p>
                            <p class="text-[10px] sm:text-xs text-teal-600 dark:text-teal-400 mt-0.5 hidden sm:block">Setoran bulan ini</p>
                        </div>
                        <div class="rounded-xl glass-liquid-inner p-3 sm:p-4 text-center border border-cyan-500/20">
                            <p class="text-[10px] sm:text-xs font-bold text-cyan-700 dark:text-cyan-300 uppercase tracking-wider mb-0.5">Kelas XI</p>
                            <p class="text-xl sm:text-3xl font-black text-cyan-800 dark:text-cyan-200">{{ $tahfizhByLevel['XI'] }}</p>
                            <p class="text-[10px] sm:text-xs text-cyan-600 dark:text-cyan-400 mt-0.5 hidden sm:block">Setoran bulan ini</p>
                        </div>
                        <div class="rounded-xl glass-liquid-inner p-3 sm:p-4 text-center border border-sky-500/20">
                            <p class="text-[10px] sm:text-xs font-bold text-sky-700 dark:text-sky-300 uppercase tracking-wider mb-0.5">Kelas XII</p>
                            <p class="text-xl sm:text-3xl font-black text-sky-800 dark:text-sky-200">{{ $tahfizhByLevel['XII'] }}</p>
                            <p class="text-[10px] sm:text-xs text-sky-600 dark:text-sky-400 mt-0.5 hidden sm:block">Setoran bulan ini</p>
                        </div>
                    </div>
                    <div class="relative h-[180px] sm:h-[220px]">
                        <canvas id="tahfizhChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ SECTION 2: ADAB ═══════════════ --}}
            <div class="glass-liquid-card rounded-2xl shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-4 sm:px-6 py-3.5 sm:py-4 border-b border-zinc-200/70 dark:border-white/10 bg-zinc-50/50 dark:bg-zinc-900/50">
                    <div>
                        <h3 class="text-xs sm:text-base font-bold text-zinc-900 dark:text-white flex items-center gap-1.5 sm:gap-2">
                            <x-heroicon-o-sparkles class="w-4 h-4 sm:w-5 sm:h-5 text-amber-500 dark:text-amber-400" /> Pengisian Adab
                        </h3>
                        <p class="text-[11px] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Rata-rata nilai adab per tingkat & kehadiran</p>
                    </div>
                    <a href="{{ route('adab.chart') }}" class="inline-flex items-center gap-1 text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline">
                        <span>Detail</span> <x-heroicon-m-arrow-right class="w-3.5 h-3.5" />
                    </a>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="grid grid-cols-3 gap-2.5 sm:gap-4 mb-4 sm:mb-6">
                        <div class="rounded-xl glass-liquid-inner p-3 sm:p-4 text-center border border-amber-500/20">
                            <p class="text-[10px] sm:text-xs font-bold text-amber-700 dark:text-amber-300 uppercase tracking-wider mb-0.5">Kelas X</p>
                            <p class="text-xl sm:text-3xl font-black text-amber-800 dark:text-amber-200">{{ $adabByLevel['X'] }}</p>
                            <p class="text-[10px] sm:text-xs text-amber-600 dark:text-amber-400 mt-0.5 hidden sm:block">Rerata nilai</p>
                        </div>
                        <div class="rounded-xl glass-liquid-inner p-3 sm:p-4 text-center border border-orange-500/20">
                            <p class="text-[10px] sm:text-xs font-bold text-orange-700 dark:text-orange-300 uppercase tracking-wider mb-0.5">Kelas XI</p>
                            <p class="text-xl sm:text-3xl font-black text-orange-800 dark:text-orange-200">{{ $adabByLevel['XI'] }}</p>
                            <p class="text-[10px] sm:text-xs text-orange-600 dark:text-orange-400 mt-0.5 hidden sm:block">Rerata nilai</p>
                        </div>
                        <div class="rounded-xl glass-liquid-inner p-3 sm:p-4 text-center border border-yellow-500/20">
                            <p class="text-[10px] sm:text-xs font-bold text-yellow-700 dark:text-yellow-300 uppercase tracking-wider mb-0.5">Kelas XII</p>
                            <p class="text-xl sm:text-3xl font-black text-yellow-800 dark:text-yellow-200">{{ $adabByLevel['XII'] }}</p>
                            <p class="text-[10px] sm:text-xs text-yellow-600 dark:text-yellow-400 mt-0.5 hidden sm:block">Rerata nilai</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                        <div>
                            <p class="text-[11px] sm:text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider text-center mb-2 sm:mb-3">Kehadiran Pengisian Adab Hari Ini</p>
                            <div class="relative h-[160px] sm:h-[180px]">
                                <canvas id="adabDonutChart"></canvas>
                            </div>
                        </div>
                        <div>
                            <p class="text-[11px] sm:text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider text-center mb-2 sm:mb-3">Rata-rata Nilai Adab per Tingkat</p>
                            <div class="relative h-[160px] sm:h-[180px]">
                                <canvas id="adabBarChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ SECTION 3: TANSE ═══════════════ --}}
            <div class="glass-liquid-card rounded-2xl shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-4 sm:px-6 py-3.5 sm:py-4 border-b border-zinc-200/70 dark:border-white/10 bg-zinc-50/50 dark:bg-zinc-900/50">
                    <div>
                        <h3 class="text-xs sm:base font-bold text-zinc-900 dark:text-white flex items-center gap-1.5 sm:gap-2">
                            <x-heroicon-o-shield-check class="w-4 h-4 sm:w-5 sm:h-5 text-rose-600 dark:text-rose-400" /> Perkembangan Tanse
                        </h3>
                        <p class="text-[11px] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Tren ketahanan sekolah & kedisiplinan</p>
                    </div>
                    <a href="{{ route('student-points.chart') }}" class="inline-flex items-center gap-1 text-xs font-bold text-rose-600 dark:text-rose-400 hover:underline">
                        <span>Detail</span> <x-heroicon-m-arrow-right class="w-3.5 h-3.5" />
                    </a>
                </div>
                <div class="p-4 sm:p-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 sm:gap-4 mb-4 sm:mb-6">
                        <div class="rounded-xl glass-liquid-inner p-3 sm:p-4 text-center border border-rose-500/20">
                            <p class="text-[10px] sm:text-xs font-bold text-rose-700 dark:text-rose-300 uppercase tracking-wider mb-0.5">Pelanggaran</p>
                            <p class="text-xl sm:text-3xl font-black text-rose-800 dark:text-rose-200">{{ $tanseStats['violations'] }}</p>
                            <p class="text-[10px] sm:text-xs text-rose-600 dark:text-rose-400 mt-0.5">Bulan ini</p>
                        </div>
                        <div class="rounded-xl glass-liquid-inner p-3 sm:p-4 text-center border border-orange-500/20">
                            <p class="text-[10px] sm:text-xs font-bold text-orange-700 dark:text-orange-300 uppercase tracking-wider mb-0.5">Total Poin</p>
                            <p class="text-xl sm:text-3xl font-black text-orange-800 dark:text-orange-200">{{ $tanseStats['violation_points'] }}</p>
                            <p class="text-[10px] sm:text-xs text-orange-600 dark:text-orange-400 mt-0.5">Poin dipotong</p>
                        </div>
                        <div class="rounded-xl glass-liquid-inner p-3 sm:p-4 text-center border border-amber-500/20">
                            <p class="text-[10px] sm:text-xs font-bold text-amber-700 dark:text-amber-300 uppercase tracking-wider mb-0.5">Keterlambatan</p>
                            <p class="text-xl sm:text-3xl font-black text-amber-800 dark:text-amber-200">{{ $tanseStats['lateness'] }}</p>
                            <p class="text-[10px] sm:text-xs text-amber-600 dark:text-amber-400 mt-0.5">Kasus</p>
                        </div>
                        <div class="rounded-xl glass-liquid-inner p-3 sm:p-4 text-center border border-purple-500/20">
                            <p class="text-[10px] sm:text-xs font-bold text-purple-700 dark:text-purple-300 uppercase tracking-wider mb-0.5">Reward</p>
                            <p class="text-xl sm:text-3xl font-black text-purple-800 dark:text-purple-200">{{ $tanseStats['rewards'] }}</p>
                            <p class="text-[10px] sm:text-xs text-purple-600 dark:text-purple-400 mt-0.5">Penghargaan</p>
                        </div>
                    </div>
                    <div class="relative h-[180px] sm:h-[220px]">
                        <canvas id="tanseChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ QUICK NAVIGATION ═══════════════ --}}
            <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-3.5 sm:p-5 shadow-sm">
                <h3 class="text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-3 sm:mb-4 flex items-center gap-1.5">
                    <x-heroicon-o-bolt class="w-4 h-4 text-amber-500" /> Akses Cepat Laporan
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-3">
                    <a href="{{ route('reports.periodic') }}"
                        class="flex items-center gap-3 p-3 sm:p-4 bg-teal-50/60 dark:bg-teal-950/30 border border-teal-100 dark:border-teal-900/40 rounded-xl sm:rounded-2xl hover:bg-teal-100/70 dark:hover:bg-teal-950/50 transition">
                        <x-heroicon-o-chart-bar class="w-5 h-5 sm:w-6 sm:h-6 text-teal-600 dark:text-teal-400 shrink-0" />
                        <div>
                            <p class="text-xs sm:text-sm font-bold text-teal-900 dark:text-teal-200">Grafik Tahfizh</p>
                            <p class="text-[10px] sm:text-xs text-teal-700 dark:text-teal-400">Laporan periodik</p>
                        </div>
                    </a>
                    <a href="{{ route('adab.chart') }}"
                        class="flex items-center gap-3 p-3 sm:p-4 bg-amber-50/60 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/40 rounded-xl sm:rounded-2xl hover:bg-amber-100/70 dark:hover:bg-amber-950/50 transition">
                        <x-heroicon-o-sparkles class="w-5 h-5 sm:w-6 sm:h-6 text-amber-600 dark:text-amber-400 shrink-0" />
                        <div>
                            <p class="text-xs sm:text-sm font-bold text-amber-900 dark:text-amber-200">Grafik Adab</p>
                            <p class="text-[10px] sm:text-xs text-amber-700 dark:text-amber-400">Monitoring keagamaan</p>
                        </div>
                    </a>
                    <a href="{{ route('student-points.chart') }}"
                        class="flex items-center gap-3 p-3 sm:p-4 bg-red-50/60 dark:bg-red-950/30 border border-red-100 dark:border-red-900/40 rounded-xl sm:rounded-2xl hover:bg-red-100/70 dark:hover:bg-red-950/50 transition">
                        <x-heroicon-o-shield-check class="w-5 h-5 sm:w-6 sm:h-6 text-red-600 dark:text-red-400 shrink-0" />
                        <div>
                            <p class="text-xs sm:text-sm font-bold text-red-900 dark:text-red-200">Grafik Tanse</p>
                            <p class="text-[10px] sm:text-xs text-red-700 dark:text-red-400">Ketahanan sekolah</p>
                        </div>
                    </a>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        if (typeof Chart === 'undefined') {
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
            document.head.appendChild(s);
        }

        function initHeadmasterCharts() {
            if (typeof Chart === 'undefined') {
                setTimeout(initHeadmasterCharts, 100);
                return;
            }

            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? 'rgba(63,63,70,0.4)' : 'rgba(228,228,231,0.8)';
            const tickColor = isDark ? '#a1a1aa' : '#71717a';

            // Tahfizh Bar Chart
            const tahfizhEl = document.getElementById('tahfizhChart');
            if (tahfizhEl) {
                new Chart(tahfizhEl, {
                    type: 'bar',
                    data: {
                        labels: ['Kelas X', 'Kelas XI', 'Kelas XII'],
                        datasets: [{
                            label: 'Setoran Hafalan',
                            data: [{{ $tahfizhByLevel['X'] }}, {{ $tahfizhByLevel['XI'] }}, {{ $tahfizhByLevel['XII'] }}],
                            backgroundColor: ['#0d9488','#0891b2','#0369a1'],
                            borderRadius: 8,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: tickColor } },
                            x: { grid: { display: false }, ticks: { color: tickColor } }
                        }
                    }
                });
            }

            // Adab Donut
            const adabDonutEl = document.getElementById('adabDonutChart');
            if (adabDonutEl) {
                new Chart(adabDonutEl, {
                    type: 'doughnut',
                    data: {
                        labels: ['Diisi', 'Belum Diisi'],
                        datasets: [{
                            data: [Math.max({{ $adabFilledToday }}, 0), Math.max({{ $totalStudents }} - {{ $adabFilledToday }}, 0)],
                            backgroundColor: ['#10b981','#f59e0b'],
                            borderWidth: 2,
                            borderColor: isDark ? '#18181b' : '#fff',
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false, cutout: '65%',
                        plugins: {
                            legend: { position: 'bottom', labels: { color: tickColor, boxWidth: 12, font: { size: 11 } } }
                        }
                    }
                });
            }

            // Adab Bar per Tingkat
            const adabBarEl = document.getElementById('adabBarChart');
            if (adabBarEl) {
                new Chart(adabBarEl, {
                    type: 'bar',
                    data: {
                        labels: ['Kelas X', 'Kelas XI', 'Kelas XII'],
                        datasets: [{
                            label: 'Rata-rata Nilai Adab',
                            data: [{{ $adabByLevel['X'] }}, {{ $adabByLevel['XI'] }}, {{ $adabByLevel['XII'] }}],
                            backgroundColor: ['#f59e0b','#f97316','#eab308'],
                            borderRadius: 8,
                            borderSkipped: false,
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, max: 100, grid: { color: gridColor }, ticks: { color: tickColor } },
                            x: { grid: { display: false }, ticks: { color: tickColor } }
                        }
                    }
                });
            }

            // Tanse Trend Line
            const tanseEl = document.getElementById('tanseChart');
            if (tanseEl) {
                const tanseTrend = @json($tanseTrend);
                new Chart(tanseEl, {
                    type: 'line',
                    data: {
                        labels: tanseTrend.map(t => t.label),
                        datasets: [{
                            label: 'Poin Pelanggaran',
                            data: tanseTrend.map(t => t.points),
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239,68,68,0.08)',
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#ef4444',
                            pointRadius: 5,
                            pointHoverRadius: 7,
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: tickColor } },
                            x: { grid: { display: false }, ticks: { color: tickColor } }
                        }
                    }
                });
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initHeadmasterCharts);
        } else {
            initHeadmasterCharts();
        }
    </script>
    @endpush
</x-app-layout>
