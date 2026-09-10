<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\MurajaahRecord;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\UmmiRecord;
use App\Services\UserAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class QuickInputController extends Controller
{
    public function index(Request $request, UserAccessService $accessService): View
    {
        Gate::authorize('create', HafalanRecord::class);

        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        $students = Student::query()
            ->with([
                'user',
                'classRoom.program',
                'teacher.user',
            ])
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $surahs = Surah::query()
            ->orderBy('number')
            ->get();

        $latestHafalanRecords = HafalanRecord::query()
            ->with([
                'student.classRoom.program',
                'teacher.user',
                'surah',
            ])
            ->whereIn('student_id', $visibleStudentIds)
            ->latest('submitted_at')
            ->latest()
            ->limit(5)
            ->get();

        $latestMurajaahRecords = MurajaahRecord::query()
            ->with([
                'student.classRoom.program',
                'teacher.user',
                'surah',
            ])
            ->whereIn('student_id', $visibleStudentIds)
            ->latest('reviewed_at')
            ->latest()
            ->limit(5)
            ->get();

        $latestUmmiRecords = UmmiRecord::query()
            ->with([
                'student.classRoom.program',
                'teacher.user',
                'surah',
            ])
            ->whereIn('student_id', $visibleStudentIds)
            ->latest('tanggal')
            ->latest()
            ->limit(5)
            ->get();

        $classRoomIds = $students->pluck('class_room_id')->filter()->unique()->values();
        $classRooms = ClassRoom::query()
            ->when($classRoomIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $classRoomIds))
            ->orderBy('name')
            ->get();

        $latestTatapMukaPerClass = DB::table('ummi_records')
            ->join('students', 'ummi_records.student_id', '=', 'students.id')
            ->select('students.class_room_id', DB::raw('MAX(ummi_records.tatap_muka) as max_tatap_muka'))
            ->groupBy('students.class_room_id')
            ->pluck('max_tatap_muka', 'students.class_room_id');

        return view('quick-inputs.index', [
            'students' => $students,
            'classRooms' => $classRooms,
            'surahs' => $surahs,
            'latestTatapMukaPerClass' => $latestTatapMukaPerClass,

            // Nama variable utama yang dipakai view.
            'latestHafalanRecords' => $latestHafalanRecords,
            'latestMurajaahRecords' => $latestMurajaahRecords,
            'latestUmmiRecords' => $latestUmmiRecords,

            // Alias pengaman kalau ada bagian view lama yang masih pakai nama ini.
            'recentHafalanRecords' => $latestHafalanRecords,
            'recentMurajaahRecords' => $latestMurajaahRecords,
        ]);
    }

    public function storeHafalan(Request $request, UserAccessService $accessService): RedirectResponse
    {
        Gate::authorize('create', HafalanRecord::class);

        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        $validator = Validator::make($request->all(), [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'surah_id' => ['required', 'integer', 'exists:surahs,id'],
            'surah_end_id' => ['nullable', 'integer', 'exists:surahs,id'],
            'ayah_start' => ['required', 'integer', 'min:1'],
            'ayah_end' => ['required', 'integer', 'min:1'],
            'submission_type' => ['required', Rule::in(['new', 'continuation', 'revision'])],
            'score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['required', Rule::in(['passed', 'repeat', 'needs_improvement'])],
            'submitted_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validator->after(function ($validator) use ($request, $visibleStudentIds) {
            if (! $visibleStudentIds->contains((int) $request->input('student_id'))) {
                $validator->errors()->add('student_id', 'Murid tidak boleh diakses oleh akun ini.');
            }

            $surahStart = Surah::query()->find($request->input('surah_id'));
            $surahEndId = $request->input('surah_end_id') ?: $request->input('surah_id');
            $surahEnd = Surah::query()->find($surahEndId);

            if ($surahStart && $surahEnd) {
                if ($surahEnd->number < $surahStart->number) {
                    $validator->errors()->add('surah_end_id', 'Surah akhir tidak boleh mendahului surah mulai.');
                }

                if ((int) $surahEndId === (int) $request->input('surah_id')) {
                    if ((int) $request->input('ayah_end') < (int) $request->input('ayah_start')) {
                        $validator->errors()->add('ayah_end', 'Ayat akhir harus lebih besar atau sama dengan ayat mulai.');
                    }
                }

                if ((int) $request->input('ayah_end') > (int) $surahEnd->total_ayah) {
                    $validator->errors()->add('ayah_end', "Ayat akhir tidak boleh melebihi {$surahEnd->total_ayah}.");
                }
            }
        });

        $validated = $validator->validate();

        $student = Student::query()->findOrFail($validated['student_id']);

        $teacherId = $this->resolveTeacherId($request, $student);

        if (! $teacherId) {
            return back()
                ->withInput()
                ->with('error', 'Murid ini belum memiliki guru pembimbing. Isi dulu guru pembimbing pada data murid.');
        }

        $surahStartId = (int) $validated['surah_id'];
        $surahEndId = (int) ($validated['surah_end_id'] ?? $surahStartId);

        if ($surahStartId === $surahEndId) {
            HafalanRecord::query()->create([
                'student_id' => $student->id,
                'teacher_id' => $teacherId,
                'surah_id' => $validated['surah_id'],
                'ayah_start' => $validated['ayah_start'],
                'ayah_end' => $validated['ayah_end'],
                'submission_type' => $validated['submission_type'],
                'score' => $validated['score'] ?? null,
                'status' => $validated['status'],
                'submitted_at' => $validated['submitted_at'],
                'notes' => $validated['notes'] ?? null,
            ]);
        } else {
            $surahStart = Surah::findOrFail($surahStartId);
            $surahEnd = Surah::findOrFail($surahEndId);

            $surahs = Surah::whereBetween('number', [$surahStart->number, $surahEnd->number])
                ->orderBy('number')
                ->get();

            DB::transaction(function () use ($surahs, $surahStart, $surahEnd, $validated, $student, $teacherId) {
                foreach ($surahs as $surah) {
                    $recordData = [
                        'student_id' => $student->id,
                        'teacher_id' => $teacherId,
                        'surah_id' => $surah->id,
                        'submission_type' => $validated['submission_type'],
                        'score' => $validated['score'] ?? null,
                        'status' => $validated['status'],
                        'submitted_at' => $validated['submitted_at'],
                        'notes' => $validated['notes'] ?? null,
                    ];

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

                    HafalanRecord::query()->create($recordData);
                }
            });
        }

        return redirect()
            ->route('quick-inputs.index')
            ->with('success', 'Setoran hafalan berhasil disimpan.');
    }

    public function storeMurajaah(Request $request, UserAccessService $accessService): RedirectResponse
    {
        Gate::authorize('create', MurajaahRecord::class);

        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        $validator = Validator::make($request->all(), [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'surah_id' => ['required', 'integer', 'exists:surahs,id'],
            'surah_end_id' => ['nullable', 'integer', 'exists:surahs,id'],
            'ayah_start' => ['required', 'integer', 'min:1'],
            'ayah_end' => ['required', 'integer', 'min:1'],
            'fluency_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'tajwid_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'makhraj_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'overall_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'status' => ['required', Rule::in(['passed', 'repeat', 'needs_improvement'])],
            'reviewed_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validator->after(function ($validator) use ($request, $visibleStudentIds) {
            if (! $visibleStudentIds->contains((int) $request->input('student_id'))) {
                $validator->errors()->add('student_id', 'Murid tidak boleh diakses oleh akun ini.');
            }

            $surahStart = Surah::query()->find($request->input('surah_id'));
            $surahEndId = $request->input('surah_end_id') ?: $request->input('surah_id');
            $surahEnd = Surah::query()->find($surahEndId);

            if ($surahStart && $surahEnd) {
                if ($surahEnd->number < $surahStart->number) {
                    $validator->errors()->add('surah_end_id', 'Surah akhir tidak boleh mendahului surah mulai.');
                }

                if ((int) $surahEndId === (int) $request->input('surah_id')) {
                    if ((int) $request->input('ayah_end') < (int) $request->input('ayah_start')) {
                        $validator->errors()->add('ayah_end', 'Ayat akhir harus lebih besar atau sama dengan ayat mulai.');
                    }
                }

                if ((int) $request->input('ayah_end') > (int) $surahEnd->total_ayah) {
                    $validator->errors()->add('ayah_end', "Ayat akhir tidak boleh melebihi {$surahEnd->total_ayah}.");
                }
            }
        });

        $validated = $validator->validate();

        if (
            blank($validated['overall_score'] ?? null)
            && filled($validated['fluency_score'] ?? null)
            && filled($validated['tajwid_score'] ?? null)
            && filled($validated['makhraj_score'] ?? null)
        ) {
            $validated['overall_score'] = (int) round((
                (float) $validated['fluency_score']
                + (float) $validated['tajwid_score']
                + (float) $validated['makhraj_score']
            ) / 3);
        }

        $student = Student::query()->findOrFail($validated['student_id']);

        $teacherId = $this->resolveTeacherId($request, $student);

        if (! $teacherId) {
            return back()
                ->withInput()
                ->with('error', 'Murid ini belum memiliki guru pembimbing. Isi dulu guru pembimbing pada data murid.');
        }

        $surahStartId = (int) $validated['surah_id'];
        $surahEndId = (int) ($validated['surah_end_id'] ?? $surahStartId);

        if ($surahStartId === $surahEndId) {
            MurajaahRecord::query()->create([
                'student_id' => $student->id,
                'teacher_id' => $teacherId,
                'surah_id' => $validated['surah_id'],
                'ayah_start' => $validated['ayah_start'],
                'ayah_end' => $validated['ayah_end'],
                'fluency_score' => $validated['fluency_score'] ?? null,
                'tajwid_score' => $validated['tajwid_score'] ?? null,
                'makhraj_score' => $validated['makhraj_score'] ?? null,
                'overall_score' => $validated['overall_score'] ?? null,
                'status' => $validated['status'],
                'reviewed_at' => $validated['reviewed_at'],
                'notes' => $validated['notes'] ?? null,
            ]);
        } else {
            $surahStart = Surah::findOrFail($surahStartId);
            $surahEnd = Surah::findOrFail($surahEndId);

            $surahs = Surah::whereBetween('number', [$surahStart->number, $surahEnd->number])
                ->orderBy('number')
                ->get();

            DB::transaction(function () use ($surahs, $surahStart, $surahEnd, $validated, $student, $teacherId) {
                foreach ($surahs as $surah) {
                    $recordData = [
                        'student_id' => $student->id,
                        'teacher_id' => $teacherId,
                        'surah_id' => $surah->id,
                        'fluency_score' => $validated['fluency_score'] ?? null,
                        'tajwid_score' => $validated['tajwid_score'] ?? null,
                        'makhraj_score' => $validated['makhraj_score'] ?? null,
                        'overall_score' => $validated['overall_score'] ?? null,
                        'status' => $validated['status'],
                        'reviewed_at' => $validated['reviewed_at'],
                        'notes' => $validated['notes'] ?? null,
                    ];

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
            ->route('quick-inputs.index')
            ->with('success', 'Data murajaah berhasil disimpan.');
    }

    public function storeUmmi(Request $request, UserAccessService $accessService): RedirectResponse
    {
        Gate::authorize('create', HafalanRecord::class);

        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        if (! $request->filled('class_room_id') && $request->filled('student_id')) {
            $student = Student::query()->find((int) $request->input('student_id'));
            if ($student) {
                $request->merge(['class_room_id' => $student->class_room_id]);
            }
        }

        $validator = Validator::make($request->all(), [
            'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
            'tatap_muka' => ['required', 'integer', 'min:1'],
            'tanggal' => ['required', 'date'],
            'hafalan_surah_id' => ['nullable', 'integer', 'exists:surahs,id'],
            'hafalan_ayah' => ['nullable', 'string', 'max:100'],
            'hafalan_surah_ids' => ['nullable', 'array'],
            'hafalan_surah_ids.*' => ['nullable', 'integer', 'exists:surahs,id'],
            'hafalan_ayahs' => ['nullable', 'array'],
            'hafalan_ayahs.*' => ['nullable', 'string', 'max:100'],
            'hafalan_baris' => ['nullable', 'array'],
            'hafalan_baris.*' => ['nullable', 'numeric', 'min:0'],
            'ummi_jilid' => ['nullable', 'string', 'max:150'],
            'ummi_halaman' => ['nullable', 'string', 'max:100'],
            'materi' => ['nullable', 'string', 'max:255'],
            'nilai' => ['nullable', 'string', 'max:50'],
            'disimak_guru' => ['required', Rule::in(['Ya', 'Tidak'])],
            'disimak_ortu' => ['required', Rule::in(['Ya', 'Tidak'])],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', 'exists:students,id'],
            'student_scores' => ['nullable', 'array'],
            'student_notes' => ['nullable', 'array'],
        ]);

        $validator->after(function ($validator) use ($request, $visibleStudentIds) {
            $classRoomId = (int) $request->input('class_room_id');
            $hasAccess = Student::query()
                ->where('class_room_id', $classRoomId)
                ->whereIn('id', $visibleStudentIds)
                ->where('status', 'active')
                ->exists();

            if (! $hasAccess) {
                $validator->errors()->add('class_room_id', 'Kelas halaqoh tidak memiliki murid aktif atau Anda tidak memiliki akses ke kelas ini.');
            }
        });

        $validated = $validator->validate();

        $classRoomId = (int) $validated['class_room_id'];
        $selectedStudentIds = $request->input('student_ids');

        if ($selectedStudentIds === null) {
            if ($request->filled('student_id')) {
                $selectedStudentIds = [$request->integer('student_id')];
                $students = Student::query()
                    ->whereIn('id', $selectedStudentIds)
                    ->where('class_room_id', $classRoomId)
                    ->whereIn('id', $visibleStudentIds)
                    ->where('status', 'active')
                    ->get();
            } else {
                $students = Student::query()
                    ->where('class_room_id', $classRoomId)
                    ->whereIn('id', $visibleStudentIds)
                    ->where('status', 'active')
                    ->get();
            }
        } else {
            if (empty($selectedStudentIds)) {
                return back()
                    ->withInput()
                    ->with('error', 'Silakan pilih minimal satu murid untuk diinput.');
            }
            $students = Student::query()
                ->whereIn('id', array_map('intval', $selectedStudentIds))
                ->where('class_room_id', $classRoomId)
                ->whereIn('id', $visibleStudentIds)
                ->where('status', 'active')
                ->get();
        }

        if ($students->isEmpty()) {
            return back()
                ->withInput()
                ->with('error', 'Tidak ada murid aktif di kelas halaqoh yang dipilih.');
        }

        $hafalans = [];
        if ($request->has('hafalan_surah_ids')) {
            $surahIds = $request->input('hafalan_surah_ids');
            $ayahs = $request->input('hafalan_ayahs');
            $baris = $request->input('hafalan_baris', []);
            foreach ($surahIds as $idx => $sid) {
                if (! empty($sid)) {
                    $hafalans[] = [
                        'surah_id' => (int) $sid,
                        'ayah' => $ayahs[$idx] ?? null,
                        'baris' => isset($baris[$idx]) && $baris[$idx] !== '' ? (float)$baris[$idx] : null,
                    ];
                }
            }
        } elseif ($request->filled('hafalan_surah_id')) {
            $hafalans[] = [
                'surah_id' => (int) $request->input('hafalan_surah_id'),
                'ayah' => $request->input('hafalan_ayah'),
                'baris' => $request->filled('hafalan_baris') ? (float)$request->input('hafalan_baris') : null,
            ];
        }

        $studentScores = $request->input('student_scores', []);
        $studentNotes = $request->input('student_notes', []);

        DB::transaction(function () use ($students, $hafalans, $validated, $request, $studentScores, $studentNotes) {
            foreach ($students as $student) {
                $teacherId = $this->resolveTeacherId($request, $student);
                if (! $teacherId) {
                    continue; // Skip student without teacher profile
                }

                $individualScore = !empty($studentScores[$student->id]) ? $studentScores[$student->id] : ($validated['nilai'] ?? null);
                $individualNote = !empty($studentNotes[$student->id]) ? $studentNotes[$student->id] : ($validated['keterangan'] ?? null);

                if (empty($hafalans)) {
                    UmmiRecord::query()->create([
                        'student_id' => $student->id,
                        'teacher_id' => $teacherId,
                        'tatap_muka' => $validated['tatap_muka'],
                        'tanggal' => $validated['tanggal'],
                        'hafalan_surah_id' => null,
                        'hafalan_ayah' => null,
                        'baris' => null,
                        'ummi_jilid' => $validated['ummi_jilid'] ?? null,
                        'ummi_halaman' => $validated['ummi_halaman'] ?? null,
                        'materi' => $validated['materi'] ?? null,
                        'nilai' => $individualScore,
                        'disimak_guru' => $validated['disimak_guru'],
                        'disimak_ortu' => $validated['disimak_ortu'],
                        'keterangan' => $individualNote,
                    ]);
                } else {
                    foreach ($hafalans as $hafalan) {
                        UmmiRecord::query()->create([
                            'student_id' => $student->id,
                            'teacher_id' => $teacherId,
                            'tatap_muka' => $validated['tatap_muka'],
                            'tanggal' => $validated['tanggal'],
                            'hafalan_surah_id' => $hafalan['surah_id'],
                            'hafalan_ayah' => $hafalan['ayah'],
                            'baris' => $hafalan['baris'],
                            'ummi_jilid' => $validated['ummi_jilid'] ?? null,
                            'ummi_halaman' => $validated['ummi_halaman'] ?? null,
                            'materi' => $validated['materi'] ?? null,
                            'nilai' => $individualScore,
                            'disimak_guru' => $validated['disimak_guru'],
                            'disimak_ortu' => $validated['disimak_ortu'],
                            'keterangan' => $individualNote,
                        ]);
                    }
                }
            }
        });

        if ($request->input('redirect_to') === 'hafalan') {
            return redirect()
                ->route('hafalan-records.index', ['category' => 'ummi'])
                ->with('success', 'Catatan Tahsin UMMI berhasil disimpan untuk seluruh kelas.');
        }

        return redirect()
            ->route('quick-inputs.index')
            ->with('success', 'Catatan Tahsin UMMI berhasil disimpan untuk seluruh kelas.');
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
}
