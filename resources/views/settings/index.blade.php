<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                Pengaturan Sistem
            </h2>
            <p class="text-sm text-gray-600 dark:text-zinc-400">
                Kustomisasi tampilan branding instansi dan halaman login.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
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

            <!-- Menu Navigasi Pengaturan Tambahan -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <a href="{{ route('settings.hafalan-targets') }}" class="p-4 rounded-2xl bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm hover:border-emerald-500 dark:hover:border-emerald-500 transition group flex items-start gap-3">
                    <div class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition">
                            Target Progres Hafalan
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">
                            Atur target juz per kelas & program
                        </p>
                    </div>
                </a>

                <a href="{{ route('settings.adab') }}" class="p-4 rounded-2xl bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm hover:border-indigo-500 dark:hover:border-indigo-500 transition group flex items-start gap-3">
                    <div class="p-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                            Kuisioner Adab
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">
                            Kelola pertanyaan adab murid
                        </p>
                    </div>
                </a>

                <a href="{{ route('academic-calendar.index') }}" class="p-4 rounded-2xl bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm hover:border-amber-500 dark:hover:border-amber-500 transition group flex items-start gap-3">
                    <div class="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 group-hover:scale-105 transition-transform">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition">
                            Kalender Akademik
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">
                            Hari libur & kalender belajar
                        </p>
                    </div>
                </a>
            </div>

            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-sm overflow-hidden transition-colors duration-200">
                <div class="border-b border-gray-200 dark:border-zinc-800 px-6 py-4 bg-gray-50/50 dark:bg-[#09090b]/40">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        Kustomisasi Branding & Login Page
                    </h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-zinc-400">
                        Unggah logo, background, dan ubah nama instansi yang tampil pada halaman login dan navigasi utama.
                    </p>
                </div>

                <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="p-6 space-y-6">
                    @csrf

                    <!-- Nama Instansi -->
                    <div class="space-y-2">
                        <label for="nama_instansi" class="block text-sm font-semibold text-gray-700 dark:text-zinc-300">
                            Nama Instansi / Sekolah
                        </label>
                        <input
                            type="text"
                            name="nama_instansi"
                            id="nama_instansi"
                            value="{{ old('nama_instansi', $nama_instansi) }}"
                            placeholder="Contoh: Pondok Pesantren Al-Hikmah"
                            class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        />
                        <p class="text-xs text-gray-500 dark:text-zinc-500">
                            Nama instansi ini akan ditampilkan di bawah teks utama pada halaman login dan di navigasi header.
                        </p>
                    </div>

                    <hr class="border-gray-200 dark:border-zinc-800" />

                    <!-- Custom Logo -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-zinc-300">
                                Logo Instansi
                            </label>
                            <p class="text-xs text-gray-500 dark:text-zinc-500">
                                Rekomendasi gambar PNG transparan beresolusi persegi (misal: 512x512px). Maksimal 2MB.
                            </p>
                        </div>
                        <div class="md:col-span-2 space-y-4">
                            @if ($logo)
                                <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-[#09090b]/20 rounded-xl border border-gray-100 dark:border-zinc-800">
                                    <img src="{{ asset('storage/' . $logo) }}" alt="Logo Instansi" class="w-16 h-16 object-contain rounded-lg bg-white border p-1" />
                                    <div>
                                        <p class="text-xs font-semibold text-gray-900 dark:text-zinc-300">Logo Custom Aktif</p>
                                        <label class="inline-flex items-center mt-1 text-xs text-red-600 hover:text-red-700 cursor-pointer">
                                            <input type="checkbox" name="reset_logo" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500 mr-1.5" />
                                            Hapus & kembali ke Logo Default
                                        </label>
                                    </div>
                                </div>
                            @else
                                <div class="p-4 bg-gray-50 dark:bg-[#09090b]/20 rounded-xl border border-gray-100 dark:border-zinc-800 text-xs text-gray-500 dark:text-zinc-400 flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-lg bg-white dark:bg-[#09090b]/40 border dark:border-zinc-800 flex items-center justify-center text-gray-455 font-bold">
                                        DEF
                                    </div>
                                    <span>Menggunakan logo default TAD (SVG).</span>
                                </div>
                            @endif

                            <input
                                type="file"
                                name="logo"
                                accept="image/*"
                                class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 file:cursor-pointer hover:file:bg-indigo-100 dark:file:bg-zinc-800 dark:file:text-zinc-200"
                            />
                        </div>
                    </div>

                    <hr class="border-gray-200 dark:border-zinc-800" />

                    <!-- Custom Background -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-zinc-300">
                                Background Login
                            </label>
                            <p class="text-xs text-gray-500 dark:text-zinc-500">
                                Gambar background halaman login. Rekomendasi gambar horizontal (misal: 1920x1080px). Maksimal 5MB.
                            </p>
                        </div>
                        <div class="md:col-span-2 space-y-4">
                            @if ($login_bg)
                                <div class="flex flex-col gap-3 p-4 bg-gray-50 dark:bg-[#09090b]/20 rounded-xl border border-gray-100 dark:border-zinc-800">
                                    <div class="w-full h-32 rounded-lg overflow-hidden border dark:border-zinc-800 bg-gray-200">
                                        <img src="{{ asset('storage/' . $login_bg) }}" alt="Background Login" class="w-full h-full object-cover" />
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-semibold text-gray-900 dark:text-zinc-300">Background Custom Aktif</span>
                                        <label class="inline-flex items-center text-xs text-red-600 hover:text-red-700 cursor-pointer">
                                            <input type="checkbox" name="reset_login_bg" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500 mr-1.5" />
                                            Hapus & kembali ke Background Default
                                        </label>
                                    </div>
                                </div>
                            @else
                                <div class="p-4 bg-gray-50 dark:bg-[#09090b]/20 rounded-xl border border-gray-100 dark:border-zinc-800 text-xs text-gray-500 dark:text-zinc-400 flex items-center gap-3">
                                    <div class="w-16 h-10 rounded bg-gradient-to-r from-slate-900 to-indigo-950 border dark:border-zinc-800"></div>
                                    <span>Menggunakan background default (Slate & Indigo Gradient).</span>
                                </div>
                            @endif

                            <input
                                type="file"
                                name="login_bg"
                                accept="image/*"
                                class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 file:cursor-pointer hover:file:bg-indigo-100 dark:file:bg-zinc-800 dark:file:text-zinc-200"
                            />
                        </div>
                    </div>

                    <hr class="border-gray-200 dark:border-zinc-800" />

                    <!-- Custom Background Landing Page -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-gray-700 dark:text-zinc-300">
                                Background Landing Page (Hero)
                            </label>
                            <p class="text-xs text-gray-500 dark:text-zinc-500 leading-relaxed">
                                Gambar panorama / foto gedung instansi yang tampil di section atas landing page.
                                <br><strong class="font-medium text-gray-700 dark:text-zinc-400">Ukuran Rekomendasi:</strong> 1920 × 1080 px (16:9 Landscape) atau 2560 × 1440 px. Format JPG / WebP terkompresi (Maksimal 5MB).
                            </p>
                        </div>
                        <div class="md:col-span-2 space-y-4">
                            @if ($landing_bg)
                                <div class="flex flex-col gap-3 p-4 bg-gray-50 dark:bg-[#09090b]/20 rounded-xl border border-gray-100 dark:border-zinc-800">
                                    <div class="w-full h-36 rounded-lg overflow-hidden border dark:border-zinc-800 bg-gray-200">
                                        <img src="{{ asset('storage/' . $landing_bg) }}" alt="Background Landing Page" class="w-full h-full object-cover" />
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-semibold text-gray-900 dark:text-zinc-300">Background Landing Page Custom Aktif</span>
                                        <label class="inline-flex items-center text-xs text-red-600 hover:text-red-700 cursor-pointer">
                                            <input type="checkbox" name="reset_landing_bg" value="1" class="rounded border-gray-300 text-red-600 focus:ring-red-500 mr-1.5" />
                                            Hapus & kembali ke Background Default
                                        </label>
                                    </div>
                                </div>
                            @else
                                <div class="flex flex-col gap-3 p-4 bg-gray-50 dark:bg-[#09090b]/20 rounded-xl border border-gray-100 dark:border-zinc-800">
                                    <div class="w-full h-36 rounded-lg overflow-hidden border dark:border-zinc-800 bg-gray-200">
                                        <img src="{{ asset('images/school_sunset_bg.jpg') }}" alt="Background Landing Page Default" class="w-full h-full object-cover" />
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-zinc-400">
                                        Menggunakan background default gedung/sunset (school_sunset_bg.jpg).
                                    </div>
                                </div>
                            @endif

                            <input
                                type="file"
                                name="landing_bg"
                                accept="image/*"
                                class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 file:cursor-pointer hover:file:bg-indigo-100 dark:file:bg-zinc-800 dark:file:text-zinc-200"
                            />
                        </div>
                    </div>

                    <hr class="border-gray-200 dark:border-zinc-800" />

                    <!-- Tanda Tangan Pejabat (rapor & Laporan Triwulan) -->
                    <div class="space-y-4">
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">Tanda Tangan Pejabat</h4>
                            <p class="text-xs text-gray-500 dark:text-zinc-500 mt-1">
                                Dipakai di Rapor Digital (cetak) dan Laporan Triwulan (.xlsx). Nama & NIK diambil dari
                                <a href="{{ route('digital-reports.settings') }}" class="text-indigo-600 hover:underline">Pengaturan Rapor</a>.
                                Gunakan PNG berlatar transparan (tinta hitam/biru), maksimal 1MB. Berkas tersimpan privat, tidak bisa dibuka lewat URL.
                            </p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach ($officials as $key => $official)
                                <div class="p-4 rounded-xl border border-gray-200 dark:border-zinc-800 bg-gray-50/50 dark:bg-[#09090b]/20 space-y-3">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400">{{ $official['label'] }}</p>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $official['name'] }}</p>
                                    </div>
                                    <div class="h-20 rounded-lg bg-white border border-dashed border-gray-300 dark:border-zinc-700 flex items-center justify-center overflow-hidden">
                                        @if ($official['preview'])
                                            <img src="{{ $official['preview'] }}" alt="Tanda tangan {{ $official['label'] }}" class="max-h-full max-w-full object-contain">
                                        @else
                                            <span class="text-xs text-gray-400">Belum ada tanda tangan</span>
                                        @endif
                                    </div>
                                    <input type="file" name="signatures[{{ $key }}]" accept="image/png,image/jpeg,image/webp"
                                           class="block w-full text-xs text-zinc-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 file:cursor-pointer hover:file:bg-indigo-100 dark:file:bg-zinc-800 dark:file:text-zinc-200" />
                                    @if ($official['preview'])
                                        <label class="inline-flex items-center text-xs text-red-600 hover:text-red-700 cursor-pointer">
                                            <input type="checkbox" name="reset_signatures[]" value="{{ $key }}" class="rounded border-gray-300 text-red-600 focus:ring-red-500 mr-1.5" />
                                            Hapus tanda tangan
                                        </label>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @error('signatures.*')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Actions -->
                    <div class="pt-4 border-t border-gray-200 dark:border-zinc-800 flex justify-end gap-3">
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-4 py-2.5 border border-gray-300 dark:border-zinc-700 rounded-xl text-sm font-semibold text-gray-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                            Batal
                        </a>
                        <button type="submit" class="inline-flex items-center justify-center px-4 py-2.5 border border-transparent rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-colors">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
