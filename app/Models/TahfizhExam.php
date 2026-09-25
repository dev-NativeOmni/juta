<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TahfizhExam extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'teacher_id',
        'juz',
        'surah_id',
        'ayah_start',
        'ayah_end',
        'total_score',
        'notes',
        'exam_date',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'teacher_id' => 'integer',
            'juz' => 'integer',
            'surah_id' => 'integer',
            'ayah_start' => 'integer',
            'ayah_end' => 'integer',
            'total_score' => 'decimal:2',
            'exam_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_id');
    }

    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class);
    }

    public function getExamRangeAttribute(): string
    {
        if ($this->juz) {
            return 'Juz '.$this->juz;
        }

        if ($this->surah) {
            $range = 'QS. '.$this->surah->name_latin;
            if ($this->ayah_start && $this->ayah_end) {
                $range .= ' (Ayat '.$this->ayah_start.'-'.$this->ayah_end.')';
            }

            return $range;
        }

        return '-';
    }
}
