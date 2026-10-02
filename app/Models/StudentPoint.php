<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPoint extends Model
{
    use BelongsToInstitution, HasFactory;

    protected $fillable = [
        'institution_id',
        'student_id',
        'type',
        'points',
        'category',
        'title',
        'description',
        'sanction',
        'achievement_type',
        'achievement_level',
        'location',
        'date',
        'logged_by',
    ];

    protected $casts = [
        'date' => 'date',
        'points' => 'integer',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }

    public static function isViolationType(string $type): bool
    {
        return in_array($type, ['violation', 'lateness', 'attribute'], true);
    }

    public static function getTypeLabel(string $type): string
    {
        return match ($type) {
            'violation' => 'Pelanggaran Tata Tertib',
            'lateness' => 'Pelanggaran Keterlambatan',
            'attribute' => 'Pelanggaran Atribut/Seragam',
            'reward' => 'Prestasi / Penghargaan',
            default => 'Catatan Kedisiplinan',
        };
    }

    public function scopeViolations($query)
    {
        return $query->whereIn('type', ['violation', 'lateness', 'attribute']);
    }
}
