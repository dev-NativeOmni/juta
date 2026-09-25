<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\HafalanRecordResource;
use App\Models\HafalanRecordSurah;
use App\Services\Api\V1\StudentApiService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;

class HafalanRecordController extends Controller
{
    public function index(Request $request, StudentApiService $studentApiService): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => [
                'nullable',
                'integer',
            ],
            'teacher_id' => [
                'nullable',
                'integer',
            ],
            'surah_id' => [
                'nullable',
                'integer',
            ],
            'status' => [
                'nullable',
                Rule::in(['passed', 'repeat', 'needs_improvement']),
            ],
            'submission_type' => [
                'nullable',
                Rule::in(['new', 'continuation', 'revision']),
            ],
            'from' => [
                'nullable',
                'date',
            ],
            'to' => [
                'nullable',
                'date',
                'after_or_equal:from',
            ],
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:50',
            ],
        ]);

        $perPage = min(max((int) ($validated['per_page'] ?? 15), 1), 50);
        $search = trim((string) ($validated['search'] ?? ''));

        $visibleStudentIdsQuery = $studentApiService
            ->visibleStudentQuery($request->user())
            ->select('students.id');

        $records = HafalanRecordSurah::query()
            ->select('hafalan_record_surahs.*')
            ->join('hafalan_records', 'hafalan_records.id', '=', 'hafalan_record_surahs.hafalan_record_id')
            ->whereNull('hafalan_records.deleted_at')
            ->with([
                'hafalanRecord.student.classRoom.program',
                'hafalanRecord.teacher.user',
                'surah',
            ])
            ->whereIn('hafalan_records.student_id', $visibleStudentIdsQuery)
            ->when(isset($validated['student_id']), function (Builder $query) use ($validated) {
                $query->where('hafalan_records.student_id', (int) $validated['student_id']);
            })
            ->when(isset($validated['teacher_id']), function (Builder $query) use ($validated) {
                $query->where('hafalan_records.teacher_id', (int) $validated['teacher_id']);
            })
            ->when(isset($validated['surah_id']), function (Builder $query) use ($validated) {
                $query->where('hafalan_record_surahs.surah_id', (int) $validated['surah_id']);
            })
            ->when(isset($validated['status']), function (Builder $query) use ($validated) {
                $query->where('hafalan_record_surahs.status', $validated['status']);
            })
            ->when(isset($validated['submission_type']), function (Builder $query) use ($validated) {
                $query->where('hafalan_record_surahs.submission_type', $validated['submission_type']);
            })
            ->when(isset($validated['from']), function (Builder $query) use ($validated) {
                $query->whereDate('hafalan_records.submitted_at', '>=', $validated['from']);
            })
            ->when(isset($validated['to']), function (Builder $query) use ($validated) {
                $query->whereDate('hafalan_records.submitted_at', '<=', $validated['to']);
            })
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $subQuery) use ($search) {
                    $subQuery->where('hafalan_records.notes', 'like', "%{$search}%")
                        ->orWhereHas('hafalanRecord.student', function (Builder $studentQuery) use ($search) {
                            $studentQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('student_number', 'like', "%{$search}%");
                        })
                        ->orWhereHas('surah', function (Builder $surahQuery) use ($search) {
                            $surahQuery->where('name_latin', 'like', "%{$search}%")
                                ->orWhere('name_ar', 'like', "%{$search}%");
                        })
                        ->orWhereHas('hafalanRecord.teacher.user', function (Builder $teacherUserQuery) use ($search) {
                            $teacherUserQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('hafalan_records.submitted_at')
            ->orderByDesc('hafalan_record_surahs.id')
            ->paginate($perPage)
            ->withQueryString();

        $records->getCollection()->transform(fn (HafalanRecordSurah $record) => $record->applyHeaderOverlay());

        return ApiResponse::success(
            data: [
                'hafalan_records' => HafalanRecordResource::collection($records->getCollection())->resolve($request),
            ],
            message: 'Data setoran hafalan berhasil diambil.',
            meta: [
                'pagination' => $this->paginationMeta($records),
                'filters' => [
                    'student_id' => $validated['student_id'] ?? null,
                    'teacher_id' => $validated['teacher_id'] ?? null,
                    'surah_id' => $validated['surah_id'] ?? null,
                    'status' => $validated['status'] ?? null,
                    'submission_type' => $validated['submission_type'] ?? null,
                    'from' => $validated['from'] ?? null,
                    'to' => $validated['to'] ?? null,
                    'search' => $search,
                ],
            ]
        );
    }

    public function show(
        Request $request,
        StudentApiService $studentApiService,
        string $hafalanRecord
    ): JsonResponse {
        if (! ctype_digit($hafalanRecord)) {
            return ApiResponse::error(
                message: 'Data setoran hafalan tidak ditemukan.',
                status: 404
            );
        }

        $visibleStudentIdsQuery = $studentApiService
            ->visibleStudentQuery($request->user())
            ->select('students.id');

        $record = HafalanRecordSurah::query()
            ->select('hafalan_record_surahs.*')
            ->join('hafalan_records', 'hafalan_records.id', '=', 'hafalan_record_surahs.hafalan_record_id')
            ->whereNull('hafalan_records.deleted_at')
            ->with([
                'hafalanRecord.student.classRoom.program',
                'hafalanRecord.teacher.user',
                'surah',
            ])
            ->whereIn('hafalan_records.student_id', $visibleStudentIdsQuery)
            ->whereKey((int) $hafalanRecord)
            ->first();

        if (! $record) {
            return ApiResponse::error(
                message: 'Data setoran hafalan tidak ditemukan.',
                status: 404
            );
        }

        $record->applyHeaderOverlay();

        return ApiResponse::success(
            data: [
                'hafalan_record' => (new HafalanRecordResource($record))->resolve($request),
            ],
            message: 'Detail setoran hafalan berhasil diambil.'
        );
    }

    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'from' => $paginator->firstItem(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
        ];
    }
}
