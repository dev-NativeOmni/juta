<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UmmiRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'teacher_id',
        'tatap_muka',
        'tanggal',
        'ummi_jilid',
        'ummi_halaman',
        'materi',
        'nilai',
        'disimak_guru',
        'disimak_ortu',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'teacher_id' => 'integer',
            'tatap_muka' => 'integer',
            'tanggal' => 'date',
        ];
    }

    public function getLinesCountAttribute(): float
    {
        return (float) $this->surahs->sum(fn (UmmiRecordSurah $surah) => $surah->lines_count);
    }

    public function getSurahsLabelAttribute(): string
    {
        return $this->surahs
            ->map(function (UmmiRecordSurah $surah) {
                $label = $surah->surah?->name_latin ?? '-';

                return $surah->hafalan_ayah ? "{$label} ({$surah->hafalan_ayah})" : $label;
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
        return $this->hasMany(UmmiRecordSurah::class)->orderBy('sort_order')->orderBy('id');
    }
}
