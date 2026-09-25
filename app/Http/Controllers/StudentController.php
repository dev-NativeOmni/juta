<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\ClassRoom;
use App\Models\ParentProfile;
use App\Models\Role;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\SimpleXlsxReader;
use App\Services\SimpleXlsxWriter;
use App\Support\HafalanOrder;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $classRooms = ClassRoom::query()
            ->with('program')
            ->orderBy('name')
            ->get();

        $students = Student::query()
            ->with([
                'user',
                'classRoom.program',
                'teacher.user',
                'parents.user',
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('student_number', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('class_room_id'), function ($query) use ($request) {
                $query->where('class_room_id', $request->integer('class_room_id'));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status')->toString());
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('students.index', compact('students', 'classRooms'));
    }

    public function create(): View
    {
        return view('students.create', $this->formData());
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $student = Student::create($this->studentPayload($validated));

        $this->syncParents($student, $validated);

        return redirect()
            ->route('students.index')
            ->with('success', 'Data murid berhasil ditambahkan.');
    }

    public function show(Student $student): View
    {
        $student->load([
            'user',
            'classRoom.program',
            'teacher.user',
            'parents.user',
            'points.logger',
        ]);

        $hafalanRecords = $student->hafalanRecords()
            ->with([
                'surahs.surah',
                'teacher.user',
            ])
            ->latest('submitted_at')
            ->latest()
            ->paginate(20);

        return view('students.show', compact('student', 'hafalanRecords'));
    }

    public function edit(Student $student): View
    {
        $student->load('parents');

        return view('students.edit', array_merge(
            ['student' => $student],
            $this->formData($student)
        ));
    }

    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        $validated = $request->validated();

        $student->update($this->studentPayload($validated));

        $this->syncParents($student, $validated);

        return redirect()
            ->route('students.index')
            ->with('success', 'Data murid berhasil diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->parents()->detach();
        $student->delete();

        return redirect()
            ->route('students.index')
            ->with('success', 'Data murid berhasil dihapus.');
    }

    private function formData(?Student $student = null): array
    {
        $classRooms = ClassRoom::query()
            ->with('program')
            ->orderBy('name')
            ->get();

        $teachers = TeacherProfile::query()
            ->with('user')
            ->whereHas('user', function ($query) {
                $query->where('status', 'active');
            })
            ->get()
            ->sortBy(fn (TeacherProfile $teacher) => $teacher->user?->name);

        $parents = ParentProfile::query()
            ->with('user')
            ->whereHas('user', function ($query) {
                $query->where('status', 'active');
            })
            ->get()
            ->sortBy(fn (ParentProfile $parent) => $parent->user?->name);

        $studentUsers = User::query()
            ->with('role')
            ->whereHas('role', function ($query) {
                $query->where('name', 'student');
            })
            ->where('status', 'active')
            ->where(function ($query) use ($student) {
                $query->whereDoesntHave('studentProfile');

                if ($student?->user_id) {
                    $query->orWhere('id', $student->user_id);
                }
            })
            ->orderBy('name')
            ->get();

        return compact('classRooms', 'teachers', 'parents', 'studentUsers');
    }

    private function studentPayload(array $validated): array
    {
        $payload = Arr::only($validated, [
            'user_id',
            'class_room_id',
            'teacher_id',
            'name',
            'student_number',
            'gender',
            'birth_date',
            'status',
            'tahfizh_level',
            'hafalan_direction',
        ]);

        $payload['hafalan_direction'] = HafalanOrder::normalizeDirection($payload['hafalan_direction'] ?? null);

        if (empty($payload['tahfizh_level'])) {
            $class = ClassRoom::find($payload['class_room_id'] ?? null);
            if ($class && (preg_match('/\bX\b/i', $class->name) || preg_match('/\b10\b/i', $class->name) || ($class->level && (preg_match('/\bX\b/i', $class->level) || preg_match('/\b10\b/i', $class->level))))) {
                $payload['tahfizh_level'] = 'ummi';
            } else {
                $payload['tahfizh_level'] = 'reguler';
            }
        }

        return $payload;
    }

    private function syncParents(Student $student, array $validated): void
    {
        $parentIds = collect($validated['parent_ids'] ?? [])
            ->filter()
            ->unique()
            ->values();

        $parentRelations = $validated['parent_relations'] ?? [];

        $syncData = $parentIds
            ->mapWithKeys(function ($parentId) use ($parentRelations) {
                return [
                    (int) $parentId => [
                        'relation' => $parentRelations[$parentId] ?? null,
                    ],
                ];
            })
            ->all();

        $student->parents()->sync($syncData);
    }

    public function export(): StreamedResponse
    {
        if (! auth()->user()?->hasAnyRole(['super_admin', 'admin', 'teacher', 'coordinator_tahfizh', 'tanse'])) {
            abort(403, 'Anda tidak memiliki hak akses untuk ekspor data.');
        }

        $students = Student::query()
            ->with([
                'user',
                'classRoom',
                'teacher.user',
                'parents.user',
            ])
            ->get();

        $fileName = 'daftar-murid-'.now()->format('Ymd-His').'.xlsx';

        $tempFile = @tempnam(sys_get_temp_dir(), 'export_xlsx');
        if (! $tempFile) {
            abort(500, 'Gagal membuat file sementara.');
        }

        $headers = [
            'Nama Murid',
            'Nomor Induk',
            'Jenis Kelamin',
            'Tanggal Lahir',
            'Status',
            'Kelas',
            'Username Guru',
            'Username Murid',
            'Username Orangtua',
            'Hubungan Orangtua',
            'Level Tahfizh',
        ];

        $data = [];
        foreach ($students as $student) {
            $data[] = [
                $student->name,
                $student->student_number,
                $student->gender,
                $student->birth_date ? optional($student->birth_date)->toDateString() : '',
                $student->status,
                $student->classRoom?->name,
                $student->teacher?->user?->username,
                $student->user?->username,
                $student->parents->map(fn ($p) => $p->user?->username)->implode(','),
                $student->parents->map(fn ($p) => $p->pivot?->relation)->implode(','),
                $student->tahfizh_level,
            ];
        }

        SimpleXlsxWriter::write($tempFile, $headers, $data);

        return response()->streamDownload(function () use ($tempFile) {
            readfile($tempFile);
            @unlink($tempFile);
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        if (! auth()->user()?->hasAnyRole(['super_admin', 'admin', 'teacher', 'coordinator_tahfizh', 'tanse'])) {
            abort(403, 'Anda tidak memiliki hak akses untuk impor data.');
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv,txt|max:4096',
        ]);

        $file = $request->file('file');
        $filePath = $file->getRealPath();
        $extension = strtolower($file->getClientOriginalExtension());

        /** @var list<list<string|null>> $rows */
        $rows = [];

        if ($extension === 'xlsx') {
            try {
                $rows = SimpleXlsxReader::read($filePath);
            } catch (\Exception $e) {
                return redirect()->back()->with('error', 'Gagal membaca berkas Excel: '.$e->getMessage());
            }
        } else {
            // Fallback to CSV parser
            $handle = fopen($filePath, 'r');
            if ($handle === false) {
                return redirect()->back()->with('error', 'Gagal membuka berkas.');
            }

            // Read the first line to check for sep=; instruction (Excel helper)
            $firstLine = fgets($handle);
            $cleanFirstLine = $firstLine !== false ? (string) preg_replace('/[\x{FEFF}\x{200B}]/u', '', $firstLine) : '';

            $separator = ',';
            if ($firstLine !== false) {
                $trimmedFirstLine = trim(strtolower($cleanFirstLine));
                if (str_starts_with($trimmedFirstLine, 'sep=')) {
                    $parts = explode('=', $trimmedFirstLine);
                    if (isset($parts[1]) && ! empty(trim($parts[1]))) {
                        $separator = trim($parts[1])[0];
                    }
                } else {
                    rewind($handle);
                    // Auto-detect separator if not specified
                    $cleanFirstLineForDetect = (string) preg_replace('/[\x{FEFF}\x{200B}]/u', '', $firstLine);
                    if (str_contains($cleanFirstLineForDetect, ';') && ! str_contains($cleanFirstLineForDetect, ',')) {
                        $separator = ';';
                    }
                }
            }

            while (($row = fgetcsv($handle, 1000, $separator)) !== false) {
                $rows[] = $row;
            }
            fclose($handle);
        }

        if (empty($rows)) {
            return redirect()->back()->with('error', 'Berkas kosong atau tidak valid.');
        }

        // The first row is the header
        $header = array_shift($rows);

        // Clean headers (remove BOM or spaces)
        $header = array_map(function ($h): string {
            $h = (string) preg_replace('/[\x{FEFF}\x{200B}]/u', '', (string) $h); // strip BOM
            $h = (string) preg_replace('/\s+/', ' ', $h);                          // normalize spaces

            return trim(strtolower($h));
        }, (array) $header);

        // Helper: resolve column index, returning null instead of false when not found
        $col = static function (string $name) use ($header): ?int {
            $result = array_search($name, $header, true);

            return $result !== false ? (int) $result : null;
        };

        // Map header column names to indexes (null = column not present in file)
        /** @var array<string, int|null> $map */
        $map = [
            'nama_santri' => $col('nama murid') ?? $col('nama santri') ?? $col('name'),
            'nomor_induk' => $col('nomor induk') ?? $col('student_number'),
            'jenis_kelamin' => $col('jenis kelamin') ?? $col('gender'),
            'tanggal_lahir' => $col('tanggal lahir') ?? $col('birth_date'),
            'status' => $col('status'),
            'kelas' => $col('kelas') ?? $col('class_room'),
            'tahfizh_level' => $col('level tahfizh') ?? $col('tahfizh level') ?? $col('level'),
            'username_guru' => $col('username guru') ?? $col('teacher_username') ?? $col('email guru') ?? $col('teacher_email'),
            'username_santri' => $col('username murid') ?? $col('username santri') ?? $col('student_username') ?? $col('email santri') ?? $col('student_email'),
            'username_orangtua' => $col('username orangtua') ?? $col('parent_usernames') ?? $col('email orangtua') ?? $col('parent_emails'),
            'hubungan_orangtua' => $col('hubungan orangtua') ?? $col('parent_relations'),
        ];

        if ($map['nama_santri'] === null) {
            return redirect()->back()->with('error', 'Format berkas tidak valid. Harus memiliki kolom "Nama Murid".');
        }

        $importedCount = 0;
        $updatedCount = 0;

        $defaultPasswordHash = Hash::make('password123');

        DB::beginTransaction();

        try {
            foreach ($rows as $row) {
                if (empty($row) || count($row) < 1 || is_null($row[0])) {
                    continue;
                }

                $name = (string) ($row[$map['nama_santri']] ?? '');
                if (empty(trim($name))) {
                    continue;
                }

                $studentNumber = $map['nomor_induk'] !== null
                    ? trim((string) ($row[$map['nomor_induk']] ?? ''))
                    : null;
                $gender = $map['jenis_kelamin'] !== null
                    ? strtolower(trim((string) ($row[$map['jenis_kelamin']] ?? '')))
                    : 'male';
                if (! in_array($gender, ['male', 'female'], true)) {
                    $gender = 'male';
                }

                $birthDate = $map['tanggal_lahir'] !== null
                    ? trim((string) ($row[$map['tanggal_lahir']] ?? ''))
                    : null;
                if (! empty($birthDate)) {
                    if (is_numeric($birthDate) && (int) $birthDate > 10000 && (int) $birthDate < 100000) {
                        try {
                            $birthDate = Carbon::createFromTimestamp(($birthDate - 25569) * 86400)->toDateString();
                        } catch (\Exception $e) {
                            $birthDate = null;
                        }
                    } else {
                        try {
                            $birthDate = Carbon::parse($birthDate)->toDateString();
                        } catch (\Exception $e) {
                            $birthDate = null;
                        }
                    }
                } else {
                    $birthDate = null;
                }

                $status = $map['status'] !== null
                    ? strtolower(trim((string) ($row[$map['status']] ?? '')))
                    : 'active';
                if (! in_array($status, ['active', 'inactive', 'graduated'], true)) {
                    $status = 'active';
                }

                $classRoomId = null;
                if ($map['kelas'] !== null) {
                    $className = trim((string) ($row[$map['kelas']] ?? ''));
                    if (! empty($className)) {
                        $classRoomId = ClassRoom::query()->where('name', $className)->value('id');
                    }
                }

                $teacherId = null;
                if ($map['username_guru'] !== null) {
                    $teacherUsername = trim((string) ($row[$map['username_guru']] ?? ''));
                    if (str_contains($teacherUsername, '@')) {
                        $teacherUsername = explode('@', $teacherUsername)[0];
                    }
                    if (! empty($teacherUsername)) {
                        $teacherId = TeacherProfile::query()
                            ->whereHas('user', function ($q) use ($teacherUsername) {
                                $q->where('username', $teacherUsername);
                            })
                            ->value('id');
                    }
                }

                $studentUserId = null;
                if ($map['username_santri'] !== null) {
                    $studentUsername = trim((string) ($row[$map['username_santri']] ?? ''));
                    if (str_contains($studentUsername, '@')) {
                        $studentUsername = explode('@', $studentUsername)[0];
                    }
                    if (! empty($studentUsername)) {
                        $studentUser = User::query()->where('username', $studentUsername)->first();
                        if (! $studentUser) {
                            $studentRole = Role::where('name', 'student')->first();
                            if ($studentRole) {
                                $studentUser = User::create([
                                    'role_id' => $studentRole->id,
                                    'name' => $name,
                                    'username' => $studentUsername,
                                    'password' => $defaultPasswordHash,
                                    'plain_password' => 'password123',
                                    'status' => 'active',
                                ]);
                            }
                        }
                        if ($studentUser) {
                            $studentUserId = $studentUser->id;
                        }
                    }
                }

                $student = null;
                // 1. Prioritize matching by user_id (via Username Murid) so reordering/changing student_number works seamlessly
                if (! empty($studentUserId)) {
                    $student = Student::query()->where('user_id', $studentUserId)->first();
                }

                // 2. Fallback matching by student_number
                if (! $student && ! empty($studentNumber)) {
                    $student = Student::query()->where('student_number', $studentNumber)->first();
                }

                // 3. Fallback matching by exact Student Name
                if (! $student && ! empty($name)) {
                    $student = Student::query()->where('name', $name)->first();
                }

                $tahfizhLevel = null;
                if ($map['tahfizh_level'] !== null) {
                    $val = strtolower(trim((string) ($row[$map['tahfizh_level']] ?? '')));
                    if (in_array($val, ['tahsin', 'reguler', 'akselerasi', 'ummi'], true)) {
                        $tahfizhLevel = $val;
                    }
                }

                if (empty($tahfizhLevel)) {
                    if ($classRoomId) {
                        $class = ClassRoom::find($classRoomId);
                        if ($class && (preg_match('/\bX\b/i', $class->name) || preg_match('/\b10\b/i', $class->name) || ($class->level && (preg_match('/\bX\b/i', $class->level) || preg_match('/\b10\b/i', $class->level))))) {
                            $tahfizhLevel = 'ummi';
                        } else {
                            $tahfizhLevel = 'reguler';
                        }
                    } else {
                        $tahfizhLevel = 'reguler';
                    }
                }

                if ($student) {
                    // Update only non-null values from import to prevent overwriting existing data with null
                    $updatePayload = [];
                    if (! empty($name)) {
                        $updatePayload['name'] = $name;
                    }
                    if (! empty($gender)) {
                        $updatePayload['gender'] = $gender;
                    }
                    if (! empty($status)) {
                        $updatePayload['status'] = $status;
                    }
                    if (! empty($studentNumber)) {
                        $updatePayload['student_number'] = $studentNumber;
                    }

                    if ($birthDate !== null) {
                        $updatePayload['birth_date'] = $birthDate;
                    }
                    if ($classRoomId !== null) {
                        $updatePayload['class_room_id'] = $classRoomId;
                    }
                    if ($teacherId !== null) {
                        $updatePayload['teacher_id'] = $teacherId;
                    }
                    if ($studentUserId !== null) {
                        $updatePayload['user_id'] = $studentUserId;
                    }

                    if ($tahfizhLevel !== null) {
                        $updatePayload['tahfizh_level'] = $tahfizhLevel;
                    }

                    $student->update($updatePayload);
                    $updatedCount++;
                } else {
                    $payload = [
                        'name' => $name,
                        'gender' => $gender,
                        'birth_date' => $birthDate,
                        'status' => $status,
                        'class_room_id' => $classRoomId,
                        'teacher_id' => $teacherId,
                        'user_id' => $studentUserId,
                        'student_number' => $studentNumber,
                        'tahfizh_level' => $tahfizhLevel,
                    ];
                    $student = Student::create($payload);
                    $importedCount++;
                }

                // Handle Parents sync
                if ($map['username_orangtua'] !== null) {
                    $parentUsernamesStr = trim((string) ($row[$map['username_orangtua']] ?? ''));
                    $parentRelationsStr = $map['hubungan_orangtua'] !== null
                        ? trim((string) ($row[$map['hubungan_orangtua']] ?? ''))
                        : '';

                    if (! empty($parentUsernamesStr)) {
                        $usernames = preg_split('/[;,]/', $parentUsernamesStr);
                        $relations = preg_split('/[;,]/', $parentRelationsStr);

                        $syncData = [];

                        foreach ($usernames as $index => $username) {
                            $username = trim((string) $username);
                            if (str_contains($username, '@')) {
                                $username = explode('@', $username)[0];
                            }
                            if (empty($username)) {
                                continue;
                            }

                            // Look up ParentProfile, or auto-create it if user exists with parent role
                            $parentProfile = ParentProfile::query()
                                ->whereHas('user', function ($q) use ($username) {
                                    $q->where('username', $username);
                                })
                                ->first();

                            if (! $parentProfile) {
                                // Try to find the User and create a ParentProfile automatically
                                $parentUser = User::query()
                                    ->where('username', $username)
                                    ->whereHas('role', function ($q) {
                                        $q->where('name', 'parent');
                                    })
                                    ->first();

                                if (! $parentUser) {
                                    $existingUser = User::where('username', $username)->first();
                                    if (! $existingUser) {
                                        $parentRole = Role::where('name', 'parent')->first();
                                        if ($parentRole) {
                                            $parentUser = User::create([
                                                'role_id' => $parentRole->id,
                                                'name' => ucwords(str_replace(['.', '_', '-'], ' ', $username)),
                                                'username' => $username,
                                                'password' => $defaultPasswordHash,
                                                'plain_password' => 'password123',
                                                'status' => 'active',
                                            ]);
                                        }
                                    } elseif ($existingUser->hasRole('parent')) {
                                        $parentUser = $existingUser;
                                    }
                                }

                                if ($parentUser) {
                                    $parentProfile = ParentProfile::create([
                                        'user_id' => $parentUser->id,
                                        'phone' => null,
                                        'address' => null,
                                    ]);
                                }
                            }

                            if ($parentProfile) {
                                $relation = isset($relations[$index]) && trim((string) $relations[$index]) !== ''
                                    ? trim((string) $relations[$index])
                                    : 'Wali';
                                $syncData[$parentProfile->id] = ['relation' => $relation];
                            }
                        }

                        if (! empty($syncData)) {
                            $student->parents()->sync($syncData);
                        }
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengimpor data: '.$e->getMessage());
        }

        return redirect()
            ->route('students.index')
            ->with('success', "Impor selesai. {$importedCount} data murid ditambahkan, {$updatedCount} data diperbarui.");
    }
}
