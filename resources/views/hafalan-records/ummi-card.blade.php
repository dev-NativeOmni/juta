<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Prestasi UMMI - {{ $student->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f4f5;
        }
        @media print {
            body {
                background-color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            @page {
                size: landscape;
                margin: 0.5cm;
            }
            .print-page {
                gap: 1.5rem !important;
                flex-direction: row !important;
                flex-wrap: nowrap !important;
            }
            .print-card {
                width: 50% !important;
                border-radius: 0.75rem !important;
                box-shadow: none !important;
            }
        }
        .prestasi-table th, .prestasi-table td {
            border: 1px solid #000000;
            padding: 3px 4px;
            font-size: 8px;
            line-height: 1.1;
        }
        .prestasi-table th {
            font-weight: 700;
            text-align: center;
            background-color: #f3f4f6;
        }
    </style>
</head>
<body class="p-4 sm:p-6 bg-zinc-100 min-h-screen text-zinc-900">
    
    <!-- Top Action Bar (no-print) -->
    <div class="no-print max-w-7xl mx-auto mb-6 flex items-center justify-between bg-white p-4 rounded-xl shadow-sm border border-zinc-200">
        <div>
            <h1 class="font-bold text-lg text-gray-900">Kartu Prestasi UMMI — {{ $student->name }}</h1>
            <p class="text-xs text-zinc-500">Gunakan pintasan Ctrl+P atau tombol di samping untuk mencetak kartu ini.</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-sm transition">
                <x-heroicon-o-printer class="w-4 h-4" />
                <span>Cetak Kartu</span>
            </button>
            <button onclick="window.close()" class="px-4 py-2 border border-zinc-300 text-zinc-700 rounded-lg text-sm font-semibold hover:bg-zinc-50 transition">
                Tutup Halaman
            </button>
        </div>
    </div>

    <!-- Printable Page Container -->
    <div class="max-w-[1600px] mx-auto print-page flex flex-col lg:flex-row justify-between gap-6">
        @for ($c = 0; $c < 2; $c++)
            <div class="w-full lg:w-1/2 bg-white p-5 border border-zinc-300 rounded-2xl shadow-sm relative text-black print-card">
                
                <!-- Logo & Title Header Banner -->
                <div class="bg-zinc-900 text-white p-3 rounded-lg flex items-center justify-center gap-3 relative mb-4">
                    <img src="{{ asset('images/logo_alazhar7.png') }}" alt="School Logo" class="w-8 h-8 object-contain rounded-full bg-white p-0.5">
                    <span class="font-extrabold text-xs sm:text-sm tracking-wider">KARTU PRESTASI SISWA</span>
                    <img src="{{ asset('images/logo_alazhar7.png') }}" alt="School Logo" class="w-8 h-8 object-contain rounded-full bg-white p-0.5 absolute right-3">
                </div>

                <!-- Student Info Two Columns -->
                <div class="grid grid-cols-2 gap-x-6 gap-y-1.5 text-[10px] mb-4 border-b border-dashed pb-3">
                    <div class="space-y-1.5">
                        <div class="flex items-center">
                            <span class="font-bold w-16">Nama</span>
                            <span class="mr-1">:</span>
                            <span class="border-b border-dotted border-gray-400 flex-1 font-semibold">{{ $student->name }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="font-bold w-16">Jilid/Tkgt</span>
                            <span class="mr-1">:</span>
                            <span class="border-b border-dotted border-gray-400 flex-1 font-semibold">
                                {{ $latestUmmiRecord?->ummi_jilid ?? '-' }}
                            </span>
                        </div>
                        <div class="flex items-center">
                            <span class="font-bold w-16">No. Induk</span>
                            <span class="mr-1">:</span>
                            <span class="border-b border-dotted border-gray-400 flex-1 font-semibold">{{ $student->student_number ?? '-' }}</span>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex items-center">
                            <span class="font-bold w-16">Ustadz/ah</span>
                            <span class="mr-1">:</span>
                            <span class="border-b border-dotted border-gray-400 flex-1 font-semibold">{{ $student->teacher?->user?->name ?? '-' }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="font-bold w-16">Kelas</span>
                            <span class="mr-1">:</span>
                            <span class="border-b border-dotted border-gray-400 flex-1 font-semibold">{{ $student->classRoom?->name ?? '-' }}</span>
                        </div>
                        <div class="flex items-center">
                            <span class="font-bold w-16">Tempat</span>
                            <span class="mr-1">:</span>
                            <span class="border-b border-dotted border-gray-400 flex-1 font-semibold">TAD / Sekolah</span>
                        </div>
                    </div>
                </div>

                <!-- Card Grid Table -->
                <table class="w-full border-collapse prestasi-table">
                    <thead>
                        <tr>
                            <th rowspan="2" class="w-[8%]">Tatap Muka</th>
                            <th rowspan="2" class="w-[10%]">Tanggal</th>
                            <th colspan="2" class="w-[20%]">Hafalan</th>
                            <th colspan="2" class="w-[22%]">UMMI / Al-Qur'an</th>
                            <th rowspan="2" class="w-[12%]">Materi</th>
                            <th rowspan="2" class="w-[6%]">Nilai</th>
                            <th colspan="2" class="w-[10%]">Disimak</th>
                            <th rowspan="2" class="w-[12%]">Keterangan</th>
                        </tr>
                        <tr>
                            <th class="font-semibold">Surat</th>
                            <th class="font-semibold">Ayat</th>
                            <th class="font-semibold">Jilid/Surat</th>
                            <th class="font-semibold">Hal/Ayat</th>
                            <th class="font-semibold">Guru</th>
                            <th class="font-semibold">Ortu</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $chunkSize = 22;
                            $currentRecords = $c === 0 ? $records->slice(0, $chunkSize) : $records->slice($chunkSize, $chunkSize);
                            $padCount = max(0, $chunkSize - $currentRecords->count());
                        @endphp
                        @foreach ($currentRecords as $record)
                            <tr>
                                <td class="text-center font-semibold">{{ $record->tatap_muka }}</td>
                                <td class="text-center">{{ $record->tanggal?->format('d/m/y') }}</td>
                                <td>{{ $record->surahs->map(fn ($s) => $s->surah?->name_latin ?? '-')->implode(', ') ?: '-' }}</td>
                                <td class="text-center">{{ $record->surahs->pluck('hafalan_ayah')->filter()->implode(', ') ?: '-' }}</td>
                                <td>{{ $record->ummi_jilid ?? '-' }}</td>
                                <td class="text-center">{{ $record->ummi_halaman ?? '-' }}</td>
                                <td>{{ $record->materi ?? '-' }}</td>
                                <td class="text-center font-bold">{{ $record->nilai ?? '-' }}</td>
                                <td class="text-center font-medium">{{ $record->disimak_guru }}</td>
                                <td class="text-center font-medium">{{ $record->disimak_ortu }}</td>
                                <td class="text-[7px]">{{ $record->keterangan ?? '-' }}</td>
                            </tr>
                        @endforeach

                        {{-- Print blank rows to pad the card --}}
                        @for ($i = 0; $i < $padCount; $i++)
                            <tr>
                                <td class="text-center text-transparent">&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                        @endfor
                    </tbody>
                </table>

            </div>
        @endfor
    </div>

</body>
</html>
