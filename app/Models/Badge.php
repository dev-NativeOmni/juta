<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    protected $fillable = [
        'key',
        'title',
        'description',
        'icon',
        'type',
        'target_value',
        'target_juz',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'target_value' => 'float',
        'target_juz' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public static function active()
    {
        return static::where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public static function typeLabels(): array
    {
        return [
            'count_hafalan'    => 'Jumlah Total Setoran Hafalan',
            'passed_hafalan'   => 'Jumlah Setoran Lulus',
            'percent_quran'    => 'Persentase Hafalan Al-Qur\'an (%)',
            'count_murajaah'   => 'Jumlah Murajaah',
            'completed_targets'=> 'Jumlah Target Selesai',
            'clean_target'     => 'Tidak Ada Target Terlambat',
            'score_quality'    => 'Rata-Rata Nilai ≥ Target',
            'completed_juz'    => 'Khatam Juz Tertentu',
        ];
    }
}
