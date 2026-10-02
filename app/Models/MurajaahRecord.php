<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MurajaahRecord extends Model
{
    use BelongsToInstitution, HasFactory, SoftDeletes;

    protected $fillable = [
        'institution_id',
        'student_id',
        'teacher_id',
        'surah_id',
        'ayah_start',
        'ayah_end',
        'fluency_score',
        'tajwid_score',
        'makhraj_score',
        'overall_score',
        'status',
        'notes',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'teacher_id' => 'integer',
            'surah_id' => 'integer',
            'ayah_start' => 'integer',
            'ayah_end' => 'integer',
            'fluency_score' => 'decimal:2',
            'tajwid_score' => 'decimal:2',
            'makhraj_score' => 'decimal:2',
            'overall_score' => 'decimal:2',
            'reviewed_at' => 'date',
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

    public function getAyahRangeAttribute(): string
    {
        return $this->ayah_start.' - '.$this->ayah_end;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'passed' => 'Lulus',
            'repeat' => 'Ulang',
            'needs_improvement' => 'Perlu Perbaikan',
            default => '-',
        };
    }
}
