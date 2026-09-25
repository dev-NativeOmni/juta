<?php

namespace App\Models;

use App\Services\SchoolCalendar;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu tanggal di kalender sekolah. Lihat App\Services\SchoolCalendar untuk aturannya.
 */
class CalendarDay extends Model
{
    protected $fillable = [
        'date',
        'class_room_id',
        'tahfizh_off',
        'adab_off',
        'note',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'tahfizh_off' => 'boolean',
        'adab_off' => 'boolean',
    ];

    /**
     * Simpan sebagai 'Y-m-d' murni supaya pencarian/unique konsisten di semua driver DB.
     */
    public function setDateAttribute($value): void
    {
        $this->attributes['date'] = Carbon::parse($value)->toDateString();
    }

    protected static function booted(): void
    {
        $flush = fn () => app(SchoolCalendar::class)->flush();
        static::saved($flush);
        static::deleted($flush);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }
}
