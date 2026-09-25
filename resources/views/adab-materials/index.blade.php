<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold leading-tight text-gray-900 dark:text-white">
                    Materi Halaqoh Adab
                </h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-zinc-400">
                    Kumpulan berkas materi dan panduan untuk kegiatan halaqoh adab murid.
                </p>
            </div>

            @if($canManage)
                <a href="{{ route('adab-materials.create') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700 transition">
                    <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Materi
                </a>
            @endif
        </div>
    </x-slot>

    @php
        $formatBytes = function($bytes) {
            if (!$bytes || $bytes <= 0) return '0 B';
            $k = 1024;
            $sizes = ['B', 'KB', 'MB', 'GB'];
            $i = floor(log($bytes) / log($k));
            return number_format($bytes / pow($k, $i), 1) . ' ' . $sizes[$i];
        };
    @endphp

    <div class="py-8" x-data="{
        openModal: false,
        previewUrl: '',
        previewTitle: '',
        previewType: 'pdf',
        previewFileName: '',
        downloadUrl: '',

        showPreview(url, title, extension, fileName, downloadUrl) {
            this.previewUrl = url;
            this.previewTitle = title;
            this.previewFileName = fileName || '';
            this.downloadUrl = downloadUrl || url;

            const ext = (extension || '').toLowerCase();
            if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext)) {
                this.previewType = 'image';
            } else if (['mp4', 'webm', 'ogg'].includes(ext)) {
                this.previewType = 'video';
            } else if (['mp3', 'wav', 'aac'].includes(ext)) {
                this.previewType = 'audio';
            } else if (['doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx'].includes(ext)) {
                this.previewType = 'doc';
            } else {
                this.previewType = 'pdf';
            }
            this.openModal = true;
        }
    }">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 dark:bg-green-950/20 dark:border-green-800/30 px-4 py-3 text-sm font-medium text-green-700 dark:text-green-400 flex items-center gap-2">
                    <svg class="h-5 w-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            <!-- Filter / Search Section -->
            <div class="rounded-2xl border border-gray-250 dark:border-zinc-800 bg-white dark:bg-zinc-900/50 p-5 shadow-sm">
                <form method="GET" action="{{ route('adab-materials.index') }}" class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <input type="text"
                               name="q"
                               value="{{ request('q') }}"
                               placeholder="Cari berdasarkan judul atau deskripsi materi..."
                               x-on:input.debounce.600ms="$el.form.submit()"
                               class="w-full rounded-xl border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:ring-indigo-500 focus:border-indigo-500 text-gray-900 dark:text-white"
                        />
                    </div>
                    <div class="flex gap-2">
                        <button type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-gray-900 dark:bg-zinc-800 px-5 py-2 text-sm font-semibold text-white hover:bg-gray-800 dark:hover:bg-zinc-700 transition">
                            Cari
                        </button>
                        @if(request('q'))
                            <a href="{{ route('adab-materials.index') }}"
                               class="inline-flex items-center justify-center rounded-xl border border-gray-300 dark:border-zinc-700 px-5 py-2 text-sm font-semibold text-gray-700 dark:text-zinc-300 hover:bg-gray-50 dark:hover:bg-zinc-800 transition">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Grid Cards -->
            @if($materials->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($materials as $material)
                        @php
                            $extension = strtolower(pathinfo($material->file_path ?? '', PATHINFO_EXTENSION));
                            $fileUrl = $material->file_path ? asset('storage/' . $material->file_path) : null;
                        @endphp
                        <div class="group relative flex flex-col justify-between rounded-2xl border border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-5 shadow-sm hover:shadow-md transition">
                            <div class="space-y-3">
                                <!-- Heading Title -->
                                <h3 class="font-bold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition text-base">
                                    {{ $material->title }}
                                </h3>

                                <!-- Description -->
                                @if($material->description)
                                    <p class="text-sm text-gray-600 dark:text-zinc-400 line-clamp-3 leading-relaxed">
                                        {{ $material->description }}
                                    </p>
                                @else
                                    <p class="text-sm text-gray-400 dark:text-zinc-600 italic">
                                        Tidak ada deskripsi tambahan.
                                    </p>
                                @endif
                            </div>

                            <!-- Metadata & Actions -->
                            <div class="mt-6 space-y-4 pt-4 border-t border-gray-100 dark:border-zinc-800/80">
                                @if($material->file_path)
                                    <!-- File Info -->
                                    <div class="flex items-center gap-2.5 bg-gray-50 dark:bg-zinc-800/50 p-2.5 rounded-xl border border-gray-100 dark:border-zinc-800">
                                        @if(in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif']))
                                            <svg class="h-8 w-8 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        @elseif(in_array($extension, ['mp4', 'webm', 'ogg', 'mp3', 'wav']))
                                            <svg class="h-8 w-8 text-purple-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @else
                                            <svg class="h-8 w-8 text-red-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            </svg>
                                        @endif
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-semibold text-gray-700 dark:text-zinc-300 truncate">
                                                {{ $material->file_name }}
                                            </p>
                                            <p class="text-[10px] text-gray-400 dark:text-zinc-500 font-mono">
                                                {{ $formatBytes($material->file_size) }}
                                            </p>
                                        </div>
                                    </div>
                                @endif

                                @if($material->url_link)
                                    <!-- Link Info -->
                                    <a href="{{ $material->url_link }}" target="_blank" rel="noopener noreferrer"
                                       class="flex items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                        Buka Link Eksternal
                                    </a>
                                @endif

                                <!-- Creator & Date -->
                                <div class="flex items-center justify-between text-[11px] text-gray-400 dark:text-zinc-500">
                                    <span>Oleh: <strong class="text-gray-600 dark:text-zinc-400 font-semibold">{{ $material->creator?->name ?? 'Sistem' }}</strong></span>
                                    <span>{{ $material->created_at?->format('d/m/Y') }}</span>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center gap-2">
                                    @if($material->file_path)
                                        <button @click="showPreview('{{ $fileUrl }}', '{{ e($material->title) }}', '{{ $extension }}', '{{ e($material->file_name) }}', '{{ $fileUrl }}')"
                                                type="button"
                                                class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-xl bg-indigo-600 text-white px-3.5 py-2 text-xs font-bold hover:bg-indigo-700 shadow-sm transition">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            Lihat Materi
                                        </button>

                                        <a href="{{ $fileUrl }}" download="{{ $material->file_name }}"
                                           title="Unduh Berkas"
                                           class="inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-zinc-800 text-gray-700 dark:text-zinc-300 p-2 hover:bg-gray-100 dark:hover:bg-zinc-800 transition">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                            </svg>
                                        </a>
                                    @endif

                                    @if($canManage)
                                        <a href="{{ route('adab-materials.edit', $material) }}"
                                           title="Edit Materi"
                                           class="inline-flex items-center justify-center rounded-xl border border-gray-200 dark:border-zinc-800 text-gray-700 dark:text-zinc-300 p-2 hover:bg-gray-50 dark:hover:bg-zinc-800 transition">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('adab-materials.destroy', $material) }}"
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus materi adab ini? Berkas juga akan terhapus dari sistem.');"
                                              class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    title="Hapus Materi"
                                                    class="inline-flex items-center justify-center rounded-xl border border-red-200 dark:border-red-950 text-red-600 p-2 hover:bg-red-50 dark:hover:bg-red-950/20 transition">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Paginator -->
                <div class="mt-6">
                    {{ $materials->links() }}
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-gray-300 dark:border-zinc-800 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-zinc-650" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <h3 class="mt-4 text-sm font-bold text-gray-900 dark:text-white">Belum Ada Materi Adab</h3>
                    <p class="mt-2 text-xs text-gray-500 dark:text-zinc-400 max-w-sm mx-auto">
                        Silakan hubungi ustadz/ustadzah pembimbing atau admin untuk mengunggah materi halaqoh adab terbaru.
                    </p>
                </div>
            @endif

        </div>

        <!-- Modal Preview Viewer -->
        <div x-show="openModal"
             x-cloak
             @keydown.escape.window="openModal = false"
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;">
            <!-- Backdrop -->
            <div x-show="openModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="openModal = false"
                 class="fixed inset-0 bg-zinc-900/80 backdrop-blur-sm"></div>

            <!-- Modal Dialog -->
            <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
                <div x-show="openModal"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="relative w-full max-w-5xl rounded-2xl bg-white dark:bg-zinc-900 shadow-2xl overflow-hidden border border-zinc-200 dark:border-zinc-800">

                    <!-- Modal Header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/80">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 flex-shrink-0">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base font-bold text-zinc-900 dark:text-white truncate" x-text="previewTitle"></h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate" x-text="previewFileName"></p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-shrink-0">
                            <a :href="downloadUrl" :download="previewFileName" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 transition">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span>Unduh</span>
                            </a>
                            <a :href="previewUrl" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 transition">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                                <span>Tab Baru</span>
                            </a>
                            <button @click="openModal = false" class="p-2 rounded-xl text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Content Body -->
                    <div class="p-4 bg-zinc-100 dark:bg-zinc-950 flex items-center justify-center min-h-[60vh] max-h-[80vh] overflow-auto">
                        <!-- PDF Viewer -->
                        <template x-if="previewType === 'pdf'">
                            <iframe :src="previewUrl" class="w-full h-[72vh] rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm bg-white"></iframe>
                        </template>

                        <!-- Image Viewer -->
                        <template x-if="previewType === 'image'">
                            <img :src="previewUrl" :alt="previewTitle" class="max-h-[72vh] max-w-full rounded-xl object-contain shadow-md">
                        </template>

                        <!-- Video Viewer -->
                        <template x-if="previewType === 'video'">
                            <video :src="previewUrl" controls class="w-full max-h-[72vh] rounded-xl shadow-md bg-black"></video>
                        </template>

                        <!-- Audio Viewer -->
                        <template x-if="previewType === 'audio'">
                            <div class="w-full max-w-md p-6 bg-white dark:bg-zinc-900 rounded-2xl shadow-md text-center space-y-4">
                                <div class="p-4 rounded-full bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 w-16 h-16 mx-auto flex items-center justify-center">
                                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 .895-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 .895-2 3-2 3 .895 3 2zM9 10l12-3" />
                                    </svg>
                                </div>
                                <audio :src="previewUrl" controls class="w-full"></audio>
                            </div>
                        </template>

                        <!-- Office Document Viewer -->
                        <template x-if="previewType === 'doc'">
                            <div class="w-full h-[72vh] flex flex-col items-center justify-center space-y-4">
                                <iframe :src="'https://docs.google.com/viewer?url=' + encodeURIComponent(previewUrl) + '&embedded=true'" class="w-full h-full rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-sm bg-white"></iframe>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
