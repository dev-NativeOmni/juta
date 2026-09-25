<?php

namespace App\Http\Controllers;

use App\Models\AdabRecord;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanRecordSurah;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\StudentReport;
use App\Models\UmmiRecord;
use App\Services\QuranLineTargetService;
use App\Services\StudentProgressService;
use App\Support\TargetRules;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StudentReportController extends Controller
{
    public function __construct(
        protected StudentProgressService $progressService,
        protected QuranLineTargetService $positionCheck
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $visibleStudentQuery = $this->progressService->visibleStudentQuery($user);

        if ($request->filled('class_room_id')) {
            $visibleStudentQuery->where('class_room_id', $request->integer('class_room_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $visibleStudentQuery->where('name', 'like', "%{$search}%");
        }

        $students = $visibleStudentQuery->with(['classRoom'])->orderBy('name')->paginate(15)->withQueryString();

        $classRooms = ClassRoom::query()->orderBy('name')->get();

        return view('reports.digital-report-index', compact('students', 'classRooms'));
    }

    public function show(Student $student, Request $request)
    {
        $user = $request->user();

        // Authorize
        $canView = $this->progressService->visibleStudentQuery($user)
            ->where('id', $student->id)
            ->exists();
        abort_unless($canView, 403);

        $student->load(['classRoom.program', 'teacher.user', 'parents.user']);

        // Academic settings (default to 2025/2026 and semester 1)
        $academicYear = $request->input('academic_year', '2025/2026');
        $semester = $request->integer('semester', 1);

        $report = StudentReport::firstOrCreate([
            'student_id' => $student->id,
            'academic_year' => $academicYear,
            'semester' => $semester,
        ], [
            'status' => 'draft',
        ]);

        $data = $this->getReportData($student, $academicYear, $semester, null, $request->integer('term') ?: null);
        $data['report'] = $report;

        $totalSetoran = HafalanRecordSurah::whereHas('hafalanRecord', fn ($q) => $q->where('student_id', $student->id))->where('status', 'passed')->count();
        $totalMurajaah = MurajaahRecord::where('student_id', $student->id)->where('status', 'passed')->count();

        $canEditNotes = $user->hasAnyRole(['super_admin', 'admin', 'teacher']) && $report->status !== 'locked';

        return view('reports.digital-report', array_merge(
            $data,
            [
                'totalSetoran' => $totalSetoran,
                'totalMurajaah' => $totalMurajaah,
                'canEditNotes' => $canEditNotes,
            ]
        ));
    }

    public function update(Request $request, Student $student)
    {
        $user = $request->user();
        abort_unless($user->hasAnyRole(['super_admin', 'admin', 'teacher']), 403);

        $validated = $request->validate([
            'academic_year' => 'required|string',
            'semester' => 'required|integer|in:1,2',
            'teacher_notes' => 'nullable|string',
            'tahfizh_target_term' => 'nullable|string|max:255',
            'status' => 'required|string|in:draft,published,locked',
        ]);

        $report = StudentReport::updateOrCreate([
            'student_id' => $student->id,
            'academic_year' => $validated['academic_year'],
            'semester' => $validated['semester'],
        ], [
            'teacher_notes' => $validated['teacher_notes'],
            'tahfizh_target_term' => $validated['tahfizh_target_term'] ?? null,
            'status' => $validated['status'],
            'created_by' => $user->id,
        ]);

        return redirect()->back()->with('success', 'Catatan rapor digital berhasil diperbarui.');
    }

    public function print(Student $student, Request $request)
    {
        $user = $request->user();
        abort_unless(self::canPrint($user), 403, 'Akses cetak rapor tidak diizinkan untuk akun ini.');

        $canView = $this->progressService->visibleStudentQuery($user)
            ->where('id', $student->id)
            ->exists();
        abort_unless($canView, 403);

        $academicYear = $request->input('academic_year', '2025/2026');
        $semester = $request->integer('semester', 1);

        $data = $this->getReportData($student, $academicYear, $semester, null, $request->integer('term') ?: null);

        return view('reports.digital-report-print', $data);
    }

    public function printClass(ClassRoom $classRoom, Request $request)
    {
        $user = $request->user();
        abort_unless(self::canPrint($user), 403, 'Akses cetak rapor kelas tidak diizinkan untuk akun ini.');

        $visibleStudentIds = $this->progressService->visibleStudentQuery($user)
            ->where('class_room_id', $classRoom->id)
            ->pluck('id')
            ->toArray();

        $students = Student::whereIn('id', $visibleStudentIds)
            ->with(['classRoom.program', 'teacher.user', 'parents.user'])
            ->orderBy('name')
            ->get();

        if ($students->isEmpty()) {
            abort(404, 'Tidak ada murid di kelas ini yang dapat Anda akses.');
        }

        $academicYear = $request->input('academic_year', '2025/2026');
        $semester = $request->integer('semester', 1);

        // Batch prefetch all data for the entire classroom in single queries (reduces 700+ queries to <10)
        $batchContext = [
            'reports' => StudentReport::whereIn('student_id', $visibleStudentIds)
                ->where('academic_year', $academicYear)
                ->where('semester', $semester)
                ->get()
                ->keyBy('student_id'),
            'hafalanRecords' => HafalanRecord::flattenSurahs(
                HafalanRecord::with(['surahs' => fn ($q) => $q->where('status', 'passed')->with('surah')])
                    ->whereIn('student_id', $visibleStudentIds)
                    ->whereHas('surahs', fn ($q) => $q->where('status', 'passed'))
                    ->latest('submitted_at')
                    ->latest()
                    ->get()
            )->groupBy('student_id'),
            'murajaahRecords' => MurajaahRecord::with('surah')
                ->whereIn('student_id', $visibleStudentIds)
                ->where('status', 'passed')
                ->latest('reviewed_at')
                ->latest()
                ->get()
                ->groupBy('student_id'),
            'targetRecords' => HafalanTarget::with('surah')
                ->whereIn('student_id', $visibleStudentIds)
                ->orderBy('target_date', 'asc')
                ->get()
                ->groupBy('student_id'),
            'ummiRecords' => UmmiRecord::with('surahs.surah')
                ->whereIn('student_id', $visibleStudentIds)
                ->latest('tanggal')
                ->latest()
                ->get()
                ->groupBy('student_id'),
            'adabRecords' => AdabRecord::whereIn('student_id', $visibleStudentIds)
                ->get()
                ->groupBy('student_id'),
            'violations' => StudentPoint::violations()
                ->whereIn('student_id', $visibleStudentIds)
                ->get()
                ->groupBy('student_id'),
            'rewards' => StudentPoint::whereIn('student_id', $visibleStudentIds)
                ->where('type', 'reward')
                ->get()
                ->groupBy('student_id'),
        ];

        $reportsData = [];
        foreach ($students as $student) {
            $reportsData[] = $this->getReportData($student, $academicYear, $semester, $batchContext, $request->integer('term') ?: null);
        }

        $tanseTerm = self::resolveTanseTerm($academicYear, $semester, $request->integer('term') ?: null);

        return view('reports.digital-report-class-print', compact('classRoom', 'reportsData', 'academicYear', 'semester', 'tanseTerm'));
    }

    /**
     * Deskripsi Tanse bawaan per predikat; bisa diubah di Pengaturan Rapor (lihat tanseRules()).
     */
    public const TANSE_NOTES = [
        'A' => 'Alhamdulillah ananda sudah Sangat Baik dalam menerapkan budaya sekolah, disiplin, bertanggung jawab, santun, peduli, dan menjadi teladan bagi lingkungan sekitar. Semoga tetap istiqomah dalam menjalankan pembiasaan budaya sekolah dan berprestasi',
        'B' => 'Alhamdulillah ananda sudah Baik dalam menerapkan budaya sekolah dan masih memerlukan bimbingan serta pembiasaan dalam kedisiplinan, tanggung jawab, dan sikap santun. Semoga bisa istiqomah dalam menjalankan pembiasaan budaya sekolah.',
        'C' => 'Alhamdulillah ananda sudah Cukup Baik dalam menerapkan budaya sekolah, namun masih memerlukan bimbingan, pendampingan, pembiasaan dan konsistensi dalam kedisiplinan, tanggung jawab, dan sikap santun.',
    ];

    public const TANSE_DEFAULT_A_MIN = 90;

    public const TANSE_DEFAULT_B_MIN = 80;

    /**
     * Batas nilai & deskripsi predikat Tanse dari Pengaturan Rapor (default di atas).
     *
     * @return array{a_min: int, b_min: int, notes: array{A: string, B: string, C: string}}
     */
    public static function tanseRules(): array
    {
        $notes = json_decode((string) Setting::get('report_tanse_notes'), true) ?: [];

        return [
            'a_min' => (int) Setting::get('report_tanse_a_min', self::TANSE_DEFAULT_A_MIN),
            'b_min' => (int) Setting::get('report_tanse_b_min', self::TANSE_DEFAULT_B_MIN),
            'notes' => collect(self::TANSE_NOTES)->map(fn ($default, $grade) => trim((string) ($notes[$grade] ?? '')) ?: $default)->all(),
        ];
    }

    /**
     * Predikat Tanse dari skor (100 - poin pelanggaran triwulan): A >= batas A, B >= batas B, selain itu C.
     */
    public static function tanseGrade(int $score): string
    {
        $rules = self::tanseRules();

        return match (true) {
            $score >= $rules['a_min'] => 'A',
            $score >= $rules['b_min'] => 'B',
            default => 'C',
        };
    }

    public static function tanseNote(string $grade): string
    {
        return self::tanseRules()['notes'][$grade] ?? self::TANSE_NOTES['C'];
    }

    /**
     * Triwulan yang dipakai bagian Tanse. Semester 1 = triwulan 1 (Jul-Sep) & 2 (Okt-Des),
     * semester 2 = triwulan 3 (Jan-Mar) & 4 (Apr-Jun) -- sama dengan Laporan Triwulan.
     * Tanpa pilihan yang valid: triwulan yang sedang berjalan bila masih di semester itu,
     * selain itu triwulan terakhir semester tersebut.
     *
     * @return array{term: int, terms: array<int, string>, label: string, start: Carbon, end: Carbon}
     */
    /**
     * Cetak/unduh rapor: semua yang boleh melihat rapor, kecuali Pendamping Adab (lihat saja).
     */
    public static function canPrint($user): bool
    {
        return $user !== null && ! $user->hasAnyRole(['student', 'parent', 'pendamping_adab']);
    }

    /**
     * Tanggal BLP (titimangsa rapor) per semester: ASTS & ASAS (semester 1), ASTS & ASAT
     * (semester 2). Diatur per tahun ajaran di Pengaturan Rapor.
     */
    public const BLP_EXAMS = [
        1 => ['1_asts' => 'ASTS', '1_asas' => 'ASAS'],
        2 => ['2_asts' => 'ASTS', '2_asat' => 'ASAT'],
    ];

    /**
     * Tanggal BLP tersimpan untuk satu tahun ajaran: ['1_asts' => 'Y-m-d'|null, ...].
     *
     * @return array<string, string|null>
     */
    public static function blpDates(string $academicYear): array
    {
        $saved = json_decode((string) Setting::get(self::blpSettingKey($academicYear)), true) ?: [];
        $keys = array_merge(array_keys(self::BLP_EXAMS[1]), array_keys(self::BLP_EXAMS[2]));

        return collect($keys)->mapWithKeys(fn ($key) => [$key => $saved[$key] ?? null])->all();
    }

    public static function blpSettingKey(string $academicYear): string
    {
        return 'report_blp_dates_'.str_replace('/', '-', $academicYear);
    }

    /**
     * Titimangsa rapor: triwulan pertama semester memakai tanggal ASTS, triwulan kedua
     * memakai ASAS (semester 1) / ASAT (semester 2). Belum diatur = tanggal hari ini.
     *
     * @return array{date: string, exam: string, is_set: bool}
     */
    public static function reportDate(string $academicYear, int $semester, int $term): array
    {
        $exams = self::BLP_EXAMS[$semester === 2 ? 2 : 1];
        $isSecondTerm = in_array($term, [2, 4], true);
        $key = array_keys($exams)[$isSecondTerm ? 1 : 0];
        $saved = self::blpDates($academicYear)[$key];

        return [
            'date' => Carbon::parse($saved ?? now())->locale('id')->translatedFormat('d F Y'),
            'exam' => $exams[$key],
            'is_set' => $saved !== null,
        ];
    }

    public static function resolveTanseTerm(string $academicYear, int $semester, ?int $requested = null): array
    {
        $startYear = (int) explode('/', $academicYear)[0];
        $all = [
            1 => ['Triwulan 1 (Jul - Sep)', Carbon::create($startYear, 7, 1)],
            2 => ['Triwulan 2 (Okt - Des)', Carbon::create($startYear, 10, 1)],
            3 => ['Triwulan 3 (Jan - Mar)', Carbon::create($startYear + 1, 1, 1)],
            4 => ['Triwulan 4 (Apr - Jun)', Carbon::create($startYear + 1, 4, 1)],
        ];
        $semesterTerms = $semester === 2 ? [3, 4] : [1, 2];

        $term = in_array($requested, $semesterTerms, true) ? $requested : null;
        foreach ($semesterTerms as $candidate) {
            $term ??= now()->between($all[$candidate][1], $all[$candidate][1]->copy()->addMonths(2)->endOfMonth()) ? $candidate : null;
        }
        $term ??= end($semesterTerms);

        return [
            'term' => $term,
            'terms' => collect($semesterTerms)->mapWithKeys(fn ($t) => [$t => $all[$t][0]])->all(),
            'label' => $all[$term][0],
            'start' => $all[$term][1]->copy()->startOfDay(),
            'end' => $all[$term][1]->copy()->addMonths(2)->endOfMonth(),
        ];
    }

    private function getReportData(Student $student, string $academicYear, int $semester, ?array $batch = null, ?int $term = null): array
    {
        if (! $student->relationLoaded('classRoom')) {
            $student->load(['classRoom.program', 'teacher.user', 'parents.user']);
        }

        // Tahfizh
        $progress = $this->progressService->calculate($student);

        if ($batch) {
            $studentHafalanAll = $batch['hafalanRecords']->get($student->id, collect());
            $hafalanRecords = $studentHafalanAll->take(5);
            $murajaahRecords = $batch['murajaahRecords']->get($student->id, collect())->take(5);
            $targetRecords = $batch['targetRecords']->get($student->id, collect())->take(5);
            $report = $batch['reports']->get($student->id);
            $studentUmmiAll = $batch['ummiRecords']->get($student->id, collect());
            $adabRecords = $batch['adabRecords']->get($student->id, collect());
            $violations = $batch['violations']->get($student->id, collect());
            $rewards = $batch['rewards']->get($student->id, collect());
        } else {
            $studentHafalanAll = HafalanRecord::flattenSurahs(
                HafalanRecord::with(['surahs' => fn ($q) => $q->where('status', 'passed')->with('surah')])
                    ->where('student_id', $student->id)
                    ->whereHas('surahs', fn ($q) => $q->where('status', 'passed'))
                    ->latest('submitted_at')
                    ->latest()
                    ->get()
            );
            $hafalanRecords = $studentHafalanAll->take(5);
            $murajaahRecords = MurajaahRecord::with('surah')->where('student_id', $student->id)->where('status', 'passed')->latest('reviewed_at')->latest()->limit(5)->get();
            $targetRecords = HafalanTarget::with('surah')->where('student_id', $student->id)->orderBy('target_date', 'asc')->limit(5)->get();
            $report = StudentReport::where([
                'student_id' => $student->id,
                'academic_year' => $academicYear,
                'semester' => $semester,
            ])->first();
            $studentUmmiAll = UmmiRecord::with('surahs.surah')->where('student_id', $student->id)->latest('tanggal')->latest()->get();
            $adabRecords = AdabRecord::where('student_id', $student->id)->get();
            $violations = StudentPoint::violations()->where('student_id', $student->id)->get();
            $rewards = StudentPoint::where('student_id', $student->id)->where('type', 'reward')->get();
        }

        foreach ($targetRecords as $target) {
            $matchingRecord = $studentHafalanAll
                ->where('surah_id', $target->surah_id)
                ->where('ayah_end', '>=', $target->ayah)
                ->first();

            if (! $matchingRecord) {
                $matchingRecord = $studentHafalanAll
                    ->where('surah_id', $target->surah_id)
                    ->first();
            }

            $target->matching_record = $matchingRecord;
        }

        $tahfizhScore = Setting::calculateTahfizhScore($student);

        // Compute Tahfizh Level and targets
        $tahfizhLevelLabel = $student->tahfizh_level_label;
        $termTargetText = '';
        if ($report && $report->tahfizh_target_term) {
            $termTargetText = $report->tahfizh_target_term;
        } else {
            $classRoomName = $student->classRoom?->name ?? '';
            $classRoomLevel = $student->classRoom?->level ?? '';
            $isGrade10 = (bool) (
                (preg_match('/\bX\b/i', $classRoomName) && ! preg_match('/\b(XI|XII)\b/i', $classRoomName))
                || preg_match('/\b10\b/i', $classRoomName)
                || preg_match('/^X[-_\s]?E/i', $classRoomName)
                || preg_match('/kelas\s*(X|10)/i', $classRoomName)
                || (preg_match('/\bX\b/i', $classRoomLevel) && ! preg_match('/\b(XI|XII)\b/i', $classRoomLevel))
                || preg_match('/\b10\b/i', $classRoomLevel)
            ) && ! preg_match('/\b(XI|XII|11|12)\b/i', $classRoomName);
            $isUmmiProgram = $isGrade10 || $student->tahfizh_level === 'ummi';

            if ($isUmmiProgram) {
                $termTargetText = 'Metode Bacaan Ummi (Target diisi Musyrif)';
            } else {
                $levelBaris = TargetRules::linesForLevel($student->tahfizh_level) ?? TargetRules::linesForLevel('reguler');

                $programName = strtolower($student->classRoom?->program?->name ?? '');
                $meetingFrequency = $student->classRoom?->program?->meeting_frequency ?? 'setiap hari';

                $isWeeklyProgram = ($meetingFrequency === 'seminggu sekali')
                    || str_contains($programName, 'reguler')
                    || (bool) preg_match('/F[2-9]\b/i', $classRoomName);

                if (str_contains($programName, 'tahfizh') || (bool) preg_match('/F1\b/i', $classRoomName)) {
                    $isWeeklyProgram = false;
                }

                $meetings = $isWeeklyProgram ? 4 : 20;
                $totalTargetBaris = $levelBaris * $meetings;

                $termTargetText = "Target: {$levelBaris} baris/pertemuan x {$meetings} pertemuan = {$totalTargetBaris} baris/bulan";
            }
        }

        // Compute Capaian Terakhir
        $latestCapaianText = '';
        $latestCapaianNotes = '';

        if ($student->tahfizh_level === 'ummi') {
            $latestUmmiRecord = $studentUmmiAll->first();

            if ($latestUmmiRecord) {
                $parts = [];
                if ($latestUmmiRecord->ummi_jilid) {
                    $parts[] = $latestUmmiRecord->ummi_jilid.($latestUmmiRecord->ummi_halaman ? ' Hal. '.$latestUmmiRecord->ummi_halaman : '');
                }

                $surahParts = [];
                foreach ($latestUmmiRecord->surahs as $surahEntry) {
                    $surahParts[] = 'Hafalan QS. '.($surahEntry->surah?->name_latin ?? '').($surahEntry->hafalan_ayah ? ' Ayat '.$surahEntry->hafalan_ayah : '');
                }
                if (! empty($surahParts)) {
                    $parts[] = implode(', ', $surahParts);
                }

                $latestCapaianText = implode(', ', $parts);
                if ($latestUmmiRecord->nilai) {
                    $latestCapaianText .= ' [Nilai: '.$latestUmmiRecord->nilai.']';
                }
                $latestCapaianNotes = (string) $latestUmmiRecord->keterangan;
            } else {
                $latestCapaianText = 'Belum ada catatan UMMI.';
            }
        } else {
            $latestHafalan = null;
            if (isset($isUmmiProgram) && $isUmmiProgram) {
                $latestHafalan = $studentHafalanAll
                    ->filter(fn ($sq) => ($sq->surah?->number ?? 0) >= 78 && ($sq->surah?->number ?? 0) <= 114)
                    ->sortBy(fn ($r) => $r->surah?->number ?? 114)
                    ->first();
            }

            if (! $latestHafalan) {
                $latestHafalan = $this->positionCheck->latestByPosition($studentHafalanAll, $student->hafalan_direction);
            }

            if ($latestHafalan) {
                $latestCapaianText = 'QS. '.($latestHafalan->surah?->name_latin ?? '').' (Ayat '.$latestHafalan->ayah_start.'-'.$latestHafalan->ayah_end.')';
                $latestCapaianNotes = $latestHafalan->notes;
            } else {
                $latestCapaianText = 'Belum ada data setoran.';
            }
        }

        // Nilai mentah untuk baris "Ummi :" / "Tahfizh Ummi :" / "Tahfizh
        // Mandiri :" di kolom Target & Capaian rapor (khusus target yang
        // dibuat lewat alur UMMI, ditandai dengan target->ummi_jilid terisi
        // -- lihat reports/partials/tahfizh-target-capaian-cell.blade.php).
        // "Tahfizh Ummi" = hafalan yang dicatat di dalam sesi UMMI itu
        // sendiri (ummi_record_surahs). "Tahfizh Mandiri" = setoran hafalan
        // terpisah/mandiri (hafalan_records), keduanya ditampilkan sekaligus
        // karena murid Kelas 10 punya dua jalur hafalan yang berbeda.
        $latestUmmiJilid = $studentUmmiAll->first()?->ummi_jilid;
        $latestUmmiHalaman = $studentUmmiAll->first()?->ummi_halaman;
        $latestUmmiSurahEntry = $studentUmmiAll->first()?->surahs->last();
        $latestJuz30Hafalan = $studentHafalanAll
            ->filter(fn ($sq) => ($sq->surah?->number ?? 0) >= 78 && ($sq->surah?->number ?? 0) <= 114)
            ->sortBy(fn ($r) => $r->surah?->number ?? 114)
            ->first() ?? $this->positionCheck->latestByPosition($studentHafalanAll, $student->hafalan_direction);

        // Dynamic Adab Evaluation & Scores
        $adabCategories = Setting::getAdabQuestions();
        $adabCategoryScores = [];

        foreach ($adabCategories as $catIdx => $cat) {
            $total = 0;
            $count = 0;
            foreach ($adabRecords as $r) {
                if (! empty($r->answers) && isset($r->answers["cat_{$catIdx}"])) {
                    $catAns = $r->answers["cat_{$catIdx}"];
                    foreach ($catAns as $ans) {
                        $total += $ans ? 1 : 0;
                        $count++;
                    }
                }
            }
            $adabCategoryScores[$catIdx] = $count > 0 ? round(($total / $count) * 100, 1) : 0;
        }

        $thisYear = (int) now()->format('Y');
        $thisMonth = (int) now()->format('n');
        $adabScoreData = Setting::calculateAdabScore($student->id, $thisYear, $thisMonth);

        $avgAttendanceRate = $adabScoreData['attendance_rate'];
        $avgMentorScore = $adabScoreData['mentor_score'];
        $avgTotal = $adabScoreData['final_score'];
        $adabGrade = $adabScoreData['grade'];
        $adabGradeLabel = $adabScoreData['grade_label'];

        // Tanse (Ketahanan Sekolah): hanya poin dalam triwulan terpilih.
        $tanseTerm = self::resolveTanseTerm($academicYear, $semester, $term);
        $reportDate = self::reportDate($academicYear, $semester, $tanseTerm['term']);
        $inTanseTerm = fn ($point) => $point->date?->between($tanseTerm['start'], $tanseTerm['end']);
        $violations = $violations->filter($inTanseTerm)->values();
        $rewards = $rewards->filter($inTanseTerm)->values();

        $totalViolationPoints = $violations->sum('points');
        $latenessCount = $violations->where('type', 'lateness')->count();
        $attributeCount = $violations->where('type', 'attribute')->count();
        $tatibCount = $violations->where('type', 'violation')->count();

        $tanseScore = max(0, 100 - $totalViolationPoints);
        $tanseGrade = self::tanseGrade($tanseScore);
        $autoTanseNotes = self::tanseNote($tanseGrade);

        return compact(
            'student',
            'academicYear',
            'semester',
            'progress',
            'hafalanRecords',
            'murajaahRecords',
            'targetRecords',
            'tahfizhScore',
            'tahfizhLevelLabel',
            'termTargetText',
            'latestCapaianText',
            'latestCapaianNotes',
            'latestUmmiJilid',
            'latestUmmiHalaman',
            'latestUmmiSurahEntry',
            'latestJuz30Hafalan',
            'adabCategories',
            'adabCategoryScores',
            'avgAttendanceRate',
            'avgMentorScore',
            'avgTotal',
            'adabGrade',
            'adabGradeLabel',
            'violations',
            'rewards',
            'totalViolationPoints',
            'latenessCount',
            'attributeCount',
            'tatibCount',
            'autoTanseNotes',
            'tanseScore',
            'tanseGrade',
            'tanseTerm',
            'reportDate',
            'report'
        );
    }

    public function settings(Request $request)
    {
        $classRooms = ClassRoom::orderBy('name')->get();
        $academicYear = Setting::get('academic_year', '2025/2026');
        $semester = (int) Setting::get('semester', 1);

        $showTahfizh = Setting::get('report_show_tahfizh', '1') === '1';
        $showAdab = Setting::get('report_show_adab', '1') === '1';
        $showTanse = Setting::get('report_show_tanse', '1') === '1';

        // Template Settings
        $reportMainTitle = Setting::get('report_main_title', 'LAPORAN TAHFIDZ, ADAB DAN TANSE');
        $reportSchoolName = Setting::get('report_school_name', 'SMA ISLAM AL AZHAR 7 SUKOHARJO');
        $reportCity = Setting::get('report_city', 'Sukoharjo');

        $coordTahfizhName = Setting::get('report_coord_tahfizh_name', 'Zainal Arifin, S.Pd');
        $coordTahfizhNik = Setting::get('report_coord_tahfizh_nik', '15.06.0393');

        $coordKeagamaanName = Setting::get('report_coord_keagamaan_name', 'Rifqi Ihsan, S.Pd., Gr.');
        $coordKeagamaanNik = Setting::get('report_coord_keagamaan_nik', '15.06.0393');

        $headmasterTitle = Setting::get('report_headmaster_title', 'Kepala SMA Islam Al Azhar 7 Sukoharjo');
        $headmasterName = Setting::get('report_headmaster_name', 'Moh Pandoyo, S.Si., M.Pd., Gr.');
        $headmasterNik = Setting::get('report_headmaster_nik', '08.04.0160');

        $coordTanseName = Setting::get('report_coord_tanse_name', 'Yatim Hermawan, S.E., S.Kom');
        $coordTanseNik = Setting::get('report_coord_tanse_nik', '15.06.0393');

        $blpDates = self::blpDates($academicYear);
        $tanseRules = self::tanseRules();

        return view('reports.digital-report-settings', compact(
            'classRooms', 'academicYear', 'semester', 'showTahfizh', 'showAdab', 'showTanse', 'blpDates', 'tanseRules',
            'reportMainTitle', 'reportSchoolName', 'reportCity',
            'coordTahfizhName', 'coordTahfizhNik',
            'coordKeagamaanName', 'coordKeagamaanNik',
            'headmasterTitle', 'headmasterName', 'headmasterNik',
            'coordTanseName', 'coordTanseNik'
        ));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'blp_dates' => 'nullable|array', 'blp_dates.*' => 'nullable|date',
            'tanse_a_min' => 'nullable|integer|between:1,100',
            'tanse_b_min' => 'nullable|integer|between:0,100|lt:tanse_a_min',
            'tanse_notes' => 'nullable|array', 'tanse_notes.*' => 'nullable|string|max:1000',
        ], ['tanse_b_min.lt' => 'Batas predikat B harus lebih kecil dari batas predikat A.']);

        if ($request->filled('tanse_a_min')) {
            Setting::set('report_tanse_a_min', (string) $request->integer('tanse_a_min'));
            Setting::set('report_tanse_b_min', (string) $request->integer('tanse_b_min'));
            Setting::set('report_tanse_notes', json_encode(collect(self::TANSE_NOTES)
                ->map(fn ($default, $grade) => trim((string) $request->input("tanse_notes.{$grade}")) ?: $default)
                ->all()));
        }

        // Tanggal BLP disimpan untuk tahun ajaran yang sedang diatur di form ini.
        $academicYear = (string) $request->input('academic_year', '2025/2026');
        $blp = collect(self::blpDates($academicYear))
            ->map(fn ($old, $key) => $request->input("blp_dates.{$key}") ?: null)
            ->all();
        Setting::set(self::blpSettingKey($academicYear), json_encode($blp));

        Setting::set('academic_year', $request->input('academic_year', '2025/2026'));
        Setting::set('semester', $request->input('semester', 1));
        Setting::set('report_show_tahfizh', $request->has('report_show_tahfizh') ? '1' : '0');
        Setting::set('report_show_adab', $request->has('report_show_adab') ? '1' : '0');
        Setting::set('report_show_tanse', $request->has('report_show_tanse') ? '1' : '0');

        // Template Settings
        Setting::set('report_main_title', $request->input('report_main_title', 'LAPORAN TAHFIDZ, ADAB DAN TANSE'));
        Setting::set('report_school_name', $request->input('report_school_name', 'SMA ISLAM AL AZHAR 7 SUKOHARJO'));
        Setting::set('report_city', $request->input('report_city', 'Sukoharjo'));

        Setting::set('report_coord_tahfizh_name', $request->input('report_coord_tahfizh_name', 'Zainal Arifin, S.Pd'));
        Setting::set('report_coord_tahfizh_nik', $request->input('report_coord_tahfizh_nik', '15.06.0393'));

        Setting::set('report_coord_keagamaan_name', $request->input('report_coord_keagamaan_name', 'Rifqi Ihsan, S.Pd., Gr.'));
        Setting::set('report_coord_keagamaan_nik', $request->input('report_coord_keagamaan_nik', '15.06.0393'));

        Setting::set('report_headmaster_title', $request->input('report_headmaster_title', 'Kepala SMA Islam Al Azhar 7 Sukoharjo'));
        Setting::set('report_headmaster_name', $request->input('report_headmaster_name', 'Moh Pandoyo, S.Si., M.Pd., Gr.'));
        Setting::set('report_headmaster_nik', $request->input('report_headmaster_nik', '08.04.0160'));

        Setting::set('report_coord_tanse_name', $request->input('report_coord_tanse_name', 'Yatim Hermawan, S.E., S.Kom'));
        Setting::set('report_coord_tanse_nik', $request->input('report_coord_tanse_nik', '15.06.0393'));

        return redirect()->back()->with('success', 'Pengaturan Rapor Digital & Template Cetak berhasil disimpan.');
    }
}
