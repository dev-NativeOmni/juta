<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-2xl text-zinc-900 dark:text-white leading-tight flex items-center gap-2.5">
                    <div class="p-2 rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 border border-teal-200/60 dark:border-teal-500/20">
                        <x-heroicon-o-building-office-2 class="w-6 h-6" />
                    </div>
                    <span>Kelola Lembaga & Multi-Sekolah</span>
                </h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                    Pusat manajemen instansi, pengaturan kode akses sekolah, branding, dan isolasi data santri per lembaga.
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if ($activeInstitutionId)
                    <form method="POST" action="{{ route('institutions.switch') }}">
                        @csrf
                        <input type="hidden" name="institution_id" value="global">
                        <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3.5 py-2.5 text-xs font-bold text-zinc-700 dark:text-zinc-200 shadow-xs hover:bg-zinc-50 dark:hover:bg-zinc-700/60 transition cursor-pointer">
                            <x-heroicon-m-globe-alt class="w-4 h-4 text-zinc-500" />
                            <span>Tampilan Global</span>
                        </button>
                    </form>
                @endif
                <a
                    href="{{ route('institutions.create') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-teal-700 transition cursor-pointer"
                >
                    <x-heroicon-m-plus class="w-4 h-4 stroke-[2.5]" />
                    <span>Tambah Lembaga Baru</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 shadow-sm dark:bg-emerald-950/40 dark:border-emerald-800/60 dark:text-emerald-300 flex items-center gap-2">
                    <x-heroicon-o-check-circle class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800 shadow-sm dark:bg-rose-950/40 dark:border-rose-800/60 dark:text-rose-300 flex items-center gap-2">
                    <x-heroicon-o-x-circle class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" />
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- Summary Stats Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-5 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Lembaga</span>
                        <span class="p-2 rounded-xl bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400">
                            <x-heroicon-o-building-library class="w-4 h-4" />
                        </span>
                    </div>
                    <p class="mt-3 text-2xl font-black text-zinc-900 dark:text-white">{{ number_format($stats['total']) }}</p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 font-medium">Terdaftar di platform</p>
                </div>

                <div class="p-5 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Lembaga Aktif</span>
                        <span class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                            <x-heroicon-o-check-badge class="w-4 h-4" />
                        </span>
                    </div>
                    <p class="mt-3 text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ number_format($stats['active']) }}</p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 font-medium">Bisa diakses login portal</p>
                </div>

                <div class="p-5 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Santri</span>
                        <span class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">
                            <x-heroicon-o-academic-cap class="w-4 h-4" />
                        </span>
                    </div>
                    <p class="mt-3 text-2xl font-black text-zinc-900 dark:text-white">{{ number_format($stats['total_students']) }}</p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 font-medium">Seluruh lembaga</p>
                </div>

                <div class="p-5 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 shadow-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Total Pengguna</span>
                        <span class="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400">
                            <x-heroicon-o-users class="w-4 h-4" />
                        </span>
                    </div>
                    <p class="mt-3 text-2xl font-black text-zinc-900 dark:text-white">{{ number_format($stats['total_users']) }}</p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 font-medium">Akun aktif platform</p>
                </div>
            </div>

            <!-- Filters & Search -->
            <div class="p-4 rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/80 dark:border-zinc-800 shadow-xs">
                <form method="GET" action="{{ route('institutions.index') }}" class="flex flex-col sm:flex-row gap-3 items-center justify-between">
                    <div class="relative w-full sm:w-80">
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Cari kode, nama, slug..."
                            class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/60 text-xs text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 focus:border-teal-500 focus:ring-teal-500 pl-9"
                        >
                        <x-heroicon-o-magnifying-glass class="w-4 h-4 text-zinc-400 absolute left-3 top-1/2 -translate-y-1/2" />
                    </div>

                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <select
                            name="status"
                            onchange="this.form.submit()"
                            class="rounded-xl border-zinc-300 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/60 text-xs text-zinc-900 dark:text-zinc-100 focus:border-teal-500 focus:ring-teal-500"
                        >
                            <option value="">Semua Status</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                        </select>

                        @if (request()->hasAny(['search', 'status']))
                            <a href="{{ route('institutions.index') }}" class="px-3 py-2 text-xs font-bold text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Table Card -->
            <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-xs rounded-2xl border border-zinc-200/80 dark:border-zinc-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/50 text-left text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3.5">Lembaga / Sekolah</th>
                                <th class="px-6 py-3.5">Kode Gerbang</th>
                                <th class="px-6 py-3.5">Statistik Domain</th>
                                <th class="px-6 py-3.5">Status</th>
                                <th class="px-6 py-3.5 text-right">Aksi & Tenancy</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200/80 dark:divide-zinc-800 text-xs">
                            @forelse ($institutions as $inst)
                                <tr class="hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40 transition {{ $activeInstitutionId === $inst->id ? 'bg-teal-50/40 dark:bg-teal-950/20' : '' }}">
                                    <!-- Logo & Name -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="w-11 h-11 rounded-xl bg-zinc-100 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 flex items-center justify-center p-1 overflow-hidden shrink-0 shadow-2xs">
                                                @if ($inst->logo_url)
                                                    <img src="{{ $inst->logo_url }}" alt="{{ $inst->name }}" class="w-full h-full object-contain">
                                                @else
                                                    <x-heroicon-o-building-office-2 class="w-6 h-6 text-zinc-400" />
                                                @endif
                                            </div>
                                            <div>
                                                <div class="font-bold text-sm text-zinc-900 dark:text-white flex items-center gap-2">
                                                    <span>{{ $inst->name }}</span>
                                                    @if ($activeInstitutionId === $inst->id)
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-teal-100 text-teal-800 dark:bg-teal-900/60 dark:text-teal-300 border border-teal-300 dark:border-teal-700">
                                                            Sesi Aktif
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="text-[11px] text-zinc-500 dark:text-zinc-400 flex items-center gap-2 mt-0.5">
                                                    <span>Slug: <code class="text-zinc-700 dark:text-zinc-300 font-mono">{{ $inst->slug }}</code></span>
                                                    @if ($inst->email)
                                                        <span>• {{ $inst->email }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Code -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-1.5">
                                                <span class="px-2.5 py-1 rounded-lg bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 font-mono font-bold text-xs border border-teal-200 dark:border-teal-800/60">
                                                    {{ $inst->code }}
                                                </span>
                                            </div>
                                            <a href="{{ route('portal.direct', $inst->code) }}" target="_blank" class="text-[10px] text-zinc-500 hover:text-teal-600 dark:hover:text-teal-400 flex items-center gap-1">
                                                <span>Buka portal: /s/{{ $inst->code }}</span>
                                                <x-heroicon-m-arrow-top-right-on-square class="w-3 h-3" />
                                            </a>
                                        </div>
                                    </td>

                                    <!-- Stats -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3 text-zinc-600 dark:text-zinc-400">
                                            <div class="flex items-center gap-1" title="Santri">
                                                <x-heroicon-o-academic-cap class="w-4 h-4 text-indigo-500" />
                                                <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ $inst->students_count }}</span>
                                                <span class="text-[10px]">Santri</span>
                                            </div>
                                            <div class="flex items-center gap-1" title="Guru">
                                                <x-heroicon-o-briefcase class="w-4 h-4 text-teal-500" />
                                                <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ $inst->teachers_count }}</span>
                                                <span class="text-[10px]">Guru</span>
                                            </div>
                                            <div class="flex items-center gap-1" title="Kelas">
                                                <x-heroicon-o-building-office class="w-4 h-4 text-amber-500" />
                                                <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ $inst->class_rooms_count }}</span>
                                                <span class="text-[10px]">Kelas</span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($inst->is_active)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-700">
                                                <span class="w-1.5 h-1.5 rounded-full bg-zinc-400"></span>
                                                Nonaktif
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            @if ($activeInstitutionId !== $inst->id && $inst->is_active)
                                                <form method="POST" action="{{ route('institutions.switch') }}" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="institution_id" value="{{ $inst->id }}">
                                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-teal-50 dark:bg-teal-950/50 text-teal-700 dark:text-teal-300 hover:bg-teal-100 dark:hover:bg-teal-900 border border-teal-200 dark:border-teal-800 text-xs font-bold transition cursor-pointer" title="Kelola lembaga ini">
                                                        <x-heroicon-m-arrow-right-end-on-rectangle class="w-3.5 h-3.5" />
                                                        <span>Masuk Lembaga</span>
                                                    </button>
                                                </form>
                                            @endif

                                            <a
                                                href="{{ route('institutions.edit', $inst) }}"
                                                class="p-1.5 rounded-lg text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition"
                                                title="Edit Lembaga"
                                            >
                                                <x-heroicon-o-pencil-square class="w-4 h-4" />
                                            </a>

                                            <form
                                                method="POST"
                                                action="{{ route('institutions.destroy', $inst) }}"
                                                onsubmit="return confirm('Apakah Anda yakin ingin menghapus/menonaktifkan lembaga ini?');"
                                                class="inline"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="p-1.5 rounded-lg text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer"
                                                    title="Hapus / Nonaktifkan"
                                                >
                                                    <x-heroicon-o-trash class="w-4 h-4" />
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                        <div class="flex flex-col items-center justify-center gap-2">
                                            <x-heroicon-o-building-office-2 class="w-8 h-8 text-zinc-400" />
                                            <p class="font-semibold text-sm">Belum ada data lembaga yang cocok.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($institutions->hasPages())
                    <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800">
                        {{ $institutions->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
