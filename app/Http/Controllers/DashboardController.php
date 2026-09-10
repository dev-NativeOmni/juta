<?php

namespace App\Http\Controllers;

use App\Models\AdabMaterial;
use App\Models\AdabRecord;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\TahfizhExam;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {
        //
    }

    public function redirect(Request $request): RedirectResponse
    {
        $user = $request->user()->loadMissing('role');

        if (! $user->isActive()) {
            auth()->logout();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Akun Anda sedang nonaktif.',
                ]);
        }

        $activeRoleName = $user->currentRole()?->name ?? $user->role?->name;

        return match ($activeRoleName) {
            'super_admin' => redirect()->route('super-admin.dashboard'),
            'admin' => redirect()->route('admin.dashboard'),
            'teacher' => redirect()->route('teacher.dashboard'),
            'parent' => redirect()->route('parent.dashboard'),
            'student' => redirect()->route('student.dashboard'),
            'supervisor' => redirect()->route('supervisor.dashboard'),
            'headmaster' => redirect()->route('headmaster.dashboard'),
            'tanse' => redirect()->route('tanse.dashboard'),
            'coordinator_tahfizh' => redirect()->route('coordinator-tahfizh.dashboard'),
            'pendamping_adab' => redirect()->route('pendamping-adab.dashboard'),
            default => redirect()->route('admin.dashboard'),
        };
    }

    public function superAdmin(): View
    {
        try {
            $stats = $this->dashboardService->adminStats();
        } catch (\Throwable $e) {
            $stats = [];
        }
        $today = now()->toDateString();
        try {
            $stats['adab_filled_today'] = AdabRecord::where('assessment_date', $today)->count();
            $stats['adab_total_students'] = Student::count();
        } catch (\Throwable $e) {
            $stats['adab_filled_today'] = 0;
            $stats['adab_total_students'] = 0;
        }

        return view('dashboards.admin', [
            'title' => 'Super Admin Dashboard',
            'subtitle' => 'Monitoring penuh seluruh data IMS.',
            'stats' => $stats,
        ]);
    }

    public function admin(): View
    {
        try {
            $stats = $this->dashboardService->adminStats();
        } catch (\Throwable $e) {
            $stats = [];
        }

        $today = now()->toDateString();
        try {
            $stats['adab_filled_today'] = AdabRecord::where('assessment_date', $today)->count();
            $stats['adab_total_students'] = Student::count();
        } catch (\Throwable $e) {
            $stats['adab_filled_today'] = 0;
            $stats['adab_total_students'] = 0;
        }

        return view('dashboards.admin', [
            'title' => 'Dashboard Utama IMS',
            'subtitle' => 'Monitoring operasional murid, guru, hafalan, adab, dan kedisiplinan.',
            'stats' => $stats,
        ]);
    }

    public function teacher(Request $request): View
    {
        try {
            $stats = $this->dashboardService->teacherStats($request->user());
        } catch (\Throwable $e) {
            $stats = [];
        }

        return view('dashboards.teacher', [
            'stats' => $stats,
        ]);
    }

    public function parent(Request $request): View
    {
        return view('dashboards.parent', [
            'stats' => $this->dashboardService->parentStats($request->user()),
        ]);
    }

    public function student(Request $request): View
    {
        return view('dashboards.student', [
            'stats' => $this->dashboardService->studentStats($request->user()),
        ]);
    }

    public function supervisor(Request $request): View
    {
        $today = now()->toDateString();

        $students = Student::with(['classRoom', 'adabRecords' => function ($q) use ($today) {
            $q->where('assessment_date', $today);
        }])->orderBy('name')->get();

        $totalStudents = $students->count();
        $filledCount = $students->filter(fn ($s) => $s->adabRecords->isNotEmpty())->count();
        $notFilledCount = $totalStudents - $filledCount;

        return view('dashboards.supervisor', compact('students', 'totalStudents', 'filledCount', 'notFilledCount', 'today'));
    }

    public function coordinatorTahfizh(Request $request): View
    {
        try {
            $today = now()->toDateString();
            $startOfMonth = now()->startOfMonth();
            $endOfMonth = now()->endOfMonth();

            $stats = [
                'hafalan_this_month' => HafalanRecord::whereBetween('submitted_at', [$startOfMonth, $endOfMonth])->count(),
                'hafalan_today' => HafalanRecord::whereDate('submitted_at', $today)->count(),
                'murajaah_this_month' => MurajaahRecord::whereBetween('reviewed_at', [$startOfMonth, $endOfMonth])->count(),
                'murajaah_today' => MurajaahRecord::whereDate('reviewed_at', $today)->count(),
                'active_targets' => HafalanTarget::where('status', 'in_progress')->count(),
                'completed_targets' => HafalanTarget::where('status', 'completed')->count(),
                'exams_this_month' => TahfizhExam::whereBetween('exam_date', [$startOfMonth, $endOfMonth])->count(),
                'passed_exams' => TahfizhExam::whereBetween('exam_date', [$startOfMonth, $endOfMonth])->where('total_score', '>=', 70)->count(),
            ];

            $recentHafalan = HafalanRecord::with(['student', 'surah'])
                ->latest('submitted_at')
                ->take(5)
                ->get();
        } catch (\Throwable $e) {
            $stats = [
                'hafalan_this_month' => 0,
                'hafalan_today' => 0,
                'murajaah_this_month' => 0,
                'murajaah_today' => 0,
                'active_targets' => 0,
                'completed_targets' => 0,
                'exams_this_month' => 0,
                'passed_exams' => 0,
            ];
            $recentHafalan = collect();
        }

        return view('dashboards.coordinator-tahfizh', compact('stats', 'recentHafalan'));
    }

    public function pendampingAdab(Request $request): View
    {
        $user = Auth::user();
        $today = now()->toDateString();
        $year = (int) date('Y');
        $month = (int) date('n');

        $isPrivileged = $user && $user->hasAnyRole(['super_admin', 'admin', 'supervisor']);

        $assignedClassIds = ($user && ! $isPrivileged)
            ? ClassRoom::where(function ($q) use ($user) {
                $q->where('pendamping_adab_id', $user->id)
                    ->orWhereHas('pendampingAdabList', fn ($sub) => $sub->where('users.id', $user->id));
            })->pluck('id')
            : null;

        $studentQuery = Student::where('status', 'active');
        if ($assignedClassIds !== null) {
            $studentQuery->whereIn('class_room_id', $assignedClassIds);
        }

        $totalStudents = (clone $studentQuery)->count();
        $assignedStudentIds = (clone $studentQuery)->pluck('id');

        $filledToday = AdabRecord::where('assessment_date', $today)
            ->whereIn('student_id', $assignedStudentIds)
            ->count();
        $fillPercentage = $totalStudents > 0 ? round(($filledToday / $totalStudents) * 100, 1) : 0;

        $students = $studentQuery->get();
        $monthlyScores = $students->map(fn ($s) => Setting::calculateAdabScore($s->id, $year, $month)['final_score']);
        $avgScoreMonth = $monthlyScores->isNotEmpty() ? round($monthlyScores->avg(), 1) : 0;
        $adabGradeMonth = Setting::getAdabGrade($avgScoreMonth);

        $rankCacheKey = 'pendamping_adab_rankings_' . ($user->id) . "_{$year}_{$month}";
        $classRankings = \Illuminate\Support\Facades\Cache::remember($rankCacheKey, 120, function () use ($assignedClassIds, $year, $month) {
            $classRoomQuery = ClassRoom::with(['students' => fn ($q) => $q->where('status', 'active')]);
            if ($assignedClassIds !== null) {
                $classRoomQuery->whereIn('id', $assignedClassIds);
            }

            return $classRoomQuery
                ->get()
                ->map(function ($classRoom) use ($year, $month) {
                    $st = $classRoom->students;
                    if ($st->isEmpty()) {
                        return ['name' => $classRoom->name, 'avg_score' => 0];
                    }
                    $sc = $st->map(fn ($s) => Setting::calculateAdabScore($s->id, $year, $month)['final_score']);
                    return ['name' => $classRoom->name, 'avg_score' => round($sc->avg(), 1)];
                })
                ->sortByDesc('avg_score')
                ->take(5)
                ->values();
        });

        $stats = [
            'total_students' => $totalStudents,
            'adab_filled_today' => $filledToday,
            'fill_percentage_today' => $fillPercentage,
            'avg_adab_score_month' => $avgScoreMonth,
            'adab_grade_month' => $adabGradeMonth,
            'total_materials' => AdabMaterial::count(),
            'effective_days' => Setting::getEffectiveDaysCount($year, $month),
        ];

        return view('dashboards.pendamping-adab', compact('stats', 'classRankings'));
    }

    public function tanse(Request $request): View
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $violationsMonth = StudentPoint::where('type', '!=', 'reward')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();

        $rewardsMonth = StudentPoint::where('type', 'reward')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->count();

        $stats = [
            'total_violations_month' => $violationsMonth->count(),
            'total_violation_points_month' => $violationsMonth->sum('points'),
            'lateness_count_month' => $violationsMonth->where('type', 'lateness')->count(),
            'attribute_count_month' => $violationsMonth->where('type', 'attribute')->count(),
            'rewards_count_month' => $rewardsMonth,
        ];

        $recentPoints = StudentPoint::with(['student'])
            ->latest('date')
            ->take(5)
            ->get();

        return view('dashboards.tanse', compact('stats', 'recentPoints'));
    }

    public function headmaster(Request $request): View
    {
        $year = (int) date('Y');
        $month = (int) date('n');
        $today = now()->toDateString();
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        // ─── Tahfizh Summary ───────────────────────────────────────────
        try {
            $hafalanThisMonth = HafalanRecord::whereBetween('submitted_at', [$startOfMonth, $endOfMonth])->count();
            $hafalanToday     = HafalanRecord::whereDate('submitted_at', $today)->count();
            $activeTargets    = HafalanTarget::where('status', 'in_progress')->count();
            $completedTargets = HafalanTarget::where('status', 'completed')->count();
            $totalTargets     = $activeTargets + $completedTargets;
            $targetRate       = $totalTargets > 0 ? round(($completedTargets / $totalTargets) * 100, 1) : 0;

            // Monthly hafalan per class-level (X, XI, XII)
            $tahfizhByLevel = [
                'X'   => HafalanRecord::whereBetween('submitted_at', [$startOfMonth, $endOfMonth])
                    ->whereHas('student.classRoom', fn ($q) => $q->where('name', 'like', 'X %')->where('name', 'not like', 'XI%'))->count(),
                'XI'  => HafalanRecord::whereBetween('submitted_at', [$startOfMonth, $endOfMonth])
                    ->whereHas('student.classRoom', fn ($q) => $q->where('name', 'like', 'XI %')->where('name', 'not like', 'XII%'))->count(),
                'XII' => HafalanRecord::whereBetween('submitted_at', [$startOfMonth, $endOfMonth])
                    ->whereHas('student.classRoom', fn ($q) => $q->where('name', 'like', 'XII %'))->count(),
            ];
        } catch (\Throwable) {
            $hafalanThisMonth = 0;
            $hafalanToday     = 0;
            $activeTargets    = 0;
            $completedTargets = 0;
            $targetRate       = 0;
            $tahfizhByLevel   = ['X' => 0, 'XI' => 0, 'XII' => 0];
        }

        // ─── Adab (Keagamaan) Summary ──────────────────────────────────
        try {
            $totalStudents    = Student::where('status', 'active')->count();
            $adabFilledToday  = AdabRecord::where('assessment_date', $today)->count();
            $fillPercentage   = $totalStudents > 0 ? round(($adabFilledToday / $totalStudents) * 100, 1) : 0;

            // Compute adab scores by level and overall in a single optimized pass
            $classRooms = ClassRoom::with(['students' => fn ($q) => $q->where('status', 'active')])->get();
            $adabByLevel = [
                'X'   => 0, 'XI'  => 0, 'XII' => 0,
                'X_total' => 0, 'XI_total' => 0, 'XII_total' => 0,
            ];
            $allScores = [];

            foreach ($classRooms as $cr) {
                $key = null;
                if (preg_match('/^XII\b/i', $cr->name)) {
                    $key = 'XII';
                } elseif (preg_match('/^XI\b/i', $cr->name)) {
                    $key = 'XI';
                } elseif (preg_match('/^X\b/i', $cr->name)) {
                    $key = 'X';
                }

                foreach ($cr->students as $st) {
                    $sc = Setting::calculateAdabScore($st->id, $year, $month)['final_score'] ?? 0;
                    $allScores[] = $sc;
                    if ($key) {
                        $adabByLevel[$key] += $sc;
                        $adabByLevel[$key . '_total']++;
                    }
                }
            }

            $avgAdabScore = count($allScores) > 0 ? round(array_sum($allScores) / count($allScores), 1) : 0;
            $adabGrade    = Setting::getAdabGrade($avgAdabScore);

            foreach (['X', 'XI', 'XII'] as $lv) {
                $cnt = $adabByLevel[$lv . '_total'];
                $adabByLevel[$lv] = $cnt > 0 ? round($adabByLevel[$lv] / $cnt, 1) : 0;
            }
        } catch (\Throwable) {
            $totalStudents   = 0;
            $adabFilledToday = 0;
            $fillPercentage  = 0;
            $avgAdabScore    = 0;
            $adabGrade       = '-';
            $adabByLevel     = ['X' => 0, 'XI' => 0, 'XII' => 0];
        }

        // ─── Tanse (Ketahanan Sekolah) Summary ─────────────────────────
        try {
            $violations = StudentPoint::where('type', '!=', 'reward')
                ->whereBetween('date', [$startOfMonth, $endOfMonth])->get();
            $rewards    = StudentPoint::where('type', 'reward')
                ->whereBetween('date', [$startOfMonth, $endOfMonth])->count();

            $tanseStats = [
                'violations'       => $violations->count(),
                'violation_points' => $violations->sum('points'),
                'lateness'         => $violations->where('type', 'lateness')->count(),
                'attribute'        => $violations->where('type', 'attribute')->count(),
                'rewards'          => $rewards,
            ];

            // Tanse 6-month trend
            $tanseTrend = [];
            for ($i = 5; $i >= 0; $i--) {
                $m = now()->subMonths($i);
                $tanseTrend[] = [
                    'label'  => $m->format('M'),
                    'points' => StudentPoint::where('type', '!=', 'reward')
                        ->whereYear('date', $m->year)->whereMonth('date', $m->month)->sum('points'),
                ];
            }
        } catch (\Throwable) {
            $tanseStats = ['violations' => 0, 'violation_points' => 0, 'lateness' => 0, 'attribute' => 0, 'rewards' => 0];
            $tanseTrend = [];
        }

        return view('dashboards.headmaster', compact(
            'hafalanThisMonth', 'hafalanToday', 'activeTargets', 'completedTargets', 'targetRate', 'tahfizhByLevel',
            'totalStudents', 'adabFilledToday', 'fillPercentage', 'avgAdabScore', 'adabGrade', 'adabByLevel',
            'tanseStats', 'tanseTrend', 'year', 'month'
        ));
    }
}
