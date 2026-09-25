@php
    $formId = 'ummi-inline-edit-'.$record->id;
@endphp

<form
    id="{{ $formId }}"
    method="POST"
    action="{{ route('ummi-records.update', $record) }}"
    x-data="{
        ummiHafalans: [
            @forelse ($record->surahs as $surahEntry)
                { surah_id: '{{ $surahEntry->surah_id }}', ayah: '{{ addslashes($surahEntry->hafalan_ayah ?? '') }}', baris: '{{ $surahEntry->baris ?? '' }}' },
            @empty
                { surah_id: '', ayah: '', baris: '' },
            @endforelse
        ]
    }"
    class="space-y-3"
>
    @csrf
    @method('PUT')
    <input type="hidden" name="student_id" value="{{ $record->student_id }}">

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div>
            <label class="block text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Tanggal</label>
            <input type="date" name="tanggal" value="{{ $record->tanggal?->format('Y-m-d') }}" required
                   class="w-full rounded-md border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 dark:text-white text-xs">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Tatap Muka</label>
            <input type="number" name="tatap_muka" min="1" value="{{ $record->tatap_muka }}"
                   class="w-full rounded-md border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 dark:text-white text-xs">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Jilid</label>
            <select name="ummi_jilid" class="w-full rounded-md border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 dark:text-white text-xs">
                <option value="">Pilih Jilid</option>
                @foreach(['Jilid 1', 'Jilid 2', 'Jilid 3', 'Al-Qur\'an', 'Ghoroib', 'Tajwid'] as $jilid)
                    <option value="{{ $jilid }}" @selected($record->ummi_jilid === $jilid)>{{ $jilid }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Halaman</label>
            <input type="text" name="ummi_halaman" value="{{ $record->ummi_halaman }}" placeholder="Contoh: 15-18"
                   class="w-full rounded-md border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 dark:text-white text-xs">
        </div>
        <div class="col-span-2">
            <label class="block text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Materi</label>
            <input type="text" name="materi" value="{{ $record->materi }}" placeholder="Materi yang dipelajari"
                   class="w-full rounded-md border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 dark:text-white text-xs">
        </div>
        <div>
            <label class="block text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Nilai</label>
            <select name="nilai" class="w-full rounded-md border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 dark:text-white text-xs">
                <option value="">Pilih Nilai</option>
                @foreach(['A+', 'A', 'B+', 'B', 'B-', 'C+', 'C', 'D'] as $n)
                    <option value="{{ $n }}" @selected($record->nilai === $n)>{{ $n }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Disimak Guru</label>
            <select name="disimak_guru" required class="w-full rounded-md border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 dark:text-white text-xs">
                <option value="Ya" @selected($record->disimak_guru === 'Ya')>Ya</option>
                <option value="Tidak" @selected($record->disimak_guru === 'Tidak')>Tidak</option>
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Disimak Ortu</label>
            <select name="disimak_ortu" required class="w-full rounded-md border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 dark:text-white text-xs">
                <option value="Tidak" @selected($record->disimak_ortu === 'Tidak')>Tidak</option>
                <option value="Ya" @selected($record->disimak_ortu === 'Ya')>Ya</option>
            </select>
        </div>
    </div>

    <div class="space-y-2 border border-zinc-200 dark:border-zinc-800 rounded-lg p-3 bg-white dark:bg-zinc-900">
        <div class="flex items-center justify-between">
            <span class="text-[11px] font-bold uppercase text-zinc-500 dark:text-zinc-400">Setoran Hafalan UMMI</span>
            <button type="button"
                    @click="ummiHafalans.push({ surah_id: '', ayah: '', baris: '' })"
                    class="inline-flex items-center px-2 py-1 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 rounded text-[11px] font-semibold hover:bg-emerald-100 cursor-pointer">
                + Tambah Surah
            </button>
        </div>

        <template x-for="(item, index) in ummiHafalans" :key="index">
            <div class="grid grid-cols-12 gap-2 items-end">
                <div class="col-span-6">
                    <select :name="'hafalan_surah_ids['+index+']'" x-model="item.surah_id"
                            class="w-full rounded-md border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 dark:text-white text-xs">
                        <option value="">Pilih Surah</option>
                        @foreach ($surahs as $surah)
                            <option value="{{ $surah->id }}">{{ $surah->number }}. {{ $surah->name_latin }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-3">
                    <input type="text" :name="'hafalan_ayahs['+index+']'" x-model="item.ayah" placeholder="Contoh: 1-10"
                           class="w-full rounded-md border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 dark:text-white text-xs">
                </div>
                <div class="col-span-2">
                    <input type="number" step="0.1" min="0" :name="'hafalan_baris['+index+']'" x-model="item.baris" placeholder="Baris"
                           class="w-full rounded-md border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 dark:text-white text-xs">
                </div>
                <div class="col-span-1 text-right">
                    <button type="button"
                            @click="if (ummiHafalans.length > 1) { ummiHafalans.splice(index, 1) } else { item.surah_id = ''; item.ayah = ''; item.baris = ''; }"
                            class="text-rose-600 hover:text-rose-700 dark:text-rose-400 text-sm font-bold cursor-pointer">
                        &times;
                    </button>
                </div>
            </div>
        </template>
    </div>

    <div>
        <label class="block text-[11px] font-bold text-zinc-500 dark:text-zinc-400 uppercase mb-1">Catatan Tambahan</label>
        <textarea name="catatan" rows="2" class="w-full rounded-md border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 dark:text-white text-xs">{{ $record->keterangan }}</textarea>
    </div>

    <div class="flex items-center justify-end gap-2 pt-1">
        <button type="button" @click="editing = false"
                class="px-3 py-1.5 text-xs font-semibold text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition cursor-pointer">
            Batal
        </button>
        <button type="submit"
                class="px-4 py-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow transition cursor-pointer">
            Simpan Perubahan
        </button>
    </div>
</form>
