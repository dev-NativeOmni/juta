<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class ClassRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'pendamping_adab_id',
        'name',
        'level',
        'tahfizh_days',
    ];

    protected $casts = [
        'tahfizh_days' => 'array',
    ];

    public function getTahfizhDaysAttribute($value)
    {
        if (is_null($value)) {
            return [1, 2, 3, 4, 5];
        }

        return json_decode($value, true) ?: [1, 2, 3, 4, 5];
    }

    public static function getAllCached()
    {
        return Cache::remember('all_class_rooms_cached', 3600, function () {
            return static::with('program')->orderBy('name')->get();
        });
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('all_class_rooms_cached'));
        static::deleted(fn () => Cache::forget('all_class_rooms_cached'));
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function pendampingAdab(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pendamping_adab_id');
    }

    public function pendampingAdabList(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_room_pendamping_adab', 'class_room_id', 'user_id')->withTimestamps();
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}
