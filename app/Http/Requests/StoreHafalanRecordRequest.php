<?php

namespace App\Http\Requests;

use App\Models\Student;
use App\Models\Surah;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHafalanRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin', 'teacher']) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->user()?->teacherProfile?->id) {
            $this->merge([
                'teacher_id' => $this->user()->teacherProfile->id,
            ]);
        } else {
            $student = Student::find($this->input('student_id'));
            if ($student) {
                $this->merge([
                    'teacher_id' => $student->teacher_id,
                ]);
            }
        }

        if (! $this->has('surah_ids')) {
            $this->merge(['__is_single' => true]);
            $surahStartId = $this->input('surah_id');
            $surahEndId = $this->input('surah_end_id') ?: $surahStartId;
            $ayahStart = $this->input('ayah_start');
            $ayahEnd = $this->input('ayah_end');

            if ($surahStartId) {
                $surahStart = Surah::find((int) $surahStartId);
                $surahEnd = Surah::find((int) $surahEndId);

                if ($surahStart && $surahEnd && $surahStart->number < $surahEnd->number) {
                    $surahs = Surah::whereBetween('number', [$surahStart->number, $surahEnd->number])
                        ->orderBy('number')
                        ->get();

                    $surahIds = [];
                    $ayahStarts = [];
                    $ayahEnds = [];
                    $submissionTypes = [];
                    $scores = [];
                    $statuses = [];

                    foreach ($surahs as $surah) {
                        $surahIds[] = $surah->id;
                        $submissionTypes[] = $this->input('submission_type');
                        $scores[] = $this->input('score');
                        $statuses[] = $this->input('status');

                        if ($surah->id === $surahStart->id) {
                            $ayahStarts[] = $ayahStart;
                            $ayahEnds[] = $surah->total_ayah;
                        } elseif ($surah->id === $surahEnd->id) {
                            $ayahStarts[] = 1;
                            $ayahEnds[] = $ayahEnd;
                        } else {
                            $ayahStarts[] = 1;
                            $ayahEnds[] = $surah->total_ayah;
                        }
                    }

                    $barisList = array_fill(0, count($surahIds), $this->input('baris'));
                    $this->merge([
                        'surah_ids' => $surahIds,
                        'ayah_starts' => $ayahStarts,
                        'ayah_ends' => $ayahEnds,
                        'submission_types' => $submissionTypes,
                        'scores' => $scores,
                        'statuses' => $statuses,
                        'baris' => $barisList,
                    ]);
                } else {
                    $this->merge([
                        'surah_ids' => [$surahStartId],
                        'ayah_starts' => [$ayahStart],
                        'ayah_ends' => [$ayahEnd],
                        'submission_types' => [$this->input('submission_type')],
                        'scores' => [$this->input('score')],
                        'statuses' => [$this->input('status')],
                        'baris' => [$this->input('baris')],
                    ]);
                }
            }
        }
    }

    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->whereNull('deleted_at'),
            ],
            'teacher_id' => [
                Rule::requiredIf(! $this->user()?->hasRole('teacher')),
                'nullable',
                'integer',
                Rule::exists('teacher_profiles', 'id'),
            ],
            'surah_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'surah_ids.*' => [
                'required',
                'integer',
                Rule::exists('surahs', 'id'),
            ],
            'ayah_starts' => [
                'required',
                'array',
                'min:1',
            ],
            'ayah_starts.*' => [
                'required',
                'integer',
                'min:1',
            ],
            'ayah_ends' => [
                'required',
                'array',
                'min:1',
            ],
            'ayah_ends.*' => [
                'required',
                'integer',
                'min:1',
            ],
            'submission_types' => [
                'required',
                'array',
                'min:1',
            ],
            'submission_types.*' => [
                'required',
                Rule::in(['new', 'continuation', 'revision']),
            ],
            'scores' => [
                'nullable',
                'array',
            ],
            'scores.*' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'statuses' => [
                'required',
                'array',
                'min:1',
            ],
            'statuses.*' => [
                'required',
                Rule::in(['passed', 'repeat', 'needs_improvement']),
            ],
            'baris' => [
                'nullable',
                'array',
            ],
            'baris.*' => [
                'nullable',
                'numeric',
                'min:0',
                'max:500',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'submitted_at' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $isSingle = (bool) $this->input('__is_single');

            if ($isSingle) {
                $surahStartId = (int) $this->input('surah_id');
                $surahEndId = (int) ($this->input('surah_end_id') ?: $surahStartId);
                $surahStart = Surah::find($surahStartId);
                $surahEnd = Surah::find($surahEndId);

                if ($surahStart && $surahEnd && $surahEnd->number < $surahStart->number) {
                    $validator->errors()->add(
                        'surah_end_id',
                        'Surah akhir tidak boleh mendahului surah mulai.'
                    );
                }
            }

            $surahIds = $this->input('surah_ids') ?: [];
            $ayahStarts = $this->input('ayah_starts') ?: [];
            $ayahEnds = $this->input('ayah_ends') ?: [];

            foreach ($surahIds as $idx => $surahId) {
                $surahStart = Surah::find($surahId);
                if ($surahStart) {
                    $ayahStart = $ayahStarts[$idx] ?? null;
                    $ayahEnd = $ayahEnds[$idx] ?? null;

                    if ($ayahStart !== null && $ayahEnd !== null) {
                        if ((int) $ayahEnd < (int) $ayahStart) {
                            $validator->errors()->add(
                                "ayah_ends.{$idx}",
                                'Ayat akhir harus lebih besar atau sama dengan ayat mulai.'
                            );
                        }

                        if ((int) $ayahEnd > $surahStart->total_ayah) {
                            $validator->errors()->add(
                                "ayah_ends.{$idx}",
                                'Ayat akhir tidak boleh melebihi jumlah ayat surah '.$surahStart->name_latin.' ('.$surahStart->total_ayah.' ayat).'
                            );
                        }
                    }
                }
            }

            $student = Student::find($this->input('student_id'));

            if ($student && $student->status !== 'active') {
                $validator->errors()->add(
                    'student_id',
                    'Murid nonaktif tidak bisa menerima input setoran hafalan.'
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
                        'Guru hanya boleh input setoran untuk murid bimbingannya.'
                    );
                }
            }

            if ($isSingle) {
                $errors = $validator->errors();

                if ($errors->has('surah_ids.0')) {
                    $errors->add('surah_id', $errors->first('surah_ids.0'));
                }
                if ($errors->has('ayah_starts.0')) {
                    $errors->add('ayah_start', $errors->first('ayah_starts.0'));
                }
                if ($errors->has('ayah_ends.0')) {
                    $errors->add('ayah_end', $errors->first('ayah_ends.0'));
                }
                if ($errors->has('submission_types.0')) {
                    $errors->add('submission_type', $errors->first('submission_types.0'));
                }
                if ($errors->has('scores.0')) {
                    $errors->add('score', $errors->first('scores.0'));
                }
                if ($errors->has('statuses.0')) {
                    $errors->add('status', $errors->first('statuses.0'));
                }

                if ($errors->has('surah_ids')) {
                    $errors->add('surah_id', 'Surah wajib diisi.');
                }
                if ($errors->has('ayah_starts')) {
                    $errors->add('ayah_start', 'Ayat mulai wajib diisi.');
                }
                if ($errors->has('ayah_ends')) {
                    $errors->add('ayah_end', 'Ayat akhir wajib diisi.');
                }
                if ($errors->has('submission_types')) {
                    $errors->add('submission_type', 'Jenis setoran wajib diisi.');
                }
                if ($errors->has('statuses')) {
                    $errors->add('status', 'Status setoran wajib diisi.');
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'student_id' => 'murid',
            'surah_ids' => 'surah',
            'surah_ids.*' => 'surah',
            'ayah_starts.*' => 'ayat mulai',
            'ayah_ends.*' => 'ayat akhir',
            'submission_types.*' => 'jenis setoran',
            'scores.*' => 'nilai',
            'statuses.*' => 'status setoran',
            'notes' => 'catatan',
            'submitted_at' => 'tanggal setoran',
        ];
    }
}
