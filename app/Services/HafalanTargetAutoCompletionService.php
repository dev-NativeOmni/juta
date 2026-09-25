<?php

namespace App\Services;

use App\Models\HafalanRecordSurah;
use App\Models\HafalanTarget;

class HafalanTargetAutoCompletionService
{
    public function syncExistingTargets(bool $dryRun = false): int
    {
        $matchedTargets = 0;

        HafalanTarget::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->chunkById(100, function ($targets) use (&$matchedTargets, $dryRun) {
                foreach ($targets as $target) {
                    $record = $this->matchingPassedRecordForTarget($target);

                    if (! $record) {
                        continue;
                    }

                    $matchedTargets++;

                    if ($dryRun) {
                        continue;
                    }

                    $submittedAt = $record->hafalanRecord?->submitted_at;

                    $target->update([
                        'status' => 'completed',
                        'completed_at' => $submittedAt
                            ? $submittedAt->copy()->endOfDay()
                            : now(),
                    ]);
                }
            });

        return $matchedTargets;
    }

    public function matchingPassedRecordForTarget(HafalanTarget $target): ?HafalanRecordSurah
    {
        return HafalanRecordSurah::query()
            ->select('hafalan_record_surahs.*')
            ->join('hafalan_records', 'hafalan_records.id', '=', 'hafalan_record_surahs.hafalan_record_id')
            ->whereNull('hafalan_records.deleted_at')
            ->where('hafalan_records.student_id', $target->student_id)
            ->where('hafalan_record_surahs.surah_id', $target->surah_id)
            ->where('hafalan_record_surahs.status', 'passed')
            ->where('hafalan_record_surahs.ayah_end', '>=', $target->ayah)
            ->with('hafalanRecord')
            ->orderBy('hafalan_records.submitted_at')
            ->orderBy('hafalan_record_surahs.id')
            ->first();
    }
}
