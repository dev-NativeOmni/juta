<?php

namespace App\Services\Api\V1;

use App\Models\HafalanRecord;
use App\Models\HafalanRecordSurah;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\Surah;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class StudentApiService
{
    public function visibleStudentQuery(?User $user): Builder
    {
        $query = Student::query();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($this->userHasAnyRole($user, ['super_admin', 'admin'])) {
            return $query;
        }

        if ($this->userHasAnyRole($user, ['teacher'])) {
            $teacherId = $user->teacherProfile?->id
                ?? TeacherProfile::query()->where('user_id', $user->id)->value('id');

            if (! $teacherId) {
                return $query->whereRaw('1 = 0');
            }

            if (Schema::hasColumn('students', 'teacher_id')) {
                return $query->where('teacher_id', $teacherId);
            }

            return $query->whereRaw('1 = 0');
        }

        if ($this->userHasAnyRole($user, ['parent'])) {
            $parentId = $user->parentProfile?->id
                ?? ParentProfile::query()->where('user_id', $user->id)->value('id');

            if (! $parentId) {
                return $query->whereRaw('1 = 0');
            }

            if (Schema::hasTable('parent_student')) {
                return $query->whereIn('id', function ($subQuery) use ($parentId) {
                    $subQuery->select('student_id')
                        ->from('parent_student')
                        ->where('parent_id', $parentId);
                });
            }

            return $query->whereRaw('1 = 0');
        }

        if ($this->userHasAnyRole($user, ['student'])) {
            if (! Schema::hasColumn('students', 'user_id')) {
                return $query->whereRaw('1 = 0');
            }

            return $query->where('user_id', $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    public function calculateProgress(Student $student): array
    {
        $student->loadMissing([
            'user',
            'classRoom.program',
            'teacher.user',
            'parents.user',
        ]);

        $totalQuranAyahs = $this->totalQuranAyahs();
        $memorizedAyahs = $this->memorizedAyahCount($student);

        $hafalanQuery = HafalanRecord::query()
            ->where('student_id', $student->id);

        $murajaahQuery = MurajaahRecord::query()
            ->where('student_id', $student->id);

        $targetQuery = HafalanTarget::query()
            ->where('student_id', $student->id);

        $activeTargetStatuses = [
            'active',
            'planned',
            'in_progress',
        ];

        $latestHafalanHeader = (clone $hafalanQuery)
            ->with([
                'surahs.surah',
                'teacher.user',
            ])
            ->latest('submitted_at')
            ->latest()
            ->first();
        $latestHafalan = $latestHafalanHeader
            ? HafalanRecord::flattenSurahs(collect([$latestHafalanHeader]))->last()
            : null;

        $latestMurajaah = (clone $murajaahQuery)
            ->with([
                'surah',
                'teacher.user',
            ])
            ->latest('reviewed_at')
            ->latest()
            ->first();

        $latestTarget = (clone $targetQuery)
            ->with([
                'surah',
                'teacher.user',
            ])
            ->orderByRaw("
                CASE
                    WHEN status IN ('active', 'planned', 'in_progress') THEN 0
                    WHEN status = 'missed' THEN 1
                    WHEN status = 'completed' THEN 2
                    ELSE 3
                END
            ")
            ->orderBy('target_date')
            ->latest()
            ->first();

        return [
            'student' => $student,

            'quran' => [
                'total_ayahs' => $totalQuranAyahs,
                'memorized_ayahs' => $memorizedAyahs,
                'progress_percentage' => $totalQuranAyahs > 0
                    ? round(($memorizedAyahs / $totalQuranAyahs) * 100, 2)
                    : 0,
            ],

            'hafalan' => [
                'total_records' => HafalanRecordSurah::whereHas('hafalanRecord', fn ($q) => $q->where('student_id', $student->id))->count(),
                'passed_records' => HafalanRecordSurah::whereHas('hafalanRecord', fn ($q) => $q->where('student_id', $student->id))
                    ->where('status', 'passed')
                    ->count(),
                'repeat_records' => HafalanRecordSurah::whereHas('hafalanRecord', fn ($q) => $q->where('student_id', $student->id))
                    ->whereIn('status', ['repeat', 'needs_improvement'])
                    ->count(),
                'average_score' => round((float) HafalanRecordSurah::whereHas('hafalanRecord', fn ($q) => $q->where('student_id', $student->id))
                    ->whereNotNull('score')
                    ->avg('score'), 2),
                'latest' => $latestHafalan,
            ],

            'murajaah' => [
                'total_records' => (clone $murajaahQuery)->count(),
                'passed_records' => (clone $murajaahQuery)->where('status', 'passed')->count(),
                'repeat_records' => (clone $murajaahQuery)
                    ->whereIn('status', ['repeat', 'needs_improvement'])
                    ->count(),
                'average_score' => round((float) (clone $murajaahQuery)
                    ->whereNotNull('overall_score')
                    ->avg('overall_score'), 2),
                'latest' => $latestMurajaah,
            ],

            'targets' => [
                'total_targets' => (clone $targetQuery)->count(),
                'active_targets' => (clone $targetQuery)
                    ->whereIn('status', $activeTargetStatuses)
                    ->count(),
                'completed_targets' => (clone $targetQuery)
                    ->where('status', 'completed')
                    ->count(),
                'missed_targets' => (clone $targetQuery)
                    ->where('status', 'missed')
                    ->count(),
                'latest' => $latestTarget,
            ],
        ];
    }

    private function memorizedAyahCount(Student $student): int
    {
        $records = HafalanRecordSurah::query()
            ->whereHas('hafalanRecord', fn ($q) => $q->where('student_id', $student->id))
            ->where('status', 'passed')
            ->get([
                'surah_id',
                'ayah_start',
                'ayah_end',
            ])
            ->groupBy('surah_id');

        return (int) $records->sum(function (Collection $surahRecords) {
            return $this->mergeAndCountRanges($surahRecords);
        });
    }

    private function mergeAndCountRanges(Collection $records): int
    {
        $ranges = $records
            ->map(function ($record) {
                return [
                    'start' => (int) $record->ayah_start,
                    'end' => (int) $record->ayah_end,
                ];
            })
            ->filter(fn (array $range) => $range['start'] > 0 && $range['end'] >= $range['start'])
            ->sortBy('start')
            ->values();

        if ($ranges->isEmpty()) {
            return 0;
        }

        $total = 0;
        $currentStart = null;
        $currentEnd = null;

        foreach ($ranges as $range) {
            $start = $range['start'];
            $end = $range['end'];

            if ($currentStart === null) {
                $currentStart = $start;
                $currentEnd = $end;

                continue;
            }

            if ($start <= $currentEnd + 1) {
                $currentEnd = max($currentEnd, $end);

                continue;
            }

            $total += $currentEnd - $currentStart + 1;

            $currentStart = $start;
            $currentEnd = $end;
        }

        if ($currentStart !== null && $currentEnd !== null) {
            $total += $currentEnd - $currentStart + 1;
        }

        return $total;
    }

    private function totalQuranAyahs(): int
    {
        $total = (int) Surah::query()->sum('total_ayah');

        return $total > 0 ? $total : 6236;
    }

    private function userHasAnyRole(User $user, array $roles): bool
    {
        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole($roles);
        }

        return in_array($user->role?->name, $roles, true);
    }
}
