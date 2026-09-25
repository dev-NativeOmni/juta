<?php

namespace App\Models;

use App\Services\SchoolCalendar;
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
        'wali_kelas_user_id',
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
        // Jadwal default berubah: pekan yang sudah lewat tetap memakai jadwal lamanya.
        static::updating(function (ClassRoom $classRoom) {
            if ($classRoom->isDirty('tahfizh_days')) {
                $previous = json_decode((string) $classRoom->getRawOriginal('tahfizh_days'), true) ?: [1, 2, 3, 4, 5];
                app(SchoolCalendar::class)->snapshotPastWeeks($classRoom, $previous);
            }
        });
        static::saved(fn () => Cache::forget('all_class_rooms_cached'));
        static::deleted(fn () => Cache::forget('all_class_rooms_cached'));
    }

    /**
     * Kelas 10 (metode UMMI): target hafalan dibuat guru, tidak dihitung otomatis.
     */
    public function isGradeTen(): bool
    {
        $name = (string) $this->name;
        $level = (string) $this->level;

        return (bool) (
            (preg_match('/\bX\b/i', $name) && ! preg_match('/\b(XI|XII)\b/i', $name))
            || preg_match('/\b10\b/i', $name)
            || preg_match('/^X[-_\s]?E/i', $name)
            || preg_match('/kelas\s*(X|10)/i', $name)
            || (preg_match('/\bX\b/i', $level) && ! preg_match('/\b(XI|XII)\b/i', $level))
            || preg_match('/\b10\b/i', $level)
        ) && ! preg_match('/\b(XI|XII|11|12)\b/i', $name);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wali_kelas_user_id');
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
