<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin')
            || $this->user()?->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'program_id' => [
                'required',
                'integer',
                Rule::exists('programs', 'id'),
            ],
            'pendamping_adab_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
            ],
            'pendamping_adab_ids' => [
                'nullable',
                'array',
            ],
            'pendamping_adab_ids.*' => [
                'integer',
                Rule::exists('users', 'id'),
            ],
            'wali_kelas_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
                Rule::unique('class_rooms', 'wali_kelas_user_id')->ignore($this->route('classRoom')),
            ],
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'level' => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'program_id' => 'program',
            'pendamping_adab_id' => 'pendamping adab',
            'wali_kelas_user_id' => 'wali kelas',
            'name' => 'nama kelas',
            'level' => 'level',
        ];
    }
}
