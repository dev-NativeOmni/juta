<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight flex items-center gap-2">
                <x-heroicon-o-arrows-up-down class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                <span>Urutan Hafalan · {{ $student->name }}</span>
            </h2>
            <p class="text-sm text-gray-600 dark:text-zinc-400">
                {{ $student->classRoom?->name }} · Juz diurutkan sesuai arah hafalan murid. Urutan di dalam juz terdeteksi otomatis dari setoran
                (surah makin kecil = dari akhir juz) dan bisa dikoreksi; perubahan langsung menghitung ulang target otomatis.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 shadow-sm">{{ session('success') }}</div>
            @endif

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <a href="{{ route('hafalan-targets.term', ['class_room_id' => $student->class_room_id]) }}" class="text-sm font-semibold text-indigo-600 hover:underline">&larr; Kembali ke Target Triwulan</a>
                <form method="POST" action="{{ route('hafalan-targets.direction', $student) }}" class="flex items-center gap-2">
                    @csrf
                    @method('PATCH')
                    <label class="text-xs font-bold text-gray-500 uppercase tracking-wider">Arah hafalan</label>
                    <select name="hafalan_direction" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm">
                        @foreach (\App\Support\HafalanOrder::directionOptions() as $value => $label)
                            <option value="{{ $value }}" @selected(\App\Support\HafalanOrder::normalizeDirection($student->hafalan_direction) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-zinc-800/60 text-[11px] font-black uppercase tracking-wider text-gray-500 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3 text-left">#</th>
                            <th class="px-4 py-3 text-left">Juz</th>
                            <th class="px-4 py-3 text-left">Cakupan</th>
                            <th class="px-4 py-3 text-left">Urutan di dalam juz</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                        @foreach ($juzRows as $index => $row)
                            <tr class="{{ $row['setoran_count'] > 0 ? '' : 'opacity-60' }}">
                                <td class="px-4 py-2.5 text-gray-400 font-bold">{{ $index + 1 }}</td>
                                <td class="px-4 py-2.5">
                                    <p class="font-bold text-gray-900 dark:text-white">Juz {{ $row['juz'] }}</p>
                                    <p class="text-[11px] text-gray-500">{{ $surahNames[$row['surah_range'][0]] ?? $row['surah_range'][0] }} – {{ $surahNames[$row['surah_range'][1]] ?? $row['surah_range'][1] }}</p>
                                </td>
                                <td class="px-4 py-2.5 min-w-[180px]">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-2 rounded-full bg-gray-100 dark:bg-zinc-800 overflow-hidden">
                                            <div class="h-full rounded-full {{ $row['covered_percent'] >= 100 ? 'bg-emerald-500' : 'bg-indigo-500' }}" style="width: {{ $row['covered_percent'] }}%"></div>
                                        </div>
                                        <span class="text-xs font-bold text-gray-700 dark:text-zinc-300 w-10 text-right">{{ $row['covered_percent'] }}%</span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 mt-0.5">{{ $row['setoran_count'] }} setoran lulus</p>
                                </td>
                                <td class="px-4 py-2.5">
                                    <form method="POST" action="{{ route('hafalan-targets.juz-order', $student) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="juz" value="{{ $row['juz'] }}">
                                        <select name="order" onchange="this.form.submit()" class="rounded-lg border-gray-200 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 text-xs py-1.5 pl-2 pr-8">
                                            <option value="auto" @selected($row['source'] !== 'manual')>
                                                {{ $row['order'] === 'desc' ? 'Dari akhir juz' : 'Dari awal juz' }} ({{ $row['source'] === 'auto' ? 'otomatis dari setoran' : 'default' }})
                                            </option>
                                            <option value="asc" @selected($row['source'] === 'manual' && $row['order'] === 'asc')>Dari awal juz (diatur)</option>
                                            <option value="desc" @selected($row['source'] === 'manual' && $row['order'] === 'desc')>Dari akhir juz (diatur)</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
