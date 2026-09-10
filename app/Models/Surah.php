<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Surah extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'name_ar',
        'name_latin',
        'total_ayah',
        'juz_start',
        'juz_end',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'total_ayah' => 'integer',
            'juz_start' => 'integer',
            'juz_end' => 'integer',
        ];
    }

    public static function getAllCached()
    {
        return \Illuminate\Support\Facades\Cache::remember('all_surahs_cached', 86400, function () {
            return static::orderBy('number')->get();
        });
    }

    protected static function booted(): void
    {
        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget('all_surahs_cached'));
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::forget('all_surahs_cached'));
    }

    public function ayahs(): HasMany
    {
        return $this->hasMany(Ayah::class);
    }

    public function hafalanRecords(): HasMany
    {
        return $this->hasMany(HafalanRecord::class);
    }

    public function murajaahRecords(): HasMany
    {
        return $this->hasMany(MurajaahRecord::class);
    }

    public function hafalanTargets(): HasMany
    {
        return $this->hasMany(HafalanTarget::class);
    }
}
