<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                    Jadwal Pelajaran Tahfizh Kelas
                </h2>
                <p class="text-sm text-gray-600 dark:text-zinc-400">
                    Atur hari aktif Tahfizh tiap kelas: jadwal default berlaku setiap pekan, jadwal per pekan untuk pekan-pekan dengan jadwal khusus.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ activeDay: '{{ array_key_first($daysOfWeek) }}', tab: '{{ $activeTab }}' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Success Alert Notification -->
            @if (session('success'))
                <div class="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/30 rounded-xl p-4 flex items-center gap-3">
                    <x-heroicon-o-check-circle class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                    <span class="text-sm text-emerald-800 dark:text-emerald-300 font-semibold">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Tab: Jadwal Default / Jadwal Per Pekan -->
            <div class="flex gap-1 border-b border-gray-200 dark:border-zinc-800">
                <button type="button" @click="tab = 'default'" :class="tab === 'default' ? 'border-teal-600 text-teal-700 dark:text-teal-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-zinc-300'" class="px-4 py-2.5 text-sm font-bold border-b-2 transition cursor-pointer">Jadwal Default</button>
                <button type="button" @click="tab = 'weekly'" :class="tab === 'weekly' ? 'border-teal-600 text-teal-700 dark:text-teal-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-zinc-300'" class="px-4 py-2.5 text-sm font-bold border-b-2 transition cursor-pointer">Jadwal Per Pekan</button>
            </div>

            <div x-show="tab === 'default'" class="space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/40 rounded-xl p-3">
                <p class="text-xs text-amber-800 dark:text-amber-300">
                    Jadwal default berlaku untuk pekan berjalan dan seterusnya. Pekan yang sudah lewat tetap memakai jadwal lamanya (otomatis terkunci).
                </p>
                <button type="submit" form="schedules-form" class="shrink-0 inline-flex items-center justify-center gap-2 rounded-xl bg-teal-600 hover:bg-teal-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition cursor-pointer">
                    <x-heroicon-o-arrow-down-on-square class="w-4 h-4" /> Simpan Jadwal Default
                </button>
            </div>

            <!-- Day Selector Tabs (Toggles) -->
            <div class="flex flex-wrap gap-2 p-1.5 bg-gray-100 dark:bg-zinc-800 rounded-2xl border border-gray-255 dark:border-zinc-700 max-w-4xl shadow-xs">
                @foreach ($daysOfWeek as $dayNum => $dayName)
                    <button type="button" 
                            @click="activeDay = '{{ $dayNum }}'" 
                            :class="activeDay === '{{ $dayNum }}' ? 'bg-teal-600 text-white shadow-xs' : 'text-gray-600 dark:text-zinc-400 hover:text-gray-900 hover:bg-gray-200/50 dark:hover:bg-zinc-700/50'"
                            class="flex-1 py-2 px-4 rounded-xl text-xs font-extrabold uppercase tracking-wider transition duration-150 cursor-pointer text-center select-none">
                        {{ $dayName }}
                    </button>
                @endforeach
            </div>

            <!-- Main Schedules Form -->
            <form id="schedules-form" method="POST" action="{{ route('class-schedules.update') }}">
                @csrf

                <!-- Day Cards Stack -->
                <div>
                    @foreach ($daysOfWeek as $dayNum => $dayName)
                        @php
                            $dayData = $scheduleBoard[$dayNum];
                            $activeClassesCount = count($dayData['classRooms']);
                            
                            $kelas10 = $classRooms->filter(fn($c) => preg_match('/^X\b/i', $c->name));
                            $kelas11 = $classRooms->filter(fn($c) => preg_match('/^XI\b/i', $c->name));
                            $kelas12 = $classRooms->filter(fn($c) => preg_match('/^XII\b/i', $c->name));
                        @endphp
                        
                        <div x-show="activeDay === '{{ $dayNum }}'" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform translate-y-1"
                             x-transition:enter-end="opacity-100 transform translate-y-0"
                             class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-3xl p-6 shadow-xs space-y-5">
                            
                            <!-- Day Header -->
                            <div class="flex justify-between items-center border-b dark:border-zinc-800 pb-3">
                                <h3 class="font-extrabold text-base text-gray-800 dark:text-zinc-200 uppercase tracking-wider flex items-center gap-2">
                                    <x-heroicon-o-calendar class="w-5 h-5 text-indigo-600 dark:text-indigo-400" /> {{ $dayName }}
                                </h3>
                                <span class="bg-indigo-100 dark:bg-indigo-950/50 text-indigo-750 dark:text-indigo-405 text-xs px-3 py-1 rounded-full font-extrabold">
                                    {{ $activeClassesCount }} Kelas Aktif
                                </span>
                            </div>

                            <!-- Group Rows by Grade -->
                            <div class="space-y-5 divide-y divide-gray-100 dark:divide-zinc-800/60">
                                
                                <!-- Kelas 10 Row -->
                                @if ($kelas10->isNotEmpty())
                                    <div class="flex flex-col md:flex-row md:items-center gap-4 pt-4 first:pt-0">
                                        <div class="md:w-28 shrink-0">
                                            <span class="bg-blue-50 dark:bg-blue-950/20 text-blue-700 dark:text-blue-400 text-xs px-3 py-1.5 rounded-xl font-bold uppercase tracking-wider block text-center md:text-left">
                                                Kelas 10
                                            </span>
                                        </div>
                                        <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-zinc-800 w-full">
                                            @foreach ($kelas10 as $class)
                                                @php
                                                    $isActive = in_array($dayNum, $class->tahfizh_days, true);
                                                @endphp
                                                @include('class-rooms.partials.schedule-card', ['class' => $class, 'dayNum' => $dayNum, 'isActive' => $isActive])
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Kelas 11 Row -->
                                @if ($kelas11->isNotEmpty())
                                    <div class="flex flex-col md:flex-row md:items-center gap-4 pt-4">
                                        <div class="md:w-28 shrink-0">
                                            <span class="bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 text-xs px-3 py-1.5 rounded-xl font-bold uppercase tracking-wider block text-center md:text-left">
                                                Kelas 11
                                            </span>
                                        </div>
                                        <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-zinc-800 w-full">
                                            @foreach ($kelas11 as $class)
                                                @php
                                                    $isActive = in_array($dayNum, $class->tahfizh_days, true);
                                                @endphp
                                                @include('class-rooms.partials.schedule-card', ['class' => $class, 'dayNum' => $dayNum, 'isActive' => $isActive])
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Kelas 12 Row -->
                                @if ($kelas12->isNotEmpty())
                                    <div class="flex flex-col md:flex-row md:items-center gap-4 pt-4">
                                        <div class="md:w-28 shrink-0">
                                            <span class="bg-purple-50 dark:bg-purple-950/20 text-purple-700 dark:text-purple-400 text-xs px-3 py-1.5 rounded-xl font-bold uppercase tracking-wider block text-center md:text-left">
                                                Kelas 12
                                            </span>
                                        </div>
                                        <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-zinc-800 w-full">
                                            @foreach ($kelas12 as $class)
                                                @php
                                                    $isActive = in_array($dayNum, $class->tahfizh_days, true);
                                                @endphp
                                                @include('class-rooms.partials.schedule-card', ['class' => $class, 'dayNum' => $dayNum, 'isActive' => $isActive])
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                            </div>
                        </div>
                    @endforeach
                </div>
            </form>
            </div>

            <!-- ===================== JADWAL PER PEKAN ===================== -->
            @php
                $weekDays = collect(range(0, 6))->map(fn ($i) => $weekStart->copy()->addDays($i));
                $dayShort = [1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum', 6 => 'Sab', 7 => 'Min'];
                $weekRoute = fn ($date) => route('class-schedules.index', ['tab' => 'weekly', 'week' => $date->toDateString()]);
                $anyEditable = collect($weekStates)->contains(fn ($st) => ! $st['locked']);
                $anyLocked = collect($weekStates)->contains(fn ($st) => $st['locked']);
            @endphp
            <div x-show="tab === 'weekly'" class="space-y-4" style="{{ $activeTab === 'weekly' ? '' : 'display: none;' }}">
                <!-- Navigasi pekan -->
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-4 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <a href="{{ $weekRoute(today()) }}" class="px-3 py-2 border border-gray-300 dark:border-zinc-700 text-gray-700 dark:text-zinc-200 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-xl text-xs font-bold transition">Pekan Ini</a>
                        <div class="flex items-center bg-gray-100 dark:bg-zinc-800/80 rounded-xl p-0.5 border border-gray-200 dark:border-zinc-700/60">
                            <a href="{{ $weekRoute($weekStart->copy()->subWeek()) }}" title="Pekan sebelumnya" class="w-8 h-8 flex items-center justify-center text-gray-700 dark:text-zinc-300 hover:bg-white dark:hover:bg-zinc-700 rounded-lg font-bold transition">‹</a>
                            <a href="{{ $weekRoute($weekStart->copy()->addWeek()) }}" title="Pekan berikutnya" class="w-8 h-8 flex items-center justify-center text-gray-700 dark:text-zinc-300 hover:bg-white dark:hover:bg-zinc-700 rounded-lg font-bold transition">›</a>
                        </div>
                        <h3 class="text-base font-black text-gray-900 dark:text-white ml-1">
                            {{ $weekStart->format('d M') }} &ndash; {{ $weekStart->copy()->addDays(6)->format('d M Y') }}
                        </h3>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($anyEditable)
                            <button type="submit" form="week-lock-all" name="action" value="lock" onclick="return confirm('Kunci jadwal pekan ini untuk semua kelas?')" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white shadow-sm transition cursor-pointer">
                                <x-heroicon-o-lock-closed class="w-4 h-4" /> Kunci Pekan Ini
                            </button>
                        @endif
                        @if ($anyLocked)
                            <button type="submit" form="week-lock-all" name="action" value="unlock" onclick="return confirm('Buka kunci jadwal pekan ini untuk semua kelas?')" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold border border-gray-300 dark:border-zinc-700 text-gray-700 dark:text-zinc-200 hover:bg-gray-100 dark:hover:bg-zinc-800 transition cursor-pointer">
                                <x-heroicon-o-lock-open class="w-4 h-4" /> Buka Kunci Semua
                            </button>
                        @endif
                        @if ($anyEditable)
                            <button type="submit" form="week-form" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold bg-teal-600 hover:bg-teal-700 text-white shadow-sm transition cursor-pointer">
                                <x-heroicon-o-arrow-down-on-square class="w-4 h-4" /> Simpan Jadwal Pekan Ini
                            </button>
                        @endif
                    </div>
                </div>

                <p class="text-xs text-gray-500 dark:text-zinc-400 px-1">
                    Centang hari pertemuan Tahfizh tiap kelas untuk pekan ini. Kosongkan semua hari bila kelas tidak ada pertemuan (mis. pekan ASTS). Pekan yang sudah lewat otomatis terkunci.
                </p>

                <form id="week-lock-all" method="POST" action="{{ route('class-schedules.week.lock') }}">
                    @csrf
                    <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                </form>
                @foreach ($classRooms as $class)
                    <form id="week-lock-{{ $class->id }}" method="POST" action="{{ route('class-schedules.week.lock') }}">
                        @csrf
                        <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">
                        <input type="hidden" name="class_room_id" value="{{ $class->id }}">
                    </form>
                @endforeach

                <form id="week-form" method="POST" action="{{ route('class-schedules.week.update') }}">
                    @csrf
                    <input type="hidden" name="week" value="{{ $weekStart->toDateString() }}">

                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl shadow-xs overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-zinc-850/60 text-[11px] font-black uppercase tracking-wider text-gray-500 dark:text-zinc-400">
                                <tr>
                                    <th class="px-4 py-3 text-left">Kelas</th>
                                    @foreach ($weekDays as $date)
                                        <th class="px-2 py-3 text-center {{ $date->dayOfWeekIso >= 6 ? 'text-rose-500' : '' }}">
                                            {{ $dayShort[$date->dayOfWeekIso] }}<span class="block font-semibold normal-case text-[10px]">{{ $date->format('d/m') }}</span>
                                        </th>
                                    @endforeach
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                                @foreach ($classRooms as $class)
                                    @php $st = $weekStates[$class->id]; @endphp
                                    <tr class="{{ $st['is_custom'] ? 'bg-sky-50/60 dark:bg-sky-950/20' : '' }}">
                                        <td class="px-4 py-2.5">
                                            <span class="font-bold text-gray-900 dark:text-white">{{ $class->name }}</span>
                                            <span class="block text-[10px] text-gray-400 uppercase">{{ $class->program?->name }}</span>
                                        </td>
                                        @foreach ($weekDays as $date)
                                            <td class="px-2 py-2.5 text-center">
                                                <input type="checkbox" name="schedules[{{ $class->id }}][]" value="{{ $date->dayOfWeekIso }}"
                                                       @checked(in_array($date->dayOfWeekIso, $st['days'], true))
                                                       @disabled($st['locked'])
                                                       class="rounded border-gray-300 text-teal-600 focus:ring-teal-500 disabled:opacity-50 cursor-pointer disabled:cursor-not-allowed">
                                            </td>
                                        @endforeach
                                        <td class="px-4 py-2.5 whitespace-nowrap">
                                            <span class="inline-flex items-center gap-1 text-[11px] font-bold">
                                                @if ($st['is_custom'])
                                                    <span class="px-2 py-0.5 rounded-full bg-sky-100 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300">Khusus</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 dark:bg-zinc-800 dark:text-zinc-300">Default</span>
                                                @endif
                                                @if ($st['locked'])
                                                    <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 inline-flex items-center gap-1">
                                                        <x-heroicon-o-lock-closed class="w-3 h-3" /> {{ $st['auto_locked'] ? 'Terkunci (sudah lewat)' : 'Terkunci' }}
                                                    </span>
                                                @elseif ($st['unlocked'])
                                                    <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">Kunci dibuka</span>
                                                @endif
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                            <button type="submit" form="week-lock-{{ $class->id }}" name="action" value="{{ $st['locked'] ? 'unlock' : 'lock' }}"
                                                    class="text-[11px] font-bold {{ $st['locked'] ? 'text-gray-600 hover:text-gray-900 dark:text-zinc-400' : 'text-amber-700 hover:text-amber-900 dark:text-amber-400' }} cursor-pointer">
                                                {{ $st['locked'] ? 'Buka' : 'Kunci' }}
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>
