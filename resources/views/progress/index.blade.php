<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Progress Murid
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Rekap progres hafalan berdasarkan ayat lulus, murajaah, target aktif, dan target terlambat.
                </p>
            </div>

            <div class="flex items-center gap-2">
                @if (auth()->user()?->hasAnyRole(['super_admin', 'admin', 'supervisor', 'coordinator_tahfizh']) && Route::has('settings.hafalan-targets'))
                    <a href="{{ route('settings.hafalan-targets') }}"
                       class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-emerald-300 bg-emerald-50 px-3.5 py-2 text-sm font-semibold text-emerald-800 shadow-sm hover:bg-emerald-100 transition">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Atur Target Progres
                    </a>
                @endif

                @if (Route::has('reports.index'))
                    <a href="{{ route('reports.index') }}"
                       class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                        Buka Laporan
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-3 sm:py-6">
        <div class="mx-auto max-w-7xl space-y-3.5 sm:space-y-5 px-2.5 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 px-3.5 py-2.5 text-xs sm:text-sm font-medium text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if(!auth()->user()->hasAnyRole(['parent', 'student']))
                <div class="rounded-xl sm:rounded-2xl border border-gray-200 bg-white p-3.5 sm:p-5 shadow-xs">
                    <form method="GET" action="{{ route('progress.index') }}" class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-5">
                        <div>
                            <label for="q" class="mb-1 block text-xs sm:text-sm font-semibold text-gray-700">
                                Cari
                            </label>
                            <input id="q"
                                   type="text"
                                   name="q"
                                   value="{{ request('q') }}"
                                   placeholder="Nama / nomor murid"
                                   x-on:input.debounce.600ms="$el.form.submit()"
                                   class="w-full rounded-lg border-gray-300 text-xs sm:text-sm text-gray-900 shadow-xs focus:border-emerald-500 focus:ring-emerald-500">
                        </div>

                        <div>
                            <label for="student_id" class="mb-1 block text-xs sm:text-sm font-semibold text-gray-700">
                                Murid
                            </label>
                            <select id="student_id"
                                    name="student_id"
                                    onchange="this.form.submit()"
                                    class="w-full rounded-lg border-gray-300 text-xs sm:text-sm text-gray-900 shadow-xs focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Semua Murid</option>
                                @foreach ($filterStudents as $student)
                                    <option value="{{ $student->id }}" @selected((string) request('student_id') === (string) $student->id)>
                                        {{ $student->name }}
                                        @if ($student->student_number)
                                            — {{ $student->student_number }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="class_room_id" class="mb-1 block text-xs sm:text-sm font-semibold text-gray-700">
                                Kelas
                            </label>
                            <select id="class_room_id"
                                    name="class_room_id"
                                    onchange="this.form.submit()"
                                    class="w-full rounded-lg border-gray-300 text-xs sm:text-sm text-gray-900 shadow-xs focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Semua Kelas</option>
                                @foreach ($classRooms as $classRoom)
                                    <option value="{{ $classRoom->id }}" @selected((string) request('class_room_id') === (string) $classRoom->id)>
                                        {{ $classRoom->name }}
                                        @if ($classRoom->program)
                                            — {{ $classRoom->program->name }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="teacher_id" class="mb-1 block text-xs sm:text-sm font-semibold text-gray-700">
                                Halaqoh / Musyrif
                            </label>
                            <select id="teacher_id"
                                    name="teacher_id"
                                    onchange="this.form.submit()"
                                    class="w-full rounded-lg border-gray-300 text-xs sm:text-sm text-gray-900 shadow-xs focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Semua Halaqoh</option>
                                @foreach ($teachers as $t)
                                    <option value="{{ $t->id }}" @selected((string) request('teacher_id') === (string) $t->id)>
                                        {{ $t->user?->name ?? 'Halaqoh #'.$t->id }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="sort" class="mb-1 block text-xs sm:text-sm font-semibold text-gray-700">
                                Urutkan
                            </label>
                            <select id="sort"
                                    name="sort"
                                    onchange="this.form.submit()"
                                    class="w-full rounded-lg border-gray-300 text-xs sm:text-sm text-gray-900 shadow-xs focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="">Progress Tertinggi</option>
                                <option value="low_progress" @selected(request('sort') === 'low_progress')>Progress Terendah</option>
                                <option value="overdue" @selected(request('sort') === 'overdue')>Target Terlambat</option>
                                <option value="name" @selected(request('sort') === 'name')>Nama Murid</option>
                            </select>
                        </div>

                        <div class="flex items-end gap-2 sm:col-span-2 lg:col-span-1">
                            <button type="submit"
                                    class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-3 py-2 text-xs sm:text-sm font-semibold text-white shadow-xs hover:bg-emerald-700">
                                Filter
                            </button>

                            <a href="{{ route('progress.index') }}"
                               class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs sm:text-sm font-semibold text-gray-700 shadow-xs hover:bg-gray-50">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            @endif

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 sm:gap-4">
                <div class="rounded-xl sm:rounded-2xl border border-gray-200 bg-white p-2.5 sm:p-5 shadow-xs">
                    <p class="text-[10px] sm:text-sm font-medium text-gray-500 truncate">Total Murid</p>
                    <p class="mt-0.5 sm:mt-2 text-base sm:text-3xl font-black text-gray-900">
                        {{ number_format($summary['total_students'] ?? 0) }}
                    </p>
                </div>

                <div class="rounded-xl sm:rounded-2xl border border-gray-200 bg-white p-2.5 sm:p-5 shadow-xs">
                    <p class="text-[10px] sm:text-sm font-medium text-gray-500 truncate">Total Ayat</p>
                    <p class="mt-0.5 sm:mt-2 text-base sm:text-3xl font-black text-gray-900">
                        {{ number_format($summary['total_memorized_ayahs'] ?? 0) }}
                    </p>
                </div>

                <div class="rounded-xl sm:rounded-2xl border border-gray-200 bg-white p-2.5 sm:p-5 shadow-xs">
                    <p class="text-[10px] sm:text-sm font-medium text-gray-500 truncate">Rata-rata</p>
                    <p class="mt-0.5 sm:mt-2 text-base sm:text-3xl font-black text-gray-900">
                        {{ number_format((float) ($summary['average_progress_percent'] ?? 0), 1) }}%
                    </p>
                </div>

                <div class="rounded-xl sm:rounded-2xl border border-gray-200 bg-white p-2.5 sm:p-5 shadow-xs">
                    <p class="text-[10px] sm:text-sm font-medium text-gray-500 truncate">Terlambat</p>
                    <p class="mt-0.5 sm:mt-2 text-base sm:text-3xl font-black text-red-600">
                        {{ number_format($summary['total_overdue_targets'] ?? 0) }}
                    </p>
                </div>
            </div>

            @if ($progressRows->isNotEmpty())
                @if (!empty($isGrade10))
                    <!-- Grade 10 UMMI Card Display -->
                    <div class="space-y-4">
                        <div class="flex justify-between items-center bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 p-4 rounded-2xl shadow-sm flex-wrap gap-3">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                    Laporan Capaian Tahfidz — Pembelajaran UMMI {{ $selectedClass?->name }}
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Tampilan khusus Kelas 10 menyajikan Jilid, Halaman, Capaian Hafalan UMMI, dan Ziyadah.
                                </p>
                            </div>
                            @if (!auth()->user()->hasAnyRole(['student', 'parent']))
                                <div class="flex items-center gap-2">
                                    <button type="button" onclick="downloadUmmiCardProgress()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow transition cursor-pointer">
                                        <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                                        <span>Download Gambar (PNG)</span>
                                    </button>
                                </div>
                            @endif
                        </div>

                        @php
                            $studentReports = $progressRows->map(function($r) {
                                $st = $r['student'] ?? null;
                                $ziyadahName = '-';
                                if ($st instanceof \App\Models\Student) {
                                    $latestPassedHeader = $st->hafalanRecords()
                                        ->whereHas('surahs', fn ($q) => $q->where('status', 'passed'))
                                        ->with(['surahs' => fn ($q) => $q->where('status', 'passed')->with('surah')])
                                        ->latest('submitted_at')
                                        ->first();
                                    $ziyadahName = $latestPassedHeader?->surahs->last()?->surah?->name_latin ?? '-';
                                }
                                return [
                                    'student' => $st,
                                    'ummi_jilid' => $r['ummi_jilid_num'] ?? '-',
                                    'ummi_halaman' => $r['ummi_halaman'] ?? '-',
                                    'ummi_capaian' => ($r['ummi_record'] && $r['ummi_record']->surahs->isNotEmpty())
                                        ? $r['ummi_record']->surahs_label
                                        : ($r['ummi_record']?->materi ?? '-'),
                                    'ziyadah' => $ziyadahName,
                                ];
                            });
                            $monthName = date('F');
                        @endphp

                        @include('reports.partials.ummi-grade10-card')
                    </div>

                    <script src="https://cdnjs.cloudflare.com/ajax/libs/html-to-image/1.11.11/html-to-image.min.js"></script>
                    <script>
                        function downloadUmmiCardProgress() {
                            const cardEl = document.getElementById('ummiGrade10ReportCard');
                            if (!cardEl) {
                                alert('Elemen laporan tidak ditemukan.');
                                return;
                            }
                            const width = cardEl.offsetWidth;
                            const height = cardEl.offsetHeight;
                            htmlToImage.toPng(cardEl, {
                                pixelRatio: 2,
                                backgroundColor: '#ffffff',
                                width: width,
                                height: height,
                                style: { margin: '0', left: '0', top: '0', transform: 'none' }
                            }).then(dataUrl => {
                                const a = document.createElement('a');
                                a.download = 'Laporan_Capaian_Ummi_{{ $selectedClass?->name }}.png';
                                a.href = dataUrl;
                                document.body.appendChild(a);
                                a.click();
                                document.body.removeChild(a);
                            }).catch(err => {
                                console.error('Gagal mengunduh gambar:', err);
                                alert('Gagal mengunduh gambar: ' + err.message);
                            });
                        }
                    </script>
                @else
                    @php
                        $chartData = $progressRows->map(fn($row) => [
                            'name' => $row['student_name'],
                            'target' => $row['total_targets'],
                            'realized' => $row['completed_targets'],
                        ])->values();
                        $chartHeight = max(220, $progressRows->count() * 45);
                    @endphp
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="mb-4 border-b border-gray-150 pb-3 flex justify-between items-center flex-wrap gap-2">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">
                                    Diagram Progres Hafalan Murid
                                    @if (request('class_room_id'))
                                        - {{ $classRooms->firstWhere('id', request('class_room_id'))?->name }}
                                    @endif
                                    @if (request('teacher_id'))
                                        (Halaqoh {{ $teachers->firstWhere('id', request('teacher_id'))?->user?->name }})
                                    @endif
                                </h3>
                                <p class="text-xs text-gray-550 mt-1">
                                    Membandingkan jumlah target hafalan yang direncanakan dengan target yang telah terealisasi (selesai).
                                </p>
                            </div>
                            <div class="flex items-center gap-4 text-xs font-semibold">
                                <span class="flex items-center gap-1.5 text-zinc-500">
                                    <span class="w-3.5 h-3.5 bg-indigo-500 rounded"></span> Target Setoran
                                </span>
                                <span class="flex items-center gap-1.5 text-zinc-500">
                                    <span class="w-3.5 h-3.5 bg-emerald-500 rounded"></span> Terealisasi (Lulus)
                                </span>
                                @if (!auth()->user()->hasAnyRole(['student', 'parent']))
                                    <button type="button" onclick="downloadChartWithTitle()" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow transition inline-flex items-center gap-1.5 cursor-pointer">
                                        <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5" />
                                        <span>Download Grafik (PNG)</span>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="relative w-full overflow-x-auto" style="height: {{ $chartHeight }}px;">
                            <canvas id="progressChart"></canvas>
                        </div>
                    </div>
                @endif
            @endif

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-900">
                        Daftar Progress Murid
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Klik detail untuk melihat timeline dan progres per surah.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Murid</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Kelas</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Progress</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Hafalan</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Murajaah</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Target</th>
                                <th class="px-5 py-3 text-right font-semibold text-gray-600">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($progressRows as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-4 align-top">
                                        <div class="font-semibold text-gray-900">
                                            {{ $row['student_name'] }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $row['student_number'] ?? '-' }}
                                        </div>
                                    </td>

                                    <td class="px-5 py-4 align-top">
                                        <div class="text-gray-900">
                                            {{ $row['class_room_name'] ?? '-' }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $row['program_name'] ?? '-' }}
                                        </div>
                                    </td>

                                    <td class="px-5 py-4 align-top">
                                        <div class="mb-1 flex items-center justify-between gap-3">
                                            <span class="font-semibold text-gray-900">
                                                {{ number_format((float) ($row['target_progress_percent'] ?? $row['progress_percent']), 2) }}%
                                                <span class="text-[10px] font-medium text-gray-500">(Target {{ $row['target_juz_count'] ?? 2 }} Juz)</span>
                                            </span>
                                            <span class="text-xs text-gray-500">
                                                {{ number_format($row['memorized_ayahs']) }} / {{ number_format($row['target_quran_ayahs'] ?? 416) }} ayat
                                            </span>
                                        </div>

                                        <div class="mb-1.5 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                            {{ $row['completed_juz_count'] }} Juz Terlampaui ({{ $row['completed_juz_list'] }})
                                        </div>

                                        <div class="h-2.5 w-56 overflow-hidden rounded-full bg-gray-100">
                                            <div class="h-2.5 rounded-full bg-emerald-600"
                                                 style="width: {{ min(100, max(0, (float) ($row['target_progress_percent'] ?? $row['progress_percent']))) }}%"></div>
                                        </div>
                                    </td>

                                    <td class="px-5 py-4 align-top">
                                        <div class="font-medium text-gray-900">
                                            {{ number_format($row['total_hafalan_records']) }} setoran
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Rata-rata nilai: {{ number_format((float) $row['average_hafalan_score'], 2) }}
                                        </div>
                                    </td>

                                    <td class="px-5 py-4 align-top">
                                        <div class="font-medium text-gray-900">
                                            {{ number_format($row['total_murajaah_records']) }} murajaah
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Rata-rata nilai: {{ number_format((float) $row['average_murajaah_score'], 2) }}
                                        </div>
                                    </td>

                                    <td class="px-5 py-4 align-top">
                                        <div class="font-medium text-gray-900">
                                            Aktif: {{ number_format($row['active_targets']) }}
                                        </div>

                                        @if (($row['overdue_targets'] ?? 0) > 0)
                                            <div class="text-xs font-semibold text-red-600">
                                                Terlambat: {{ number_format($row['overdue_targets']) }}
                                            </div>
                                        @else
                                            <div class="text-xs text-gray-500">
                                                Tidak ada overdue
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-5 py-4 text-right align-top">
                                        <a href="{{ route('progress.show', $row['student_id']) }}"
                                           class="btn-action-detail">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-10 text-center text-gray-500">
                                        Belum ada data progress yang sesuai filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    @if ($progressRows->isNotEmpty())
        @push('scripts')
        <script>
            if (typeof Chart === 'undefined') {
                const s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                document.head.appendChild(s);
            }

            function downloadChartWithTitle() {
                const originalCanvas = document.getElementById('progressChart');
                if (!originalCanvas) return;

                const tempCanvas = document.createElement('canvas');
                const ctx = tempCanvas.getContext('2d');

                const titleText = "Diagram Progres Hafalan Murid";
                const subTitleText = "TAD Management System (Tahfizh, Adab, Disiplin) — Tanggal Ekspor: " + new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });

                const bannerHeight = 80;
                tempCanvas.width = originalCanvas.width;
                tempCanvas.height = originalCanvas.height + bannerHeight;

                ctx.fillStyle = '#FFFFFF';
                ctx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);

                ctx.textAlign = 'center';
                ctx.fillStyle = '#111827';
                ctx.font = 'bold 18px Inter, sans-serif';
                ctx.fillText(titleText, tempCanvas.width / 2, 35);

                ctx.fillStyle = '#6B7280';
                ctx.font = '13px Inter, sans-serif';
                ctx.fillText("TAD-SMAIA7", tempCanvas.width / 2, 58);

                ctx.strokeStyle = '#E5E7EB';
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.moveTo(20, 72);
                ctx.lineTo(tempCanvas.width - 20, 72);
                ctx.stroke();

                ctx.drawImage(originalCanvas, 0, bannerHeight);

                const a = document.createElement('a');
                a.download = 'Grafik_Perkembangan_Hafalan.png';
                a.href = tempCanvas.toDataURL('image/png');
                a.click();
            }
            window.downloadChartWithTitle = downloadChartWithTitle;

            function initProgressChart() {
                if (typeof Chart === 'undefined') {
                    setTimeout(initProgressChart, 100);
                    return;
                }

                const canvasEl = document.getElementById('progressChart');
                if (!canvasEl) return;

                const ctx = canvasEl.getContext('2d');
                const studentsData = @json($chartData ?? []);

                const labels = studentsData.map(item => item.name);
                const targets = studentsData.map(item => item.target);
                const realized = studentsData.map(item => item.realized);

                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Target Setoran',
                                data: targets,
                                backgroundColor: 'rgba(99, 102, 241, 0.85)',
                                borderColor: 'rgb(99, 102, 241)',
                                borderWidth: 1,
                                borderRadius: 4,
                                barThickness: 12,
                            },
                            {
                                label: 'Terealisasi (Lulus)',
                                data: realized,
                                backgroundColor: 'rgba(16, 185, 129, 0.85)',
                                borderColor: 'rgb(16, 185, 129)',
                                borderWidth: 1,
                                borderRadius: 4,
                                barThickness: 12,
                            }
                        ]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                padding: 10,
                                cornerRadius: 8,
                                callbacks: {
                                    label: function(context) {
                                        return ` ${context.dataset.label}: ${context.raw} Target`;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { color: 'rgba(243, 244, 246, 1)', drawBorder: false },
                                ticks: { precision: 0, font: { family: 'Inter, sans-serif', size: 11 } },
                                title: { display: true, text: 'Jumlah Target Setoran', font: { family: 'Inter, sans-serif', size: 11, weight: '600' } }
                            },
                            y: {
                                grid: { display: false },
                                ticks: { font: { family: 'Inter, sans-serif', size: 11, weight: '500' } }
                            }
                        }
                    }
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initProgressChart);
            } else {
                initProgressChart();
            }
        </script>
        @endpush
    @endif
</x-app-layout>