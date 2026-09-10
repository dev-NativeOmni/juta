<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\Student;

class StudentMotivationService
{
    public function build(Student $student, array $progress): array
    {
        $progressPercent = (float) ($progress['progress_percent'] ?? 0);
        $totalHafalanRecords = (int) ($progress['total_hafalan_records'] ?? 0);
        $passedHafalanRecords = (int) ($progress['passed_hafalan_records'] ?? 0);
        $repeatHafalanRecords = (int) ($progress['repeat_hafalan_records'] ?? 0);
        $totalMurajaahRecords = (int) ($progress['total_murajaah_records'] ?? 0);
        $completedTargets = (int) ($progress['completed_targets'] ?? 0);
        $activeTargets = (int) ($progress['active_targets'] ?? 0);
        $overdueTargets = (int) ($progress['overdue_targets'] ?? 0);
        $averageHafalanScore = (float) ($progress['average_hafalan_score'] ?? 0);
        $averageMurajaahScore = (float) ($progress['average_murajaah_score'] ?? 0);
        $completedJuz = $progress['completed_juz_list_array'] ?? [];

        return [
            'level' => $this->level($progressPercent),
            'message' => $this->message($student, $progressPercent, $overdueTargets, $repeatHafalanRecords),
            'badges' => $this->badges(
                $progressPercent,
                $totalHafalanRecords,
                $passedHafalanRecords,
                $repeatHafalanRecords,
                $totalMurajaahRecords,
                $completedTargets,
                $overdueTargets,
                $averageHafalanScore,
                $averageMurajaahScore,
                $completedJuz
            ),
            'next_actions' => $this->nextActions(
                $activeTargets,
                $overdueTargets,
                $repeatHafalanRecords,
                $totalMurajaahRecords,
                $completedTargets
            ),
        ];
    }

    private function level(float $progressPercent): array
    {
        if ($progressPercent >= 20) {
            return [
                'name' => 'Level Mutqin Berkembang',
                'tone' => 'emerald',
                'description' => 'Progress sudah kuat. Fokus berikutnya adalah menjaga kualitas murajaah.',
            ];
        }

        if ($progressPercent >= 10) {
            return [
                'name' => 'Level Stabil',
                'tone' => 'blue',
                'description' => 'Ritme hafalan mulai stabil. Jangan biarkan murajaah tertinggal.',
            ];
        }

        if ($progressPercent >= 5) {
            return [
                'name' => 'Level Naik Ritme',
                'tone' => 'amber',
                'description' => 'Progress mulai terlihat. Target mingguan harus dibuat lebih disiplin.',
            ];
        }

        if ($progressPercent >= 1) {
            return [
                'name' => 'Level Awal Konsisten',
                'tone' => 'indigo',
                'description' => 'Fondasi sudah dimulai. Konsistensi setoran lebih penting daripada mengejar banyak ayat.',
            ];
        }

        return [
            'name' => 'Level Mulai Perjalanan',
            'tone' => 'gray',
            'description' => 'Belum banyak progres lulus. Mulai dari target kecil dan setoran rutin.',
        ];
    }

    private function message(Student $student, float $progressPercent, int $overdueTargets, int $repeatHafalanRecords): string
    {
        if ($overdueTargets > 0) {
            return "{$student->name} punya target yang terlambat. Prioritasnya bukan menambah target baru, tapi menuntaskan target lama dulu.";
        }

        if ($repeatHafalanRecords > 0) {
            return "{$student->name} punya setoran yang perlu diulang. Fokuskan perbaikan kualitas sebelum mengejar kuantitas.";
        }

        if ($progressPercent >= 10) {
            return "{$student->name} sudah punya progres yang cukup stabil. Pertahankan ritme hafalan dan perkuat murajaah.";
        }

        if ($progressPercent > 0) {
            return "{$student->name} sudah mulai bergerak. Jaga setoran kecil tapi konsisten agar progres tidak berhenti.";
        }

        return "{$student->name} belum memiliki hafalan lulus yang tercatat. Mulai dari target ringan agar sistem punya baseline progres.";
    }

