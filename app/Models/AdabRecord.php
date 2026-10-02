<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdabRecord extends Model
{
    use BelongsToInstitution, HasFactory;

    protected $fillable = [
        'institution_id',
        'student_id',
        'evaluator_id',
        'mentor_id',
        'assessment_date',
        // Legacy boolean columns (q1–q20) kept for compatibility
        'q1',  'q2',  'q3',  'q4',  'q5',
        'q6',  'q7',  'q8',  'q9',  'q10',
        'q11', 'q12', 'q13', 'q14', 'q15',
        'q16', 'q17', 'q18', 'q19', 'q20',
        // New flexible fields
        'answers',       // JSON: {"cat_0":[true,false,...], "cat_1":[...], ...}
        'student_score', // 0–100 (pure self-assessment)
        'total_score',   // 0–100 (combined: student + mentor)
        'mentor_score',  // kept for backward compat (daily mentor score, deprecated)
        'notes',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'answers' => 'array',
        'student_score' => 'float',
        'total_score' => 'float',
        'mentor_score' => 'float',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    /**
     * Calculate student_score from answers JSON.
     * Returns 0–100 percentage of "true" answers.
     */
    public function calculateStudentScore(): float
    {
        if (empty($this->answers)) {
            return 0;
        }
        $all = [];
        foreach ($this->answers as $catAnswers) {
            foreach ($catAnswers as $answer) {
                $all[] = $answer ? 1 : 0;
            }
        }
        if (empty($all)) {
            return 0;
        }

        return round((array_sum($all) / count($all)) * 100, 2);
    }
}
