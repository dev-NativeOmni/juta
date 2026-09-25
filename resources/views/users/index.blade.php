<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
                {{ __('Manajemen Akun User') }}
            </h2>
            <a
                href="{{ route('users.create') }}"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-755 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest transition duration-150"
            >
                Tambah User
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-4 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-emerald-800 dark:text-emerald-300 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900 rounded-lg text-rose-800 dark:text-rose-300 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Deskripsi Panel -->
            <div class="bg-gradient-to-r from-violet-600 via-indigo-600 to-indigo-700 text-white rounded-xl shadow-lg p-6 relative overflow-hidden">
                <div class="absolute right-0 bottom-0 opacity-10 transform translate-x-12 translate-y-12">
                    <svg class="h-64 w-64" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
                <div class="relative z-10 max-w-2xl">
                    <h3 class="text-xl font-bold mb-2">Panel Kontrol Akun Super Admin</h3>
                    <p class="text-violet-100 text-sm leading-relaxed">
                        Manajemen kredensial seluruh pengguna TAD. Anda dapat melihat username, mencatat/memperbarui password dalam teks biasa, serta mengontrol peran dan status aktifasi seluruh akun dari halaman monitoring ini.
                    </p>
                </div>
            </div>

            @php
                $getRoleIconName = function($roleName) {
                    return match($roleName) {
                        'student' => 'academic-cap',
                        'teacher' => 'user-group',
                        'parent' => 'heart',
                        'coordinator_tahfizh' => 'book-open',
                        'tanse' => 'shield-check',
                        'super_admin', 'admin' => 'key',
                        'headmaster' => 'building-library',
                        'supervisor' => 'eye',
                        'pendamping_adab' => 'sparkles',
                        default => 'user'
                    };
                };

                $hasActiveFilters = request('role_id') || request('status') || request('search') || request('class_room_id');
            @endphp

            <!-- CARD TOGGLE FILTERS (COMPACT) -->
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider flex items-center gap-2">
                        <x-heroicon-o-funnel class="w-4 h-4 text-indigo-600 dark:text-indigo-400" /> Card Toggle Filter Peran &amp; Status Akun
                        @if($hasActiveFilters)
                            <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50 px-2 py-0.5 rounded-full border border-indigo-200 dark:border-indigo-800">
                                Filter Aktif
                            </span>
                        @endif
                    </h3>
                    @if($hasActiveFilters)
                        <a href="{{ route('users.index') }}" class="text-xs font-bold text-rose-600 dark:text-rose-400 hover:underline flex items-center gap-1">
                            <x-heroicon-o-x-mark class="w-3.5 h-3.5" /> Reset Filter
                        </a>
                    @endif
                </div>

                <div class="flex flex-wrap gap-2">
                    <!-- Card 1: Semua User -->
                    @php
                        $isAllActive = !request('role_id') && !request('status');
                        $allUrl = route('users.index', array_filter(request()->only(['search', 'class_room_id'])));
                    @endphp
                    <a href="{{ $allUrl }}"
                       class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 border {{ $isAllActive ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm ring-2 ring-indigo-500/40' : 'bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-200 border-zinc-200 dark:border-zinc-800 hover:border-indigo-300 dark:hover:border-indigo-700 hover:bg-zinc-50 dark:hover:bg-zinc-800/60' }}">
                        <x-heroicon-o-users class="w-4 h-4 shrink-0" />
                        <span>Semua User</span>
                        <span class="px-1.5 py-0.5 rounded-md text-[10px] font-extrabold {{ $isAllActive ? 'bg-white/20 text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400' }}">
                            {{ $totalUsers }}
                        </span>
                    </a>

                    <!-- Loop Roles as Compact Card Toggles -->
                    @foreach($roles as $role)
                        @php
                            $isRoleActive = (string) request('role_id') === (string) $role->id;
                            $count = $roleCounts[$role->id] ?? 0;
                            $iconName = $getRoleIconName($role->name);
                            $roleUrl = $isRoleActive
                                ? route('users.index', array_filter(request()->only(['search', 'class_room_id'])))
                                : route('users.index', array_merge(array_filter(request()->only(['search', 'class_room_id'])), ['role_id' => $role->id]));
                        @endphp
                        <a href="{{ $roleUrl }}"
                           class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 border {{ $isRoleActive ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm ring-2 ring-indigo-500/40' : 'bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-200 border-zinc-200 dark:border-zinc-800 hover:border-indigo-300 dark:hover:border-indigo-700 hover:bg-zinc-50 dark:hover:bg-zinc-800/60' }}">
                            <x-dynamic-component :component="'heroicon-o-' . $iconName" class="w-4 h-4 shrink-0" />
                            <span>{{ $role->display_name }}</span>
                            <span class="px-1.5 py-0.5 rounded-md text-[10px] font-extrabold {{ $isRoleActive ? 'bg-white/20 text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400' }}">
                                {{ $count }}
                            </span>
                        </a>
                    @endforeach

                    <!-- Card Non-Aktif -->
                    @php
                        $isInactiveActive = request('status') === 'inactive';
                        $inactiveUrl = $isInactiveActive
                            ? route('users.index', array_filter(request()->only(['search', 'class_room_id'])))
                            : route('users.index', array_merge(array_filter(request()->only(['search', 'class_room_id'])), ['status' => 'inactive']));
                    @endphp
                    <a href="{{ $inactiveUrl }}"
                       class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 border {{ $isInactiveActive ? 'bg-rose-600 text-white border-rose-600 shadow-sm ring-2 ring-rose-500/40' : 'bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-200 border-zinc-200 dark:border-zinc-800 hover:border-rose-300 dark:hover:border-rose-700 hover:bg-zinc-50 dark:hover:bg-zinc-800/60' }}">
                        <x-heroicon-o-no-symbol class="w-4 h-4 shrink-0" />
                        <span>Non-Aktif</span>
                        <span class="px-1.5 py-0.5 rounded-md text-[10px] font-extrabold {{ $isInactiveActive ? 'bg-white/20 text-white' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400' }}">
                            {{ $inactiveUsers }}
                        </span>
                    </a>
                </div>
            </div>

            <!-- Filter & Pencarian Form -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl sm:rounded-2xl p-4 sm:p-6">
                <form method="GET" action="{{ route('users.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
                    <div>
                        <label for="search" class="block text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-2">Cari User</label>
                        <input
                            type="text"
                            name="search"
                            id="search"
                            value="{{ request('search') }}"
                            placeholder="Cari nama atau username..."
                            x-on:input.debounce.600ms="$el.form.submit()"
                            class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white placeholder-zinc-400"
                        >
                    </div>

                    <div>
                        <label for="role_id" class="block text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-2">Peran (Role)</label>
                        <select name="role_id" id="role_id" onchange="this.form.submit()" class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white">
                            <option value="" class="dark:bg-zinc-900">Semua Peran</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @selected((string) request('role_id') === (string) $role->id) class="dark:bg-zinc-900">
                                    {{ $role->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="class_room_id" class="block text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-2">Kelas (Murid)</label>
                        <select name="class_room_id" id="class_room_id" onchange="this.form.submit()" class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white">
                            <option value="" class="dark:bg-zinc-900">Semua Kelas</option>
                            @foreach ($classRooms as $cRoom)
                                <option value="{{ $cRoom->id }}" @selected((string) request('class_room_id') === (string) $cRoom->id) class="dark:bg-zinc-900">
                                    {{ $cRoom->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-2">Status Akun</label>
                        <select name="status" id="status" onchange="this.form.submit()" class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white">
                            <option value="" class="dark:bg-zinc-900">Semua Status</option>
                            <option value="active" @selected(request('status') === 'active') class="dark:bg-zinc-900">Aktif</option>
                            <option value="inactive" @selected(request('status') === 'inactive') class="dark:bg-zinc-900">Nonaktif</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2 col-span-1 sm:col-span-2 lg:col-span-1">
                        <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white rounded-lg text-sm font-semibold transition duration-150 shadow-sm min-h-[42px]">
                            Cari
                        </button>

                        <a href="{{ route('users.index') }}" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 active:scale-95 text-zinc-700 dark:text-zinc-300 rounded-lg text-sm font-semibold transition duration-150 min-h-[42px]">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- List Users Table -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl sm:rounded-2xl overflow-hidden">
                <div class="overflow-x-auto touch-scroll">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                            <tr class="text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <th class="px-6 py-4">Nama Lengkap</th>
                                <th class="px-6 py-4">Username</th>
                                <th class="px-6 py-4">Peran (Role)</th>
                                <th class="px-6 py-4">Password (Teks Biasa)</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($users as $u)
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-white/[0.01] transition duration-150" x-data="{ showPass: false, openLinkModal: false, modalSearch: '' }">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-zinc-900 dark:text-white text-sm">
                                            {{ $u->name }}
                                        </div>

                                        {{-- Relasi Murid -> Orang Tua --}}
                                        @if ($u->studentProfile)
                                            <div class="mt-1.5 space-y-1">
                                                @if ($u->studentProfile->classRoom)
                                                    <div>
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-900/50">
                                                            <x-heroicon-o-academic-cap class="w-3.5 h-3.5 text-indigo-500" /> Kelas {{ $u->studentProfile->classRoom->name }}
                                                        </span>
                                                    </div>
                                                @endif

                                                @if ($u->studentProfile->parents->isNotEmpty())
                                                    @foreach ($u->studentProfile->parents as $pProfile)
                                                        <div class="text-[11px] font-medium text-zinc-600 dark:text-zinc-400 flex items-center gap-1 flex-wrap">
                                                            <x-heroicon-o-user-group class="w-3.5 h-3.5 text-zinc-400" />
                                                            <span class="text-zinc-500 dark:text-zinc-400">Ortu:</span>
                                                            <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ $pProfile->user?->name ?? 'Orang Tua' }}</span>
                                                            <span class="font-mono text-[10px] text-zinc-400 dark:text-zinc-500">({{ '@' . ($pProfile->user?->username ?? '-') }})</span>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <div class="text-[11px] font-medium text-amber-600 dark:text-amber-400 flex items-center gap-1">
                                                        <x-heroicon-o-exclamation-triangle class="w-3.5 h-3.5" /> Belum Terhubung Orang Tua
                                                    </div>
                                                @endif

                                                <div>
                                                    <button type="button" @click="openLinkModal = true" class="mt-0.5 inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:hover:bg-indigo-900 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800 transition">
                                                        <x-heroicon-o-link class="w-3 h-3" /> <span>Edit Relasi Ortu</span>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- MODAL HUBUNGKAN ORANG TUA FOR MURID -->
                                            <template x-teleport="body">
                                                <div x-show="openLinkModal"
                                                     x-transition:enter="transition ease-out duration-200"
                                                     x-transition:enter-start="opacity-0"
                                                     x-transition:enter-end="opacity-100"
                                                     x-transition:leave="transition ease-in duration-150"
                                                     x-transition:leave-start="opacity-100"
                                                     x-transition:leave-end="opacity-0"
                                                     class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-zinc-900/60 backdrop-blur-sm"
                                                     style="display: none;">
                                                    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-xl max-w-lg w-full p-6 border border-zinc-200 dark:border-zinc-800 text-left space-y-4"
                                                         @click.away="openLinkModal = false">
                                                        <div class="flex items-center justify-between border-b pb-3 dark:border-zinc-800">
                                                            <h3 class="text-base font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                                                <x-heroicon-o-user-plus class="w-5 h-5 text-indigo-600" />
                                                                <span>Hubungkan Orang Tua</span>
                                                            </h3>
                                                            <button type="button" @click="openLinkModal = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1"><x-heroicon-o-x-mark class="w-5 h-5" /></button>
                                                        </div>

                                                        <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                                            Pilih akun Orang Tua yang terhubung dengan murid <strong class="text-zinc-900 dark:text-white">{{ $u->name }}</strong>:
                                                        </p>

                                                        <form method="POST" action="{{ route('users.link-parents', $u->id) }}" class="space-y-4">
                                                            @csrf
                                                            @php
                                                                $currentParentProfileIds = $u->studentProfile->parents->pluck('id')->toArray();
                                                            @endphp

                                                            <div>
                                                                <input type="text"
                                                                       x-model="modalSearch"
                                                                       placeholder="Cari nama atau username orang tua..."
                                                                       class="w-full text-xs rounded-xl border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 dark:text-white px-3 py-2">
                                                            </div>

                                                            <div class="max-h-60 overflow-y-auto space-y-1.5 pr-1 border rounded-xl p-3 border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                                                                @forelse($allParentProfiles as $pProf)
                                                                    @php
                                                                        $pName = $pProf->user?->name ?? 'Orang Tua (Tanpa User)';
                                                                        $pUsername = $pProf->user?->username ?? '-';
                                                                    @endphp
                                                                    <label x-show="modalSearch === '' || '{{ strtolower($pName) }}'.includes(modalSearch.toLowerCase()) || '{{ strtolower($pUsername) }}'.includes(modalSearch.toLowerCase())"
                                                                           class="flex items-center justify-between p-2 rounded-lg hover:bg-white dark:hover:bg-zinc-800 border border-transparent hover:border-zinc-200 dark:hover:border-zinc-700 cursor-pointer transition text-xs">
                                                                        <div class="flex items-center gap-2.5">
                                                                            <input type="checkbox"
                                                                                   name="parent_ids[]"
                                                                                   value="{{ $pProf->id }}"
                                                                                   @checked(in_array($pProf->id, $currentParentProfileIds))
                                                                                   class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                                                                            <div>
                                                                                <span class="font-bold text-zinc-900 dark:text-white block">{{ $pName }}</span>
                                                                                <span class="font-mono text-[10px] text-zinc-500">({{ '@' . $pUsername }})</span>
                                                                            </div>
                                                                        </div>
                                                                    </label>
                                                                @empty
                                                                    <p class="text-xs text-zinc-400 text-center py-4">Belum ada data akun Orang Tua.</p>
                                                                @endforelse
                                                            </div>

                                                            <div class="flex items-center justify-end gap-2 pt-2 border-t dark:border-zinc-800">
                                                                <button type="button" @click="openLinkModal = false" class="px-4 py-2 bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-xs font-semibold rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition">
                                                                    Batal
                                                                </button>
                                                                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-xl hover:bg-indigo-700 shadow-sm transition">
                                                                    Simpan Relasi
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </template>
                                        @endif

                                        {{-- Relasi Orang Tua -> Murid --}}
                                        @if ($u->parentProfile)
                                            <div class="mt-1.5 space-y-1">
                                                @if ($u->parentProfile->students->isNotEmpty())
                                                    @foreach ($u->parentProfile->students as $cStudent)
                                                        <div class="text-[11px] font-medium text-zinc-600 dark:text-zinc-400 flex items-center gap-1 flex-wrap">
                                                            <x-heroicon-o-academic-cap class="w-3.5 h-3.5 text-zinc-400" />
                                                            <span class="text-zinc-500 dark:text-zinc-400">Anak:</span>
                                                            <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ $cStudent->name }}</span>
                                                            @if($cStudent->classRoom)
                                                                <span class="px-1.5 py-0.2 rounded text-[10px] bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 font-bold">
                                                                    {{ $cStudent->classRoom->name }}
                                                                </span>
                                                            @endif
                                                            <span class="font-mono text-[10px] text-zinc-400 dark:text-zinc-500">({{ '@' . ($cStudent->user?->username ?? '-') }})</span>
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <div class="text-[11px] font-medium text-amber-600 dark:text-amber-400 flex items-center gap-1">
                                                        <x-heroicon-o-exclamation-triangle class="w-3.5 h-3.5" /> Belum Terhubung Murid
                                                    </div>
                                                @endif

                                                <div>
                                                    <button type="button" @click="openLinkModal = true" class="mt-0.5 inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:hover:bg-indigo-900 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800 transition">
                                                        <x-heroicon-o-link class="w-3 h-3" /> <span>Edit Relasi Murid</span>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- MODAL HUBUNGKAN ANAK/MURID FOR PARENT -->
                                            <template x-teleport="body">
                                                <div x-show="openLinkModal"
                                                     x-transition:enter="transition ease-out duration-200"
                                                     x-transition:enter-start="opacity-0"
                                                     x-transition:enter-end="opacity-100"
                                                     x-transition:leave="transition ease-in duration-150"
                                                     x-transition:leave-start="opacity-100"
                                                     x-transition:leave-end="opacity-0"
                                                     class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-zinc-900/60 backdrop-blur-sm"
                                                     style="display: none;">
                                                    <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-xl max-w-lg w-full p-6 border border-zinc-200 dark:border-zinc-800 text-left space-y-4"
                                                         @click.away="openLinkModal = false">
                                                        <div class="flex items-center justify-between border-b pb-3 dark:border-zinc-800">
                                                            <h3 class="text-base font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                                                <x-heroicon-o-user-plus class="w-5 h-5 text-indigo-600" />
                                                                <span>Hubungkan Anak/Murid</span>
                                                            </h3>
                                                            <button type="button" @click="openLinkModal = false" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1"><x-heroicon-o-x-mark class="w-5 h-5" /></button>
                                                        </div>

                                                        <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                                            Pilih murid/anak yang terhubung dengan orang tua <strong class="text-zinc-900 dark:text-white">{{ $u->name }}</strong>:
                                                        </p>

                                                        <form method="POST" action="{{ route('users.link-students', $u->id) }}" class="space-y-4">
                                                            @csrf
                                                            @php
                                                                $currentStudentIds = $u->parentProfile->students->pluck('id')->toArray();
                                                            @endphp

                                                            <div>
                                                                <input type="text"
                                                                       x-model="modalSearch"
                                                                       placeholder="Cari nama murid, kelas, atau username..."
                                                                       class="w-full text-xs rounded-xl border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 dark:text-white px-3 py-2">
                                                            </div>

                                                            <div class="max-h-60 overflow-y-auto space-y-1.5 pr-1 border rounded-xl p-3 border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                                                                @forelse($allStudentProfiles as $sProf)
                                                                    @php
                                                                        $sName = $sProf->name;
                                                                        $sClassName = $sProf->classRoom?->name ?? 'Tanpa Kelas';
                                                                        $sUsername = $sProf->user?->username ?? '-';
                                                                    @endphp
                                                                    <label x-show="modalSearch === '' || '{{ strtolower($sName) }}'.includes(modalSearch.toLowerCase()) || '{{ strtolower($sClassName) }}'.includes(modalSearch.toLowerCase()) || '{{ strtolower($sUsername) }}'.includes(modalSearch.toLowerCase())"
                                                                           class="flex items-center justify-between p-2 rounded-lg hover:bg-white dark:hover:bg-zinc-800 border border-transparent hover:border-zinc-200 dark:hover:border-zinc-700 cursor-pointer transition text-xs">
                                                                        <div class="flex items-center gap-2.5">
                                                                            <input type="checkbox"
                                                                                   name="student_ids[]"
                                                                                   value="{{ $sProf->id }}"
                                                                                   @checked(in_array($sProf->id, $currentStudentIds))
                                                                                   class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                                                                            <div>
                                                                                <div class="flex items-center gap-1.5">
                                                                                    <span class="font-bold text-zinc-900 dark:text-white">{{ $sName }}</span>
                                                                                    <span class="px-1.5 py-0.2 rounded text-[10px] bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-bold border border-indigo-100 dark:border-indigo-900">
                                                                                        {{ $sClassName }}
                                                                                    </span>
                                                                                </div>
                                                                                <span class="font-mono text-[10px] text-zinc-500">({{ '@' . $sUsername }})</span>
                                                                            </div>
                                                                        </div>
                                                                    </label>
                                                                @empty
                                                                    <p class="text-xs text-zinc-400 text-center py-4">Belum ada data Murid.</p>
                                                                @endforelse
                                                            </div>

                                                            <div class="flex items-center justify-end gap-2 pt-2 border-t dark:border-zinc-800">
                                                                <button type="button" @click="openLinkModal = false" class="px-4 py-2 bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 text-xs font-semibold rounded-xl hover:bg-zinc-200 dark:hover:bg-zinc-700 transition">
                                                                    Batal
                                                                </button>
                                                                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-xl hover:bg-indigo-700 shadow-sm transition">
                                                                    Simpan Relasi
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </template>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 font-mono text-xs text-zinc-600 dark:text-zinc-400">
                                        {{ $u->username }}
                                    </td>

                                    <td class="px-6 py-4">
                                        @php
                                            $getRoleBadge = function($rName, $dName, $isPrimary = true) {
                                                $cls = match($rName) {
                                                    'super_admin' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 border border-purple-200/60 dark:border-purple-800',
                                                    'admin' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800',
                                                    'coordinator_tahfizh' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800',
                                                    'pendamping_adab', 'supervisor' => 'bg-teal-50 text-teal-700 dark:bg-teal-950/40 dark:text-teal-300 border border-teal-200/60 dark:border-teal-800',
                                                    'teacher' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200/60 dark:border-amber-800',
                                                    'student' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800',
                                                    'tanse' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border border-rose-200/60 dark:border-rose-800',
                                                    default => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200 border border-zinc-200 dark:border-zinc-700'
                                                };
                                                return '<span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold ' . $cls . '">' . e($dName) . '</span>';
                                            };
                                            $allAssigned = $u->assignedRoles();
                                        @endphp
                                        <div class="flex flex-wrap gap-1 items-center">
                                            @if ($u->role)
                                                {!! $getRoleBadge($u->role->name, $u->role->display_name, true) !!}
                                            @endif
                                            @foreach ($allAssigned->where('id', '!=', $u->role_id) as $extraRole)
                                                {!! $getRoleBadge($extraRole->name, '+ ' . $extraRole->display_name, false) !!}
                                            @endforeach
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-sm tracking-wide bg-zinc-50 dark:bg-zinc-800 px-2 py-1 rounded border dark:border-zinc-800 text-zinc-800 dark:text-zinc-200 select-all" x-text="showPass ? '{{ $u->plain_password ?: '(Belum Tersimpan)' }}' : '••••••••'"></span>
                                            <button @click="showPass = !showPass" type="button" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 focus:outline-none">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        @if ($u->isActive())
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30">
                                                Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-bold bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                                Nonaktif
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2 flex-wrap">
                                            @if (auth()->id() !== $u->id)
                                                <form action="{{ route('impersonate.start', $u) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" title="Masuk & Uji Sistem sebagai User ini" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-amber-800 bg-amber-100 hover:bg-amber-200 dark:bg-amber-950/60 dark:hover:bg-amber-900 dark:text-amber-300 border border-amber-300 dark:border-amber-800 rounded-md transition duration-150 cursor-pointer">
                                                        <x-heroicon-o-arrow-right-on-rectangle class="w-3.5 h-3.5" />
                                                        <span>Impersonate</span>
                                                    </button>
                                                </form>
                                            @endif
                                            <a href="{{ route('users.edit', $u) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-md transition duration-150">
                                                Edit
                                            </a>
                                            @if (auth()->id() !== $u->id)
                                                <form action="{{ route('users.destroy', $u) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-md transition duration-150 cursor-pointer">
                                                        Hapus
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-zinc-400 dark:text-zinc-500">
                                        Tidak ada data user ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">
                        @if ($users->total() > 0)
                            Menampilkan {{ $users->firstItem() }} - {{ $users->lastItem() }} dari {{ $users->total() }} user terdaftar
                        @else
                            Menampilkan 0 user
                        @endif
                    </div>
                    @if ($users->hasPages())
                        <div class="flex-shrink-0">
                            {{ $users->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
