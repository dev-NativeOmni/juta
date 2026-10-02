<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdabMentorAssessment extends Model
{
    use BelongsToInstitution;

    protected $fillable = [
        'institution_id',
        'student_id',
        'mentor_id',
        'year',
        'month',
        'period_label',
        'mentor_score',
        'notes',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    /**
     * Get the period label (e.g. "Juli 2026").
     */
    public function getPeriodAttribute(): string
    {
        if ($this->period_label) {
            return $this->period_label;
        }
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return ($months[$this->month] ?? '-').' '.$this->year;
    }
}
