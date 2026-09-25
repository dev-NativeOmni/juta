<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                Pengaturan Kuisioner Adab
            </h2>
            <p class="text-sm text-gray-600 dark:text-zinc-400">
                Kelola kategori adab dan butir pertanyaan kuisioner (dapat ditambah/dikurangi, minimal 1 kategori).
            </p>
        </div>
    </x-slot>

    <div class="py-8" x-data="adabSettingsForm(@js($categories))">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800 dark:bg-emerald-950/40 dark:border-emerald-800/60 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 dark:bg-red-950/40 dark:border-red-800/60 dark:text-red-300">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('settings.adab.update') }}" class="space-y-6">
                @csrf

                <template x-for="(cat, catIdx) in categories" :key="catIdx">
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-sm overflow-hidden mb-6">
                        {{-- Header Kategori --}}
                        <div class="border-b border-gray-200 dark:border-zinc-800 px-6 py-4 bg-gray-50/50 dark:bg-[#09090b]/40 space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center justify-center h-7 w-7 rounded-full bg-indigo-100 dark:bg-indigo-950/40 text-sm font-bold text-indigo-700 dark:text-indigo-400" x-text="catIdx + 1"></span>
                                    <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400" x-text="'Kategori ' + (catIdx + 1)"></span>
                                </div>
                                <button type="button" @click="removeCategory(catIdx)" x-show="categories.length > 1" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Hapus Kategori
                                </button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-550 dark:text-zinc-400">Nama Kategori</label>
                                    <input
                                        type="text"
                                        :name="'categories[' + catIdx + '][title]'"
                                        x-model="cat.title"
                                        placeholder="Misal: Adab Kepada Allah"
                                        class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm font-bold"
                                        required
                                    />
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-550 dark:text-zinc-400">Deskripsi Kategori</label>
                                    <input
                                        type="text"
                                        :name="'categories[' + catIdx + '][desc]'"
                                        x-model="cat.desc"
                                        placeholder="Deskripsi singkat kategori..."
                                        class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                        required
                                    />
                                </div>
                            </div>
                        </div>

                        {{-- Pertanyaan --}}
                        <div class="p-6 space-y-4">
                            <div class="flex items-center justify-between border-b pb-2">
                                <h4 class="text-xs font-bold text-gray-400 dark:text-zinc-550 uppercase tracking-widest">Butir Pertanyaan Kuisioner</h4>
                                <button type="button" @click="addQuestion(catIdx)" class="inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                    + Tambah Pertanyaan
                                </button>
                            </div>
                            <div class="space-y-3">
                                <template x-for="(qText, qIdx) in cat.questions" :key="qIdx">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex items-center justify-center h-7 w-7 rounded-full bg-indigo-50 dark:bg-indigo-950/40 text-xs font-bold text-indigo-600 dark:text-indigo-400 shrink-0" x-text="qIdx + 1"></span>
                                        <div class="flex-1">
                                            <input
                                                type="text"
                                                :name="'categories[' + catIdx + '][questions][' + qIdx + ']'"
                                                x-model="cat.questions[qIdx]"
                                                placeholder="Tuliskan butir pertanyaan..."
                                                class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                                required
                                            />
                                        </div>
                                        <button type="button" @click="removeQuestion(catIdx, qIdx)" x-show="cat.questions.length > 1" class="text-gray-400 hover:text-rose-600 transition-colors p-1" title="Hapus Pertanyaan">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Tombol Tambah Kategori Baru --}}
                <div class="flex justify-center">
                    <button type="button" @click="addCategory()" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border-2 border-dashed border-indigo-300 dark:border-indigo-800 text-sm font-bold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/30 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Kategori Adab Baru
                    </button>
                </div>

                {{-- Keterangan Nilai & Formula Penilaian --}}
                <div class="bg-indigo-50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/30 rounded-xl px-6 py-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-700 dark:text-indigo-400 flex items-center gap-1.5">
                            <x-heroicon-o-chart-bar class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                            <span>Formula Logika Penilaian Adab Terpadu</span>
                        </h4>
                        <span class="text-xs font-bold bg-indigo-200 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 px-2.5 py-1 rounded-full">Kerajinan 40% + Pendamping 60%</span>
                    </div>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400">
                        Penilaian adab murid dihitung dari <strong>Kerajinan Pengisian Kuisioner (40%)</strong> pada Hari Kerja Efektif (Senin-Jumat, menyesuaikan tanggal merah) dan <strong>Nilai Pendamping Adab (60%)</strong>.
                    </p>
                    <div class="grid grid-cols-2 xs:grid-cols-3 sm:grid-cols-5 gap-3 text-center text-xs pt-2">
                        @foreach (['A'=>['90–100%','bg-emerald-100 text-emerald-700'], 'B'=>['80–89%','bg-teal-100 text-teal-700'], 'C'=>['70–79%','bg-amber-100 text-amber-700'], 'D'=>['60–69%','bg-orange-100 text-orange-700'], 'E'=>['0–59%','bg-rose-100 text-rose-700']] as $g => [$range, $cls])
                            <div class="rounded-lg p-3 {{ $cls }} dark:opacity-80">
                                <div class="text-2xl font-black">{{ $g }}</div>
                                <div class="font-semibold mt-1">{{ $range }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Tombol Simpan --}}
                <div class="pt-4 border-t border-gray-200 dark:border-zinc-800 flex justify-end gap-3">
                    <a href="{{ route('adab.index') }}" class="inline-flex items-center justify-center px-5 py-3 border border-gray-300 dark:border-zinc-700 rounded-xl text-sm font-semibold text-gray-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md hover:shadow-lg transition-all duration-150">
                        Simpan Semua Pengaturan Adab
                    </button>
                </div>
            </form>

        </div>
    </div>

    <script>
        function adabSettingsForm(initialCategories) {
            return {
                categories: Array.isArray(initialCategories) && initialCategories.length > 0 ? initialCategories : [
                    {
                        title: 'Adab Utama',
                        desc: 'Pengembangan karakter dan kebiasaan adab harian',
                        questions: [
                            'Apakah Anda melaksanakan ibadah dan pembiasaan adab tepat waktu?',
                            'Apakah Anda bersikap santun dan hormat?',
                        ]
                    }
                ],
                addCategory() {
                    this.categories.push({
                        title: 'Kategori Adab Baru',
                        desc: 'Deskripsi kategori adab baru',
                        questions: [
                            'Apakah Anda melaksanakan pembiasaan adab ini dengan konsisten?',
                        ]
                    });
                },
                removeCategory(idx) {
                    if (this.categories.length > 1) {
                        this.categories.splice(idx, 1);
                    }
                },
                addQuestion(catIdx) {
                    if (this.categories[catIdx]) {
                        this.categories[catIdx].questions.push('');
                    }
                },
                removeQuestion(catIdx, qIdx) {
                    if (this.categories[catIdx] && this.categories[catIdx].questions.length > 1) {
                        this.categories[catIdx].questions.splice(qIdx, 1);
                    }
                }
            };
        }
    </script>
</x-app-layout>
