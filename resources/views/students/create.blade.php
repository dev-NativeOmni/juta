@php
    $parentsListFormatted = $parents->map(function($parent, $index) {
        return [
            'index' => $index,
            'id' => $parent->id,
            'name' => $parent->user?->name ?? '',
            'phone' => $parent->phone ?? '',
        ];
    })->values();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Tambah Murid
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('students.store') }}" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">
                                Nama Murid
                            </label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                value="{{ old('name') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="student_number" class="block text-sm font-medium text-gray-700">
                                Nomor Murid / NIS
                            </label>
                            <input
                                id="student_number"
                                name="student_number"
                                type="text"
                                value="{{ old('student_number') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                            @error('student_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="class_room_id" class="block text-sm font-medium text-gray-700">
                                Kelas
                            </label>
                            <select
                                id="class_room_id"
                                name="class_room_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            >
                                <option value="">Pilih Kelas</option>
                                @foreach ($classRooms as $classRoom)
                                    <option value="{{ $classRoom->id }}" @selected((string) old('class_room_id') === (string) $classRoom->id)>
                                        {{ $classRoom->program?->name ? $classRoom->program->name . ' - ' : '' }}{{ $classRoom->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('class_room_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="teacher_id" class="block text-sm font-medium text-gray-700">
                                Guru Pembimbing
                            </label>
                            <select
                                id="teacher_id"
                                name="teacher_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            >
                                <option value="">Pilih Guru</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" @selected((string) old('teacher_id') === (string) $teacher->id)>
                                        {{ $teacher->user?->name }}{{ $teacher->employee_number ? ' - ' . $teacher->employee_number : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('teacher_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="gender" class="block text-sm font-medium text-gray-700">
                                Gender
                            </label>
                            <select
                                id="gender"
                                name="gender"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                                <option value="">Pilih Gender</option>
                                <option value="male" @selected(old('gender') === 'male')>Laki-laki</option>
                                <option value="female" @selected(old('gender') === 'female')>Perempuan</option>
                            </select>
                            @error('gender')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="birth_date" class="block text-sm font-medium text-gray-700">
                                Tanggal Lahir
                            </label>
                            <input
                                id="birth_date"
                                name="birth_date"
                                type="date"
                                value="{{ old('birth_date') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                            @error('birth_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">
                                Status
                            </label>
                            <select
                                id="status"
                                name="status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            >
                                <option value="active" @selected(old('status', 'active') === 'active')>Aktif</option>
                                <option value="inactive" @selected(old('status') === 'inactive')>Nonaktif</option>
                                <option value="graduated" @selected(old('status') === 'graduated')>Lulus</option>
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="tahfizh_level" class="block text-sm font-medium text-gray-700">
                                Level Tahfizh
                            </label>
                            <select
                                id="tahfizh_level"
                                name="tahfizh_level"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                                <option value="" @selected(old('tahfizh_level') === '')>Otomatis (Sesuai Kelas)</option>
                                <option value="tahsin" @selected(old('tahfizh_level') === 'tahsin')>Tahsin (3 baris/pertemuan)</option>
                                <option value="reguler" @selected(old('tahfizh_level') === 'reguler')>Reguler (5 baris/pertemuan)</option>
                                <option value="akselerasi" @selected(old('tahfizh_level') === 'akselerasi')>Akselerasi (7 baris/pertemuan)</option>
                                <option value="ummi" @selected(old('tahfizh_level') === 'ummi')>Metode Ummi (Kelas 10)</option>
                            </select>
                            @error('tahfizh_level')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="hafalan_direction" class="block text-sm font-medium text-gray-700">
                                Arah Hafalan
                            </label>
                            <select
                                id="hafalan_direction"
                                name="hafalan_direction"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                                @foreach (\App\Support\HafalanOrder::directionOptions() as $value => $label)
                                    <option value="{{ $value }}" @selected(\App\Support\HafalanOrder::normalizeDirection(old('hafalan_direction', 'backward')) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Juz 30 &amp; 29 selalu lebih dulu; pindah ke depan paling lambat setelah Juz 27. Dipakai untuk menghitung target otomatis.</p>
                            @error('hafalan_direction')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="user_id" class="block text-sm font-medium text-gray-700">
                                Akun Login Murid
                            </label>
                            <select
                                id="user_id"
                                name="user_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                                <option value="">Tidak dihubungkan dulu</option>
                                @foreach ($studentUsers as $studentUser)
                                    <option value="{{ $studentUser->id }}" @selected((string) old('user_id') === (string) $studentUser->id)>
                                        {{ $studentUser->name }} - {{ $studentUser->username }}
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <script>
                        window.parentRelationsPicker = function(parentsList, selectedIds) {
                            return {
                                search: '',
                                currentPage: 1,
                                perPage: 10,
                                selectedIds: Array.isArray(selectedIds) ? selectedIds : [],
                                parentsList: Array.isArray(parentsList) ? parentsList : [],

                                get filteredIndices() {
                                    if (!this.search || !this.search.trim()) {
                                        return this.parentsList.map(function(p) { return p.index; });
                                    }
                                    var q = this.search.toLowerCase().trim();
                                    return this.parentsList
                                        .filter(function(p) {
                                            return (p.name && p.name.toLowerCase().indexOf(q) !== -1) || 
                                                   (p.phone && String(p.phone).toLowerCase().indexOf(q) !== -1);
                                        })
                                        .map(function(p) { return p.index; });
                                },

                                get totalPages() {
                                    return Math.ceil(this.filteredIndices.length / this.perPage) || 1;
                                },

                                get paginatedIndices() {
                                    var start = (this.currentPage - 1) * this.perPage;
                                    return this.filteredIndices.slice(start, start + this.perPage);
                                },

                                prevPage() {
                                    if (this.currentPage > 1) this.currentPage--;
                                },

                                nextPage() {
                                    if (this.currentPage < this.totalPages) this.currentPage++;
                                },

                                toggleSelect(id, isChecked) {
                                    if (isChecked && !this.selectedIds.includes(id)) {
                                        this.selectedIds.push(id);
                                    } else if (!isChecked) {
                                        this.selectedIds = this.selectedIds.filter(function(i) { return i !== id; });
                                    }
                                }
                            };
                        };
                    </script>

                    <div 
                        data-parents="{{ json_encode($parentsListFormatted) }}"
                        data-selected="{{ json_encode(old('parent_ids', [])) }}"
                        x-data="parentRelationsPicker(JSON.parse($el.dataset.parents), JSON.parse($el.dataset.selected))" 
                        class="border-t pt-5"
                    >
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                            <h3 class="font-semibold text-gray-900 flex items-center gap-2">
                                <x-heroicon-o-user-group class="w-5 h-5 text-indigo-600" /> Relasi Orangtua/Wali
                            </h3>
                            <span class="text-xs text-gray-500 font-medium bg-gray-100 dark:bg-zinc-800 px-2.5 py-1 rounded-full border border-gray-200 dark:border-zinc-700">
                                <span class="font-bold text-indigo-600 dark:text-indigo-400" x-text="selectedIds.length"></span> Terpilih
                            </span>
                        </div>

                        <!-- Search Bar Input -->
                        <div class="relative mb-4">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                            </div>
                            <input 
                                type="text" 
                                x-model="search" 
                                @input="currentPage = 1"
                                placeholder="Cari nama orangtua atau nomor telepon..." 
                                class="block w-full pl-9 pr-8 py-2 rounded-lg border-gray-300 shadow-xs text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            >
                            <button 
                                type="button" 
                                x-show="search.length > 0" 
                                @click="search = ''; currentPage = 1" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                            >
                                <x-heroicon-o-x-mark class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="space-y-3">
                            @forelse ($parents as $index => $parent)
                                @php
                                    $isInitiallySelected = in_array($parent->id, old('parent_ids', []));
                                @endphp
                                <div 
                                    x-show="paginatedIndices.includes({{ $index }})"
                                    class="grid grid-cols-1 md:grid-cols-2 gap-3 items-center border rounded-xl p-3 transition"
                                    :class="selectedIds.includes({{ $parent->id }}) ? 'bg-indigo-50/60 dark:bg-indigo-950/20 border-indigo-200' : 'bg-white border-gray-200'"
                                >
                                    <label class="flex items-center gap-2.5 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            name="parent_ids[]"
                                            value="{{ $parent->id }}"
                                            @checked($isInitiallySelected)
                                            @change="toggleSelect({{ $parent->id }}, $event.target.checked)"
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        >
                                        <span class="text-sm font-semibold text-gray-900">
                                            {{ $parent->user?->name }}
                                            @if($parent->phone)
                                                <span class="text-xs font-normal text-gray-500 block sm:inline">({{ $parent->phone }})</span>
                                            @endif
                                        </span>
                                    </label>

                                    <input
                                        type="text"
                                        name="parent_relations[{{ $parent->id }}]"
                                        value="{{ old('parent_relations.' . $parent->id) }}"
                                        placeholder="Relasi, contoh: ayah / ibu / wali"
                                        class="rounded-md border-gray-300 shadow-xs text-sm"
                                    >
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 italic">
                                    Belum ada data orangtua/wali.
                                </p>
                            @endforelse
                        </div>

                        <!-- Pagination Navigation Bar -->
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 mt-4 pt-3 border-t border-gray-200">
                            <div class="text-xs text-gray-500 font-medium">
                                Menampilkan <span class="font-bold text-gray-800" x-text="filteredIndices.length > 0 ? ((currentPage - 1) * perPage + 1) : 0"></span> 
                                s/d <span class="font-bold text-gray-800" x-text="Math.min(currentPage * perPage, filteredIndices.length)"></span> 
                                dari <span class="font-bold text-gray-800" x-text="filteredIndices.length"></span> Orang Tua
                            </div>

                            <div class="flex items-center gap-2" x-show="totalPages > 1">
                                <button 
                                    type="button" 
                                    @click="prevPage()" 
                                    :disabled="currentPage === 1"
                                    :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed bg-gray-100 text-gray-400' : 'bg-white hover:bg-gray-100 text-gray-700 shadow-xs border border-gray-300 cursor-pointer'"
                                    class="px-3 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1"
                                >
                                    « Sebelumnya
                                </button>

                                <span class="text-xs font-bold text-gray-700 px-2">
                                    Halaman <span x-text="currentPage"></span> / <span x-text="totalPages"></span>
                                </span>

                                <button 
                                    type="button" 
                                    @click="nextPage()" 
                                    :disabled="currentPage === totalPages"
                                    :class="currentPage === totalPages ? 'opacity-50 cursor-not-allowed bg-gray-100 text-gray-400' : 'bg-white hover:bg-gray-100 text-gray-700 shadow-xs border border-gray-300 cursor-pointer'"
                                    class="px-3 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1"
                                >
                                    Berikutnya »
                                </button>
                            </div>
                        </div>

                        @error('parent_ids')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('students.index') }}" class="text-sm text-gray-600 hover:underline">
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                        >
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>