    private function badges(
        float $progressPercent,
        int $totalHafalanRecords,
        int $passedHafalanRecords,
        int $repeatHafalanRecords,
        int $totalMurajaahRecords,
        int $completedTargets,
        int $overdueTargets,
        float $averageHafalanScore,
        float $averageMurajaahScore,
        array $completedJuz = []
    ): array {
        try {
            $dbBadges = Badge::active()->get();
        } catch (\Throwable) {
            $dbBadges = collect();
        }

        // If no badges in DB yet, fall back to hardcoded list
        if ($dbBadges->isEmpty()) {
            return $this->fallbackBadges(
                $progressPercent, $totalHafalanRecords, $passedHafalanRecords,
                $repeatHafalanRecords, $totalMurajaahRecords, $completedTargets,
                $overdueTargets, $averageHafalanScore, $averageMurajaahScore
            );
        }

        $result = [];
        foreach ($dbBadges as $badge) {
            $status = $this->evaluateBadge(
                $badge,
                $progressPercent,
                $totalHafalanRecords,
                $passedHafalanRecords,
                $repeatHafalanRecords,
                $totalMurajaahRecords,
                $completedTargets,
                $overdueTargets,
                $averageHafalanScore,
                $averageMurajaahScore,
                $completedJuz
            );

            $result[] = [
                'key'         => $badge->key,
                'title'       => $badge->title,
                'description' => $badge->description,
                'icon'        => $badge->icon,
                'status'      => $status['status'],
                'value'       => $status['value'],
            ];
        }

        return $result;
    }

    private function evaluateBadge(
        Badge $badge,
        float $progressPercent,
        int $totalHafalanRecords,
        int $passedHafalanRecords,
        int $repeatHafalanRecords,
        int $totalMurajaahRecords,
        int $completedTargets,
        int $overdueTargets,
        float $averageHafalanScore,
        float $averageMurajaahScore,
        array $completedJuz
    ): array {
        $target = $badge->target_value;

        return match ($badge->type) {
            'count_hafalan' => [
                'status' => $totalHafalanRecords >= $target ? 'earned' : 'locked',
                'value'  => "{$totalHafalanRecords}/{$target}",
            ],
            'passed_hafalan' => [
                'status' => $passedHafalanRecords >= $target ? 'earned' : 'locked',
                'value'  => "{$passedHafalanRecords}/{$target}",
            ],
            'percent_quran' => [
                'status' => $progressPercent >= $target ? 'earned' : 'locked',
                'value'  => number_format($progressPercent, 2) . '%',
            ],
            'count_murajaah' => [
                'status' => $totalMurajaahRecords >= $target ? 'earned' : 'locked',
                'value'  => "{$totalMurajaahRecords}/{$target}",
            ],
            'completed_targets' => [
                'status' => $completedTargets >= $target ? 'earned' : 'locked',
                'value'  => "{$completedTargets}/{$target}",
            ],
            'clean_target' => [
                'status' => $overdueTargets === 0 ? 'earned' : 'attention',
                'value'  => "{$overdueTargets} terlambat",
            ],
            'score_quality' => [
                'status' => ($averageHafalanScore >= $target || $averageMurajaahScore >= $target) ? 'earned' : 'locked',
                'value'  => 'H: ' . number_format($averageHafalanScore, 1) . ' / M: ' . number_format($averageMurajaahScore, 1),
            ],
            'completed_juz' => [
                'status' => in_array((int) $badge->target_juz, $completedJuz) ? 'earned' : 'locked',
                'value'  => in_array((int) $badge->target_juz, $completedJuz) ? 'Khatam ✓' : 'Belum',
            ],
            default => ['status' => 'locked', 'value' => '-'],
        };
    }

