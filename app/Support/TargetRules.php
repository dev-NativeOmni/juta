<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Aturan target hafalan otomatis yang bisa diatur di Pengaturan Target Hafalan:
 * baris per pertemuan per level, juz wajib di bagian belakang, dan batas paling
 * akhir murid boleh pindah ke depan (Juz 1). Satu-satunya sumber nilai ini.
 */
class TargetRules
{
    public const LEVELS = ['tahsin' => 'Tahsin', 'reguler' => 'Reguler', 'akselerasi' => 'Akselerasi'];

    public const DEFAULT_LEVEL_LINES = ['tahsin' => 3, 'reguler' => 5, 'akselerasi' => 7];

    /** Juz 30 sampai juz ini wajib sebelum boleh pindah ke depan. */
    public const DEFAULT_MANDATORY_UNTIL = 29;

    /** Batas paling akhir pindah ke depan. */
    public const DEFAULT_LATEST_SWITCH = 27;

    /**
     * @return array<string, int>
     */
    public static function levelLines(): array
    {
        $saved = json_decode((string) Setting::get('target_level_lines'), true) ?: [];

        return collect(self::DEFAULT_LEVEL_LINES)
            ->map(fn ($default, $level) => max(1, (int) ($saved[$level] ?? $default)))
            ->all();
    }

    /**
     * Baris per pertemuan untuk level murid; null untuk Ummi (target dibuat guru).
     */
    public static function linesForLevel(?string $level): ?int
    {
        if ($level === 'ummi') {
            return null;
        }

        $lines = self::levelLines();

        return $lines[$level] ?? $lines['reguler'];
    }

    public static function mandatoryUntil(): int
    {
        return (int) Setting::get('target_mandatory_until_juz', self::DEFAULT_MANDATORY_UNTIL);
    }

    public static function latestSwitch(): int
    {
        return (int) Setting::get('target_latest_switch_juz', self::DEFAULT_LATEST_SWITCH);
    }

    /**
     * Juz setelah mana murid boleh pindah ke depan, mis. [29, 28, 27].
     *
     * @return array<int, int>
     */
    public static function switchOptions(): array
    {
        return range(self::mandatoryUntil(), self::latestSwitch());
    }

    /**
     * @param  array<string, int>  $levelLines
     */
    public static function save(array $levelLines, int $mandatoryUntil, int $latestSwitch): void
    {
        Setting::set('target_level_lines', json_encode(collect(self::DEFAULT_LEVEL_LINES)
            ->map(fn ($default, $level) => max(1, (int) ($levelLines[$level] ?? $default)))
            ->all()));
        Setting::set('target_mandatory_until_juz', (string) $mandatoryUntil);
        Setting::set('target_latest_switch_juz', (string) $latestSwitch);
    }
}
