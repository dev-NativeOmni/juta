<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UmmiRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'teacher_id',
        'tatap_muka',
        'tanggal',
        'hafalan_surah_id',
        'hafalan_ayah',
        'ummi_jilid',
        'ummi_halaman',
        'materi',
        'nilai',
        'disimak_guru',
        'disimak_ortu',
        'keterangan',
        'baris',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'teacher_id' => 'integer',
            'tatap_muka' => 'integer',
            'tanggal' => 'date',
            'hafalan_surah_id' => 'integer',
            'baris' => 'decimal:2',
        ];
    }

    public function getLinesCountAttribute(): float
    {
        if ($this->baris !== null) {
            return (float) $this->baris;
        }
        if (!$this->surah || !$this->hafalan_ayah) {
            return 0.0;
        }
        $clean = str_replace(' ', '', $this->hafalan_ayah);
        if (str_contains($clean, '-')) {
            $parts = explode('-', $clean);
            $start = (int) $parts[0];
            $end = (int) $parts[1];
        } else {
            $start = (int) $clean;
            $end = (int) $clean;
        }
        if ($start <= 0 || $end <= 0 || $start > $end) {
            return 0.0;
        }
        return \App\Http\Controllers\ReportController::calculateLines(
            $this->surah->number,
            $start,
            $end,
            $this->surah->total_ayah
        );
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
        return $this->belongsTo(Surah::class, 'hafalan_surah_id');
    }
}
