<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edit Setoran Hafalan
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('hafalan-records.update', $hafalanRecord) }}" class="space-y-6" x-data="{
                    selectedClass: '',
                    selectedStudent: '{{ old('student_id', $hafalanRecord->student_id) }}',
                    allStudents: [
                        @foreach($students as $student)
                            { id: {{ $student->id }}, name: '{{ addslashes($student->name) }}', nis: '{{ $student->student_number ?? '' }}', classId: '{{ $student->class_room_id }}', className: '{{ $student->classRoom?->name ?? '' }}' },
                        @endforeach
                    ],
                    hafalans: [
                        @forelse (old('surah_ids', []) as $index => $surahId)
                            { surah_id: '{{ $surahId }}', ayah_start: '{{ old('ayah_starts')[$index] ?? '' }}', ayah_end: '{{ old('ayah_ends')[$index] ?? '' }}', submission_type: '{{ old('submission_types')[$index] ?? 'new' }}', score: '{{ old('scores')[$index] ?? '' }}', status: '{{ old('statuses')[$index] ?? 'passed' }}' },
                        @empty
                            @forelse ($hafalanRecord->surahs as $surahEntry)
                                { surah_id: '{{ $surahEntry->surah_id }}', ayah_start: '{{ $surahEntry->ayah_start }}', ayah_end: '{{ $surahEntry->ayah_end }}', submission_type: '{{ $surahEntry->submission_type }}', score: '{{ $surahEntry->score ?? '' }}', status: '{{ $surahEntry->status }}' },
                            @empty
                                { surah_id: '', ayah_start: '', ayah_end: '', submission_type: 'new', score: '', status: 'passed' },
                            @endforelse
                        @endforelse
                    ],
                    get filteredStudents() {
                        if (!this.selectedClass) return this.allStudents;
                        return this.allStudents.filter(s => s.classId == this.selectedClass);
                    }
                }" x-init="
                    if (selectedStudent) {
                        let s = allStudents.find(x => x.id == selectedStudent);
                        if (s) selectedClass = s.classId;
                    }
                }">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="class_room_filter" class="block text-sm font-medium text-gray-700">
                                Saring Berdasarkan Kelas
                            </label>
                            <select
                                id="class_room_filter"
                                x-model="selectedClass"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                                <option value="">Semua Kelas</option>
                                @foreach ($classRooms as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="student_id" class="block text-sm font-medium text-gray-700">
                                Murid
                            </label>

                            <select
                                id="student_id"
                                name="student_id"
                                x-model="selectedStudent"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            >
                                <option value="">Pilih Murid</option>
                                <template x-for="student in filteredStudents" :key="student.id">
                                    <option :value="student.id" x-text="student.name + (student.nis ? ' - ' + student.nis : '') + (student.className ? ' - ' + student.className : '')" :selected="student.id == selectedStudent"></option>
                                </template>
                            </select>

                            @error('student_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="submitted_at" class="block text-sm font-medium text-gray-700">
                                Tanggal Setoran
                            </label>

                            <input
                                id="submitted_at"
                                name="submitted_at"
                                type="date"
                                value="{{ old('submitted_at', $hafalanRecord->submitted_at?->format('Y-m-d')) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            >

                            @error('submitted_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Dynamic Setoran List -->
                    <div class="mt-2 border border-gray-200 rounded-lg p-5 bg-gray-50/50 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase text-gray-600 tracking-wider">
                                Daftar Setoran Hafalan
                            </h3>
                            <button type="button"
                                    @click="hafalans.push({ surah_id: '', ayah_start: '', ayah_end: '', submission_type: 'new', score: '', status: 'passed' })"
                                    class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-semibold shadow-sm transition-colors cursor-pointer">
                                + Tambah Baris Setoran
                            </button>
                        </div>

                        <div class="space-y-4">
                            <template x-for="(item, index) in hafalans" :key="index">
                                <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm relative space-y-4">
                                    <div class="flex items-center justify-between border-b pb-2">
                                        <span class="text-xs font-bold text-gray-500" x-text="'Setoran #' + (index + 1)"></span>
                                        <button type="button"
                                                @click="if (hafalans.length > 1) { hafalans.splice(index, 1); } else { item.surah_id = ''; item.ayah_start = ''; item.ayah_end = ''; item.submission_type = 'new'; item.score = ''; item.status = 'passed'; }"
                                                class="text-xs text-red-600 hover:text-red-800 font-medium cursor-pointer">
                                            Hapus
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-4">
                                        <div class="col-span-1 md:col-span-2">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Surah</label>
                                            <select :name="'surah_ids['+index+']'" x-model="item.surah_id"
                                                    class="block w-full rounded-md border-gray-300 text-xs" required>
                                                <option value="">Pilih Surah</option>
                                                @foreach ($surahs as $surah)
                                                    <option value="{{ $surah->id }}">{{ $surah->number }}. {{ $surah->name_latin }} — {{ $surah->total_ayah }} ayat</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Ayat Mulai</label>
                                            <input type="number" min="1" :name="'ayah_starts['+index+']'" x-model="item.ayah_start"
                                                   class="block w-full rounded-md border-gray-300 text-xs" required>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Ayat Akhir</label>
                                            <input type="number" min="1" :name="'ayah_ends['+index+']'" x-model="item.ayah_end"
                                                   class="block w-full rounded-md border-gray-300 text-xs" required>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Jenis Setoran</label>
                                            <select :name="'submission_types['+index+']'" x-model="item.submission_type"
                                                    class="block w-full rounded-md border-gray-300 text-xs" required>
                                                <option value="new">Baru</option>
                                                <option value="continuation">Lanjutan</option>
                                                <option value="revision">Perbaikan</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Nilai (Skala A - E)</label>
                                            <select :name="'scores['+index+']'" x-model="item.score"
                                                    class="block w-full rounded-md border-gray-300 text-xs">
                                                <option value="">Pilih Nilai</option>
                                                <option value="95">A (Sangat Baik)</option>
                                                <option value="85">B (Baik)</option>
                                                <option value="75">C (Cukup)</option>
                                                <option value="65">D (Kurang)</option>
                                                <option value="55">E (Sangat Kurang)</option>
                                            </select>
                                        </div>

                                        <div class="col-span-1 md:col-span-2">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Status Setoran</label>
                                            <select :name="'statuses['+index+']'" x-model="item.status"
                                                    class="block w-full rounded-md border-gray-300 text-xs" required>
                                                <option value="passed">Lulus</option>
                                                <option value="repeat">Ulang</option>
                                                <option value="needs_improvement">Perlu Perbaikan</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="mt-2">
                        <label for="notes" class="block text-sm font-medium text-gray-700">
                            Catatan Guru (Berlaku untuk semua setoran di atas)
                        </label>

                        <textarea
                            id="notes"
                            name="notes"
                            rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        >{{ old('notes', $hafalanRecord->notes) }}</textarea>

                        @error('notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('hafalan-records.index') }}" class="text-sm text-gray-600 hover:underline">
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                        >
                            Update Setoran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
