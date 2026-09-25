<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h2 class="font-bold text-lg sm:text-xl text-zinc-900 dark:text-white leading-tight">
                Data Murid
            </h2>

            <div class="flex flex-wrap items-center gap-2">
                @if (auth()->user()->hasAnyRole(['super_admin', 'admin', 'teacher', 'coordinator_tahfizh', 'tanse']))
                    <a
                        href="{{ route('students.export') }}"
                        class="inline-flex items-center justify-center px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white rounded-xl font-bold text-xs shadow-sm transition duration-150 min-h-[38px] flex-1 sm:flex-none"
                    >
                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 mr-1.5 shrink-0" /> Ekspor Excel
                    </a>

                    <button
                        type="button"
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'import-students')"
                        class="inline-flex items-center justify-center px-3.5 py-2 bg-amber-600 hover:bg-amber-700 active:scale-95 text-white rounded-xl font-bold text-xs shadow-sm transition duration-150 min-h-[38px] flex-1 sm:flex-none"
                    >
                        <x-heroicon-o-arrow-up-tray class="w-4 h-4 mr-1.5 shrink-0" /> Impor Excel
                    </button>
                @endif

                <a
                    href="{{ route('students.create') }}"
                    class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white rounded-xl font-bold text-xs shadow-sm transition duration-150 min-h-[38px] flex-1 sm:flex-none"
                >
                    + Tambah Murid
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-3 sm:py-6">
        <div class="max-w-7xl mx-auto space-y-3 sm:space-y-4">
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

            <!-- Filter Section -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-2xl p-3.5 sm:p-5">
                <form method="GET" action="{{ route('students.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari nama / nomor murid..."
                        x-on:input.debounce.600ms="$el.form.submit()"
                        class="rounded-xl border-zinc-300 dark:border-zinc-700 bg-transparent text-xs sm:text-sm text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 shadow-sm py-2 px-3"
                    >

                    <select name="class_room_id" onchange="this.form.submit()" class="rounded-xl border-zinc-300 dark:border-zinc-700 bg-transparent text-xs sm:text-sm text-zinc-900 dark:text-zinc-100 shadow-sm py-2 px-3 font-medium cursor-pointer">
                        <option value="">Semua Kelas</option>
                        @foreach ($classRooms as $classRoom)
                            <option value="{{ $classRoom->id }}" @selected((string) request('class_room_id') === (string) $classRoom->id)>
                                {{ $classRoom->program?->name ? $classRoom->program->name . ' - ' : '' }}{{ $classRoom->name }}
                            </option>
                        @endforeach
                    </select>

                    <select name="status" onchange="this.form.submit()" class="rounded-xl border-zinc-300 dark:border-zinc-700 bg-transparent text-xs sm:text-sm text-zinc-900 dark:text-zinc-100 shadow-sm py-2 px-3 font-medium cursor-pointer">
                        <option value="">Semua Status</option>
                        <option value="active" @selected(request('status') === 'active')>Aktif</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                        <option value="graduated" @selected(request('status') === 'graduated')>Lulus</option>
                    </select>

                    <div class="flex gap-2 col-span-1 sm:col-span-2 lg:col-span-1">
                        <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:scale-95 rounded-xl text-xs font-bold text-white uppercase tracking-wider transition min-h-[38px] shadow-sm cursor-pointer">
                            Filter
                        </button>

                        <a href="{{ route('students.index') }}" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 active:scale-95 rounded-xl text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider transition min-h-[38px] cursor-pointer">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Mobile View: Card Stack (< md) -->
            <div class="block md:hidden space-y-3">
                @forelse ($students as $student)
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-4 space-y-3">
                        <div class="flex items-start justify-between gap-2 border-b border-zinc-100 dark:border-zinc-800 pb-2.5">
                            <div>
                                <h3 class="font-bold text-sm text-zinc-900 dark:text-white leading-tight">
                                    {{ $student->name }}
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    NIS: {{ $student->student_number ?: '-' }}
                                </p>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold shrink-0
                                {{ $student->status === 'active' ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400' : '' }}
                                {{ $student->status === 'inactive' ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-400' : '' }}
                                {{ $student->status === 'graduated' ? 'bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400' : '' }}
                            ">
                                @if ($student->status === 'active')
                                    Aktif
                                @elseif ($student->status === 'inactive')
                                    Nonaktif
                                @else
                                    Lulus
                                @endif
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-zinc-400 dark:text-zinc-500 block text-[10px] uppercase font-semibold">Kelas</span>
                                <span class="font-bold text-zinc-800 dark:text-zinc-200">
                                    {{ $student->classRoom?->name ?: '-' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-zinc-400 dark:text-zinc-500 block text-[10px] uppercase font-semibold">Guru Pembimbing</span>
                                <span class="font-semibold text-zinc-700 dark:text-zinc-300 truncate block">
                                    {{ $student->teacher?->user?->name ?: '-' }}
                                </span>
                            </div>
                        </div>

                        <!-- Action Bar Mobile -->
                        <div class="flex items-center gap-2 pt-2 border-t border-zinc-100 dark:border-zinc-800">
                            <a href="{{ route('students.show', $student) }}" class="btn-action-detail flex-1 inline-flex items-center justify-center gap-1">
                                <x-heroicon-o-eye class="w-3.5 h-3.5" />
                                <span>Detail</span>
                            </a>
                            <a href="{{ route('students.edit', $student) }}" class="btn-action-edit flex-1 inline-flex items-center justify-center gap-1">
                                <x-heroicon-o-pencil-square class="w-3.5 h-3.5" />
                                <span>Edit</span>
                            </a>
                            <form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('Hapus murid ini? Data akan soft delete.')" class="flex-1">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-action-delete w-full inline-flex items-center justify-center gap-1">
                                    <x-heroicon-o-trash class="w-3.5 h-3.5" />
                                    <span>Hapus</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-xl p-6 text-center text-xs text-zinc-500">
                        Belum ada data murid.
                    </div>
                @endforelse

                <div class="mt-4">
                    {{ $students->links() }}
                </div>
            </div>

            <!-- Desktop View: Table (>= md) -->
            <div class="hidden md:block bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 overflow-hidden shadow-sm rounded-xl">
                <div class="p-4 sm:p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">
                                <th class="px-4 py-3">Murid</th>
                                <th class="px-4 py-3">Kelas</th>
                                <th class="px-4 py-3">Guru</th>
                                <th class="px-4 py-3">Orangtua/Wali</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                            @forelse ($students as $student)
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/40 transition duration-150">
                                    <td class="px-4 py-3.5">
                                        <div class="font-bold text-xs text-zinc-900 dark:text-white">
                                            {{ $student->name }}
                                        </div>
                                        <div class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                            {{ $student->student_number ?: 'Nomor belum diisi' }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3.5 text-xs text-zinc-700 dark:text-zinc-300">
                                        {{ $student->classRoom?->name ?: '-' }}
                                        @if ($student->classRoom?->program)
                                            <div class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                                {{ $student->classRoom->program->name }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3.5 text-xs text-zinc-700 dark:text-zinc-300">
                                        {{ $student->teacher?->user?->name ?: '-' }}
                                    </td>

                                    <td class="px-4 py-3.5 text-xs text-zinc-700 dark:text-zinc-300">
                                        @forelse ($student->parents as $parent)
                                            <div>
                                                {{ $parent->user?->name }}
                                                @if ($parent->pivot?->relation)
                                                    <span class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                                        ({{ $parent->pivot->relation }})
                                                    </span>
                                                @endif
                                            </div>
                                        @empty
                                            -
                                        @endforelse
                                    </td>

                                    <td class="px-4 py-3.5">
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold whitespace-nowrap inline-block
                                            {{ $student->status === 'active' ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400' : '' }}
                                            {{ $student->status === 'inactive' ? 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-400' : '' }}
                                            {{ $student->status === 'graduated' ? 'bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400' : '' }}
                                        ">
                                            @if ($student->status === 'active')
                                                Aktif
                                            @elseif ($student->status === 'inactive')
                                                Nonaktif
                                            @else
                                                Lulus
                                            @endif
                                        </span>
                                    </td>

                                    <td class="px-4 py-3.5">
                                        <div class="flex justify-end items-center gap-2">
                                            <a href="{{ route('students.show', $student) }}" class="btn-action-detail">
                                                Detail
                                            </a>

                                            <a href="{{ route('students.edit', $student) }}" class="btn-action-edit">
                                                Edit
                                            </a>

                                            <form method="POST" action="{{ route('students.destroy', $student) }}" onsubmit="return confirm('Hapus murid ini? Data akan soft delete.')">
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
                                    <td colspan="6" class="px-4 py-6 text-center text-xs text-zinc-500">
                                        Belum ada data murid.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $students->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (auth()->user()->hasAnyRole(['super_admin', 'admin']))
    <x-modal name="import-students" :show="false" focusable>
        <form method="POST" action="{{ route('students.import') }}" enctype="multipart/form-data" class="p-6">
            @csrf

            <h2 class="text-lg font-medium text-gray-900 dark:text-white">
                Impor Data Murid
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-zinc-400">
                Unggah berkas Excel (.xlsx / .xls) untuk mengimpor atau memperbarui data murid secara massal.
            </p>

            <div class="mt-4 p-3 bg-gray-50 dark:bg-zinc-800/60 rounded text-xs text-gray-600 dark:text-zinc-300 space-y-1 border border-gray-100 dark:border-zinc-700 max-h-60 overflow-y-auto">
                <p class="font-semibold text-gray-700 dark:text-zinc-200">Format Kolom Berkas Excel:</p>
                <ul class="list-disc pl-4 space-y-1">
                    <li><strong>Nama Murid</strong> (Wajib): Nama lengkap murid</li>
                    <li><strong>Nomor Induk</strong> (Opsional, kunci pencocokan): Jika diisi dan sudah ada, data murid akan diperbarui. Jika belum ada, data murid baru akan dibuat.</li>
                    <li><strong>Jenis Kelamin</strong> (Opsional): male / female</li>
                    <li><strong>Tanggal Lahir</strong> (Opsional): format YYYY-MM-DD</li>
                    <li><strong>Status</strong> (Opsional): active / inactive / graduated</li>
                    <li><strong>Kelas</strong> (Opsional): Nama kelas yang sesuai di sistem</li>
                    <li><strong>Level Tahfizh</strong> (Opsional): <code>tahsin</code>, <code>reguler</code>, <code>akselerasi</code>, atau <code>ummi</code></li>
                    <li><strong>Username Guru</strong> (Opsional): Username akun guru pembimbing</li>
                    <li><strong>Username Murid</strong> (Opsional): Username akun murid</li>
                    <li><strong>Username Orangtua</strong> (Opsional): Username akun orangtua, pisahkan dengan koma jika memiliki lebih dari satu orangtua (contoh: <code>ortu1,ortu2</code>).</li>
                    <li><strong>Hubungan Orangtua</strong> (Opsional): Relasi orangtua, pisahkan dengan koma (contoh: <code>Ayah,Ibu</code>). Harus berurutan sesuai dengan Username Orangtua.</li>
                </ul>
            </div>

            <div class="mt-6">
                <x-input-label for="excel_file" value="Pilih Berkas Excel" class="sr-only" />

                <input
                    id="excel_file"
                    name="file"
                    type="file"
                    accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                    class="block w-full border border-gray-300 dark:border-zinc-700 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-zinc-800 dark:text-white"
                    required
                />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Batal
                </x-secondary-button>

                <x-primary-button class="ms-3 bg-yellow-600 hover:bg-yellow-700 active:bg-yellow-800">
                    Proses Impor
                </x-primary-button>
            </div>
        </form>
    </x-modal>
    @endif
</x-app-layout>