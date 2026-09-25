<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                    Poin & Disiplin Murid
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">
                    Pencatatan pelanggaran tata tertib dan penghargaan prestasi murid.
                </p>
            </div>
            
            @if ($canManage)
                <a href="{{ route('student-points.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Catat Poin Baru</span>
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-3 sm:py-6" x-data="{ tab: 'list' }">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-3.5 sm:space-y-6">

            @if (session('success'))
                <div class="rounded-xl sm:rounded-2xl border border-green-200 bg-green-50 px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm font-medium text-green-800 dark:bg-emerald-950/40 dark:border-emerald-800/60 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Statistics Summary Cards (Horizontal 3-column row on mobile) -->
            <div class="grid grid-cols-3 gap-2 sm:gap-4 md:grid-cols-3">
                <!-- Total Violations Card -->
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-2.5 sm:p-5 shadow-xs flex items-center justify-between transition-all hover:shadow-sm">
                    <div>
                        <p class="text-[10px] sm:text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider truncate">Pelanggaran</p>
                        <h3 class="text-base sm:text-3xl font-black text-red-600 dark:text-rose-500 mt-0.5 sm:mt-2">{{ $totalViolations }}</h3>
                        <p class="hidden sm:block text-[10px] text-gray-400 dark:text-zinc-500 mt-1">Akumulasi tata tertib</p>
                    </div>
                    <div class="hidden sm:flex w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-red-50 dark:bg-rose-950/30 items-center justify-center text-red-500 text-lg sm:text-xl shadow-xs shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 sm:w-6 sm:h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" />
                        </svg>
                    </div>
                </div>

                <!-- Total Rewards Card -->
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-2.5 sm:p-5 shadow-xs flex items-center justify-between transition-all hover:shadow-sm">
                    <div>
                        <p class="text-[10px] sm:text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider truncate">Penghargaan</p>
                        <h3 class="text-base sm:text-3xl font-black text-green-600 dark:text-emerald-500 mt-0.5 sm:mt-2">{{ $totalRewards }}</h3>
                        <p class="hidden sm:block text-[10px] text-gray-400 dark:text-zinc-500 mt-1">Akumulasi prestasi</p>
                    </div>
                    <div class="hidden sm:flex w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-green-50 dark:bg-emerald-950/30 items-center justify-center text-green-500 text-lg sm:text-xl shadow-xs shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 sm:w-6 sm:h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.504-1.125-1.125-1.125h-2.25a1.125 1.125 0 00-1.125 1.125V18.75m9 0a9 9 0 11-18 0M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Net Score Card -->
                @php $balance = $totalRewards - $totalViolations; @endphp
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-2.5 sm:p-5 shadow-xs flex items-center justify-between transition-all hover:shadow-sm">
                    <div>
                        <p class="text-[10px] sm:text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider truncate">Selisih Net</p>
                        <h3 class="text-base sm:text-3xl font-black mt-0.5 sm:mt-2 {{ $balance >= 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-amber-600' }}">
                            {{ $balance > 0 ? '+' : '' }}{{ $balance }}
                        </h3>
                        <p class="hidden sm:block text-[10px] text-gray-400 dark:text-zinc-500 mt-1">Net kebaikan murid</p>
                    </div>
                    <div class="hidden sm:flex w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-indigo-50 dark:bg-indigo-950/30 items-center justify-center text-indigo-500 text-lg sm:text-xl shadow-xs shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 sm:w-6 sm:h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Tab Controls (Only for Staff/Teachers) -->
            @if(!auth()->user()->hasAnyRole(['parent', 'student']))
                <div class="flex border-b border-gray-200 dark:border-zinc-800 gap-4 sm:gap-6 no-print overflow-x-auto scrollbar-none">
                    <button 
                        @click="tab = 'list'"
                        :class="tab === 'list' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-zinc-400' "
                        class="py-2.5 sm:py-3 px-1 border-b-2 text-xs sm:text-sm transition-all focus:outline-none shrink-0 cursor-pointer"
                    >
                        Riwayat Catatan
                    </button>
                    <button 
                        @click="tab = 'dashboard'"
                        :class="tab === 'dashboard' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-zinc-400' "
                        class="py-2.5 sm:py-3 px-1 border-b-2 text-xs sm:text-sm transition-all focus:outline-none shrink-0 cursor-pointer"
                    >
                        Dashboard Analitis
                    </button>
                </div>
            @endif

            <!-- List Tab Content -->
            <div x-show="tab === 'list'" x-transition class="space-y-5 sm:space-y-6">
                @if(!auth()->user()->hasAnyRole(['parent', 'student']))
                    <!-- Filter & Search (no-print) -->
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-4 sm:p-5 shadow-sm transition-colors duration-200">
                        <form method="GET" action="{{ route('student-points.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 items-end">
                            <div>
                                <label for="search" class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Cari Murid</label>
                                <input
                                    type="text"
                                    name="search"
                                    id="search"
                                    value="{{ request('search') }}"
                                    placeholder="Cari nama atau NIS murid..."
                                    x-on:input.debounce.600ms="$el.form.submit()"
                                    class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs sm:text-sm py-2.5 px-3"
                                />
                            </div>

                            @if ($classRooms->isNotEmpty())
                                <div>
                                    <label for="class_room_id" class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Kelas</label>
                                    <select name="class_room_id" id="class_room_id" onchange="this.form.submit()" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs sm:text-sm py-2.5 px-3 font-medium cursor-pointer">
                                        <option value="">Semua Kelas</option>
                                        @foreach ($classRooms as $class)
                                            <option value="{{ $class->id }}" {{ request('class_room_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="sm:col-span-2 lg:col-span-2">
                                <label for="type" class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Tipe Poin</label>
                                <x-filter-toggle
                                    name="type"
                                    :options="['' => 'Semua Tipe', 'violation' => 'Tata Tertib', 'lateness' => 'Keterlambatan', 'attribute' => 'Atribut/Seragam', 'reward' => 'Prestasi']"
                                    :colors="['violation' => 'bg-rose-600 text-white shadow-sm', 'lateness' => 'bg-amber-600 text-white shadow-sm', 'attribute' => 'bg-blue-600 text-white shadow-sm', 'reward' => 'bg-emerald-600 text-white shadow-sm']"
                                />
                            </div>

                            <div class="flex gap-2">
                                <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 border border-transparent rounded-xl text-xs sm:text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-colors min-h-[42px] cursor-pointer">
                                    Filter
                                </button>
                                @if (request()->anyFilled(['search', 'type', 'class_room_id']))
                                    <a href="{{ route('student-points.index') }}" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 border border-gray-300 dark:border-zinc-700 rounded-xl text-xs sm:text-sm font-semibold text-gray-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors min-h-[42px] cursor-pointer">
                                        Reset
                                    </a>
                                @endif
                            </div>
                        </form>
                    </div>
                @endif

                <!-- List Table -->
                <div class="bg-white dark:bg-zinc-900 rounded-xl sm:rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-xs overflow-hidden transition-colors duration-200">
                    <div class="border-b border-gray-200 dark:border-zinc-800 px-3.5 sm:px-6 py-2.5 sm:py-4 bg-gray-50/50 dark:bg-[#09090b]/40">
                        <h3 class="text-xs sm:text-lg font-bold text-gray-900 dark:text-white">Riwayat Poin Kedisiplinan</h3>
                    </div>

                    @if ($points->isEmpty())
                        <div class="p-6 sm:p-8 text-center text-xs sm:text-sm text-gray-500 dark:text-zinc-500">
                            Tidak ada riwayat catatan poin disiplin ditemukan.
                        </div>
                    @else
                        <div class="overflow-x-auto touch-scroll">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800">
                                <thead class="bg-gray-50 dark:bg-[#09090b]/40">
                                    <tr>
                                        <th scope="col" class="px-3.5 sm:px-6 py-2.5 sm:py-3.5 text-left text-[10px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Tanggal</th>
                                        <th scope="col" class="px-3.5 sm:px-6 py-2.5 sm:py-3.5 text-left text-[10px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Murid</th>
                                        <th scope="col" class="px-3.5 sm:px-6 py-2.5 sm:py-3.5 text-left text-[10px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Kategori / Judul</th>
                                        <th scope="col" class="px-3.5 sm:px-6 py-2.5 sm:py-3.5 text-center text-[10px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Tipe</th>
                                        <th scope="col" class="px-3.5 sm:px-6 py-2.5 sm:py-3.5 text-center text-[10px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Poin</th>
                                        <th scope="col" class="px-3.5 sm:px-6 py-2.5 sm:py-3.5 text-left text-[10px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Dicatat Oleh</th>
                                        @if ($canManage)
                                            <th scope="col" class="px-3.5 sm:px-6 py-2.5 sm:py-3.5 text-center text-[10px] sm:text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Aksi</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-zinc-800 bg-white dark:bg-zinc-900 transition-colors duration-200">
                                    @foreach ($points as $item)
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-white/5 transition-colors">
                                            <!-- Tanggal -->
                                            <td class="px-3.5 sm:px-6 py-2.5 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-gray-700 dark:text-zinc-300">
                                                {{ $item->date?->format('d/m/Y') }}
                                            </td>
                                            
                                            <!-- Murid -->
                                            <td class="px-3.5 sm:px-6 py-2.5 sm:py-4 whitespace-nowrap text-xs sm:text-sm font-semibold text-gray-900 dark:text-white">
                                                <div>{{ $item->student?->name }}</div>
                                                <div class="text-[10px] text-gray-400 font-medium">Kelas: {{ $item->student?->classRoom?->name ?? '-' }}</div>
                                            </td>

                                            <!-- Judul / Deskripsi -->
                                            <td class="px-3.5 sm:px-6 py-2.5 sm:py-4 text-xs sm:text-sm text-gray-700 dark:text-zinc-300">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span class="font-semibold text-gray-900 dark:text-zinc-200">{{ $item->title }}</span>
                                                    @if($item->category)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-red-50 text-red-700 dark:bg-rose-950/20 dark:text-rose-300 uppercase">
                                                            {{ $item->category }}
                                                        </span>
                                                    @endif
                                                    @if($item->achievement_type)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-green-50 text-green-700 dark:bg-emerald-950/20 dark:text-emerald-300 uppercase">
                                                            {{ $item->achievement_type === 'academic' ? 'Akademik' : 'Non-Akademik' }}
                                                        </span>
                                                    @endif
                                                    @if($item->achievement_level)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-indigo-50 text-indigo-700 dark:bg-indigo-950/20 dark:text-indigo-300 uppercase">
                                                            {{ $item->achievement_level }}
                                                        </span>
                                                    @endif
                                                    @if($item->location)
                                                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600 dark:bg-zinc-800 dark:text-zinc-350">
                                                            <x-heroicon-o-map-pin class="w-3 h-3 text-gray-500 shrink-0" />
                                                            <span>{{ $item->location }}</span>
                                                        </span>
                                                    @endif
                                                </div>
                                                @if ($item->description)
                                                    <p class="text-[11px] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 sm:mt-1 line-clamp-1" title="{{ $item->description }}">{{ $item->description }}</p>
                                                @endif
                                                @if ($item->sanction)
                                                    <p class="text-[11px] sm:text-xs text-amber-600 dark:text-amber-500 mt-0.5 sm:mt-1" title="Sanksi: {{ $item->sanction }}">
                                                        <strong>Sanksi:</strong> {{ $item->sanction }}
                                                    </p>
                                                @endif
                                            </td>

                                            <!-- Tipe Badge -->
                                            <td class="px-3.5 sm:px-6 py-2.5 sm:py-4 text-center whitespace-nowrap">
                                                @php $isV = \App\Models\StudentPoint::isViolationType($item->type); @endphp
                                                @if ($isV)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] sm:text-xs font-bold {{ $item->type === 'lateness' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200 dark:border-amber-900/40' : ($item->type === 'attribute' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950/40 dark:text-blue-300 border border-blue-200 dark:border-blue-900/40' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300 border border-rose-200 dark:border-rose-900/40') }} uppercase">
                                                        {{ \App\Models\StudentPoint::getTypeLabel($item->type) }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] sm:text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900/40 uppercase">
                                                        Prestasi / Penghargaan
                                                    </span>
                                                @endif
                                            </td>

                                            <!-- Poin -->
                                            <td class="px-3.5 sm:px-6 py-2.5 sm:py-4 text-center whitespace-nowrap text-xs sm:text-sm font-black">
                                                <span class="{{ $isV ? 'text-red-600 dark:text-rose-500' : 'text-green-600 dark:text-emerald-500' }}">
                                                    {{ $isV ? '-' : '+' }}{{ $item->points }}
                                                </span>
                                            </td>

                                            <!-- Logger -->
                                            <td class="px-3.5 sm:px-6 py-2.5 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-gray-700 dark:text-zinc-400">
                                                {{ $item->logger?->name ?? 'Sistem' }}
                                            </td>

                                            <!-- Aksi -->
                                            @if ($canManage)
                                                <td class="px-3.5 sm:px-6 py-2.5 sm:py-4 whitespace-nowrap text-center text-xs sm:text-sm font-medium">
                                                    <div class="inline-flex gap-2">
                                                        <a href="{{ route('student-points.edit', $item) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300" title="Ubah">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 sm:w-4 sm:h-4">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.83 20.013a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" />
                                                            </svg>
                                                        </a>
                                                        
                                                        <form method="POST" action="{{ route('student-points.destroy', $item) }}" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan poin ini?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-rose-600 hover:text-rose-900 dark:text-rose-400 dark:hover:text-rose-300 cursor-pointer" title="Hapus">
                                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5 sm:w-4 sm:h-4">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($points->hasPages())
                            <div class="px-3.5 sm:px-6 py-2.5 sm:py-4 border-t border-gray-200 dark:border-zinc-800 bg-gray-50/50 dark:bg-[#09090b]/40">
                                {{ $points->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Dashboard Visual Tab Content -->
            <div x-show="tab === 'dashboard'" x-transition class="space-y-4 sm:space-y-6">
                <!-- Top Aggregates -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 sm:gap-6">
                    
                    <!-- Kategori Pelanggaran -->
                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-3.5 sm:p-6 shadow-xs">
                        <h4 class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-3 sm:mb-4 border-b border-gray-100 dark:border-zinc-800 pb-2 flex items-center gap-1.5">
                            <x-heroicon-o-exclamation-triangle class="w-4 h-4 text-rose-500 shrink-0" />
                            <span>Poin Pelanggaran per Kategori</span>
                        </h4>
                        <div class="space-y-3 sm:space-y-4">
                            @foreach(['ringan' => 'Ringan', 'sedang' => 'Sedang', 'berat' => 'Berat'] as $key => $label)
                                @php
                                    $data = $violationsByCategory->get($key);
                                    $cnt = $data?->count ?? 0;
                                    $pts = $data?->points ?? 0;
                                    $maxPts = max(1, $totalViolations);
                                    $pct = round(($pts / $maxPts) * 100);
                                @endphp
                                <div class="space-y-1">
                                    <div class="flex justify-between text-[11px] sm:text-xs font-semibold">
                                        <span class="text-gray-700 dark:text-zinc-350">{{ $label }}</span>
                                        <span class="text-red-600 dark:text-rose-400">{{ $pts }} Poin ({{ $cnt }} Kasus)</span>
                                    </div>
                                    <div class="w-full bg-gray-100 dark:bg-zinc-800 h-2 sm:h-2.5 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-rose-500 to-red-600 rounded-full" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Kategori Penghargaan / Tingkatan -->
                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-3.5 sm:p-6 shadow-xs">
                        <h4 class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-3 sm:mb-4 border-b border-gray-100 dark:border-zinc-800 pb-2 flex items-center gap-1.5">
                            <x-heroicon-o-trophy class="w-4 h-4 text-emerald-500 shrink-0" />
                            <span>Poin Prestasi per Tingkat</span>
                        </h4>
                        <div class="space-y-3 sm:space-y-4">
                            @foreach(['school' => 'Tingkat Sekolah', 'district' => 'Kabupaten/Kota', 'province' => 'Provinsi', 'national' => 'Nasional'] as $key => $label)
                                @php
                                    $data = $rewardsByLevel->get($key);
                                    $cnt = $data?->count ?? 0;
                                    $pts = $data?->points ?? 0;
                                    $maxPts = max(1, $totalRewards);
                                    $pct = round(($pts / $maxPts) * 100);
                                @endphp
                                <div class="space-y-1">
                                    <div class="flex justify-between text-[11px] sm:text-xs font-semibold">
                                        <span class="text-gray-700 dark:text-zinc-350">{{ $label }}</span>
                                        <span class="text-emerald-600 dark:text-emerald-400">{{ $pts }} Poin ({{ $cnt }} Prestasi)</span>
                                    </div>
                                    <div class="w-full bg-gray-100 dark:bg-zinc-800 h-2 sm:h-2.5 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 sm:gap-6">
                    
                    <!-- Peta Lokasi Kejadian (Heatmap List) -->
                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-3.5 sm:p-6 shadow-xs md:col-span-1">
                        <h4 class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-3 sm:mb-4 border-b border-gray-100 dark:border-zinc-800 pb-2 flex items-center gap-1.5">
                            <x-heroicon-o-map-pin class="w-4 h-4 text-indigo-500 shrink-0" />
                            <span>Lokasi Kejadian Terbanyak</span>
                        </h4>
                        @if($violationsByLocation->isEmpty())
                            <p class="text-xs text-gray-500 dark:text-zinc-500 text-center py-4 sm:py-6">Belum ada lokasi tercatat.</p>
                        @else
                            <div class="space-y-2.5 sm:space-y-3">
                                @foreach($violationsByLocation as $loc)
                                    <div class="flex justify-between items-center text-[11px] sm:text-xs font-semibold">
                                        <span class="text-gray-700 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-800 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg">
                                            {{ $loc->location }}
                                        </span>
                                        <span class="text-rose-600 dark:text-rose-400 font-bold">{{ $loc->count }} kali ({{ $loc->points }} Poin)</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Top 5 Murid Pelanggaran Terbanyak -->
                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-3.5 sm:p-6 shadow-xs md:col-span-1">
                        <h4 class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-3 sm:mb-4 border-b border-gray-100 dark:border-zinc-800 pb-2 flex items-center gap-1.5">
                            <x-heroicon-o-shield-exclamation class="w-4 h-4 text-rose-500 shrink-0" />
                            <span>Pelanggar Terbanyak</span>
                        </h4>
                        @if($topViolators->isEmpty())
                            <p class="text-xs text-gray-500 dark:text-zinc-500 text-center py-4 sm:py-6">Belum ada data pelanggaran.</p>
                        @else
                            <div class="space-y-2 sm:space-y-3">
                                @foreach($topViolators as $student)
                                    <div class="flex justify-between items-center text-[11px] sm:text-xs">
                                        <span class="font-semibold text-gray-800 dark:text-zinc-200 truncate">{{ $student->name }}</span>
                                        <span class="text-red-600 dark:text-rose-500 font-bold bg-red-50 dark:bg-red-950/20 px-2 py-0.5 rounded shrink-0">{{ $student->total_points }} Poin</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Top 5 Murid Berprestasi Terbanyak -->
                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-3.5 sm:p-6 shadow-xs md:col-span-1">
                        <h4 class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-3 sm:mb-4 border-b border-gray-100 dark:border-zinc-800 pb-2 flex items-center gap-1.5">
                            <x-heroicon-o-sparkles class="w-4 h-4 text-emerald-500 shrink-0" />
                            <span>Prestasi Tertinggi</span>
                        </h4>
                        @if($topAchievers->isEmpty())
                            <p class="text-xs text-gray-500 dark:text-zinc-500 text-center py-4 sm:py-6">Belum ada data prestasi.</p>
                        @else
                            <div class="space-y-2 sm:space-y-3">
                                @foreach($topAchievers as $student)
                                    <div class="flex justify-between items-center text-[11px] sm:text-xs">
                                        <span class="font-semibold text-gray-800 dark:text-zinc-200 truncate">{{ $student->name }}</span>
                                        <span class="text-green-600 dark:text-emerald-500 font-bold bg-green-50 dark:bg-green-950/20 px-2 py-0.5 rounded shrink-0">+{{ $student->total_points }} Poin</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
