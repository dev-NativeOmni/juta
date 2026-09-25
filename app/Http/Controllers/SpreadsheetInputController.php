<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanRecordSurah;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\UmmiRecord;
use App\Services\SchoolCalendar;
use App\Services\UserAccessService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SpreadsheetInputController extends Controller
{
    public function index(Request $request, UserAccessService $accessService): View
    {
        Gate::authorize('create', HafalanRecord::class);

        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        // Get classes matching user scope
        $classRoomIds = Student::query()
            ->whereIn('id', $visibleStudentIds)
            ->where('status', 'active')
            ->pluck('class_room_id')
            ->filter()
            ->unique()
            ->values();

        $classRooms = ClassRoom::query()
            ->with('program')
            ->when($classRoomIds->isNotEmpty(), fn ($q) => $q->whereIn('id', $classRoomIds))
            ->orderBy('name')
            ->get();

        $selectedClassId = $request->input('class_room_id');
        if (! $selectedClassId && $classRooms->isNotEmpty()) {
            $selectedClassId = $classRooms->first()->id;
        }

        $selectedClass = $classRooms->firstWhere('id', $selectedClassId);

        // Get selected month (default to current month)
        $selectedMonth = $request->input('month', date('Y-m'));

        // Parse month dates (Monday to Friday only)
        $year = (int) date('Y', strtotime($selectedMonth.'-01'));
        $month = (int) date('m', strtotime($selectedMonth.'-01'));

        $allDates = [];
        $daysInMonth = (int) date('t', strtotime($selectedMonth.'-01'));
        $calendar = app(SchoolCalendar::class);

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day);
            $isEffective = $selectedClass
                ? $calendar->isTahfizhEffectiveDay($selectedClass, $date)
                : $date->isWeekday() && ! $calendar->isTahfizhHoliday(null, $date);

            if ($isEffective) {
                $allDates[] = $date->toDateString();
            }
        }

        // Group dates by week of the year
        $weeks = [];
        foreach ($allDates as $date) {
            $weekNum = date('W', strtotime($date));
            if (! isset($weeks[$weekNum])) {
                $weeks[$weekNum] = [];
            }
            $weeks[$weekNum][] = $date;
        }

        // Reindex weeks starting from 1 with friendly labels
        $weeksList = [];
        $weekCounter = 1;
        $monthsName = [
            'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr', 'May' => 'Mei', 'Jun' => 'Jun',
            'Jul' => 'Jul', 'Aug' => 'Agt', 'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Des',
        ];

        foreach ($weeks as $weekNum => $weekDates) {
            $startDate = reset($weekDates);
            $endDate = end($weekDates);

            $startDay = date('j', strtotime($startDate));
            $startMonth = $monthsName[date('M', strtotime($startDate))];

            $endDay = date('j', strtotime($endDate));
            $endMonth = $monthsName[date('M', strtotime($endDate))];

            if ($startMonth === $endMonth) {
                $label = "Pekan $weekCounter ($startDay - $endDay $startMonth)";
            } else {
                $label = "Pekan $weekCounter ($startDay $startMonth - $endDay $endMonth)";
            }

            $weeksList[$weekCounter] = [
                'label' => $label,
                'dates' => $weekDates,
            ];
            $weekCounter++;
        }

        $selectedWeek = $request->input('week', 'all');

        $meetingFrequency = $selectedClass?->program?->meeting_frequency ?? 'setiap hari';
        $isWeekly = ($meetingFrequency === 'seminggu sekali');

        $weekNumToRepDate = [];
        foreach ($weeksList as $weekCounter => $wInfo) {
            $weekNum = date('W', strtotime($wInfo['dates'][0]));
            $weekNumToRepDate[$weekNum] = $wInfo['dates'][0];
        }

        $dates = $allDates;
        if ($isWeekly) {
            $dates = [];
            foreach ($weeksList as $weekCounter => $wInfo) {
                $dates[] = $wInfo['dates'][0]; // Representative date (first day of the week)
            }
        } elseif ($selectedWeek !== 'all' && isset($weeksList[$selectedWeek])) {
            $dates = $weeksList[$selectedWeek]['dates'];
        }

        $columns = [];
        if ($isWeekly) {
            foreach ($weeksList as $weekCounter => $wInfo) {
                $repDate = $wInfo['dates'][0];
                $columns[] = [
                    'date' => $repDate,
                    'label' => "Pekan $weekCounter",
                    'sub_label' => date('d/m', strtotime(reset($wInfo['dates']))).' - '.date('d/m', strtotime(end($wInfo['dates']))),
                ];
            }
        } else {
            foreach ($dates as $d) {
                $columns[] = [
                    'date' => $d,
                    'label' => Carbon::parse($d)->translatedFormat('D'),
                    'sub_label' => Carbon::parse($d)->translatedFormat('d M'),
                ];
            }
        }

        $students = collect();
        $attendancesMap = [];
        $hafalanRecordsMap = [];
        $ummiRecordsMap = [];

        if ($selectedClassId) {
            $students = Student::query()
                ->with(['classRoom.program', 'teacher.user'])
                ->where('class_room_id', $selectedClassId)
                ->whereIn('id', $visibleStudentIds)
                ->where('status', 'active')
                ->orderBy('name')
                ->get();

            $studentIds = $students->pluck('id')->toArray();
            $startDate = $selectedMonth.'-01';
            $endDate = date('Y-m-t', strtotime($startDate));

            // Load Attendances
            $attendances = Attendance::query()
                ->whereIn('student_id', $studentIds)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->get();

            foreach ($attendances as $att) {
                $dateStr = $att->tanggal instanceof Carbon ? $att->tanggal->toDateString() : $att->tanggal;
                if ($isWeekly) {
                    $weekNum = date('W', strtotime($dateStr));
                    if (isset($weekNumToRepDate[$weekNum])) {
                        $dateStr = $weekNumToRepDate[$weekNum];
                    }
                }
                $attendancesMap[$att->student_id][$dateStr] = $att->status;
            }

            // Load HafalanRecords
            $hafalanRecords = HafalanRecord::query()
                ->with('surahs')
                ->whereIn('student_id', $studentIds)
                ->whereBetween('submitted_at', [$startDate, $endDate])
                ->get();

            foreach ($hafalanRecords as $record) {
                $dateStr = $record->submitted_at instanceof Carbon ? $record->submitted_at->toDateString() : $record->submitted_at;
                if ($isWeekly) {
                    $weekNum = date('W', strtotime($dateStr));
                    if (isset($weekNumToRepDate[$weekNum])) {
                        $dateStr = $weekNumToRepDate[$weekNum];
                    }
                }

                foreach ($record->surahs as $surahEntry) {
                    $scoreFormatted = $surahEntry->score !== null ? (string) (int) round((float) $surahEntry->score) : '';
                    $hafalanRecordsMap[$record->student_id][$dateStr][] = [
                        'id' => $surahEntry->id,
                        'surah_id' => $surahEntry->surah_id ? (string) $surahEntry->surah_id : '',
                        'ayah_start' => $surahEntry->ayah_start,
                        'ayah_end' => $surahEntry->ayah_end,
                        'score' => $scoreFormatted,
                        'status' => $surahEntry->status,
                        'submission_type' => $surahEntry->submission_type,
                    ];
                }

                if (empty($attendancesMap[$record->student_id][$dateStr])) {
                    $attendancesMap[$record->student_id][$dateStr] = 'hadir';
                }
            }

            // Load UmmiRecords
            $ummiRecords = UmmiRecord::query()
                ->with('surahs')
                ->whereIn('student_id', $studentIds)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->get();

            foreach ($ummiRecords as $record) {
                $dateStr = $record->tanggal instanceof Carbon ? $record->tanggal->toDateString() : $record->tanggal;
                if ($isWeekly) {
                    $weekNum = date('W', strtotime($dateStr));
                    if (isset($weekNumToRepDate[$weekNum])) {
                        $dateStr = $weekNumToRepDate[$weekNum];
                    }
                }
                $ummiRecordsMap[$record->student_id][$dateStr]['tatap_muka'] = $record->tatap_muka;
                $ummiRecordsMap[$record->student_id][$dateStr]['ummi_jilid'] = $record->ummi_jilid;
                $ummiRecordsMap[$record->student_id][$dateStr]['ummi_halaman'] = $record->ummi_halaman;
                $ummiRecordsMap[$record->student_id][$dateStr]['materi'] = $record->materi;
                $ummiRecordsMap[$record->student_id][$dateStr]['nilai'] = $record->nilai;

                if (empty($attendancesMap[$record->student_id][$dateStr])) {
                    $attendancesMap[$record->student_id][$dateStr] = 'hadir';
                }

                foreach ($record->surahs as $surahEntry) {
                    $ummiRecordsMap[$record->student_id][$dateStr]['hafalans'][] = [
                        'id' => $surahEntry->id,
                        'surah_id' => (string) $surahEntry->surah_id,
                        'ayah' => $surahEntry->hafalan_ayah,
                    ];
                }
            }
            // Calculate last hafalan & auto +1 next verse continuation for each student in 1 batch query (O(1) instead of O(N))
            $lastHafalanMap = [];
            if (! empty($studentIds)) {
                $latestSurahIds = DB::table('hafalan_record_surahs')
                    ->join('hafalan_records', 'hafalan_records.id', '=', 'hafalan_record_surahs.hafalan_record_id')
                    ->whereNull('hafalan_records.deleted_at')
                    ->whereIn('hafalan_records.student_id', $studentIds)
                    ->selectRaw('MAX(hafalan_record_surahs.id) as id')
                    ->groupBy('hafalan_records.student_id')
                    ->pluck('id');

                $latestRecords = HafalanRecordSurah::query()
                    ->with(['surah', 'hafalanRecord'])
                    ->whereIn('id', $latestSurahIds)
                    ->get()
                    ->keyBy(fn (HafalanRecordSurah $s) => $s->hafalanRecord->student_id);

                foreach ($students as $student) {
                    $latestRec = $latestRecords->get($student->id);

                    if ($latestRec && $latestRec->surah) {
                        $lSurahId = (int) $latestRec->surah_id;
                        $lAyahEnd = (int) $latestRec->ayah_end;
                        $totalAyah = (int) $latestRec->surah->total_ayah;

                        if ($lAyahEnd < $totalAyah) {
                            $nSurahId = $lSurahId;
                            $nAyahStart = $lAyahEnd + 1;
                        } else {
                            $nSurahId = $lSurahId < 114 ? $lSurahId + 1 : 1;
                            $nAyahStart = 1;
                        }

                        $lastHafalanMap[$student->id] = [
                            'last_surah_id' => $lSurahId,
                            'last_ayah_end' => $lAyahEnd,
                            'next_surah_id' => $nSurahId,
                            'next_ayah_start' => $nAyahStart,
                        ];
                    } else {
                        $lastHafalanMap[$student->id] = null;
                    }
                }
            }
        }

        $surahs = Surah::query()->orderBy('number')->get();

        return view('spreadsheet-input.index', [
            'classRooms' => $classRooms,
            'selectedClassId' => $selectedClassId,
            'selectedClass' => $selectedClass,
            'selectedMonth' => $selectedMonth,
            'weeksList' => $weeksList,
            'selectedWeek' => $selectedWeek,
            'weeks' => $weeks,
            'dates' => $dates,
            'columns' => $columns,
            'isWeekly' => $isWeekly,
            'students' => $students,
            'surahs' => $surahs,
            'attendancesMap' => $attendancesMap,
            'hafalanRecordsMap' => $hafalanRecordsMap,
            'ummiRecordsMap' => $ummiRecordsMap,
            'lastHafalanMap' => $lastHafalanMap,
        ]);
    }

    public function save(Request $request, UserAccessService $accessService): RedirectResponse
    {
        Gate::authorize('create', HafalanRecord::class);

        $validated = $request->validate([
            'class_room_id' => ['required', 'integer', 'exists:class_rooms,id'],
            'month' => ['required', 'string'],
            'type' => ['required', Rule::in(['hafalan', 'ummi'])],
            'records' => ['nullable', 'array'],
        ]);

        $classRoomId = (int) $validated['class_room_id'];
        $type = $validated['type'];

        $visibleStudentIds = $accessService->visibleStudentIds($request->user());

        $classRoom = ClassRoom::find($classRoomId);
        $meetingFrequency = $classRoom?->program?->meeting_frequency ?? 'setiap hari';
        $isWeekly = ($meetingFrequency === 'seminggu sekali');

        // Build weekly dates map if weekly meeting frequency
        $weekDatesMap = [];
        if ($isWeekly) {
            $year = (int) date('Y', strtotime($validated['month'].'-01'));
            $month = (int) date('m', strtotime($validated['month'].'-01'));
            $daysInMonth = (int) date('t', strtotime($validated['month'].'-01'));

            $weeks = [];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $time = mktime(0, 0, 0, $month, $d, $year);
                $dateStr = date('Y-m-d', $time);
                $weekNum = date('W', $time);
                $weeks[$weekNum][] = $dateStr;
            }
            foreach ($weeks as $wDates) {
                $repDate = $wDates[0];
                $weekDatesMap[$repDate] = $wDates;
            }
        }

        $records = $request->input('records');

        if (empty($records) && $request->has('records_json')) {
            $rawJson = $request->input('records_json');
            if (is_array($rawJson)) {
                $records = $rawJson;
            } elseif (is_string($rawJson) && filled($rawJson)) {
                $records = json_decode($rawJson, true);
            }
        }

        if (is_string($records) && filled($records)) {
            $records = json_decode($records, true);
        }

        if (! is_array($records)) {
            $records = [];
        }

        try {
            DB::transaction(function () use ($request, $classRoomId, $type, $visibleStudentIds, $isWeekly, $weekDatesMap, $records) {
                foreach ($records as $studentId => $studentData) {
                    $studentId = (int) $studentId;
                    if (! $visibleStudentIds->contains($studentId)) {
                        continue; // Skip student without access (halaqoh scope)
                    }

                    $student = Student::find($studentId);
                    if (! $student) {
                        continue;
                    }

                    $teacherId = $this->resolveTeacherId($request, $student);
                    if (! $teacherId) {
                        continue; // Skip student without teacher profile
                    }

                    foreach ($studentData['dates'] ?? [] as $date => $cellData) {
                        $attendance = $cellData['attendance'] ?? null;
                        $targetDates = ($isWeekly && ! empty($weekDatesMap[$date])) ? $weekDatesMap[$date] : [$date];

                        // Check if hafalan or UMMI data is filled in for this cell
                        $hasHafalanInput = false;
                        foreach ($cellData['hafalans'] ?? [] as $hData) {
                            if (! empty($hData['surah_id']) && (filled($hData['ayah_start'] ?? null) || filled($hData['ayah_end'] ?? null))) {
                                $hasHafalanInput = true;
                                break;
                            }
                        }

                        $hasUmmiInput = filled($cellData['ummi_jilid'] ?? null)
                            || filled($cellData['ummi_halaman'] ?? null)
                            || filled($cellData['materi'] ?? null)
                            || filled($cellData['nilai'] ?? null);

                        // Auto-mark attendance as 'hadir' if hafalan/UMMI is input but attendance pill was not set
                        if (($hasHafalanInput || $hasUmmiInput) && empty($attendance)) {
                            $attendance = 'hadir';
                        }

                        // 1. Save Attendance
                        if (filled($attendance)) {
                            Attendance::updateOrCreate(
                                ['student_id' => $studentId, 'tanggal' => $date, 'class_room_id' => $classRoomId],
                                ['status' => $attendance]
                            );
                        }

                        // ONLY clear records if student was explicitly marked absent ('sakit', 'izin', 'alpa')
                        if (in_array($attendance, ['sakit', 'izin', 'alpa'], true)) {
                            HafalanRecord::where('student_id', $studentId)
                                ->whereIn('submitted_at', $targetDates)
                                ->delete();
                            UmmiRecord::where('student_id', $studentId)
                                ->whereIn('tanggal', $targetDates)
                                ->delete();

                            continue;
                        }

                        // 2. Save Setoran (Hafalan / UMMI)
                        if ($attendance === 'hadir' || $hasHafalanInput || $hasUmmiInput) {
                            if ($type === 'hafalan') {
                                $this->saveHafalanRecords($studentId, $teacherId, $date, $cellData, $targetDates);
                            } elseif ($type === 'ummi') {
                                if ($student->tahfizh_level === 'ummi' || $hasUmmiInput) {
                                    $this->saveUmmiRecords($studentId, $teacherId, $date, $cellData, $targetDates);
                                } else {
                                    $this->saveHafalanRecords($studentId, $teacherId, $date, $cellData, $targetDates);
                                }
                            }
                        }
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::error('Spreadsheet save error: '.$e->getMessage(), [
                'user_id' => $request->user()?->id,
                'class_room_id' => $classRoomId,
                'exception' => $e,
            ]);

            if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal menyimpan: '.$e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', 'Gagal menyimpan: '.$e->getMessage());
        }

        if ($request->wantsJson() || $request->ajax() || $request->isJson()) {
            $request->session()->flash('success', 'Perubahan data kelas berhasil disimpan.');

            return response()->json([
                'success' => true,
                'message' => 'Perubahan data kelas berhasil disimpan.',
                'redirect' => route('spreadsheet-input.index', [
                    'class_room_id' => $classRoomId,
                    'month' => $validated['month'],
                    'week' => $request->input('week', 'all'),
                ]),
            ]);
        }

        return redirect()
            ->route('spreadsheet-input.index', [
                'class_room_id' => $classRoomId,
                'month' => $validated['month'],
                'week' => $request->input('week', 'all'),
            ])
            ->with('success', 'Perubahan data kelas berhasil disimpan.');
    }

    private function saveHafalanRecords(int $studentId, int $teacherId, string $date, array $cellData, array $targetDates): void
    {
        $existingHeaders = HafalanRecord::where('student_id', $studentId)
            ->whereIn('submitted_at', $targetDates)
            ->orderBy('id')
            ->get();

        $rawHafalans = $cellData['hafalans'] ?? [];
        $hafalansList = array_values(array_filter($rawHafalans, fn ($h) => ! empty($h['surah_id'])));

        if (empty($hafalansList)) {
            HafalanRecord::where('student_id', $studentId)
                ->whereIn('submitted_at', $targetDates)
                ->delete();

            return;
        }

        // Keep a single header row per date; any older duplicate headers get merged away.
        $header = $existingHeaders->first();
        $headerData = [
            'student_id' => $studentId,
            'teacher_id' => $teacherId,
            'submitted_at' => $date,
        ];

        if ($header) {
            $header->update($headerData);
        } else {
            $header = HafalanRecord::create($headerData);
        }

        $duplicateHeaderIds = $existingHeaders->pluck('id')->reject(fn ($id) => $id === $header->id);
        if ($duplicateHeaderIds->isNotEmpty()) {
            HafalanRecord::whereIn('id', $duplicateHeaderIds)->delete();
        }

        $existingSurahIds = $header->surahs()->pluck('id')->all();
        $processedSurahIds = [];

        foreach ($hafalansList as $sortOrder => $hafalanData) {
            $surah = Surah::find($hafalanData['surah_id']);
            if (! $surah) {
                continue;
            }

            $ayahStart = filled($hafalanData['ayah_start'] ?? null) ? (int) $hafalanData['ayah_start'] : 1;
            $ayahEnd = filled($hafalanData['ayah_end'] ?? null) ? (int) $hafalanData['ayah_end'] : $ayahStart;

            $baris = ReportController::calculateLines(
                $surah->number,
                $ayahStart,
                $ayahEnd,
                $surah->total_ayah
            );

            $rawScore = $hafalanData['score'] ?? null;
            $score = (filled($rawScore) && is_numeric($rawScore)) ? (float) $rawScore : null;

            $lineData = [
                'surah_id' => $surah->id,
                'ayah_start' => $ayahStart,
                'ayah_end' => $ayahEnd,
                'score' => $score,
                'status' => filled($hafalanData['status'] ?? null) ? $hafalanData['status'] : 'passed',
                'submission_type' => filled($hafalanData['submission_type'] ?? null) ? $hafalanData['submission_type'] : 'new',
                'baris' => $baris,
                'sort_order' => $sortOrder,
            ];

            $lineId = ! empty($hafalanData['id']) ? (int) $hafalanData['id'] : null;

            if ($lineId && in_array($lineId, $existingSurahIds, true)) {
                $header->surahs()->where('id', $lineId)->update($lineData);
                $processedSurahIds[] = $lineId;
            } else {
                $newLine = $header->surahs()->create($lineData);
                $processedSurahIds[] = $newLine->id;
            }
        }

        $toDeleteSurahIds = array_diff($existingSurahIds, $processedSurahIds);
        if (! empty($toDeleteSurahIds)) {
            $header->surahs()->whereIn('id', $toDeleteSurahIds)->delete();
        }
    }

    private function saveUmmiRecords(int $studentId, int $teacherId, string $date, array $cellData, array $targetDates): void
    {
        $existingRecords = UmmiRecord::where('student_id', $studentId)
            ->whereIn('tanggal', $targetDates)
            ->orderBy('id')
            ->get();

        $ummiJilid = filled($cellData['ummi_jilid'] ?? null) ? $cellData['ummi_jilid'] : null;
        $ummiHalaman = filled($cellData['ummi_halaman'] ?? null) ? $cellData['ummi_halaman'] : null;
        $materi = filled($cellData['materi'] ?? null) ? $cellData['materi'] : null;
        $nilai = filled($cellData['nilai'] ?? null) ? $cellData['nilai'] : null;
        $tatapMuka = filled($cellData['tatap_muka'] ?? null) ? (int) $cellData['tatap_muka'] : 1;

        $hasUmmiFields = filled($ummiJilid) || filled($ummiHalaman) || filled($materi) || filled($nilai);
        $rawHafalans = $cellData['hafalans'] ?? [];
        $hafalansList = array_values(array_filter($rawHafalans, function ($h) {
            return ! empty($h['surah_id']);
        }));

        if (! $hasUmmiFields && empty($hafalansList)) {
            UmmiRecord::where('student_id', $studentId)
                ->whereIn('tanggal', $targetDates)
                ->delete();

            return;
        }

        // Keep a single header row per date; any older duplicate headers get merged away.
        $header = $existingRecords->first();
        $headerData = [
            'student_id' => $studentId,
            'teacher_id' => $teacherId,
            'tatap_muka' => $tatapMuka,
            'tanggal' => $date,
            'ummi_jilid' => $ummiJilid,
            'ummi_halaman' => $ummiHalaman,
            'materi' => $materi,
            'nilai' => $nilai,
            'disimak_guru' => 'Ya',
            'disimak_ortu' => 'Ya',
        ];

        if ($header) {
            $header->update($headerData);
        } else {
            $header = UmmiRecord::create($headerData);
        }

        $duplicateHeaderIds = $existingRecords->pluck('id')->reject(fn ($id) => $id === $header->id);
        if ($duplicateHeaderIds->isNotEmpty()) {
            UmmiRecord::whereIn('id', $duplicateHeaderIds)->delete();
        }

        $existingSurahIds = $header->surahs()->pluck('id')->all();
        $processedSurahIds = [];

        foreach ($hafalansList as $sortOrder => $hafalanData) {
            $surah = Surah::find($hafalanData['surah_id']);
            if (! $surah) {
                continue;
            }

            $baris = 0.0;
            if (! empty($hafalanData['ayah'])) {
                $clean = str_replace(' ', '', $hafalanData['ayah']);
                if (str_contains($clean, '-')) {
                    $parts = explode('-', $clean);
                    $start = (int) $parts[0];
                    $end = (int) $parts[1];
                } else {
                    $start = (int) $clean;
                    $end = (int) $clean;
                }
                if ($start > 0 && $end >= $start) {
                    $baris = ReportController::calculateLines(
                        $surah->number,
                        $start,
                        $end,
                        $surah->total_ayah
                    );
                }
            }

            $lineData = [
                'surah_id' => $surah->id,
                'hafalan_ayah' => $hafalanData['ayah'] ?? null,
                'baris' => $baris,
                'sort_order' => $sortOrder,
            ];

            $lineId = ! empty($hafalanData['id']) ? (int) $hafalanData['id'] : null;

            if ($lineId && in_array($lineId, $existingSurahIds, true)) {
                $header->surahs()->where('id', $lineId)->update($lineData);
                $processedSurahIds[] = $lineId;
            } else {
                $newLine = $header->surahs()->create($lineData);
                $processedSurahIds[] = $newLine->id;
            }
        }

        $toDeleteSurahIds = array_diff($existingSurahIds, $processedSurahIds);
        if (! empty($toDeleteSurahIds)) {
            $header->surahs()->whereIn('id', $toDeleteSurahIds)->delete();
        }
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
