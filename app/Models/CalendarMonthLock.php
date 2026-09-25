<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kunci kalender satu bulan untuk satu cakupan (SchoolCalendar::SCOPE_*).
 */
class CalendarMonthLock extends Model
{
    protected $fillable = ['year', 'month', 'scope', 'locked_by'];

    /**
     * Nama tampilan di Audit Log, mis. "Tahfizh 07/2026".
     */
    public function getNameAttribute(): string
    {
        return ucfirst((string) $this->scope).' '.sprintf('%02d/%04d', $this->month, $this->year);
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }
}
