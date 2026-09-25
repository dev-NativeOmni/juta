<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HafalanTarget extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'teacher_id',
        'ummi_jilid',
        'halaman_peraga',
        'halaman_buku',
        'surah_id',
        'ayah',
        'target_date',
        'status',
        'completed_at',
        'notes',
        'auto_month',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'teacher_id' => 'integer',
            'surah_id' => 'integer',
            'ayah' => 'integer',
            'target_date' => 'date',
            'completed_at' => 'datetime',
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

    public function getIsAutoAttribute(): bool
    {
        return $this->auto_month !== null;
    }

    public function getAyahRangeAttribute(): string
    {
        return $this->ayah ? '1 - '.$this->ayah : '-';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Aktif',
            'completed' => 'Selesai',
            'missed' => 'Terlewat',
            'cancelled' => 'Dibatalkan',
            default => '-',
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'active'
            && $this->target_date !== null
            && $this->target_date->lt(today());
    }
}
