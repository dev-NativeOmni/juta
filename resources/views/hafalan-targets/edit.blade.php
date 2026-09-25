<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Target Hafalan
            </h2>
            <p class="text-sm text-gray-500">
                Perbarui target hafalan murid.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-xl bg-white p-6 shadow-sm border border-gray-100">

                @if ($errors->any())
                    <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <div class="font-semibold">Ada input yang belum benar:</div>
                        <ul class="mt-2 list-disc ps-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @php
                    $studentsMapped = $students->map(fn($student) => [
                        'id' => $student->id,
                        'name' => $student->name,
                        'classId' => (string) $student->class_room_id,
                        'className' => $student->classRoom?->name ?? '',
                        'teacherName' => $student->teacher?->user?->name ?? ''
                    ]);
                @endphp

                <script>
                    window.allStudents = @json($studentsMapped);
                    window.surahDetails = {
                        @foreach ($surahs as $surah)
                            '{{ $surah->id }}': { id: {{ $surah->id }}, number: {{ $surah->number }}, totalAyah: {{ $surah->total_ayah }}, name: '{{ addslashes($surah->name_latin) }}' },
                        @endforeach
                    };
                </script>

                <form method="POST" action="{{ route('hafalan-targets.update', $target) }}" class="space-y-6" x-data="{
                    selectedClass: '',
                    selectedStudent: '{{ old('student_id', $target->student_id) }}',
                    selectedSurah: '{{ old('surah_id', $target->surah_id) }}',
                    allStudents: window.allStudents || [],
                    surahDetails: window.surahDetails || {},
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

                    <div>
                        <label for="class_room_filter" class="block text-sm font-medium text-gray-700">Saring Berdasarkan Kelas</label>
                        <select id="class_room_filter"
                                x-model="selectedClass"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Semua Kelas</option>
                            @foreach ($classRooms as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Murid</label>
                        <div x-data="{ open: false, search: '' }" @click.outside="open = false" class="relative mt-1">
                            <button type="button" 
                                    @click="open = !open" 
                                    class="flex items-center justify-between w-full rounded-lg border border-gray-300 bg-white text-left text-sm px-3 py-2 text-gray-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                                <span x-text="selectedStudent && allStudents.find(s => s.id == selectedStudent) ? allStudents.find(s => s.id == selectedStudent).name + (allStudents.find(s => s.id == selectedStudent).className ? ' — ' + allStudents.find(s => s.id == selectedStudent).className : '') + (allStudents.find(s => s.id == selectedStudent).teacherName ? ' — Guru: ' + allStudents.find(s => s.id == selectedStudent).teacherName : '') : 'Pilih murid'"></span>
                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            
                            <div x-show="open" 
                                 class="absolute z-50 mt-1 w-full bg-white rounded-md shadow-lg border border-gray-200"
                                 style="display: none;">
                                <div class="p-2 border-b border-gray-200 bg-gray-50">
                                    <input type="text" 
                                           x-model="search" 
                                           placeholder="Cari nama murid..." 
                                           class="w-full rounded border border-gray-300 bg-transparent text-xs px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <ul class="max-h-[180px] overflow-y-auto py-1 text-xs">
                                    <template x-for="student in filteredStudents.filter(s => s.name.toLowerCase().includes(search.toLowerCase()))" :key="student.id">
                                        <li @click="selectedStudent = student.id; open = false; search = ''" 
                                            class="px-3 py-2 hover:bg-indigo-600 hover:text-white cursor-pointer transition-colors"
                                            x-text="student.name + (student.className ? ' — ' + student.className : '') + (student.teacherName ? ' — Guru: ' + student.teacherName : '')">
                                        </li>
                                    </template>
                                    <li x-show="filteredStudents.filter(s => s.name.toLowerCase().includes(search.toLowerCase())).length === 0" 
                                        class="px-3 py-2 text-gray-500 text-center">
                                        Murid tidak ditemukan
                                    </li>
                                </ul>
                            </div>
                            <input type="hidden" name="student_id" x-model="selectedStudent" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Surah</label>
                        <div x-data="{ open: false, search: '' }" @click.outside="open = false" class="relative mt-1">
                            <button type="button" 
                                    @click="open = !open" 
                                    class="flex items-center justify-between w-full rounded-lg border border-gray-300 bg-white text-left text-sm px-3 py-2 text-gray-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                                <span x-text="selectedSurah && surahDetails[selectedSurah] ? surahDetails[selectedSurah].number + '. ' + surahDetails[selectedSurah].name + ' — ' + surahDetails[selectedSurah].totalAyah + ' ayat' : 'Pilih surah'"></span>
                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            
                            <div x-show="open" 
                                 class="absolute z-50 mt-1 w-full bg-white rounded-md shadow-lg border border-gray-200"
                                 style="display: none;">
                                <div class="p-2 border-b border-gray-200 bg-gray-50">
                                    <input type="text" 
                                           x-model="search" 
                                           placeholder="Cari nama surat..." 
                                           class="w-full rounded border border-gray-300 bg-transparent text-xs px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                                <ul class="max-h-[180px] overflow-y-auto py-1 text-xs">
                                    <template x-for="surah in Object.values(surahDetails).filter(s => s.name.toLowerCase().includes(search.toLowerCase()))" :key="surah.id">
                                        <li @click="selectedSurah = surah.id; $nextTick(() => { const el = document.getElementById('surah_id'); el.dispatchEvent(new Event('change')); }); open = false; search = ''" 
                                            class="px-3 py-2 hover:bg-indigo-600 hover:text-white cursor-pointer transition-colors"
                                            x-text="surah.number + '. ' + surah.name + ' — ' + surah.totalAyah + ' ayat'">
                                        </li>
                                    </template>
                                    <li x-show="Object.values(surahDetails).filter(s => s.name.toLowerCase().includes(search.toLowerCase())).length === 0" 
                                        class="px-3 py-2 text-gray-500 text-center">
                                        Surah tidak ditemukan
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <select id="surah_id" name="surah_id" x-model="selectedSurah" required data-surah-select class="hidden">
                            <option value="">Pilih surah</option>
                            @foreach ($surahs as $surah)
                                <option value="{{ $surah->id }}"
                                        data-total-ayah="{{ $surah->total_ayah }}">
                                    {{ $surah->number }}. {{ $surah->name_latin }} — {{ $surah->total_ayah }} ayat
                                </option>
                            @endforeach
                        </select>
                        <p data-total-ayah-label class="mt-1 text-xs text-gray-500">
                            Pilih surah untuk melihat batas ayat.
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Ayat (hafal dari awal surah sampai ayat ini)</label>
                        <input type="number" name="ayah" value="{{ old('ayah', $target->ayah) }}"
                               min="1" required data-ayah
                               class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tanggal Target</label>
                            <input type="date" name="target_date"
                                   value="{{ old('target_date', $target->target_date?->format('Y-m-d')) }}"
                                   required class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" required class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                                <option value="active" @selected(old('status', $target->status) === 'active')>Aktif</option>
                                <option value="completed" @selected(old('status', $target->status) === 'completed')>Selesai</option>
                                <option value="missed" @selected(old('status', $target->status) === 'missed')>Terlewat</option>
                                <option value="cancelled" @selected(old('status', $target->status) === 'cancelled')>Dibatalkan</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Catatan</label>
                        <textarea name="notes" rows="4"
                                  class="mt-1 w-full rounded-lg border-gray-300 text-sm">{{ old('notes', $target->notes) }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-6">
                        <a href="{{ route('hafalan-targets.index') }}"
                           class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Batal
                        </a>

                        <button type="submit"
                                class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const surahSelect = document.querySelector('[data-surah-select]');
            const ayah = document.querySelector('[data-ayah]');
            const totalLabel = document.querySelector('[data-total-ayah-label]');

            function syncAyahLimit() {
                const selectedOption = surahSelect.options[surahSelect.selectedIndex];
                const totalAyah = selectedOption ? selectedOption.dataset.totalAyah : '';

                if (!totalAyah) {
                    totalLabel.textContent = 'Pilih surah untuk melihat batas ayat.';
                    ayah.removeAttribute('max');
                    return;
                }

                ayah.setAttribute('max', totalAyah);
                totalLabel.textContent = 'Maksimal ' + totalAyah + ' ayat untuk surah ini.';
            }

            surahSelect.addEventListener('change', syncAyahLimit);
            syncAyahLimit();
        });
    </script>
</x-app-layout>