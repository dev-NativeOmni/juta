<?php

namespace App\Http\Requests;

use App\Models\Student;
use App\Models\Surah;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHafalanTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin', 'teacher']) ?? false;
    }

    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->whereNull('deleted_at'),
            ],
            'surah_id' => [
                'required',
                'integer',
                Rule::exists('surahs', 'id'),
            ],
            'ayah' => [
                'required',
                'integer',
                'min:1',
            ],
            'target_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'status' => [
                'required',
                Rule::in(['active', 'completed', 'missed', 'cancelled']),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $surah = Surah::find($this->input('surah_id'));

            if ($surah && (int) $this->input('ayah') > $surah->total_ayah) {
                $validator->errors()->add(
                    'ayah',
                    'Ayat tidak boleh melebihi jumlah ayat surah '.$surah->name_latin.' ('.$surah->total_ayah.' ayat).'
                );
            }

            $student = Student::find($this->input('student_id'));

            if ($student && $student->status !== 'active') {
                $validator->errors()->add(
                    'student_id',
                    'Target hanya bisa dibuat untuk murid aktif.'
                );
            }

            if ($student && ! $student->teacher_id) {
                $fallbackTeacherId = $this->user()?->teacherProfile?->id ?? TeacherProfile::query()->value('id');
                if ($fallbackTeacherId) {
                    $student->update(['teacher_id' => $fallbackTeacherId]);
                    $this->merge(['teacher_id' => $fallbackTeacherId]);
                } else {
                    $validator->errors()->add(
                        'student_id',
                        'Murid ini belum memiliki guru pembimbing.'
                    );
                }
            }

            if ($student && $this->user()?->hasRole('teacher')) {
                $teacherId = $this->user()?->teacherProfile?->id;

                if (! $teacherId || ((int) $student->teacher_id !== (int) $teacherId)) {
                    $validator->errors()->add(
                        'student_id',
                        'Guru hanya boleh membuat target untuk murid bimbingannya.'
                    );
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'student_id' => 'murid',
            'surah_id' => 'surah',
            'ayah' => 'ayat (sampai)',
            'target_date' => 'tanggal target',
            'status' => 'status',
            'notes' => 'catatan',
        ];
    }
}
