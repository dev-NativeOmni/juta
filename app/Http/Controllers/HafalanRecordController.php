<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreHafalanRecordRequest;
use App\Http\Requests\UpdateHafalanRecordRequest;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\UmmiRecord;
use App\Models\User;
use App\Services\AcademicCalendarService;
use App\Services\StudentProgressService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HafalanRecordController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $category = $request->query('category', 'reguler');

        if ($category === 'ummi') {
            $hafalanRecords = UmmiRecord::query()
                ->with([
                    'student.classRoom.program',
                    'teacher.user',
                    'surahs.surah',
                ])
                ->when($user->hasRole('teacher'), function ($query) use ($user) {
                    $query->whereHas('student', function ($q) use ($user) {
                        $q->where('teacher_id', $user->teacherProfile?->id);
                    });
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
                    $query->whereHas('surahs', function ($q) use ($request) {
                        $q->where('surah_id', $request->integer('surah_id'));
                    });
                })
                ->when($request->filled('date'), function ($query) use ($request) {
                    $query->whereDate('tanggal', $request->input('date'));
                })
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = $request->string('search')->trim()->toString();
                    $query->where(function ($q) use ($search) {
                        $q->whereHas('student', function ($sub) use ($search) {
                            $sub->where('name', 'like', "%{$search}%");
                        })
                            ->orWhereHas('surahs.surah', function ($sub) use ($search) {
                                $sub->where('name_latin', 'like', "%{$search}%");
                            })
                            ->orWhere('ummi_jilid', 'like', "%{$search}%")
                            ->orWhere('materi', 'like', "%{$search}%");
                    });
                })
                ->latest('tanggal')
                ->latest()
                ->paginate(20)
                ->withQueryString();
        } else {
            $hafalanRecords = HafalanRecord::query()
                ->with([
                    'student.classRoom.program',
                    'teacher.user',
                    'surahs.surah',
                ])
                ->when($user->hasRole('teacher'), function ($query) use ($user) {
                    $query->whereHas('student', function ($q) use ($user) {
                        $q->where('teacher_id', $user->teacherProfile?->id);
                    });
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
                    $query->whereHas('surahs', function ($q) use ($request) {
                        $q->where('surah_id', $request->integer('surah_id'));
                    });
                })
                ->when($request->filled('status'), function ($query) use ($request) {
                    $status = $request->string('status')->toString();
                    $query->whereHas('surahs', function ($q) use ($status) {
                        $q->where('status', $status);
                    });
                })
                ->when($request->filled('date'), function ($query) use ($request) {
                    $query->whereDate('submitted_at', $request->input('date'));
                })
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = $request->string('search')->trim()->toString();
                    $query->where(function ($q) use ($search) {
                        $q->whereHas('student', function ($sub) use ($search) {
                            $sub->where('name', 'like', "%{$search}%");
                        })
                            ->orWhereHas('surahs.surah', function ($sub) use ($search) {
                                $sub->where('name_latin', 'like', "%{$search}%");
                            });
                    });
                })
                ->latest('submitted_at')
                ->latest()
                ->paginate(20)
                ->withQueryString();
        }

        return view('hafalan-records.index', array_merge(
            [
                'hafalanRecords' => $hafalanRecords,
            ],
            $this->formData($user, $category)
        ));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', HafalanRecord::class);

        return view('hafalan-records.create', $this->formData($request->user()));
    }

    public function store(StoreHafalanRecordRequest $request): RedirectResponse
    {
        $this->authorize('create', HafalanRecord::class);

        $validated = $request->validated();
        $studentId = $validated['student_id'];
        $teacherId = $validated['teacher_id'] ?? null;
        $notes = $validated['notes'] ?? null;
        $submittedAt = $validated['submitted_at'];

        $surahIds = $validated['surah_ids'] ?? [];
        $ayahStarts = $validated['ayah_starts'] ?? [];
        $ayahEnds = $validated['ayah_ends'] ?? [];
        $submissionTypes = $validated['submission_types'] ?? [];
        $scores = $validated['scores'] ?? [];
        $statuses = $validated['statuses'] ?? [];
        $baris = $validated['baris'] ?? [];

        DB::transaction(function () use (
            $studentId,
            $teacherId,
            $notes,
            $submittedAt,
            $surahIds,
            $ayahStarts,
            $ayahEnds,
            $submissionTypes,
            $scores,
            $statuses,
            $baris
        ) {
            $hafalanRecord = HafalanRecord::query()->create([
                'student_id' => $studentId,
                'teacher_id' => $teacherId,
                'notes' => $notes,
                'submitted_at' => $submittedAt,
            ]);

            foreach ($surahIds as $idx => $surahId) {
                if (empty($surahId)) {
                    continue;
                }

                $hafalanRecord->surahs()->create([
                    'surah_id' => (int) $surahId,
                    'ayah_start' => (int) ($ayahStarts[$idx] ?? 1),
                    'ayah_end' => (int) ($ayahEnds[$idx] ?? 1),
                    'submission_type' => $submissionTypes[$idx] ?? 'new',
                    'score' => isset($scores[$idx]) && $scores[$idx] !== '' ? $scores[$idx] : null,
                    'status' => $statuses[$idx] ?? 'passed',
                    'baris' => isset($baris[$idx]) && $baris[$idx] !== '' ? (float) $baris[$idx] : null,
                    'sort_order' => $idx,
                ]);
            }
        });

        return redirect()
            ->route('hafalan-records.index')
            ->with('success', 'Data hafalan berhasil ditambahkan.');
    }

    public function show(HafalanRecord $hafalanRecord): View
    {
        $this->authorize('view', $hafalanRecord);

        $hafalanRecord->load([
            'student.classRoom.program',
            'teacher.user',
            'surahs.surah',
        ]);

        return view('hafalan-records.show', [
            'hafalanRecord' => $hafalanRecord,
        ]);
    }

    public function edit(Request $request, HafalanRecord $hafalanRecord): View
    {
        $this->authorize('update', $hafalanRecord);

        $hafalanRecord->load('surahs.surah');

        return view('hafalan-records.edit', array_merge(
            [
                'hafalanRecord' => $hafalanRecord,
            ],
            $this->formData($request->user())
        ));
    }

    public function update(UpdateHafalanRecordRequest $request, HafalanRecord $hafalanRecord): RedirectResponse
    {
        $this->authorize('update', $hafalanRecord);

        $validated = $request->validated();

        DB::transaction(function () use ($hafalanRecord, $validated) {
            $hafalanRecord->update([
                'student_id' => $validated['student_id'],
                'teacher_id' => $validated['teacher_id'] ?? $hafalanRecord->teacher_id,
                'notes' => $validated['notes'] ?? null,
                'submitted_at' => $validated['submitted_at'],
            ]);

            $hafalanRecord->surahs()->delete();

            $surahIds = $validated['surah_ids'] ?? [];
            $ayahStarts = $validated['ayah_starts'] ?? [];
            $ayahEnds = $validated['ayah_ends'] ?? [];
            $submissionTypes = $validated['submission_types'] ?? [];
            $scores = $validated['scores'] ?? [];
            $statuses = $validated['statuses'] ?? [];
            $baris = $validated['baris'] ?? [];

            foreach ($surahIds as $idx => $surahId) {
                if (empty($surahId)) {
                    continue;
                }

                $hafalanRecord->surahs()->create([
                    'surah_id' => (int) $surahId,
                    'ayah_start' => (int) ($ayahStarts[$idx] ?? 1),
                    'ayah_end' => (int) ($ayahEnds[$idx] ?? 1),
                    'submission_type' => $submissionTypes[$idx] ?? 'new',
                    'score' => isset($scores[$idx]) && $scores[$idx] !== '' ? $scores[$idx] : null,
                    'status' => $statuses[$idx] ?? 'passed',
                    'baris' => isset($baris[$idx]) && $baris[$idx] !== '' ? (float) $baris[$idx] : null,
                    'sort_order' => $idx,
                ]);
            }
        });

        return redirect()
            ->route('hafalan-records.index')
            ->with('success', 'Data hafalan berhasil diperbarui.');
    }

    public function destroy(HafalanRecord $hafalanRecord): RedirectResponse
    {
        $this->authorize('delete', $hafalanRecord);

        $hafalanRecord->delete();

        return redirect()
            ->route('hafalan-records.index')
            ->with('success', 'Data hafalan berhasil dihapus.');
    }

    public function updateUmmi(Request $request, UmmiRecord $ummiRecord): RedirectResponse
    {
        $this->authorize('create', HafalanRecord::class);

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'tanggal' => ['required', 'date'],
            'tatap_muka' => ['nullable', 'integer', 'min:1'],
            'ummi_jilid' => ['nullable', 'string', 'max:150'],
            'ummi_halaman' => ['nullable', 'string', 'max:100'],
            'materi' => ['nullable', 'string', 'max:255'],
            'nilai' => ['nullable', 'string', 'max:50'],
            'hafalan_surah_ids' => ['nullable', 'array'],
            'hafalan_surah_ids.*' => ['nullable', 'integer', 'exists:surahs,id'],
            'hafalan_ayahs' => ['nullable', 'array'],
            'hafalan_ayahs.*' => ['nullable', 'string', 'max:100'],
            'hafalan_baris' => ['nullable', 'array'],
            'hafalan_baris.*' => ['nullable', 'numeric', 'min:0'],
            'disimak_guru' => ['required', Rule::in(['Ya', 'Tidak'])],
            'disimak_ortu' => ['required', Rule::in(['Ya', 'Tidak'])],
            'catatan' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($ummiRecord, $validated) {
            $ummiRecord->update([
                'student_id' => $validated['student_id'],
                'tanggal' => $validated['tanggal'],
                'tatap_muka' => $validated['tatap_muka'] ?? $ummiRecord->tatap_muka,
                'ummi_jilid' => $validated['ummi_jilid'] ?? null,
                'ummi_halaman' => $validated['ummi_halaman'] ?? null,
                'materi' => $validated['materi'] ?? null,
                'nilai' => $validated['nilai'] ?? null,
                'disimak_guru' => $validated['disimak_guru'],
                'disimak_ortu' => $validated['disimak_ortu'],
                'keterangan' => $validated['catatan'] ?? null,
            ]);

            $ummiRecord->surahs()->delete();

            $surahIds = $validated['hafalan_surah_ids'] ?? [];
            $ayahs = $validated['hafalan_ayahs'] ?? [];
            $baris = $validated['hafalan_baris'] ?? [];

            foreach ($surahIds as $idx => $surahId) {
                if (empty($surahId)) {
                    continue;
                }

                $ummiRecord->surahs()->create([
                    'surah_id' => (int) $surahId,
                    'hafalan_ayah' => $ayahs[$idx] ?? null,
                    'baris' => isset($baris[$idx]) && $baris[$idx] !== '' ? (float) $baris[$idx] : null,
                    'sort_order' => $idx,
                ]);
            }
        });

        return back()
            ->with('success', 'Data progres UMMI berhasil diperbarui.');
    }

    public function destroyUmmi(Request $request, UmmiRecord $ummiRecord): RedirectResponse
    {
        $this->authorize('create', HafalanRecord::class);

        $ummiRecord->delete();

        return redirect()
            ->route('hafalan-records.index', ['category' => 'ummi'])
            ->with('success', 'Data progres UMMI berhasil dihapus.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:hafalan_records,id'],
        ]);

        $user = $request->user();
        $records = HafalanRecord::whereIn('id', $validated['ids'])->get();

        $count = 0;
        foreach ($records as $record) {
            if ($user->hasAnyRole(['super_admin', 'admin']) || ($user->hasRole('teacher') && (int) $record->teacher_id === (int) $user->teacherProfile?->id)) {
                $record->delete();
                $count++;
            }
        }

        return redirect()
            ->route('hafalan-records.index', ['category' => 'hafalan'])
            ->with('success', "Berhasil menghapus {$count} data hafalan.");
    }

    public function bulkDestroyUmmi(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:ummi_records,id'],
        ]);

        $user = $request->user();
        $records = UmmiRecord::whereIn('id', $validated['ids'])->get();

        $count = 0;
        foreach ($records as $record) {
            if ($user->hasAnyRole(['super_admin', 'admin']) || ($user->hasRole('teacher') && (int) $record->teacher_id === (int) $user->teacherProfile?->id)) {
                $record->delete();
                $count++;
            }
        }

        return redirect()
            ->route('hafalan-records.index', ['category' => 'ummi'])
            ->with('success', "Berhasil menghapus {$count} data UMMI.");
    }

    private function formData(User $user, string $category = 'reguler'): array
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

        if ($category === 'ummi') {
            $classRooms = $classRooms->filter(function ($class) {
                $name = $class->name ?? '';
                $level = (string) ($class->level ?? '');
                $isGrade10 = (
                    (preg_match('/\bX\b/i', $name) && ! preg_match('/\b(XI|XII)\b/i', $name))
                    || preg_match('/\b10\b/i', $name)
                    || preg_match('/^X[-_\s]?E/i', $name)
                    || preg_match('/kelas\s*(X|10)/i', $name)
                    || (preg_match('/\bX\b/i', $level) && ! preg_match('/\b(XI|XII)\b/i', $level))
                    || preg_match('/\b10\b/i', $level)
                ) && ! preg_match('/\b(XI|XII|11|12)\b/i', $name);

                return $isGrade10;
            })->values();
        }

        return [
            'students' => $students,
            'teachers' => $teachers,
            'surahs' => $surahs,
            'classRooms' => $classRooms,
        ];
    }

    /**
     * Saran nomor Tatap Muka (TM) untuk kelas & tanggal tertentu, dihitung
     * dari kalender hari efektif kelas (bukan riwayat TM murid) -- lihat
     * AcademicCalendarService::tatapMukaNumber().
     */
    public function suggestTatapMuka(Request $request, AcademicCalendarService $calendar): JsonResponse
    {
        $validated = $request->validate([
            'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
            'date' => ['required', 'date'],
        ]);

        $classRoom = ClassRoom::query()->with('program')->findOrFail($validated['class_room_id']);

        return response()->json([
            'tatap_muka' => $calendar->tatapMukaNumber($classRoom, Carbon::parse($validated['date']), forUmmi: true),
        ]);
    }

    public function ummiCard(Request $request, Student $student): View
    {
        $user = $request->user();

        abort_if($user->hasAnyRole(['student', 'parent']), 403, 'Opsi cetak kartu UMMI tidak diperbolehkan untuk akun murid dan orang tua.');

        // Check if student belongs to the visible students for this user
        $visibleStudentIds = app(StudentProgressService::class)
            ->visibleStudentQuery($user)
            ->pluck('id');

        abort_unless($visibleStudentIds->contains($student->id), 403, 'Akses tidak diperbolehkan.');

        $student->load([
            'classRoom.program',
            'teacher.user',
        ]);

        $records = UmmiRecord::with('surahs.surah')
            ->where('student_id', $student->id)
            ->orderBy('tanggal')
            ->orderBy('tatap_muka')
            ->orderBy('id')
            ->get();

        $latestUmmiRecord = $records->last();

        return view('hafalan-records.ummi-card', compact('student', 'records', 'latestUmmiRecord'));
    }
}
