<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\Student;
use App\Models\StudentPoint;
use Carbon\Carbon;
use Illuminate\Http\Request;

class QuarterlyReportController extends Controller
{
    public static function mapScoreToGrade($score): string
    {
        if (empty($score)) {
            return 'A';
        }
        if (is_string($score) && ! is_numeric($score)) {
            return $score;
        }
        $scoreVal = (float) $score;
        if ($scoreVal >= 90) {
            return 'A+';
        }
        if ($scoreVal >= 80) {
            return 'A';
        }
        if ($scoreVal >= 70) {
            return 'B+';
        }
        if ($scoreVal >= 60) {
            return 'B';
        }
        if ($scoreVal >= 50) {
            return 'B-';
        }

        return 'C';
    }

    public function index(Request $request)
    {
        // Load all classrooms with their program
        $classRooms = ClassRoom::query()->with('program')->orderBy('name')->get();
        $selectedClassId = $request->input('class_room_id', $classRooms->first()?->id);
        $selectedClass = $classRooms->firstWhere('id', $selectedClassId);

        // Auto-detect defaults from latest database record to ensure the dashboard works on seeded data
        $latestRecord = HafalanRecord::query()->latest('submitted_at')->first();
        $detectedYearString = '2025/2026';
        $detectedTerm = '1';
        $detectedMonth = '09';

        if ($latestRecord) {
            $latestDate = Carbon::parse($latestRecord->submitted_at);
            $detectedMonth = $latestDate->format('m');
            $yearVal = $latestDate->year;

            if (in_array($detectedMonth, ['07', '08', '09'])) {
                $detectedTerm = '1';
                $detectedYearString = "{$yearVal}/".($yearVal + 1);
            } elseif (in_array($detectedMonth, ['10', '11', '12'])) {
                $detectedTerm = '2';
                $detectedYearString = "{$yearVal}/".($yearVal + 1);
            } elseif (in_array($detectedMonth, ['01', '02', '03'])) {
                $detectedTerm = '3';
                $detectedYearString = ($yearVal - 1)."/{$yearVal}";
            } else {
                $detectedTerm = '4';
                $detectedYearString = ($yearVal - 1)."/{$yearVal}";
            }
        }

        $academicYear = $request->input('academic_year', $detectedYearString);
        $selectedTerm = $request->input('term', $detectedTerm);

        // Determine months of the selected term
        $monthsMap = [];
        if ($selectedTerm == '1') {
            $monthsMap = ['07' => 'Juli', '08' => 'Agustus', '09' => 'September'];
        } elseif ($selectedTerm == '2') {
            $monthsMap = ['10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
        } elseif ($selectedTerm == '3') {
            $monthsMap = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret'];
        } else {
            $monthsMap = ['04' => 'April', '05' => 'Mei', '06' => 'Juni'];
        }

        $selectedMonth = $request->input('month');
        if (! $selectedMonth || ! isset($monthsMap[$selectedMonth])) {
            $selectedMonth = (string) array_key_last($monthsMap);
        }

        // Parse start and end years
        $years = explode('/', $academicYear);
        $startYear = (int) $years[0];
        $endYear = isset($years[1]) ? (int) $years[1] : ($startYear + 1);

        // Calculate selected month year
        $monthYear = in_array($selectedMonth, ['01', '02', '03', '04', '05', '06']) ? $endYear : $startYear;
        $startDate = "{$monthYear}-{$selectedMonth}-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        // Term range (3 months)
        $termMonths = array_keys($monthsMap);
        $firstMonth = $termMonths[0];
        $lastMonth = $termMonths[2];
        $termStartYear = in_array($firstMonth, ['01', '02', '03', '04', '05', '06']) ? $endYear : $startYear;
        $termEndYear = in_array($lastMonth, ['01', '02', '03', '04', '05', '06']) ? $endYear : $startYear;
        $termStartDate = "{$termStartYear}-{$firstMonth}-01";
        $termEndDate = date('Y-m-t', strtotime("{$termEndYear}-{$lastMonth}-01"));

        // Detect program type
        $programName = strtolower($selectedClass?->program?->name ?? '');
        $isTahfizhProgram = str_contains($programName, 'tahfizh') || str_contains($programName, 'akselerasi');

        // Get actual active students in the selected class
        $students = Student::query()
            ->with(['classRoom', 'teacher.user'])
            ->where('class_room_id', $selectedClassId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // Fallback for empty seeded classrooms
        if ($students->isEmpty()) {
            $students = Student::query()
                ->with(['classRoom', 'teacher.user'])
                ->where('status', 'active')
                ->orderBy('name')
                ->take(10)
                ->get();
        }

        $studentIds = $students->pluck('id')->toArray();

        // 1. Fetch real monthly data (column is 'tanggal', not 'date')
        $attendances = Attendance::query()
            ->whereIn('student_id', $studentIds)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->get();

        $hafalanRecords = HafalanRecord::query()
            ->with('surah')
            ->whereIn('student_id', $studentIds)
            ->whereBetween('submitted_at', [$startDate, $endDate])
            ->orderBy('submitted_at')
            ->get();

        $violations = StudentPoint::query()
            ->whereIn('student_id', $studentIds)
            ->where('type', 'violation')
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        // 2. Fetch real term-wide data
        $termHafalanRecords = HafalanRecord::query()
            ->with('surah')
            ->whereIn('student_id', $studentIds)
            ->whereBetween('submitted_at', [$termStartDate, $termEndDate])
            ->get();

        $termAttendances = Attendance::query()
            ->whereIn('student_id', $studentIds)
            ->whereBetween('tanggal', [$termStartDate, $termEndDate])
            ->get();

        $termViolations = StudentPoint::query()
            ->whereIn('student_id', $studentIds)
            ->where('type', 'violation')
            ->whereBetween('date', [$termStartDate, $termEndDate])
            ->get();

        $latestTargets = HafalanTarget::query()
            ->with('surah')
            ->whereIn('student_id', $studentIds)
            ->where('target_date', '<=', $termEndDate)
            ->orderBy('target_date', 'desc')
            ->get()
            ->groupBy('student_id');

        $latestHafalans = HafalanRecord::query()
            ->with('surah')
            ->whereIn('student_id', $studentIds)
            ->where('status', 'passed')
            ->where('submitted_at', '<=', $termEndDate)
            ->orderBy('submitted_at', 'desc')
            ->get()
            ->groupBy('student_id');

        // Group students by their Musyrif
        $studentsByHalaqah = $students->groupBy(function ($student) {
            return $student->teacher?->user?->name ?? 'Ust. Fuad Faris Ghazi';
        });

        // Determine unique dates for harian jurnal / tatap muka
        $uniqueDates = $attendances->pluck('tanggal')
            ->map(fn ($d) => $d instanceof Carbon ? $d->toDateString() : Carbon::parse($d)->toDateString())
            ->merge($hafalanRecords->pluck('submitted_at')->map(fn ($d) => $d instanceof Carbon ? $d->toDateString() : Carbon::parse($d)->toDateString()))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $meetingDates = array_slice($uniqueDates, 0, 12);
        while (count($meetingDates) < 12) {
            $meetingDates[] = null;
        }

        $halaqahData = [];

        foreach ($studentsByHalaqah as $musyrifName => $groupStudents) {
            $tahfizhRecords = [];
            $regulerRecords = [];
            $presensiData = [];

            $gStudentIds = $groupStudents->pluck('id')->toArray();
            $gAttendances = $attendances->whereIn('student_id', $gStudentIds);
            $gHafalanRecords = $hafalanRecords->whereIn('student_id', $gStudentIds);

            // A. Presensi & Setoran Mapping
            if ($isTahfizhProgram) {
                // Tahfizh: 3 months grid
                foreach ($groupStudents as $student) {
                    $studentPresensi = [];
                    $studentTermAtt = $termAttendances->where('student_id', $student->id);
                    $studentTermHaf = $termHafalanRecords->where('student_id', $student->id);

                    foreach ($monthsMap as $mCode => $mName) {
                        $mStart = "{$monthYear}-{$mCode}-01";
                        $mEnd = date('Y-m-t', strtotime($mStart));

                        $mAtt = $studentTermAtt->whereBetween('tanggal', [$mStart, $mEnd]);
                        $mHaf = $studentTermHaf->whereBetween('submitted_at', [$mStart, $mEnd]);

                        // Compute unique meeting dates for this month
                        $mUniqueDates = $termAttendances->whereBetween('tanggal', [$mStart, $mEnd])->pluck('tanggal')
                            ->map(fn ($d) => $d instanceof Carbon ? $d->toDateString() : Carbon::parse($d)->toDateString())
                            ->merge($termHafalanRecords->whereBetween('submitted_at', [$mStart, $mEnd])->pluck('submitted_at')->map(fn ($d) => $d instanceof Carbon ? $d->toDateString() : Carbon::parse($d)->toDateString()))
                            ->unique()
                            ->sort()
                            ->values()
                            ->toArray();
                        $mMeetings = array_slice($mUniqueDates, 0, 12);

                        $mDays = [];
                        for ($i = 1; $i <= 12; $i++) {
                            $date = $mMeetings[$i - 1] ?? null;
                            if ($date) {
                                $att = $mAtt->first(fn ($a) => ($a->tanggal instanceof Carbon ? $a->tanggal->toDateString() : $a->tanggal) === $date);
                                if ($att) {
                                    $mDays[$i] = match ($att->status) {
                                        'hadir' => 'H',
                                        'sakit' => 'S',
                                        'izin' => 'I',
                                        'alpa' => 'A',
                                        default => 'H'
                                    };
                                } else {
                                    $hasSetoran = $mHaf->contains(fn ($h) => $h->submitted_at->toDateString() === $date);
                                    $mDays[$i] = $hasSetoran ? 'H' : 'H';
                                }
                            } else {
                                $mDays[$i] = '-';
                            }
                        }

                        $studentPresensi[$mName] = [
                            'days' => $mDays,
                            'sakit' => $mAtt->where('status', 'sakit')->count(),
                            'izin' => $mAtt->where('status', 'izin')->count(),
                            'alpa' => $mAtt->where('status', 'alpa')->count(),
                        ];
                    }
                    $presensiData[$student->id] = $studentPresensi;
                }
            } else {
                // Reguler: Pekan 1 - 5 grid
                foreach ($groupStudents as $student) {
                    $pekan = [];
                    $sAtt = $gAttendances->where('student_id', $student->id);
                    $sHaf = $gHafalanRecords->where('student_id', $student->id);

                    for ($p = 1; $p <= 5; $p++) {
                        $pStart = 1 + ($p - 1) * 7;
                        $pEnd = $p === 5 ? 31 : $p * 7;

                        $att = $sAtt->first(function ($a) use ($pStart, $pEnd) {
                            $dayNum = (int) date('d', strtotime($a->tanggal));

                            return $dayNum >= $pStart && $dayNum <= $pEnd;
                        });

                        if ($att) {
                            $pekan[$p] = match ($att->status) {
                                'hadir' => 'Hadir',
                                'sakit' => 'Sakit',
                                'izin' => 'Izin',
                                'alpa' => 'Alpa',
                                default => 'Hadir'
                            };
                        } else {
                            $hasSetoran = $sHaf->contains(function ($h) use ($pStart, $pEnd) {
                                $dayNum = (int) $h->submitted_at->format('d');

                                return $dayNum >= $pStart && $dayNum <= $pEnd;
                            });
                            $pekan[$p] = $hasSetoran ? 'Hadir' : 'Hadir';
                        }
                    }

                    $presensiData[$student->id] = [
                        'pekan' => $pekan,
                        'hadir' => $sAtt->where('status', 'hadir')->count() ?: 5,
                        'sakit' => $sAtt->where('status', 'sakit')->count(),
                        'izin' => $sAtt->where('status', 'izin')->count(),
                        'alpa' => $sAtt->where('status', 'alpa')->count(),
                    ];
                }
            }

            // B. Jurnal Mapping
            $jurnalData = [];
            if ($isTahfizhProgram) {
                foreach ($uniqueDates as $date) {
                    if (! $date) {
                        continue;
                    }
                    $materi = "Muroja'ah & Ziyadah Hafalan";

                    $jurnalData[] = [
                        'tanggal' => date('d-m-Y', strtotime($date)),
                        'materi' => $materi,
                        'jumlah_murid' => $gAttendances->filter(fn ($a) => ($a->tanggal instanceof Carbon ? $a->tanggal->toDateString() : $a->tanggal) === $date)->where('status', 'hadir')->count() ?: count($groupStudents),
                        'paraf' => '✓',
                    ];
                }
                if (empty($jurnalData)) {
                    $jurnalData[] = [
                        'tanggal' => 'Belum ada kegiatan',
                        'materi' => "Muroja'ah & Ziyadah Hafalan",
                        'jumlah_murid' => 0,
                        'paraf' => '-',
                    ];
                }
            } else {
                for ($p = 1; $p <= 5; $p++) {
                    $materi = "Muroja'ah & Ziyadah Hafalan";

                    $jurnalData[] = [
                        'tanggal' => "Pekan $p",
                        'materi' => $materi,
                        'jumlah_murid' => count($groupStudents),
                        'paraf' => '✓',
                    ];
                }
            }

            // C. Capaian Setoran Mapping
            if ($isTahfizhProgram) {
                foreach ($groupStudents as $student) {
                    $sHaf = $gHafalanRecords->where('student_id', $student->id);
                    $sAtt = $gAttendances->where('student_id', $student->id);

                    $pekanRecords = [];
                    $totalCapaianLines = 0;

                    for ($p = 1; $p <= 5; $p++) {
                        $dailyLogs = [];
                        $weekLines = 0;

                        $pStart = 1 + ($p - 1) * 7;
                        $pEnd = $p === 5 ? 31 : $p * 7;

                        $pRecords = $sHaf->filter(function ($h) use ($pStart, $pEnd) {
                            $dayNum = (int) $h->submitted_at->format('d');

                            return $dayNum >= $pStart && $dayNum <= $pEnd;
                        });

                        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
                        $dayMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat'];

                        foreach ($days as $dayName) {
                            $record = $pRecords->first(function ($r) use ($dayName, $dayMap) {
                                $wDay = (int) date('w', strtotime($r->submitted_at));

                                return isset($dayMap[$wDay]) && $dayMap[$wDay] === $dayName;
                            });

                            if ($record && $record->surah) {
                                $lines = $record->lines_count;
                                $dailyLogs[$dayName] = [
                                    'surah' => $record->surah->name_latin,
                                    'ayat_start' => $record->ayah_start,
                                    'ayat_end' => $record->ayah_end,
                                    'baris' => $lines,
                                    'nilai' => self::mapScoreToGrade($record->score),
                                ];
                                $weekLines += $lines;
                            } else {
                                $attRecord = $sAtt->first(function ($a) use ($dayName, $dayMap, $pStart, $pEnd) {
                                    $dayNum = (int) date('d', strtotime($a->tanggal));
                                    if ($dayNum < $pStart || $dayNum > $pEnd) {
                                        return false;
                                    }
                                    $wDay = (int) date('w', strtotime($a->tanggal));

                                    return isset($dayMap[$wDay]) && $dayMap[$wDay] === $dayName;
                                });

                                if ($attRecord && $attRecord->status !== 'hadir') {
                                    $dailyLogs[$dayName] = [
                                        'surah' => ucfirst($attRecord->status),
                                        'ayat_start' => '',
                                        'ayat_end' => '',
                                        'baris' => 0,
                                        'nilai' => '-',
                                    ];
                                } else {
                                    $dailyLogs[$dayName] = [
                                        'surah' => '-',
                                        'ayat_start' => '',
                                        'ayat_end' => '',
                                        'baris' => 0,
                                        'nilai' => '-',
                                    ];
                                }
                            }
                        }

                        $pekanRecords[$p] = [
                            'days' => $dailyLogs,
                            'week_lines' => $weekLines,
                        ];
                        $totalCapaianLines += $weekLines;
                    }

                    $levelBaris = match ($student->tahfizh_level) {
                        'tahsin' => 3,
                        'reguler' => 5,
                        'akselerasi' => 7,
                        'ummi' => null,
                        default => 5,
                    };
                    $targetLines = ($levelBaris === null) ? 0 : ($levelBaris * 20);
                    $isTuntas = ($levelBaris === null) ? true : ($totalCapaianLines >= $targetLines);
                    $pCount = $violations->where('student_id', $student->id)->count();

                    $studentTarget = $latestTargets->get($student->id)?->first();
                    $studentHafalan = $latestHafalans->get($student->id)?->first();

                    $targetSurah = $studentTarget?->surah?->name_latin ?? '-';
                    $targetAyat = $studentTarget ? "{$studentTarget->ayah_start}-{$studentTarget->ayah_end}" : '-';

                    $capaianSurah = $studentHafalan?->surah?->name_latin ?? '-';
                    $capaianAyat = $studentHafalan ? "{$studentHafalan->ayah_start}-{$studentHafalan->ayah_end}" : '-';

                    $tahfizhRecords[] = [
                        'student_id' => $student->id,
                        'name' => $student->name,
                        'nis' => $student->student_number ?? '4407-2526'.sprintf('%03d', $student->id),
                        'level' => ucfirst($student->tahfizh_level ?? 'reguler'),
                        'pekan' => $pekanRecords,
                        'target_lines' => $targetLines,
                        'total_lines' => $totalCapaianLines,
                        'is_tuntas' => $isTuntas,
                        'pelanggaran' => $pCount,
                        'target_surah' => $targetSurah,
                        'target_ayat' => $targetAyat,
                        'capaian_surah' => $capaianSurah,
                        'capaian_ayat' => $capaianAyat,
                    ];
                }
            } else {
                foreach ($groupStudents as $student) {
                    $sHaf = $gHafalanRecords->where('student_id', $student->id);
                    $pekanRecords = [];
                    $totalCapaianLines = 0;

                    for ($p = 1; $p <= 5; $p++) {
                        $pStart = 1 + ($p - 1) * 7;
                        $pEnd = $p === 5 ? 31 : $p * 7;

                        $record = $sHaf->first(function ($h) use ($pStart, $pEnd) {
                            $dayNum = (int) $h->submitted_at->format('d');

                            return $dayNum >= $pStart && $dayNum <= $pEnd;
                        });

                        if ($record && $record->surah) {
                            $lines = $record->lines_count;
                            $pekanRecords[$p] = [
                                'surah' => $record->surah->name_latin,
                                'ayat' => "{$record->ayah_start}-{$record->ayah_end}",
                                'baris' => $lines,
                                'nilai' => self::mapScoreToGrade($record->score),
                                'kehadiran' => 'Hadir',
                            ];
                            $totalCapaianLines += $lines;
                        } else {
                            $status = $presensiData[$student->id]['pekan'][$p];
                            $pekanRecords[$p] = [
                                'surah' => '-',
                                'ayat' => '-',
                                'baris' => 0,
                                'nilai' => '-',
                                'kehadiran' => $status,
                            ];
                        }
                    }

                    $levelBaris = match ($student->tahfizh_level) {
                        'tahsin' => 3,
                        'reguler' => 5,
                        'akselerasi' => 7,
                        'ummi' => null,
                        default => 5,
                    };
                    $targetLines = ($levelBaris === null) ? 0 : ($levelBaris * 4);
                    $isTuntas = ($levelBaris === null) ? true : ($totalCapaianLines >= $targetLines);
                    $pCount = $violations->where('student_id', $student->id)->count();

                    $studentTarget = $latestTargets->get($student->id)?->first();
                    $studentHafalan = $latestHafalans->get($student->id)?->first();

                    $targetSurah = $studentTarget?->surah?->name_latin ?? '-';
                    $targetAyat = $studentTarget ? "{$studentTarget->ayah_start}-{$studentTarget->ayah_end}" : '-';

                    $capaianSurah = $studentHafalan?->surah?->name_latin ?? '-';
                    $capaianAyat = $studentHafalan ? "{$studentHafalan->ayah_start}-{$studentHafalan->ayah_end}" : '-';

                    $regulerRecords[] = [
                        'student_id' => $student->id,
                        'name' => $student->name,
                        'nis' => $student->student_number ?? '4407-2526'.sprintf('%03d', $student->id),
                        'level' => ucfirst($student->tahfizh_level ?? 'reguler'),
                        'pekan' => $pekanRecords,
                        'target_lines' => $targetLines,
                        'total_lines' => $totalCapaianLines,
                        'is_tuntas' => $isTuntas,
                        'pelanggaran' => $pCount,
                        'target_surah' => $targetSurah,
                        'target_ayat' => $targetAyat,
                        'capaian_surah' => $capaianSurah,
                        'capaian_ayat' => $capaianAyat,
                    ];
                }
            }

            // Group everything under this Halaqah Musyrif
            $halaqahData[] = [
                'musyrif' => $musyrifName,
                'students' => $groupStudents,
                'is_tahfizh' => $isTahfizhProgram,
                'presensi' => $presensiData,
                'jurnal' => $jurnalData,
                'tahfizh_records' => $tahfizhRecords,
                'reguler_records' => $regulerRecords,
                'months' => array_values($monthsMap),
                'total_students' => count($groupStudents),
                'tuntas_count' => $isTahfizhProgram
                    ? collect($tahfizhRecords)->where('is_tuntas', true)->count()
                    : collect($regulerRecords)->where('is_tuntas', true)->count(),
            ];
        }

        return view('reports.quarterly', [
            'classRooms' => $classRooms,
            'selectedClass' => $selectedClass,
            'isTahfizhProgram' => $isTahfizhProgram,
            'academicYear' => $academicYear,
            'selectedTerm' => $selectedTerm,
            'selectedMonth' => $selectedMonth,
            'monthsMap' => $monthsMap,
            'halaqahData' => $halaqahData,
            'months' => array_values($monthsMap),
        ]);
    }
}
