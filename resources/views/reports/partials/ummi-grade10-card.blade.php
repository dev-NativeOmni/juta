@php
    $circleImgPath = public_path('images/logo-alazhar7-circle.png');
    $gemilangImgPath = public_path('images/logo-gemilang-banner.png');

    $circleLogoBase64 = file_exists($circleImgPath) 
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($circleImgPath))
        : asset('images/logo-alazhar7-circle.png');

    $gemilangLogoBase64 = file_exists($gemilangImgPath) 
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($gemilangImgPath))
        : asset('images/logo-gemilang-banner.png');

    $className = $selectedClass?->name ?? '';
    $hideZiyadah = (bool) preg_match('/(E2|E3|X\.?E2|X\.?E3|E-2|E-3)/i', $className);
@endphp

<div id="ummiGrade10ReportCard" class="mx-auto max-w-5xl rounded-[28px] p-5 sm:p-8 shadow-2xl relative font-sans border-[5px] border-amber-400" style="background-color: #ffffff !important; color: #0f172a !important; box-sizing: border-box !important;">
    
    <!-- Top Header Bar with Centered Logos -->
    <div class="flex items-center justify-center gap-3 sm:gap-5 border-b-2 border-amber-300 pb-3 mb-3 text-center">
        <!-- Logo Bulat (Left) -->
        <img src="{{ $circleLogoBase64 }}" alt="Logo Al Azhar 7" class="w-12 h-12 sm:w-14 sm:h-14 object-contain shrink-0" style="height: 48px !important; width: 48px !important; max-height: 48px !important; max-width: 48px !important;">

        <!-- Logo Gemilang (Right of Logo Bulat) -->
        <img src="{{ $gemilangLogoBase64 }}" alt="SMA Islam Al Azhar 7 GEMILANG" class="h-10 sm:h-12 object-contain shrink-0" style="height: 44px !important; max-width: 320px !important; max-height: 44px !important;">
    </div>

    <!-- Main Title Section -->
    <div class="text-center my-3">
        <h1 class="text-xl sm:text-3xl font-black text-slate-900 tracking-wide uppercase leading-none" style="color: #0f172a !important;">
            LAPORAN CAPAIAN TAHFIDZ
        </h1>

        <!-- Subtitle Badge UMMI -->
        <div class="inline-flex items-center gap-2 bg-slate-900 text-amber-400 text-xs font-black px-5 py-1 rounded-xl shadow-sm mt-2 border border-amber-400 uppercase tracking-widest" style="background-color: #0f172a !important; color: #fbbf24 !important;">
            <span>PEMBELAJARAN UMMI</span>
        </div>

        <!-- Badges: Kelas & Bulan -->
        <div class="flex justify-center items-center gap-2 mt-2.5 flex-wrap">
            <div class="bg-blue-800 text-white font-extrabold text-xs px-4 py-1 rounded-lg shadow-sm border border-blue-600 uppercase" style="background-color: #1e40af !important; color: #ffffff !important;">
                KELAS {{ $selectedClass?->name ?? '10' }}
            </div>
            <div class="bg-amber-600 text-white font-extrabold text-xs px-4 py-1 rounded-lg shadow-sm border border-amber-500 uppercase" style="background-color: #d97706 !important; color: #ffffff !important;">
                BULAN {{ strtoupper($monthName ?? (isset($monthsList, $selectedMonth) ? ($monthsList[$selectedMonth] ?? '') : date('F'))) }} {{ $selectedYear ?? date('Y') }}
            </div>
        </div>
    </div>

    <!-- Main Data Table -->
    <div class="mt-4 overflow-hidden rounded-xl border-2 border-emerald-700 shadow-sm" style="background-color: #ffffff !important;">
        <table class="w-full text-left border-collapse text-xs" style="background-color: #ffffff !important; color: #0f172a !important;">
            <thead>
                <tr class="bg-emerald-700 text-white text-center font-bold tracking-wide" style="background-color: #047857 !important; color: #ffffff !important;">
                    <th class="py-2 px-2 border-r border-emerald-600 w-10">No</th>
                    <th class="py-2 px-3 border-r border-emerald-600 text-left">Nama Murid</th>
                    <th class="py-2 px-2 border-r border-emerald-600 w-14">Jilid</th>
                    <th class="py-2 px-2 border-r border-emerald-600 w-16">Halaman</th>
                    <th class="py-2 px-3 {{ $hideZiyadah ? '' : 'border-r border-emerald-600' }} text-left">Capaian Hafalan</th>
                    @if (!$hideZiyadah)
                        <th class="py-2 px-3 text-left">Ziyadah</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200" style="color: #0f172a !important;">
                @forelse ($studentReports as $idx => $row)
                    <tr style="background-color: {{ $idx % 2 === 0 ? '#ffffff' : '#f0fdf4' }} !important; color: #0f172a !important;">
                        <td class="py-1.5 px-2 text-center border-r border-zinc-200 font-bold" style="color: #334155 !important;">
                            {{ $idx + 1 }}
                        </td>
                        <td class="py-1.5 px-3 border-r border-zinc-200 font-bold" style="color: #0f172a !important;">
                            {{ is_object($row['student'] ?? null) ? $row['student']->name : ($row['student']['name'] ?? ($row['student_name'] ?? '-')) }}
                        </td>
                        <td class="py-1.5 px-2 text-center border-r border-zinc-200 font-black" style="color: #1e3a8a !important;">
                            {{ $row['ummi_jilid'] ?: '-' }}
                        </td>
                        @php
                            $rawH = $row['ummi_halaman'] ?? '-';
                            if ($rawH !== '-' && !empty($rawH)) {
                                $hParts = preg_split('/[-–—]/u', trim((string) $rawH));
                                $lastHPart = trim(end($hParts));
                                $displayH = is_numeric($lastHPart) ? $lastHPart : $rawH;
                            } else {
                                $displayH = '-';
                            }
                        @endphp
                        <td class="py-1.5 px-2 text-center border-r border-zinc-200 font-black" style="color: #1e3a8a !important;">
                            {{ $displayH }}
                        </td>
                        <td class="py-1.5 px-3 {{ $hideZiyadah ? '' : 'border-r border-zinc-200' }} font-semibold" style="color: #1e293b !important;">
                            {{ $row['ummi_capaian'] ?: '-' }}
                        </td>
                        @if (!$hideZiyadah)
                            <td class="py-1.5 px-3 font-semibold" style="color: #0369a1 !important;">
                                {{ $row['ziyadah'] ?: '-' }}
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr style="background-color: #ffffff !important;">
                        <td colspan="{{ $hideZiyadah ? 5 : 6 }}" class="py-6 text-center text-xs font-semibold" style="color: #64748b !important;">
                            Belum ada data catatan Pembelajaran UMMI untuk kelas ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Footer Hadits Emblem & Social Info -->
    <div class="mt-4 flex flex-col sm:flex-row justify-between items-center gap-3 pt-3 border-t-2 border-amber-300">
        <!-- Left: Hadits Emblem Badge -->
        <div class="rounded-xl p-2.5 shadow-sm flex items-center gap-2.5 max-w-lg border-2 border-amber-400" style="background-color: #0f172a !important; color: #ffffff !important;">
            <div class="w-8 h-8 rounded-full flex items-center justify-center font-black text-sm shrink-0" style="background-color: #d97706 !important; color: #0f172a !important;">
                <x-heroicon-o-book-open class="w-4 h-4 text-slate-900" />
            </div>
            <div>
                <p class="font-bold text-[11px] tracking-wide" style="color: #fbbf24 !important;">"خَيْرُكُمْ مَنْ تَعَلَّمَ الْقُرْآنَ وَعَلَّمَهُ"</p>
                <p class="text-[10px] italic mt-0.5" style="color: #e2e8f0 !important;">"Sebaik-baik kalian adalah yang belajar Al-Qur'an dan mengajarkannya." (HR. Bukhari)</p>
            </div>
        </div>

        <!-- Right: Social Media Contacts -->
        <div class="text-right text-[10px] font-semibold space-y-0.5" style="color: #334155 !important;">
            <p class="font-bold text-sm" style="color: #0f172a !important;">SMA Islam Al Azhar 7 Solo Baru</p>
            <p class="inline-flex items-center gap-1.5 justify-end"><x-heroicon-o-globe-alt class="w-3 h-3 text-emerald-600 inline" /> <span>smaialazhar7.sch.id</span> <span class="mx-1">|</span> <x-heroicon-o-phone class="w-3 h-3 text-emerald-600 inline" /> <span>0812-2347-0077</span></p>
            <p class="text-[9px]" style="color: #64748b !important;">@smaialazhar7</p>
        </div>
    </div>
</div>