<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Laporan Murid: {{ $student->name }}
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $student->classRoom?->name ?? 'Tanpa kelas' }}
                    @if ($student->classRoom?->program)
                        · {{ $student->classRoom->program->name }}
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @if (auth()->user()->hasAnyRole(['super_admin', 'admin', 'teacher']) || (auth()->user()->hasRole('parent') && auth()->user()->parentProfile?->students()->count() > 1))
                    <a href="{{ route('reports.index') }}"
                       class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                        Kembali
                    </a>
                @endif

                @if (auth()->user()->hasAnyRole(['super_admin', 'admin', 'teacher', 'coordinator_tahfizh', 'tanse']))
                    <a href="{{ route('reports.student.export.csv', $student) }}"
                       class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                        Export CSV
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6 lg:py-8">
        <div class="mx-auto max-w-7xl space-y-4 sm:space-y-6 px-3 sm:px-6 lg:px-8">

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
                        <p class="text-[11px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400 truncate">Target Selesai</p>
                        <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
                            <x-heroicon-o-trophy class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                        </div>
                    </div>
                    <p class="mt-1 sm:mt-2 text-xl sm:text-2xl lg:text-3xl font-black tracking-tight text-zinc-900 dark:text-white">{{ number_format($summary['completed_targets'] ?? 0) }}</p>
                </div>
            </div>

            <div class="rounded-xl sm:rounded-2xl border border-zinc-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-3.5 sm:p-5 shadow-xs">
                <h3 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-white">Profil Murid</h3>

                <dl class="mt-3 grid grid-cols-2 gap-2.5 sm:gap-4 lg:grid-cols-4">
                    <div class="bg-zinc-50/50 dark:bg-zinc-800/40 rounded-xl p-2.5 sm:p-3 border border-zinc-100 dark:border-zinc-800">
                        <dt class="text-[10px] sm:text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Nomor Induk</dt>
                        <dd class="mt-0.5 text-xs sm:text-sm font-bold text-zinc-900 dark:text-white truncate">{{ $student->student_number ?? '-' }}</dd>
                    </div>

                    <div class="bg-zinc-50/50 dark:bg-zinc-800/40 rounded-xl p-2.5 sm:p-3 border border-zinc-100 dark:border-zinc-800">
                        <dt class="text-[10px] sm:text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Kelas</dt>
                        <dd class="mt-0.5 text-xs sm:text-sm font-bold text-zinc-900 dark:text-white truncate">{{ $student->classRoom?->name ?? '-' }}</dd>
                    </div>

                    <div class="bg-zinc-50/50 dark:bg-zinc-800/40 rounded-xl p-2.5 sm:p-3 border border-zinc-100 dark:border-zinc-800">
                        <dt class="text-[10px] sm:text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Program</dt>
                        <dd class="mt-0.5 text-xs sm:text-sm font-bold text-zinc-900 dark:text-white truncate">{{ $student->classRoom?->program?->name ?? '-' }}</dd>
                    </div>

                    <div class="bg-zinc-50/50 dark:bg-zinc-800/40 rounded-xl p-2.5 sm:p-3 border border-zinc-100 dark:border-zinc-800">
                        <dt class="text-[10px] sm:text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Guru</dt>
                        <dd class="mt-0.5 text-xs sm:text-sm font-bold text-zinc-900 dark:text-white truncate">{{ $student->teacher?->user?->name ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl sm:rounded-2xl border border-zinc-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs overflow-hidden">
                <div class="border-b border-zinc-100 dark:border-zinc-800 px-4 py-3 sm:px-5 sm:py-3.5 bg-zinc-50/50 dark:bg-zinc-800/30">
                    <h3 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-white">Riwayat Hafalan</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                        <thead class="bg-zinc-50/80 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-3.5 py-2.5 sm:px-4 sm:py-3 text-left text-[10px] sm:text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Tanggal</th>
                                <th class="px-3.5 py-2.5 sm:px-4 sm:py-3 text-left text-[10px] sm:text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Surah</th>
                                <th class="px-3.5 py-2.5 sm:px-4 sm:py-3 text-left text-[10px] sm:text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Ayat</th>
                                <th class="px-3.5 py-2.5 sm:px-4 sm:py-3 text-left text-[10px] sm:text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Nilai</th>
                                <th class="px-3.5 py-2.5 sm:px-4 sm:py-3 text-left text-[10px] sm:text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                                <th class="px-3.5 py-2.5 sm:px-4 sm:py-3 text-left text-[10px] sm:text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60 bg-white dark:bg-zinc-900">
                            @forelse ($hafalanRecords as $record)
                                <tr>
                                    <td class="whitespace-nowrap px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ filled($record->submitted_at) ? \Illuminate\Support\Carbon::parse($record->submitted_at)->format('d M Y') : '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm font-semibold text-zinc-900 dark:text-white">
                                        {{ $record->surah?->name_latin ?? $record->surah?->name ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                    </td>
                                    <td class="whitespace-nowrap px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm font-bold text-zinc-900 dark:text-white">
                                        {{ $record->score ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ \Illuminate\Support\Str::headline($record->status ?? '-') }}
                                    </td>
                                    <td class="px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ $record->notes ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-xs sm:text-sm text-zinc-400">
                                        Belum ada riwayat hafalan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl sm:rounded-2xl border border-zinc-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs overflow-hidden">
                <div class="border-b border-zinc-100 dark:border-zinc-800 px-4 py-3 sm:px-5 sm:py-3.5 bg-zinc-50/50 dark:bg-zinc-800/30">
                    <h3 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-white">Riwayat Murajaah</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                        <thead class="bg-zinc-50/80 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-3.5 py-2.5 sm:px-4 sm:py-3 text-left text-[10px] sm:text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Tanggal</th>
                                <th class="px-3.5 py-2.5 sm:px-4 sm:py-3 text-left text-[10px] sm:text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Surah</th>
                                <th class="px-3.5 py-2.5 sm:px-4 sm:py-3 text-left text-[10px] sm:text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Ayat</th>
                                <th class="px-3.5 py-2.5 sm:px-4 sm:py-3 text-left text-[10px] sm:text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Nilai</th>
                                <th class="px-3.5 py-2.5 sm:px-4 sm:py-3 text-left text-[10px] sm:text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                                <th class="px-3.5 py-2.5 sm:px-4 sm:py-3 text-left text-[10px] sm:text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60 bg-white dark:bg-zinc-900">
                            @forelse ($murajaahRecords as $record)
                                <tr>
                                    <td class="whitespace-nowrap px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ filled($record->reviewed_at) ? \Illuminate\Support\Carbon::parse($record->reviewed_at)->format('d M Y') : '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm font-semibold text-zinc-900 dark:text-white">
                                        {{ $record->surah?->name_latin ?? $record->surah?->name ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                    </td>
                                    <td class="whitespace-nowrap px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm font-bold text-zinc-900 dark:text-white">
                                        {{ $record->overall_score ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ \Illuminate\Support\Str::headline($record->status ?? '-') }}
                                    </td>
                                    <td class="px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ $record->notes ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-xs sm:text-sm text-zinc-400">
                                        Belum ada riwayat murajaah.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl sm:rounded-2xl border border-zinc-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900 shadow-xs overflow-hidden">
                <div class="border-b border-zinc-100 dark:border-zinc-800 px-4 py-3 sm:px-5 sm:py-3.5 bg-zinc-50/50 dark:bg-zinc-800/30">
                    <h3 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-white">Target Hafalan</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                        <thead class="bg-zinc-50/80 dark:bg-zinc-800/50">
                            <tr>
                                <th class="px-3.5 py-2.5 sm:px-4 sm:py-3 text-left text-[10px] sm:text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Tanggal Target</th>
                                <th class="px-3.5 py-2.5 sm:px-4 sm:py-3 text-left text-[10px] sm:text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Surah</th>
                                <th class="px-3.5 py-2.5 sm:px-4 sm:py-3 text-left text-[10px] sm:text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Ayat</th>
                                <th class="px-3.5 py-2.5 sm:px-4 sm:py-3 text-left text-[10px] sm:text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</th>
                                <th class="px-3.5 py-2.5 sm:px-4 sm:py-3 text-left text-[10px] sm:text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60 bg-white dark:bg-zinc-900">
                            @forelse ($hafalanTargets as $target)
                                <tr>
                                    <td class="whitespace-nowrap px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ filled($target->target_date) ? \Illuminate\Support\Carbon::parse($target->target_date)->format('d M Y') : '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm font-semibold text-zinc-900 dark:text-white">
                                        {{ $target->surah?->name_latin ?? $target->surah?->name ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ $target->ayah_range }}
                                    </td>
                                    <td class="whitespace-nowrap px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ \Illuminate\Support\Str::headline($target->status ?? '-') }}
                                    </td>
                                    <td class="px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm text-zinc-700 dark:text-zinc-300">
                                        {{ $target->notes ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-xs sm:text-sm text-zinc-400">
                                        Belum ada target hafalan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>