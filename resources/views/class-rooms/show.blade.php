<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail Kelas
            </h2>

            <a href="{{ route('class-rooms.edit', $classRoom) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                Edit Kelas
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Nama Kelas</p>
                        <p class="font-semibold text-gray-900">{{ $classRoom->name }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Program</p>
                        <p class="font-semibold text-gray-900">{{ $classRoom->program?->name ?: '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Level</p>
                        <p class="font-semibold text-gray-900">{{ $classRoom->level ?: '-' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Jumlah Murid</p>
                        <p class="font-semibold text-gray-900">{{ $classRoom->students_count }}</p>
                    </div>
                </div>
            </div>

            @php
                $months = [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                ];
                $isReguler1112 = ($classRoom->level == 11 || $classRoom->level == 12);
            @endphp

            <div x-data="{ activeTab: '{{ request('tab', $isReguler1112 ? 'capaian' : 'murid') }}' }" class="space-y-4">
                
                <!-- Tabs Switcher -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button @click="activeTab = 'murid'" 
                                type="button"
                                :class="activeTab === 'murid' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition flex items-center gap-1.5">
                            <x-heroicon-o-user-group class="w-4 h-4" /> Daftar Murid
                        </button>
                        <button @click="activeTab = 'capaian'" 
                                type="button"
                                :class="activeTab === 'capaian' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition flex items-center gap-1.5">
                            <x-heroicon-o-chart-bar class="w-4 h-4" /> Capaian Hafalan (Format Excel)
                        </button>
                    </nav>
                </div>

                <!-- Tab 1: Daftar Murid -->
                <div x-show="activeTab === 'murid'" x-cloak class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                    <h3 class="font-semibold text-gray-900 mb-4">
                        Murid di Kelas Ini
                    </h3>

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-gray-500 uppercase">
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Nomor Murid</th>
                                <th class="px-4 py-3">Gender</th>
                                <th class="px-4 py-3">Status</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($students as $student)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ $student->name }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $student->student_number ?: '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $student->gender === 'male' ? 'Laki-laki' : ($student->gender === 'female' ? 'Perempuan' : '-') }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ ucfirst($student->status) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                        Belum ada murid di kelas ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $students->links() }}
                    </div>
                </div>

                <!-- Tab 2: Capaian Hafalan (Format Excel) -->
                <div x-show="activeTab === 'capaian'" x-cloak class="space-y-4">
                    
                    <!-- Filters -->
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <form method="GET" action="{{ route('class-rooms.show', $classRoom) }}" class="flex flex-wrap items-end gap-4">
                            <input type="hidden" name="tab" value="capaian">
                            <div class="w-44">
                                <label for="month" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Bulan</label>
                                <select id="month" name="month" onchange="this.form.submit()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    @foreach ($months as $num => $name)
                                        <option value="{{ $num }}" @selected($month == $num)>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="w-32">
                                <label for="year" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Tahun</label>
                                <select id="year" name="year" onchange="this.form.submit()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    @for ($y = now()->year - 2; $y <= now()->year + 2; $y++)
                                        <option value="{{ $y }}" @selected($year == $y)>{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="w-36">
                                <label for="week" class="block text-xs font-semibold text-gray-500 uppercase mb-1">Pekan</label>
                                <select id="week" name="week" onchange="this.form.submit()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    @for ($w = 1; $w <= 5; $w++)
                                        <option value="{{ $w }}" @selected($week == $w)>Pekan {{ $w }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-750 text-white rounded-lg text-sm font-semibold shadow-sm transition gap-1.5">
                                    <x-heroicon-o-magnifying-glass class="w-4 h-4" /> Filter
                                </button>
                                <a href="{{ route('class-rooms.export-capaian', [$classRoom->id, 'month' => $month, 'year' => $year, 'week' => $week]) }}" 
                                   class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-750 text-white rounded-lg text-sm font-semibold shadow-sm transition gap-1.5">
                                    <x-heroicon-o-arrow-down-tray class="w-4 h-4" /> Ekspor Excel
                                </a>
                                @if (!auth()->user()->hasAnyRole(['student', 'parent']))
                                    <a href="{{ route('class-rooms.print-ummi-cards', $classRoom->id) }}" 
                                       target="_blank"
                                       class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-750 text-white rounded-lg text-sm font-semibold shadow-sm transition gap-1.5">
                                        <x-heroicon-o-printer class="w-4 h-4" /> Cetak Kartu UMMI Kelas
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>

                    <!-- Visual Class Summary Stats Deck -->
                    @php
                        $totalStudentsCount = count($capaianData);
                        $presentStudentsCount = collect($capaianData)->where('kehadiran', 'Hadir')->count();
                        $attendanceRatePercent = $totalStudentsCount > 0 ? round(($presentStudentsCount / $totalStudentsCount) * 100, 1) : 0;
                        
                        $totalClassWeeklyLines = collect($capaianData)->sum('baris');
                        $averageClassLines = $presentStudentsCount > 0 ? round($totalClassWeeklyLines / $presentStudentsCount, 1) : 0;
                    @endphp

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 my-4">
                        <!-- Card 1: Attendance -->
                        <div class="bg-gradient-to-br from-emerald-50 to-white dark:from-zinc-900 dark:to-zinc-800 p-5 rounded-2xl border border-emerald-100 dark:border-zinc-700 shadow-sm relative overflow-hidden group hover:shadow-md transition duration-200">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">Kehadiran Setoran</span>
                                <span class="p-2 bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 rounded-xl">
                                    <x-heroicon-o-chart-bar class="w-6 h-6" />
                                </span>
                            </div>
                            <div class="mt-4 flex items-baseline gap-2">
                                <span class="text-3xl font-extrabold text-gray-900 dark:text-white" x-text="'{{ $attendanceRatePercent }}%'"></span>
                                <span class="text-xs text-gray-500 dark:text-zinc-400" x-text="'({{ $presentStudentsCount }}/{{ $totalStudentsCount }} Murid)'"></span>
                            </div>
                            <!-- Mini Progress Bar -->
                            <div class="w-full bg-gray-200 dark:bg-zinc-750 h-1.5 rounded-full mt-4 overflow-hidden">
                                <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full rounded-full transition-all duration-500" style="width: {{ $attendanceRatePercent }}%"></div>
                            </div>
                        </div>

                        <!-- Card 2: Total Lines -->
                        <div class="bg-gradient-to-br from-indigo-50 to-white dark:from-zinc-900 dark:to-zinc-800 p-5 rounded-2xl border border-indigo-100 dark:border-zinc-700 shadow-sm relative overflow-hidden group hover:shadow-md transition duration-200">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider">Total Capaian Kelas</span>
                                <span class="p-2 bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 rounded-xl">
                                    <x-heroicon-o-book-open class="w-6 h-6" />
                                </span>
                            </div>
                            <div class="mt-4 flex items-baseline gap-2">
                                <span class="text-3xl font-extrabold text-gray-900 dark:text-white" x-text="'{{ $totalClassWeeklyLines }}'"></span>
                                <span class="text-sm font-semibold text-indigo-650 dark:text-indigo-400">Baris</span>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-zinc-400 mt-4 leading-relaxed">Akumulasi seluruh baris setoran reguler pekan ini.</p>
                        </div>

                        <!-- Card 3: Average Lines -->
                        <div class="bg-gradient-to-br from-amber-50 to-white dark:from-zinc-900 dark:to-zinc-800 p-5 rounded-2xl border border-amber-100 dark:border-zinc-700 shadow-sm relative overflow-hidden group hover:shadow-md transition duration-200">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">Rata-rata Setoran</span>
                                <span class="p-2 bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 rounded-xl">
                                    <x-heroicon-o-sparkles class="w-6 h-6" />
                                </span>
                            </div>
                            <div class="mt-4 flex items-baseline gap-2">
                                <span class="text-3xl font-extrabold text-gray-900 dark:text-white" x-text="'{{ $averageClassLines }}'"></span>
                                <span class="text-sm font-semibold text-amber-650 dark:text-amber-400">Baris/Murid</span>
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-zinc-400 mt-4 leading-relaxed font-medium">Kontribusi rata-rata tiap murid yang hadir menyetor.</p>
                        </div>
                    </div>

                    <!-- Excel Table Sheet -->
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 overflow-x-auto">
                        <div class="min-w-[1000px] border border-zinc-300 p-6 bg-white rounded-xl">
                            <!-- Excel Title Header -->
                            <div class="grid grid-cols-2 border border-black text-center font-bold text-sm bg-zinc-50">
                                <div class="py-2 border-r border-black uppercase text-black">
                                    KELAS : {{ $classRoom->name }}
                                </div>
                                <div class="py-2 uppercase text-black">
                                    PEKAN {{ $week }} ({{ $months[$month] }} {{ $year }})
                                </div>
                            </div>

                            <!-- Table -->
                            <table class="w-full border-collapse mt-2 border border-black">
                                <thead>
                                    <tr class="bg-amber-450 bg-amber-400 text-black text-xs font-bold text-center border-b border-black">
                                        <th rowspan="2" class="border border-black py-2.5 px-2 w-[4%]">No</th>
                                        <th rowspan="2" class="border border-black py-2.5 px-3 w-[20%] text-left">Nama Murid</th>
                                        <th rowspan="2" class="border border-black py-2.5 px-2 w-[10%]">Halaqah</th>
                                        <th rowspan="2" class="border border-black py-2.5 px-2 w-[15%]">Musyrif</th>
                                        <th colspan="2" class="border border-black py-1.5 px-2 w-[28%] border-b-2">Setoran</th>
                                        <th rowspan="2" class="border border-black py-2.5 px-2 w-[8%]">Jumlah Baris</th>
                                        <th rowspan="2" class="border border-black py-2.5 px-2 w-[6%]">Nilai</th>
                                        <th rowspan="2" class="border border-black py-2.5 px-2 w-[7%]">Kehadiran</th>
                                    </tr>
                                    <tr class="bg-amber-400 text-black text-[11px] font-bold text-center border-b border-black">
                                        <th class="border border-black py-1.5 px-2">Surah</th>
                                        <th class="border border-black py-1.5 px-2">Ayat</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-black text-xs text-black">
                                    @forelse ($capaianData as $row)
                                        <tr class="hover:bg-zinc-50 transition duration-150">
                                            <td class="border border-black py-2 px-2 text-center font-semibold">{{ $row['no'] }}</td>
                                            <td class="border border-black py-2 px-3 font-medium">{{ $row['student']->name }}</td>
                                            <td class="border border-black py-2 px-2 text-center">{{ $row['halaqah'] }}</td>
                                            <td class="border border-black py-2 px-2 text-center">{{ $row['musyrif'] }}</td>
                                            <td class="border border-black py-2 px-2 font-medium">{{ $row['surah'] }}</td>
                                            <td class="border border-black py-2 px-2 text-center font-medium">{{ $row['ayat'] }}</td>
                                            <td class="border border-black py-2 px-2 text-center font-bold text-zinc-700">{{ $row['baris'] }}</td>
                                            <td class="border border-black py-2 px-2 text-center font-bold text-indigo-700">{{ $row['nilai'] }}</td>
                                            <td class="border border-black py-2 px-2 text-center font-bold">
                                                @if ($row['kehadiran'] === 'Hadir')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-green-150 bg-green-100 text-green-800 border border-green-300">
                                                        Hadir
                                                    </span>
                                                @else
                                                    <span class="text-gray-400">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="border border-black py-6 text-center text-gray-500">
                                                Tidak ada data capaian hafalan untuk pekan ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</x-app-layout>