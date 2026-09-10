<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Services\StudentMotivationService;
use App\Services\StudentProgressService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProgressController extends Controller
{
    public function __construct(
        private readonly StudentProgressService $studentProgressService,
        private readonly StudentMotivationService $studentMotivationService
    ) {}

    public function index(Request $request)
    {
        $selectedClass = null;
        $isGrade10 = false;

        try {
            $user = $request->user();

            if ($user->hasRole('student')) {
                $student = Student::query()->where('user_id', $user->id)->first();
                if ($student) {
                    return redirect()->route('progress.show', $student);
                }
                abort(403, 'Akun murid belum memiliki profil murid.');
            }

            if ($user->hasRole('parent')) {
                $visibleStudents = $this->studentProgressService->visibleStudentQuery($user)->get();
                if ($visibleStudents->count() === 1) {
                    return redirect()->route('progress.show', $visibleStudents->first());
                }
            }

            $visibleStudentQuery = $this->studentProgressService
                ->visibleStudentQuery($user);

            $filterStudents = (clone $visibleStudentQuery)
                ->with(['classRoom.program'])
                ->orderBy('name')
                ->get();

            $classRoomIds = $filterStudents
                ->pluck('class_room_id')
                ->filter()
                ->unique()
                ->values();

            $classRooms = ClassRoom::query()
                ->with('program')
                ->orderBy('name')
                ->get();

            $teachers = TeacherProfile::with('user')->get();

            $studentsQuery = $this->studentProgressService
                ->visibleStudentQuery($user)
                ->with(['classRoom.program']);

            if ($user->hasRole('teacher') && $request->filled('class_room_id')) {
                $studentsQuery = Student::query()
                    ->where('class_room_id', (int) $request->input('class_room_id'))
                    ->with(['classRoom.program']);
            }

            $studentsQuery
                ->when($request->filled('student_id'), function (Builder $query) use ($request) {
                    $query->where('id', (int) $request->input('student_id'));
                })
                ->when($request->filled('class_room_id') && ! $user->hasRole('teacher'), function (Builder $query) use ($request) {
                    $query->where('class_room_id', (int) $request->input('class_room_id'));
                })
                ->when($request->filled('teacher_id'), function (Builder $query) use ($request) {
                    $query->where('teacher_id', (int) $request->input('teacher_id'));
                })
                ->when($request->filled('q'), function (Builder $query) use ($request) {
                    $keyword = trim((string) $request->input('q'));

                    $query->where(function (Builder $searchQuery) use ($keyword) {
                        $searchQuery
                            ->where('name', 'like', '%'.$keyword.'%')
                            ->orWhere('student_number', 'like', '%'.$keyword.'%');
                    });
                })
                ->orderBy('name');

            $students = $studentsQuery->get();

            $progressRows = $this->studentProgressService
                ->buildRows($students)
                ->sortByDesc('progress_percent')
                ->values();

            if ($request->input('sort') === 'name') {
                $progressRows = $progressRows
                    ->sortBy('student_name')
                    ->values();
            }

            if ($request->input('sort') === 'overdue') {
                $progressRows = $progressRows
                    ->sortByDesc('overdue_targets')
                    ->values();
            }

            if ($request->input('sort') === 'low_progress') {
                $progressRows = $progressRows
                    ->sortBy('progress_percent')
                    ->values();
            }

            $selectedClass = $request->filled('class_room_id') ? $classRooms->firstWhere('id', (int) $request->input('class_room_id')) : null;
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

            $summary = $this->studentProgressService->summaryFromRows($progressRows);

            return view('progress.index', compact(
                'summary',
                'progressRows',
                'filterStudents',
                'classRooms',
                'teachers',
                'selectedClass',
                'isGrade10'
            ));
        } catch (\Throwable $e) {
            Log::error('ProgressController index error: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            $visibleStudentQuery = $this->studentProgressService->visibleStudentQuery($request->user());
            $students = (clone $visibleStudentQuery)->with(['classRoom.program'])->orderBy('name')->get();
            $filterStudents = $students;
            $classRoomIds = $students->pluck('class_room_id')->filter()->unique()->values();
            $classRooms = ClassRoom::query()->with('program')->whereIn('id', $classRoomIds)->orderBy('name')->get();
            $teachers = TeacherProfile::with('user')->get();

            $progressRows = $students->map(function ($s) {
                return [
                    'student' => $s,
                    'student_id' => $s->id,
                    'student_name' => $s->name,
                    'student_number' => $s->student_number ?? null,
                    'class_room_name' => $s->classRoom?->name,
                    'program_name' => $s->classRoom?->program?->name,
                    'progress_percent' => 0,
                    'memorized_ayahs' => 0,
                    'total_quran_ayahs' => 6236,
                    'completed_juz_count' => 0,
                    'completed_juz_list' => 'Belum ada Juz lengkap',
                    'total_hafalan_records' => 0,
                    'total_murajaah_records' => 0,
                    'average_hafalan_score' => 0,
                    'active_targets' => 0,
                    'completed_targets' => 0,
                    'overdue_targets' => 0,
                    'total_targets' => 0,
                ];
            });

            $summary = $this->studentProgressService->summaryFromRows($progressRows);

            return view('progress.index', compact(
                'summary',
                'progressRows',
                'filterStudents',
                'classRooms',
                'teachers',
                'selectedClass',
                'isGrade10'
            ));
        }
    }

    public function show(Request $request, Student $student): View
    {
        $user = $request->user();

        $canViewStudent = $this->studentProgressService
            ->visibleStudentQuery($user)
            ->where('id', $student->id)
            ->exists();

        abort_unless($canViewStudent, 403);

        $student->load([
            'user',
            'classRoom.program',
            'teacher.user',
            'parents.user',
        ]);

        $progress = $this->studentProgressService->calculate($student);

        $motivation = $this->studentMotivationService->build($student, $progress);

        $surahProgressRows = $this->buildSurahProgressRows($student);

        $timelineRows = $this->buildTimelineRows($student);

        $hafalanRecords = HafalanRecord::query()
            ->with(['surah', 'teacher.user'])
            ->where('student_id', $student->id)
            ->latest('submitted_at')
            ->latest()
            ->paginate(20, ['*'], 'hafalan_page')
            ->withQueryString();

        $murajaahRecords = MurajaahRecord::query()
            ->with(['surah', 'teacher.user'])
            ->where('student_id', $student->id)
            ->latest('reviewed_at')
            ->latest()
            ->paginate(20, ['*'], 'murajaah_page')
            ->withQueryString();

        $targets = HafalanTarget::query()
            ->with(['surah', 'teacher.user'])
            ->where('student_id', $student->id)
            ->orderByRaw("CASE WHEN status IN ('active', 'planned', 'in_progress') THEN 0 ELSE 1 END")
            ->orderBy('target_date')
            ->latest()
            ->paginate(20, ['*'], 'target_page')
            ->withQueryString();

        // Trend Hafalan 12 Bulan Terakhir
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $months[] = Carbon::now()->subMonths($i)->format('Y-m');
        }

        $allPassedRecords = HafalanRecord::query()
            ->with('surah')
            ->where('student_id', $student->id)
            ->where('status', 'passed')
            ->get();

        $monthlyData = [];
        foreach ($months as $m) {
            $monthlyData[$m] = 0.0;
        }

        foreach ($allPassedRecords as $rec) {
            $recMonth = $rec->submitted_at
                ? $rec->submitted_at->format('Y-m')
                : $rec->created_at->format('Y-m');

            $lines = (float) $rec->lines_count;

            if (isset($monthlyData[$recMonth])) {
                $monthlyData[$recMonth] += $lines;
            }
        }

        $tempCumulative = 0.0;
        $firstMonth = $months[0];
        foreach ($allPassedRecords as $rec) {
            $recMonth = $rec->submitted_at
                ? $rec->submitted_at->format('Y-m')
                : $rec->created_at->format('Y-m');

            if ($recMonth < $firstMonth) {
                $tempCumulative += (float) $rec->lines_count;
            }
        }

        $chartLabels = [];
        $monthlyValues = [];
        $cumulativeValues = [];

        $indonesianMonths = [
            '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
            '05' => 'Mei', '06' => 'Jun', '07' => 'Jul', '08' => 'Agt',
            '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des',
        ];

        $mostActiveMonth = '';
        $maxLines = 0.0;
        foreach ($monthlyData as $m => $lines) {
            if ($lines > $maxLines) {
                $maxLines = $lines;
                $mostActiveMonth = $m;
            }
        }
        $mostActiveMonthLabel = '';
        if ($maxLines > 0) {
            [$y, $mon] = explode('-', $mostActiveMonth);
            $mostActiveMonthLabel = $indonesianMonths[$mon].' '.$y;
        }

        foreach ($months as $m) {
            [$y, $mon] = explode('-', $m);
            $chartLabels[] = $indonesianMonths[$mon].' '.$y;
            $tempCumulative += $monthlyData[$m];
            $monthlyValues[] = round($monthlyData[$m], 1);
            $cumulativeValues[] = round($tempCumulative, 1);
        }

        return view('progress.show', compact(
            'student',
            'progress',
            'motivation',
            'surahProgressRows',
            'timelineRows',
            'hafalanRecords',
            'murajaahRecords',
            'targets',
            'chartLabels',
            'monthlyValues',
            'cumulativeValues',
            'mostActiveMonthLabel',
            'maxLines'
        ));
    }

    private function buildSurahProgressRows(Student $student): Collection
    {
        $records = HafalanRecord::query()
            ->with('surah')
            ->where('student_id', $student->id)
            ->where('status', 'passed')
            ->whereNotNull('surah_id')
            ->whereNotNull('ayah_start')
            ->whereNotNull('ayah_end')
            ->get();

        if ($records->isEmpty()) {
            return collect();
        }

        $surahIds = $records
            ->pluck('surah_id')
            ->filter()
            ->unique()
            ->values();

        $surahs = Surah::query()
            ->whereIn('id', $surahIds)
            ->orderBy('number')
            ->get()
            ->keyBy('id');

        return $records
            ->groupBy('surah_id')
            ->map(function (Collection $surahRecords, int|string $surahId) use ($surahs) {
                $surah = $surahs->get((int) $surahId);

                if (! $surah) {
                    return null;
                }

                $totalAyah = max(1, (int) $surah->total_ayah);

                $intervals = $surahRecords
                    ->map(function (HafalanRecord $record) use ($totalAyah) {
                        $start = max(1, (int) $record->ayah_start);
                        $end = min($totalAyah, (int) $record->ayah_end);

                        return $start <= $end ? [$start, $end] : null;
                    })
                    ->filter()
                    ->values()
                    ->all();

                $mergedIntervals = $this->mergeIntervals($intervals);

                $memorizedAyahs = collect($mergedIntervals)
                    ->sum(fn (array $range) => $range[1] - $range[0] + 1);

                return [
                    'surah' => $surah,
                    'memorized_ayahs' => $memorizedAyahs,
                    'total_ayahs' => $totalAyah,
                    'progress_percent' => round(($memorizedAyahs / $totalAyah) * 100, 2),
                    'ranges' => collect($mergedIntervals)
                        ->map(fn (array $range) => $range[0].'-'.$range[1])
                        ->implode(', '),
                ];
            })
            ->filter()
            ->sortBy(fn (array $row) => $row['surah']->number)
            ->values();
    }

    private function buildTimelineRows(Student $student): Collection
    {
        $hafalanRows = HafalanRecord::query()
            ->with(['surah', 'teacher.user'])
            ->where('student_id', $student->id)
            ->latest('submitted_at')
            ->latest()
            ->limit(20)
            ->get()
            ->map(function (HafalanRecord $record) {
                $date = $record->submitted_at
                    ? Carbon::parse($record->submitted_at)
                    : $record->created_at;

                return [
                    'type' => 'hafalan',
                    'label' => 'Hafalan',
                    'title' => $record->surah?->name_latin ?? '-',
                    'range' => $record->ayah_start.' - '.$record->ayah_end,
                    'status' => $record->status,
                    'score' => $record->score,
                    'teacher' => $record->teacher?->user?->name,
                    'notes' => $record->notes,
                    'date' => $date,
                    'date_sort' => $date?->timestamp ?? 0,
                ];
            });

        $murajaahRows = MurajaahRecord::query()
            ->with(['surah', 'teacher.user'])
            ->where('student_id', $student->id)
            ->latest('reviewed_at')
            ->latest()
            ->limit(20)
            ->get()
            ->map(function (MurajaahRecord $record) {
                $date = $record->reviewed_at
                    ? Carbon::parse($record->reviewed_at)
                    : $record->created_at;

                return [
                    'type' => 'murajaah',
                    'label' => 'Murajaah',
                    'title' => $record->surah?->name_latin ?? '-',
                    'range' => $record->ayah_start.' - '.$record->ayah_end,
                    'status' => $record->status,
                    'score' => $record->overall_score ?? $record->score ?? null,
                    'teacher' => $record->teacher?->user?->name,
                    'notes' => $record->notes,
                    'date' => $date,
                    'date_sort' => $date?->timestamp ?? 0,
                ];
            });

        $targetRows = HafalanTarget::query()
            ->with(['surah', 'teacher.user'])
            ->where('student_id', $student->id)
            ->latest()
            ->limit(20)
            ->get()
            ->map(function (HafalanTarget $target) {
                $dateValue = $target->completed_at
                    ?? $target->target_date
                    ?? $target->created_at;

                $date = $dateValue ? Carbon::parse($dateValue) : null;

                return [
                    'type' => 'target',
                    'label' => 'Target',
                    'title' => $target->surah?->name_latin ?? '-',
                    'range' => $target->ayah_start.' - '.$target->ayah_end,
                    'status' => $target->status,
                    'score' => null,
                    'teacher' => $target->teacher?->user?->name,
                    'notes' => $target->notes ?? null,
                    'date' => $date,
                    'date_sort' => $date?->timestamp ?? 0,
                ];
            });

        return $hafalanRows
            ->concat($murajaahRows)
            ->concat($targetRows)
            ->sortByDesc('date_sort')
            ->take(30)
            ->values();
    }

    private function mergeIntervals(array $intervals): array
    {
        if (empty($intervals)) {
            return [];
        }

        usort($intervals, fn (array $a, array $b) => $a[0] <=> $b[0]);

        $merged = [];

        foreach ($intervals as [$start, $end]) {
            if (empty($merged)) {
                $merged[] = [$start, $end];

                continue;
            }

            $lastIndex = count($merged) - 1;
            [$lastStart, $lastEnd] = $merged[$lastIndex];

            if ($start <= $lastEnd + 1) {
                $merged[$lastIndex] = [
                    $lastStart,
                    max($lastEnd, $end),
                ];

                continue;
            }

            $merged[] = [$start, $end];
        }

        return $merged;
    }
}
