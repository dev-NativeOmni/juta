<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\HafalanRecord;
use Illuminate\Support\Arr;

class HafalanRecordObserver
{
    public function created(HafalanRecord $hafalanRecord): void
    {
        $this->writeLog(
            hafalanRecord: $hafalanRecord,
            event: 'created',
            oldValues: null,
            newValues: Arr::only($hafalanRecord->getAttributes(), $this->trackedFields())
        );
    }

    public function updated(HafalanRecord $hafalanRecord): void
    {
        $changes = Arr::except($hafalanRecord->getChanges(), [
            'updated_at',
        ]);

        if ($changes === []) {
            return;
        }

        $changedKeys = array_keys($changes);

        $oldValues = Arr::only($hafalanRecord->getOriginal(), $changedKeys);
        $newValues = Arr::only($hafalanRecord->getAttributes(), $changedKeys);

        $this->writeLog(
            hafalanRecord: $hafalanRecord,
            event: 'updated',
            oldValues: $oldValues,
            newValues: $newValues
        );
    }

    public function deleted(HafalanRecord $hafalanRecord): void
    {
        $this->writeLog(
            hafalanRecord: $hafalanRecord,
            event: 'deleted',
            oldValues: Arr::only($hafalanRecord->getOriginal(), $this->trackedFields()),
            newValues: Arr::only($hafalanRecord->getAttributes(), $this->trackedFields())
        );
    }

    public function restored(HafalanRecord $hafalanRecord): void
    {
        $this->writeLog(
            hafalanRecord: $hafalanRecord,
            event: 'restored',
            oldValues: Arr::only($hafalanRecord->getOriginal(), $this->trackedFields()),
            newValues: Arr::only($hafalanRecord->getAttributes(), $this->trackedFields())
        );
    }

    private function writeLog(
        HafalanRecord $hafalanRecord,
        string $event,
        ?array $oldValues,
        ?array $newValues
    ): void {
        $runningInConsole = app()->runningInConsole();

        AuditLog::create([
            'user_id' => auth()->id(),
            'auditable_type' => $hafalanRecord->getMorphClass(),
            'auditable_id' => $hafalanRecord->getKey(),
            'auditable_label' => $this->makeAuditableLabel($hafalanRecord),
            'action' => $event,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'url' => $runningInConsole ? null : request()->fullUrl(),
            'ip_address' => $runningInConsole ? null : request()->ip(),
            'user_agent' => $runningInConsole ? null : request()->userAgent(),
        ]);
    }

    private function makeAuditableLabel(HafalanRecord $hafalanRecord): string
    {
        $hafalanRecord->loadMissing(['student', 'surahs.surah']);

        $studentName = $hafalanRecord->student?->name
            ?? 'Murid ID '.$hafalanRecord->student_id;

        $dateLabel = $hafalanRecord->submitted_at?->format('d M Y') ?? '-';

        // At the "created" event the header row exists but its surah lines haven't
        // been attached yet (they're created right after), so the label falls back
        // to a generic "Setoran" tag rather than listing surahs in that case.
        $surahsLabel = $hafalanRecord->surahs->isNotEmpty() ? $hafalanRecord->surahs_label : null;

        return $surahsLabel
            ? "{$studentName} - {$surahsLabel} ({$dateLabel})"
            : "{$studentName} - Setoran {$dateLabel}";
    }

    private function trackedFields(): array
    {
        return [
            'id',
            'student_id',
            'teacher_id',
            'notes',
            'submitted_at',
            'deleted_at',
        ];
    }
}
