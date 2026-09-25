<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-155 leading-tight">
                    Pembuat Laporan Harian WhatsApp
                </h2>
                <p class="text-sm text-gray-600 dark:text-zinc-400">
                    Kompilasi setoran harian kelas halaqoh secara otomatis untuk dibagikan langsung ke grup WhatsApp.
                </p>
            </div>
            <a href="{{ route('reports.index') }}" class="no-print inline-flex items-center gap-1.5 rounded-xl bg-gray-150 dark:bg-zinc-850 hover:bg-gray-250 dark:hover:bg-zinc-750 px-4 py-2.5 text-sm font-semibold text-gray-700 dark:text-zinc-300 transition duration-150">
                <x-heroicon-m-arrow-left class="w-4 h-4 shrink-0" />
                <span>Kembali ke Laporan</span>
            </a>
        </div>
    </x-slot>

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto space-y-5 sm:space-y-6">

            <!-- Filter Kelas & Tanggal -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-4 sm:p-5 shadow-sm">
                <form method="GET" action="{{ route('reports.whatsapp') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 items-end">
                    <!-- Classroom Selector -->
                    <div>
                        <label for="class_room_id" class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Kelas Halaqoh</label>
                        <select name="class_room_id" id="class_room_id" onchange="this.form.submit()" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm">
                            @foreach ($classRooms as $class)
                                <option value="{{ $class->id }}" {{ $selectedClassId == $class->id ? 'selected' : '' }}>{{ $class->name }} ({{ $class->program?->name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Date Selector -->
                    <div>
                        <label for="date" class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Tanggal Laporan</label>
                        <input type="date" name="date" id="date" value="{{ $selectedDate }}" onchange="this.form.submit()" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm">
                    </div>

                    <!-- Submit Button -->
                    <div class="col-span-1 sm:col-span-2 lg:col-span-1">
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 border border-transparent rounded-xl text-sm font-semibold text-white bg-teal-600 hover:bg-teal-700 active:scale-95 shadow-sm transition-all duration-150 min-h-[42px]">
                            Muat Data Setoran
                        </button>
                    </div>
                </form>
            </div>

            @if ($selectedClass)
                @php
                    $indonesianDate = \Carbon\Carbon::parse($selectedDate)->translatedFormat('l, j F Y');
                @endphp
                <script>
                    window.whatsappReportConfig = {
                        layout: @json($hasUmmiRecords ? 'ummi' : 'tahfidz'),
                        className: @json($selectedClass ? $selectedClass->name : ''),
                        musyrifName: @json($musyrifName),
                        selectedDateFormatted: @json($indonesianDate),
                        classUmmiJilid: @json($classUmmiJilid),
                        classUmmiHalaman: @json($classUmmiHalaman),
                        classUmmiHafalanSurah: @json($classUmmiHafalanSurah),
                        students: @json($students)
                    };
                </script>
                <!-- Main Generator Workspace -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6"
                     x-data="whatsappReport(window.whatsappReportConfig)">
                    
                    <!-- Left: Controls & Student Status Editor -->
                    <div class="lg:col-span-7 space-y-6">
                        
                        <!-- Layout & Header Settings -->
                        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm space-y-4">
                            <h3 class="font-bold text-gray-900 dark:text-white text-sm border-b dark:border-zinc-800 pb-2">Pengaturan Layout Laporan</h3>
                            
                            <!-- Layout Switcher Buttons -->
                            <div class="flex items-center gap-2 p-1 bg-gray-105 dark:bg-zinc-800 rounded-xl">
                                <button type="button"
                                        @click="layout = 'tahfidz'"
                                        :class="layout === 'tahfidz' ? 'bg-white dark:bg-zinc-900 text-gray-905 dark:text-white shadow-sm font-semibold' : 'text-gray-500 hover:text-gray-900'"
                                        class="flex-1 px-4 py-2.5 text-xs rounded-lg transition-all duration-150 inline-flex items-center justify-center gap-1.5 cursor-pointer">
                                    <x-heroicon-o-book-open class="w-4 h-4" />
                                    <span>Tahfidz Reguler</span>
                                </button>
                                <button type="button"
                                        @click="layout = 'ummi'"
                                        :class="layout === 'ummi' ? 'bg-white dark:bg-zinc-900 text-gray-905 dark:text-white shadow-sm font-semibold' : 'text-gray-500 hover:text-gray-900'"
                                        class="flex-1 px-4 py-2.5 text-xs rounded-lg transition-all duration-150 inline-flex items-center justify-center gap-1.5 cursor-pointer">
                                    <x-heroicon-o-sparkles class="w-4 h-4 text-emerald-500" />
                                    <span>Metode UMMI</span>
                                </button>
                            </div>

                            <!-- UMMI Specific Global Settings (Conditional) -->
                            <div x-show="layout === 'ummi'" class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2" style="display: none;">
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Ummi Jilid</label>
                                    <input type="text" x-model="classUmmiJilid" placeholder="e.g. Jilid 1" class="block w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white text-xs py-2 px-3 focus:ring-teal-500 focus:border-teal-500">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Halaman</label>
                                    <input type="text" x-model="classUmmiHalaman" placeholder="e.g. 21-23" class="block w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white text-xs py-2 px-3 focus:ring-teal-500 focus:border-teal-500">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Klasikal Surat</label>
                                    <input type="text" x-model="classUmmiHafalanSurah" placeholder="e.g. Al-Zalzalah" class="block w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white text-xs py-2 px-3 focus:ring-teal-500 focus:border-teal-500">
                                </div>
                            </div>
                        </div>

                        <!-- Students List -->
                        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
                            <div class="px-5 py-4 border-b border-gray-150 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                                <h3 class="font-bold text-gray-900 dark:text-white text-sm">Status & Catatan Setoran Murid</h3>
                                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">Status setoran siswa untuk laporan hari ini.</p>
                            </div>

                            <div class="divide-y divide-gray-150 dark:divide-zinc-800 max-h-[500px] overflow-y-auto">
                                <template x-for="(student, index) in students" :key="student.id">
                                    <div class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-gray-50 dark:hover:bg-zinc-800/30 transition">
                                        <div class="flex items-start gap-3">
                                            <span class="text-xs font-bold text-gray-400 dark:text-zinc-650 mt-0.5" x-text="(index + 1) + '.'"></span>
                                            <div>
                                                <span class="font-bold text-xs text-gray-800 dark:text-zinc-200 block" x-text="student.name"></span>
                                                
                                                <!-- If student has record -->
                                                <div x-show="student.has_record" class="mt-1 flex items-center gap-2">
                                                    <span class="px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 text-[10px] font-extrabold border border-emerald-250 dark:border-emerald-900/30">Setor</span>
                                                    <span class="text-[11px] text-gray-600 dark:text-zinc-400 font-medium" x-text="student.progress"></span>
                                                </div>

                                                <!-- If student does not have record -->
                                                <div x-show="!student.has_record" class="mt-1 flex items-center gap-2" style="display: none;">
                                                    <span class="px-2 py-0.5 rounded-md bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 text-[10px] font-extrabold border border-gray-200 dark:border-zinc-700">Absen / Belum Setor</span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Attendance Status Editor for students with NO record -->
                                        <div x-show="!student.has_record" class="flex items-center gap-2 shrink-0 self-end sm:self-auto" style="display: none;">
                                            <div class="w-28">
                                                <select x-model="student.attendance" class="block w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-850 dark:text-white text-[11px] py-1 pl-2 pr-6 focus:ring-teal-500 focus:border-teal-500">
                                                    <option value="Belum Setor">Belum Setor</option>
                                                    <option value="Izin">Izin (ijin)</option>
                                                    <option value="Sakit">Sakit</option>
                                                    <option value="Alpa">Alpa</option>
                                                </select>
                                            </div>
                                            <div class="w-32 sm:w-40">
                                                <input type="text" x-model="student.customStatus" placeholder="Status kustom" class="block w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-850 dark:text-white text-[11px] py-1 px-2.5 focus:ring-teal-500 focus:border-teal-500">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>

                    <!-- Right: Live Preview -->
                    <div class="lg:col-span-5 space-y-6">
                        
                        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm space-y-4 sticky top-6">
                            <div class="flex items-center justify-between border-b dark:border-zinc-800 pb-2">
                                <h3 class="font-bold text-gray-900 dark:text-white text-sm">Preview Laporan WhatsApp</h3>
                                <span class="px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-450 font-bold text-[10px] border border-emerald-100 dark:border-emerald-900">Live</span>
                            </div>

                            <div class="relative bg-emerald-50/20 dark:bg-[#09090b]/40 rounded-xl p-4 border border-emerald-100/30 dark:border-zinc-800">
                                <textarea readonly
                                          :value="generatedText"
                                          class="w-full h-80 bg-transparent border-0 focus:ring-0 p-0 text-xs font-mono text-gray-800 dark:text-zinc-300 resize-none whitespace-pre-wrap select-all focus:outline-none"></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-3 pt-2">
                                <button type="button"
                                        @click="copyToClipboard()"
                                        style="background-color: #0d9488;"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:opacity-90 transition cursor-pointer">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5A3.375 3.375 0 006.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0015 2.25h-1.5a2.251 2.251 0 00-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V16.5a9 9 0 00-9-9z" />
                                    </svg>
                                    <span>Salin Teks</span>
                                </button>
                                <button type="button"
                                        @click="shareToWhatsApp()"
                                        style="background-color: #25d366;"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:opacity-90 transition cursor-pointer">
                                    <svg viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.724-1.455L0 24zm5.835-3.27c1.649.98 3.264 1.498 4.855 1.5c5.385 0 9.768-4.382 9.771-9.77.001-2.611-1.015-5.067-2.861-6.916C15.8 3.693 13.35 2.678 10.74 2.678c-5.39 0-9.772 4.382-9.775 9.77-.001 1.674.453 3.31 1.313 4.747L1.246 21.1l4.646-1.37zM18.14 14.86c-.3-.15-1.774-.875-2.047-.975-.274-.1-.474-.15-.674.15-.2.3-.774.975-.95 1.175-.175.2-.35.225-.65.075-1.2-.6-1.95-1.05-2.725-2.375-.203-.35.203-.325.58-.1.336-.2.175-.35-.025-.65-.2-.5-.475-1.15-.65-1.575-.175-.425-.35-.35-.475-.35h-.4c-.15 0-.4.05-.6.275-.2.225-.775.75-.775 1.825s.775 2.125.875 2.25c.1.125 1.525 2.325 3.7 3.25.52.22 1.07.36 1.45.42.54.08 1.04.06 1.42.01.43-.06 1.77-.72 2.02-1.42.25-.7.25-1.3.175-1.42-.075-.1-.275-.2-.575-.35z"/>
                                    </svg>
                                    <span>Share WA</span>
                                </button>
                            </div>
                        </div>

                    </div>

                </div>
            @else
                <div class="bg-yellow-50 dark:bg-yellow-950/20 border border-yellow-100 dark:border-yellow-900/30 rounded-2xl p-6 text-center text-yellow-800 dark:text-yellow-400">
                    Belum ada kelas yang dapat Anda akses atau tidak ada data murid terdaftar.
                </div>
            @endif

        </div>
        <script>
        function whatsappReport(config) {
            return {
                layout: config.layout || 'tahfidz',
                className: config.className || '',
                musyrifName: config.musyrifName || '',
                selectedDateFormatted: config.selectedDateFormatted || '',
                classUmmiJilid: config.classUmmiJilid || '',
                classUmmiHalaman: config.classUmmiHalaman || '',
                classUmmiHafalanSurah: config.classUmmiHafalanSurah || '',
                students: [],

                init() {
                    this.students = (config.students || []).map(s => ({
                        ...s,
                        attendance: 'Belum Setor',
                        customStatus: ''
                    }));
                },

                get generatedText() {
                    if (this.layout === 'ummi') {
                        let text = `📝 *LAPORAN TAHFIZH ${this.className.toUpperCase()}*\n`;
                        text += `👤 *Halaqah ${this.musyrifName}*\n`;
                        text += `📅 *${this.selectedDateFormatted}*\n\n`;

                        if (this.classUmmiJilid || this.classUmmiHalaman) {
                            text += `✅ *Ummi Dewasa : ${this.classUmmiJilid} Halaman ${this.classUmmiHalaman}*\n`;
                        } else {
                            text += `✅ *Ummi Dewasa : -*\n`;
                        }

                        if (this.classUmmiHafalanSurah) {
                            text += `✅ *Klasikal Hafalan Ummi : ${this.classUmmiHafalanSurah}*\n`;
                        } else {
                            text += `✅ *Klasikal Hafalan Ummi : -*\n`;
                        }

                        text += `\n✅ *Laporan Setoran Ziyadah*\n`;

                        this.students.forEach((student, index) => {
                            let statusStr = '';
                            if (student.has_record) {
                                statusStr = (student.progress || '').replace(/\/\/ /g, '').replace(/ Baris/g, ' baris');
                            } else if (student.customStatus) {
                                statusStr = student.customStatus;
                            } else {
                                statusStr = student.attendance || 'Belum Setor';
                            }
                            text += `${index + 1}. ${student.name} : ${statusStr}\n`;
                        });

                        text += `\nNB : Pada Klasikal Hafalan Ummi, semua murid telah menyetorkan hafalan pada surat tersebut dengan Metode Ummi.\n\n`;
                        text += `*Baarakallaahu lanaa bil qur'an* ✨`;
                        return text;
                    } else {
                        let text = `📝 *Laporan Tahfidz ${this.className}*\n`;
                        text += `👤 *Halaqoh ${this.musyrifName}*\n`;
                        text += `📅 *${this.selectedDateFormatted}*\n\n`;

                        this.students.forEach((student, index) => {
                            let statusStr = '';
                            if (student.has_record) {
                                let progress = student.progress || '';
                                statusStr = progress.replace(/\/\/ /g, '').replace(/ Baris/g, ' baris');
                            } else if (student.customStatus) {
                                statusStr = student.customStatus;
                            } else {
                                let att = student.attendance || 'Belum Setor';
                                statusStr = att === 'Izin' ? 'ijin' : att.toLowerCase();
                            }
                            text += `${index + 1}. ${student.name} 👉 ${statusStr}\n`;
                        });

                        text += `\nSemoga Allah beri kemudahan dan kelancaran hafalannya sholih sholihah 🤲`;
                        return text;
                    }
                },

                copyToClipboard() {
                    try {
                        const text = this.generatedText;
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(text).then(() => {
                                alert('Laporan berhasil disalin ke clipboard!');
                            }).catch(err => {
                                this.fallbackCopy(text);
                            });
                        } else {
                            this.fallbackCopy(text);
                        }
                    } catch (e) {
                        alert('Gagal menyalin secara otomatis. Silakan salin teks di kotak preview secara manual.');
                    }
                },

                fallbackCopy(text) {
                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    textarea.style.position = 'fixed';
                    document.body.appendChild(textarea);
                    textarea.select();
                    try {
                        document.execCommand('copy');
                        alert('Laporan berhasil disalin ke clipboard!');
                    } catch (err) {
                        alert('Gagal menyalin secara otomatis. Silakan salin teks di kotak preview secara manual.');
                    }
                    document.body.removeChild(textarea);
                },

                shareToWhatsApp() {
                    try {
                        const text = encodeURIComponent(this.generatedText);
                        const win = window.open(`https://api.whatsapp.com/send?text=${text}`, '_blank');
                        if (!win) {
                            alert('Gagal membuka WhatsApp. Harap izinkan pop-up (pop-up blocker) untuk situs ini dan coba lagi.');
                        }
                    } catch (e) {
                        alert('Gagal membuka WhatsApp secara otomatis.');
                    }
                }
            };
        }
    </script>
</x-app-layout>
