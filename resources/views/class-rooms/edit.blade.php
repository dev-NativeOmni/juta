<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Kelas
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('class-rooms.update', $classRoom) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="program_id" class="block text-sm font-medium text-gray-700">
                            Program
                        </label>
                        <select
                            id="program_id"
                            name="program_id"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            required
                        >
                            <option value="">Pilih Program</option>
                            @foreach ($programs as $program)
                                <option value="{{ $program->id }}" @selected((string) old('program_id', $classRoom->program_id) === (string) $program->id)>
                                    {{ $program->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('program_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            Nama Kelas
                        </label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name', $classRoom->name) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            required
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="level" class="block text-sm font-medium text-gray-700">
                            Level
                        </label>
                        <input
                            id="level"
                            name="level"
                            type="text"
                            value="{{ old('level', $classRoom->level) }}"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        >
                        @error('level')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>


                    <!-- Wali Kelas (satu kelas satu wali kelas) -->
                    <div>
                        <label for="wali_kelas_user_id" class="block text-sm font-medium text-gray-700">Wali Kelas</label>
                        <select id="wali_kelas_user_id" name="wali_kelas_user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">-- Belum ditentukan --</option>
                            @foreach ($waliKelasList as $w)
                                <option value="{{ $w->id }}" @selected((string) old('wali_kelas_user_id', $classRoom->wali_kelas_user_id) === (string) $w->id)>
                                    {{ $w->name }}
                                </option>
                            @endforeach
                        </select>
                        @if ($waliKelasList->isEmpty())
                            <p class="mt-1 text-xs text-amber-600">Belum ada user dengan peran Wali Kelas yang tersedia (semua sudah ditugaskan, atau belum ada user berperan Wali Kelas).</p>
                        @endif
                        @error('wali_kelas_user_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Pendamping Adab (Bisa lebih dari 1) -->
                    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700/80 p-4 bg-zinc-50/50 dark:bg-zinc-800/40 space-y-2.5">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="block text-sm font-bold text-zinc-800 dark:text-zinc-200">
                                    Pendamping Adab (Bisa Pilih Lebih dari 1)
                                </label>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    Centang satu atau beberapa asatidzah/guru pendamping adab untuk kelas ini.
                                </p>
                            </div>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-300">
                                Multi-Pendamping
                            </span>
                        </div>

                        @php
                            $selectedPendampingIds = old('pendamping_adab_ids', $classRoom->pendampingAdabList->pluck('id')->all());
                            if (empty($selectedPendampingIds) && $classRoom->pendamping_adab_id) {
                                $selectedPendampingIds = [$classRoom->pendamping_adab_id];
                            }
                        @endphp

                        @if ($pendampingList->isEmpty())
                            <p class="text-xs text-amber-600 dark:text-amber-400 italic">
                                Belum ada user dengan peran/side-role Pendamping Adab aktif.
                            </p>
                        @else
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1 max-h-48 overflow-y-auto pr-1">
                                @foreach ($pendampingList as $p)
                                    <label class="flex items-center gap-2.5 p-2.5 rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-xs font-medium text-zinc-800 dark:text-zinc-200 cursor-pointer hover:border-teal-400 dark:hover:border-teal-500 transition shadow-2xs">
                                        <input
                                            type="checkbox"
                                            name="pendamping_adab_ids[]"
                                            value="{{ $p->id }}"
                                            @checked(in_array($p->id, $selectedPendampingIds))
                                            class="rounded border-zinc-300 dark:border-zinc-600 text-teal-600 focus:ring-teal-500"
                                        >
                                        <div class="flex flex-col">
                                            <span class="font-bold text-zinc-900 dark:text-white">{{ $p->name }}</span>
                                            <span class="text-[10px] text-zinc-400">{{ $p->username }} · {{ $p->role?->display_name ?? 'Pendamping Adab' }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        @error('pendamping_adab_ids')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('class-rooms.index') }}" class="text-sm text-gray-600 hover:underline">
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                        >
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>