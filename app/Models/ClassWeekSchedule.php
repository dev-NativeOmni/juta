<?php

namespace App\Models;

use App\Services\SchoolCalendar;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jadwal hari Tahfizh satu kelas untuk satu pekan. Lihat SchoolCalendar::classMeetingDays().
 */
class ClassWeekSchedule extends Model
{
    protected $fillable = [
        'class_room_id',
        'week_start',
        'days',
        'is_custom',
        'locked_at',
        'locked_by',
        'unlocked',
    ];

    protected $casts = [
        'week_start' => 'date',
        'days' => 'array',
        'is_custom' => 'boolean',
        'locked_at' => 'datetime',
        'unlocked' => 'boolean',
    ];

    /**
     * Simpan sebagai 'Y-m-d' murni supaya pencarian/unique konsisten di semua driver DB.
     */
    public function setWeekStartAttribute($value): void
    {
        $this->attributes['week_start'] = Carbon::parse($value)->toDateString();
    }

    protected static function booted(): void
    {
        $flush = fn () => app(SchoolCalendar::class)->flush();
        static::saved($flush);
        static::deleted($flush);
    }

    /**
     * Nama tampilan di Audit Log, mis. "XI F4 · pekan 12-10-2026".
     */
    public function getNameAttribute(): string
    {
        return ($this->classRoom?->name ?? 'Kelas #'.$this->class_room_id).' · pekan '.$this->week_start?->format('d-m-Y');
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }
}
