<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
            {{ __('Penilaian Adab & Akhlak') }}
        </h2>
    </x-slot>

    <div class="py-2.5 sm:py-6" x-data="{ tab: '{{ request('tab', 'list') }}' }">
        <div class="max-w-7xl mx-auto space-y-3 sm:space-y-5 px-2 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="p-3 sm:p-4 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-xl sm:rounded-2xl text-emerald-800 dark:text-emerald-300 text-xs sm:text-sm font-semibold">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Banner / Deskripsi (Hidden on mobile to save vertical screen space) -->
            <div class="hidden sm:block bg-gradient-to-r from-teal-500 via-indigo-600 to-indigo-700 text-white rounded-2xl shadow-lg p-5 sm:p-6 relative overflow-hidden">
                <div class="absolute right-0 bottom-0 opacity-10 transform translate-x-12 translate-y-12">
                    <svg class="h-64 w-64" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H7c0-2.76 2.24-5 5-5s5 2.24 5 5c0 1.04-.42 1.99-1.07 2.75z"/>
                    </svg>
                </div>
                <div class="relative z-10 max-w-2xl">
                    <h3 class="text-lg sm:text-xl font-bold mb-1.5 sm:mb-2">Evaluasi Akhlak & Adab Harian</h3>
                    <p class="text-teal-100 text-xs sm:text-sm leading-relaxed">
                        Evaluasi kedisiplinan dan pembiasaan adab islami harian murid. Penilaian mencakup 3 modul mandiri murid (adab kepada Allah, adab kepada Rasulullah, adab belajar) dengan bobot 50% dan penilaian pendamping adab dengan bobot 50%.
                    </p>
                </div>
            </div>

            <!-- Tab Controls -->
            <div class="flex border-b border-zinc-200 dark:border-zinc-800 gap-4 sm:gap-6 no-print overflow-x-auto scrollbar-none">
                <button 
                    @click="tab = 'list'"
                    :class="tab === 'list' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-zinc-400 font-medium' "
                    class="py-3 px-1 border-b-2 text-xs sm:text-sm transition-all focus:outline-none shrink-0 cursor-pointer"
                >
                    Daftar Evaluasi Murid
                </button>
                @if ($canEvaluateMentor)
                    <button 
                        @click="tab = 'monthly_mentor'"
                        :class="tab === 'monthly_mentor' ? 'border-teal-600 text-teal-600 dark:text-teal-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-zinc-400 font-medium' "
                        class="py-3 px-1 border-b-2 text-xs sm:text-sm transition-all focus:outline-none flex items-center gap-1.5 shrink-0 cursor-pointer"
                    >
                        <x-heroicon-o-bolt class="w-4 h-4 text-teal-600 dark:text-teal-400 shrink-0" />
                        <span>Penilaian Bulanan Pendamping</span>
                    </button>
                @endif
                <button 
                    @click="tab = 'dashboard'"
                    :class="tab === 'dashboard' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-zinc-400 font-medium' "
                    class="py-3 px-1 border-b-2 text-xs sm:text-sm transition-all focus:outline-none shrink-0 cursor-pointer"
                >
                    Dashboard Kepatuhan Adab
                </button>
            </div>

            <!-- List Tab Content -->
            <div x-show="tab === 'list'" x-transition class="space-y-5 sm:space-y-6">
                @if (!auth()->user()->hasAnyRole(['student', 'parent']))
                    <!-- Filter -->
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-2xl p-4 sm:p-5">
                        <form method="GET" action="{{ route('adab.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                            <div>
                                <label for="search" class="block text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-2">Cari Murid</label>
                                <input
                                    type="text"
                                    name="search"
                                    id="search"
                                    value="{{ request('search') }}"
                                    placeholder="Cari nama..."
                                    x-on:input.debounce.600ms="$el.form.submit()"
                                    class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 bg-transparent text-xs sm:text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600 py-2.5 px-3"
                                >
                            </div>

                            <div>
                                <label for="class_room_id" class="block text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-2">Kelas</label>
                                <select name="class_room_id" id="class_room_id" onchange="this.form.submit()" class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 bg-transparent text-xs sm:text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white py-2.5 px-3 font-medium cursor-pointer">
                                    <option value="" class="dark:bg-zinc-900">Semua Kelas</option>
                                    @foreach ($classRooms as $classRoom)
                                        <option value="{{ $classRoom->id }}" @selected((string) request('class_room_id') === (string) $classRoom->id) class="dark:bg-zinc-900">
                                            {{ $classRoom->program?->name ? $classRoom->program->name . ' - ' : '' }}{{ $classRoom->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="sm:col-span-2 lg:col-span-2 flex items-end gap-3">
                                <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white rounded-xl text-xs sm:text-sm font-semibold transition duration-150 shadow-sm min-h-[42px] cursor-pointer">
                                    Filter Data
                                </button>

                                <a href="{{ route('adab.index') }}" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 active:scale-95 text-zinc-700 dark:text-zinc-300 rounded-xl text-xs sm:text-sm font-semibold transition duration-150 min-h-[42px] cursor-pointer">
                                    Reset
                                </a>
                            </div>
                        </form>

                        <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800">
                            <p class="text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-2">Pengisian Hari Ini</p>
                            <x-filter-toggle
                                name="fill_status"
                                :options="['' => 'Semua', 'belum' => 'Belum Isi Hari Ini', 'sudah' => 'Sudah Isi Hari Ini']"
                                :current="$fillStatus"
                                :colors="['belum' => 'bg-amber-600 text-white shadow-sm', 'sudah' => 'bg-emerald-600 text-white shadow-sm']"
                            />
                        </div>
                    </div>
                @endif

                <!-- Student List Table -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm sm:rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                                <tr class="text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                    <th class="px-6 py-4">Nama Murid</th>
                                    <th class="px-6 py-4">Kelas</th>
                                    <th class="px-6 py-4 text-center">Status Hari Ini ({{ \Carbon\Carbon::parse($today)->format('d M') }})</th>
                                    <th class="px-6 py-4 text-center">Rata-rata Nilai</th>
                                    <th class="px-6 py-4 text-right">Aksi</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @forelse ($students as $student)
                                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-white/[0.01] transition duration-150">
                                        <td class="px-6 py-4">
                                            <div class="font-semibold text-zinc-900 dark:text-white">
                                                {{ $student->name }}
                                            </div>
                                            <div class="text-xs text-zinc-400 dark:text-zinc-550 mt-0.5">
                                                NIS: {{ $student->student_number ?: '-' }} | {{ $student->gender == 'male' ? 'Laki-laki' : 'Perempuan' }}
                                            </div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200">
                                                {{ $student->classRoom?->name ?: '-' }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 text-center">
                                            @if ($student->today_record)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30">
                                                    Sudah ({{ $student->today_record->total_score }} Poin)
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400 border border-rose-100 dark:border-rose-900/30">
                                                    Belum Mengisi
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-4 text-center">
                                            @if ($student->average_adab_score > 0)
                                                @php
                                                    $score = $student->average_adab_score;
                                                    $grade = $student->adab_grade;
                                                    $badgeColor = match($grade) {
                                                        'A' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30',
                                                        'B' => 'bg-teal-50 text-teal-700 dark:bg-teal-950/20 dark:text-teal-400 border border-teal-100 dark:border-teal-900/30',
                                                        'C' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 border border-amber-100 dark:border-amber-900/30',
                                                        'D' => 'bg-orange-50 text-orange-700 dark:bg-orange-950/20 dark:text-orange-400 border border-orange-100 dark:border-orange-900/30',
                                                        default => 'bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400 border border-rose-100 dark:border-rose-900/30',
                                                    };
                                                @endphp
                                                <div class="flex items-center justify-center gap-2">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-bold {{ $badgeColor }}">
                                                        {{ round($score) }}
                                                    </span>
                                                    <span class="inline-flex items-center justify-center h-7 w-7 rounded-full text-sm font-black {{ $badgeColor }}">
                                                        {{ $grade }}
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-xs text-zinc-400 dark:text-zinc-650 italic">Belum ada data</span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-4 text-right space-x-1">
                                            <a href="{{ route('adab.show', $student) }}" class="inline-flex items-center px-3 py-1.5 border border-zinc-300 dark:border-zinc-700 text-xs font-semibold text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 rounded-md hover:bg-zinc-50 dark:hover:bg-zinc-700 transition duration-150">
                                                Riwayat & Rincian
                                            </a>

                                            @if ($isAdmin || $isSupervisor || Auth::user()->hasRole('teacher') || Auth::user()->hasRole('pendamping_adab'))
                                                @if (!$student->today_record)
                                                    <a href="{{ route('adab.create', $student) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-md transition duration-150">
                                                        Bantu Isi
                                                    </a>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-zinc-400 dark:text-zinc-500">
                                            Tidak ada data murid ditemukan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($students->hasPages())
                        <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                            {{ $students->links() }}
                        </div>
                    @endif
                </div>
            </div>

            @if ($canEvaluateMentor)
                <!-- ═══════════════ PENILAIAN BULANAN PENDAMPING TAB ═══════════════ -->
                <div x-show="tab === 'monthly_mentor'" x-transition class="space-y-6" x-data="monthlyMentorManager()" x-init="init()">
                    
                    <!-- Notification Banner for AJAX Save -->
                    <template x-if="alertMessage">
                        <div :class="alertType === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800' : 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800'"
                             class="p-4 border rounded-2xl text-sm font-semibold flex items-center justify-between shadow-sm transition-all">
                            <div class="flex items-center gap-2">
                                <template x-if="alertType === 'success'">
                                    <x-heroicon-o-check-circle class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                </template>
                                <template x-if="alertType !== 'success'">
                                    <x-heroicon-o-exclamation-triangle class="w-4 h-4 text-rose-600 dark:text-rose-400 shrink-0" />
                                </template>
                                <span x-text="alertMessage"></span>
                            </div>
                            <button type="button" @click="alertMessage = ''" class="text-xs opacity-70 hover:opacity-100 p-1 cursor-pointer">
                                <x-heroicon-o-x-mark class="w-4 h-4" />
                            </button>
                        </div>
                    </template>

                    <!-- Filter Bar & Bulk Actions Header -->
                    <div class="bg-white dark:bg-zinc-900 p-5 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800 space-y-4">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <!-- Filters -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 flex-1">
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1">
                                        Pilih Kelas
                                    </label>
                                    <select x-model="selectedClassId" @change="fetchClassData()" 
                                            class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-xs font-semibold px-3 py-2.5 focus:border-teal-500 focus:ring-teal-500">
                                        @foreach ($classRooms as $class)
                                            <option value="{{ $class->id }}">
                                                {{ $class->program?->name ? $class->program->name . ' - ' : '' }}{{ $class->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1">
                                        Bulan Penilaian
                                    </label>
                                    <select x-model="selectedMonth" @change="fetchClassData()"
                                            class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-xs font-semibold px-3 py-2.5 focus:border-teal-500 focus:ring-teal-500">
                                        @php
                                            $allMonths = [
                                                1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                                                4 => 'April', 5 => 'Mei', 6 => 'Juni',
                                                7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                                                10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                            ];
                                        @endphp
                                        @foreach ($allMonths as $mNum => $mName)
                                            <option value="{{ $mNum }}" @selected($mNum == (int) now()->format('n'))>
                                                {{ $mName }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 mb-1">
                                        Tahun
                                    </label>
                                    <select x-model="selectedYear" @change="fetchClassData()"
                                            class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-xs font-semibold px-3 py-2.5 focus:border-teal-500 focus:ring-teal-500">
                                        @for ($y = (int) now()->format('Y') + 1; $y >= 2024; $y--)
                                            <option value="{{ $y }}" @selected($y == (int) now()->format('Y'))>
                                                {{ $y }}
                                            </option>
                                        @endfor
                                    </select>
                                </div>
                            </div>

                            <!-- Right Stats & Save Button -->
                            <div class="flex items-center gap-3 justify-between lg:justify-end border-t lg:border-t-0 pt-3 lg:pt-0 border-zinc-100 dark:border-zinc-800">
                                <div class="text-xs font-semibold text-zinc-600 dark:text-zinc-400">
                                    <span>Terisi: </span>
                                    <strong class="text-teal-600 dark:text-teal-400 text-sm font-black" x-text="filledCount">0</strong>
                                    <span> / </span>
                                    <span class="font-bold text-zinc-800 dark:text-zinc-200" x-text="rows.length">0</span> Murid
                                </div>

                                <button
                                    type="button"
                                    @click="submitAll()"
                                    :disabled="isSubmitting || filledCount === 0 || isLoading"
                                    :class="filledCount > 0 && !isLoading ? 'bg-teal-600 hover:bg-teal-700 text-white shadow-md cursor-pointer' : 'bg-zinc-200 dark:bg-zinc-800 text-zinc-400 dark:text-zinc-600 cursor-not-allowed'"
                                    class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl font-bold text-xs sm:text-sm transition-all"
                                >
                                    <span x-show="!isSubmitting && !isLoading" class="inline-flex items-center gap-1.5"><x-heroicon-o-check class="w-4 h-4" /> Simpan Semua (<span x-text="filledCount">0</span>)</span>
                                    <span x-show="isSubmitting" x-cloak class="flex items-center gap-1.5">
                                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                        Menyimpan...
                                    </span>
                                    <span x-show="isLoading" x-cloak>Memuat...</span>
                                </button>
                            </div>
                        </div>

                        <!-- Shortcut Bar: Set Cepat Semua Murid -->
                        <div class="pt-3 border-t border-zinc-100 dark:border-zinc-800/80 flex flex-wrap items-center justify-between gap-3 text-xs">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mr-1">
                                    Set Nilai Cepat Semua:
                                </span>
                                <template x-for="val in [100, 95, 90, 85, 80, 75]" :key="val">
                                    <button type="button" @click="setAllScores(val)"
                                            class="px-2.5 py-1 rounded-lg bg-zinc-100 dark:bg-zinc-800 hover:bg-teal-600 hover:text-white dark:hover:bg-teal-600 dark:hover:text-white text-zinc-700 dark:text-zinc-300 font-bold text-xs transition cursor-pointer">
                                        <span x-text="val"></span>
                                    </button>
                                </template>
                                <button type="button" @click="clearAllScores()"
                                        class="px-2.5 py-1 rounded-lg border border-zinc-200 dark:border-zinc-700 text-zinc-500 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 text-[11px] font-semibold transition cursor-pointer ml-1">
                                    Kosongkan
                                </button>
                            </div>

                            <div class="text-[11px] text-zinc-500 dark:text-zinc-400 flex items-center gap-1">
                                <x-heroicon-o-light-bulb class="w-3.5 h-3.5 text-amber-500 shrink-0" />
                                <em>Klik tombol angka untuk memilih nilai secara cepat dengan kelipatan 5 atau ketik langsung di kolom nilai.</em>
                            </div>
                        </div>
                    </div>

                    <!-- Main Fast Grading Table -->
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800 overflow-hidden relative">
                        
                        <!-- Loading Overlay -->
                        <div x-show="isLoading" x-cloak class="absolute inset-0 bg-white/70 dark:bg-zinc-900/70 backdrop-blur-xs flex items-center justify-center z-20">
                            <div class="flex items-center gap-2 text-teal-600 dark:text-teal-400 font-bold text-sm">
                                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                Memuat data murid...
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                                <thead>
                                    <tr class="bg-zinc-50/80 dark:bg-zinc-800/50 text-left text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                        <th class="px-4 py-3.5 text-center w-12">No</th>
                                        <th class="px-4 py-3.5 min-w-[200px]">Murid</th>
                                        <th class="px-4 py-3.5 text-center min-w-[120px]">Bulan Lalu</th>
                                        <th class="px-4 py-3.5 min-w-[360px]">Nilai Pendamping (0 - 100)</th>
                                        <th class="px-4 py-3.5 min-w-[220px]">Catatan / Evaluasi</th>
                                        <th class="px-4 py-3.5 text-center min-w-[110px]">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/80 text-xs">
                                    <template x-for="(row, index) in rows" :key="row.student_id">
                                        <tr :class="row.mentor_score !== '' && row.mentor_score !== null ? 'bg-teal-50/20 dark:bg-teal-950/10' : 'hover:bg-zinc-50/60 dark:hover:bg-zinc-800/30'" 
                                            class="transition-colors">
                                            
                                            <!-- No -->
                                            <td class="px-4 py-3 text-center text-zinc-400 font-bold" x-text="index + 1"></td>

                                            <!-- Nama Murid -->
                                            <td class="px-4 py-3">
                                                <div class="font-bold text-zinc-900 dark:text-white" x-text="row.student_name"></div>
                                                <div class="text-[11px] text-zinc-400 dark:text-zinc-500 mt-0.5">
                                                    NIS: <span x-text="row.student_number || '-'"></span> · 
                                                    <span x-text="row.gender === 'male' ? 'Laki-laki' : 'Perempuan'"></span>
                                                </div>
                                            </td>

                                            <!-- Nilai Bulan Lalu -->
                                            <td class="px-4 py-3 text-center">
                                                <template x-if="row.previous_score !== null && row.previous_score !== undefined">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                                        <span x-text="row.previous_score"></span> / 100
                                                    </span>
                                                </template>
                                                <template x-if="row.previous_score === null || row.previous_score === undefined">
                                                    <span class="text-zinc-400 text-xs italic">-</span>
                                                </template>
                                            </td>

                                            <!-- Nilai Pendamping & Penilaian Cepat -->
                                            <td class="px-4 py-3">
                                                <div class="space-y-2">
                                                    <div class="flex items-center gap-2">
                                                        <div class="relative w-24 shrink-0">
                                                            <input
                                                                type="number"
                                                                min="0"
                                                                max="100"
                                                                step="1"
                                                                x-model="row.mentor_score"
                                                                @input="row.is_touched = true"
                                                                placeholder="0-100"
                                                                class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-xs font-black text-center px-2.5 py-1.5 focus:ring-teal-500 focus:border-teal-500"
                                                                :class="getScoreBorderClass(row.mentor_score)"
                                                            >
                                                        </div>
                                                        <span class="text-[11px] font-bold text-zinc-400">/ 100</span>

                                                        <template x-if="row.mentor_score !== '' && row.mentor_score !== null">
                                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider"
                                                                  :class="getScoreBadgeClass(row.mentor_score)"
                                                                  x-text="getScoreGradeLabel(row.mentor_score)">
                                                            </span>
                                                        </template>
                                                    </div>

                                                    <!-- Quick Score Pills (100, 95, 90, 85, 80, 75, 70, 65, 60, 55, 50) -->
                                                    <div class="flex flex-wrap items-center gap-1">
                                                        <template x-for="qVal in [100, 95, 90, 85, 80, 75, 70, 65, 60, 55, 50]" :key="qVal">
                                                            <button
                                                                type="button"
                                                                @click="setScore(row, qVal)"
                                                                :class="Number(row.mentor_score) === qVal ? 'bg-teal-600 text-white font-black shadow-xs scale-105' : 'bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 font-semibold'"
                                                                class="px-2 py-1 rounded-md text-[11px] transition-all cursor-pointer select-none"
                                                            >
                                                                <span x-text="qVal"></span>
                                                            </button>
                                                        </template>
                                                    </div>
                                                </div>
                                            </td>

                                            <!-- Catatan Pembiasaan -->
                                            <td class="px-4 py-3">
                                                <input
                                                    type="text"
                                                    x-model="row.notes"
                                                    @input="row.is_touched = true"
                                                    placeholder="Catatan perkembangan pembiasaan..."
                                                    class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-xs px-3 py-1.5 focus:ring-teal-500 focus:border-teal-500 placeholder-zinc-400 dark:placeholder-zinc-600"
                                                >
                                            </td>

                                            <!-- Status -->
                                            <td class="px-4 py-3 text-center">
                                                <template x-if="row.is_touched">
                                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                                        <x-heroicon-o-pencil class="w-3 h-3" />
                                                        <span>Belum Disimpan</span>
                                                    </span>
                                                </template>
                                                <template x-if="!row.is_touched && row.is_already_saved">
                                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                        <x-heroicon-o-check class="w-3 h-3 stroke-[2.5]" />
                                                        <span>Tersimpan</span>
                                                    </span>
                                                </template>
                                                <template x-if="!row.is_touched && !row.is_already_saved">
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-[10px] font-bold bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                                        Belum Dinilai
                                                    </span>
                                                </template>
                                            </td>

                                        </tr>
                                    </template>

                                    <!-- Empty State -->
                                    <template x-if="rows.length === 0 && !isLoading">
                                        <tr>
                                            <td colspan="6" class="px-6 py-12 text-center text-zinc-400 dark:text-zinc-500">
                                                Tidak ada data murid aktif pada kelas ini.
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            @endif

            <!-- Dashboard Visual Tab Content -->
            <div x-show="tab === 'dashboard'" x-transition class="space-y-6">
                <!-- Compliance per Aspect -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
                    <h4 class="text-base font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2 border-b pb-2">
                        <x-heroicon-o-chart-bar class="w-5 h-5 text-indigo-600 dark:text-indigo-400" /> Persentase Kepatuhan Berdasarkan Kategori Adab
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach ($categories as $catIdx => $cat)
                            @php
                                $pct   = $catStats[$catIdx] ?? 0;
                                $grade = \App\Models\Setting::getAdabGrade($pct);
                                $colors = [
                                    'A' => ['bar' => 'bg-emerald-500', 'text' => 'text-emerald-600 dark:text-emerald-400'],
                                    'B' => ['bar' => 'bg-teal-500',    'text' => 'text-teal-600 dark:text-teal-400'],
                                    'C' => ['bar' => 'bg-amber-500',   'text' => 'text-amber-600 dark:text-amber-400'],
                                    'D' => ['bar' => 'bg-orange-500',  'text' => 'text-orange-600 dark:text-orange-400'],
                                    'E' => ['bar' => 'bg-rose-500',    'text' => 'text-rose-600 dark:text-rose-400'],
                                ][$grade] ?? ['bar' => 'bg-zinc-500', 'text' => 'text-zinc-600 dark:text-zinc-400'];
                            @endphp
                            <div class="bg-zinc-50 dark:bg-zinc-800/40 rounded-xl p-5 border dark:border-zinc-800 flex flex-col justify-between items-center text-center">
                                <span class="text-2xl mb-2">{{ substr($cat['title'], 0, 2) }}</span>
                                <span class="text-xs font-bold uppercase tracking-wider text-gray-550 dark:text-zinc-400 leading-tight">{{ Str::after($cat['title'], ' ') }}</span>
                                <h3 class="text-3xl font-extrabold {{ $colors['text'] }} mt-2">{{ $grade }}</h3>
                                <p class="text-sm font-semibold text-zinc-500 dark:text-zinc-400">{{ $pct }}%</p>
                                <div class="w-full bg-gray-200 dark:bg-zinc-700 h-2 rounded-full overflow-hidden mt-4">
                                    <div class="{{ $colors['bar'] }} h-full rounded-full" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Class Rankings -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm lg:col-span-1">
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 border-b pb-2 flex items-center gap-1.5">
                            <x-heroicon-o-trophy class="w-5 h-5 text-amber-500" /> Kelas dengan Adab Terbaik
                        </h4>
                        @if($classRankings->isEmpty())
                            <p class="text-xs text-gray-500 dark:text-zinc-500 text-center py-6">Belum ada kelas yang terdata.</p>
                        @else
                            <div class="space-y-4">
                                @foreach($classRankings as $rank => $class)
                                    <div class="flex items-center justify-between text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-50 dark:bg-amber-950/20 text-xs font-bold text-amber-700 dark:text-amber-400">
                                                {{ $rank + 1 }}
                                            </span>
                                            <span class="font-medium text-gray-700 dark:text-zinc-250">{{ is_array($class) ? $class['name'] : $class->name }}</span>
                                        </div>
                                        <span class="font-extrabold text-indigo-600 dark:text-indigo-400">{{ round(is_array($class) ? $class['avg_score'] : $class->avg_score, 1) }} / 100</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Instructions and Advice -->
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm lg:col-span-2 flex flex-col justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 border-b pb-2 flex items-center gap-1.5">
                                <x-heroicon-o-light-bulb class="w-5 h-5 text-amber-500" /> Tips Pembinaan Karakter Murid
                            </h4>
                            <ul class="space-y-2 text-xs text-gray-600 dark:text-zinc-400 leading-relaxed list-disc list-inside">
                                <li><strong>Target Kepatuhan Tinggi:</strong> Murid dengan kepatuhan adab di atas 85% dikategorikan sebagai <span class="text-green-600 dark:text-emerald-400 font-semibold">Mumtaz</span>. Berikan pujian untuk mempertahankan konsistensi.</li>
                                <li><strong>Intervensi Dini:</strong> Jika adab Al-Qur'an memiliki nilai kepatuhan yang rendah, kaji ulang jadwal murojaah harian bersama asatidzah/guru tahfizh.</li>
                                <li><strong>Kolaborasi dengan Orang Tua:</strong> Manfaatkan menu adab untuk mendiskusikan kepatuhan harian murid saat berada di lingkungan rumah bersama orang tua wali.</li>
                            </ul>
                        </div>
                        <div class="mt-6 p-4 bg-teal-50/50 dark:bg-teal-950/10 rounded-xl border border-teal-100 dark:border-teal-900/30 text-xs text-teal-800 dark:text-teal-400">
                            <strong>Statistik Real-time:</strong> Data di atas diperoleh secara langsung dari rangkuman kuisioner adab harian murid aktif yang telah divalidasi.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($canEvaluateMentor)
        <script>
        function monthlyMentorManager() {
            return {
                selectedClassId: {{ $classRooms->first()?->id ?? 0 }},
                selectedMonth: {{ (int) now()->format('n') }},
                selectedYear: {{ (int) now()->format('Y') }},
                rows: [],
                isLoading: false,
                isSubmitting: false,
                alertMessage: '',
                alertType: 'success',

                get filledCount() {
                    return this.rows.filter(r => r.mentor_score !== '' && r.mentor_score !== null && !isNaN(r.mentor_score)).length;
                },

                init() {
                    if (this.selectedClassId) {
                        this.fetchClassData();
                    }
                },

                async fetchClassData() {
                    if (!this.selectedClassId) return;

                    this.isLoading = true;
                    this.alertMessage = '';

                    try {
                        const url = '{{ route('adab.mentor-class-data') }}?class_room_id=' + this.selectedClassId + 
                                    '&year=' + this.selectedYear + 
                                    '&month=' + this.selectedMonth;

                        const response = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (!response.ok) {
                            throw new Error('Gagal memuat data kelas.');
                        }

                        const result = await response.json();
                        this.rows = (result.students || []).map(s => ({
                            ...s,
                            is_touched: false
                        }));
                    } catch (error) {
                        console.error('Error fetching class data:', error);
                        this.alertType = 'error';
                        this.alertMessage = 'Terjadi kesalahan saat memuat data kelas: ' + error.message;
                    } finally {
                        this.isLoading = false;
                    }
                },

                setScore(row, val) {
                    row.mentor_score = val;
                    row.is_touched = true;
                },

                setAllScores(val) {
                    this.rows.forEach(r => {
                        r.mentor_score = val;
                        r.is_touched = true;
                    });
                },

                clearAllScores() {
                    this.rows.forEach(r => {
                        r.mentor_score = '';
                        r.is_touched = true;
                    });
                },

                getScoreBorderClass(score) {
                    if (score === '' || score === null) return '';
                    const s = Number(score);
                    if (s >= 85) return 'border-emerald-500 text-emerald-700 dark:text-emerald-300';
                    if (s >= 75) return 'border-teal-500 text-teal-700 dark:text-teal-300';
                    if (s >= 65) return 'border-amber-500 text-amber-700 dark:text-amber-300';
                    return 'border-rose-500 text-rose-700 dark:text-rose-300';
                },

                getScoreBadgeClass(score) {
                    if (score === '' || score === null) return '';
                    const s = Number(score);
                    if (s >= 85) return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300';
                    if (s >= 75) return 'bg-teal-100 text-teal-800 dark:bg-teal-950/60 dark:text-teal-300';
                    if (s >= 65) return 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300';
                    return 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300';
                },

                getScoreGradeLabel(score) {
                    if (score === '' || score === null) return '';
                    const s = Number(score);
                    if (s >= 90) return 'Mumtaz (A)';
                    if (s >= 80) return 'Jayyid Jiddan (B)';
                    if (s >= 70) return 'Jayyid (C)';
                    if (s >= 60) return 'Maqbūl (D)';
                    return 'Perlu Pembinaan (E)';
                },

                async submitAll() {
                    const filledEntries = this.rows
                        .filter(r => r.mentor_score !== '' && r.mentor_score !== null)
                        .map(r => ({
                            student_id: r.student_id,
                            mentor_score: parseFloat(r.mentor_score),
                            notes: r.notes || ''
                        }));

                    if (filledEntries.length === 0) {
                        this.alertType = 'error';
                        this.alertMessage = 'Harap isi nilai minimal untuk 1 murid sebelum menyimpan.';
                        return;
                    }

                    this.isSubmitting = true;
                    this.alertMessage = '';

                    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                    try {
                        const response = await fetch('{{ route('adab.batch-mentor-score') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                class_room_id: this.selectedClassId,
                                year: this.selectedYear,
                                month: this.selectedMonth,
                                entries: filledEntries
                            })
                        });

                        const res = await response.json();

                        if (response.ok && res.success) {
                            this.alertType = 'success';
                            this.alertMessage = res.message || 'Penilaian bulanan berhasil disimpan!';
                            
                            // Mark touched rows as saved
                            this.rows.forEach(r => {
                                if (r.mentor_score !== '' && r.mentor_score !== null) {
                                    r.is_already_saved = true;
                                    r.is_touched = false;
                                }
                            });
                        } else {
                            throw new Error(res.message || 'Gagal menyimpan penilaian.');
                        }
                    } catch (err) {
                        console.error('Save error:', err);
                        this.alertType = 'error';
                        this.alertMessage = 'Gagal menyimpan: ' + (err.message || 'Terjadi kesalahan pada server.');
                    } finally {
                        this.isSubmitting = false;
                    }
                }
            };
        }
        </script>
    @endif
</x-app-layout>
