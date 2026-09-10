<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HafalanRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'teacher_id',
        'surah_id',
        'ayah_start',
        'ayah_end',
        'submission_type',
        'score',
        'status',
        'notes',
        'submitted_at',
        'baris',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'teacher_id' => 'integer',
            'surah_id' => 'integer',
            'ayah_start' => 'integer',
            'ayah_end' => 'integer',
            'score' => 'decimal:2',
            'baris' => 'decimal:2',
            'submitted_at' => 'date',
        ];
    }

    public function getLinesCountAttribute(): float
    {
        if ($this->baris !== null) {
            return (float) $this->baris;
        }
        if (! $this->surah_id || ! $this->surah) {
            return 0.0;
        }
        try {
            return \App\Http\Controllers\ReportController::calculateLines(
                $this->surah->number,
                $this->ayah_start ?? 1,
                $this->ayah_end ?? 1,
                $this->surah->total_ayah
            );
        } catch (\Throwable) {
            return 0.0;
        }
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

    public function getSubmissionTypeLabelAttribute(): string
    {
        return match ($this->submission_type) {
            'new' => 'Baru',
            'continuation' => 'Lanjutan',
            'revision' => 'Perbaikan',
            default => '-',
        };
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

    public function getScoreLetterAttribute(): string
    {
        if ($this->score === null) {
            return '-';
        }

        $val = (float) $this->score;
        if ($val >= 90) return 'A';
        if ($val >= 80) return 'B';
        if ($val >= 70) return 'C';
        if ($val >= 60) return 'D';
        return 'E';
    }
}
