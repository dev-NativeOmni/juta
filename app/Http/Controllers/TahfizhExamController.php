<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TahfizhExam;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TahfizhExamController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

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
            ->latest('exam_date')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('tahfizh-exams.index', array_merge(
            [
                'exams' => $exams,
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

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'teacher_id' => 'required|exists:teacher_profiles,id',
            'type' => 'required|in:juz,surah',
            'juz' => 'required_if:type,juz|nullable|integer|between:1,30',
            'surah_id' => 'required_if:type,surah|nullable|exists:surahs,id',
            'ayah_start' => 'required_if:type,surah|nullable|integer|min:1',
            'ayah_end' => 'required_if:type,surah|nullable|integer|gte:ayah_start',
            'q1' => 'required|integer|between:0,100',
            'q2' => 'required|integer|between:0,100',
            'q3' => 'required|integer|between:0,100',
            'q4' => 'required|integer|between:0,100',
            'q5' => 'required|integer|between:0,100',
            'notes' => 'nullable|string',
            'exam_date' => 'required|date',
        ]);

        // Calculate total_score as average
        $q1 = (int) $validated['q1'];
        $q2 = (int) $validated['q2'];
        $q3 = (int) $validated['q3'];
        $q4 = (int) $validated['q4'];
        $q5 = (int) $validated['q5'];
        $total = ($q1 + $q2 + $q3 + $q4 + $q5) / 5;

        $data = [
            'student_id' => $validated['student_id'],
            'teacher_id' => $validated['teacher_id'],
            'q1' => $q1,
            'q2' => $q2,
            'q3' => $q3,
            'q4' => $q4,
            'q5' => $q5,
            'total_score' => $total,
            'notes' => $validated['notes'],
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

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'teacher_id' => 'required|exists:teacher_profiles,id',
            'type' => 'required|in:juz,surah',
            'juz' => 'required_if:type,juz|nullable|integer|between:1,30',
            'surah_id' => 'required_if:type,surah|nullable|exists:surahs,id',
            'ayah_start' => 'required_if:type,surah|nullable|integer|min:1',
            'ayah_end' => 'required_if:type,surah|nullable|integer|gte:ayah_start',
            'q1' => 'required|integer|between:0,100',
            'q2' => 'required|integer|between:0,100',
            'q3' => 'required|integer|between:0,100',
            'q4' => 'required|integer|between:0,100',
            'q5' => 'required|integer|between:0,100',
            'notes' => 'nullable|string',
            'exam_date' => 'required|date',
        ]);

        $q1 = (int) $validated['q1'];
        $q2 = (int) $validated['q2'];
        $q3 = (int) $validated['q3'];
        $q4 = (int) $validated['q4'];
        $q5 = (int) $validated['q5'];
        $total = ($q1 + $q2 + $q3 + $q4 + $q5) / 5;

        $data = [
            'student_id' => $validated['student_id'],
            'teacher_id' => $validated['teacher_id'],
            'q1' => $q1,
            'q2' => $q2,
            'q3' => $q3,
            'q4' => $q4,
            'q5' => $q5,
            'total_score' => $total,
            'notes' => $validated['notes'],
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

        return [
            'students' => $students,
            'teachers' => $teachers,
            'surahs' => $surahs,
            'classRooms' => $classRooms,
        ];
    }
}
