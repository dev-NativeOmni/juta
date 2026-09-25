<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class HafalanRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'teacher_id',
        'notes',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'teacher_id' => 'integer',
            'submitted_at' => 'date',
        ];
    }

    public function getLinesCountAttribute(): float
    {
        return (float) $this->surahs->sum(fn (HafalanRecordSurah $surah) => $surah->lines_count);
    }

    public function getSurahsLabelAttribute(): string
    {
        return $this->surahs
            ->map(function (HafalanRecordSurah $surah) {
                $label = $surah->surah?->name_latin ?? '-';

                return "{$label} ({$surah->ayah_start}-{$surah->ayah_end})";
            })
            ->implode(', ');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_id');
    }

    public function surahs(): HasMany
    {
        return $this->hasMany(HafalanRecordSurah::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Flattens a collection of header rows (with `surahs` eager-loaded) into one
     * pseudo-row per surah entry, for call sites that historically treated
     * "one hafalan_records row" as "one graded surah submission". Each returned
     * HafalanRecordSurah carries the header's submitted_at/notes/teacher_id/
     * student_id as overlay properties (so it can be filtered/sorted/displayed
     * the same way the old flat rows were), plus the header itself and any of
     * its already-loaded relations (teacher, student, ...) reattached, so code
     * that still needs the full session context can reach it via ->hafalanRecord.
     */
    public static function flattenSurahs(Collection $headers): Collection
    {
        return $headers->flatMap(function (self $header) {
            return $header->surahs->map(function (HafalanRecordSurah $surah) use ($header) {
                $surah->setRelation('hafalanRecord', $header);

                return $surah->applyHeaderOverlay();
            });
        })->values();
    }
}
