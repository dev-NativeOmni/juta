<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TahfizhExam;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\AcademicCalendarService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TahfizhExamController extends Controller
{
    private const MONTH_NAMES = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function index(Request $request, AcademicCalendarService $calendar): View
    {
        $user = $request->user();

        $months = $calendar->termMonths(Carbon::today());
        $termStart = reset($months)['start'];
        $termEnd = end($months)['end'];
        $termLabel = self::MONTH_NAMES[$termStart->month].' - '.self::MONTH_NAMES[$termEnd->month].' '.$termEnd->year;

        $examStatus = $request->input('exam_status');
        $examStatus = in_array($examStatus, ['belum', 'sudah'], true) ? $examStatus : null;

        $maxScore = Setting::getTahfizhScoringConfig()['exam_weight'];
        $passThreshold = round($maxScore * 0.7, 1);
        $passStatus = $request->input('pass_status');
        $passStatus = in_array($passStatus, ['lulus', 'tidak_lulus'], true) ? $passStatus : null;

        if ($examStatus === 'belum') {
            $pendingStudents = Student::query()
                ->with(['classRoom.program', 'teacher.user'])
                ->where('status', 'active')
                ->when($user->hasRole('teacher'), fn ($q) => $q->where('teacher_id', $user->teacherProfile?->id))
                ->when($request->filled('class_room_id'), fn ($q) => $q->where('class_room_id', $request->integer('class_room_id')))
                ->when($request->filled('student_id'), fn ($q) => $q->where('id', $request->integer('student_id')))
                ->whereDoesntHave('tahfizhExams', function ($q) use ($termStart, $termEnd) {
                    $q->whereDate('exam_date', '>=', $termStart->toDateString())
                        ->whereDate('exam_date', '<=', $termEnd->toDateString());
                })
                ->orderBy('class_room_id')
                ->orderBy('name')
                ->paginate(50)
                ->withQueryString();

            return view('tahfizh-exams.index', array_merge(
                [
                    'exams' => null,
                    'pendingStudents' => $pendingStudents,
                    'examStatus' => $examStatus,
                    'passStatus' => null,
                    'termLabel' => $termLabel,
                ],
                $this->formData($user)
            ));
        }

        $exams = TahfizhExam::query()
            ->with([
                'student.classRoom.program',
                'teacher.user',
                'surah',
            ])
            ->when($user->hasRole('teacher'), function ($query) use ($user) {
                $query->where('teacher_id', $user->teacherProfile?->id);
            })
            ->when($request->filled('class_room_id'), function ($query) use ($request) {
                $query->whereHas('student', function ($q) use ($request) {
                    $q->where('class_room_id', $request->integer('class_room_id'));
                });
            })
            ->when($request->filled('student_id'), function ($query) use ($request) {
                $query->where('student_id', $request->integer('student_id'));
            })
            ->when($request->filled('juz'), function ($query) use ($request) {
                $query->where('juz', $request->integer('juz'));
            })
            ->when($request->filled('surah_id'), function ($query) use ($request) {
                $query->where('surah_id', $request->integer('surah_id'));
            })
            ->when($examStatus === 'sudah', function ($query) use ($termStart, $termEnd) {
                $query->whereDate('exam_date', '>=', $termStart->toDateString())
                    ->whereDate('exam_date', '<=', $termEnd->toDateString());
            })
            ->when($passStatus === 'lulus', fn ($query) => $query->where('total_score', '>=', $passThreshold))
            ->when($passStatus === 'tidak_lulus', fn ($query) => $query->where('total_score', '<', $passThreshold))
            ->latest('exam_date')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('tahfizh-exams.index', array_merge(
            [
                'exams' => $exams,
                'pendingStudents' => null,
                'examStatus' => $examStatus,
                'passStatus' => $passStatus,
                'termLabel' => $termLabel,
            ],
            $this->formData($user)
        ));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', TahfizhExam::class);

        return view('tahfizh-exams.create', $this->formData($request->user()));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', TahfizhExam::class);

        $maxScore = Setting::getTahfizhScoringConfig()['exam_weight'];

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'teacher_id' => 'required|exists:teacher_profiles,id',
            'type' => 'required|in:juz,surah',
            'juz' => 'required_if:type,juz|nullable|integer|between:1,30',
            'surah_id' => 'required_if:type,surah|nullable|exists:surahs,id',
            'ayah_start' => 'required_if:type,surah|nullable|integer|min:1',
            'ayah_end' => 'required_if:type,surah|nullable|integer|gte:ayah_start',
            'score' => "required|numeric|between:0,{$maxScore}",
            'notes' => 'nullable|string',
            'exam_date' => 'required|date',
        ]);

        $data = [
            'student_id' => $validated['student_id'],
            'teacher_id' => $validated['teacher_id'],
            'total_score' => $validated['score'],
            'notes' => $validated['notes'] ?? null,
            'exam_date' => $validated['exam_date'],
        ];

        if ($validated['type'] === 'juz') {
            $data['juz'] = $validated['juz'];
            $data['surah_id'] = null;
            $data['ayah_start'] = null;
            $data['ayah_end'] = null;
        } else {
            $data['juz'] = null;
            $data['surah_id'] = $validated['surah_id'];
            $data['ayah_start'] = $validated['ayah_start'];
            $data['ayah_end'] = $validated['ayah_end'];
        }

        TahfizhExam::query()->create($data);

        return redirect()
            ->route('tahfizh-exams.index')
            ->with('success', 'Data ujian tahfizh berhasil ditambahkan.');
    }

    public function edit(Request $request, TahfizhExam $tahfizhExam): View
    {
        $this->authorize('update', $tahfizhExam);

        return view('tahfizh-exams.edit', array_merge(
            [
                'exam' => $tahfizhExam,
            ],
            $this->formData($request->user())
        ));
    }

    public function update(Request $request, TahfizhExam $tahfizhExam): RedirectResponse
    {
        $this->authorize('update', $tahfizhExam);

        $maxScore = Setting::getTahfizhScoringConfig()['exam_weight'];

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'teacher_id' => 'required|exists:teacher_profiles,id',
            'type' => 'required|in:juz,surah',
            'juz' => 'required_if:type,juz|nullable|integer|between:1,30',
            'surah_id' => 'required_if:type,surah|nullable|exists:surahs,id',
            'ayah_start' => 'required_if:type,surah|nullable|integer|min:1',
            'ayah_end' => 'required_if:type,surah|nullable|integer|gte:ayah_start',
            'score' => "required|numeric|between:0,{$maxScore}",
            'notes' => 'nullable|string',
            'exam_date' => 'required|date',
        ]);

        $data = [
            'student_id' => $validated['student_id'],
            'teacher_id' => $validated['teacher_id'],
            'total_score' => $validated['score'],
            'notes' => $validated['notes'] ?? null,
            'exam_date' => $validated['exam_date'],
        ];

        if ($validated['type'] === 'juz') {
            $data['juz'] = $validated['juz'];
            $data['surah_id'] = null;
            $data['ayah_start'] = null;
            $data['ayah_end'] = null;
        } else {
            $data['juz'] = null;
            $data['surah_id'] = $validated['surah_id'];
            $data['ayah_start'] = $validated['ayah_start'];
            $data['ayah_end'] = $validated['ayah_end'];
        }

        $tahfizhExam->update($data);

        return redirect()
            ->route('tahfizh-exams.index')
            ->with('success', 'Data ujian tahfizh berhasil diperbarui.');
    }

    public function destroy(TahfizhExam $tahfizhExam): RedirectResponse
    {
        $this->authorize('delete', $tahfizhExam);

        $tahfizhExam->delete();

        return redirect()
            ->route('tahfizh-exams.index')
            ->with('success', 'Data ujian tahfizh berhasil dihapus.');
    }

    private function formData(User $user): array
    {
        $students = Student::query()
            ->with([
                'classRoom.program',
                'teacher.user',
            ])
            ->where('status', 'active')
            ->when($user->hasRole('teacher'), function ($query) use ($user) {
                $query->where('teacher_id', $user->teacherProfile?->id);
            })
            ->orderBy('name')
            ->get();

        $teachers = TeacherProfile::query()
            ->with('user')
            ->whereHas('user', function ($query) {
                $query->where('status', 'active');
            })
            ->get()
            ->sortBy(fn (TeacherProfile $teacher) => $teacher->user?->name)
            ->values();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        $classRoomIds = $students->pluck('class_room_id')->filter()->unique()->values();
        $classRooms = ClassRoom::query()
            ->when($classRoomIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $classRoomIds))
            ->orderBy('name')
            ->get();

        $maxScore = Setting::getTahfizhScoringConfig()['exam_weight'];

        return [
            'students' => $students,
            'teachers' => $teachers,
            'surahs' => $surahs,
            'classRooms' => $classRooms,
            'maxScore' => $maxScore,
            'passThreshold' => round($maxScore * 0.7, 1),
        ];
    }
}
