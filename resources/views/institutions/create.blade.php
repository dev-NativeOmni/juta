<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('institutions.index') }}" class="p-2 rounded-xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 transition">
                    <x-heroicon-m-arrow-left class="w-5 h-5" />
                </a>
                <div>
                    <h2 class="font-bold text-2xl text-zinc-900 dark:text-white leading-tight">
                        Tambah Lembaga Baru
                    </h2>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-0.5">
                        Daftarkan sekolah atau cabang pesantren baru ke dalam sistem multi-tenancy.
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('institutions.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <!-- Card 1: Identitas Lembaga -->
                <div class="p-6 bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 shadow-xs space-y-4">
                    <h3 class="font-bold text-base text-zinc-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-identification class="w-5 h-5 text-teal-600 dark:text-teal-400" />
                        <span>Identitas Lembaga & Akses Gerbang</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                        <div class="md:col-span-2">
                            <label for="name" class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">
                                Nama Lembaga / Sekolah <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="{{ old('name') }}"
                                required
                                placeholder="Contoh: SMAIT Al-Hikmah Solo"
                                class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/60 text-sm text-zinc-900 dark:text-zinc-100 focus:border-teal-500 focus:ring-teal-500"
                            >
                            @error('name')
                                <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="code" class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">
                                Kode Gerbang Sekolah <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="code"
                                name="code"
                                value="{{ old('code') }}"
                                required
                                placeholder="Contoh: ALHIKMAH01"
                                class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/60 text-sm font-mono uppercase text-zinc-900 dark:text-zinc-100 focus:border-teal-500 focus:ring-teal-500"
                            >
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1">
                                Kode unik yang dimasukkan santri/guru di halaman portal gerbang.
                            </p>
                            @error('code')
                                <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="slug" class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">
                                Slug URL (Opsional)
                            </label>
                            <input
                                type="text"
                                id="slug"
                                name="slug"
                                value="{{ old('slug') }}"
                                placeholder="Contoh: al-hikmah-solo"
                                class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/60 text-sm font-mono text-zinc-900 dark:text-zinc-100 focus:border-teal-500 focus:ring-teal-500"
                            >
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1">
                                Jika kosong, akan digenerate otomatis dari nama lembaga.
                            </p>
                            @error('slug')
                                <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Card 2: Kontak & Alamat -->
                <div class="p-6 bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 shadow-xs space-y-4">
                    <h3 class="font-bold text-base text-zinc-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-envelope class="w-5 h-5 text-teal-600 dark:text-teal-400" />
                        <span>Kontak & Lokasi</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                        <div>
                            <label for="email" class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">
                                Email Lembaga
                            </label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="admin@sekolah.sch.id"
                                class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/60 text-sm text-zinc-900 dark:text-zinc-100 focus:border-teal-500 focus:ring-teal-500"
                            >
                            @error('email')
                                <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phone" class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">
                                Nomor Telepon / WA
                            </label>
                            <input
                                type="text"
                                id="phone"
                                name="phone"
                                value="{{ old('phone') }}"
                                placeholder="081234567890"
                                class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/60 text-sm text-zinc-900 dark:text-zinc-100 focus:border-teal-500 focus:ring-teal-500"
                            >
                            @error('phone')
                                <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="address" class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">
                                Alamat Lengkap
                            </label>
                            <textarea
                                id="address"
                                name="address"
                                rows="3"
                                placeholder="Jl. Pendidikan No. 123, Solo..."
                                class="w-full rounded-xl border-zinc-300 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-800/60 text-sm text-zinc-900 dark:text-zinc-100 focus:border-teal-500 focus:ring-teal-500"
                            >{{ old('address') }}</textarea>
                            @error('address')
                                <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Card 3: Branding & Tampilan -->
                <div class="p-6 bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 shadow-xs space-y-4">
                    <h3 class="font-bold text-base text-zinc-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-photo class="w-5 h-5 text-teal-600 dark:text-teal-400" />
                        <span>Kustomisasi Branding Portal</span>
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                        <div>
                            <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">
                                Logo Lembaga (PNG / SVG)
                            </label>
                            <input
                                type="file"
                                name="logo"
                                accept="image/png,image/jpeg,image/svg+xml,image/webp"
                                class="w-full text-xs text-zinc-500 dark:text-zinc-400 file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-700 dark:file:bg-teal-950 dark:file:text-teal-300 hover:file:bg-teal-100"
                            >
                            <p class="text-[10px] text-zinc-400 mt-1">Maks. 2MB. Format persegi disarankan.</p>
                            @error('logo')
                                <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">
                                Background Login
                            </label>
                            <input
                                type="file"
                                name="login_bg"
                                accept="image/png,image/jpeg,image/webp"
                                class="w-full text-xs text-zinc-500 dark:text-zinc-400 file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-700 dark:file:bg-teal-950 dark:file:text-teal-300 hover:file:bg-teal-100"
                            >
                            <p class="text-[10px] text-zinc-400 mt-1">Maks. 4MB. Tampil di sebelah kiri login.</p>
                            @error('login_bg')
                                <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">
                                Background Landing
                            </label>
                            <input
                                type="file"
                                name="landing_bg"
                                accept="image/png,image/jpeg,image/webp"
                                class="w-full text-xs text-zinc-500 dark:text-zinc-400 file:mr-2 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-teal-50 file:text-teal-700 dark:file:bg-teal-950 dark:file:text-teal-300 hover:file:bg-teal-100"
                            >
                            <p class="text-[10px] text-zinc-400 mt-1">Maks. 4MB. Background cover landing page.</p>
                            @error('landing_bg')
                                <p class="text-xs text-rose-500 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Card 4: Kontrol Fitur & Modul Aktif -->
                <div class="p-6 bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 shadow-xs space-y-4">
                    <div>
                        <h3 class="font-bold text-base text-zinc-900 dark:text-white flex items-center gap-2">
                            <x-heroicon-o-squares-plus class="w-5 h-5 text-teal-600 dark:text-teal-400" />
                            <span>Kontrol Fitur & Modul Lembaga</span>
                        </h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                            Pilih fitur-fitur yang diaktifkan khusus untuk lembaga ini. Modul nonaktif akan otomatis disembunyikan dari sidebar & navigasi sekolah.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2">
                        @foreach (\App\Models\Institution::AVAILABLE_FEATURES as $fKey => $fMeta)
                            <label class="flex items-start gap-3 p-3.5 rounded-xl border border-zinc-200/80 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-800/40 hover:bg-zinc-100/60 dark:hover:bg-zinc-800/70 transition cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    name="features[{{ $fKey }}]"
                                    value="1"
                                    {{ old("features.{$fKey}", $fMeta['default'] ? '1' : '0') === '1' ? 'checked' : '' }}
                                    class="mt-1 w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shrink-0"
                                >
                                <div class="min-w-0 flex-1">
                                    <span class="block font-bold text-xs text-zinc-900 dark:text-white">{{ $fMeta['name'] }}</span>
                                    <span class="block text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5 leading-snug">{{ $fMeta['description'] }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Status & Submit -->
                <div class="p-6 bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200/80 dark:border-zinc-800 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-4">
                    <label class="flex items-center gap-3 cursor-pointer select-none">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            {{ old('is_active', '1') === '1' ? 'checked' : '' }}
                            class="w-4 h-4 rounded text-teal-600 focus:ring-teal-500 border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800"
                        >
                        <div>
                            <span class="text-sm font-bold text-zinc-900 dark:text-white">Lembaga Aktif</span>
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">Izinkan portal dan login pengguna untuk lembaga ini.</p>
                        </div>
                    </label>

                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <a
                            href="{{ route('institutions.index') }}"
                            class="w-full sm:w-auto text-center px-4 py-2.5 rounded-xl border border-zinc-300 dark:border-zinc-700 text-xs font-bold text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition"
                        >
                            Batal
                        </a>
                        <button
                            type="submit"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-xs font-bold text-white shadow-sm transition cursor-pointer"
                        >
                            <x-heroicon-m-check class="w-4 h-4 stroke-[2.5]" />
                            <span>Simpan Lembaga</span>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</x-app-layout>
