<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\SystemNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentPointController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()->loadMissing('role');
        $query = StudentPoint::with(['student.classRoom', 'logger']);

        // Filter based on role
        $visibleStudentIds = collect();
        if ($user->hasRole('student')) {
            $student = Student::where('user_id', $user->id)->first();
            $query->where('student_id', $student?->id ?? 0);
            if ($student) {
                $visibleStudentIds->push($student->id);
            }
        } elseif ($user->hasRole('parent')) {
            $parent = ParentProfile::where('user_id', $user->id)->with('students')->first();
            $studentIds = $parent?->students->pluck('id')->toArray() ?? [];
            $query->whereIn('student_id', $studentIds);
            $visibleStudentIds = collect($studentIds);
        } else {
            $visibleStudentIds = Student::where('status', 'active')->pluck('id');
        }

        $classRooms = collect();
        if ($visibleStudentIds->isNotEmpty()) {
            $classRoomIds = Student::whereIn('id', $visibleStudentIds)->pluck('class_room_id')->filter()->unique()->values();
            $classRooms = ClassRoom::query()
                ->when($classRoomIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $classRoomIds))
                ->orderBy('name')
                ->get();
        }

        // Apply filters
        if ($request->filled('class_room_id')) {
            $query->whereHas('student', function ($q) use ($request) {
                $q->where('class_room_id', $request->integer('class_room_id'));
            });
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('student_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        // Sorting & pagination
        $points = $query->orderBy('date', 'desc')->paginate(15)->withQueryString();

        // Calculate statistics
        $statsQuery = StudentPoint::query();
        if ($user->hasRole('student')) {
            $student = Student::where('user_id', $user->id)->first();
            $statsQuery->where('student_id', $student?->id ?? 0);
        } elseif ($user->hasRole('parent')) {
            $parent = ParentProfile::where('user_id', $user->id)->with('students')->first();
            $studentIds = $parent?->students->pluck('id')->toArray() ?? [];
            $statsQuery->whereIn('student_id', $studentIds);
        }

        $totalViolations = (clone $statsQuery)->whereIn('type', ['violation', 'lateness', 'attribute'])->sum('points');
        $totalRewards = (clone $statsQuery)->where('type', 'reward')->sum('points');

        // Extended dashboard statistics
        $violationsByCategory = (clone $statsQuery)
            ->whereIn('type', ['violation', 'lateness', 'attribute'])
            ->selectRaw('category, count(*) as count, sum(points) as points')
            ->groupBy('category')
            ->get()
            ->keyBy('category');

        $violationsByLocation = (clone $statsQuery)
            ->whereIn('type', ['violation', 'lateness', 'attribute'])
            ->selectRaw('location, count(*) as count, sum(points) as points')
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->groupBy('location')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $rewardsByLevel = (clone $statsQuery)
            ->where('type', 'reward')
            ->selectRaw('achievement_level, count(*) as count, sum(points) as points')
            ->whereNotNull('achievement_level')
            ->groupBy('achievement_level')
            ->get()
            ->keyBy('achievement_level');

        // Top Students (Violations vs Rewards)
        $topAchievers = Student::query()
            ->select('students.id', 'students.name')
            ->join('student_points', 'students.id', '=', 'student_points.student_id')
            ->where('student_points.type', 'reward')
            ->selectRaw('sum(student_points.points) as total_points')
            ->groupBy('students.id', 'students.name')
            ->orderByDesc('total_points')
            ->limit(5)
            ->get();

        $topViolators = Student::query()
            ->select('students.id', 'students.name')
            ->join('student_points', 'students.id', '=', 'student_points.student_id')
            ->whereIn('student_points.type', ['violation', 'lateness', 'attribute'])
            ->selectRaw('sum(student_points.points) as total_points')
            ->groupBy('students.id', 'students.name')
            ->orderByDesc('total_points')
            ->limit(5)
            ->get();

        return view('student-points.index', [
            'points' => $points,
            'classRooms' => $classRooms,
            'totalViolations' => $totalViolations,
            'totalRewards' => $totalRewards,
            'violationsByCategory' => $violationsByCategory,
            'violationsByLocation' => $violationsByLocation,
            'rewardsByLevel' => $rewardsByLevel,
            'topAchievers' => $topAchievers,
            'topViolators' => $topViolators,
            'canManage' => $user->hasAnyRole(['super_admin', 'admin', 'tanse']),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorizeManagement();

        $students = Student::where('status', 'active')->with('classRoom')->orderBy('name')->get();
        $classRoomIds = $students->pluck('class_room_id')->filter()->unique()->values();
        $classRooms = ClassRoom::query()
            ->when($classRoomIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $classRoomIds))
            ->orderBy('name')
            ->get();
        $selectedStudentId = $request->input('student_id');

        return view('student-points.create', compact('students', 'classRooms', 'selectedStudentId'));
    }

    public function store(Request $request)
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'type' => 'required|in:violation,lateness,attribute,reward',
            'points' => 'required|integer|min:1|max:1000',
            'category' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sanction' => 'nullable|string',
            'achievement_type' => 'nullable|in:academic,non-academic',
            'achievement_level' => 'nullable|in:school,district,province,national',
            'location' => 'nullable|string|max:255',
            'date' => 'required|date',
        ]);

        $validated['logged_by'] = Auth::id();

        $point = StudentPoint::create($validated);

        if (StudentPoint::isViolationType($point->type)) {
            $this->checkThresholds($point->student_id);
        }

        return redirect()
            ->route('student-points.index')
            ->with('success', 'Catatan poin kedisiplinan berhasil ditambahkan.');
    }

    public function edit(StudentPoint $studentPoint)
    {
        $this->authorizeManagement();

        $students = Student::where('status', 'active')->with('classRoom')->orderBy('name')->get();
        $classRoomIds = $students->pluck('class_room_id')->filter()->unique()->values();
        $classRooms = ClassRoom::query()
            ->when($classRoomIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $classRoomIds))
            ->orderBy('name')
            ->get();

        return view('student-points.edit', compact('studentPoint', 'students', 'classRooms'));
    }

    public function update(Request $request, StudentPoint $studentPoint)
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'type' => 'required|in:violation,lateness,attribute,reward',
            'points' => 'required|integer|min:1|max:1000',
            'category' => 'nullable|string|max:50',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'sanction' => 'nullable|string',
            'achievement_type' => 'nullable|in:academic,non-academic',
            'achievement_level' => 'nullable|in:school,district,province,national',
            'location' => 'nullable|string|max:255',
            'date' => 'required|date',
        ]);

        $studentPoint->update($validated);

        if (StudentPoint::isViolationType($studentPoint->type)) {
            $this->checkThresholds($studentPoint->student_id);
        }

        return redirect()
            ->route('student-points.index')
            ->with('success', 'Catatan poin kedisiplinan berhasil diperbarui.');
    }

    public function chart(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();

        if ($user->hasRole('student') || $user->hasRole('parent')) {
            return redirect()->route('student-points.index');
        }

        $year = (int) $request->input('year', date('Y'));
        $month = (int) $request->input('month', date('n'));
        $classRoomId = $request->input('class_room_id');

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
        $studentIds = $allStudentsInScope->pluck('id')->toArray();

        // ─── Violations for selected month ───
        $monthViolations = StudentPoint::violations()
            ->whereIn('student_id', $studentIds)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->when($classRoomId, fn ($q) => $q->whereHas('student', fn ($sq) => $sq->where('class_room_id', $classRoomId)))
            ->get();

        $monthViolationsCount = $monthViolations->count();
        $monthViolationsPoints = $monthViolations->sum('points');

        $typeBreakdown = [
            'lateness' => $monthViolations->where('type', 'lateness')->count(),
            'attribute' => $monthViolations->where('type', 'attribute')->count(),
            'violation' => $monthViolations->where('type', 'violation')->count(),
        ];

        $monthsList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        // ─── Violations for entire year (1 Query) ───
        $allYearViolations = StudentPoint::violations()
            ->whereIn('student_id', $studentIds)
            ->whereYear('date', $year)
            ->when($classRoomId, fn ($q) => $q->whereHas('student', fn ($sq) => $sq->where('class_room_id', $classRoomId)))
            ->get();

        $violationsByMonth = $allYearViolations->groupBy(fn ($item) => (int) \Carbon\Carbon::parse($item->date)->format('n'));

        $monthlyTrends = [];
        for ($m = 1; $m <= 12; $m++) {
            $mViolations = $violationsByMonth->get($m, collect());
            $monthlyTrends[$m] = [
                'month_name' => substr($monthsList[$m], 0, 3),
                'full_month_name' => $monthsList[$m],
                'count' => $mViolations->count(),
                'points' => $mViolations->sum('points'),
            ];
        }

        // Group month violations by student_id for fast lookup
        $monthViolationsByStudent = $monthViolations->groupBy('student_id');

        $classReport = $classRooms->map(function ($classRoom) use ($monthViolationsByStudent) {
            $students = $classRoom->students;
            $totalStudents = $students->count();

            $studentsDetail = $students->map(function ($student) use ($monthViolationsByStudent) {
                $stViolations = $monthViolationsByStudent->get($student->id, collect());

                $vCount = $stViolations->count();
                $vPoints = $stViolations->sum('points');

                $latenessCount = $stViolations->where('type', 'lateness')->count();
                $attributeCount = $stViolations->where('type', 'attribute')->count();
                $tatibCount = $stViolations->where('type', 'violation')->count();

                return [
                    'student' => $student,
                    'violation_count' => $vCount,
                    'violation_points' => $vPoints,
                    'lateness_count' => $latenessCount,
                    'attribute_count' => $attributeCount,
                    'tatib_count' => $tatibCount,
                    'recent_sanctions' => $stViolations->pluck('sanction')->filter()->values()->all(),
                ];
            })->sortByDesc('violation_points')->values()->all();

            $totalViolationCount = collect($studentsDetail)->sum('violation_count');
            $totalViolationPoints = collect($studentsDetail)->sum('violation_points');

            return [
                'class_room' => $classRoom,
                'total_students' => $totalStudents,
                'violation_count' => $totalViolationCount,
                'violation_points' => $totalViolationPoints,
                'students_detail' => $studentsDetail,
            ];
        })->sortByDesc('violation_count')->values();

        // ─── Student Leaderboard (Rekap Murid Terbanyak Pelanggaran) ───
        $timeFrame = $request->input('time_frame', 'month'); // 'month' or 'all'
        $violationType = $request->input('violation_type', 'all'); // 'all', 'lateness', 'attribute', 'violation'
        $sortBy = $request->input('sort_by', 'count'); // 'count' or 'points'

        $leaderboardViolationsQuery = StudentPoint::violations()
            ->whereIn('student_id', $studentIds)
            ->when($timeFrame === 'month', function ($q) use ($year, $month) {
                $q->whereYear('date', $year)->whereMonth('date', $month);
            })
            ->when($violationType !== 'all', function ($q) use ($violationType) {
                $q->where('type', $violationType);
            })
            ->when($classRoomId, fn ($q) => $q->whereHas('student', fn ($sq) => $sq->where('class_room_id', $classRoomId)));

        $leaderboardViolations = $leaderboardViolationsQuery->get()->groupBy('student_id');

        $studentLeaderboard = $allStudentsInScope
            ->when($classRoomId, fn ($collection) => $collection->where('class_room_id', $classRoomId))
            ->map(function ($student) use ($leaderboardViolations) {
                $stViolations = $leaderboardViolations->get($student->id, collect());
                $vCount = $stViolations->count();
                $vPoints = $stViolations->sum('points');

                return [
                    'student' => $student,
                    'violation_count' => $vCount,
                    'violation_points' => $vPoints,
                    'lateness_count' => $stViolations->where('type', 'lateness')->count(),
                    'attribute_count' => $stViolations->where('type', 'attribute')->count(),
                    'tatib_count' => $stViolations->where('type', 'violation')->count(),
                    'latest_violation_date' => $stViolations->max('date'),
                    'recent_sanctions' => $stViolations->pluck('sanction')->filter()->unique()->values()->all(),
                    'recent_titles' => $stViolations->pluck('title')->take(3)->values()->all(),
                ];
            })
            ->filter(fn ($item) => $item['violation_count'] > 0);

        if ($sortBy === 'points') {
            $studentLeaderboard = $studentLeaderboard->sortByDesc('violation_points')->values();
        } else {
            $studentLeaderboard = $studentLeaderboard->sortByDesc('violation_count')->values();
        }

        return view('student-points.chart', compact(
            'classReport', 'year', 'month', 'classRoomId',
            'monthViolationsCount', 'monthViolationsPoints',
            'monthlyTrends', 'monthsList', 'classRooms', 'typeBreakdown',
            'studentLeaderboard', 'timeFrame', 'violationType', 'sortBy'
        ));
    }

    public function destroy(StudentPoint $studentPoint)
    {
        $this->authorizeManagement();

        $studentPoint->delete();

        return redirect()
            ->route('student-points.index')
            ->with('success', 'Catatan poin kedisiplinan berhasil dihapus.');
    }

    private function checkThresholds(int $studentId): void
    {
        $totalViolations = StudentPoint::where('student_id', $studentId)->where('type', 'violation')->sum('points');
        $thresholds = [50, 100, 150, 200];

        foreach ($thresholds as $threshold) {
            if ($totalViolations >= $threshold) {
                $uniqueHash = "student_points_warning_{$studentId}_{$threshold}";

                $student = Student::with(['parents.user', 'teacher.user', 'user'])->find($studentId);
                if (! $student) {
                    continue;
                }

                $usersToNotify = collect();
                if ($student->user) {
                    $usersToNotify->push($student->user);
                }
                foreach ($student->parents as $parent) {
                    if ($parent->user) {
                        $usersToNotify->push($parent->user);
                    }
                }
                if ($student->teacher && $student->teacher->user) {
                    $usersToNotify->push($student->teacher->user);
                }

                foreach ($usersToNotify as $user) {
                    $userSpecificHash = $uniqueHash.'_'.$user->id;
                    if (! SystemNotification::where('unique_hash', $userSpecificHash)->exists()) {
                        SystemNotification::create([
                            'user_id' => $user->id,
                            'created_by' => Auth::id(),
                            'unique_hash' => $userSpecificHash,
                            'title' => 'Ambang Batas Pelanggaran Terlampaui',
                            'message' => "Poin pelanggaran murid {$student->name} telah mencapai {$totalViolations} (Ambang batas: {$threshold} poin). Sanksi dan pembinaan diperlukan.",
                            'type' => 'warning',
                            'action_url' => '/student-points',
                            'published_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    private function authorizeManagement()
    {
        $user = Auth::user();
        if (! $user || ! $user->hasAnyRole(['super_admin', 'admin', 'tanse'])) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk mengelola poin kedisiplinan.');
        }
    }
}
