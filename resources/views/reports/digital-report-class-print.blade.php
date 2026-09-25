<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Masal Rapor Digital Kelas - {{ $classRoom->name }}</title>
    @vite(['resources/css/app.css'])
    <!-- Tailwind CSS fallback for standalone print -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: 215mm 330mm;
            margin: 10mm 12mm 10mm 12mm;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                color: black !important;
                margin: 0 !important;
                padding: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .page-break {
                page-break-before: always;
                break-before: page;
            }
            .print-container {
                min-height: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .signature-block, .report-section, tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
        body {
            font-family: 'Times New Roman', 'Liberation Serif', serif;
        }
        .report-table {
            border-collapse: collapse;
            width: 100%;
        }
        .report-table th, .report-table td {
            border: 1px solid #000;
        }
    </style>
</head>
<body class="bg-zinc-100 text-gray-900 p-4 sm:p-8" x-data="{ paperSize: 'f4' }" :class="{ 'max-w-[215mm]': paperSize === 'f4', 'max-w-[210mm]': paperSize === 'a4' }">
    @php
        // Tanda tangan pejabat (Pengaturan Umum); dihitung sekali untuk seluruh halaman.
        $signatureUris = collect(\App\Support\Signatures::OFFICIALS)
            ->map(fn ($official, $key) => \App\Support\Signatures::dataUri(\App\Support\Signatures::officialFile($key)))
            ->all();
    @endphp

    <!-- Floating Action Toolbar for bulk print preview (hidden during print) -->
    <div class="max-w-4xl mx-auto mb-6 flex flex-wrap justify-between items-center gap-3 no-print bg-white/95 dark:bg-zinc-900/95 backdrop-blur-md p-4 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-lg">
        <div class="flex items-center gap-3">
            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-pulse"></span>
            <div>
                <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                    <x-heroicon-o-document-text class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                    <span>Cetak Masal Rapor Kelas: {{ $classRoom->name }}</span>
                </h4>
                <p class="text-xs text-gray-500 dark:text-zinc-400">
                    Total: {{ count($reportsData) }} Santri &bull; Tahun Ajaran {{ $academicYear }} (Sem. {{ $semester }})
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Paper Size Selector -->
            <div class="flex items-center bg-gray-100 dark:bg-zinc-800 p-1 rounded-xl text-xs font-semibold">
                <button type="button" @click="paperSize = 'f4'" :class="paperSize === 'f4' ? 'bg-white dark:bg-zinc-700 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-gray-500 hover:text-gray-700'" class="px-2.5 py-1 rounded-lg transition">
                    F4 (Folio)
                </button>
                <button type="button" @click="paperSize = 'a4'" :class="paperSize === 'a4' ? 'bg-white dark:bg-zinc-700 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-gray-500 hover:text-gray-700'" class="px-2.5 py-1 rounded-lg transition">
                    A4
                </button>
            </div>

            <button onclick="window.close()" class="px-3.5 py-2 border border-gray-200 dark:border-zinc-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 hover:bg-gray-50 transition cursor-pointer">
                Tutup
            </button>
            <button onclick="window.print()" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-600/20 transition flex items-center gap-1.5 cursor-pointer">
                <x-heroicon-o-printer class="w-4 h-4" />
                <span>Cetak Semua Rapor</span>
            </button>
        </div>
    </div>

    @foreach ($reportsData as $index => $data)
        @php
            $student = $data['student'];
            $academicYear = $data['academicYear'];
            $semester = $data['semester'];
            $progress = $data['progress'];
            $hafalanRecords = $data['hafalanRecords'];
            $murajaahRecords = $data['murajaahRecords'];
            $targetRecords = $data['targetRecords'];
            $tahfizhScore = $data['tahfizhScore'];
            $tahfizhLevelLabel = $data['tahfizhLevelLabel'];
            $termTargetText = $data['termTargetText'];
            $latestCapaianText = $data['latestCapaianText'];
            $latestCapaianNotes = $data['latestCapaianNotes'];
            $latestUmmiJilid = $data['latestUmmiJilid'] ?? null;
            $latestUmmiHalaman = $data['latestUmmiHalaman'] ?? null;
            $latestUmmiSurahEntry = $data['latestUmmiSurahEntry'] ?? null;
            $latestJuz30Hafalan = $data['latestJuz30Hafalan'] ?? null;
            $adabCategories = $data['adabCategories'] ?? \App\Models\Setting::getAdabQuestions();
            $adabCategoryScores = $data['adabCategoryScores'] ?? [];
            $avgAttendanceRate = $data['avgAttendanceRate'] ?? 0;
            $avgMentorScore = $data['avgMentorScore'] ?? null;
            $avgTotal = $data['avgTotal'] ?? 0;
            $adabGrade = $data['adabGrade'] ?? 'E';
            $adabGradeLabel = $data['adabGradeLabel'] ?? '-';
            $violations = $data['violations'];
            $rewards = $data['rewards'];
            $tanseGrade = $data['tanseGrade'];
            $autoTanseNotes = $data['autoTanseNotes'];
            $tanseTerm = $data['tanseTerm'];
            $report = $data['report'] ?? null;

            $reportMainTitle = \App\Models\Setting::get('report_main_title', 'LAPORAN TAHFIDZ, ADAB DAN TANSE');
            $reportSchoolName = \App\Models\Setting::get('report_school_name', 'SMA ISLAM AL AZHAR 7 SUKOHARJO');
            $reportCity = \App\Models\Setting::get('report_city', 'Sukoharjo');

            $coordTahfizhName = \App\Models\Setting::get('report_coord_tahfizh_name', 'Zainal Arifin, S.Pd');
            $coordTahfizhNik = \App\Models\Setting::get('report_coord_tahfizh_nik', '15.06.0393');

            $coordKeagamaanName = \App\Models\Setting::get('report_coord_keagamaan_name', 'Rifqi Ihsan, S.Pd., Gr.');
            $coordKeagamaanNik = \App\Models\Setting::get('report_coord_keagamaan_nik', '15.06.0393');

            $headmasterTitle = \App\Models\Setting::get('report_headmaster_title', 'Kepala SMA Islam Al Azhar 7 Sukoharjo');
            $headmasterName = \App\Models\Setting::get('report_headmaster_name', 'Moh Pandoyo, S.Si., M.Pd., Gr.');
            $headmasterNik = \App\Models\Setting::get('report_headmaster_nik', '08.04.0160');

            $coordTanseName = \App\Models\Setting::get('report_coord_tanse_name', 'Yatim Hermawan, S.E., S.Kom');
            $coordTanseNik = \App\Models\Setting::get('report_coord_tanse_nik', '15.06.0393');
        @endphp

        <!-- Official Report Card Layout -->
        <div class="print-container max-w-4xl mx-auto bg-white p-8 sm:p-12 border shadow-sm rounded-none min-h-[330mm] {{ $index > 0 ? 'page-break mt-8 print:mt-0' : '' }}" style="font-family: 'Times New Roman', serif;">
            
            <!-- Kop Surat Terpadu -->
            <div class="grid grid-cols-[85px_1fr_85px] items-center border-b border-black pb-4 mb-6">
                <!-- Left Logo: SMA Islam Al Azhar 7 -->
                <div class="shrink-0 flex justify-start">
                    <img src="{{ asset('images/logo_alazhar7.png') }}" class="h-20 w-auto object-contain" alt="Logo SMA Islam Al Azhar 7" />
                </div>
                
                <!-- Title & Basmalah -->
                <div class="flex-1 flex flex-col items-center px-2">
                    <img src="{{ asset('images/image1.png') }}" class="h-6 object-contain mb-2" alt="Basmalah" />
                    <h1 class="text-xs sm:text-sm font-black text-black uppercase tracking-wider text-center">{{ $reportMainTitle }}</h1>
                    <h2 class="text-[10px] sm:text-xs font-bold text-black uppercase text-center mt-0.5">{{ $reportSchoolName }}</h2>
                    
                    <!-- Semester Box -->
                    <div class="border border-black px-4 py-0.5 mt-2 bg-gray-50 text-[9px] font-bold text-black uppercase">
                        SEMESTER : {{ $semester == 1 ? '1 (SATU)' : '2 (DUA)' }}
                    </div>
                    
                    <p class="text-[9px] font-bold text-black mt-1">Tahun Ajaran {{ $academicYear }}</p>
                </div>
                
                <!-- Right Spacer for Header Balance -->
                <div class="shrink-0 w-[85px]"></div>
            </div>

            <!-- Identitas Siswa -->
            <table class="text-xs text-black mb-6" style="line-height: 1.6; min-width: 300px;">
                <tr>
                    <td class="w-20 font-bold">Nama</td>
                    <td class="w-4">:</td>
                    <td>{{ $student->name }}</td>
                </tr>
                <tr>
                    <td class="font-bold">NIS/NISN</td>
                    <td>:</td>
                    <td>{{ $student->student_number ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="font-bold">Kelas</td>
                    <td>:</td>
                    <td>{{ $student->classRoom?->name ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="font-bold">Term</td>
                    <td>:</td>
                    <td>{{ $student->classRoom?->program?->name ?: '-' }}</td>
                </tr>
            </table>

            <!-- I. LAPORAN TAHFIDZ -->
            <div class="mb-6 space-y-3">
                <h3 class="text-xs font-black uppercase text-black">I. LAPORAN TAHFIDZ</h3>
                
                <!-- Table 1: Targets Summary -->
                <table class="w-full table-fixed border border-black text-xs text-left">
                    <thead>
                        <tr class="bg-gray-100 border-b border-black text-center font-bold">
                            <th class="p-1.5 border-r border-black w-[8%]">No.</th>
                            <th class="p-1.5 border-r border-black w-[32%]">TARGET</th>
                            <th class="p-1.5 border-r border-black w-[32%]">CAPAIAN</th>
                            <th class="p-1.5 border-r border-black w-[12%]">KETERANGAN</th>
                            <th class="p-1.5 w-[16%]">Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($targetRecords as $idx => $target)
                            <tr class="border-b border-black">
                                <td class="p-1.5 border-r border-black text-center align-middle">{{ $idx + 1 }}</td>
                                <td class="p-1.5 border-r border-black align-middle">
                                    @include('reports.partials.tahfizh-target-capaian-cell', ['target' => $target, 'mode' => 'target'])
                                </td>
                                <td class="p-1.5 border-r border-black align-middle">
                                    @include('reports.partials.tahfizh-target-capaian-cell', ['target' => $target, 'mode' => 'capaian'])
                                </td>
                                <td class="p-1.5 border-r border-black text-center align-middle font-bold {{ $target->status === 'completed' ? 'text-green-700' : 'text-red-650' }}">
                                    {{ $target->status === 'completed' ? 'Tuntas' : 'Tidak Tuntas' }}
                                </td>
                                <td class="p-1.5 align-middle text-gray-700">
                                    {{ $target->notes ?: ($target->status === 'completed' ? 'Target hafalan term ini telah tercapai.' : 'Belum menyelesaikan target hafalan.') }}
                                </td>
                            </tr>
                        @empty
                            <tr class="border-b border-black">
                                <td colspan="5" class="p-3 text-center text-gray-500 italic">Belum ada data target tahfizh.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- Nilai Akhir Tahfizh -->
                <div class="border border-black rounded p-3 mt-4 flex items-center justify-between bg-gray-50">
                    <div class="text-xs font-black uppercase">Nilai Akhir Tahfizh</div>
                    <div class="text-2xl font-black">{{ $tahfizhScore['final_score'] }}<span class="text-xs font-semibold"> / 100</span></div>
                </div>
            </div>

            <!-- II. PENILAIAN ADAB -->
            <div class="mb-6 space-y-3">
                <h3 class="text-xs font-black uppercase text-black">II. PENILAIAN ADAB</h3>
                
                <table class="w-full border border-black text-xs text-left">
                    <thead>
                        <tr class="bg-gray-100 border-b border-black text-center font-bold">
                            <th class="p-1 border-r border-black w-10">No.</th>
                            <th class="p-1 border-r border-black w-56">KOMPONEN ADAB</th>
                            <th class="p-1 border-r border-black w-24">Nilai</th>
                            <th class="p-1">Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $catCount = count($adabCategories);
                            $pred = $adabGrade;
                            $predLabel = $adabGradeLabel;
                            $desc = '';
                            if ($avgTotal >= 90) {
                                $desc = 'Sangat baik (Mumtaz), konsisten beribadah kepada Allah, berperilaku sopan terhadap sesama teman, menerapkan adab belajar secara tertib dan disiplin, serta menjaga kebersihan lingkungan dengan sangat baik.';
                            } elseif ($avgTotal >= 80) {
                                $desc = 'Baik sekali (Jayyid Jiddan), rutin melaksanakan ibadah harian, bersikap sopan kepada teman, tertib dalam mengikuti pelajaran, dan turut menjaga kebersihan lingkungan dengan baik.';
                            } elseif ($avgTotal >= 70) {
                                $desc = 'Baik (Jayyid), menunjukkan kesopanan kepada guru dan teman, mengikuti kegiatan belajar dengan tertib, dan menjaga kebersihan diri serta lingkungan.';
                            } elseif ($avgTotal >= 60) {
                                $desc = 'Cukup (Maqbul), sudah berusaha membiasakan adab harian dengan cukup baik, namun masih memerlukan pengawasan dan motivasi berkala agar lebih konsisten.';
                            } else {
                                $desc = 'Kurang (Dha\'if), memerlukan pembinaan moral intensif serta bimbingan khusus baik di sekolah maupun asrama untuk meningkatkan kedisiplinan dan adab sehari-hari.';
                            }
                        @endphp
                        @foreach ($adabCategories as $catIdx => $cat)
                            <tr class="border-b border-black">
                                <td class="p-2 border-r border-black text-center align-middle">{{ $catIdx + 1 }}</td>
                                <td class="p-2 border-r border-black font-semibold align-middle uppercase">{{ $cat['title'] }}</td>
                                @if ($catIdx === 0)
                                    <td rowspan="{{ $catCount }}" class="p-2 border-r border-black text-center align-middle font-bold text-sm text-black">
                                        <span class="text-base font-black">{{ $pred }}</span>
                                        <span class="text-[9px] font-bold text-gray-700 block mt-1 uppercase">{{ round($avgTotal) }}/100</span>
                                    </td>
                                    <td rowspan="{{ $catCount }}" class="p-3 text-gray-700 leading-relaxed align-middle">
                                        <div class="font-bold text-black mb-1">{{ $predLabel }}</div>
                                        {{ $desc }}
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- III. LAPORAN TANSE -->
            <div class="mb-6 space-y-3">
                <h3 class="text-xs font-black uppercase text-black">III. LAPORAN TANSE <span class="font-semibold normal-case">&mdash; {{ $tanseTerm['label'] }}</span></h3>
                
                <table class="w-full border border-black text-xs text-left">
                    <thead>
                        <tr class="bg-gray-100 border-b border-black text-center font-bold">
                            <th class="p-1 border-r border-black w-10">No.</th>
                            <th class="p-1 border-r border-black w-48">JENIS PERILAKU</th>
                            <th class="p-1 border-r border-black w-24">POIN</th>
                            <th class="p-1">Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-black">
                            <td class="p-2 border-r border-black text-center">1</td>
                            <td class="p-2 border-r border-black font-bold">Penghargaan</td>
                            <td class="p-2 border-r border-black text-center font-bold text-emerald-700">{{ $rewards->sum('points') }}</td>
                            {{-- Satu deskripsi untuk seluruh Tanse, berdasarkan predikat triwulan. --}}
                            <td rowspan="2" class="p-2 text-gray-900 align-top">
                                <p class="font-black">Predikat {{ $tanseGrade }}</p>
                                <p class="mt-1 leading-relaxed">{{ $autoTanseNotes }}</p>
                            </td>
                        </tr>
                        <tr class="border-b border-black">
                            <td class="p-2 border-r border-black text-center">2</td>
                            <td class="p-2 border-r border-black font-bold">Pelanggaran</td>
                            <td class="p-2 border-r border-black text-center font-bold text-rose-700">{{ $violations->sum('points') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Catatan Wali Kelas -->
            <div class="mb-6 p-3 border border-black rounded-none text-xs">
                <h4 class="font-bold text-black uppercase mb-1">CATATAN & EVALUASI WALI KELAS:</h4>
                <p class="italic text-gray-900 leading-relaxed font-semibold">
                    "{{ $report?->teacher_notes ?: 'Belum ada catatan deskriptif dari wali kelas.' }}"
                </p>
            </div>

            <!-- Signature Area (4 Kolom Sesuai PDF Rapor Baru Integrasi) -->
            <div class="signature-block w-full text-xs text-black mt-8">
                <!-- Row 1 -->
                <div class="grid grid-cols-2 gap-8 text-center">
                    <div>
                        <p class="invisible select-none">{{ $reportCity }}, {{ $data['reportDate']['date'] }}</p>
                        <p class="font-semibold">Koordinator Tahfidz</p>
                        @include('reports.partials.signature-slot', ['uri' => $signatureUris['coord_tahfizh']])
                        <p class="font-bold underline text-black">{{ $coordTahfizhName }}</p>
                        <p class="text-[10px] text-gray-650">NIK. {{ $coordTahfizhNik }}</p>
                    </div>
                    <div>
                        <p>{{ $reportCity }}, {{ $data['reportDate']['date'] }}</p>
                        <p class="font-semibold">Koordinator Keagamaan</p>
                        @include('reports.partials.signature-slot', ['uri' => $signatureUris['coord_keagamaan']])
                        <p class="font-bold underline text-black">{{ $coordKeagamaanName }}</p>
                        <p class="text-[10px] text-gray-650">NIK. {{ $coordKeagamaanNik }}</p>
                    </div>
                </div>
                
                <!-- Row 2 -->
                <div class="grid grid-cols-2 gap-8 text-center mt-6">
                    <div>
                        <p>Mengetahui,</p>
                        <p class="font-semibold">{{ $headmasterTitle }}</p>
                        @include('reports.partials.signature-slot', ['uri' => $signatureUris['headmaster']])
                        <p class="font-bold underline text-black">{{ $headmasterName }}</p>
                        <p class="text-[10px] text-gray-650">NIK. {{ $headmasterNik }}</p>
                    </div>
                    <div>
                        <p class="invisible select-none">Mengetahui,</p>
                        <p class="font-semibold">Koordinator Tanse</p>
                        @include('reports.partials.signature-slot', ['uri' => $signatureUris['coord_tanse']])
                        <p class="font-bold underline text-black">{{ $coordTanseName }}</p>
                        <p class="text-[10px] text-gray-650">NIK. {{ $coordTanseNik }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <!-- Auto Print Trigger script -->
    <script>
        window.addEventListener('DOMContentLoaded', (event) => {
            // Auto open print dialog
            setTimeout(() => {
                window.print();
            }, 800);
        });
    </script>
</body>
</html>
