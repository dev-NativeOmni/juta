<?php

namespace App\Http\Requests;

use App\Support\HafalanOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin')
            || $this->user()?->hasRole('admin');
    }

    public function rules(): array
    {
        $studentId = $this->route('student')?->id;

        return [
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereIn('role_id', function ($subQuery) {
                        $subQuery->select('id')
                            ->from('roles')
                            ->where('name', 'student');
                    });
                }),
                Rule::unique('students', 'user_id')->ignore($studentId),
            ],
            'class_room_id' => [
                'required',
                'integer',
                Rule::exists('class_rooms', 'id'),
            ],
            'teacher_id' => [
                'required',
                'integer',
                Rule::exists('teacher_profiles', 'id'),
            ],
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'student_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('students', 'student_number')->ignore($studentId),
            ],
            'gender' => [
                'nullable',
                Rule::in(['male', 'female']),
            ],
            'birth_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'status' => [
                'required',
                Rule::in(['active', 'inactive', 'graduated']),
            ],
            'tahfizh_level' => [
                'nullable',
                Rule::in(['tahsin', 'reguler', 'akselerasi', 'ummi']),
            ],
            'hafalan_direction' => [
                'nullable',
                Rule::in(array_keys(HafalanOrder::directionOptions())),
            ],
            'parent_ids' => [
                'nullable',
                'array',
            ],
            'parent_ids.*' => [
                'integer',
                Rule::exists('parent_profiles', 'id'),
            ],
            'parent_relations' => [
                'nullable',
                'array',
            ],
            'parent_relations.*' => [
                'nullable',
                'string',
                'max:50',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'akun murid',
            'class_room_id' => 'kelas',
            'teacher_id' => 'guru pembimbing',
            'name' => 'nama murid',
            'student_number' => 'nomor murid',
            'gender' => 'gender',
            'birth_date' => 'tanggal lahir',
            'status' => 'status',
            'parent_ids' => 'orangtua/wali',
            'parent_relations' => 'relasi orangtua/wali',
        ];
    }
}
