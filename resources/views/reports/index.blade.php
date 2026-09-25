<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Laporan TAD
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Ringkasan hafalan, murajaah, target, dan progres murid.
                </p>
            </div>

            @if (auth()->user()->hasAnyRole(['super_admin', 'admin', 'teacher', 'coordinator_tahfizh', 'tanse']))
                <a href="{{ route('reports.export.csv', request()->query()) }}"
                   class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                    Export CSV
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-4 sm:py-6 lg:py-8">
        <div class="mx-auto max-w-7xl space-y-4 sm:space-y-6 px-3 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 dark:border-emerald-800/50 bg-emerald-50 dark:bg-emerald-950/40 px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm font-medium text-emerald-800 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            @if(!auth()->user()->hasAnyRole(['parent', 'student']))
                <div class="rounded-xl sm:rounded-2xl border border-zinc-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3.5 sm:p-5 shadow-xs">
                    <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 gap-3 sm:gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label for="student_id" class="mb-1 block text-sm font-semibold text-gray-700">
                                Murid
                            </label>
                            <select id="student_id" name="student_id" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-gray-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Semua Murid</option>
                                @foreach ($students as $student)
                                    <option value="{{ $student->id }}" @selected((string) request('student_id') === (string) $student->id)>
                                        {{ $student->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="class_room_id" class="mb-1 block text-sm font-semibold text-gray-700">
                                Kelas
                            </label>
                            <select id="class_room_id" name="class_room_id" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-gray-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Semua Kelas</option>
                                @foreach ($classRooms as $classRoom)
                                    <option value="{{ $classRoom->id }}" @selected((string) request('class_room_id') === (string) $classRoom->id)>
                                        {{ $classRoom->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="teacher_id" class="mb-1 block text-sm font-semibold text-gray-700">
                                Guru
                            </label>
                            <select id="teacher_id" name="teacher_id" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-gray-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Semua Guru</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" @selected((string) request('teacher_id') === (string) $teacher->id)>
                                        {{ $teacher->user?->name ?? 'Guru #' . $teacher->id }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="surah_id" class="mb-1 block text-sm font-semibold text-gray-700">
                                Surah
                            </label>
                            <select id="surah_id" name="surah_id" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-gray-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Semua Surah</option>
                                @foreach ($surahs as $surah)
                                    <option value="{{ $surah->id }}" @selected((string) request('surah_id') === (string) $surah->id)>
                                        {{ $surah->number }}. {{ $surah->name_latin ?? $surah->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="status" class="mb-1 block text-sm font-semibold text-gray-700">
                                Status Setoran
                            </label>
                            <select id="status" name="status" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-gray-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Semua Status</option>
                                <option value="passed" @selected(request('status') === 'passed')>Lulus</option>
                                <option value="good" @selected(request('status') === 'good')>Baik</option>
                                <option value="repeat" @selected(request('status') === 'repeat')>Ulang</option>
                                <option value="needs_improvement" @selected(request('status') === 'needs_improvement')>Perlu Perbaikan</option>
                            </select>
                        </div>

                        <div>
                            <label for="from" class="mb-1 block text-sm font-semibold text-gray-700">
                                Dari Tanggal
                            </label>
                            <input id="from" type="date" name="from" value="{{ request('from') }}"
                                   onchange="this.form.submit()"
                                   class="w-full rounded-lg border-gray-300 text-gray-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>

                        <div>
                            <label for="to" class="mb-1 block text-sm font-semibold text-gray-700">
                                Sampai Tanggal
                            </label>
                            <input id="to" type="date" name="to" value="{{ request('to') }}"
                                   onchange="this.form.submit()"
                                   class="w-full rounded-lg border-gray-300 text-gray-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        </div>

                        <div class="flex items-end gap-2">
                            <button type="submit"
                                    class="inline-flex w-full items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800">
                                Terapkan
                            </button>

                            <a href="{{ route('reports.index') }}"
                               class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            @endif

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
                <div class="rounded-xl sm:rounded-2xl border border-zinc-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3 sm:p-4 shadow-xs flex flex-col justify-between transition-all hover:border-zinc-300 dark:hover:border-zinc-700">
                    <div class="flex items-center justify-between gap-1.5">
                        <p class="text-[11px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400 truncate">Total Hafalan</p>
                        <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                            <x-heroicon-o-book-open class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                        </div>
                    </div>
                    <p class="mt-1 sm:mt-2 text-xl sm:text-2xl lg:text-3xl font-black tracking-tight text-zinc-900 dark:text-white">{{ number_format($summary['total_hafalan'] ?? 0) }}</p>
                </div>

                <div class="rounded-xl sm:rounded-2xl border border-zinc-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3 sm:p-4 shadow-xs flex flex-col justify-between transition-all hover:border-zinc-300 dark:hover:border-zinc-700">
                    <div class="flex items-center justify-between gap-1.5">
                        <p class="text-[11px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400 truncate">Total Murajaah</p>
                        <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
                            <x-heroicon-o-arrow-path class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                        </div>
                    </div>
                    <p class="mt-1 sm:mt-2 text-xl sm:text-2xl lg:text-3xl font-black tracking-tight text-zinc-900 dark:text-white">{{ number_format($summary['total_murajaah'] ?? 0) }}</p>
                </div>

                <div class="rounded-xl sm:rounded-2xl border border-zinc-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3 sm:p-4 shadow-xs flex flex-col justify-between transition-all hover:border-zinc-300 dark:hover:border-zinc-700">
                    <div class="flex items-center justify-between gap-1.5">
                        <p class="text-[11px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400 truncate">Target Aktif</p>
                        <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-amber-500/10 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
                            <x-heroicon-o-check-circle class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                        </div>
                    </div>
                    <p class="mt-1 sm:mt-2 text-xl sm:text-2xl lg:text-3xl font-black tracking-tight text-zinc-900 dark:text-white">{{ number_format($summary['active_targets'] ?? 0) }}</p>
                </div>

                <div class="rounded-xl sm:rounded-2xl border border-zinc-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3 sm:p-4 shadow-xs flex flex-col justify-between transition-all hover:border-zinc-300 dark:hover:border-zinc-700">
                    <div class="flex items-center justify-between gap-1.5">
                        <p class="text-[11px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400 truncate">Rata-rata Nilai</p>
                        <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-purple-500/10 flex items-center justify-center text-purple-600 dark:text-purple-400 shrink-0">
                            <x-heroicon-o-chart-bar class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                        </div>
                    </div>
                    <p class="mt-1 sm:mt-2 text-xl sm:text-2xl lg:text-3xl font-black tracking-tight text-zinc-900 dark:text-white">
                        {{ number_format((float) (($summary['average_hafalan_score'] ?? 0) + ($summary['average_murajaah_score'] ?? 0)) / 2, 2) }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
                <div class="rounded-xl sm:rounded-2xl border border-zinc-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3 sm:p-4 shadow-xs flex flex-col justify-between transition-all">
                    <p class="text-[11px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400 truncate">Hafalan Lulus</p>
                    <p class="mt-1 sm:mt-1.5 text-lg sm:text-xl lg:text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ number_format($summary['passed_hafalan'] ?? 0) }}</p>
                </div>

                <div class="rounded-xl sm:rounded-2xl border border-zinc-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3 sm:p-4 shadow-xs flex flex-col justify-between transition-all">
                    <p class="text-[11px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400 truncate">Hafalan Perlu Perhatian</p>
                    <p class="mt-1 sm:mt-1.5 text-lg sm:text-xl lg:text-2xl font-black text-amber-600 dark:text-amber-400">{{ number_format($summary['repeat_hafalan'] ?? 0) }}</p>
                </div>

                <div class="rounded-xl sm:rounded-2xl border border-zinc-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3 sm:p-4 shadow-xs flex flex-col justify-between transition-all">
                    <p class="text-[11px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400 truncate">Murajaah Lulus/Baik</p>
                    <p class="mt-1 sm:mt-1.5 text-lg sm:text-xl lg:text-2xl font-black text-teal-600 dark:text-teal-400">{{ number_format($summary['passed_murajaah'] ?? 0) }}</p>
                </div>

                <div class="rounded-xl sm:rounded-2xl border border-zinc-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3 sm:p-4 shadow-xs flex flex-col justify-between transition-all">
                    <p class="text-[11px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400 truncate">Murajaah Perlu Perhatian</p>
                    <p class="mt-1 sm:mt-1.5 text-lg sm:text-xl lg:text-2xl font-black text-amber-600 dark:text-amber-400">{{ number_format($summary['repeat_murajaah'] ?? 0) }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Data Hafalan</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Tanggal</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Murid</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Kelas</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Surah</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Ayat</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Nilai</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($hafalanRecords as $record)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ filled($record->submitted_at) ? \Illuminate\Support\Carbon::parse($record->submitted_at)->format('d M Y') : '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-gray-900">
                                        @if ($record->student)
                                            <a href="{{ route('reports.student', $record->student) }}" class="text-emerald-700 hover:text-emerald-900">
                                                {{ $record->student->name }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $record->student?->classRoom?->name ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $record->surah?->name_latin ?? $record->surah?->name ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-gray-900">
                                        {{ $record->score ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ \Illuminate\Support\Str::headline($record->status ?? '-') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center text-sm text-gray-500">
                                        Belum ada data hafalan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-200 px-5 py-4">
                    {{ $hafalanRecords->links() }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Data Murajaah</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Tanggal</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Murid</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Kelas</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Surah</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Ayat</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Nilai</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($murajaahRecords as $record)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ filled($record->reviewed_at) ? \Illuminate\Support\Carbon::parse($record->reviewed_at)->format('d M Y') : '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-gray-900">
                                        @if ($record->student)
                                            <a href="{{ route('reports.student', $record->student) }}" class="text-emerald-700 hover:text-emerald-900">
                                                {{ $record->student->name }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $record->student?->classRoom?->name ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $record->surah?->name_latin ?? $record->surah?->name ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-gray-900">
                                        {{ $record->overall_score ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ \Illuminate\Support\Str::headline($record->status ?? '-') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center text-sm text-gray-500">
                                        Belum ada data murajaah.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-200 px-5 py-4">
                    {{ $murajaahRecords->links() }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Target Hafalan</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Target</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Murid</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Surah</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Ayat</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Guru</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($hafalanTargets as $target)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ filled($target->target_date) ? \Illuminate\Support\Carbon::parse($target->target_date)->format('d M Y') : '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-gray-900">
                                        @if ($target->student)
                                            <a href="{{ route('reports.student', $target->student) }}" class="text-emerald-700 hover:text-emerald-900">
                                                {{ $target->student->name }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $target->surah?->name_latin ?? $target->surah?->name ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $target->ayah_range }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ \Illuminate\Support\Str::headline($target->status ?? '-') }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $target->teacher?->user?->name ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-sm text-gray-500">
                                        Belum ada target hafalan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-200 px-5 py-4">
                    {{ $hafalanTargets->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>