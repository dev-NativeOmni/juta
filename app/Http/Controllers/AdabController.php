<?php

namespace App\Http\Controllers;

use App\Models\AdabMentorAssessment;
use App\Models\AdabRecord;
use App\Models\ClassRoom;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdabController extends Controller
{
    /* -----------------------------------------------------------------------
     | INDEX
     * -------------------------------------------------------------------- */
    public function index(Request $request): View|RedirectResponse
    {
        $user = Auth::user();
        $isStudent = $user->hasRole('student');

        if ($isStudent) {
            $student = Student::where('user_id', $user->id)->first() ?? $user->studentProfile;

            if (! $student) {
                return redirect()->route('student.dashboard')
                    ->with('error', 'Profil murid Anda belum terhubung dengan akun ini. Silakan hubungi Admin.');
            }

            return redirect()->route('adab.show', $student);
        }

        $isAdmin = $user->hasAnyRole(['super_admin', 'admin']);
        $isSupervisor = $user->hasRole('supervisor');
        $isTeacher = $user->hasRole('teacher');
        $isParent = $user->hasRole('parent');
        $isPendampingAdab = $user->hasRole('pendamping_adab');

        if ($isParent) {
            $parentProfile = $user->parentProfile;
            $parentChildren = $parentProfile ? $parentProfile->students()->where('students.status', 'active')->get() : collect();

            if ($parentChildren->count() === 1) {
                return redirect()->route('adab.show', $parentChildren->first());
            }
        }

        $classRoomsQuery = ClassRoom::query()->orderBy('name');
        $studentQuery = Student::query()->with(['classRoom']);

        if ($isPendampingAdab && ! $isAdmin && ! $isSupervisor) {
            $assignedClassIds = ClassRoom::where(function ($q) use ($user) {
                $q->where('pendamping_adab_id', $user->id)
                    ->orWhereHas('pendampingAdabList', fn ($sub) => $sub->where('users.id', $user->id));
            })->pluck('id');
            $studentQuery->whereIn('class_room_id', $assignedClassIds);
            $classRoomsQuery->whereIn('id', $assignedClassIds);
        } elseif ($isTeacher) {
            $teacherProfile = $user->teacherProfile;
            $studentQuery->where('teacher_id', $teacherProfile?->id);
        } elseif ($isParent) {
            $parentProfile = $user->parentProfile;
            $studentQuery->whereHas('parents', function ($q) use ($parentProfile) {
                $q->where('parent_profiles.id', $parentProfile?->id);
            });
        }

        $classRooms = $classRoomsQuery->get();

        if ($request->filled('class_room_id')) {
            $studentQuery->where('class_room_id', $request->integer('class_room_id'));
        }
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $studentQuery->where('name', 'like', "%{$search}%");
        }

        $students = $studentQuery->orderBy('name')->paginate(20)->withQueryString();

        $today = now()->toDateString();
        $year = $request->integer('year', (int) now()->format('Y'));
        $month = $request->integer('month', (int) now()->format('n'));

        foreach ($students as $student) {
            $student->today_record = AdabRecord::where('student_id', $student->id)
                ->where('assessment_date', $today)
                ->first();

            $adabScoreData = Setting::calculateAdabScore($student->id, $year, $month);
            $student->adab_attendance_rate = $adabScoreData['attendance_rate'];
            $student->mentor_score = $adabScoreData['mentor_score'];
            $student->average_adab_score = $adabScoreData['final_score'];
            $student->adab_grade = $adabScoreData['grade'];
            $student->adab_grade_label = $adabScoreData['grade_label'];
        }

        // Categories & Stats
        $categories = Setting::getAdabQuestions();

        $allVisibleStudentIds = (clone $studentQuery)->pluck('id');
        $adabStats = AdabRecord::whereIn('student_id', $allVisibleStudentIds)
            ->whereNotNull('answers')
            ->get();

        $catStats = [];
        foreach ($categories as $catIdx => $cat) {
            $total = 0;
            $count = 0;
            foreach ($adabStats as $rec) {
                $answers = $rec->answers;
                if (isset($answers["cat_{$catIdx}"])) {
                    $catAnswers = $answers["cat_{$catIdx}"];
                    $count += count($catAnswers);
                    $total += array_sum(array_map(fn ($v) => $v ? 1 : 0, $catAnswers));
                }
            }
            $catStats[$catIdx] = $count > 0 ? round(($total / $count) * 100, 1) : 0;
        }

        try {
            if ($isParent) {
                $classRankings = collect();
            } else {
                $classRankings = Cache::remember("adab_class_rankings_{$year}_{$month}", 180, function () use ($year, $month) {
                    return ClassRoom::query()
                        ->with(['students' => fn ($q) => $q->where('status', 'active')])
                        ->get()
                        ->map(function ($classRoom) use ($year, $month) {
                            $st = $classRoom->students;
                            if ($st->isEmpty()) {
                                return ['name' => $classRoom->name, 'avg_score' => 0];
                            }
                            $scores = $st->map(function ($s) use ($year, $month) {
                                try {
                                    return Setting::calculateAdabScore($s->id, $year, $month)['final_score'] ?? 0;
                                } catch (\Throwable $e) {
                                    return 0;
                                }
                            });

                            return [
                                'name' => $classRoom->name,
                                'avg_score' => round($scores->avg() ?: 0, 1),
                            ];
                        })
                        ->sortByDesc('avg_score')
                        ->take(5)
                        ->values();
                });
            }
        } catch (\Throwable $e) {
            $classRankings = collect();
        }

        $canEvaluateMentor = ! $user->hasAnyRole(['student', 'parent']);

        return view('adab.index', compact(
            'students', 'classRooms', 'isAdmin', 'isSupervisor', 'canEvaluateMentor',
            'today', 'year', 'month', 'catStats', 'categories', 'classRankings'
        ));
    }

    /* -----------------------------------------------------------------------
     | MONTHLY CHART — Kuisioner Adab per Kelas per Bulan
     * -------------------------------------------------------------------- */
    public function monthlyChart(Request $request): View|RedirectResponse
    {
        $user = Auth::user();

        if ($user->hasRole('student') || $user->hasRole('parent')) {
            return redirect()->route('adab.index');
        }

        $year = $request->integer('year', (int) now()->format('Y'));
        $month = $request->integer('month', (int) now()->format('n'));

        $classRoomsQuery = ClassRoom::query()->with(['students' => function ($q) {
            $q->where('status', 'active');
        }])->orderBy('name');

        if ($user->hasRole('pendamping_adab') && ! $user->hasAnyRole(['super_admin', 'admin', 'supervisor'])) {
            $classRoomsQuery->where(function ($q) use ($user) {
                $q->where('pendamping_adab_id', $user->id)
                    ->orWhereHas('pendampingAdabList', fn ($sub) => $sub->where('users.id', $user->id));
            });
        }

        $classRooms = $classRoomsQuery->get();

        $allStudentsInScope = $classRooms->flatMap(fn ($c) => $c->students);
        $totalScopeStudents = $allStudentsInScope->count();
        $studentIds = $allStudentsInScope->pluck('id')->toArray();

        // Fetch national holidays for this year once
        $holidays = Setting::getNationalHolidays($year);

        // Fetch effective days count for the selected month
        $effectiveDaysTotal = Setting::getEffectiveDaysCount($year, $month);

        // Fetch all AdabRecord for the selected month in one query
        $startDate = Carbon::createFromDate($year, $month, 1)->toDateString();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();
        $adabRecordsThisMonth = AdabRecord::whereIn('student_id', $studentIds)
            ->whereBetween('assessment_date', [$startDate, $endDate])
            ->get()
            ->groupBy('student_id');

        // Fetch all mentor assessments for the selected month in one query
        $mentorAssessmentsThisMonth = AdabMentorAssessment::whereIn('student_id', $studentIds)
            ->where('year', $year)
            ->where('month', $month)
            ->get()
            ->keyBy('student_id');

        // Fetch latest mentor assessments for fallback
        $fallbackAssessments = AdabMentorAssessment::whereIn('student_id', $studentIds)
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->groupBy('student_id');

        $classReport = $classRooms->map(function ($classRoom) use ($year, $month, $effectiveDaysTotal, $adabRecordsThisMonth, $mentorAssessmentsThisMonth, $fallbackAssessments) {
            $students = $classRoom->students;
            $totalStudents = $students->count();

            if ($totalStudents === 0) {
                return [
                    'class_room' => $classRoom,
                    'total_students' => 0,
                    'avg_filled_days' => 0,
                    'attendance_rate' => 0,
                    'students_detail' => [],
                ];
            }

            $studentsDetail = [];
            $totalAttendanceRateSum = 0;
            $totalFilledDaysSum = 0;
            $effectiveDatesSet = Setting::getEffectiveDatesSet($year, $month);

            foreach ($students as $student) {
                // Calculate effective days filled in memory
                $studentRecs = $adabRecordsThisMonth->get($student->id, collect());
                $filledDates = $studentRecs->pluck('assessment_date')->unique();
                $effectiveDaysFilled = 0;
                foreach ($filledDates as $dateStr) {
                    $d = is_string($dateStr) ? substr($dateStr, 0, 10) : (is_object($dateStr) ? $dateStr->format('Y-m-d') : '');
                    if (isset($effectiveDatesSet[$d])) {
                        $effectiveDaysFilled++;
                    }
                }
                $attendanceRate = round(($effectiveDaysFilled / $effectiveDaysTotal) * 100, 1);
                $attendanceRate = min(100.0, $attendanceRate);

                // Mentor Score in memory
                $mentorAssessment = $mentorAssessmentsThisMonth->get($student->id);
                if (! $mentorAssessment) {
                    $studentFallbacks = $fallbackAssessments->get($student->id);
                    $mentorAssessment = $studentFallbacks ? $studentFallbacks->first() : null;
                }
                $mentorScore = $mentorAssessment ? (float) $mentorAssessment->mentor_score : null;

                if ($mentorScore !== null) {
                    $finalScore = round(($attendanceRate * 0.40) + ($mentorScore * 0.60), 1);
                } else {
                    $finalScore = $attendanceRate;
                }
                $grade = Setting::getAdabGrade($finalScore);

                $studentsDetail[] = [
                    'student' => $student,
                    'filled_days' => $effectiveDaysFilled,
                    'attendance_rate' => $attendanceRate,
                    'final_score' => $finalScore,
                    'grade' => $grade,
                    'mentor_score' => $mentorScore,
                ];

                $totalAttendanceRateSum += $attendanceRate;
                $totalFilledDaysSum += $effectiveDaysFilled;
            }

            return [
                'class_room' => $classRoom,
                'total_students' => $totalStudents,
                'avg_filled_days' => round($totalFilledDaysSum / $totalStudents, 1),
                'attendance_rate' => round($totalAttendanceRateSum / $totalStudents, 1),
                'students_detail' => $studentsDetail,
            ];
        });

        $totalStudentsAll = $classReport->sum('total_students');
        $overallAttendanceRate = $totalStudentsAll > 0
            ? round($classReport->sum(fn ($c) => $c['attendance_rate'] * $c['total_students']) / $totalStudentsAll, 1)
            : 0;

        $monthsList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        // ─── OPTIMIZED 12-Month Historical Trend ───

        $allYearRecords = AdabRecord::whereIn('student_id', $studentIds)
            ->whereBetween('assessment_date', [Carbon::createFromDate($year, 1, 1)->toDateString(), Carbon::createFromDate($year, 12, 31)->toDateString()])
            ->get()
            ->groupBy(function ($rec) {
                $m = (int) Carbon::parse($rec->assessment_date)->format('n');

                return $rec->student_id.'-'.$m;
            });

        $monthlyTrends = [];
        $effectiveDaysByMonth = [];
        $effectiveDatesSetByMonth = [];
        for ($m = 1; $m <= 12; $m++) {
            $effectiveDaysByMonth[$m] = Setting::getEffectiveDaysCount($year, $m);
            $effectiveDatesSetByMonth[$m] = Setting::getEffectiveDatesSet($year, $m);
        }

        for ($m = 1; $m <= 12; $m++) {
            if ($totalScopeStudents === 0) {
                $monthlyTrends[$m] = [
                    'month_name' => substr($monthsList[$m], 0, 3),
                    'full_month_name' => $monthsList[$m],
                    'rate' => 0,
                ];

                continue;
            }

            $rateSum = 0;
            $effDaysMonth = $effectiveDaysByMonth[$m];
            $monthDatesSet = $effectiveDatesSetByMonth[$m];

            foreach ($allStudentsInScope as $st) {
                // Get pre-grouped records from memory
                $studentMonthRecords = $allYearRecords->get($st->id.'-'.$m, collect());
                $filledDates = $studentMonthRecords->pluck('assessment_date')->unique();
                $effectiveDaysFilled = 0;
                foreach ($filledDates as $dateStr) {
                    $d = is_string($dateStr) ? substr($dateStr, 0, 10) : (is_object($dateStr) ? $dateStr->format('Y-m-d') : '');
                    if (isset($monthDatesSet[$d])) {
                        $effectiveDaysFilled++;
                    }
                }
                $attendanceRate = $effDaysMonth > 0 ? round(($effectiveDaysFilled / $effDaysMonth) * 100, 1) : 0;
                $rateSum += min(100.0, $attendanceRate);
            }
            $avgMonthRate = round($rateSum / $totalScopeStudents, 1);

            $monthlyTrends[$m] = [
                'month_name' => substr($monthsList[$m], 0, 3),
                'full_month_name' => $monthsList[$m],
                'rate' => $avgMonthRate,
            ];
        }

        return view('adab.chart', compact(
            'classReport', 'year', 'month', 'effectiveDaysTotal',
            'overallAttendanceRate', 'monthsList', 'monthlyTrends'
        ));
    }

    /* -----------------------------------------------------------------------
     | CREATE — show questionnaire form
     * -------------------------------------------------------------------- */
    public function create(Student $student): View|RedirectResponse
    {
        $user = Auth::user();

        $isOwn = $user->hasRole('student') && ((int) $student->user_id === (int) $user->id || $student->id === $user->studentProfile?->id);
        $isAdminOrSupervisor = $user->hasAnyRole(['super_admin', 'admin', 'supervisor']);
        $isPendampingAdab = $user->hasRole('pendamping_adab') && ($this->isUserAssignedPendamping($user, $student->classRoom) || ($student->classRoom?->pendamping_adab_id === null && $student->classRoom?->pendampingAdabList()->doesntExist()));
        $isTeacher = $user->hasRole('teacher') && $student->teacher_id === $user->teacherProfile?->id;

        abort_unless($isOwn || $isAdminOrSupervisor || $isPendampingAdab || $isTeacher, 403);

        $categories = Setting::getAdabQuestions();

        return view('adab.create', compact('student', 'categories'));
    }

    /* -----------------------------------------------------------------------
     | STORE — save student questionnaire
     * -------------------------------------------------------------------- */
    public function store(Request $request, Student $student): RedirectResponse
    {
        $user = Auth::user();

        $isOwn = $user->hasRole('student') && ((int) $student->user_id === (int) $user->id || $student->id === $user->studentProfile?->id);
        $isAdminOrSupervisor = $user->hasAnyRole(['super_admin', 'admin', 'supervisor']);
        $isPendampingAdab = $user->hasRole('pendamping_adab') && ($this->isUserAssignedPendamping($user, $student->classRoom) || ($student->classRoom?->pendamping_adab_id === null && $student->classRoom?->pendampingAdabList()->doesntExist()));
        $isTeacher = $user->hasRole('teacher') && $student->teacher_id === $user->teacherProfile?->id;

        if (! ($isOwn || $isAdminOrSupervisor || $isPendampingAdab || $isTeacher)) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Anda tidak memiliki izin untuk menyimpan data ini.');
        }

        $today = now()->toDateString();
        $categories = Setting::getAdabQuestions();

        $answers = [];
        $totalAnswers = 0;
        $positiveAnswers = 0;

        foreach ($categories as $catIdx => $cat) {
            $catKey = "cat_{$catIdx}";
            $catAnswers = $request->input("answers.{$catKey}", []);
            $processedAnswers = [];
            foreach ($cat['questions'] as $qIdx => $_) {
                $val = (bool) ($catAnswers[$qIdx] ?? $request->input("{$catKey}_q{$qIdx}", false));
                $processedAnswers[] = $val;
                $totalAnswers++;
                if ($val) {
                    $positiveAnswers++;
                }
            }
            $answers[$catKey] = $processedAnswers;
        }

        $studentScore = $totalAnswers > 0 ? round(($positiveAnswers / $totalAnswers) * 100, 2) : 0;

        AdabRecord::updateOrCreate(
            [
                'student_id' => $student->id,
                'assessment_date' => $today,
            ],
            [
                'evaluator_id' => $user->id,
                'answers' => $answers,
                'student_score' => $studentScore,
                'total_score' => $studentScore,
                'notes' => $request->input('notes'),
            ]
        );

        return redirect()->route('adab.show', $student)
            ->with('success', 'Kuisioner adab harian berhasil disimpan.');
    }

    /* -----------------------------------------------------------------------
     | SHOW — detail adab santri
     * -------------------------------------------------------------------- */
    public function show(Student $student, Request $request): View|RedirectResponse
    {
        $user = Auth::user();

        $visible = false;
        if ($user->hasAnyRole(['super_admin', 'admin', 'supervisor'])) {
            $visible = true;
        } elseif ($user->hasRole('pendamping_adab') && ($student->classRoom?->pendamping_adab_id === $user->id || $student->classRoom?->pendamping_adab_id === null)) {
            $visible = true;
        } elseif ($user->hasRole('teacher') && $student->teacher_id === $user->teacherProfile?->id) {
            $visible = true;
        } elseif ($user->hasRole('parent') && $student->parents->contains($user->parentProfile?->id)) {
            $visible = true;
        } elseif ($user->hasRole('student') && ((int) $student->user_id === (int) $user->id || $student->id === $user->studentProfile?->id)) {
            $visible = true;
        }

        abort_unless($visible, 403);

        $student->load(['classRoom', 'teacher.user']);

        $year = $request->integer('year', (int) now()->format('Y'));
        $month = $request->integer('month', (int) now()->format('n'));

        $adabRecords = AdabRecord::where('student_id', $student->id)
            ->with(['evaluator'])
            ->orderBy('assessment_date', 'desc')
            ->paginate(20);

        // Mentor assessments (periodic)
        $mentorAssessments = AdabMentorAssessment::where('student_id', $student->id)
            ->with('mentor')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        $latestMentor = $mentorAssessments->first();

        // New Adab Score 40/60 calculation
        $adabScoreData = Setting::calculateAdabScore($student->id, $year, $month);
        $attendanceRate = $adabScoreData['attendance_rate'];
        $effectiveDaysFilled = $adabScoreData['effective_days_filled'];
        $effectiveDaysTotal = $adabScoreData['effective_days_total'];
        $mentorScore = $adabScoreData['mentor_score'];
        $combinedScore = $adabScoreData['final_score'];
        $grade = $adabScoreData['grade'];
        $gradeLabel = $adabScoreData['grade_label'];

        // Per-category averages
        $categories = Setting::getAdabQuestions();
        $allRecords = AdabRecord::where('student_id', $student->id)->whereNotNull('answers')->get();
        $catAverages = [];
        foreach ($categories as $catIdx => $cat) {
            $total = 0;
            $count = 0;
            foreach ($allRecords as $rec) {
                $catAnswers = $rec->answers["cat_{$catIdx}"] ?? [];
                foreach ($catAnswers as $answer) {
                    $total += $answer ? 1 : 0;
                    $count++;
                }
            }
            $catAverages[$catIdx] = $count > 0 ? round(($total / $count) * 100, 1) : 0;
        }

        $mentorAlreadyScoredThisMonth = AdabMentorAssessment::where('student_id', $student->id)
            ->where('year', $year)
            ->where('month', $month)
            ->exists();

        $isMentor = $user->hasAnyRole(['super_admin', 'admin', 'supervisor'])
            || ($user->hasRole('pendamping_adab') && ($this->isUserAssignedPendamping($user, $student->classRoom) || ($student->classRoom?->pendamping_adab_id === null && $student->classRoom?->pendampingAdabList()->doesntExist())));

        return view('adab.show', compact(
            'student', 'adabRecords', 'mentorAssessments',
            'attendanceRate', 'effectiveDaysFilled', 'effectiveDaysTotal',
            'mentorScore', 'combinedScore', 'grade', 'gradeLabel',
            'categories', 'catAverages',
            'latestMentor', 'isMentor',
            'mentorAlreadyScoredThisMonth', 'year', 'month'
        ));
    }

    /* -----------------------------------------------------------------------
     | DESTROY — delete a daily record
     * -------------------------------------------------------------------- */
    public function destroy(AdabRecord $adabRecord): RedirectResponse
    {
        $user = Auth::user();
        if (! $user->hasAnyRole(['super_admin', 'admin', 'supervisor'])) {
            abort(403, 'Hanya Koordinator Keagamaan atau Admin yang dapat menghapus penilaian.');
        }

        $student = $adabRecord->student;
        $adabRecord->delete();

        return redirect()->route('adab.show', $student)
            ->with('success', 'Penilaian adab berhasil dihapus.');
    }

    /* -----------------------------------------------------------------------
     | STORE MENTOR SCORE — periodic (monthly)
     * -------------------------------------------------------------------- */
    public function storeMentorScore(Request $request, Student $student): RedirectResponse
    {
        $user = Auth::user();

        $isAuthorizedMentor = $user->hasAnyRole(['super_admin', 'admin', 'supervisor'])
            || ($user->hasRole('pendamping_adab') && ($this->isUserAssignedPendamping($user, $student->classRoom) || ($student->classRoom?->pendamping_adab_id === null && $student->classRoom?->pendampingAdabList()->doesntExist())));

        abort_unless(
            $isAuthorizedMentor,
            403, 'Hanya pendamping adab kelas ini atau admin yang dapat memberi nilai.'
        );

        $validated = $request->validate([
            'mentor_score' => 'required|integer|min:0|max:100',
            'year' => 'required|integer|min:2020|max:2099',
            'month' => 'required|integer|min:1|max:12',
            'notes' => 'nullable|string|max:1000',
        ]);

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $periodLabel = ($months[$validated['month']] ?? '-').' '.$validated['year'];

        AdabMentorAssessment::updateOrCreate(
            [
                'student_id' => $student->id,
                'year' => $validated['year'],
                'month' => $validated['month'],
            ],
            [
                'mentor_id' => $user->id,
                'mentor_score' => $validated['mentor_score'],
                'period_label' => $periodLabel,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return redirect()->route('adab.show', $student)
            ->with('success', "Nilai pendamping untuk periode {$periodLabel} berhasil disimpan: {$validated['mentor_score']}/100.");
    }

    /* -----------------------------------------------------------------------
     | BATCH / FAST STORE MENTOR SCORES — per class per month
     * -------------------------------------------------------------------- */
    public function batchStoreMentorScores(Request $request): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        if ($user->hasRole('student') || $user->hasRole('parent')) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2099',
            'month' => 'required|integer|min:1|max:12',
            'class_room_id' => 'nullable|integer',
            'entries' => 'required|array',
            'entries.*.student_id' => 'required|exists:students,id',
            'entries.*.mentor_score' => 'nullable|numeric|min:0|max:100',
            'entries.*.notes' => 'nullable|string|max:1000',
        ]);

        $year = (int) $validated['year'];
        $month = (int) $validated['month'];
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $periodLabel = ($months[$month] ?? '-').' '.$year;

        $savedCount = 0;

        DB::transaction(function () use ($validated, $user, $year, $month, $periodLabel, &$savedCount) {
            foreach ($validated['entries'] as $entry) {
                if (! isset($entry['mentor_score']) || $entry['mentor_score'] === '' || $entry['mentor_score'] === null) {
                    continue;
                }

                $student = Student::with('classRoom')->find($entry['student_id']);
                if (! $student) {
                    continue;
                }

                $isAuthorized = $user->hasAnyRole(['super_admin', 'admin', 'supervisor', 'headmaster'])
                    || ($user->hasRole('pendamping_adab') && ($this->isUserAssignedPendamping($user, $student->classRoom) || ($student->classRoom?->pendamping_adab_id === null && $student->classRoom?->pendampingAdabList()->doesntExist())))
                    || ($user->hasRole('teacher') && $student->teacher_id === $user->teacherProfile?->id);

                if (! $isAuthorized) {
                    continue;
                }

                AdabMentorAssessment::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'year' => $year,
                        'month' => $month,
                    ],
                    [
                        'mentor_id' => $user->id,
                        'mentor_score' => (int) $entry['mentor_score'],
                        'period_label' => $periodLabel,
                        'notes' => $entry['notes'] ?? null,
                    ]
                );

                $savedCount++;
            }
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Alhamdulillah, berhasil menyimpan {$savedCount} penilaian adab untuk periode {$periodLabel}.",
                'saved_count' => $savedCount,
            ]);
        }

        return redirect()->back()
            ->with('success', "Alhamdulillah, berhasil menyimpan {$savedCount} penilaian adab untuk periode {$periodLabel}.");
    }

    /* -----------------------------------------------------------------------
     | GET MENTOR CLASS DATA (AJAX for fast input)
     * -------------------------------------------------------------------- */
    public function getMentorClassData(Request $request): JsonResponse
    {
        $user = Auth::user();
        if ($user->hasRole('student') || $user->hasRole('parent')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $classRoomId = $request->integer('class_room_id');
        $year = $request->integer('year', (int) now()->format('Y'));
        $month = $request->integer('month', (int) now()->format('n'));

        $classRoom = ClassRoom::find($classRoomId);
        if (! $classRoom) {
            return response()->json(['students' => []]);
        }

        $isMentor = $user->hasAnyRole(['super_admin', 'admin', 'supervisor', 'headmaster'])
            || ($user->hasRole('pendamping_adab') && ($this->isUserAssignedPendamping($user, $classRoom) || ($classRoom->pendamping_adab_id === null && $classRoom->pendampingAdabList()->doesntExist())))
            || $user->hasRole('teacher');

        if (! $isMentor) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $students = Student::query()
            ->where('class_room_id', $classRoomId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $studentIds = $students->pluck('id');

        $currentAssessments = AdabMentorAssessment::whereIn('student_id', $studentIds)
            ->where('year', $year)
            ->where('month', $month)
            ->get()
            ->keyBy('student_id');

        // Previous month calculation
        $prevDate = Carbon::createFromDate($year, $month, 1)->subMonth();
        $prevAssessments = AdabMentorAssessment::whereIn('student_id', $studentIds)
            ->where('year', $prevDate->year)
            ->where('month', $prevDate->month)
            ->get()
            ->keyBy('student_id');

        $data = $students->map(function ($student) use ($currentAssessments, $prevAssessments) {
            $curr = $currentAssessments->get($student->id);
            $prev = $prevAssessments->get($student->id);

            return [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'student_number' => $student->student_number ?? '-',
                'gender' => $student->gender,
                'mentor_score' => $curr?->mentor_score !== null ? (int) $curr->mentor_score : '',
                'notes' => $curr?->notes ?? '',
                'previous_score' => $prev?->mentor_score !== null ? (int) $prev->mentor_score : null,
                'is_already_saved' => $curr !== null,
                'updated_at' => $curr?->updated_at?->format('d M Y H:i'),
            ];
        });

        return response()->json([
            'class_room_name' => $classRoom->name,
            'year' => $year,
            'month' => $month,
            'students' => $data,
        ]);
    }

    private function isUserAssignedPendamping(User $user, ?ClassRoom $classRoom): bool
    {
        if (! $classRoom) {
            return true;
        }

        return $classRoom->pendamping_adab_id === $user->id
            || $classRoom->pendampingAdabList()->where('users.id', $user->id)->exists();
    }
}
