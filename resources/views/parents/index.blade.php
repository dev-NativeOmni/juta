<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Orangtua/Wali
            </h2>

            <div class="flex items-center gap-2">
                @if (auth()->user()->hasAnyRole(['super_admin', 'admin', 'teacher', 'coordinator_tahfizh', 'tanse']))
                    <a
                        href="{{ route('parents.export') }}"
                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 active:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Ekspor Excel
                    </a>

                    <button
                        type="button"
                        x-data=""
                        x-on:click.prevent="$dispatch('open-modal', 'import-parents')"
                        class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 active:bg-yellow-800 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Impor Excel
                    </button>
                @endif

                <a
                    href="{{ route('parents.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                >
                    Tambah Orangtua
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
                <form method="GET" action="{{ route('parents.index') }}" class="flex flex-col md:flex-row gap-3">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Cari nama, username, telepon, alamat"
                        x-on:input.debounce.600ms="$el.form.submit()"
                        class="rounded-md border-gray-300 shadow-sm md:w-96"
                    >

                    <button type="submit" class="inline-flex items-center justify-center px-4 py-2 bg-gray-800 rounded-md text-xs font-semibold text-white uppercase hover:bg-gray-700">
                        Cari
                    </button>

                    <a href="{{ route('parents.index') }}" class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 rounded-md text-xs font-semibold text-gray-700 uppercase hover:bg-gray-200">
                        Reset
                    </a>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-gray-500 uppercase">
                                <th class="px-4 py-3">Orangtua/Wali</th>
                                <th class="px-4 py-3">Telepon</th>
                                <th class="px-4 py-3">Alamat</th>
                                <th class="px-4 py-3">Murid Terhubung</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($parents as $parent)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">
                                            {{ $parent->user?->name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $parent->user?->username }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $parent->phone ?: '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $parent->address ?: '-' }}
                                    </td>

                                    <td class="px-4 py-3 text-gray-700">
                                        @if($parent->students->isNotEmpty())
                                            <div class="flex flex-col gap-1">
                                                @foreach($parent->students as $student)
                                                    <a href="{{ route('students.show', $student) }}" class="text-xs font-semibold text-indigo-600 hover:underline bg-indigo-50 px-2 py-0.5 rounded w-max">
                                                        {{ $student->name }} ({{ $student->pivot?->relation ?: 'Wali' }})
                                                    </a>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400 italic">Belum terhubung</span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded text-xs font-semibold {{ $parent->user?->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                            {{ $parent->user?->status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('parents.show', $parent) }}" class="btn-action-detail">
                                                Detail
                                            </a>

                                            <a href="{{ route('parents.edit', $parent) }}" class="btn-action-edit">
                                                Edit
                                            </a>

                                            <form method="POST" action="{{ route('parents.destroy', $parent) }}" onsubmit="return confirm('Hapus orangtua/wali ini?')">
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
                                        Belum ada data orangtua/wali.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="mt-4">
                        {{ $parents->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (auth()->user()->hasAnyRole(['super_admin', 'admin']))
    <x-modal name="import-parents" :show="false" focusable>
        <form method="POST" action="{{ route('parents.import') }}" enctype="multipart/form-data" class="p-6">
            @csrf

            <h2 class="text-lg font-medium text-gray-900">
                Impor Data Orangtua/Wali
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Unggah berkas Excel (.xlsx) atau CSV untuk mengimpor atau memperbarui data orangtua/wali secara massal.
            </p>

            <div class="mt-4 p-3 bg-gray-50 rounded text-xs text-gray-600 space-y-1 border border-gray-100">
                <p class="font-semibold text-gray-700">Format Kolom:</p>
                <ul class="list-disc pl-4 space-y-1">
                    <li><strong>Nama</strong> (Wajib): Nama lengkap orangtua/wali.</li>
                    <li><strong>Username</strong> (Wajib, kunci pencocokan): Jika sudah ada, data diperbarui. Jika belum ada, akun baru dibuat dengan password default <code>password123</code>.</li>
                    <li><strong>Telepon</strong> (Opsional): Nomor telepon.</li>
                    <li><strong>Alamat</strong> (Opsional): Alamat lengkap.</li>
                    <li><strong>Status</strong> (Opsional): <code>active</code> / <code>inactive</code>. Default: active.</li>
                </ul>
            </div>

            <div class="mt-6">
                <input
                    id="parent_import_file"
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