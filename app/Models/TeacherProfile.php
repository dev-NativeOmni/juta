<?php

namespace App\Models;

use App\Traits\BelongsToInstitution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherProfile extends Model
{
    use BelongsToInstitution, HasFactory;

    protected $fillable = [
        'institution_id',
        'user_id',
        'employee_number',
        'phone',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'teacher_id');
    }

    public function hafalanRecords(): HasMany
    {
        return $this->hasMany(HafalanRecord::class, 'teacher_id');
    }

    public function murajaahRecords(): HasMany
    {
        return $this->hasMany(MurajaahRecord::class, 'teacher_id');
    }

    public function hafalanTargets(): HasMany
    {
        return $this->hasMany(HafalanTarget::class, 'teacher_id');
    }
}
