<?php

namespace App\Http\Controllers;

use App\Models\AdabMentorAssessment;
use App\Models\AdabRecord;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\StudentReport;
use App\Models\TahfizhExam;
use App\Models\UmmiRecord;
use App\Services\StudentProgressService;
use Illuminate\Http\Request;

class StudentReportController extends Controller
{
    public function __construct(
        protected StudentProgressService $progressService
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

        $data = $this->getReportData($student, $academicYear, $semester);
        $data['report'] = $report;

        $totalSetoran = HafalanRecord::where('student_id', $student->id)->where('status', 'passed')->count();
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
        $canView = $this->progressService->visibleStudentQuery($user)
            ->where('id', $student->id)
            ->exists();
        abort_unless($canView, 403);

        $academicYear = $request->input('academic_year', '2025/2026');
        $semester = $request->integer('semester', 1);

        $data = $this->getReportData($student, $academicYear, $semester);

        return view('reports.digital-report-print', $data);
    }

    public function printClass(ClassRoom $classRoom, Request $request)
    {
        $user = $request->user();

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
            'hafalanRecords' => HafalanRecord::with('surah')
                ->whereIn('student_id', $visibleStudentIds)
                ->where('status', 'passed')
                ->latest('submitted_at')
                ->latest()
                ->get()
                ->groupBy('student_id'),
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
            'tahfizhExams' => TahfizhExam::with('surah')
                ->whereIn('student_id', $visibleStudentIds)
                ->latest('exam_date')
                ->latest()
                ->get()
                ->groupBy('student_id'),
            'ummiRecords' => UmmiRecord::with('surah')
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
            $reportsData[] = $this->getReportData($student, $academicYear, $semester, $batchContext);
        }

