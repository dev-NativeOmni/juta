<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Kelas
            </h2>

            <div class="flex items-center gap-2">
                @if (auth()->user()->hasAnyRole(['super_admin', 'admin', 'teacher', 'coordinator_tahfizh', 'tanse']))
                    <a
                        href="{{ route('class-rooms.export') }}"
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Ekspor Excel
                    </a>

                    <button
                        type="button"
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'import-class-rooms')"
                        class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 active:bg-yellow-800 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Impor Excel
                    </button>
                @endif

                <a
                    href="{{ route('class-rooms.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                >
                    Tambah Kelas
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('class-rooms.index') }}" class="flex flex-col md:flex-row gap-3">
                    <select name="program_id" onchange="this.form.submit()" class="rounded-md border-gray-300 shadow-sm">
                        <option value="">Semua Program</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" @selected((string) request('program_id') === (string) $program->id)>
                                {{ $program->name }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit" class="inline-flex items-center justify-center px-4 py-2 bg-gray-800 rounded-md text-xs font-semibold text-white uppercase hover:bg-gray-700">
                        Filter
                    </button>

                    <a href="{{ route('class-rooms.index') }}" class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 rounded-md text-xs font-semibold text-gray-700 uppercase hover:bg-gray-200">
                        Reset
                    </a>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-gray-500 uppercase">
                                <th class="px-4 py-3">Nama Kelas</th>
                                <th class="px-4 py-3">Program</th>
                                <th class="px-4 py-3">Level</th>
                                <th class="px-4 py-3">Pendamping Adab</th>
                                <th class="px-4 py-3">Wali Kelas</th>
                                <th class="px-4 py-3">Jumlah Murid</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($classRooms as $classRoom)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ $classRoom->name }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $classRoom->program?->name ?: '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $classRoom->level ?: '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        @php
                                            $allPendamping = $classRoom->pendampingAdabList->isNotEmpty() 
                                                ? $classRoom->pendampingAdabList 
                                                : ($classRoom->pendampingAdab ? collect([$classRoom->pendampingAdab]) : collect());
                                        @endphp
                                        @if($allPendamping->isNotEmpty())
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($allPendamping as $pItem)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                        {{ $pItem->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-gray-400 italic text-xs">Belum diatur</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        @if ($classRoom->waliKelas)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-sky-50 text-sky-800 dark:bg-sky-950/40 dark:text-sky-300 border border-sky-200 dark:border-sky-800">
                                                {{ $classRoom->waliKelas->name }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 italic text-xs">Belum diatur</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $classRoom->students_count }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('class-rooms.show', $classRoom) }}" class="btn-action-detail">
                                                Detail
                                            </a>

                                            <a href="{{ route('class-rooms.edit', $classRoom) }}" class="btn-action-edit">
                                                Edit
                                            </a>

                                            <form method="POST" action="{{ route('class-rooms.destroy', $classRoom) }}" onsubmit="return confirm('Hapus kelas ini?')">
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
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                        Belum ada data kelas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $classRooms->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (auth()->user()->hasAnyRole(['super_admin', 'admin']))
    <x-modal name="import-class-rooms" :show="false" focusable>
        <form method="POST" action="{{ route('class-rooms.import') }}" enctype="multipart/form-data" class="p-6">
            @csrf

            <h2 class="text-lg font-medium text-gray-900">
                Impor Data Kelas
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Unggah berkas Excel (.xlsx) atau CSV untuk mengimpor atau memperbarui data kelas secara massal.
            </p>

            <div class="mt-4 p-3 bg-gray-50 rounded text-xs text-gray-600 space-y-1 border border-gray-100">
                <p class="font-semibold text-gray-700">Format Kolom:</p>
                <ul class="list-disc pl-4 space-y-1">
                    <li><strong>Nama Kelas</strong> (Wajib): Nama kelas. Jika sudah ada (dengan program sama), data diperbarui.</li>
                    <li><strong>Level</strong> (Opsional): Level/tingkatan kelas.</li>
                    <li><strong>Program</strong> (Opsional): Nama program yang sesuai di sistem.</li>
                </ul>
            </div>

            <div class="mt-6">
                <input
                    id="classroom_import_file"
                    name="file"
                    type="file"
                    accept=".xlsx,.csv,.txt"
                    class="block w-full border border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
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