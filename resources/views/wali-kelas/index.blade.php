<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Pemantauan Wali Kelas
            </h2>
            <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                Ketuntasan hafalan, kuisioner adab, dan kedisiplinan murid kelas yang Anda ampu.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (! $classRoom)
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm rounded-xl p-10 text-center text-gray-500 dark:text-zinc-500">
                    Akun ini belum ditugaskan sebagai wali kelas manapun. Hubungi admin untuk mengatur penugasan di halaman Kelas.
                </div>
            @else
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm rounded-xl p-5">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $classRoom->name }}</h3>
                    <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">
                        {{ $classRoom->program?->name ?? '-' }} &middot; {{ $students->count() }} murid aktif
                    </p>

                    <div class="flex flex-wrap gap-2 mt-4">
                        <a href="{{ route('reports.periodic', ['class_room_id' => $classRoom->id]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-900/40 hover:bg-indigo-100 dark:hover:bg-indigo-950/50 transition">
                            Grafik Tahfizh Kelas
                        </a>
                        <a href="{{ route('adab.chart') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/40 hover:bg-emerald-100 dark:hover:bg-emerald-950/50 transition">
                            Grafik Adab Kelas
                        </a>
                        <a href="{{ route('student-points.chart', ['class_room_id' => $classRoom->id]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-50 dark:bg-amber-950/30 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-900/40 hover:bg-amber-100 dark:hover:bg-amber-950/50 transition">
                            Grafik Kedisiplinan Kelas
                        </a>
                        <a href="{{ route('student-points.index', ['class_room_id' => $classRoom->id]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition">
                            Riwayat Kedisiplinan Kelas
                        </a>
                    </div>
                </div>

                <!-- Daftar Murid -->
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm rounded-xl p-5 space-y-3">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Daftar Murid</h3>
                        <p class="text-xs text-gray-500 dark:text-zinc-400">Lihat progres tahfizh (grafik &amp; riwayat setoran) dan riwayat adab per murid.</p>
                    </div>

                    <div class="overflow-x-auto border dark:border-zinc-800 rounded-xl">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-xs">
                            <thead class="bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 font-bold">
                                <tr>
                                    <th class="px-4 py-2 text-left border-r dark:border-zinc-700">Nama Murid</th>
                                    <th class="px-4 py-2 text-right">Detail</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-zinc-800">
                                @foreach ($students as $student)
                                    <tr>
                                        <td class="px-4 py-2 border-r dark:border-zinc-700 font-semibold text-gray-900 dark:text-zinc-200">{{ $student->name }}</td>
                                        <td class="px-4 py-2 text-right whitespace-nowrap">
                                            <a href="{{ route('progress.show', $student) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline font-semibold">Progres Tahfizh</a>
                                            <span class="text-gray-300 dark:text-zinc-700 mx-1.5">&middot;</span>
                                            <a href="{{ route('adab.show', $student) }}" class="text-emerald-600 dark:text-emerald-400 hover:underline font-semibold">Riwayat Adab</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Ketuntasan Hafalan -->
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm rounded-xl p-5 space-y-4">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Belum Tuntas Hafalan</h3>
                        <p class="text-xs text-gray-500 dark:text-zinc-400">
                            Murid yang capaian barisnya belum memenuhi target (jumlah pertemuan &times; level), dan posisi capaiannya belum sampai target.
                        </p>
                    </div>

                    @foreach ($monthlyTuntas as $month)
                        <div>
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-600 dark:text-zinc-300 border-l-4 border-indigo-500 pl-2 mb-2">
                                {{ $month['label'] }}
                            </h4>
                            @if ($month['rows']->isEmpty())
                                <p class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold pl-2">Semua murid sudah tuntas bulan ini. 🎉</p>
                            @else
                                <div class="overflow-x-auto border dark:border-zinc-800 rounded-xl">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-xs">
                                        <thead class="bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 font-bold">
                                            <tr>
                                                <th class="px-4 py-2 text-left border-r dark:border-zinc-700">Nama Murid</th>
                                                <th class="px-4 py-2 text-center border-r dark:border-zinc-700">Capaian Baris</th>
                                                <th class="px-4 py-2 text-center">Target Baris</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-zinc-800">
                                            @foreach ($month['rows'] as $row)
                                                <tr>
                                                    <td class="px-4 py-2 border-r dark:border-zinc-700 font-semibold text-gray-900 dark:text-zinc-200">{{ $row['student']->name }}</td>
                                                    <td class="px-4 py-2 text-center border-r dark:border-zinc-700 text-rose-600 dark:text-rose-400 font-bold">{{ $row['capaian_baris'] }}</td>
                                                    <td class="px-4 py-2 text-center text-gray-600 dark:text-zinc-400">{{ $row['target_baris'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <div>
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-600 dark:text-zinc-300 border-l-4 border-amber-500 pl-2 mb-2">
                            Triwulan ({{ $termTuntas['label'] }})
                        </h4>
                        @if ($termTuntas['rows']->isEmpty())
                            <p class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold pl-2">Semua murid sudah tuntas triwulan ini. 🎉</p>
                        @else
                            <div class="overflow-x-auto border dark:border-zinc-800 rounded-xl">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-xs">
                                    <thead class="bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 font-bold">
                                        <tr>
                                            <th class="px-4 py-2 text-left border-r dark:border-zinc-700">Nama Murid</th>
                                            <th class="px-4 py-2 text-center border-r dark:border-zinc-700">Capaian Baris</th>
                                            <th class="px-4 py-2 text-center">Target Baris</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-zinc-800">
                                        @foreach ($termTuntas['rows'] as $row)
                                            <tr>
                                                <td class="px-4 py-2 border-r dark:border-zinc-700 font-semibold text-gray-900 dark:text-zinc-200">{{ $row['student']->name }}</td>
                                                <td class="px-4 py-2 text-center border-r dark:border-zinc-700 text-rose-600 dark:text-rose-400 font-bold">{{ $row['capaian_baris'] }}</td>
                                                <td class="px-4 py-2 text-center text-gray-600 dark:text-zinc-400">{{ $row['target_baris'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Kuisioner Adab -->
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm rounded-xl p-5 space-y-3">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Kuisioner Adab Hari Ini</h3>
                            <p class="text-xs text-gray-500 dark:text-zinc-400">{{ now()->translatedFormat('l, d F Y') }}</p>
                        </div>
                        <span class="px-3 py-1 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 rounded-full text-xs font-bold border border-indigo-500/20">
                            Terisi {{ $adabToday['submitted_count'] }} / {{ $adabToday['total_count'] }} murid
                        </span>
                    </div>

                    @if (! $adabToday['is_effective_day'])
                        <p class="text-xs text-gray-500 dark:text-zinc-400 italic">Hari ini bukan hari efektif pengisian kuisioner adab ({{ collect(app(\App\Services\SchoolCalendar::class)->adabDays())->map(fn ($d) => \App\Services\SchoolCalendar::DAY_NAMES[$d])->implode(', ') }}, di luar libur Adab).</p>
                    @elseif ($adabToday['missing']->isEmpty())
                        <p class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold">Semua murid sudah mengisi kuisioner adab hari ini. 🎉</p>
                    @else
                        <p class="text-xs text-gray-500 dark:text-zinc-400">Murid yang belum mengisi:</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($adabToday['missing'] as $student)
                                <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-900/30">
                                    {{ $student->name }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Kedisiplinan -->
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm rounded-xl p-5 space-y-3">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Kedisiplinan</h3>
                        <p class="text-xs text-gray-500 dark:text-zinc-400">Rekap poin pelanggaran bulan {{ $discipline['label'] }}.</p>
                    </div>

                    <div class="overflow-x-auto border dark:border-zinc-800 rounded-xl">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-xs">
                            <thead class="bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 font-bold">
                                <tr>
                                    <th class="px-4 py-2 text-left border-r dark:border-zinc-700">Nama Murid</th>
                                    <th class="px-4 py-2 text-center border-r dark:border-zinc-700">Jumlah Pelanggaran</th>
                                    <th class="px-4 py-2 text-center border-r dark:border-zinc-700">Poin</th>
                                    <th class="px-4 py-2 text-center border-r dark:border-zinc-700">Grade</th>
                                    <th class="px-4 py-2 text-left">Catatan Terbaru</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-zinc-800">
                                @foreach ($discipline['rows'] as $row)
                                    <tr>
                                        <td class="px-4 py-2 border-r dark:border-zinc-700 font-semibold text-gray-900 dark:text-zinc-200">{{ $row['student']->name }}</td>
                                        <td class="px-4 py-2 text-center border-r dark:border-zinc-700 {{ $row['count'] > 0 ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-gray-300' }}">{{ $row['count'] ?: '-' }}</td>
                                        <td class="px-4 py-2 text-center border-r dark:border-zinc-700 {{ $row['total_points'] > 0 ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-gray-300' }}">{{ $row['total_points'] ?: '-' }}</td>
                                        <td class="px-4 py-2 text-center border-r dark:border-zinc-700 font-bold {{ $row['grade'] === 'A' ? 'text-emerald-600' : ($row['grade'] === 'E' ? 'text-rose-600' : 'text-amber-600') }}">{{ $row['grade'] }}</td>
                                        <td class="px-4 py-2 text-gray-600 dark:text-zinc-400">
                                            @if ($row['items']->isEmpty())
                                                -
                                            @else
                                                {{ $row['items']->map(fn ($v) => ($v->title ?: ucfirst($v->type)).' ('.\Carbon\Carbon::parse($v->date)->format('d/m').')')->implode(', ') }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
