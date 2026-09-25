<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HafalanTargetResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'student_id' => $this->student_id,
            'teacher_id' => $this->teacher_id,
            'surah_id' => $this->surah_id,

            'ayah' => $this->ayah,
            'ayah_range' => $this->ayah_range,

            'target_date' => optional($this->target_date)->format('Y-m-d'),
            'status' => $this->status,
            'status_label' => $this->status_label ?? $this->statusLabel(),
            'completed_at' => optional($this->completed_at)->toISOString(),
            'notes' => $this->notes,

            'is_overdue' => $this->is_overdue ?? $this->isOverdue(),

            'student' => $this->whenLoaded('student', function () {
                return $this->student ? [
                    'id' => $this->student->id,
                    'user_id' => $this->student->user_id,
                    'class_room_id' => $this->student->class_room_id,
                    'teacher_id' => $this->student->teacher_id,
                    'student_number' => $this->student->student_number,
                    'name' => $this->student->name,
                    'gender' => $this->student->gender,
                    'status' => $this->student->status,

                    'class_room' => $this->student->relationLoaded('classRoom') && $this->student->classRoom ? [
                        'id' => $this->student->classRoom->id,
                        'program_id' => $this->student->classRoom->program_id,
                        'name' => $this->student->classRoom->name,
                        'program' => $this->student->classRoom->relationLoaded('program') && $this->student->classRoom->program ? [
                            'id' => $this->student->classRoom->program->id,
                            'name' => $this->student->classRoom->program->name,
                        ] : null,
                    ] : null,
                ] : null;
            }),

            'teacher' => $this->whenLoaded('teacher', function () {
                return $this->teacher ? [
                    'id' => $this->teacher->id,
                    'employee_number' => $this->teacher->employee_number,
                    'phone' => $this->teacher->phone,
                    'user' => $this->teacher->relationLoaded('user') && $this->teacher->user ? [
                        'id' => $this->teacher->user->id,
                        'name' => $this->teacher->user->name,
                        'email' => $this->teacher->user->email,
                    ] : null,
                ] : null;
            }),

            'surah' => $this->whenLoaded('surah', function () {
                return $this->surah ? [
                    'id' => $this->surah->id,
                    'number' => $this->surah->number,
                    'name_ar' => $this->surah->name_ar,
                    'name_latin' => $this->surah->name_latin,
                    'total_ayah' => $this->surah->total_ayah,
                    'juz_start' => $this->surah->juz_start,
                    'juz_end' => $this->surah->juz_end,
                ] : null;
            }),

            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }

    private function statusLabel(): string
    {
        return match ($this->status) {
            'active' => 'Aktif',
            'planned' => 'Direncanakan',
            'in_progress' => 'Berjalan',
            'completed' => 'Selesai',
            'missed' => 'Terlewat',
            'cancelled' => 'Dibatalkan',
            default => '-',
        };
    }

    private function isOverdue(): bool
    {
        return in_array($this->status, ['active', 'planned', 'in_progress'], true)
            && $this->target_date !== null
            && $this->target_date->lt(today());
    }
}
