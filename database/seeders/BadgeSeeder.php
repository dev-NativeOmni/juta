<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            // ─── Badge Setoran ───
            ['key' => 'first_hafalan',   'title' => 'Setoran Pertama',   'description' => 'Memiliki minimal 1 setoran hafalan.',                             'icon' => 'sparkles',    'type' => 'count_hafalan',    'target_value' => 1,  'target_juz' => null, 'sort_order' => 1],
            ['key' => 'five_hafalan',    'title' => '5 Setoran',          'description' => 'Mencapai minimal 5 kali setoran hafalan.',                         'icon' => 'bolt',        'type' => 'count_hafalan',    'target_value' => 5,  'target_juz' => null, 'sort_order' => 2],
            ['key' => 'ten_passed',      'title' => '10 Hafalan Lulus',   'description' => 'Memiliki minimal 10 setoran hafalan berstatus lulus.',              'icon' => 'check-badge', 'type' => 'passed_hafalan',   'target_value' => 10, 'target_juz' => null, 'sort_order' => 3],

            // ─── Badge Progress Al-Qur'an ───
            ['key' => 'one_percent',     'title' => '1% Al-Qur\'an',      'description' => 'Progress hafalan mencapai minimal 1% dari total ayat Al-Qur\'an.',  'icon' => 'book-open',   'type' => 'percent_quran',    'target_value' => 1,  'target_juz' => null, 'sort_order' => 4],
            ['key' => 'five_percent',    'title' => '5% Al-Qur\'an',      'description' => 'Progress hafalan mencapai minimal 5% dari total ayat Al-Qur\'an.',  'icon' => 'book-open',   'type' => 'percent_quran',    'target_value' => 5,  'target_juz' => null, 'sort_order' => 5],

            // ─── Badge Murajaah ───
            ['key' => 'murajaah_active', 'title' => 'Murajaah Aktif',     'description' => 'Memiliki minimal 5 catatan murajaah yang disetor.',                 'icon' => 'arrow-path',  'type' => 'count_murajaah',   'target_value' => 5,  'target_juz' => null, 'sort_order' => 6],

            // ─── Badge Target ───
            ['key' => 'target_finisher', 'title' => 'Penuntas Target',    'description' => 'Menyelesaikan minimal 3 target hafalan.',                           'icon' => 'trophy',      'type' => 'completed_targets','target_value' => 3,  'target_juz' => null, 'sort_order' => 7],
            ['key' => 'clean_target',    'title' => 'Target Tertib',       'description' => 'Tidak memiliki target hafalan yang terlambat.',                     'icon' => 'shield-check','type' => 'clean_target',     'target_value' => 0,  'target_juz' => null, 'sort_order' => 8],

            // ─── Badge Kualitas ───
            ['key' => 'score_quality',   'title' => 'Kualitas Baik',       'description' => 'Rata-rata nilai hafalan atau murajaah minimal 80.',                 'icon' => 'star',        'type' => 'score_quality',    'target_value' => 80, 'target_juz' => null, 'sort_order' => 9],

            // ─── Badge Khatam Juz (Juz Amma - Juz Tabarak) ───
            ['key' => 'juz_30',          'title' => 'Khatam Juz 30 (Juz Amma)',   'description' => 'Menyelesaikan seluruh hafalan Juz 30 (Juz Amma).',   'icon' => 'academic-cap','type' => 'completed_juz',    'target_value' => 30, 'target_juz' => 30,  'sort_order' => 10],
            ['key' => 'juz_29',          'title' => 'Khatam Juz 29 (Tabarak)',     'description' => 'Menyelesaikan seluruh hafalan Juz 29.',               'icon' => 'academic-cap','type' => 'completed_juz',    'target_value' => 29, 'target_juz' => 29,  'sort_order' => 11],
            ['key' => 'juz_28',          'title' => 'Khatam Juz 28',               'description' => 'Menyelesaikan seluruh hafalan Juz 28.',               'icon' => 'academic-cap','type' => 'completed_juz',    'target_value' => 28, 'target_juz' => 28,  'sort_order' => 12],
            ['key' => 'juz_1',           'title' => 'Khatam Juz 1 (Al-Baqarah)',  'description' => 'Menyelesaikan seluruh hafalan Juz 1.',                'icon' => 'academic-cap','type' => 'completed_juz',    'target_value' => 1,  'target_juz' => 1,   'sort_order' => 13],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(['key' => $badge['key']], $badge);
        }
    }
}