    private function fallbackBadges(
        float $progressPercent,
        int $totalHafalanRecords,
        int $passedHafalanRecords,
        int $repeatHafalanRecords,
        int $totalMurajaahRecords,
        int $completedTargets,
        int $overdueTargets,
        float $averageHafalanScore,
        float $averageMurajaahScore
    ): array {
        return [
            ['key' => 'first_hafalan',   'title' => 'Setoran Pertama',  'description' => 'Memiliki minimal 1 setoran hafalan.',                    'icon' => 'sparkles',    'status' => $totalHafalanRecords >= 1  ? 'earned' : 'locked',    'value' => "{$totalHafalanRecords}/1"],
            ['key' => 'five_hafalan',    'title' => '5 Setoran',         'description' => 'Mencapai minimal 5 setoran hafalan.',                    'icon' => 'bolt',        'status' => $totalHafalanRecords >= 5  ? 'earned' : 'locked',    'value' => "{$totalHafalanRecords}/5"],
            ['key' => 'ten_passed',      'title' => '10 Hafalan Lulus',  'description' => 'Memiliki minimal 10 setoran hafalan berstatus lulus.',   'icon' => 'check-badge', 'status' => $passedHafalanRecords >= 10 ? 'earned' : 'locked',  'value' => "{$passedHafalanRecords}/10"],
            ['key' => 'one_percent',     'title' => '1% Al-Qur\'an',     'description' => 'Progress hafalan minimal 1% dari total ayat.',           'icon' => 'book-open',   'status' => $progressPercent >= 1      ? 'earned' : 'locked',    'value' => number_format($progressPercent, 2).'%'],
            ['key' => 'five_percent',    'title' => '5% Al-Qur\'an',     'description' => 'Progress hafalan minimal 5% dari total ayat.',           'icon' => 'book-open',   'status' => $progressPercent >= 5      ? 'earned' : 'locked',    'value' => number_format($progressPercent, 2).'%'],
            ['key' => 'murajaah_active', 'title' => 'Murajaah Aktif',    'description' => 'Memiliki minimal 5 catatan murajaah.',                   'icon' => 'arrow-path',  'status' => $totalMurajaahRecords >= 5  ? 'earned' : 'locked',   'value' => "{$totalMurajaahRecords}/5"],
            ['key' => 'target_finisher', 'title' => 'Penuntas Target',   'description' => 'Menyelesaikan minimal 3 target hafalan.',                'icon' => 'trophy',      'status' => $completedTargets >= 3      ? 'earned' : 'locked',   'value' => "{$completedTargets}/3"],
            ['key' => 'clean_target',    'title' => 'Target Tertib',      'description' => 'Tidak memiliki target terlambat.',                       'icon' => 'shield-check','status' => $overdueTargets === 0       ? 'earned' : 'attention','value' => "{$overdueTargets} terlambat"],
            ['key' => 'score_quality',   'title' => 'Kualitas Baik',      'description' => 'Rata-rata nilai hafalan atau murajaah minimal 80.',      'icon' => 'star',        'status' => ($averageHafalanScore >= 80 || $averageMurajaahScore >= 80) ? 'earned' : 'locked', 'value' => 'H: '.number_format($averageHafalanScore, 2).' / M: '.number_format($averageMurajaahScore, 2)],
        ];
    }

    private function nextActions(
        int $activeTargets,
        int $overdueTargets,
        int $repeatHafalanRecords,
        int $totalMurajaahRecords,
        int $completedTargets
    ): array {
        $actions = [];

        if ($overdueTargets > 0) {
            $actions[] = [
                'title' => 'Tuntaskan target terlambat',
                'description' => 'Jangan tambah target baru sebelum target lama dibereskan.',
                'priority' => 'high',
            ];
        }

        if ($repeatHafalanRecords > 0) {
            $actions[] = [
                'title' => 'Ulangi setoran yang belum lulus',
                'description' => 'Setoran repeat tidak dihitung sebagai progress hafalan lulus.',
                'priority' => 'high',
            ];
        }

        if ($activeTargets === 0) {
            $actions[] = [
                'title' => 'Buat target hafalan aktif',
                'description' => 'Tanpa target aktif, guru dan orangtua sulit mengukur arah progres.',
                'priority' => 'medium',
            ];
        }

        if ($totalMurajaahRecords === 0) {
            $actions[] = [
                'title' => 'Mulai catatan murajaah',
                'description' => 'Progress hafalan tanpa murajaah rawan turun kualitasnya.',
                'priority' => 'medium',
            ];
        }

        if ($completedTargets < 3) {
            $actions[] = [
                'title' => 'Kejar 3 target selesai',
                'description' => 'Tiga target selesai cukup untuk membaca pola disiplin awal murid.',
                'priority' => 'low',
            ];
        }

        if (empty($actions)) {
            $actions[] = [
                'title' => 'Pertahankan ritme',
                'description' => 'Data murid cukup sehat. Fokus berikutnya adalah konsistensi dan kualitas murajaah.',
                'priority' => 'low',
            ];
        }

        return array_slice($actions, 0, 4);
    }
}
