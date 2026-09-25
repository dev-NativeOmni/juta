<x-app-layout>
    <div x-data="badgeManager()" x-init="init()">
        <x-slot name="header">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight flex items-center gap-2">
                        <x-heroicon-o-sparkles class="w-6 h-6 text-indigo-600 dark:text-indigo-400 shrink-0" />
                        <span>Kelola Badge & Gamifikasi Hafalan</span>
                    </h2>
                    <p class="text-sm text-gray-600 dark:text-zinc-400">
                        Atur badge penghargaan, kriteria kelulusan, serta pencapaian hafalan murid.
                    </p>
                </div>
                <div>
                    <button
                        type="button"
                        @click="openCreate()"
                        class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition cursor-pointer"
                    >
                        + Tambah Badge Baru
                    </button>
                </div>
            </div>
        </x-slot>

        <div class="py-8">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                @if ($dbMissing ?? false)
                    <div class="rounded-2xl border border-amber-300 bg-amber-50 p-5 shadow-sm dark:bg-amber-950/40 dark:border-amber-700/60 text-amber-900 dark:text-amber-200 space-y-2">
                        <div class="flex items-center gap-2 font-bold text-base">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0" />
                            <span>Migrasi Database Belum Dijalankan</span>
                        </div>
                        <p class="text-sm">
                            Tabel <code class="bg-amber-200/60 dark:bg-amber-900/60 px-1.5 py-0.5 rounded font-mono text-xs">badges</code> belum ada di database MySQL server Anda. Silakan jalankan perintah migrasi berikut di terminal server:
                        </p>
                        <div class="bg-amber-900 text-amber-100 dark:bg-black/60 dark:text-amber-300 font-mono text-xs p-3 rounded-xl select-all">
                            php artisan migrate --force
                        </div>
                    </div>
                @endif

                @if (session('success'))
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 shadow-sm dark:bg-emerald-950/40 dark:border-emerald-800/60 dark:text-emerald-300">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800 shadow-sm dark:bg-red-950/40 dark:border-red-800/60 dark:text-red-300">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Table Card -->
                <div class="bg-white dark:bg-zinc-900 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-200 dark:border-zinc-800">
                    <div class="p-6 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800">
                            <thead>
                                <tr class="text-left text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                                    <th class="px-4 py-3">Badge / Key</th>
                                    <th class="px-4 py-3">Judul & Deskripsi</th>
                                    <th class="px-4 py-3">Tipe Kriteria</th>
                                    <th class="px-4 py-3">Target</th>
                                    <th class="px-4 py-3">Urutan</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-800 text-sm">
                                <template x-if="loading">
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-zinc-400">
                                            Memuat data badge...
                                        </td>
                                    </tr>
                                </template>

                                <template x-if="!loading && badges.length === 0">
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-zinc-400">
                                            Belum ada badge yang dibuat. Klik "+ Tambah Badge Baru" untuk memulai.
                                        </td>
                                    </tr>
                                </template>

                                <template x-for="badge in badges" :key="badge.id">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition">
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2.5">
                                                <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-100 dark:border-indigo-800/40 flex items-center justify-center text-lg flex-shrink-0" x-text="getIconEmoji(badge.icon)"></div>
                                                <span class="px-2 py-1 bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 font-mono text-xs font-bold rounded-md">
                                                    <span x-text="badge.key"></span>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                                                <span x-text="badge.title"></span>
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-zinc-400" x-text="badge.description || '-'"></div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 dark:bg-zinc-800 text-gray-800 dark:text-zinc-300" x-text="typeLabels[badge.type] || badge.type"></span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap font-semibold">
                                            <span x-text="badge.type === 'completed_juz' ? ('Juz ' + (badge.target_juz || '-')) : badge.target_value"></span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-600 dark:text-zinc-400" x-text="badge.sort_order"></td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <button
                                                @click="toggleActive(badge)"
                                                :class="badge.is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 border-emerald-300' : 'bg-gray-100 text-gray-600 dark:bg-zinc-800 dark:text-zinc-400 border-gray-300'"
                                                class="px-3 py-1 rounded-full text-xs font-bold border transition cursor-pointer"
                                            >
                                                <span x-text="badge.is_active ? 'Aktif' : 'Nonaktif'"></span>
                                            </button>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-right font-medium">
                                            <button @click="openEdit(badge)" class="text-indigo-600 dark:text-indigo-400 hover:underline mr-3 font-semibold cursor-pointer">Edit</button>
                                            <button @click="destroyBadge(badge)" class="text-red-600 dark:text-red-400 hover:underline font-semibold cursor-pointer">Hapus</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal Create / Edit Badge -->
                <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 overflow-y-auto" @keydown.escape.window="closeModal()">
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-2xl max-w-xl w-full p-6 space-y-5" @click.away="closeModal()">
                        <div class="flex items-center justify-between border-b border-gray-100 dark:border-zinc-800 pb-3">
                            <h3 class="text-lg font-extrabold text-gray-900 dark:text-white" x-text="editMode ? 'Edit Badge' : 'Tambah Badge Baru'"></h3>
                            <button @click="closeModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-zinc-300 text-xl font-bold cursor-pointer">&times;</button>
                        </div>

                        <form @submit.prevent="submitForm" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-zinc-300 mb-1">Key Identifier</label>
                                    <input type="text" x-model="form.key" class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm" :readonly="editMode" placeholder="contoh: first_hafalan" required>
                                    <p class="text-[10px] text-gray-500 mt-1">Hanya huruf kecil, angka, dan underscore (_).</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-zinc-300 mb-1">Judul Badge</label>
                                    <input type="text" x-model="form.title" class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm" placeholder="contoh: Hafalan Perdana" required>
                                </div>

                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-zinc-300 mb-1">Deskripsi</label>
                                    <textarea x-model="form.description" rows="2" class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm" placeholder="Penjelasan mengenai syarat mendapatkan badge ini"></textarea>
                                </div>

                                <!-- Visual Icon Picker Grid -->
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-zinc-300 mb-2 flex items-center justify-between">
                                        <span>Pilih Ikon Visual Badge</span>
                                        <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 capitalize">
                                            Ikon Terpilih: <span x-text="getIconEmoji(form.icon) + ' ' + (form.icon || 'trophy')"></span>
                                        </span>
                                    </label>

                                    <div class="grid grid-cols-4 sm:grid-cols-6 gap-2">
                                        <template x-for="item in iconOptions" :key="item.key">
                                            <button
                                                type="button"
                                                @click="form.icon = item.key"
                                                :class="form.icon === item.key ? 'ring-2 ring-indigo-600 bg-indigo-50 dark:bg-indigo-950/80 border-indigo-500 text-indigo-700 dark:text-indigo-300 scale-105 shadow-sm' : 'bg-gray-50 dark:bg-zinc-800/60 border-gray-200 dark:border-zinc-700 text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-800'"
                                                class="flex flex-col items-center justify-center p-2 rounded-xl border transition-all cursor-pointer text-center group"
                                            >
                                                <div class="text-xl mb-0.5" x-text="item.emoji"></div>
                                                <span class="text-[10px] font-medium truncate w-full" x-text="item.label"></span>
                                            </button>
                                        </template>
                                    </div>

                                    <!-- Custom Icon Name Input Fallback -->
                                    <div class="mt-3 flex items-center gap-2">
                                        <span class="text-xs text-gray-500 dark:text-zinc-400">Nama Ikon Custom:</span>
                                        <input type="text" x-model="form.icon" class="flex-1 rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-xs px-3 py-1.5 font-mono" placeholder="trophy, star, crown, dll." required>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-zinc-300 mb-1">Tipe Kriteria</label>
                                    <select x-model="form.type" class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm" required>
                                        <template x-for="(label, key) in typeLabels" :key="key">
                                            <option :value="key" x-text="label"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-zinc-300 mb-1">Target Value</label>
                                    <input type="number" step="0.01" x-model="form.target_value" class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm" required>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-zinc-300 mb-1">Target Juz (Opsional)</label>
                                    <input type="number" x-model="form.target_juz" class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm" placeholder="Isi 1-30 jika tipe Khatam Juz">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-zinc-300 mb-1">Urutan Tampil (Sort Order)</label>
                                    <input type="number" x-model="form.sort_order" class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm" required>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-zinc-800">
                                <button type="button" @click="closeModal()" class="px-4 py-2 rounded-xl border border-gray-300 text-gray-700 dark:text-zinc-300 text-sm font-semibold hover:bg-gray-50 dark:hover:bg-zinc-800 cursor-pointer">
                                    Batal
                                </button>
                                <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 shadow-sm cursor-pointer" x-text="editMode ? 'Simpan Perubahan' : 'Buat Badge'">
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
    function badgeManager() {
        return {
            badges: @json($badges),
            typeLabels: @json($typeLabels),
            loading: false,
            showModal: false,
            editMode: false,
            iconOptions: [
                { key: 'trophy', label: 'Piala', emoji: '🏆' },
                { key: 'star', label: 'Bintang', emoji: '⭐' },
                { key: 'sparkles', label: 'Kilauan', emoji: '✨' },
                { key: 'bolt', label: 'Kilat', emoji: '⚡' },
                { key: 'check-badge', label: 'Lencana', emoji: '🎖️' },
                { key: 'book-open', label: 'Al-Qur\'an', emoji: '📖' },
                { key: 'academic-cap', label: 'Khatam', emoji: '🎓' },
                { key: 'shield-check', label: 'Tertib', emoji: '🛡️' },
                { key: 'arrow-path', label: 'Murajaah', emoji: '🔄' },
                { key: 'crown', label: 'Mahkota', emoji: '👑' },
                { key: 'fire', label: 'Semangat', emoji: '🔥' },
                { key: 'award', label: 'Medali', emoji: '🏅' }
            ],
            form: {
                id: null,
                key: '',
                title: '',
                description: '',
                icon: 'trophy',
                type: 'count_hafalan',
                target_value: 1,
                target_juz: null,
                sort_order: 0
            },

            getIconEmoji(icon) {
                const item = this.iconOptions.find(i => i.key === icon);
                if (item) return item.emoji;
                const extra = {
                    'sun': '☀️',
                    'heart': '❤️'
                };
                return extra[icon] || '🎖️';
            },

            fetchBadges() {
                this.loading = true;
                fetch('{{ route('badges.index') }}', {
                    headers: { 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    this.badges = data.badges || [];
                    this.loading = false;
                })
                .catch(() => {
                    this.loading = false;
                });
            },

            openCreate() {
                this.editMode = false;
                this.form = {
                    id: null,
                    key: '',
                    title: '',
                    description: '',
                    icon: 'trophy',
                    type: 'count_hafalan',
                    target_value: 1,
                    target_juz: null,
                    sort_order: (this.badges.length + 1) * 10
                };
                this.showModal = true;
            },

            openEdit(badge) {
                this.editMode = true;
                this.form = { ...badge };
                this.showModal = true;
            },

            closeModal() {
                this.showModal = false;
            },

            submitForm() {
                const url = this.editMode 
                    ? '{{ route('badges.index') }}/' + this.form.id 
                    : '{{ route('badges.store') }}';
                
                const method = this.editMode ? 'PUT' : 'POST';
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                fetch(url, {
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                })
                .then(r => {
                    if (r.ok) {
                        this.closeModal();
                        this.fetchBadges();
                    } else {
                        return r.json().then(err => alert(err.message || 'Gagal menyimpan badge. Periksa input data.'));
                    }
                })
                .catch(err => {
                    alert('Terjadi kesalahan jaringan.');
                });
            },

            toggleActive(badge) {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                fetch('{{ route('badges.index') }}/' + badge.id + '/toggle', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    }
                })
                .then(r => {
                    if (r.ok) {
                        this.fetchBadges();
                    }
                });
            },

            destroyBadge(badge) {
                if (!confirm('Apakah Anda yakin ingin menghapus badge "' + badge.title + '"?')) {
                    return;
                }
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                fetch('{{ route('badges.index') }}/' + badge.id, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    }
                })
                .then(r => {
                    if (r.ok) {
                        this.fetchBadges();
                    }
                });
            },

            init() {
                this.fetchBadges();
            }
        };
    }
    </script>
</x-app-layout>
