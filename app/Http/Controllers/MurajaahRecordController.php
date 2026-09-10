<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMurajaahRecordRequest;
use App\Http\Requests\UpdateMurajaahRecordRequest;
use App\Models\ClassRoom;
use App\Models\MurajaahRecord;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MurajaahRecordController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $murajaahRecords = MurajaahRecord::query()
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
            ->when($request->filled('surah_id'), function ($query) use ($request) {
                $query->where('surah_id', $request->integer('surah_id'));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status')->toString());
            })
            ->latest('reviewed_at')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('murajaah-records.index', array_merge(
            [
                'murajaahRecords' => $murajaahRecords,
            ],
            $this->formData($user)
        ));
    }

    public function fastInput(Request $request): View
    {
        $user = $request->user();
        $this->authorize('create', MurajaahRecord::class);

        $formData = $this->formData($user);
        $classRooms = $formData['classRooms'];

        $selectedClassId = $request->integer('class_room_id') ?: ($classRooms->first()?->id ?? 0);

        $students = Student::query()
            ->with(['classRoom.program'])
            ->where('status', 'active')
            ->when($selectedClassId > 0, function ($query) use ($selectedClassId) {
                $query->where('class_room_id', $selectedClassId);
            })
            ->when($user->hasRole('teacher'), function ($query) use ($user) {
                $query->where('teacher_id', $user->teacherProfile?->id);
            })
            ->orderBy('name')
            ->get();

        $studentIds = $students->pluck('id');

        $latestRecords = MurajaahRecord::query()
            ->with('surah')
            ->whereIn('student_id', $studentIds)
            ->latest('reviewed_at')
            ->latest('id')
            ->get()
            ->unique('student_id')
            ->keyBy('student_id');

        $surahs = Surah::query()->orderBy('number')->get();

        return view('murajaah-records.fast-input', [
            'classRooms' => $classRooms,
            'selectedClassId' => $selectedClassId,
            'students' => $students,
            'latestRecords' => $latestRecords,
            'surahs' => $surahs,
        ]);
    }

    public function fastStore(Request $request)
    {
        $this->authorize('create', MurajaahRecord::class);

        $request->validate([
            'entries' => 'required|array',
            'entries.*.student_id' => 'required|exists:students,id',
            'entries.*.surah_id' => 'required|exists:surahs,id',
            'entries.*.surah_end_id' => 'nullable|exists:surahs,id',
            'entries.*.ayah_start' => 'required|integer|min:1',
            'entries.*.ayah_end' => 'required|integer|min:1',
            'entries.*.score' => 'required|numeric|min:0|max:100',
        ]);

        $user = $request->user();
        $userTeacherId = $user->teacherProfile?->id;
        $fallbackTeacherId = $userTeacherId ?? TeacherProfile::query()->first()?->id;
        $reviewedAt = $request->input('reviewed_at') ?: now()->toDateString();
        $savedCount = 0;

        DB::transaction(function () use ($request, $userTeacherId, $fallbackTeacherId, $reviewedAt, &$savedCount) {
            foreach ($request->input('entries', []) as $entry) {
                if (empty($entry['student_id']) || empty($entry['surah_id']) || ! isset($entry['score']) || $entry['score'] === '') {
                    continue;
                }

                $student = Student::find($entry['student_id']);
                $teacherId = $userTeacherId ?? ($student?->teacher_id ?? $fallbackTeacherId);

                if (! $teacherId) {
                    continue;
                }

                $score = (float) $entry['score'];
                $status = $score >= 80 ? 'passed' : ($score >= 70 ? 'needs_improvement' : 'repeat');

                $surahStartId = (int) $entry['surah_id'];
                $surahEndId = (int) ($entry['surah_end_id'] ?? $surahStartId);

                if ($surahStartId === $surahEndId) {
                    MurajaahRecord::create([
                        'student_id' => $entry['student_id'],
                        'teacher_id' => $teacherId,
                        'surah_id' => $surahStartId,
                        'ayah_start' => (int) $entry['ayah_start'],
                        'ayah_end' => (int) $entry['ayah_end'],
                        'overall_score' => $score,
                        'fluency_score' => $score,
                        'status' => $status,
                        'reviewed_at' => $reviewedAt,
                        'notes' => $entry['notes'] ?? null,
                    ]);
                    $savedCount++;
                } else {
                    $surahStart = Surah::find($surahStartId);
                    $surahEnd = Surah::find($surahEndId);

                    if (! $surahStart || ! $surahEnd) {
                        continue;
                    }

                    $isAscending = $surahStart->number <= $surahEnd->number;
                    $minNumber = min($surahStart->number, $surahEnd->number);
                    $maxNumber = max($surahStart->number, $surahEnd->number);

                    $surahs = Surah::whereBetween('number', [$minNumber, $maxNumber])
                        ->orderBy('number', $isAscending ? 'asc' : 'desc')
                        ->get();

                    foreach ($surahs as $surah) {
                        if ($surah->id === $surahStart->id) {
                            $ayahStart = (int) $entry['ayah_start'];
                            $ayahEnd = $surah->total_ayah;
                        } elseif ($surah->id === $surahEnd->id) {
                            $ayahStart = 1;
                            $ayahEnd = (int) $entry['ayah_end'];
                        } else {
                            $ayahStart = 1;
                            $ayahEnd = $surah->total_ayah;
                        }

                        MurajaahRecord::create([
                            'student_id' => $entry['student_id'],
                            'teacher_id' => $teacherId,
                            'surah_id' => $surah->id,
                            'ayah_start' => $ayahStart,
                            'ayah_end' => min($ayahEnd, $surah->total_ayah),
                            'overall_score' => $score,
                            'fluency_score' => $score,
                            'status' => $status,
                            'reviewed_at' => $reviewedAt,
                            'notes' => $entry['notes'] ?? null,
                        ]);
                        $savedCount++;
                    }
                }
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Berhasil menyimpan {$savedCount} catatan murajaah.",
            ]);
        }

        return redirect()
            ->route('murajaah-records.index')
            ->with('success', "Berhasil menyimpan {$savedCount} catatan murajaah.");
    }

    public function create(Request $request): View
    {
        $this->authorize('create', MurajaahRecord::class);

        return view('murajaah-records.create', $this->formData($request->user()));
    }

    public function store(StoreMurajaahRecordRequest $request): RedirectResponse
    {
        $this->authorize('create', MurajaahRecord::class);

        $validated = $request->validated();
        $surahStartId = (int) $validated['surah_id'];
        $surahEndId = (int) ($validated['surah_end_id'] ?? $surahStartId);

        if ($surahStartId === $surahEndId) {
            unset($validated['surah_end_id']);
            MurajaahRecord::query()->create($validated);
        } else {
            $surahStart = Surah::findOrFail($surahStartId);
            $surahEnd = Surah::findOrFail($surahEndId);

            $isAscending = $surahStart->number <= $surahEnd->number;
            $minNumber = min($surahStart->number, $surahEnd->number);
            $maxNumber = max($surahStart->number, $surahEnd->number);

            $surahs = Surah::whereBetween('number', [$minNumber, $maxNumber])
                ->orderBy('number', $isAscending ? 'asc' : 'desc')
                ->get();

            DB::transaction(function () use ($surahs, $surahStart, $surahEnd, $validated) {
                foreach ($surahs as $surah) {
                    $recordData = $validated;
                    unset($recordData['surah_end_id']);
                    $recordData['surah_id'] = $surah->id;

                    if ($surah->id === $surahStart->id) {
                        $recordData['ayah_start'] = $validated['ayah_start'];
                        $recordData['ayah_end'] = $surah->total_ayah;
                    } elseif ($surah->id === $surahEnd->id) {
                        $recordData['ayah_start'] = 1;
                        $recordData['ayah_end'] = $validated['ayah_end'];
                    } else {
                        $recordData['ayah_start'] = 1;
                        $recordData['ayah_end'] = $surah->total_ayah;
                    }

                    MurajaahRecord::query()->create($recordData);
                }
            });
        }

        return redirect()
            ->route('murajaah-records.index')
            ->with('success', 'Data murajaah berhasil ditambahkan.');
    }

    public function show(MurajaahRecord $murajaahRecord): View
    {
        $this->authorize('view', $murajaahRecord);

        $murajaahRecord->load([
            'student.classRoom.program',
            'teacher.user',
            'surah',
        ]);

        return view('murajaah-records.show', [
            'murajaahRecord' => $murajaahRecord,
        ]);
    }

    public function edit(Request $request, MurajaahRecord $murajaahRecord): View
    {
        $this->authorize('update', $murajaahRecord);

        return view('murajaah-records.edit', array_merge(
            [
                'murajaahRecord' => $murajaahRecord,
            ],
            $this->formData($request->user())
        ));
    }

    public function update(UpdateMurajaahRecordRequest $request, MurajaahRecord $murajaahRecord): RedirectResponse
    {
        $this->authorize('update', $murajaahRecord);

        $murajaahRecord->update($request->validated());

        return redirect()
            ->route('murajaah-records.index')
            ->with('success', 'Data murajaah berhasil diperbarui.');
    }

    public function destroy(MurajaahRecord $murajaahRecord): RedirectResponse
    {
        $this->authorize('delete', $murajaahRecord);

        $murajaahRecord->delete();

        return redirect()
            ->route('murajaah-records.index')
            ->with('success', 'Data murajaah berhasil dihapus.');
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
