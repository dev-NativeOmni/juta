<?php

namespace App\Models;

use App\Http\Controllers\ReportController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HafalanRecordSurah extends Model
{
    protected $fillable = [
        'hafalan_record_id',
        'surah_id',
        'ayah_start',
        'ayah_end',
        'submission_type',
        'score',
        'status',
        'baris',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'hafalan_record_id' => 'integer',
            'surah_id' => 'integer',
            'ayah_start' => 'integer',
            'ayah_end' => 'integer',
            'score' => 'decimal:2',
            'baris' => 'decimal:2',
            'sort_order' => 'integer',
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
            return ReportController::calculateLines(
                $this->surah->number,
                $this->ayah_start ?? 1,
                $this->ayah_end ?? 1,
                $this->surah->total_ayah
            );
        } catch (\Throwable) {
            return 0.0;
        }
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
        if ($val >= 90) {
            return 'A';
        }
        if ($val >= 80) {
            return 'B';
        }
        if ($val >= 70) {
            return 'C';
        }
        if ($val >= 60) {
            return 'D';
        }

        return 'E';
    }

    public function hafalanRecord(): BelongsTo
    {
        return $this->belongsTo(HafalanRecord::class);
    }

    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class);
    }

    /**
     * Copies the header's submitted_at/notes/teacher_id/student_id onto this row
     * as overlay properties, plus any already-loaded teacher/student relations,
     * so code that historically treated "one hafalan_records row" as "one graded
     * surah submission" can keep reading those fields directly. Requires the
     * `hafalanRecord` relation to already be loaded.
     */
    public function applyHeaderOverlay(): static
    {
        if ($header = $this->hafalanRecord) {
            $this->submitted_at = $header->submitted_at;
            $this->notes = $header->notes;
            $this->teacher_id = $header->teacher_id;
            $this->student_id = $header->student_id;

            foreach (['teacher', 'student'] as $relation) {
                if ($header->relationLoaded($relation)) {
                    $this->setRelation($relation, $header->getRelation($relation));
                }
            }
        }

        return $this;
    }
}
