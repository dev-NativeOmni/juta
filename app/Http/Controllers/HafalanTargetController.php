<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\HafalanTarget;
use App\Models\Student;
use App\Models\Surah;
use App\Models\User;
use App\Services\AcademicCalendarService;
use App\Services\AutoHafalanTargetService;
use App\Services\HafalanProgressService;
use App\Services\StudentProgressService;
use App\Support\AyahCoverage;
use App\Support\HafalanOrder;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class HafalanTargetController extends Controller
{
    public function index(Request $request): View
    {
        $visibleStudentIds = $this->visibleStudentIds($request->user());
        $activeProgram = $request->input('program', 'reguler');

        $query = HafalanTarget::query()
            ->with([
                'student.classRoom.program',
                'surah',
                'teacher.user',
            ])
            ->whereIn('student_id', $visibleStudentIds)
            ->when($activeProgram === 'ummi', function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNotNull('ummi_jilid')
                        ->orWhereHas('student.classRoom', function ($c) {
                            $c->where('name', 'like', 'X %')
                                ->orWhere('name', 'like', 'X-%')
                                ->orWhere('name', 'X');
                        });
                });
            })
            ->when($activeProgram === 'reguler', function ($q) {
                $q->whereNull('ummi_jilid');
            })
            ->when($request->filled('class_room_id'), function ($query) use ($request) {
                $query->whereHas('student', function ($q) use ($request) {
                    $q->where('class_room_id', $request->integer('class_room_id'));
                });
            })
            ->when($request->filled('teacher_id'), function ($query) use ($request) {
                $query->where('teacher_id', $request->integer('teacher_id'));
            })
            ->when($request->filled('student_id'), function ($query) use ($request, $visibleStudentIds) {
                $studentId = (int) $request->input('student_id');

                if ($visibleStudentIds->contains($studentId)) {
                    $query->where('student_id', $studentId);
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->when($request->filled('surah_id'), function ($query) use ($request) {
                $query->where('surah_id', $request->input('surah_id'));
            })
            ->when($request->filled('date_from'), function ($query) use ($request) {
                $query->whereDate('target_date', '>=', $request->input('date_from'));
            })
            ->when($request->filled('date_to'), function ($query) use ($request) {
                $query->whereDate('target_date', '<=', $request->input('date_to'));
            });

        $targets = (clone $query)
            ->orderBy('target_date')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $activeStatuses = $this->activeTargetStatuses();

        $summary = [
            'total' => (clone $query)->count(),

            'active' => (clone $query)
                ->whereIn('status', $activeStatuses)
                ->count(),

            'planned' => (clone $query)
                ->where('status', 'planned')
                ->count(),

            'in_progress' => (clone $query)
                ->where('status', 'in_progress')
                ->count(),

            'completed' => (clone $query)
                ->where('status', 'completed')
                ->count(),

            'missed' => (clone $query)
                ->where('status', 'missed')
                ->count(),

            'cancelled' => (clone $query)
                ->where('status', 'cancelled')
                ->count(),

            'overdue' => (clone $query)
                ->whereIn('status', $activeStatuses)
                ->whereDate('target_date', '<', today())
                ->count(),
        ];

        $allVisibleStudents = Student::query()
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active')
            ->get();

        $classRoomIds = $allVisibleStudents->pluck('class_room_id')->filter()->unique()->values();
        $classRooms = ClassRoom::query()
            ->when($classRoomIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $classRoomIds))
            ->orderBy('name')
            ->get();

        $grade10ClassRooms = $classRooms->filter(function ($c) {
            $name = $c->name;
            $level = $c->level ?? '';

            return ((preg_match('/\bX\b/i', $name) && ! preg_match('/\b(XI|XII)\b/i', $name))
                || preg_match('/\b10\b/i', $name)
                || preg_match('/^X[-_\s]?E/i', $name)
                || preg_match('/kelas\s*(X|10)/i', $name)
                || (preg_match('/\bX\b/i', $level) && ! preg_match('/\b(XI|XII)\b/i', $level))
                || preg_match('/\b10\b/i', $level))
                && ! preg_match('/\b(XI|XII|11|12)\b/i', $name);
        })->values();

        $regulerClassRooms = $classRooms->reject(function ($c) use ($grade10ClassRooms) {
            return $grade10ClassRooms->contains('id', $c->id);
        })->values();

        $user = $request->user();
        $isTeacherOnly = $user?->hasRole('teacher') && ! $user?->hasAnyRole(['super_admin', 'admin']);

        if ($isTeacherOnly && $user->teacherProfile) {
            $teachers = \App\Models\TeacherProfile::query()
                ->with('user')
                ->where('id', $user->teacherProfile->id)
                ->get();
            $currentTeacherId = $user->teacherProfile->id;
        } else {
            $teachers = \App\Models\TeacherProfile::query()
                ->with('user')
                ->whereHas('user')
                ->orderBy('id')
                ->get();
            $currentTeacherId = (int) ($request->input('teacher_id') ?: ($user->teacherProfile?->id ?? $teachers->first()?->id));
        }

        $studentsQuery = Student::query()
            ->with(['classRoom.program', 'teacher.user'])
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active')
            ->when($request->filled('class_room_id'), function ($q) use ($request) {
                $q->where('class_room_id', $request->integer('class_room_id'));
            })
            ->when($request->filled('teacher_id') || $isTeacherOnly, function ($q) use ($currentTeacherId) {
                $q->where('teacher_id', $currentTeacherId);
            });

        if ($activeProgram === 'ummi' && ! $request->filled('class_room_id')) {
            $grade10Ids = $grade10ClassRooms->pluck('id')->all();
            if (! empty($grade10Ids)) {
                $studentsQuery->whereIn('class_room_id', $grade10Ids);
            }
        }

        $students = $studentsQuery->orderBy('name')->get();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        $statusOptions = $this->targetStatuses();

        return view('hafalan-targets.index', compact(
            'targets',
            'students',
            'classRooms',
            'grade10ClassRooms',
            'regulerClassRooms',
            'teachers',
            'surahs',
            'summary',
            'statusOptions',
            'activeProgram',
            'currentTeacherId',
            'isTeacherOnly'
        ));
    }

    public function storeBulkReguler(Request $request): RedirectResponse
    {
        $this->authorize('create', HafalanTarget::class);
        $visibleStudentIds = $this->visibleStudentIds($request->user());

        $validated = $request->validate([
            'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
            'targets' => ['required', 'array'],
            'targets.*.student_id' => ['required', 'integer', 'exists:students,id'],
            'targets.*.surah_id' => ['nullable', 'integer', 'exists:surahs,id'],
            'targets.*.ayah' => ['nullable', 'integer', 'min:1'],
            'targets.*.target_date' => ['nullable', 'date'],
            'targets.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $count = 0;
        foreach ($validated['targets'] as $row) {
            if (empty($row['surah_id']) || empty($row['ayah'])) {
                continue;
            }

            $studentId = (int) $row['student_id'];
            if (! $visibleStudentIds->contains($studentId)) {
                continue;
            }

            $student = Student::find($studentId);
            if (! $student) {
                continue;
            }

            $teacherId = $this->resolveTeacherId($request, $student);

            HafalanTarget::create([
                'student_id' => $student->id,
                'teacher_id' => $teacherId,
                'surah_id' => $row['surah_id'],
                'ayah' => $row['ayah'],
                'target_date' => $row['target_date'] ?: now()->addWeeks(2)->toDateString(),
                'notes' => $row['notes'] ?? null,
                'status' => $this->defaultOpenTargetStatus(),
            ]);
            $count++;
        }

        return redirect()
            ->route('hafalan-targets.index', ['program' => 'reguler', 'class_room_id' => $validated['class_room_id']])
            ->with('success', "Berhasil menyimpan {$count} target hafalan reguler.");
    }

    public function storeBulkUmmi(Request $request): RedirectResponse
    {
        $this->authorize('create', HafalanTarget::class);

        $validated = $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:teacher_profiles,id'],
            'class_room_id' => ['nullable', 'integer', 'exists:class_rooms,id'],
            'ummi_jilid' => ['required', 'string', 'max:100'],
            'halaman_peraga' => ['nullable', 'string', 'max:100'],
            'halaman_buku' => ['nullable', 'string', 'max:100'],
            'surah_id' => ['nullable', 'integer', 'exists:surahs,id'],
            'target_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        if ($user?->hasRole('teacher') && ! $user?->hasAnyRole(['super_admin', 'admin']) && $user->teacherProfile) {
            $teacherProfile = $user->teacherProfile;
        } else {
            $teacherProfile = \App\Models\TeacherProfile::findOrFail($validated['teacher_id']);
        }

        $studentsQuery = Student::query()
            ->where('teacher_id', $teacherProfile->id)
            ->where('status', 'active');

        if (! empty($validated['class_room_id'])) {
            $studentsQuery->where('class_room_id', (int) $validated['class_room_id']);
        } else {
            // Apply only to Grade 10 students (Never apply to Grade 11 / Grade 12)
            $studentsQuery->whereHas('classRoom', function ($q) {
                $q->where(function ($sq) {
                    $sq->where('name', 'like', '%X%')
                        ->orWhere('name', 'like', '%10%')
                        ->orWhere('level', 'like', '%X%')
                        ->orWhere('level', 'like', '%10%');
                })->where('name', 'not like', '%XI%')
                    ->where('name', 'not like', '%XII%')
                    ->where('name', 'not like', '%11%')
                    ->where('name', 'not like', '%12%');
            });
        }

        $students = $studentsQuery->get();

        $count = 0;
        foreach ($students as $student) {
            HafalanTarget::create([
                'student_id' => $student->id,
                'teacher_id' => $teacherProfile->id,
                'ummi_jilid' => $validated['ummi_jilid'],
                'halaman_peraga' => $validated['halaman_peraga'] ?? null,
                'halaman_buku' => $validated['halaman_buku'] ?? null,
                'surah_id' => $validated['surah_id'] ?? null,
                'ayah' => null,
                'target_date' => $validated['target_date'],
                'notes' => $validated['notes'] ?? null,
                'status' => $this->defaultOpenTargetStatus(),
            ]);
            $count++;
        }

        $redirectParams = ['program' => 'ummi', 'teacher_id' => $validated['teacher_id']];
        if (! empty($validated['class_room_id'])) {
            $redirectParams['class_room_id'] = $validated['class_room_id'];
        }

        return redirect()
            ->route('hafalan-targets.index', $redirectParams)
            ->with('success', "Berhasil menyimpan target Ummi serentak untuk {$count} murid Kelas 10 di Halaqah Musyrif.");
    }

    public function bulkComplete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'target_ids' => ['required', 'array'],
            'target_ids.*' => ['required', 'integer', 'exists:hafalan_targets,id'],
        ]);

        $visibleStudentIds = $this->visibleStudentIds($request->user());

        $targets = HafalanTarget::query()
            ->whereIn('id', $validated['target_ids'])
            ->whereIn('student_id', $visibleStudentIds)
            ->get();

        $count = 0;
        foreach ($targets as $target) {
            $this->authorize('update', $target);
            $target->update(['status' => 'completed']);
            $count++;
        }

        return redirect()
            ->back()
            ->with('success', "Berhasil menandai {$count} target hafalan sebagai selesai.");
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'target_ids' => ['required', 'array'],
            'target_ids.*' => ['required', 'integer', 'exists:hafalan_targets,id'],
        ]);

        $visibleStudentIds = $this->visibleStudentIds($request->user());

        $targets = HafalanTarget::query()
            ->whereIn('id', $validated['target_ids'])
            ->whereIn('student_id', $visibleStudentIds)
            ->get();

        $count = 0;
        foreach ($targets as $target) {
            $this->authorize('delete', $target);
            $target->delete();
            $count++;
        }

        return redirect()
            ->back()
            ->with('success', "Berhasil menghapus {$count} target hafalan terpilih.");
    }

    public function create(Request $request): View
    {
        $this->authorize('create', HafalanTarget::class);
        $visibleStudentIds = $this->visibleStudentIds($request->user());

        $students = Student::query()
            ->with(['classRoom.program', 'teacher.user'])
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $classRoomIds = $students->pluck('class_room_id')->filter()->unique()->values();
        $classRooms = ClassRoom::query()
            ->when($classRoomIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $classRoomIds))
            ->orderBy('name')
            ->get();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        $statusOptions = $this->targetStatuses();

        return view('hafalan-targets.create', compact(
            'students',
            'classRooms',
            'surahs',
            'statusOptions'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', HafalanTarget::class);
        $visibleStudentIds = $this->visibleStudentIds($request->user());

        $validated = $this->validateTarget($request, $visibleStudentIds);

        $student = Student::query()->findOrFail($validated['student_id']);

        $data = $this->targetPayload($validated);
        $data['student_id'] = $student->id;

        $data['teacher_id'] = $this->resolveTeacherId($request, $student);

        if (empty($data['status'])) {
            $data['status'] = $this->defaultOpenTargetStatus();
        }

        HafalanTarget::query()->create($data);

        return redirect()
            ->route('hafalan-targets.index')
            ->with('success', 'Target hafalan berhasil ditambahkan.');
    }

    public function show(Request $request, HafalanTarget $hafalanTarget): View
    {
        $this->authorizeTargetAccess($request, $hafalanTarget);

        $hafalanTarget->load([
            'student.classRoom.program',
            'surah',
            'teacher.user',
        ]);

        $target = $hafalanTarget;

        return view('hafalan-targets.show', compact(
            'hafalanTarget',
            'target'
        ));
    }

    public function edit(Request $request, HafalanTarget $hafalanTarget): View
    {
        $this->authorizeTargetAccess($request, $hafalanTarget);
        $this->authorize('update', $hafalanTarget);

        $visibleStudentIds = $this->visibleStudentIds($request->user());

        $students = Student::query()
            ->with(['classRoom.program', 'teacher.user'])
            ->whereIn('id', $visibleStudentIds)
            ->orderBy('name')
            ->get();

        $classRoomIds = $students->pluck('class_room_id')->filter()->unique()->values();
        $classRooms = ClassRoom::query()
            ->when($classRoomIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $classRoomIds))
            ->orderBy('name')
            ->get();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        $statusOptions = $this->targetStatuses();

        $target = $hafalanTarget;

        return view('hafalan-targets.edit', compact(
            'hafalanTarget',
            'target',
            'students',
            'classRooms',
            'surahs',
            'statusOptions'
        ));
    }

    public function update(Request $request, HafalanTarget $hafalanTarget): RedirectResponse
    {
        $this->authorizeTargetAccess($request, $hafalanTarget);
        $this->authorize('update', $hafalanTarget);

        $visibleStudentIds = $this->visibleStudentIds($request->user());

        $validated = $this->validateTarget($request, $visibleStudentIds);

        $student = Student::query()->findOrFail($validated['student_id']);

        $data = $this->targetPayload($validated);
        $data['student_id'] = $student->id;

        $data['teacher_id'] = $this->resolveTeacherId($request, $student);

        // Target otomatis yang diedit guru menjadi target guru: tidak ditimpa lagi oleh perhitungan otomatis.
        $data['auto_month'] = null;

        $hafalanTarget->update($data);

        return redirect()
            ->route('hafalan-targets.index')
            ->with('success', 'Target hafalan berhasil diperbarui.');
    }

    public function destroy(Request $request, HafalanTarget $hafalanTarget): RedirectResponse
    {
        $this->authorizeTargetAccess($request, $hafalanTarget);
        $this->authorize('delete', $hafalanTarget);

        $hafalanTarget->delete();

        return redirect()
            ->route('hafalan-targets.index')
            ->with('success', 'Target hafalan berhasil dihapus.');
    }

    public function complete(Request $request, HafalanTarget $hafalanTarget): RedirectResponse
    {
        $this->authorizeTargetAccess($request, $hafalanTarget);
        $this->authorize('update', $hafalanTarget);

        $data = [
            'status' => 'completed',
        ];

        if (Schema::hasColumn('hafalan_targets', 'completed_at')) {
            $data['completed_at'] = now();
        }

        $hafalanTarget->update($data);

        return redirect()
            ->back()
            ->with('success', 'Target hafalan ditandai selesai.');
    }

    public function markMissed(Request $request, HafalanTarget $hafalanTarget): RedirectResponse
    {
        $this->authorizeTargetAccess($request, $hafalanTarget);
        $this->authorize('update', $hafalanTarget);

        $hafalanTarget->update([
            'status' => 'missed',
        ]);

        return redirect()
            ->back()
            ->with('success', 'Target hafalan ditandai terlewat.');
    }

    private function validateTarget(Request $request, Collection $visibleStudentIds): array
    {
        $statuses = $this->targetStatuses();

        $validator = Validator::make($request->all(), [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'surah_id' => ['required', 'integer', 'exists:surahs,id'],
            'ayah' => ['required', 'integer', 'min:1'],
            'target_date' => ['required', 'date'],
            'status' => ['nullable', Rule::in($statuses)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validator->after(function ($validator) use ($request, $visibleStudentIds) {
            $studentId = (int) $request->input('student_id');

            if (! $visibleStudentIds->contains($studentId)) {
                $validator->errors()->add(
                    'student_id',
                    'Murid tidak boleh diakses oleh akun ini.'
                );
            }

            $surah = Surah::query()->find($request->input('surah_id'));

            if ($surah && isset($surah->total_ayah)) {
                if ((int) $request->input('ayah') > (int) $surah->total_ayah) {
                    $validator->errors()->add(
                        'ayah',
                        'Ayat tidak boleh melebihi jumlah ayat surah.'
                    );
                }
            }
        });

        return $validator->validate();
    }

    private function targetPayload(array $validated): array
    {
        $allowedColumns = Schema::getColumnListing('hafalan_targets');

        $payload = [];

        foreach ($validated as $key => $value) {
            if (in_array($key, $allowedColumns, true)) {
                $payload[$key] = $value;
            }
        }

        return $payload;
    }

    private function authorizeTargetAccess(Request $request, HafalanTarget $target): void
    {
        $visibleStudentIds = $this->visibleStudentIds($request->user());

        abort_unless(
            $visibleStudentIds->contains((int) $target->student_id),
            403,
            'Target hafalan tidak boleh diakses oleh akun ini.'
        );
    }

    /**
     * Target Triwulan: per murid kelas 11/12, titik awal (setoran pertama triwulan), target
     * akhir triwulan & titik antara tiap bulan, capaian, dan persentase -- semuanya dihitung
     * otomatis dari pertemuan aktif (AutoHafalanTargetService::termPlan).
     */
    public function term(Request $request, AutoHafalanTargetService $targets, AcademicCalendarService $calendar): View
    {
        $visibleStudentIds = $this->visibleStudentIds($request->user());

        // Pilihan triwulan: 6 triwulan terakhir (termasuk yang berjalan).
        $currentStart = $calendar->termStartDate(today());
        $periods = collect(range(0, 5))->mapWithKeys(function ($i) use ($currentStart) {
            $start = $currentStart->copy()->subMonthsNoOverflow($i * 3);
            $termNumber = [7 => 1, 10 => 2, 1 => 3, 4 => 4][$start->month];
            $academicYear = $start->month >= 7 ? $start->year.'/'.($start->year + 1) : ($start->year - 1).'/'.$start->year;

            return [$start->toDateString() => "Triwulan {$termNumber} · {$academicYear} ({$start->locale('id')->translatedFormat('M')} – {$start->copy()->addMonths(2)->locale('id')->translatedFormat('M Y')})"];
        });
        $period = $periods->has($request->input('period')) ? $request->input('period') : $currentStart->toDateString();

        $classRooms = ClassRoom::query()
            ->with('program')
            ->whereIn('id', Student::query()->whereIn('id', $visibleStudentIds)->where('status', 'active')->select('class_room_id'))
            ->orderBy('name')
            ->get()
            ->reject(fn (ClassRoom $class) => $class->isGradeTen())
            ->values();
        $selectedClass = $classRooms->firstWhere('id', (int) $request->input('class_room_id')) ?? $classRooms->first();

        $rows = collect();
        if ($selectedClass) {
            $rows = Student::query()
                ->whereIn('id', $visibleStudentIds)
                ->where('class_room_id', $selectedClass->id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get()
                ->map(function (Student $student) use ($targets, $selectedClass, $period) {
                    $student->setRelation('classRoom', $selectedClass);

                    return ['student' => $student, 'plan' => $targets->termPlan($student, Carbon::parse($period), $selectedClass)];
                });
        }

        $withStart = $rows->filter(fn ($row) => $row['plan']['start'] !== null);
        $summary = [
            'students' => $rows->count(),
            'reached' => $rows->where('plan.reached', true)->count(),
            'no_start' => $rows->count() - $withStart->count(),
            'avg_progress' => $withStart->isEmpty() ? 0 : (int) round($withStart->avg('plan.progress')),
            'meetings' => $rows->first()['plan']['term_meetings'] ?? 0,
        ];

        return view('hafalan-targets.term', compact('periods', 'period', 'classRooms', 'selectedClass', 'rows', 'summary'));
    }

    /**
     * Ubah arah hafalan murid (lanjut ke belakang / pindah ke depan setelah Juz 29, 28, atau 27),
     * lalu hitung ulang target otomatis triwulan yang sedang dilihat.
     */
    public function updateDirection(Request $request, Student $student, AutoHafalanTargetService $targets): RedirectResponse
    {
        abort_unless($this->visibleStudentIds($request->user())->contains($student->id), 403);

        $validated = $request->validate([
            'hafalan_direction' => ['required', Rule::in(array_keys(HafalanOrder::directionOptions()))],
            'period' => ['nullable', 'date'],
        ]);

        $student->update(['hafalan_direction' => $validated['hafalan_direction']]);
        $targets->syncStudent($student->fresh(), Carbon::parse($validated['period'] ?? today()));

        return back()->with('success', "Arah hafalan {$student->name} diperbarui dan target dihitung ulang.");
    }

    /**
     * Koreksi urutan di dalam satu juz (dari awal / dari akhir) untuk satu murid, atau
     * kembalikan ke deteksi otomatis; lalu hitung ulang target otomatis triwulan itu.
     */
    public function updateJuzOrder(Request $request, Student $student, AutoHafalanTargetService $targets): RedirectResponse
    {
        abort_unless($this->visibleStudentIds($request->user())->contains($student->id), 403);

        $validated = $request->validate([
            'juz' => ['required', 'integer', 'between:1,30'],
            'order' => ['required', Rule::in(['auto', HafalanOrder::ASC, HafalanOrder::DESC])],
            'period' => ['nullable', 'date'],
        ]);

        $orders = collect($student->juz_orders ?? [])->except((string) $validated['juz']);
        if ($validated['order'] !== 'auto') {
            $orders->put((string) $validated['juz'], $validated['order']);
        }
        $student->update(['juz_orders' => $orders->isEmpty() ? null : $orders->all()]);
        $targets->syncStudent($student->fresh(), Carbon::parse($validated['period'] ?? today()));

        return back()->with('success', "Urutan Juz {$validated['juz']} untuk {$student->name} diperbarui dan target dihitung ulang.");
    }

    /**
     * Urutan hafalan satu murid: 30 juz dalam urutan arah murid, cakupan ayat tiap juz,
     * urutan di dalam juz (terdeteksi / diatur guru) yang bisa dikoreksi.
     */
    public function juzOrders(Request $request, Student $student, HafalanProgressService $progress): View
    {
        abort_unless($this->visibleStudentIds($request->user())->contains($student->id), 403);

        $records = $progress->records($student);
        $coverage = $progress->coverage($records);
        $detected = $progress->detectedJuzOrders($records);
        $effective = $progress->juzOrders($student, $records);
        $manual = array_map('intval', array_keys($student->juz_orders ?? []));

        $juzRows = collect(HafalanOrder::juzSequence($student->hafalan_direction))->map(function (int $juz) use ($coverage, $detected, $effective, $manual, $records) {
            $totalAyat = 0;
            $coveredAyat = 0;
            foreach (HafalanOrder::JUZ_RANGES[$juz] as $range) {
                $totalAyat += $range['end'] - $range['start'] + 1;
                foreach (AyahCoverage::covered($coverage[$range['surah']] ?? [], $range['start'], $range['end']) as [$a, $b]) {
                    $coveredAyat += $b - $a + 1;
                }
            }
            $surahsInJuz = collect(HafalanOrder::JUZ_RANGES[$juz])->pluck('surah')->all();

            return [
                'juz' => $juz,
                'surah_range' => [reset($surahsInJuz), end($surahsInJuz)],
                'covered_percent' => $totalAyat > 0 ? (int) round($coveredAyat / $totalAyat * 100) : 0,
                'setoran_count' => $records->where('status', 'passed')->filter(fn ($r) => in_array((int) $r->surah_number, $surahsInJuz, true))->count(),
                'order' => $effective[$juz] ?? HafalanOrder::defaultJuzOrder($juz),
                'source' => in_array($juz, $manual, true) ? 'manual' : (isset($detected[$juz]) ? 'auto' : 'default'),
            ];
        });

        return view('hafalan-targets.juz-orders', [
            'student' => $student->load('classRoom'),
            'juzRows' => $juzRows,
            'surahNames' => $progress->surahs()->map->name_latin,
        ]);
    }

    private function visibleStudentIds(?User $user): Collection
    {
        if (! $user) {
            return collect();
        }

        return app(StudentProgressService::class)
            ->visibleStudentQuery($user)
            ->pluck('id')
            ->map(fn ($id) => (int) $id);
    }

    private function resolveTeacherId(Request $request, Student $student): ?int
    {
        $user = $request->user();
        $teacherId = null;

        if ($user?->teacherProfile?->id) {
            $teacherId = (int) $user->teacherProfile->id;
        } elseif ($user?->hasRole('teacher')) {
            $tId = TeacherProfile::query()->where('user_id', $user->id)->value('id');
            if ($tId) {
                $teacherId = (int) $tId;
            }
        }

        if (! $teacherId && $student->teacher_id) {
            $teacherId = (int) $student->teacher_id;
        }

        if (! $teacherId) {
            $teacherId = (int) TeacherProfile::query()->value('id');
        }

        if ($teacherId && ! $student->teacher_id) {
            $student->update(['teacher_id' => $teacherId]);
        }

        return $teacherId;
    }

    private function targetStatuses(): array
    {
        if (DB::getDriverName() === 'mysql') {
            try {
                $column = DB::selectOne("SHOW COLUMNS FROM hafalan_targets LIKE 'status'");

                if ($column && isset($column->Type)) {
                    preg_match_all("/'([^']+)'/", (string) $column->Type, $matches);

                    if (! empty($matches[1])) {
                        return $matches[1];
                    }
                }
            } catch (Throwable) {
                // Fallback di bawah sengaja dibiarkan.
            }
        }

        return [
            'active',
            'planned',
            'in_progress',
            'completed',
            'missed',
            'cancelled',
        ];
    }

    private function activeTargetStatuses(): array
    {
        $statuses = $this->targetStatuses();

        $activeStatuses = array_values(array_intersect($statuses, [
            'active',
            'planned',
            'in_progress',
        ]));

        return ! empty($activeStatuses)
            ? $activeStatuses
            : [$this->defaultOpenTargetStatus()];
    }

    private function defaultOpenTargetStatus(): string
    {
        $statuses = $this->targetStatuses();

        if (in_array('active', $statuses, true)) {
            return 'active';
        }

        if (in_array('planned', $statuses, true)) {
            return 'planned';
        }

        if (in_array('in_progress', $statuses, true)) {
            return 'in_progress';
        }

        return $statuses[0] ?? 'active';
    }
}