        return view('reports.digital-report-class-print', compact('classRoom', 'reportsData', 'academicYear', 'semester'));
    }

    private function getReportData(Student $student, string $academicYear, int $semester, ?array $batch = null): array
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
            $tahfizhExams = $batch['tahfizhExams']->get($student->id, collect())->take(5);
            $report = $batch['reports']->get($student->id);
            $studentUmmiAll = $batch['ummiRecords']->get($student->id, collect());
            $adabRecords = $batch['adabRecords']->get($student->id, collect());
            $violations = $batch['violations']->get($student->id, collect());
            $rewards = $batch['rewards']->get($student->id, collect());
        } else {
            $studentHafalanAll = HafalanRecord::with('surah')->where('student_id', $student->id)->where('status', 'passed')->latest('submitted_at')->latest()->get();
            $hafalanRecords = $studentHafalanAll->take(5);
            $murajaahRecords = MurajaahRecord::with('surah')->where('student_id', $student->id)->where('status', 'passed')->latest('reviewed_at')->latest()->limit(5)->get();
            $targetRecords = HafalanTarget::with('surah')->where('student_id', $student->id)->orderBy('target_date', 'asc')->limit(5)->get();
            $tahfizhExams = TahfizhExam::with('surah')->where('student_id', $student->id)->latest('exam_date')->latest()->limit(5)->get();
            $report = StudentReport::where([
                'student_id' => $student->id,
                'academic_year' => $academicYear,
                'semester' => $semester,
            ])->first();
            $studentUmmiAll = UmmiRecord::with('surah')->where('student_id', $student->id)->latest('tanggal')->latest()->get();
            $adabRecords = AdabRecord::where('student_id', $student->id)->get();
            $violations = StudentPoint::violations()->where('student_id', $student->id)->get();
            $rewards = StudentPoint::where('student_id', $student->id)->where('type', 'reward')->get();
        }

        foreach ($targetRecords as $target) {
            $matchingRecord = $studentHafalanAll
                ->where('surah_id', $target->surah_id)
                ->where('ayah_start', '<=', $target->ayah_start)
                ->where('ayah_end', '>=', $target->ayah_end)
                ->first();

            if (! $matchingRecord) {
                $matchingRecord = $studentHafalanAll
                    ->where('surah_id', $target->surah_id)
                    ->first();
            }

            $target->matching_record = $matchingRecord;
        }

        // Compute Tahfizh Level and targets
        $tahfizhLevelLabel = $student->tahfizh_level_label;
        $termTargetText = '';
        if ($report && $report->tahfizh_target_term) {
            $termTargetText = $report->tahfizh_target_term;
        } else {
            $classRoomName = $student->classRoom?->name ?? '';
            $classRoomLevel = $student->classRoom?->level ?? '';
            $isGrade10 = (bool) (
                (preg_match('/\bX\b/i', $classRoomName) && !preg_match('/\b(XI|XII)\b/i', $classRoomName))
                || preg_match('/\b10\b/i', $classRoomName)
                || preg_match('/^X[-_\s]?E/i', $classRoomName)
                || preg_match('/kelas\s*(X|10)/i', $classRoomName)
                || (preg_match('/\bX\b/i', $classRoomLevel) && !preg_match('/\b(XI|XII)\b/i', $classRoomLevel))
                || preg_match('/\b10\b/i', $classRoomLevel)
            ) && !preg_match('/\b(XI|XII|11|12)\b/i', $classRoomName);
            $isUmmiProgram = $isGrade10 || $student->tahfizh_level === 'ummi';

            if ($isUmmiProgram) {
                $termTargetText = 'Metode Bacaan Ummi (Target diisi Musyrif)';
            } else {
                $levelBaris = match ($student->tahfizh_level) {
                    'tahsin' => 3,
                    'reguler' => 5,
                    'akselerasi' => 7,
                    default => 5,
                };

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
                $rawTanggal = $latestUmmiRecord->getRawOriginal('tanggal');
                $latestUmmiRecords = $studentUmmiAll
                    ->filter(fn ($rec) => $rec->getRawOriginal('tanggal') === $rawTanggal && $rec->tatap_muka == $latestUmmiRecord->tatap_muka);

                $parts = [];
                $firstRec = $latestUmmiRecords->first();
                if ($firstRec && $firstRec->ummi_jilid) {
                    $parts[] = $firstRec->ummi_jilid.($firstRec->ummi_halaman ? ' Hal. '.$firstRec->ummi_halaman : '');
                }

                $surahParts = [];
                foreach ($latestUmmiRecords as $rec) {
                    if ($rec->hafalan_surah_id) {
                        $surahParts[] = 'Hafalan QS. '.($rec->surah?->name_latin ?? '').($rec->hafalan_ayah ? ' Ayat '.$rec->hafalan_ayah : '');
                    }
                }
                if (! empty($surahParts)) {
                    $parts[] = implode(', ', $surahParts);
                }

                $latestCapaianText = implode(', ', $parts);
                if ($firstRec && $firstRec->nilai) {
                    $latestCapaianText .= ' [Nilai: '.$firstRec->nilai.']';
                }
                $latestCapaianNotes = $latestUmmiRecords->pluck('keterangan')->filter()->unique()->implode('; ');
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
                $latestHafalan = $studentHafalanAll->first();
            }

            if ($latestHafalan) {
                $latestCapaianText = 'QS. '.($latestHafalan->surah?->name_latin ?? '').' (Ayat '.$latestHafalan->ayah_start.'-'.$latestHafalan->ayah_end.')';
                $latestCapaianNotes = $latestHafalan->notes;
            } else {
                $latestCapaianText = 'Belum ada data setoran.';
            }
        }

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

        // Tanse (Ketahanan Sekolah)
        $totalViolationPoints = $violations->sum('points');
        $latenessCount = $violations->where('type', 'lateness')->count();
        $attributeCount = $violations->where('type', 'attribute')->count();
        $tatibCount = $violations->where('type', 'violation')->count();

        if ($violations->isEmpty()) {
            $autoTanseNotes = "Murid menunjukkan kedisiplinan dan kepatuhan yang sangat baik terhadap tata tertib, atribut seragam, dan ketepatan waktu di sekolah (0 Poin Pelanggaran).";
            $tanseScore = 100;
            $tanseGrade = 'A';
        } else {
            $detailsArr = [];
            if ($latenessCount > 0) {
                $detailsArr[] = "{$latenessCount} Pelanggaran Keterlambatan";
            }
            if ($attributeCount > 0) {
                $detailsArr[] = "{$attributeCount} Pelanggaran Atribut/Seragam";
            }
            if ($tatibCount > 0) {
                $detailsArr[] = "{$tatibCount} Pelanggaran Tata Tertib";
            }
            $detailsStr = implode(', ', $detailsArr);

            $sanctions = $violations->pluck('sanction')->filter()->unique()->implode('; ');
            $sanctionStr = $sanctions ? " Sanksi/pembinaan: {$sanctions}." : "";

            $autoTanseNotes = "Murid memiliki total {$totalViolationPoints} poin pelanggaran pada semester ini ({$detailsStr}).{$sanctionStr} Diharapkan tingkat kedisiplinan murid lebih ditingkatkan.";

            $tanseScore = max(0, 100 - $totalViolationPoints);
            if ($tanseScore >= 90) {
                $tanseGrade = 'A';
            } elseif ($tanseScore >= 80) {
                $tanseGrade = 'B';
            } elseif ($tanseScore >= 70) {
                $tanseGrade = 'C';
            } elseif ($tanseScore >= 60) {
                $tanseGrade = 'D';
            } else {
                $tanseGrade = 'E';
            }
        }

        return compact(
            'student',
            'academicYear',
            'semester',
            'progress',
            'hafalanRecords',
            'murajaahRecords',
            'targetRecords',
            'tahfizhExams',
            'tahfizhLevelLabel',
            'termTargetText',
            'latestCapaianText',
            'latestCapaianNotes',
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

        return view('reports.digital-report-settings', compact(
            'classRooms', 'academicYear', 'semester', 'showTahfizh', 'showAdab', 'showTanse',
            'reportMainTitle', 'reportSchoolName', 'reportCity',
            'coordTahfizhName', 'coordTahfizhNik',
            'coordKeagamaanName', 'coordKeagamaanNik',
            'headmasterTitle', 'headmasterName', 'headmasterNik',
            'coordTanseName', 'coordTanseNik'
        ));
    }

    public function updateSettings(Request $request)
    {
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
