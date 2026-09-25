<?php

namespace App\Http\Controllers;

use App\Exports\QuarterlyReportExport;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\TeacherProfile;
use App\Models\UmmiRecord;
use App\Services\AcademicCalendarService;
use App\Services\AutoHafalanTargetService;
use App\Services\HafalanProgressService;
use App\Services\QuranLineTargetService;
use App\Support\TargetRules;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        return view('reports.quarterly', $this->buildReportData($request, $this->teacherScope($request)));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $data = $this->buildReportData($request, $this->teacherScope($request));

        $classSlug = Str::slug($data['selectedClass']?->name ?? 'kelas');
        $termSlug = 'term-'.$data['selectedTerm'].'-'.str_replace('/', '-', $data['academicYear']);
        $fileName = "laporan-triwulan-{$classSlug}-{$termSlug}.xlsx";

        // Download per halaqoh: filter ke satu kelompok musyrif saja kalau diminta.
        if ($request->filled('musyrif')) {
            $data['halaqahData'] = array_values(array_filter(
                $data['halaqahData'],
                fn (array $halaqah) => $halaqah['musyrif'] === $request->string('musyrif')->toString()
            ));

            $fileName = "laporan-triwulan-{$classSlug}-".Str::slug($request->string('musyrif')->toString())."-{$termSlug}.xlsx";
        }

        return Excel::download(new QuarterlyReportExport($data), $fileName);
    }

    /**
     * Download laporan triwulan untuk guru yang login: kelas/halaqoh yang benar-benar
     * dia ampu saja (program reguler ATAU tahfizh, dipilih lewat parameter `program`),
     * dirangkum lintas semua kelas -- bukan satu kelas seperti export() di atas.
     */
    public function exportMine(Request $request): BinaryFileResponse|Response
    {
        $user = $request->user();
        $teacherProfile = $user?->teacherProfile;

        if (! $teacherProfile) {
            abort(403, 'Akun ini tidak terhubung ke profil guru.');
        }

        $isTahfizhProgram = $request->string('program')->toString() === 'tahfizh';
        $data = $this->buildTeacherReportData($request, $teacherProfile, $isTahfizhProgram);

        $teacherSlug = Str::slug($user->name);
        $programSlug = $isTahfizhProgram ? 'tahfizh' : 'reguler';
        $termSlug = 'term-'.$data['selectedTerm'].'-'.str_replace('/', '-', $data['academicYear']);
        $fileName = "kelas-{$programSlug}-{$teacherSlug}-{$termSlug}.xlsx";

        if (empty($data['halaqahData'])) {
            return response(
                "Tidak ada kelas program {$programSlug} yang diampu {$user->name} pada triwulan ini.",
                404
            );
        }

        return Excel::download(new QuarterlyReportExport($data), $fileName);
    }

    private function userHasAnyRole($user, array $roles): bool
    {
        if (! $user) {
            return false;
        }

        foreach ($roles as $role) {
            if (method_exists($user, 'hasRole') && $user->hasRole($role)) {
                return true;
            }

            if (($user->role?->name ?? null) === $role) {
                return true;
            }
        }

        return false;
    }

    /**
     * Profil guru bila yang login adalah guru (bukan admin): laporan per kelas lalu
     * dibatasi ke kelas & murid yang dia ampu saja. Null = admin, lihat semua.
     */
    private function teacherScope(Request $request): ?TeacherProfile
    {
        $user = $request->user();

        if (! $this->userHasAnyRole($user, ['teacher']) || $this->userHasAnyRole($user, ['super_admin', 'admin'])) {
            return null;
        }

        return $user->teacherProfile ?? abort(403, 'Akun ini tidak terhubung ke profil guru.');
    }

    /**
     * Tahun ajaran, triwulan, dan rentang tanggal per bulan -- dipakai bersama oleh laporan
     * per kelas (buildReportData) maupun laporan per guru lintas kelas (buildTeacherReportData).
     */
    private function resolveTermContext(Request $request): array
    {
        // Auto-detect defaults from latest database record to ensure the dashboard works on seeded data
        $latestRecord = HafalanRecord::query()->latest('submitted_at')->first();
        $detectedYearString = '2025/2026';
        $detectedTerm = '1';

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
        if ($selectedTerm == '1') {
            $monthsMap = ['07' => 'Juli', '08' => 'Agustus', '09' => 'September'];
        } elseif ($selectedTerm == '2') {
            $monthsMap = ['10' => 'Oktober', '11' => 'November', '12' => 'Desember'];
        } elseif ($selectedTerm == '3') {
            $monthsMap = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret'];
        } else {
            $monthsMap = ['04' => 'April', '05' => 'Mei', '06' => 'Juni'];
        }

        // Parse start and end years
        $years = explode('/', $academicYear);
        $startYear = (int) $years[0];
        $endYear = isset($years[1]) ? (int) $years[1] : ($startYear + 1);

        // Date range of every month in the term
        $monthRanges = [];
        foreach ($monthsMap as $mCode => $mName) {
            $mYear = in_array($mCode, ['01', '02', '03', '04', '05', '06'], true) ? $endYear : $startYear;
            $mStart = "{$mYear}-{$mCode}-01";
            $monthRanges[$mCode] = [
                'label' => $mName,
                'start' => $mStart,
                'end' => date('Y-m-t', strtotime($mStart)),
            ];
        }
        $termStartDate = reset($monthRanges)['start'];
        $termEndDate = end($monthRanges)['end'];

        return compact('academicYear', 'selectedTerm', 'monthsMap', 'monthRanges', 'termStartDate', 'termEndDate');
    }

    /**
     * Ambil seluruh data mentah satu triwulan sekali jalan (presensi, setoran, Ummi,
     * pelanggaran, target & capaian terakhir) untuk sekelompok murid tertentu.
     */
    private function fetchTermData(array $studentIds, string $termStartDate, string $termEndDate): array
    {
        $termAttendances = Attendance::query()
            ->whereIn('student_id', $studentIds)
            ->whereBetween('tanggal', [$termStartDate, $termEndDate])
            ->get();

        $termHafalanRecords = HafalanRecord::flattenSurahs(
            HafalanRecord::query()
                ->with('surahs.surah')
                ->whereIn('student_id', $studentIds)
                ->whereBetween('submitted_at', [$termStartDate, $termEndDate])
                ->orderBy('submitted_at')
                ->orderBy('id')
                ->get()
        );

        $termUmmiRecords = UmmiRecord::query()
            ->with('surahs.surah')
            ->whereIn('student_id', $studentIds)
            ->whereBetween('tanggal', [$termStartDate, $termEndDate])
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        $termViolations = StudentPoint::query()
            ->whereIn('student_id', $studentIds)
            ->where('type', 'violation')
            ->whereBetween('date', [$termStartDate, $termEndDate])
            ->get();

        // Target triwulan = target tersimpan terakhir DI DALAM triwulan ini (sama dengan Target Triwulan).
        $latestTargets = HafalanTarget::query()
            ->with('surah')
            ->whereIn('student_id', $studentIds)
            ->whereBetween('target_date', [$termStartDate, $termEndDate.' 23:59:59'])
            ->orderBy('target_date', 'desc')
            ->get()
            ->groupBy('student_id');

        $latestHafalans = HafalanRecord::flattenSurahs(
            HafalanRecord::query()
                ->with(['surahs' => fn ($q) => $q->where('status', 'passed')->with('surah')])
                ->whereIn('student_id', $studentIds)
                ->whereHas('surahs', fn ($q) => $q->where('status', 'passed'))
                ->where('submitted_at', '<=', $termEndDate.' 23:59:59')
                ->orderBy('submitted_at', 'desc')
                ->get()
        )->groupBy('student_id');

        $latestUmmiRecords = UmmiRecord::query()
            ->with('surahs.surah')
            ->whereIn('student_id', $studentIds)
            ->where('tanggal', '<=', $termEndDate)
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('student_id');

        return [
            'termAttendances' => $termAttendances,
            'termHafalanRecords' => $termHafalanRecords,
            'termUmmiRecords' => $termUmmiRecords,
            'termViolations' => $termViolations,
            'latestTargets' => $latestTargets,
            'latestHafalans' => $latestHafalans,
            'latestUmmiRecords' => $latestUmmiRecords,
        ];
    }

    /**
     * Bangun satu "kartu" halaqah/kelas (presensi, jurnal, capaian per pekan & rekap term)
     * untuk sekelompok murid. Dipakai baik oleh laporan per kelas (satu kelas, banyak
     * halaqah/musyrif) maupun laporan per guru (satu guru, banyak kelas).
     */
    private function buildHalaqahSection(
        string $musyrifName,
        $groupStudents,
        ?ClassRoom $classRoom,
        bool $isTahfizhProgram,
        array $monthRanges,
        array $monthsMap,
        array $term,
        AcademicCalendarService $calendar,
        ?QuranLineTargetService $positionCheck,
        ?string $musyrifSignature = null
    ): array {
        $gStudentIds = $groupStudents->pluck('id')->toArray();
        $gAttendances = $term['termAttendances']->whereIn('student_id', $gStudentIds);
        $gHafalanRecords = $term['termHafalanRecords']->whereIn('student_id', $gStudentIds);
        $gUmmiRecords = $term['termUmmiRecords']->whereIn('student_id', $gStudentIds);
        $gViolations = $term['termViolations']->whereIn('student_id', $gStudentIds);

        $context = [
            'classRoom' => $classRoom,
            'calendar' => $calendar,
            'isTahfizhProgram' => $isTahfizhProgram,
            'groupStudents' => $groupStudents,
            'gAttendances' => $gAttendances,
            'gHafalanRecords' => $gHafalanRecords,
            'gUmmiRecords' => $gUmmiRecords,
            'gViolations' => $gViolations,
            'classAttendances' => $term['termAttendances'],
            'classHafalanRecords' => $term['termHafalanRecords'],
            'latestTargets' => $term['latestTargets'],
            'latestHafalans' => $term['latestHafalans'],
            'latestUmmiRecords' => $term['latestUmmiRecords'],
        ];

        $monthly = [];
        foreach ($monthRanges as $mCode => $range) {
            $monthly[$mCode] = $this->buildMonthReport($range, $context);
        }

        $termRecords = $this->buildTermRecords(
            $monthly,
            $groupStudents,
            $gAttendances,
            $gViolations,
            $term['latestTargets'],
            $term['latestHafalans'],
            $positionCheck,
            Carbon::parse(reset($monthRanges)['start']),
            Carbon::parse(end($monthRanges)['end'])
        );

        return [
            'musyrif' => $musyrifName,
            // Berkas tanda tangan guru pengampu (Profil) -- untuk Laporan Triwulan .xlsx.
            'musyrif_signature' => $musyrifSignature,
            'class_room_name' => $classRoom?->name ?? '-',
            'students' => $groupStudents,
            'is_tahfizh' => $isTahfizhProgram,
            // Grid presensi 3 bulan (khusus program Tahfizh); Reguler memakai presensi per bulan di 'monthly'.
            'presensi' => $isTahfizhProgram
                ? $this->buildTahfizhPresensiGrid($monthRanges, $groupStudents, $term['termAttendances'], $term['termHafalanRecords'])
                : [],
            'monthly' => $monthly,
            'term_records' => $termRecords,
            'months' => array_values($monthsMap),
            'total_students' => count($groupStudents),
            'tuntas_count' => collect($termRecords)->where('is_tuntas', true)->count(),
        ];
    }

    /**
     * Bangun seluruh data Laporan Triwulan (dipakai bersama oleh tampilan halaman & ekspor
     * spreadsheet, supaya isi file yang di-download selalu sama persis dengan yang tampil di layar).
     */
    private function buildReportData(Request $request, ?TeacherProfile $teacher = null): array
    {
        // Load all classrooms with their program (guru: hanya kelas yang punya murid aktif dia ampu)
        $classRooms = ClassRoom::query()
            ->with('program')
            ->when($teacher, fn ($query) => $query->whereIn(
                'id',
                $teacher->students()->where('status', 'active')->select('class_room_id')
            ))
            ->orderBy('name')
            ->get();
        $selectedClassId = $request->input('class_room_id', $classRooms->first()?->id);
        $selectedClass = $classRooms->firstWhere('id', $selectedClassId);

        if ($teacher && ! $selectedClass) {
            $selectedClass = $classRooms->first();
            $selectedClassId = $selectedClass?->id;
        }

        $termContext = $this->resolveTermContext($request);
        $academicYear = $termContext['academicYear'];
        $selectedTerm = $termContext['selectedTerm'];
        $monthsMap = $termContext['monthsMap'];
        $monthRanges = $termContext['monthRanges'];
        $termStartDate = $termContext['termStartDate'];
        $termEndDate = $termContext['termEndDate'];

        // Detect program type
        $programName = strtolower($selectedClass?->program?->name ?? '');
        $isTahfizhProgram = str_contains($programName, 'tahfizh') || str_contains($programName, 'akselerasi');

        // Get actual active students in the selected class
        $students = $teacher && ! $selectedClass
            ? collect()
            : Student::query()
                ->with(['classRoom', 'teacher.user'])
                ->where('class_room_id', $selectedClassId)
                ->when($teacher, fn ($query) => $query->where('teacher_id', $teacher->id))
                ->where('status', 'active')
                ->orderBy('name')
                ->get();

        // Fallback for empty seeded classrooms (admin saja -- guru tidak boleh melihat murid lain)
        if ($students->isEmpty() && ! $teacher) {
            $students = Student::query()
                ->with(['classRoom', 'teacher.user'])
                ->where('status', 'active')
                ->orderBy('name')
                ->take(10)
                ->get();
        }

        $studentIds = $students->pluck('id')->toArray();

        // Segarkan target otomatis per bulan (kelas 11 & 12) SEBELUM data term diambil, supaya
        // target yang baru dibuat ikut terbaca oleh fetchTermData(); target buatan guru tetap
        // menang. Kegagalan sinkron tidak boleh menghalangi laporan tampil.
        if ($selectedClass && ! $selectedClass->isGradeTen()) {
            try {
                app(AutoHafalanTargetService::class)->syncClass($selectedClass, Carbon::parse($termStartDate));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $term = $this->fetchTermData($studentIds, $termStartDate, $termEndDate);

        // Group students by their Musyrif
        $studentsByHalaqah = $students->groupBy(function ($student) {
            return $student->teacher?->user?->name ?? 'Ust. Fuad Faris Ghazi';
        });

        $halaqahData = [];
        $calendar = new AcademicCalendarService;

        // Kelas 10 (UMMI) tetap memakai target guru; kelas 11 & 12 juga dinilai dari posisi
        // capaian vs target (capaian >= target otomatis tuntas), selain dari jumlah baris.
        $positionCheck = ! ($selectedClass?->isGradeTen() ?? false) ? new QuranLineTargetService : null;

        foreach ($studentsByHalaqah as $musyrifName => $groupStudents) {
            $halaqahData[] = $this->buildHalaqahSection(
                $musyrifName,
                $groupStudents,
                $selectedClass,
                $isTahfizhProgram,
                $monthRanges,
                $monthsMap,
                $term,
                $calendar,
                $positionCheck,
                $groupStudents->first()?->teacher?->user?->signature_path
            );
        }

        return [
            'isTeacherView' => (bool) $teacher,
            'termEndDate' => $termEndDate,
            'classRooms' => $classRooms,
            'selectedClass' => $selectedClass,
            'isTahfizhProgram' => $isTahfizhProgram,
            'academicYear' => $academicYear,
            'selectedTerm' => $selectedTerm,
            'monthsMap' => $monthsMap,
            'halaqahData' => $halaqahData,
            'months' => array_values($monthsMap),
        ];
    }

    /**
     * Bangun laporan lintas-kelas untuk satu guru: semua kelas program reguler ATAU
     * tahfizh (tergantung $isTahfizhProgram) yang punya murid aktif diampu guru ini,
     * masing-masing jadi satu "kartu" (bagian) di dalam halaqahData -- sama seperti
     * per-halaqoh di buildReportData, hanya saja bagiannya per kelas, bukan per musyrif
     * (musyrifnya sudah pasti guru yang sama).
     */
    private function buildTeacherReportData(Request $request, TeacherProfile $teacherProfile, bool $isTahfizhProgram): array
    {
        $termContext = $this->resolveTermContext($request);
        $monthsMap = $termContext['monthsMap'];
        $monthRanges = $termContext['monthRanges'];
        $termStartDate = $termContext['termStartDate'];
        $termEndDate = $termContext['termEndDate'];

        $classRooms = ClassRoom::query()->with('program')->orderBy('level')->orderBy('name')->get()
            ->filter(function (ClassRoom $classRoom) use ($isTahfizhProgram) {
                $programName = strtolower($classRoom->program?->name ?? '');
                $classIsTahfizh = str_contains($programName, 'tahfizh') || str_contains($programName, 'akselerasi');

                return $classIsTahfizh === $isTahfizhProgram;
            });

        $musyrifName = $teacherProfile->user?->name ?? 'Guru';
        $calendar = new AcademicCalendarService;
        $halaqahData = [];

        foreach ($classRooms as $classRoom) {
            $groupStudents = $teacherProfile->students()
                ->with(['classRoom', 'teacher.user'])
                ->where('class_room_id', $classRoom->id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get();

            if ($groupStudents->isEmpty()) {
                continue;
            }

            $studentIds = $groupStudents->pluck('id')->toArray();

            if (! $classRoom->isGradeTen()) {
                try {
                    app(AutoHafalanTargetService::class)->syncClass($classRoom, Carbon::parse($termStartDate));
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            $term = $this->fetchTermData($studentIds, $termStartDate, $termEndDate);
            $positionCheck = ! $classRoom->isGradeTen() ? new QuranLineTargetService : null;

            $halaqahData[] = $this->buildHalaqahSection(
                $musyrifName,
                $groupStudents,
                $classRoom,
                $isTahfizhProgram,
                $monthRanges,
                $monthsMap,
                $term,
                $calendar,
                $positionCheck,
                $teacherProfile->user?->signature_path
            );
        }

        return [
            'isTahfizhProgram' => $isTahfizhProgram,
            'termEndDate' => $termEndDate,
            'academicYear' => $termContext['academicYear'],
            'selectedTerm' => $termContext['selectedTerm'],
            'monthsMap' => $monthsMap,
            'halaqahData' => $halaqahData,
            'months' => array_values($monthsMap),
        ];
    }

    private function dateString($value): string
    {
        return $value instanceof Carbon ? $value->toDateString() : Carbon::parse($value)->toDateString();
    }

    private function inRange($value, array $range): bool
    {
        $date = $this->dateString($value);

        return $date >= $range['start'] && $date <= $range['end'];
    }

    /**
     * Presensi Tahfizh: satu grid untuk seluruh bulan dalam term (maks. 12 pertemuan per bulan).
     */
    private function buildTahfizhPresensiGrid(array $monthRanges, $groupStudents, $classAttendances, $classHafalanRecords): array
    {
        $grid = [];

        foreach ($groupStudents as $student) {
            $studentAtt = $classAttendances->where('student_id', $student->id);
            $studentHaf = $classHafalanRecords->where('student_id', $student->id);
            $studentPresensi = [];

            foreach ($monthRanges as $range) {
                $mAtt = $studentAtt->filter(fn ($a) => $this->inRange($a->tanggal, $range));
                $mHaf = $studentHaf->filter(fn ($h) => $this->inRange($h->submitted_at, $range));

                $mUniqueDates = $classAttendances->filter(fn ($a) => $this->inRange($a->tanggal, $range))
                    ->pluck('tanggal')
                    ->map(fn ($d) => $this->dateString($d))
                    ->merge(
                        $classHafalanRecords->filter(fn ($h) => $this->inRange($h->submitted_at, $range))
                            ->pluck('submitted_at')
                            ->map(fn ($d) => $this->dateString($d))
                    )
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray();
                $mMeetings = array_slice($mUniqueDates, 0, 12);

                $mDays = [];
                for ($i = 1; $i <= 12; $i++) {
                    $date = $mMeetings[$i - 1] ?? null;
                    if ($date) {
                        $att = $mAtt->first(fn ($a) => $this->dateString($a->tanggal) === $date);
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
                            $mDays[$i] = $hasSetoran ? 'H' : '-';
                        }
                    } else {
                        $mDays[$i] = '-';
                    }
                }

                $studentPresensi[$range['label']] = [
                    'days' => $mDays,
                    'sakit' => $mAtt->where('status', 'sakit')->count(),
                    'izin' => $mAtt->where('status', 'izin')->count(),
                    'alpa' => $mAtt->where('status', 'alpa')->count(),
                ];
            }

            $grid[$student->id] = $studentPresensi;
        }

        return $grid;
    }

    /**
     * Laporan satu bulan untuk satu halaqoh: presensi (Reguler), jurnal, capaian setoran, dan ketuntasan.
     */
    private function buildMonthReport(array $range, array $context): array
    {
        $isTahfizhProgram = $context['isTahfizhProgram'];
        $groupStudents = $context['groupStudents'];
        $latestTargets = $context['latestTargets'];
        $latestHafalans = $context['latestHafalans'];
        $latestUmmiRecords = $context['latestUmmiRecords'] ?? collect();

        $gAttendances = $context['gAttendances']->filter(fn ($a) => $this->inRange($a->tanggal, $range));
        $gHafalanRecords = $context['gHafalanRecords']->filter(fn ($h) => $this->inRange($h->submitted_at, $range));
        $gUmmiRecords = ($context['gUmmiRecords'] ?? collect())->filter(fn ($u) => $this->inRange($u->tanggal, $range));
        $violations = $context['gViolations']->filter(fn ($v) => $this->inRange($v->date, $range));

        // Tanggal unik (presensi atau setoran) sekelas pada bulan ini -- dasar jurnal tatap muka.
        $uniqueDates = $context['classAttendances']->filter(fn ($a) => $this->inRange($a->tanggal, $range))
            ->pluck('tanggal')
            ->map(fn ($d) => $this->dateString($d))
            ->merge(
                $context['classHafalanRecords']->filter(fn ($h) => $this->inRange($h->submitted_at, $range))
                    ->pluck('submitted_at')
                    ->map(fn ($d) => $this->dateString($d))
            )
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // Hari efektif kelas di bulan ini (jadwal kelas, libur nasional, libur kelas):
        // membedakan "Libur" (tidak ada pertemuan) dari "Belum di input" (ada pertemuan
        // tapi musyrif belum mengisi).
        $monthStart = Carbon::parse($range['start']);
        $daysInMonth = $monthStart->daysInMonth;
        $effectiveByDay = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $effectiveByDay[$d] = $context['classRoom'] === null
                || $context['calendar']->isEffectiveDay($context['classRoom'], $monthStart->copy()->day($d));
        }
        $emptyPekanState = function (int $pStart, int $pEnd) use ($effectiveByDay, $daysInMonth): string {
            for ($d = $pStart; $d <= min($pEnd, $daysInMonth); $d++) {
                if ($effectiveByDay[$d]) {
                    return 'Belum di input';
                }
            }

            return 'Libur';
        };
        $emptyDayState = function (int $isoWeekday, int $pStart, int $pEnd) use ($effectiveByDay, $daysInMonth, $monthStart): string {
            for ($d = $pStart; $d <= min($pEnd, $daysInMonth); $d++) {
                if ((int) $monthStart->copy()->day($d)->format('N') === $isoWeekday) {
                    return $effectiveByDay[$d] ? 'Belum di input' : 'Libur';
                }
            }

            return '-';
        };

        // Tanggal & hari pertemuan aktif per pekan (jadwal kelas x kalender akademik),
        // dipakai sebagai label kolom "Pekan N" di Presensi/Setoran supaya menunjukkan
        // pertemuan sungguhan, bukan sekadar nomor pekan generik.
        $dayNames = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
        $pekanDates = [];
        for ($p = 1; $p <= 5; $p++) {
            $pStart = 1 + ($p - 1) * 7;
            $pEnd = min($p === 5 ? $daysInMonth : $p * 7, $daysInMonth);
            $dates = [];
            for ($d = $pStart; $d <= $pEnd; $d++) {
                if ($effectiveByDay[$d] ?? false) {
                    $date = $monthStart->copy()->day($d);
                    $dates[] = $dayNames[$date->dayOfWeekIso - 1].', '.$date->format('j M');
                }
            }
            $pekanDates[$p] = $dates;
        }

        // Jumlah pertemuan terjadwal bulan ini menurut kalender akademik & jadwal kelas
        // (bukan dari data yang sudah diinput musyrif) -- pengali target baris per bulan.
        // Program "seminggu sekali" dihitung maksimal satu pertemuan per pekan kalender.
        $isWeeklyProgram = $context['classRoom']?->program?->meeting_frequency === 'seminggu sekali';
        $scheduledMeetings = 0;
        $countedWeeks = [];
        foreach ($effectiveByDay as $day => $isEffective) {
            if (! $isEffective) {
                continue;
            }
            if ($isWeeklyProgram) {
                $weekKey = $monthStart->copy()->day($day)->format('o-W');
                if (isset($countedWeeks[$weekKey])) {
                    continue;
                }
                $countedWeeks[$weekKey] = true;
            }
            $scheduledMeetings++;
        }

        // A. Presensi mingguan (Reguler)
        $presensiData = [];
        if (! $isTahfizhProgram) {
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
                        // Tidak ada presensi tercatat untuk pekan ini: anggap hadir
                        // hanya kalau memang ada setoran nyata. Selain itu, pekan tanpa
                        // hari efektif = "Libur", pekan dengan hari efektif tapi belum
                        // diisi = "Belum di input" -- bukan otomatis hadir.
                        $pekan[$p] = $hasSetoran ? 'Hadir' : $emptyPekanState($pStart, $pEnd);
                    }
                }

                $presensiData[$student->id] = [
                    'pekan' => $pekan,
                    'hadir' => collect($pekan)->filter(fn ($status) => $status === 'Hadir')->count(),
                    'sakit' => $sAtt->where('status', 'sakit')->count(),
                    'izin' => $sAtt->where('status', 'izin')->count(),
                    'alpa' => $sAtt->where('status', 'alpa')->count(),
                ];
            }
        }

        // B. Jurnal
        $jurnalData = [];
        if ($isTahfizhProgram) {
            foreach ($uniqueDates as $date) {
                $jurnalData[] = [
                    'tanggal' => date('d-m-Y', strtotime($date)),
                    'materi' => "Muroja'ah & Ziyadah Hafalan",
                    'jumlah_murid' => $gAttendances->filter(fn ($a) => $this->dateString($a->tanggal) === $date)->where('status', 'hadir')->count() ?: count($groupStudents),
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
            // Reguler: satu baris per hari pertemuan aktif kelas (jadwal kelas x kalender
            // akademik), ditambah tanggal lain yang ternyata ada presensi/setorannya.
            // Program seminggu sekali: satu pertemuan per pekan -- tanggal yang ada datanya,
            // kalau belum ada, hari efektif pertama pekan itu.
            $meetingDates = [];
            foreach ($effectiveByDay as $day => $isEffective) {
                if (! $isEffective) {
                    continue;
                }
                $date = $monthStart->copy()->day($day);
                $meetingDates[$isWeeklyProgram ? $date->format('o-W') : $date->toDateString()] ??= $date->toDateString();
            }
            foreach ($uniqueDates as $date) {
                $key = $isWeeklyProgram ? Carbon::parse($date)->format('o-W') : $date;
                if ($isWeeklyProgram && isset($meetingDates[$key]) && ! in_array($meetingDates[$key], $uniqueDates, true)) {
                    $meetingDates[$key] = $date;
                }
                $meetingDates[$key] ??= $date;
            }
            $meetingDates = collect($meetingDates)->unique()->sort()->values();

            foreach ($meetingDates as $date) {
                $dayAttendances = $gAttendances->filter(fn ($a) => $this->dateString($a->tanggal) === $date);
                $daySetoranStudents = $gHafalanRecords->filter(fn ($h) => $this->dateString($h->submitted_at) === $date)
                    ->pluck('student_id')
                    ->unique();
                $held = $dayAttendances->isNotEmpty() || $daySetoranStudents->isNotEmpty();
                $carbonDate = Carbon::parse($date);

                $jurnalData[] = [
                    'tanggal' => $dayNames[$carbonDate->dayOfWeekIso - 1].', '.$carbonDate->format('d-m-Y'),
                    'materi' => "Muroja'ah & Ziyadah Hafalan",
                    'jumlah_murid' => $held
                        ? ($dayAttendances->isNotEmpty() ? $dayAttendances->where('status', 'hadir')->count() : $daySetoranStudents->count())
                        : null,
                    'paraf' => $held ? '✓' : '-',
                ];
            }

            if (empty($jurnalData)) {
                $jurnalData[] = [
                    'tanggal' => 'Tidak ada pertemuan terjadwal',
                    'materi' => '-',
                    'jumlah_murid' => null,
                    'paraf' => '-',
                ];
            }
        }

        // C. Capaian setoran
        $tahfizhRecords = [];
        $regulerRecords = [];

        foreach ($groupStudents as $student) {
            $sHaf = $gHafalanRecords->where('student_id', $student->id);
            $sAttAll = $gAttendances->where('student_id', $student->id);
            $sUmmi = $gUmmiRecords->where('student_id', $student->id);
            $isUmmiStudent = $student->tahfizh_level === 'ummi';
            $pekanRecords = [];
            $totalCapaianLines = 0;

            for ($p = 1; $p <= 5; $p++) {
                $pStart = 1 + ($p - 1) * 7;
                $pEnd = $p === 5 ? 31 : $p * 7;

                if ($isTahfizhProgram) {
                    $sAtt = $sAttAll;
                    $dailyLogs = [];
                    $weekLines = 0;

                    $pRecords = $sHaf->filter(function ($h) use ($pStart, $pEnd) {
                        $dayNum = (int) $h->submitted_at->format('d');

                        return $dayNum >= $pStart && $dayNum <= $pEnd;
                    });

                    $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
                    $dayMap = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat'];

                    foreach ($days as $dayName) {
                        $dayRecords = $pRecords->filter(function ($r) use ($dayName, $dayMap) {
                            $wDay = (int) date('w', strtotime($r->submitted_at));

                            return isset($dayMap[$wDay]) && $dayMap[$wDay] === $dayName;
                        })->filter(fn ($r) => $r->surah);

                        if ($dayRecords->isNotEmpty()) {
                            $lines = $dayRecords->sum('lines_count');
                            $surahLabel = $dayRecords
                                ->map(fn ($r) => "{$r->surah->name_latin} ({$r->ayah_start}-{$r->ayah_end})")
                                ->implode(', ');
                            $avgScore = $dayRecords->whereNotNull('score')->avg('score');
                            $dailyLogs[$dayName] = [
                                'surah' => $surahLabel,
                                'ayat_start' => '',
                                'ayat_end' => '',
                                'baris' => $lines,
                                'nilai' => self::mapScoreToGrade($avgScore),
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

                            $dailyLogs[$dayName] = [
                                'surah' => ($attRecord && $attRecord->status !== 'hadir')
                                    ? ucfirst($attRecord->status)
                                    : $emptyDayState(array_search($dayName, $dayMap), $pStart, $pEnd),
                                'ayat_start' => '',
                                'ayat_end' => '',
                                'baris' => 0,
                                'nilai' => '-',
                            ];
                        }
                    }

                    $pekanRecords[$p] = [
                        'days' => $dailyLogs,
                        'week_lines' => $weekLines,
                    ];
                    $totalCapaianLines += $weekLines;
                } else {
                    $weekUmmi = $isUmmiStudent ? $sUmmi->filter(function ($u) use ($pStart, $pEnd) {
                        $dayNum = (int) Carbon::parse($u->tanggal)->day;

                        return $dayNum >= $pStart && $dayNum <= $pEnd;
                    }) : collect();

                    $weekRecords = $sHaf->filter(function ($h) use ($pStart, $pEnd) {
                        $dayNum = (int) $h->submitted_at->format('d');

                        return $dayNum >= $pStart && $dayNum <= $pEnd;
                    })->filter(fn ($h) => $h->surah);

                    if ($isUmmiStudent && $weekUmmi->isNotEmpty()) {
                        // Kelas 10 / Metode Ummi: setoran dicatat sebagai Jilid & Halaman, bukan Surah & Ayat.
                        $lastUmmi = $weekUmmi->sortByDesc('tanggal')->first();
                        $lines = (float) $weekUmmi->sum(fn ($u) => $u->lines_count);
                        $pekanRecords[$p] = [
                            'surah' => $lastUmmi->ummi_jilid ?: '-',
                            'ayat' => $lastUmmi->ummi_halaman ?: '-',
                            'baris' => $lines,
                            'nilai' => $lastUmmi->nilai ?: '-',
                            'kehadiran' => 'Hadir',
                        ];
                        $totalCapaianLines += $lines;
                    } elseif ($weekRecords->isNotEmpty()) {
                        $lines = $weekRecords->sum('lines_count');
                        $avgScore = $weekRecords->whereNotNull('score')->avg('score');
                        $pekanRecords[$p] = [
                            'surah' => $weekRecords->map(fn ($h) => $h->surah->name_latin)->implode(', '),
                            'ayat' => $weekRecords->map(fn ($h) => "{$h->ayah_start}-{$h->ayah_end}")->implode(', '),
                            'baris' => $lines,
                            'nilai' => self::mapScoreToGrade($avgScore),
                            'kehadiran' => 'Hadir',
                        ];
                        $totalCapaianLines += $lines;
                    } else {
                        $pekanRecords[$p] = [
                            'surah' => '-',
                            'ayat' => '-',
                            'baris' => 0,
                            'nilai' => '-',
                            'kehadiran' => $presensiData[$student->id]['pekan'][$p],
                        ];
                    }
                }
            }

            $levelBaris = TargetRules::linesForLevel($student->tahfizh_level);
            $targetLines = ($levelBaris === null) ? 0 : ($levelBaris * $scheduledMeetings);
            $isTuntas = ($levelBaris === null) ? true : ($totalCapaianLines >= $targetLines);

            $studentTarget = $latestTargets->get($student->id)?->first();
            $studentHafalan = app(QuranLineTargetService::class)->latestByPosition($latestHafalans->get($student->id, collect()), $student->hafalan_direction);

            // Target: pakai Jilid/Halaman hanya kalau target guru memang dibuat lewat alur Ummi
            // (ummi_jilid terisi) -- murid Ummi bisa juga punya target Ziyadah Surah/Ayat biasa.
            if ($studentTarget?->ummi_jilid) {
                $targetSurah = $studentTarget->ummi_jilid;
                $targetAyat = trim('Peraga: '.($studentTarget->halaman_peraga ?: '-').' · Buku: '.($studentTarget->halaman_buku ?: '-'));
            } else {
                $targetSurah = $studentTarget?->surah?->name_latin ?? '-';
                $targetAyat = $studentTarget ? $studentTarget->ayah_range : '-';
            }

            // Capaian: pakai catatan Ummi terbaru kalau ada (Ziyadah, kalau ada, dihitung mundur
            // dari Juz 30), selain itu tetap posisi Surah/Ayat terjauh seperti biasa.
            $studentLatestUmmi = $isUmmiStudent ? $latestUmmiRecords->get($student->id, collect())->first() : null;
            if ($studentLatestUmmi) {
                $capaianSurah = $studentLatestUmmi->ummi_jilid ?: '-';
                $capaianAyat = $studentLatestUmmi->ummi_halaman ?: '-';
                $ziyadahRecord = app(QuranLineTargetService::class)->furthestRecord($latestHafalans->get($student->id, collect()), true);
                if ($ziyadahRecord?->surah) {
                    $capaianAyat .= ' (Ziyadah: '.$ziyadahRecord->surah->name_latin.' '.$ziyadahRecord->ayah_end.')';
                }
            } else {
                $capaianSurah = $studentHafalan?->surah?->name_latin ?? '-';
                $capaianAyat = $studentHafalan ? "{$studentHafalan->ayah_start}-{$studentHafalan->ayah_end}" : '-';
            }

            $record = [
                'student_id' => $student->id,
                'name' => $student->name,
                'nis' => $student->student_number ?? '4407-2526'.sprintf('%03d', $student->id),
                'level' => ucfirst($student->tahfizh_level ?? 'reguler'),
                'pekan' => $pekanRecords,
                'target_lines' => $targetLines,
                'total_lines' => $totalCapaianLines,
                'is_tuntas' => $isTuntas,
                'pelanggaran' => $violations->where('student_id', $student->id)->count(),
                'target_surah' => $targetSurah,
                'target_ayat' => $targetAyat,
                'capaian_surah' => $capaianSurah,
                'capaian_ayat' => $capaianAyat,
            ];

            if ($isTahfizhProgram) {
                $tahfizhRecords[] = $record;
            } else {
                $regulerRecords[] = $record;
            }
        }

        $records = $isTahfizhProgram ? $tahfizhRecords : $regulerRecords;

        return [
            'label' => $range['label'],
            'presensi' => $presensiData,
            'jurnal' => $jurnalData,
            'tahfizh_records' => $tahfizhRecords,
            'reguler_records' => $regulerRecords,
            'pekan_dates' => $pekanDates,
            'tuntas_count' => collect($records)->where('is_tuntas', true)->count(),
        ];
    }

    /**
     * Rekap satu term per murid: baris & target dijumlahkan dari semua bulan, absensi dan pelanggaran dihitung sepanjang term.
     */
    private function buildTermRecords(array $monthly, $groupStudents, $gAttendances, $gViolations, $latestTargets, $latestHafalans, ?QuranLineTargetService $positionCheck, Carbon $termStart, Carbon $termEnd): array
    {
        $termRecords = [];

        foreach ($groupStudents as $student) {
            $rows = collect($monthly)->map(function ($month) use ($student) {
                $records = $month['tahfizh_records'] ?: $month['reguler_records'];

                return collect($records)->firstWhere('student_id', $student->id);
            })->filter();

            $first = $rows->first();
            $totalLines = $rows->sum('total_lines');
            $targetLines = $rows->sum('target_lines');
            $studentAtt = $gAttendances->where('student_id', $student->id);

            // Ketercapaian: bila murid punya target posisi (surah & ayat), tuntas HANYA jika semua
            // ayat dari titik awal triwulan sampai target sudah lulus disetor (cakupan, urutan bebas;
            // lihat HafalanProgressService). Jumlah baris hanya dipakai bila tidak ada target posisi
            // (mis. Kelas 10/Ummi atau belum ada target).
            $studentTarget = $latestTargets->get($student->id)?->first();
            $evaluation = ($positionCheck && $studentTarget?->surah)
                ? app(HafalanProgressService::class)->evaluate($student, (int) $studentTarget->surah->number, (int) $studentTarget->ayah, $termStart, $termEnd, $termEnd)
                : null;

            $termRecords[] = [
                'student_id' => $student->id,
                'name' => $student->name,
                'level' => $first['level'] ?? ucfirst($student->tahfizh_level ?? 'reguler'),
                'target_surah' => $first['target_surah'] ?? '-',
                'target_ayat' => $first['target_ayat'] ?? '-',
                'capaian_surah' => $first['capaian_surah'] ?? '-',
                'capaian_ayat' => $first['capaian_ayat'] ?? '-',
                'total_lines' => $totalLines,
                'target_lines' => $targetLines,
                'is_tuntas' => $evaluation !== null ? $evaluation['reached'] : $totalLines >= $targetLines,
                'alpa' => $studentAtt->where('status', 'alpa')->count(),
                'izin' => $studentAtt->where('status', 'izin')->count(),
                'sakit' => $studentAtt->where('status', 'sakit')->count(),
                'pelanggaran' => $gViolations->where('student_id', $student->id)->count(),
            ];
        }

        return $termRecords;
    }
}
