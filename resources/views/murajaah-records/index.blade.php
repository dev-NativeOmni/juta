<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <h2 class="font-bold text-lg sm:text-xl text-zinc-900 dark:text-white leading-tight">
                Data Murajaah
            </h2>

            <div class="flex items-center gap-2">
                <a href="{{ route('murajaah-records.fast-input') }}"
                   class="inline-flex items-center justify-center gap-1.5 px-4 py-2.5 sm:py-2 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl font-bold text-xs shadow-md transition duration-150 shrink-0 min-h-[38px]">
                    <x-heroicon-o-bolt class="w-4 h-4" />
                    <span>Input Murajaah Cepat</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-3 sm:py-6">
        <div class="max-w-7xl mx-auto space-y-3 sm:space-y-4">
            @include('partials.tahfizh-records-nav-tabs', ['activeTab' => 'murajaah'])

            @if (session('success'))
                <div class="bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 px-4 py-3 rounded-xl text-sm font-semibold">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 px-4 py-3 rounded-xl text-sm font-semibold">
                    {{ session('error') }}
                </div>
            @endif

            @if (!auth()->user()->hasAnyRole(['student', 'parent']))
                <!-- Filter Section -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-3.5 sm:p-5">
                    <form method="GET" action="{{ route('murajaah-records.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-2.5 sm:gap-3">
                        <div class="sm:col-span-2 lg:col-span-2">
                            <label class="block text-[10px] sm:text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-1">Kelas</label>
                            <x-filter-toggle
                                name="class_room_id"
                                :options="['' => 'Semua Kelas'] + $classRooms->pluck('name', 'id')->all()"
                            />
                        </div>

                        <div>
                            <label class="block text-[10px] sm:text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-1">Cari</label>
                            <input type="text"
                                   name="search"
                                   value="{{ request('search') }}"
                                   placeholder="Nama murid / surah..."
                                   x-on:input.debounce.600ms="$el.form.submit()"
                                   class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-xs sm:text-sm text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 shadow-sm">
                        </div>

                        <div>
                            <label class="block text-[10px] sm:text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-1">Surah</label>
                            <select name="surah_id" onchange="this.form.submit()" class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-xs sm:text-sm text-zinc-900 dark:text-zinc-100 shadow-sm">
                                <option value="">Semua Surah</option>
                                @foreach ($surahs as $surah)
                                    <option value="{{ $surah->id }}" @selected(request('surah_id') == $surah->id)>
                                        {{ $surah->number }}. {{ $surah->name_latin }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sm:col-span-2 lg:col-span-2">
                            <label class="block text-[10px] sm:text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-1">Status</label>
                            <x-filter-toggle
                                name="status"
                                :options="['' => 'Semua Status', 'passed' => 'Lulus', 'repeat' => 'Ulang', 'needs_improvement' => 'Perlu Perbaikan']"
                                :colors="['passed' => 'bg-emerald-600 text-white shadow-sm', 'repeat' => 'bg-rose-600 text-white shadow-sm', 'needs_improvement' => 'bg-amber-600 text-white shadow-sm']"
                            />
                        </div>

                        <div class="flex items-end gap-2 col-span-1 sm:col-span-2 lg:col-span-1">
                            <button type="submit"
                                    class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:scale-95 rounded-lg text-xs font-bold text-white uppercase tracking-wider transition min-h-[38px]">
                                Filter
                            </button>

                            <a href="{{ route('murajaah-records.index') }}"
                               class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 active:scale-95 rounded-lg text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider transition min-h-[38px]">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            @endif

            <!-- Mobile View: Card Stack (< md) -->
            <div class="block md:hidden space-y-3">
                @forelse ($murajaahRecords as $record)
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-4 space-y-3">
                        <div class="flex items-start justify-between gap-2 border-b border-zinc-100 dark:border-zinc-800 pb-2.5">
                            <div>
                                <h3 class="font-bold text-sm text-zinc-900 dark:text-white leading-tight">
                                    {{ $record->student?->name }}
                                </h3>
                                <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    <span>NIS: {{ $record->student?->student_number ?? '-' }}</span>
                                    <span>•</span>
                                    <span>{{ $record->reviewed_at?->format('d M Y') }}</span>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold shrink-0 bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                {{ $record->status_label }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-zinc-400 dark:text-zinc-500 block text-[10px] uppercase font-semibold">Surah & Ayat</span>
                                <span class="font-bold text-zinc-800 dark:text-zinc-200">
                                    {{ $record->surah?->number }}. {{ $record->surah?->name_latin }} ({{ $record->ayah_range }})
                                </span>
                            </div>
                            <div>
                                <span class="text-zinc-400 dark:text-zinc-500 block text-[10px] uppercase font-semibold">Nilai & Guru</span>
                                <span class="font-semibold text-zinc-700 dark:text-zinc-300">
                                    Nilai: <strong class="text-indigo-600 dark:text-indigo-400">{{ $record->overall_score ?? '-' }}</strong>
                                </span>
                            </div>
                        </div>

                        <!-- Action Bar Mobile -->
                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                            @can('view', $record)
                                <a href="{{ route('murajaah-records.show', $record) }}"
                                   class="p-2 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                            @endcan

                            @can('update', $record)
                                <a href="{{ route('murajaah-records.edit', $record) }}"
                                   class="p-2 rounded-lg bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </a>
                            @endcan

                            @can('delete', $record)
                                <form action="{{ route('murajaah-records.destroy', $record) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan muraja\'ah ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="p-2 rounded-lg bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 hover:bg-rose-100 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    </div>
                @empty
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-8 text-center text-zinc-500">
                        Belum ada catatan muraja'ah.
                    </div>
                @endforelse

                <div class="mt-4">
                    {{ $murajaahRecords->links() }}
                </div>
            </div>

            <!-- Desktop View: Table (>= md) -->
            <div class="hidden md:block bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 overflow-hidden shadow-sm rounded-xl">
                <div class="p-4 sm:p-6 overflow-x-auto touch-scroll">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Murid</th>
                                <th class="px-4 py-3">Surah</th>
                                <th class="px-4 py-3">Ayat</th>
                                <th class="px-4 py-3">Nilai</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Guru</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                            @forelse ($murajaahRecords as $record)
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40 transition duration-150">
                                    <td class="px-4 py-3.5 text-xs text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                                        {{ $record->reviewed_at?->format('d M Y') }}
                                    </td>

                                    <td class="px-4 py-3.5">
                                        <div class="font-bold text-xs text-zinc-900 dark:text-white">
                                            {{ $record->student?->name }}
                                        </div>
                                        <div class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                            {{ $record->student?->student_number ?? '-' }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3.5 text-xs text-zinc-700 dark:text-zinc-300">
                                        {{ $record->surah?->number }}. {{ $record->surah?->name_latin }}
                                    </td>

                                    <td class="px-4 py-3.5 text-xs text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                                        {{ $record->ayah_range }}
                                    </td>

                                    <td class="px-4 py-3.5 font-bold text-xs text-zinc-800 dark:text-zinc-200">
                                        {{ $record->overall_score ?? '-' }}
                                    </td>

                                    <td class="px-4 py-3.5">
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap inline-block bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                            {{ $record->status_label }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3.5 text-xs text-zinc-700 dark:text-zinc-300">
                                        {{ $record->teacher?->user?->name ?? '-' }}
                                    </td>

                                    <td class="px-4 py-3.5">
                                         <div class="flex justify-end items-center gap-2">
                                             <a href="{{ route('murajaah-records.show', $record) }}"
                                                class="btn-action-detail">
                                                 Detail
                                             </a>

                                             <a href="{{ route('murajaah-records.edit', $record) }}"
                                                class="btn-action-edit">
                                                 Edit
                                             </a>

                                             <form action="{{ route('murajaah-records.destroy', $record) }}"
                                                   method="POST"
                                                   onsubmit="return confirm('Yakin ingin menghapus data murajaah ini?')">
                                                 @csrf
                                                 @method('DELETE')

                                                 <button type="submit" class="btn-action-delete">
                                                     Hapus
                                                 </button>
                                             </form>
                                         </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-6 text-center text-xs text-zinc-500">
                                        Belum ada data murajaah.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $murajaahRecords->links() }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>