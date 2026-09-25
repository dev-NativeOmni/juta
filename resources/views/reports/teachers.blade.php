<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                    Laporan Kinerja Guru
                </h2>
                <p class="text-sm text-gray-600 dark:text-zinc-400">
                    Evaluasi partisipasi input, target murid, dan rerata nilai hafalan/murajaah guru pendamping.
                </p>
            </div>
            
            <button onclick="window.print()" class="no-print inline-flex items-center gap-2 rounded-xl bg-zinc-900 dark:bg-white/10 px-4 py-2.5 text-sm font-semibold text-white dark:text-zinc-200 border dark:border-white/10 shadow-sm hover:bg-zinc-800 dark:hover:bg-white/15 focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.82l-.24.24c-1.316 1.316-3.484 1.316-4.8 0L1 13.38V9.25a2.25 2.25 0 012.25-2.25h15.5A2.25 2.25 0 0121 9.25v4.13l-.68.68c-1.316 1.316-3.484 1.316-4.8 0l-.24-.24M6.72 13.82A4.488 4.488 0 005.25 17v3.25h13.5V17c0-1.28-.52-2.438-1.37-3.18M6.72 13.82h10.56M9 11.25h.008v.008H9v-.008z" />
                </svg>
                <span>Cetak Laporan</span>
            </button>
        </div>
    </x-slot>

    <!-- Print styling -->
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                color: black !important;
            }
            .print-card {
                border: none !important;
                box-shadow: none !important;
                background: transparent !important;
                padding: 0 !important;
            }
            aside, nav, header {
                display: none !important;
            }
            main {
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>

    <div class="py-4 sm:py-6 lg:py-8">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

            <!-- Filter Form (no-print) -->
            <div class="no-print bg-white dark:bg-zinc-900 rounded-xl sm:rounded-2xl border border-zinc-200/80 dark:border-zinc-800 p-3.5 sm:p-5 shadow-xs transition-colors duration-200">
                <form method="GET" action="{{ route('reports.teachers') }}" class="flex flex-wrap items-end gap-3 sm:gap-4">
                    <div class="flex-1 min-w-[160px] sm:min-w-[200px]">
                        <label for="month" class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5 sm:mb-2">Bulan</label>
                        <select name="month" id="month" onchange="this.form.submit()" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-xs sm:text-sm">
                            @foreach ($months as $key => $name)
                                <option value="{{ $key }}" {{ $selectedMonth == $key ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex-1 min-w-[120px] sm:min-w-[150px]">
                        <label for="year" class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5 sm:mb-2">Tahun</label>
                        <select name="year" id="year" onchange="this.form.submit()" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-xs focus:border-indigo-500 focus:ring-indigo-500 text-xs sm:text-sm">
                            @foreach ($years as $yr)
                                <option value="{{ $yr }}" {{ $selectedYear == $yr ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="inline-flex items-center justify-center px-4 py-2 sm:py-2.5 border border-transparent rounded-xl text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-xs transition-colors min-h-[38px] sm:min-h-[42px]">
                        Terapkan Filter
                    </button>
                </form>
            </div>

            <!-- Formula / Metric Information Box -->
            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950/20 dark:to-purple-950/20 border border-indigo-100 dark:border-indigo-900/40 rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-xs print-card">
                <h3 class="text-xs sm:text-sm font-bold text-indigo-900 dark:text-indigo-300 flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 sm:w-5 sm:h-5 text-indigo-700 dark:text-indigo-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                    </svg>
                    Rumusan Penilaian Kinerja Guru Bulanan (Skala 100)
                </h3>
                <p class="mt-1 sm:mt-1.5 text-[11px] sm:text-xs text-indigo-800 dark:text-indigo-400 leading-relaxed">
                    Penilaian kinerja dirumuskan menggunakan pembobotan gabungan dari keaktifan, pencapaian target hafalan, dan kualitas setoran murid:
                </p>
                <div class="mt-3 sm:mt-4 grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-4 text-xs">
                    <div class="bg-white/80 dark:bg-zinc-900/60 backdrop-blur-sm rounded-xl p-2.5 sm:p-3 border border-indigo-100/50 dark:border-zinc-800">
                        <span class="font-bold text-indigo-950 dark:text-zinc-200 block mb-0.5 sm:mb-1">1. Keaktifan Input (Bobot 40%)</span>
                        Kekerapan input setoran & murajaah. Target minimum <span class="font-semibold text-indigo-700 dark:text-indigo-400">30 input/bulan</span> untuk poin maksimal (40).
                    </div>
                    <div class="bg-white/80 dark:bg-zinc-900/60 backdrop-blur-sm rounded-xl p-2.5 sm:p-3 border border-indigo-100/50 dark:border-zinc-800">
                        <span class="font-bold text-indigo-950 dark:text-zinc-200 block mb-0.5 sm:mb-1">2. Ketercapaian Target (Bobot 40%)</span>
                        Persentase target hafalan murid bimbingan yang selesai (`completed`) dibagi total target pada bulan tersebut (maksimal 40 poin).
                    </div>
                    <div class="bg-white/80 dark:bg-zinc-900/60 backdrop-blur-sm rounded-xl p-2.5 sm:p-3 border border-indigo-100/50 dark:border-zinc-800">
                        <span class="font-bold text-indigo-950 dark:text-zinc-200 block mb-0.5 sm:mb-1">3. Kualitas Hafalan Murid (Bobot 20%)</span>
                        Rerata nilai kelulusan setoran dan murajaah murid dalam skala 100 (maksimal 20 poin).
                    </div>
                </div>
            </div>

            <!-- Performance Table Card -->
            <div class="bg-white dark:bg-zinc-900 rounded-xl sm:rounded-2xl border border-zinc-200/80 dark:border-zinc-800 shadow-xs overflow-hidden print-card transition-colors duration-200">
                <div class="border-b border-zinc-100 dark:border-zinc-800 px-4 py-3 sm:px-6 sm:py-4 bg-gray-50/50 dark:bg-[#09090b]/40 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            Peringkat Kinerja Guru
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">
                            Periode Evaluasi: {{ $months[(int) $selectedMonth] }} {{ $selectedYear }}
                        </p>
                    </div>
                </div>

                @if (empty($performanceData))
                    <div class="p-8 text-center text-sm text-gray-500 dark:text-zinc-500">
                        Belum ada data guru/musyrif yang terdaftar dalam sistem.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800">
                            <thead class="bg-gray-50 dark:bg-[#09090b]/40">
                                <tr>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-505 dark:text-zinc-400 uppercase tracking-wider">
                                        Nama Guru
                                    </th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-505 dark:text-zinc-400 uppercase tracking-wider">
                                        Keaktifan Input (40%)
                                    </th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-505 dark:text-zinc-400 uppercase tracking-wider">
                                        Ketercapaian Target (40%)
                                    </th>
                                    <th scope="col" class="px-6 py-3.5 text-left text-xs font-semibold text-gray-505 dark:text-zinc-400 uppercase tracking-wider">
                                        Rerata Nilai Murid (20%)
                                    </th>
                                    <th scope="col" class="px-6 py-3.5 text-center text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">
                                        Nilai Akhir
                                    </th>
                                    <th scope="col" class="px-6 py-3.5 text-center text-xs font-semibold text-gray-505 dark:text-zinc-400 uppercase tracking-wider">
                                        Kategori
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-800 bg-white dark:bg-zinc-900 transition-colors duration-200">
                                @foreach ($performanceData as $data)
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-white/5 transition-colors">
                                        <!-- Nama Guru -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-800 dark:text-indigo-300 flex items-center justify-center font-bold text-xs uppercase shadow-sm">
                                                    {{ substr($data['teacher']->user?->name ?? 'G', 0, 2) }}
                                                </div>
                                                <div>
                                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                                        {{ $data['teacher']->user?->name ?? 'Tanpa Nama' }}
                                                    </div>
                                                    <div class="text-xs text-gray-500 dark:text-zinc-400">
                                                        Username: {{ $data['teacher']->user?->username ?? '-' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Keaktifan Input (40%) -->
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 dark:text-zinc-300">
                                                <span class="font-bold">{{ $data['total_inputs'] }}</span> input
                                            </div>
                                            <div class="text-xs text-gray-500 mt-0.5">
                                                ({{ $data['total_hafalan'] }} Hafalan, {{ $data['total_murajaah'] }} Murajaah)
                                            </div>
                                            <div class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 mt-1">
                                                Poin: {{ number_format($data['keaktifan_score'], 2) }} / 40
                                            </div>
                                        </td>

                                        <!-- Ketercapaian Target (40%) -->
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 dark:text-zinc-300">
                                                <span class="font-bold">{{ $data['completed_targets'] }}</span> / {{ $data['total_targets'] }} target
                                            </div>
                                            <div class="text-xs text-gray-500 mt-0.5">
                                                Ketercapaian: {{ $data['target_percentage'] }}%
                                            </div>
                                            <div class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 mt-1">
                                                Poin: {{ number_format($data['target_score'], 2) }} / 40
                                            </div>
                                        </td>

                                        <!-- Rerata Nilai Murid (20%) -->
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 dark:text-zinc-300">
                                                Rerata: <span class="font-bold">{{ $data['avg_student_score'] }}</span>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-0.5">
                                                (Hafalan: {{ $data['avg_hafalan_score'] ?? '-' }}, Murajaah: {{ $data['avg_murajaah_score'] ?? '-' }})
                                            </div>
                                            <div class="text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 mt-1">
                                                Poin: {{ number_format($data['student_score_points'], 2) }} / 20
                                            </div>
                                        </td>

                                        <!-- Nilai Kinerja Akhir -->
                                        <td class="px-6 py-4 text-center whitespace-nowrap">
                                            <span class="text-base font-extrabold text-gray-900 dark:text-white">
                                                {{ number_format($data['final_score'], 2) }}
                                            </span>
                                        </td>

                                        <!-- Kategori Badge -->
                                        <td class="px-6 py-4 text-center whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $data['badge_color'] }} uppercase tracking-wider">
                                                {{ $data['category'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <!-- Print Footer Info -->
            <div class="hidden print:block text-right text-xs text-gray-505 dark:text-zinc-500 mt-12">
                <p>Dicetak pada: {{ date('d F Y H:i:s') }}</p>
                <p>Oleh: {{ auth()->user()->name }} · TAD</p>
            </div>

        </div>
    </div>
</x-app-layout>
