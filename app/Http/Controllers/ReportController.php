<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\ParentProfile;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\UmmiRecord;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('student')) {
            $student = Student::query()->where('user_id', $user->id)->first();
            if ($student) {
                return redirect()->route('reports.student', $student);
            }
            abort(403, 'Akun murid belum memiliki profil murid.');
        }

        if ($user->hasRole('parent')) {
            $visibleStudentIds = $this->visibleStudentIds($user);
            if ($visibleStudentIds->count() === 1) {
                $student = Student::query()->find($visibleStudentIds->first());
                if ($student) {
                    return redirect()->route('reports.student', $student);
                }
            }
        }

        $visibleStudentIds = $this->visibleStudentIds($user);

        $hafalanQuery = $this->filteredHafalanQuery($request, $visibleStudentIds);
        $murajaahQuery = $this->filteredMurajaahQuery($request, $visibleStudentIds);
        $targetQuery = $this->filteredTargetQuery($request, $visibleStudentIds);

        $hafalanAgg = (clone $hafalanQuery)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) as passed_cnt,
                SUM(CASE WHEN status IN ('repeat', 'needs_improvement') THEN 1 ELSE 0 END) as repeat_cnt,
                AVG(score) as avg_score
            ")
            ->first();

        $murajaahAgg = (clone $murajaahQuery)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'passed' THEN 1 ELSE 0 END) as passed_cnt,
                SUM(CASE WHEN status IN ('repeat', 'needs_improvement') THEN 1 ELSE 0 END) as repeat_cnt,
                AVG(overall_score) as avg_score
            ")
            ->first();

        $targetAgg = (clone $targetQuery)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('active', 'planned', 'in_progress') THEN 1 ELSE 0 END) as active_cnt,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_cnt,
                SUM(CASE WHEN status = 'missed' THEN 1 ELSE 0 END) as missed_cnt
            ")
            ->first();

        $summary = [
            'total_students' => $visibleStudentIds->count(),
            'total_hafalan' => (int) ($hafalanAgg?->total ?? 0),
            'total_murajaah' => (int) ($murajaahAgg?->total ?? 0),
            'total_targets' => (int) ($targetAgg?->total ?? 0),
            'active_targets' => (int) ($targetAgg?->active_cnt ?? 0),
            'completed_targets' => (int) ($targetAgg?->completed_cnt ?? 0),
            'missed_targets' => (int) ($targetAgg?->missed_cnt ?? 0),
            'passed_hafalan' => (int) ($hafalanAgg?->passed_cnt ?? 0),
            'repeat_hafalan' => (int) ($hafalanAgg?->repeat_cnt ?? 0),
            'passed_murajaah' => (int) ($murajaahAgg?->passed_cnt ?? 0),
            'repeat_murajaah' => (int) ($murajaahAgg?->repeat_cnt ?? 0),
            'average_hafalan_score' => round((float) ($hafalanAgg?->avg_score ?? 0), 2),
            'average_murajaah_score' => round((float) ($murajaahAgg?->avg_score ?? 0), 2),
        ];

        $hafalanRecords = (clone $hafalanQuery)
            ->with([
                'student.classRoom.program',
                'student.teacher.user',
                'surah',
                'teacher.user',
            ])
            ->latest('submitted_at')
            ->latest()
            ->paginate(20, ['*'], 'hafalan_page')
            ->withQueryString();

        $murajaahRecords = (clone $murajaahQuery)
            ->with([
                'student.classRoom.program',
                'student.teacher.user',
                'surah',
                'teacher.user',
            ])
            ->latest('reviewed_at')
            ->latest()
            ->paginate(20, ['*'], 'murajaah_page')
            ->withQueryString();

        $hafalanTargets = (clone $targetQuery)
            ->with([
                'student.classRoom.program',
                'student.teacher.user',
                'surah',
                'teacher.user',
            ])
            ->orderByRaw("
                CASE
                    WHEN status IN ('active', 'planned', 'in_progress') THEN 0
                    WHEN status = 'missed' THEN 1
                    WHEN status = 'completed' THEN 2
                    ELSE 3
                END
            ")
            ->orderBy('target_date')
            ->paginate(20, ['*'], 'target_page')
            ->withQueryString();

        return view('reports.index', array_merge([
            'summary' => $summary,
            'hafalanRecords' => $hafalanRecords,
            'murajaahRecords' => $murajaahRecords,
            'hafalanTargets' => $hafalanTargets,
            'filters' => $request->only([
                'student_id',
                'class_room_id',
                'teacher_id',
                'surah_id',
                'status',
                'from',
                'to',
            ]),
        ], $this->filterData($visibleStudentIds)));
    }

    public function student(Request $request, Student $student): View
    {
        $visibleStudentIds = $this->visibleStudentIds($request->user());

        abort_unless($visibleStudentIds->contains($student->id), 403);

        $student->load([
            'user',
            'classRoom.program',
            'teacher.user',
            'parents.user',
        ]);

        $hafalanRecords = HafalanRecord::query()
            ->with(['surah', 'teacher.user'])
            ->where('student_id', $student->id)
            ->latest('submitted_at')
            ->latest()
            ->limit(500)
            ->get();

        $murajaahRecords = MurajaahRecord::query()
            ->with(['surah', 'teacher.user'])
            ->where('student_id', $student->id)
            ->latest('reviewed_at')
            ->latest()
            ->limit(500)
            ->get();

        $hafalanTargets = HafalanTarget::query()
            ->with(['surah', 'teacher.user'])
            ->where('student_id', $student->id)
            ->orderByRaw("
                CASE
                    WHEN status IN ('active', 'planned', 'in_progress') THEN 0
                    WHEN status = 'missed' THEN 1
                    WHEN status = 'completed' THEN 2
                    ELSE 3
                END
            ")
            ->orderBy('target_date')
            ->get();

        $summary = [
            'total_hafalan' => $hafalanRecords->count(),
            'total_murajaah' => $murajaahRecords->count(),
            'total_targets' => $hafalanTargets->count(),
            'active_targets' => $hafalanTargets
                ->whereIn('status', ['active', 'planned', 'in_progress'])
                ->count(),
            'completed_targets' => $hafalanTargets
                ->where('status', 'completed')
                ->count(),
            'missed_targets' => $hafalanTargets
                ->where('status', 'missed')
                ->count(),
            'average_hafalan_score' => round((float) $hafalanRecords->avg('score'), 2),
            'average_murajaah_score' => round((float) $murajaahRecords->avg('overall_score'), 2),
        ];

        return view('reports.student', compact(
            'student',
            'hafalanRecords',
            'murajaahRecords',
            'hafalanTargets',
            'summary'
        ));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        if (! $request->user()?->hasAnyRole(['super_admin', 'admin', 'teacher', 'coordinator_tahfizh', 'tanse'])) {
            abort(403, 'Anda tidak memiliki hak akses untuk ekspor data laporan.');
        }

        $visibleStudentIds = $this->visibleStudentIds($request->user());

        $hafalanQuery = $this->filteredHafalanQuery($request, $visibleStudentIds)
            ->with(['student.classRoom.program', 'surah', 'teacher.user'])
            ->latest('submitted_at')
            ->latest();

        $murajaahQuery = $this->filteredMurajaahQuery($request, $visibleStudentIds)
            ->with(['student.classRoom.program', 'surah', 'teacher.user'])
            ->latest('reviewed_at')
            ->latest();

        $fileName = 'laporan-ims-'.now()->format('Ymd-His').'.csv';

        // Gunakan cursor() agar hanya satu baris dimuat ke memory pada satu waktu.
        return response()->streamDownload(function () use ($hafalanQuery, $murajaahQuery) {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, [
                'Jenis',
                'Murid',
                'Kelas',
                'Program',
                'Surah',
                'Ayat Mulai',
                'Ayat Akhir',
                'Status',
                'Nilai',
                'Tanggal',
                'Guru',
                'Catatan',
            ]);

            foreach ($hafalanQuery->cursor() as $record) {
                fputcsv($handle, [
                    'Hafalan',
                    $record->student?->name,
                    $record->student?->classRoom?->name,
                    $record->student?->classRoom?->program?->name,
                    $record->surah?->name_latin ?? $record->surah?->name,
                    $record->ayah_start,
                    $record->ayah_end,
                    $record->status,
                    $record->score,
                    $this->formatDateForCsv($record->submitted_at),
                    $record->teacher?->user?->name,
                    $record->notes,
                ]);
            }

            foreach ($murajaahQuery->cursor() as $record) {
                fputcsv($handle, [
                    'Murajaah',
                    $record->student?->name,
                    $record->student?->classRoom?->name,
                    $record->student?->classRoom?->program?->name,
                    $record->surah?->name_latin ?? $record->surah?->name,
                    $record->ayah_start,
                    $record->ayah_end,
                    $record->status,
                    $record->overall_score,
                    $this->formatDateForCsv($record->reviewed_at),
                    $record->teacher?->user?->name,
                    $record->notes,
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportStudentCsv(Request $request, Student $student): StreamedResponse
    {
        $visibleStudentIds = $this->visibleStudentIds($request->user());

        abort_unless($visibleStudentIds->contains($student->id), 403);

        $request->merge([
            'student_id' => $student->id,
        ]);

        return $this->exportCsv($request);
    }

    private function filteredHafalanQuery(Request $request, Collection $visibleStudentIds): Builder
    {
        $query = HafalanRecord::query();

        $this->applyCommonFilters($query, $request, $visibleStudentIds);
        $this->applyDateFilters($query, $request, 'submitted_at');

        return $query;
    }

    private function filteredMurajaahQuery(Request $request, Collection $visibleStudentIds): Builder
    {
        $query = MurajaahRecord::query();

        $this->applyCommonFilters($query, $request, $visibleStudentIds);
        $this->applyDateFilters($query, $request, 'reviewed_at');

        return $query;
    }

    private function filteredTargetQuery(Request $request, Collection $visibleStudentIds): Builder
    {
        $query = HafalanTarget::query();

        $this->applyCommonFilters($query, $request, $visibleStudentIds, false);
        $this->applyDateFilters($query, $request, 'target_date');

        return $query;
    }

    private function applyCommonFilters(
        Builder $query,
        Request $request,
        Collection $visibleStudentIds,
        bool $allowStatusFilter = true
    ): void {
        $query->whereIn('student_id', $visibleStudentIds);

        $studentId = $request->integer('student_id');

        if ($studentId > 0) {
            abort_unless($visibleStudentIds->contains($studentId), 403);

            $query->where('student_id', $studentId);
        }

        if ($request->filled('class_room_id')) {
            $query->whereHas('student', function (Builder $studentQuery) use ($request) {
                $studentQuery->where('class_room_id', $request->integer('class_room_id'));
            });
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->integer('teacher_id'));
        }

        if ($request->filled('surah_id')) {
            $query->where('surah_id', $request->integer('surah_id'));
        }

        if ($allowStatusFilter && $request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
    }

    private function applyDateFilters(Builder $query, Request $request, string $column): void
    {
        if ($request->filled('from')) {
            $query->whereDate($column, '>=', $request->date('from')->toDateString());
        }

        if ($request->filled('to')) {
            $query->whereDate($column, '<=', $request->date('to')->toDateString());
        }
    }

    private function filterData(Collection $visibleStudentIds): array
    {
        $students = Student::query()
            ->with(['classRoom.program', 'teacher.user'])
            ->whereIn('id', $visibleStudentIds)
            ->orderBy('name')
            ->get();

        $classRoomIds = $students
            ->pluck('class_room_id')
            ->filter()
            ->unique()
            ->values();

        $teacherIds = $students
            ->pluck('teacher_id')
            ->filter()
            ->unique()
            ->values();

        return [
            'students' => $students,

            'classRooms' => ClassRoom::query()
                ->with('program')
                ->when($classRoomIds->isNotEmpty(), fn (Builder $query) => $query->whereIn('id', $classRoomIds))
                ->orderBy('name')
                ->get(),

            'teachers' => TeacherProfile::query()
                ->with('user')
                ->when($teacherIds->isNotEmpty(), fn (Builder $query) => $query->whereIn('id', $teacherIds))
                ->orderBy('id')
                ->get(),

            'surahs' => Surah::query()
                ->orderBy('number')
                ->get(),
        ];
    }

    private function visibleStudentIds(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        if ($this->userHasAnyRole($user, ['super_admin', 'admin', 'headmaster', 'supervisor', 'coordinator_tahfizh'])) {
            return Student::query()
                ->pluck('id');
        }

        if ($this->userHasAnyRole($user, ['teacher'])) {
            $teacherId = TeacherProfile::query()
                ->where('user_id', $user->id)
                ->value('id');

            if (! $teacherId) {
                return collect();
            }

            return Student::query()
                ->where('teacher_id', $teacherId)
                ->pluck('id');
        }

        if ($this->userHasAnyRole($user, ['parent'])) {
            $parentId = ParentProfile::query()
                ->where('user_id', $user->id)
                ->value('id');

            if (! $parentId || ! Schema::hasTable('parent_student')) {
                return collect();
            }

            return DB::table('parent_student')
                ->where('parent_id', $parentId)
                ->pluck('student_id');
        }

        if ($this->userHasAnyRole($user, ['student'])) {
            if (! Schema::hasColumn('students', 'user_id')) {
                return collect();
            }

            return Student::query()
                ->where('user_id', $user->id)
                ->pluck('id');
        }

        return collect();
    }

    private function userHasAnyRole(User $user, array $roles): bool
    {
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

    public function teacherPerformance(Request $request)
    {
        $month = (int) $request->input('month', date('n'));
        $year = (int) $request->input('year', date('Y'));

        // Fetch all active teachers sorted by user name
        $teachers = TeacherProfile::query()
            ->select('teacher_profiles.*')
            ->join('users', 'teacher_profiles.user_id', '=', 'users.id')
            ->where('users.status', 'active')
            ->orderBy('users.name')
            ->with('user')
            ->get();

        $performanceData = [];

        foreach ($teachers as $teacher) {
            // Count total hafalan inputs
            $totalHafalan = HafalanRecord::where('teacher_id', $teacher->id)
                ->whereYear('submitted_at', $year)
                ->whereMonth('submitted_at', $month)
                ->count();

            // Count total murajaah inputs
            $totalMurajaah = MurajaahRecord::where('teacher_id', $teacher->id)
                ->whereYear('reviewed_at', $year)
                ->whereMonth('reviewed_at', $month)
                ->count();

            // Count student targets
            $totalTargets = HafalanTarget::where('teacher_id', $teacher->id)
                ->whereYear('target_date', $year)
                ->whereMonth('target_date', $month)
                ->count();

            $completedTargets = HafalanTarget::where('teacher_id', $teacher->id)
                ->whereYear('target_date', $year)
                ->whereMonth('target_date', $month)
                ->where('status', 'completed')
                ->count();

            // Average student scores
            $avgHafalan = HafalanRecord::where('teacher_id', $teacher->id)
                ->whereYear('submitted_at', $year)
                ->whereMonth('submitted_at', $month)
                ->whereNotNull('score')
                ->avg('score');

            $avgMurajaah = MurajaahRecord::where('teacher_id', $teacher->id)
                ->whereYear('reviewed_at', $year)
                ->whereMonth('reviewed_at', $month)
                ->whereNotNull('overall_score')
                ->avg('overall_score');

            // Formulate metrics
            // 1. Keaktifan Input (Max 40 points) - Healthy input rate of at least 30 records per month
            $totalInputs = $totalHafalan + $totalMurajaah;
            $keaktifanScore = min(40.0, ($totalInputs / 30.0) * 40.0);

            // 2. Ketercapaian Target (Max 40 points)
            if ($totalTargets > 0) {
                $targetPercentage = ($completedTargets / $totalTargets) * 100.0;
            } else {
                $targetPercentage = 100.0; // Assume full score if no targets were set
            }
            $targetScore = ($targetPercentage / 100.0) * 40.0;

            // 3. Rerata Nilai Santri (Max 20 points)
            $scoresCount = 0;
            $scoresSum = 0;
            if ($avgHafalan !== null) {
                $scoresSum += $avgHafalan;
                $scoresCount++;
            }
            if ($avgMurajaah !== null) {
                $scoresSum += $avgMurajaah;
                $scoresCount++;
            }
            $avgStudentScore = $scoresCount > 0 ? ($scoresSum / $scoresCount) : 0.0;
            $studentScorePoints = ($avgStudentScore / 100.0) * 20.0;

            // Final Weighted Score (Max 100 points)
            $finalScore = round($keaktifanScore + $targetScore + $studentScorePoints, 2);

            // Performance Category
            if ($finalScore >= 85.0) {
                $category = 'Sangat Baik';
                $badgeColor = 'bg-green-100 text-green-800 border-green-200';
            } elseif ($finalScore >= 70.0) {
                $category = 'Baik';
                $badgeColor = 'bg-blue-100 text-blue-800 border-blue-200';
            } elseif ($finalScore >= 55.0) {
                $category = 'Cukup';
                $badgeColor = 'bg-yellow-100 text-yellow-800 border-yellow-200';
            } else {
                $category = 'Kurang';
                $badgeColor = 'bg-red-100 text-red-800 border-red-200';
            }

            $performanceData[] = [
                'teacher' => $teacher,
                'total_hafalan' => $totalHafalan,
                'total_murajaah' => $totalMurajaah,
                'total_inputs' => $totalInputs,
                'total_targets' => $totalTargets,
                'completed_targets' => $completedTargets,
                'target_percentage' => round($targetPercentage, 2),
                'avg_hafalan_score' => $avgHafalan !== null ? round($avgHafalan, 2) : null,
                'avg_murajaah_score' => $avgMurajaah !== null ? round($avgMurajaah, 2) : null,
                'avg_student_score' => round($avgStudentScore, 2),
                'keaktifan_score' => round($keaktifanScore, 2),
                'target_score' => round($targetScore, 2),
                'student_score_points' => round($studentScorePoints, 2),
                'final_score' => $finalScore,
                'category' => $category,
                'badge_color' => $badgeColor,
            ];
        }

        usort($performanceData, fn ($a, $b) => $b['final_score'] <=> $a['final_score']);

        return view('reports.teachers', [
            'performanceData' => $performanceData,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'months' => [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ],
            'years' => range(date('Y') - 4, date('Y') + 1),
        ]);
    }

    private function formatDateForCsv(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }

        return Carbon::parse($value)->format('Y-m-d');
    }

    public function periodicProgress(Request $request)
    {
        $data = $this->getPeriodicProgressData($request);
        if ($data instanceof RedirectResponse) {
            return $data;
        }

        return view('reports.periodic', $data);
    }

    public function periodicProgressPrint(Request $request)
    {
        $data = $this->getPeriodicProgressData($request);
        if ($data instanceof RedirectResponse) {
            return $data;
        }

        return view('reports.periodic-print', $data);
    }

    private function getPeriodicProgressData(Request $request): array
    {
        $user = $request->user();
        $visibleStudentIds = $this->visibleStudentIds($user);

        // Fetch classrooms available to user
        $classRooms = ClassRoom::query()
            ->with('program')
            ->whereHas('students', function ($q) use ($visibleStudentIds) {
                $q->whereIn('id', $visibleStudentIds);
            })
            ->orderBy('name')
            ->get();

        if ($classRooms->isEmpty()) {
            return [
                'classRooms' => collect(),
                'selectedClass' => null,
                'studentReports' => [],
                'groupedReports' => [],
                'summary' => [
                    'total_students' => 0,
                    'total_hafalan' => 0,
                    'total_murajaah' => 0,
                    'avg_hafalan_score' => 0,
                    'avg_murajaah_score' => 0,
                    'total_targets' => 0,
                    'completed_targets' => 0,
                    'target_completion_rate' => 100,
                ],
                'chartLabels' => [],
                'hafalanTrend' => [],
                'murajaahTrend' => [],
                'selectedClassId' => null,
                'periodType' => 'monthly',
                'selectedMonth' => date('n'),
                'selectedQuarter' => 1,
                'selectedYear' => date('Y'),
                'tuntasCount' => 0,
                'tidakTuntasCount' => 0,
                'monthsList' => [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                ],
            ];
        }

        $selectedClassId = $request->integer('class_room_id', $classRooms->first()->id);
        $periodType = $request->input('period_type', 'monthly');
        $selectedMonth = $request->integer('month', date('n'));

        $currentMonth = date('n');
        $defaultQuarter = 1;
        if ($currentMonth >= 7 && $currentMonth <= 9) {
            $defaultQuarter = 1;
        } elseif ($currentMonth >= 10 && $currentMonth <= 12) {
            $defaultQuarter = 2;
        } elseif ($currentMonth >= 1 && $currentMonth <= 3) {
            $defaultQuarter = 3;
        } elseif ($currentMonth >= 4 && $currentMonth <= 6) {
            $defaultQuarter = 4;
        }

        $selectedQuarter = $request->integer('quarter', $defaultQuarter);
        $selectedYear = $request->integer('year', date('Y'));

        // Calculate Date Range
        if ($periodType === 'monthly') {
            $startDate = Carbon::create($selectedYear, $selectedMonth, 1)->startOfMonth();
            $endDate = Carbon::create($selectedYear, $selectedMonth, 1)->endOfMonth();
        } else {
            // Quarterly
            switch ($selectedQuarter) {
                case 1:
                    $startDate = Carbon::create($selectedYear, 7, 1)->startOfDay();
                    $endDate = Carbon::create($selectedYear, 9, 30)->endOfDay();
                    break;
                case 2:
                    $startDate = Carbon::create($selectedYear, 10, 1)->startOfDay();
                    $endDate = Carbon::create($selectedYear, 12, 31)->endOfDay();
                    break;
                case 3:
                    $startDate = Carbon::create($selectedYear, 1, 1)->startOfDay();
                    $endDate = Carbon::create($selectedYear, 3, 31)->endOfDay();
                    break;
                case 4:
                default:
                    $startDate = Carbon::create($selectedYear, 4, 1)->startOfDay();
                    $endDate = Carbon::create($selectedYear, 6, 30)->endOfDay();
                    break;
            }
        }

        // Get class students
        $students = Student::query()
            ->with(['teacher.user'])
            ->whereIn('id', $visibleStudentIds)
            ->where('class_room_id', $selectedClassId)
            ->orderBy('name')
            ->get();

        $studentIds = $students->pluck('id');

        // Fetch records
        $hafalanRecords = HafalanRecord::query()
            ->with(['surah', 'student'])
            ->whereIn('student_id', $studentIds)
            ->where('status', 'passed')
            ->whereBetween('submitted_at', [$startDate, $endDate])
            ->get();

        $murajaahRecords = MurajaahRecord::query()
            ->with(['surah', 'student'])
            ->whereIn('student_id', $studentIds)
            ->where('status', 'passed')
            ->whereBetween('reviewed_at', [$startDate, $endDate])
            ->get();

        $targets = HafalanTarget::query()
            ->whereIn('student_id', $studentIds)
            ->whereBetween('target_date', [$startDate, $endDate])
            ->get();

        // Calculate trends
        $chartLabels = [];
        $hafalanTrend = [];
        $murajaahTrend = [];

        if ($periodType === 'monthly') {
            $chartLabels = ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4', 'Minggu 5'];
            $hafalanTrend = [0, 0, 0, 0, 0];
            $murajaahTrend = [0, 0, 0, 0, 0];

            foreach ($hafalanRecords as $record) {
                $day = Carbon::parse($record->submitted_at)->day;
                if ($day <= 7) {
                    $hafalanTrend[0]++;
                } elseif ($day <= 14) {
                    $hafalanTrend[1]++;
                } elseif ($day <= 21) {
                    $hafalanTrend[2]++;
                } elseif ($day <= 28) {
                    $hafalanTrend[3]++;
                } else {
                    $hafalanTrend[4]++;
                }
            }

            foreach ($murajaahRecords as $record) {
                $day = Carbon::parse($record->reviewed_at)->day;
                if ($day <= 7) {
                    $murajaahTrend[0]++;
                } elseif ($day <= 14) {
                    $murajaahTrend[1]++;
                } elseif ($day <= 21) {
                    $murajaahTrend[2]++;
                } elseif ($day <= 28) {
                    $murajaahTrend[3]++;
                } else {
                    $murajaahTrend[4]++;
                }
            }
        } else {
            // Quarterly
            if ($selectedQuarter === 1) {
                $chartLabels = ['Juli', 'Agustus', 'September'];
                $months = [7, 8, 9];
            } elseif ($selectedQuarter === 2) {
                $chartLabels = ['Oktober', 'November', 'Desember'];
                $months = [10, 11, 12];
            } elseif ($selectedQuarter === 3) {
                $chartLabels = ['Januari', 'Februari', 'Maret'];
                $months = [1, 2, 3];
            } else {
                $chartLabels = ['April', 'Mei', 'Juni'];
                $months = [4, 5, 6];
            }

            $hafalanTrend = [0, 0, 0];
            $murajaahTrend = [0, 0, 0];

            foreach ($hafalanRecords as $record) {
                $m = Carbon::parse($record->submitted_at)->month;
                $idx = array_search($m, $months);
                if ($idx !== false) {
                    $hafalanTrend[$idx]++;
                }
            }

            foreach ($murajaahRecords as $record) {
                $m = Carbon::parse($record->reviewed_at)->month;
                $idx = array_search($m, $months);
                if ($idx !== false) {
                    $murajaahTrend[$idx]++;
                }
            }
        }

        // Summary metrics
        $totalHafalan = $hafalanRecords->count();
        $totalMurajaah = $murajaahRecords->count();
        $avgHafalanScore = $hafalanRecords->avg('score') ? round((float) $hafalanRecords->avg('score'), 1) : 0;
        $avgMurajaahScore = $murajaahRecords->avg('overall_score') ? round((float) $murajaahRecords->avg('overall_score'), 1) : 0;

        $totalTargets = $targets->count();
        $completedTargets = $targets->where('status', 'completed')->count();
        $targetCompletionRate = $totalTargets > 0 ? round(($completedTargets / $totalTargets) * 100, 1) : 100;

        $summary = [
            'total_students' => $students->count(),
            'total_hafalan' => $totalHafalan,
            'total_murajaah' => $totalMurajaah,
            'avg_hafalan_score' => $avgHafalanScore,
            'avg_murajaah_score' => $avgMurajaahScore,
            'total_targets' => $totalTargets,
            'completed_targets' => $completedTargets,
            'target_completion_rate' => $targetCompletionRate,
        ];

        $selectedClass = $classRooms->firstWhere('id', $selectedClassId);
        $selectedClassName = $selectedClass?->name ?? '';
        $selectedClassLevel = $selectedClass?->level ?? '';

        $isGrade10 = (bool) (
            (preg_match('/\bX\b/i', $selectedClassName) && ! preg_match('/\b(XI|XII)\b/i', $selectedClassName))
            || preg_match('/\b10\b/i', $selectedClassName)
            || preg_match('/^X[-_\s]?E/i', $selectedClassName)
            || preg_match('/kelas\s*(X|10)/i', $selectedClassName)
            || (preg_match('/\bX\b/i', $selectedClassLevel) && ! preg_match('/\b(XI|XII)\b/i', $selectedClassLevel))
            || preg_match('/\b10\b/i', $selectedClassLevel)
        ) && ! preg_match('/\b(XI|XII|11|12)\b/i', $selectedClassName);

        // Detailed student list
        $studentReports = [];
        $tuntasCount = 0;
        $tidakTuntasCount = 0;

        // Bulk fetch latest targets for all students in scope
        $allLatestTargets = HafalanTarget::query()
            ->with('surah')
            ->whereIn('student_id', $studentIds)
            ->where('target_date', '<=', $endDate)
            ->orderBy('target_date', 'desc')
            ->get()
            ->groupBy('student_id');

        // Bulk fetch latest passed hafalan records for all students in scope
        $allPassedHafalan = HafalanRecord::query()
            ->with('surah')
            ->whereIn('student_id', $studentIds)
            ->where('status', 'passed')
            ->where('submitted_at', '<=', $endDate)
            ->orderBy('submitted_at', 'desc')
            ->get()
            ->groupBy('student_id');

        // Bulk fetch violations for all students in scope
        $allViolations = StudentPoint::query()
            ->whereIn('student_id', $studentIds)
            ->where('type', 'violation')
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('student_id');

        // Bulk fetch attendances for all students in scope
        $allAttendances = Attendance::query()
            ->whereIn('student_id', $studentIds)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->get()
            ->groupBy('student_id');

        // Bulk fetch latest UmmiRecords for Grade 10 / Ummi students
        $allUmmiRecords = UmmiRecord::query()
            ->with('surah')
            ->whereIn('student_id', $studentIds)
            ->where('tanggal', '<=', $endDate)
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('student_id');

        foreach ($students as $student) {
            $studentHafalan = $hafalanRecords->where('student_id', $student->id);
            $studentMurajaah = $murajaahRecords->where('student_id', $student->id);

            // Latest surah during the period
            $latestHafalan = $studentHafalan->sortByDesc('submitted_at')->first();
            $latestMurajaah = $studentMurajaah->sortByDesc('reviewed_at')->first();

            $latestProgressText = '-';
            if ($latestHafalan) {
                $latestProgressText = 'Hafalan: '.($latestHafalan->surah?->name_latin ?? '-').' (Ayat '.$latestHafalan->ayah_start.'-'.$latestHafalan->ayah_end.')';
            } elseif ($latestMurajaah) {
                $latestProgressText = 'Murajaah: '.($latestMurajaah->surah?->name_latin ?? '-').' (Ayat '.$latestMurajaah->ayah_start.'-'.$latestMurajaah->ayah_end.')';
            }

            // Average score
            $avgScore = $studentHafalan->avg('score') ? round((float) $studentHafalan->avg('score'), 1) : null;

            // Calculate Capaian Baris (lines of setoran)
            $capaianBaris = 0;
            foreach ($studentHafalan as $rec) {
                if ($rec->surah) {
                    $capaianBaris += $rec->lines_count;
                }
            }

            // Calculate Target Baris
            $levelBaris = match ($student->tahfizh_level) {
                'tahsin' => 3,
                'reguler' => 5,
                'akselerasi' => 7,
                'ummi' => null,
                default => 5,
            };

            if ($levelBaris === null) {
                $targetBaris = 0;
            } else {
                $meetingFrequency = $selectedClass?->program?->meeting_frequency ?? 'setiap hari';
                $meetings = $this->countMeetings($startDate, $endDate, $meetingFrequency, $selectedClass);
                $targetBaris = $levelBaris * $meetings;
            }

            // Check if student completed their target
            $isTuntas = ($levelBaris === null) ? true : ($capaianBaris >= $targetBaris);

            if ($isTuntas) {
                $tuntasCount++;
            } else {
                $tidakTuntasCount++;
            }

            // Target Surah and Ayat (latest target_date <= $endDate)
            $latestTarget = $allLatestTargets->get($student->id, collect())->first();

            $targetSurah = $latestTarget?->surah?->name_latin ?? '-';
            $targetAyat = $latestTarget?->ayah_end ?? '-';

            // Capaian Surah and Ayat (latest passed setoran submitted_at <= $endDate)
            $studentPassedRecords = $allPassedHafalan->get($student->id, collect());
            $latestHafalanPassed = $this->getFurthestHafalanRecord($studentPassedRecords, $isGrade10 || $student->tahfizh_level === 'ummi');

            $capaianSurah = $latestHafalanPassed?->surah?->name_latin ?? '-';
            $capaianAyat = $latestHafalanPassed?->ayah_end ?? '-';

            // Ummi Record Details
            $latestUmmi = $allUmmiRecords->get($student->id, collect())->first();
            $ummiJilidRaw = $latestUmmi?->ummi_jilid ?: '-';
            preg_match('/(\d+)/', (string) $ummiJilidRaw, $mJilid);
            $ummiJilidNum = isset($mJilid[1]) ? $mJilid[1] : (is_numeric($ummiJilidRaw) ? $ummiJilidRaw : $ummiJilidRaw);
            $rawHalaman = $latestUmmi?->ummi_halaman ?: '-';
            if ($rawHalaman !== '-' && ! empty($rawHalaman)) {
                $hParts = preg_split('/[-–—]/u', trim((string) $rawHalaman));
                $lastHPart = trim(end($hParts));
                $ummiHalaman = is_numeric($lastHPart) ? $lastHPart : $rawHalaman;
            } else {
                $ummiHalaman = '-';
            }
            $ummiCapaian = $latestUmmi?->surah?->name_latin ?? ($latestUmmi?->materi ?? '-');

            $ziyadahText = '-';
            if ($latestHafalanPassed && $latestHafalanPassed->surah) {
                $ziyadahText = $latestHafalanPassed->surah->name_latin.($latestHafalanPassed->ayah_end ? ' ('.$latestHafalanPassed->ayah_end.')' : '');
            }

            // Violations count during the period
            $violationsCount = $allViolations->get($student->id, collect())->count();

            // Attendance counts during the period
            $stAttendances = $allAttendances->get($student->id, collect());
            $sakit = $stAttendances->where('status', 'sakit')->count();
            $izin = $stAttendances->where('status', 'izin')->count();
            $alpa = $stAttendances->where('status', 'alpa')->count();
            $hadir = $stAttendances->where('status', 'hadir')->count();

            $studentReports[] = [
                'student' => $student,
                'total_hafalan' => $studentHafalan->count(),
                'total_murajaah' => $studentMurajaah->count(),
                'avg_score' => $avgScore,
                'latest_progress' => $latestProgressText,
                'capaian_baris' => $capaianBaris,
                'target_baris' => $targetBaris,
                'is_tuntas' => $isTuntas,
                'target_surah' => $targetSurah,
                'target_ayat' => $targetAyat,
                'capaian_surah' => $capaianSurah,
                'capaian_ayat' => $capaianAyat,
                'ummi_jilid' => $ummiJilidNum,
                'ummi_halaman' => $ummiHalaman,
                'ummi_capaian' => $ummiCapaian,
                'ziyadah' => $ziyadahText,
                'violations_count' => $violationsCount,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa,
                'hadir' => $hadir,
                'teacher_name' => $student->teacher?->user?->name ?? 'Tanpa Pembimbing',
                'halaqah_label' => $student->tahfizh_level_label,
            ];
        }

        // Group the student reports by Teacher and then by Halaqah (Level)
        $groupedReports = [];
        foreach ($studentReports as $report) {
            $tName = $report['teacher_name'];
            $hLabel = $report['halaqah_label'];
            $groupedReports[$tName][$hLabel][] = $report;
        }

        return [
            'classRooms' => $classRooms,
            'selectedClass' => $selectedClass,
            'isGrade10' => $isGrade10,
            'studentReports' => $studentReports,
            'groupedReports' => $groupedReports,
            'summary' => $summary,
            'chartLabels' => $chartLabels,
            'hafalanTrend' => $hafalanTrend,
            'murajaahTrend' => $murajaahTrend,
            'selectedClassId' => $selectedClassId,
            'periodType' => $periodType,
            'selectedMonth' => $selectedMonth,
            'selectedQuarter' => $selectedQuarter,
            'selectedYear' => $selectedYear,
            'tuntasCount' => $tuntasCount,
            'tidakTuntasCount' => $tidakTuntasCount,
            'monthsList' => [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ],
        ];
    }

    private function countMeetings(Carbon $startDate, Carbon $endDate, string $meetingFrequency, ClassRoom $classRoom): int
    {
        $meetings = 0;
        $current = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->endOfDay();
        $tahfizhDays = $classRoom->tahfizh_days;

        $year = $startDate->year;
        $holidays = Setting::getNationalHolidays($year);
        $classHolidaysRaw = Setting::get("class_holidays_{$year}");
        $classHolidays = $classHolidaysRaw ? json_decode($classHolidaysRaw, true) : [];

        if ($meetingFrequency === 'seminggu sekali') {
            $weeks = [];
            while ($current->lte($end)) {
                $dayOfWeek = $current->dayOfWeek;
                $isoDay = $dayOfWeek === 0 ? 7 : $dayOfWeek;
                $dateString = $current->toDateString();

                $isClassHoliday = isset($classHolidays[$dateString]) && in_array($classRoom->id, $classHolidays[$dateString]);

                if (in_array($isoDay, $tahfizhDays, true) && ! in_array($dateString, $holidays, true) && ! $isClassHoliday) {
                    $weekNum = $current->format('o-W');
                    $weeks[$weekNum] = true;
                }
                $current->addDay();
            }
            $meetings = count($weeks);
        } else {
            while ($current->lte($end)) {
                $dayOfWeek = $current->dayOfWeek;
                $isoDay = $dayOfWeek === 0 ? 7 : $dayOfWeek;
                $dateString = $current->toDateString();

                $isClassHoliday = isset($classHolidays[$dateString]) && in_array($classRoom->id, $classHolidays[$dateString]);

                if (in_array($isoDay, $tahfizhDays, true) && ! in_array($dateString, $holidays, true) && ! $isClassHoliday) {
                    $meetings++;
                }
                $current->addDay();
            }
        }

        return $meetings;
    }

    private static ?array $quranVerseLines = null;

    public static function getVerseLines(): array
    {
        if (self::$quranVerseLines !== null) {
            return self::$quranVerseLines;
        }

        $path = public_path('quran_verse_lines.json');
        if (! file_exists($path)) {
            $path = storage_path('app/quran_verse_lines.json');
        }

        if (file_exists($path)) {
            self::$quranVerseLines = json_decode(file_get_contents($path), true) ?: [];
        } else {
            self::$quranVerseLines = [];
        }

        return self::$quranVerseLines;
    }

    private static function fallbackCalculateLines(int $surahNumber, int $ayahStart, int $ayahEnd, int $totalAyah): float
    {
        $pages = [
            1 => 1.0, 2 => 48.0, 3 => 27.0, 4 => 29.0, 5 => 22.0, 6 => 23.0, 7 => 26.0, 8 => 10.0, 9 => 21.0, 10 => 13.0,
            11 => 14.0, 12 => 12.0, 13 => 7.0, 14 => 7.0, 15 => 6.0, 16 => 15.0, 17 => 12.0, 18 => 12.0, 19 => 7.0, 20 => 10.0,
            21 => 10.0, 22 => 10.0, 23 => 8.0, 24 => 10.0, 25 => 6.0, 26 => 11.0, 27 => 9.0, 28 => 11.0, 29 => 7.0, 30 => 6.0,
            31 => 4.0, 32 => 3.0, 33 => 9.0, 34 => 6.0, 35 => 6.0, 36 => 6.0, 37 => 7.0, 38 => 5.0, 39 => 8.0, 40 => 9.0,
            41 => 6.0, 42 => 6.0, 43 => 7.0, 44 => 3.0, 45 => 3.0, 46 => 4.0, 47 => 4.0, 48 => 4.0, 49 => 2.5, 50 => 3.0,
            51 => 2.5, 52 => 2.5, 53 => 2.5, 54 => 2.5, 55 => 3.0, 56 => 3.0, 57 => 4.0, 58 => 3.0, 59 => 3.0, 60 => 2.5,
            61 => 1.5, 62 => 1.5, 63 => 1.5, 64 => 2.0, 65 => 2.0, 66 => 2.0, 67 => 2.5, 68 => 2.0, 69 => 2.0, 70 => 2.0,
            71 => 1.5, 72 => 2.0, 73 => 1.5, 74 => 2.0, 75 => 2.0, 76 => 2.0, 77 => 2.0, 78 => 2.0, 79 => 2.0, 80 => 1.5,
            81 => 1.0, 82 => 1.0, 83 => 2.0, 84 => 1.0, 85 => 1.0, 86 => 1.0, 87 => 1.0, 88 => 1.0, 89 => 1.5, 90 => 1.0,
            91 => 1.0, 92 => 1.0, 93 => 0.5, 94 => 0.5, 95 => 0.5, 96 => 1.0, 97 => 0.5, 98 => 1.0, 99 => 0.5, 100 => 0.5,
            101 => 0.5, 102 => 0.5, 103 => 0.3, 104 => 0.5, 105 => 0.3, 106 => 0.3, 107 => 0.5, 108 => 0.3, 109 => 0.5, 110 => 0.3,
            111 => 0.3, 112 => 0.3, 113 => 0.3, 114 => 0.3,
        ];

        $pageCount = $pages[$surahNumber] ?? 1.0;
        $totalLines = $pageCount * 15.0;

        if ($totalAyah <= 0) {
            return 0.0;
        }

        $versesCount = max(1, $ayahEnd - $ayahStart + 1);
        $ratio = min(1.0, $versesCount / $totalAyah);

        return (float) round($ratio * $totalLines, 1);
    }

    /**
     * Estimates the lines count of a setoran based on the mushaf page layout (15 lines per page)
     */
    public static function calculateLines(int $surahNumber, int $ayahStart, int $ayahEnd, int $totalAyah): float
    {
        $linesMapping = self::getVerseLines();

        if (empty($linesMapping)) {
            return self::fallbackCalculateLines($surahNumber, $ayahStart, $ayahEnd, $totalAyah);
        }

        $keyStart = "{$surahNumber}:{$ayahStart}";
        $keyEnd = "{$surahNumber}:{$ayahEnd}";

        $startInfo = $linesMapping[$keyStart] ?? null;
        $endInfo = $linesMapping[$keyEnd] ?? null;

        if ($startInfo === null || $endInfo === null) {
            return self::fallbackCalculateLines($surahNumber, $ayahStart, $ayahEnd, $totalAyah);
        }

        $pageStart = $startInfo['page'];
        $pageEnd = $endInfo['page'];

        $lineStart = $startInfo['start'];
        $lineEnd = $endInfo['end'];

        if ($pageStart == $pageEnd) {
            // Same page: simply end_line - start_line + 1
            $lines = $lineEnd - $lineStart + 1;

            return (float) max(0, $lines);
        } else {
            // Start Page lines: from lineStart to the end of the start page
            $startPageCapacity = ($pageStart == 1 || $pageStart == 2) ? 7 : 15;
            $startPageLines = $startPageCapacity - $lineStart + 1;

            // End Page lines: from line 1 of the end page to lineEnd
            $endPageLines = $lineEnd;

            // Middle Pages lines
            $middleLines = 0;
            for ($p = $pageStart + 1; $p < $pageEnd; $p++) {
                $pageCapacity = ($p == 1 || $p == 2) ? 7 : 15;
                $middleLines += $pageCapacity;
            }

            return (float) max(0, $startPageLines + $middleLines + $endPageLines);
        }
    }

    public function whatsappDaily(Request $request)
    {
        $user = $request->user();
        $visibleStudentIds = $this->visibleStudentIds($user);

        // Fetch classrooms available to user
        $classRooms = ClassRoom::query()
            ->whereHas('students', function ($q) use ($visibleStudentIds) {
                $q->whereIn('id', $visibleStudentIds);
            })
            ->orderBy('name')
            ->get();

        if ($classRooms->isEmpty()) {
            return view('reports.whatsapp', [
                'classRooms' => collect(),
                'selectedClass' => null,
                'students' => [],
                'selectedDate' => date('Y-m-d'),
                'selectedClassId' => null,
                'hasUmmiRecords' => false,
                'classUmmiJilid' => '',
                'classUmmiHalaman' => '',
                'classUmmiHafalanSurah' => '',
                'musyrifName' => 'Tanpa Pembimbing',
            ]);
        }

        $selectedClassId = $request->integer('class_room_id', $classRooms->first()->id);
        $selectedClass = $classRooms->firstWhere('id', $selectedClassId);
        $selectedDate = $request->input('date', date('Y-m-d'));

        // Get class students
        $students = Student::query()
            ->with(['teacher.user'])
            ->whereIn('id', $visibleStudentIds)
            ->where('class_room_id', $selectedClassId)
            ->orderBy('name')
            ->get();

        $studentIds = $students->pluck('id');

        // Fetch records for this date
        $hafalanRecords = HafalanRecord::query()
            ->with(['surah'])
            ->whereIn('student_id', $studentIds)
            ->whereDate('submitted_at', $selectedDate)
            ->get();

        $murajaahRecords = MurajaahRecord::query()
            ->with(['surah'])
            ->whereIn('student_id', $studentIds)
            ->whereDate('reviewed_at', $selectedDate)
            ->get();

        $ummiRecords = UmmiRecord::query()
            ->with(['surah'])
            ->whereIn('student_id', $studentIds)
            ->whereDate('tanggal', $selectedDate)
            ->get();

        // Check if there is any UmmiRecord to suggest the default layout type
        $hasUmmiRecords = $ummiRecords->isNotEmpty();

        // Extract class-wide Ummi details if they exist
        $classUmmiJilid = '';
        $classUmmiHalaman = '';
        $classUmmiHafalanSurah = '';

        if ($hasUmmiRecords) {
            $firstUmmi = $ummiRecords->first();
            $classUmmiJilid = $firstUmmi->ummi_jilid;
            $classUmmiHalaman = $firstUmmi->ummi_halaman;
            if ($firstUmmi->surah) {
                $classUmmiHafalanSurah = $firstUmmi->surah->name_latin;
            }
        }

        // We will build the student records map for the frontend:
        $studentData = [];
        foreach ($students as $student) {
            $hRecs = $hafalanRecords->where('student_id', $student->id);
            $mRecs = $murajaahRecords->where('student_id', $student->id);
            $uRecs = $ummiRecords->where('student_id', $student->id);

            $progressParts = [];
            $totalLines = 0;

            // Ziyadah / Hafalan
            foreach ($hRecs as $rec) {
                if ($rec->surah) {
                    $lines = $rec->lines_count;
                    $totalLines += $lines;
                    $progressParts[] = "{$rec->surah->name_latin} ({$rec->ayah_start}-{$rec->ayah_end}) ({$lines} Baris)";
                }
            }

            // Murojaah - Rangkum seluruh surat yang dimuroja'ah di hari itu
            $murojaahParts = [];
            $sortedMRecs = $mRecs->sortBy(function ($r) {
                return $r->surah?->number ?? 999;
            });

            foreach ($sortedMRecs as $rec) {
                if ($rec->surah) {
                    $murojaahParts[] = "{$rec->surah->name_latin} ({$rec->ayah_start}-{$rec->ayah_end})";
                }
            }

            if (! empty($murojaahParts)) {
                $progressParts[] = 'murojaah '.implode(', ', $murojaahParts);
            }

            // Ummi progress
            $ummiProgressStr = '';
            if ($uRecs->isNotEmpty()) {
                $up = $uRecs->first();
                if ($up->ummi_jilid) {
                    $ummiProgressStr = "{$up->ummi_jilid} Halaman {$up->ummi_halaman}";
                }
            }

            $hasRecord = ($hRecs->isNotEmpty() || $mRecs->isNotEmpty() || $uRecs->isNotEmpty());

            $studentData[] = [
                'id' => $student->id,
                'name' => $student->name,
                'has_record' => $hasRecord,
                'progress' => implode(', ', $progressParts),
                'ummi_progress' => $ummiProgressStr,
                'total_lines' => $totalLines ?: null,
            ];
        }

        // Musyrif / Teacher Name for the halaqah
        $musyrifName = 'Tanpa Pembimbing';
        if ($students->isNotEmpty()) {
            $firstStudent = $students->first();
            if ($firstStudent->teacher && $firstStudent->teacher->user) {
                $musyrifName = $firstStudent->teacher->user->name;
            }
        }

        return view('reports.whatsapp', [
            'classRooms' => $classRooms,
            'selectedClass' => $selectedClass,
            'selectedClassId' => $selectedClassId,
            'selectedDate' => $selectedDate,
            'students' => $studentData,
            'hasUmmiRecords' => $hasUmmiRecords,
            'classUmmiJilid' => $classUmmiJilid,
            'classUmmiHalaman' => $classUmmiHalaman,
            'classUmmiHafalanSurah' => $classUmmiHafalanSurah,
            'musyrifName' => $musyrifName,
        ]);
    }

    private function getFurthestHafalanRecord(Collection $records, bool $isGrade10Ummi = false): mixed
    {
        if ($records->isEmpty()) {
            return null;
        }

        if ($isGrade10Ummi) {
            // Khusus Kelas 10 / Metode Ummi di Juz 30 (Surah 78 An-Naba' s/d 114 An-Naas):
            // Perjalanan dari Surah 114 (An-Naas) menuju 78 (An-Naba').
            // Capaian tertinggi/terjauh di Juz 30 adalah rekor dengan nomor surah paling kecil (mendekati 78).
            $juz30Records = $records->filter(function ($r) {
                $num = $r->surah?->number;

                return $num >= 78 && $num <= 114;
            });

            if ($juz30Records->isNotEmpty()) {
                return $juz30Records->sort(function ($a, $b) {
                    $numA = $a->surah?->number ?? 114;
                    $numB = $b->surah?->number ?? 114;
                    if ($numA !== $numB) {
                        return $numA <=> $numB; // Nomor surah lebih kecil = lebih dekat ke 78 An-Naba'
                    }
                    $dateA = $a->submitted_at ? Carbon::parse($a->submitted_at)->timestamp : 0;
                    $dateB = $b->submitted_at ? Carbon::parse($b->submitted_at)->timestamp : 0;

                    return $dateB <=> $dateA;
                })->first();
            }
        }

        return $records->sortByDesc(fn ($r) => $r->submitted_at ? Carbon::parse($r->submitted_at)->timestamp : 0)->first();
    }
}
