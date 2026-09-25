<?php

namespace App\Models;

use App\Http\Controllers\ReportController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UmmiRecordSurah extends Model
{
    protected $fillable = [
        'ummi_record_id',
        'surah_id',
        'hafalan_ayah',
        'baris',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'ummi_record_id' => 'integer',
            'surah_id' => 'integer',
            'baris' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function getLinesCountAttribute(): float
    {
        if ($this->baris !== null) {
            return (float) $this->baris;
        }
        if (! $this->surah || ! $this->hafalan_ayah) {
            return 0.0;
        }
        $clean = str_replace(' ', '', $this->hafalan_ayah);
        if (str_contains($clean, '-')) {
            $parts = explode('-', $clean);
            $start = (int) $parts[0];
            $end = (int) $parts[1];
        } else {
            $start = (int) $clean;
            $end = (int) $clean;
        }
        if ($start <= 0 || $end <= 0 || $start > $end) {
            return 0.0;
        }

        return ReportController::calculateLines(
            $this->surah->number,
            $start,
            $end,
            $this->surah->total_ayah
        );
    }

    public function ummiRecord(): BelongsTo
    {
        return $this->belongsTo(UmmiRecord::class);
    }

    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class);
    }
}
