<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassRoomRequest;
use App\Http\Requests\UpdateClassRoomRequest;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\Program;
use App\Models\UmmiRecord;
use App\Models\User;
use App\Services\SchoolCalendar;
use App\Services\SimpleXlsxReader;
use App\Services\SimpleXlsxWriter;
use App\Services\StudentProgressService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClassRoomController extends Controller
{
    public function index(Request $request): View
    {
        $programs = Program::query()
            ->orderBy('name')
            ->get();

        $classRooms = ClassRoom::query()
            ->with(['program', 'pendampingAdab', 'pendampingAdabList', 'waliKelas'])
            ->withCount('students')
            ->when($request->filled('program_id'), function ($query) use ($request) {
                $query->where('program_id', $request->integer('program_id'));
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('class-rooms.index', compact('classRooms', 'programs'));
    }

    public function create(): View
    {
        $programs = Program::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $pendampingList = User::query()
            ->where(function ($q) {
                $q->whereHas('role', fn ($sub) => $sub->where('name', 'pendamping_adab'))
                    ->orWhereHas('roles', fn ($sub) => $sub->where('name', 'pendamping_adab'));
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $waliKelasList = $this->waliKelasCandidates();

        return view('class-rooms.create', compact('programs', 'pendampingList', 'waliKelasList'));
    }

    /**
     * User dengan role wali_kelas yang belum ditugaskan ke kelas lain (satu wali kelas
     * cuma satu kelas), plus yang sedang ditugaskan ke kelas ini sendiri kalau ada.
     */
    private function waliKelasCandidates(?int $currentlyAssignedId = null)
    {
        return User::query()
            ->where(function ($q) use ($currentlyAssignedId) {
                $q->where(function ($sub) {
                    $sub->whereHas('role', fn ($r) => $r->where('name', 'wali_kelas'))
                        ->orWhereHas('roles', fn ($r) => $r->where('name', 'wali_kelas'));
                })->whereDoesntHave('waliKelasClassRoom');

                if ($currentlyAssignedId) {
                    $q->orWhere('id', $currentlyAssignedId);
                }
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    public function store(StoreClassRoomRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $pendampingIds = collect($request->input('pendamping_adab_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($pendampingIds) && ! empty($validated['pendamping_adab_id'])) {
            $pendampingIds = [(int) $validated['pendamping_adab_id']];
        }

        $validated['pendamping_adab_id'] = $pendampingIds[0] ?? null;

        $classRoom = ClassRoom::create($validated);
        $classRoom->pendampingAdabList()->sync($pendampingIds);

        return redirect()
            ->route('class-rooms.index')
            ->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function show(Request $request, ClassRoom $classRoom): View
    {
        $classRoom->load(['program', 'pendampingAdab'])->loadCount('students');

        $students = $classRoom->students()
            ->latest()
            ->paginate(20);

        // Capaian Hafalan logic
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $currentDay = now()->day;
        if ($currentDay <= 7) {
            $defaultWeek = 1;
        } elseif ($currentDay <= 14) {
            $defaultWeek = 2;
        } elseif ($currentDay <= 21) {
            $defaultWeek = 3;
        } elseif ($currentDay <= 28) {
            $defaultWeek = 4;
        } else {
            $defaultWeek = 5;
        }

        $week = (int) $request->input('week', $defaultWeek);

        // Date ranges for weeks
        if ($week === 1) {
            $startDate = Carbon::create($year, $month, 1)->startOfDay();
            $endDate = Carbon::create($year, $month, 7)->endOfDay();
        } elseif ($week === 2) {
            $startDate = Carbon::create($year, $month, 8)->startOfDay();
            $endDate = Carbon::create($year, $month, 14)->endOfDay();
        } elseif ($week === 3) {
            $startDate = Carbon::create($year, $month, 15)->startOfDay();
            $endDate = Carbon::create($year, $month, 21)->endOfDay();
        } elseif ($week === 4) {
            $startDate = Carbon::create($year, $month, 22)->startOfDay();
            $endDate = Carbon::create($year, $month, 28)->endOfDay();
        } else {
            $startDate = Carbon::create($year, $month, 29)->startOfDay();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
        }

        $capaianData = [];
        $allStudents = $classRoom->students()->with(['teacher.user'])->orderBy('name')->get();

        foreach ($allStudents as $index => $std) {
            $records = HafalanRecord::flattenSurahs(
                HafalanRecord::with(['surahs' => fn ($q) => $q->where('status', 'passed')->with('surah')])
                    ->where('student_id', $std->id)
                    ->whereBetween('submitted_at', [$startDate, $endDate])
                    ->whereHas('surahs', fn ($q) => $q->where('status', 'passed'))
                    ->get()
            );

            $surahNames = [];
            $ayatRanges = [];
            $totalLines = 0;
            $scores = [];

            foreach ($records as $rec) {
                if ($rec->surah) {
                    $surahNames[] = $rec->surah->name_latin;
                    $ayatRanges[] = $rec->ayah_start.'-'.$rec->ayah_end;
                    $totalLines += $rec->lines_count;
                }
                if ($rec->score !== null) {
                    $scores[] = $rec->score_letter;
                }
            }

            $avgScoreLetter = '-';
            if (! empty($scores)) {
                $avgScoreLetter = implode(', ', array_unique($scores));
            }

            $capaianData[] = [
                'no' => $index + 1,
                'student' => $std,
                'halaqah' => $classRoom->name,
                'musyrif' => $std->teacher?->user?->name ?? '-',
                'surah' => implode(', ', $surahNames) ?: '-',
                'ayat' => implode(', ', $ayatRanges) ?: '-',
                'baris' => $totalLines,
                'nilai' => $avgScoreLetter,
                'kehadiran' => $records->isNotEmpty() ? 'Hadir' : '-',
            ];
        }

        return view('class-rooms.show', compact(
            'classRoom',
            'students',
            'capaianData',
            'month',
            'year',
            'week'
        ));
    }

    public function exportCapaian(Request $request, ClassRoom $classRoom): StreamedResponse
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $week = (int) $request->input('week', 1);

        // Date ranges for weeks
        if ($week === 1) {
            $startDate = Carbon::create($year, $month, 1)->startOfDay();
            $endDate = Carbon::create($year, $month, 7)->endOfDay();
        } elseif ($week === 2) {
            $startDate = Carbon::create($year, $month, 8)->startOfDay();
            $endDate = Carbon::create($year, $month, 14)->endOfDay();
        } elseif ($week === 3) {
            $startDate = Carbon::create($year, $month, 15)->startOfDay();
            $endDate = Carbon::create($year, $month, 21)->endOfDay();
        } elseif ($week === 4) {
            $startDate = Carbon::create($year, $month, 22)->startOfDay();
            $endDate = Carbon::create($year, $month, 28)->endOfDay();
        } else {
            $startDate = Carbon::create($year, $month, 29)->startOfDay();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
        }

        $headers = [
            'No',
            'Nama Murid',
            'Halaqah',
            'Musyrif',
            'Setoran Surah',
            'Setoran Ayat',
            'Jumlah Baris',
            'Nilai',
            'Kehadiran',
        ];

        $data = [];
        $allStudents = $classRoom->students()->with(['teacher.user'])->orderBy('name')->get();

        foreach ($allStudents as $index => $std) {
            $records = HafalanRecord::flattenSurahs(
                HafalanRecord::with(['surahs' => fn ($q) => $q->where('status', 'passed')->with('surah')])
                    ->where('student_id', $std->id)
                    ->whereBetween('submitted_at', [$startDate, $endDate])
                    ->whereHas('surahs', fn ($q) => $q->where('status', 'passed'))
                    ->get()
            );

            $surahNames = [];
            $ayatRanges = [];
            $totalLines = 0;
            $scores = [];

            foreach ($records as $rec) {
                if ($rec->surah) {
                    $surahNames[] = $rec->surah->name_latin;
                    $ayatRanges[] = $rec->ayah_start.'-'.$rec->ayah_end;
                    $totalLines += $rec->lines_count;
                }
                if ($rec->score !== null) {
                    $scores[] = $rec->score_letter;
                }
            }

            $avgScoreLetter = '-';
            if (! empty($scores)) {
                $avgScoreLetter = implode(', ', array_unique($scores));
            }

            $data[] = [
                $index + 1,
                $std->name,
                $classRoom->name,
                $std->teacher?->user?->name ?? '-',
                implode(', ', $surahNames) ?: '-',
                implode(', ', $ayatRanges) ?: '-',
                $totalLines,
                $avgScoreLetter,
                $records->isNotEmpty() ? 'Hadir' : '-',
            ];
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'capaian_export_').'.xlsx';

        $monthsIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktobers', 11 => 'November', 12 => 'Desember',
        ];
        $monthName = $monthsIndo[$month] ?? 'Bulan';
        $fileName = 'Capaian_Hafalan_'.str_replace(' ', '_', $classRoom->name).'_'.$monthName.'_Pekan_'.$week.'.xlsx';

        SimpleXlsxWriter::write($tempFile, $headers, $data);

        return response()->streamDownload(function () use ($tempFile) {
            readfile($tempFile);
            @unlink($tempFile);
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function edit(ClassRoom $classRoom): View
    {
        $programs = Program::query()
            ->orderBy('name')
            ->get();

        $pendampingList = User::query()
            ->where(function ($q) {
                $q->whereHas('role', fn ($sub) => $sub->where('name', 'pendamping_adab'))
                    ->orWhereHas('roles', fn ($sub) => $sub->where('name', 'pendamping_adab'));
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $waliKelasList = $this->waliKelasCandidates($classRoom->wali_kelas_user_id);

        return view('class-rooms.edit', compact('classRoom', 'programs', 'pendampingList', 'waliKelasList'));
    }

    public function update(UpdateClassRoomRequest $request, ClassRoom $classRoom): RedirectResponse
    {
        $validated = $request->validated();
        $pendampingIds = collect($request->input('pendamping_adab_ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($pendampingIds) && ! empty($validated['pendamping_adab_id'])) {
            $pendampingIds = [(int) $validated['pendamping_adab_id']];
        }

        $validated['pendamping_adab_id'] = $pendampingIds[0] ?? null;

        $classRoom->update($validated);
        $classRoom->pendampingAdabList()->sync($pendampingIds);

        return redirect()
            ->route('class-rooms.index')
            ->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(ClassRoom $classRoom): RedirectResponse
    {
        if ($classRoom->students()->exists()) {
            return back()->with('error', 'Kelas tidak bisa dihapus karena masih memiliki murid.');
        }

        $classRoom->delete();

        return redirect()
            ->route('class-rooms.index')
            ->with('success', 'Kelas berhasil dihapus.');
    }

    // -------------------------------------------------------------------------
    // Excel Export
    // -------------------------------------------------------------------------
    public function export(): StreamedResponse
    {
        $classRooms = ClassRoom::query()
            ->with('program')
            ->withCount('students')
            ->orderBy('name')
            ->get();

        $headers = ['Nama Kelas', 'Level', 'Program', 'Jumlah Murid'];
        $data = [];

        foreach ($classRooms as $classRoom) {
            $data[] = [
                $classRoom->name,
                $classRoom->level ?? '',
                $classRoom->program?->name ?? '',
                $classRoom->students_count,
            ];
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'classrooms_export_').'.xlsx';
        $fileName = 'kelas_'.now()->format('Ymd_His').'.xlsx';

        SimpleXlsxWriter::write($tempFile, $headers, $data);

        return response()->streamDownload(function () use ($tempFile) {
            readfile($tempFile);
            @unlink($tempFile);
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // -------------------------------------------------------------------------
    // Excel Import
    // -------------------------------------------------------------------------
    public function import(Request $request): RedirectResponse
    {
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
            } catch (\Throwable $e) {
                return redirect()->back()->with('error', 'Gagal membaca berkas Excel: '.$e->getMessage());
            }
        } else {
            $handle = fopen($filePath, 'r');
            if ($handle === false) {
                return redirect()->back()->with('error', 'Gagal membuka berkas.');
            }
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $rows[] = $row;
            }
            fclose($handle);
        }

        if (empty($rows)) {
            return redirect()->back()->with('error', 'Berkas kosong atau tidak valid.');
        }

        $header = array_shift($rows);
        $header = array_map(
            fn ($h): string => trim(strtolower((string) preg_replace('/[\x{FEFF}\x{200B}]/u', '', (string) $h))),
            (array) $header
        );

        $col = static function (string $name) use ($header): ?int {
            $v = array_search($name, $header, true);

            return $v !== false ? (int) $v : null;
        };

        /** @var array<string, int|null> $map */
        $map = [
            'nama' => $col('nama kelas') ?? $col('nama') ?? $col('name'),
            'level' => $col('level'),
            'program' => $col('program') ?? $col('nama program'),
        ];

        if ($map['nama'] === null) {
            return redirect()->back()->with('error', 'Format tidak valid. Kolom "Nama Kelas" wajib ada.');
        }

        $imported = 0;
        $updated = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $name = trim((string) ($row[$map['nama']] ?? ''));
                if ($name === '') {
                    continue;
                }

                $level = $map['level'] !== null ? trim((string) ($row[$map['level']] ?? '')) : null;

                $programId = null;
                if ($map['program'] !== null) {
                    $programName = trim((string) ($row[$map['program']] ?? ''));
                    if ($programName !== '') {
                        $programId = Program::where('name', $programName)->value('id');
                    }
                }

                $existing = ClassRoom::where('name', $name)
                    ->when($programId, fn ($q) => $q->where('program_id', $programId))
                    ->first();

                if ($existing) {
                    $payload = [];
                    if ($level !== null && $level !== '') {
                        $payload['level'] = $level;
                    }
                    if ($programId !== null) {
                        $payload['program_id'] = $programId;
                    }
                    if (! empty($payload)) {
                        $existing->update($payload);
                    }
                    $updated++;
                } else {
                    ClassRoom::create([
                        'name' => $name,
                        'level' => $level ?: null,
                        'program_id' => $programId,
                    ]);
                    $imported++;
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal mengimpor: '.$e->getMessage());
        }

        return redirect()->route('class-rooms.index')
            ->with('success', "Impor selesai. {$imported} kelas ditambahkan, {$updated} diperbarui.");
    }

    public function printUmmiCards(Request $request, ClassRoom $classRoom): View
    {
        $user = $request->user();

        abort_if($user->hasAnyRole(['student', 'parent']), 403, 'Akses cetak kartu UMMI kelas tidak diperbolehkan untuk akun murid dan orang tua.');

        // Get visible students to ensure access control
        $visibleStudentIds = app(StudentProgressService::class)
            ->visibleStudentQuery($user)
            ->pluck('id');

        $students = $classRoom->students()
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->filter(fn ($std) => $visibleStudentIds->contains($std->id))
            ->values();

        abort_if($students->isEmpty(), 403, 'Akses tidak diperbolehkan atau tidak ada murid aktif di kelas ini.');

        $studentsData = [];
        foreach ($students as $student) {
            $records = UmmiRecord::with('surahs.surah')
                ->where('student_id', $student->id)
                ->orderBy('tanggal')
                ->orderBy('tatap_muka')
                ->orderBy('id')
                ->get();

            $latestUmmiRecord = $records->last();
            $studentsData[] = [
                'student' => $student,
                'records' => $records,
                'latestUmmiRecord' => $latestUmmiRecord,
            ];
        }

        return view('class-rooms.print-ummi-cards', compact('classRoom', 'studentsData'));
    }

    public function scheduleIndex(Request $request): View
    {
        $classRooms = ClassRoom::query()
            ->with('program')
            ->orderBy('name')
            ->get();

        $daysOfWeek = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];

        $scheduleBoard = [];
        foreach ($daysOfWeek as $dayNum => $dayName) {
            $scheduleBoard[$dayNum] = [
                'name' => $dayName,
                'classRooms' => $classRooms->filter(function ($class) use ($dayNum) {
                    return in_array($dayNum, $class->tahfizh_days, true);
                })->values(),
            ];
        }

        // Jadwal per pekan (tab "Jadwal Per Pekan"): jadwal khusus & kunci pekan.
        $calendar = app(SchoolCalendar::class);
        $weekStart = $calendar->weekStart(Carbon::parse($request->input('week', today()->toDateString())));
        $weekStates = $classRooms->mapWithKeys(fn (ClassRoom $class) => [$class->id => $calendar->weekState($class, $weekStart)]);
        $activeTab = $request->input('tab') === 'weekly' ? 'weekly' : 'default';

        return view('class-rooms.schedules', compact('scheduleBoard', 'classRooms', 'daysOfWeek', 'weekStart', 'weekStates', 'activeTab'));
    }

    /**
     * Simpan jadwal khusus satu pekan untuk semua kelas; kelas yang pekannya terkunci dilewati.
     */
    public function scheduleWeekUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'week' => ['required', 'date'],
            'schedules' => ['array'],
            'schedules.*' => ['array'],
            'schedules.*.*' => ['integer', 'between:1,7'],
        ]);
        $calendar = app(SchoolCalendar::class);
        $weekStart = $calendar->weekStart(Carbon::parse($validated['week']));
        $lockedCount = 0;

        // Kelas tanpa centang = tidak ada pertemuan pekan itu (mis. pekan ASTS).
        foreach (ClassRoom::query()->get() as $class) {
            if (! $calendar->saveWeek($class, $weekStart, $validated['schedules'][$class->id] ?? [], $request->user()?->id)) {
                $lockedCount++;
            }
        }

        $message = 'Jadwal pekan '.$weekStart->format('d-m-Y').' berhasil disimpan.';
        if ($lockedCount > 0) {
            $message .= " {$lockedCount} kelas terkunci tidak diubah.";
        }

        return redirect()
            ->route('class-schedules.index', ['tab' => 'weekly', 'week' => $weekStart->toDateString()])
            ->with('success', $message);
    }

    /**
     * Kunci / buka kunci jadwal satu pekan (semua kelas atau kelas tertentu).
     */
    public function scheduleWeekLock(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'week' => ['required', 'date'],
            'action' => ['required', 'in:lock,unlock'],
            'class_room_id' => ['nullable', 'integer', 'exists:class_rooms,id'],
        ]);
        $calendar = app(SchoolCalendar::class);
        $weekStart = $calendar->weekStart(Carbon::parse($validated['week']));
        $classes = ClassRoom::query()
            ->when($validated['class_room_id'] ?? null, fn ($q, $id) => $q->whereKey($id))
            ->get();

        foreach ($classes as $class) {
            $validated['action'] === 'lock'
                ? $calendar->lockWeek($class, $weekStart, $request->user()?->id)
                : $calendar->unlockWeek($class, $weekStart);
        }

        return redirect()
            ->route('class-schedules.index', ['tab' => 'weekly', 'week' => $weekStart->toDateString()])
            ->with('success', $validated['action'] === 'lock' ? 'Jadwal pekan ini dikunci.' : 'Kunci jadwal pekan ini dibuka.');
    }

    public function scheduleUpdate(Request $request): RedirectResponse
    {
        $schedules = $request->input('schedules', []);

        $allClassrooms = ClassRoom::all();

        foreach ($allClassrooms as $class) {
            $days = [];
            for ($day = 1; $day <= 7; $day++) {
                $isActive = isset($schedules[$day][$class->id]) && (int) $schedules[$day][$class->id] === 1;
                if ($isActive) {
                    $days[] = $day;
                }
            }
            sort($days);
            $class->tahfizh_days = $days;
            $class->save();
        }

        return redirect()
            ->route('class-schedules.index')
            ->with('success', 'Jadwal pelajaran tahfizh berhasil diperbarui.');
    }
}
