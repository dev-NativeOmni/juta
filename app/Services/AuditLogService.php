<?php

namespace App\Services;

use App\Models\AdabMentorAssessment;
use App\Models\AdabRecord;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Badge;
use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\ParentProfile;
use App\Models\Program;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentPoint;
use App\Models\StudentReport;
use App\Models\TahfizhExam;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class AuditLogService
{
    public function logCreated(Model $model): void
    {
        $this->write(
            action: 'created',
            model: $model,
            oldValues: null,
            newValues: $this->safeAttributes($model->getAttributes())
        );
    }

    public function logUpdated(Model $model): void
    {
        $changes = Arr::except($model->getChanges(), ['updated_at']);

        if (empty($changes)) {
            return;
        }

        $oldValues = [];

        foreach (array_keys($changes) as $key) {
            $oldValues[$key] = $this->normalizeValue($model->getOriginal($key));
        }

        $newValues = [];

        foreach ($changes as $key => $value) {
            $newValues[$key] = $this->normalizeValue($value);
        }

        $this->write(
            action: 'updated',
            model: $model,
            oldValues: $this->safeAttributes($oldValues),
            newValues: $this->safeAttributes($newValues)
        );
    }

    public function logDeleted(Model $model): void
    {
        $this->write(
            action: 'deleted',
            model: $model,
            oldValues: $this->safeAttributes($model->getAttributes()),
            newValues: null
        );
    }

    public function logRestored(Model $model): void
    {
        $this->write(
            action: 'restored',
            model: $model,
            oldValues: null,
            newValues: $this->safeAttributes($model->getAttributes())
        );
    }

    public function logForceDeleted(Model $model): void
    {
        $this->write(
            action: 'force_deleted',
            model: $model,
            oldValues: $this->safeAttributes($model->getAttributes()),
            newValues: null
        );
    }

    /**
     * Log an action that isn't tied to an Eloquent model change,
     * e.g. downloading/deleting a backup file or starting an impersonation session.
     */
    public function logAction(string $action, string $description, ?array $context = null): void
    {
        $actor = auth()->user();

        AuditLog::create([
            'user_id' => $actor?->id,
            'user_name' => $actor?->name,
            'role_name' => $actor?->role?->name,

            'action' => $action,

            'auditable_type' => null,
            'auditable_id' => null,
            'auditable_label' => null,
            'auditable_name' => null,

            'description' => $description,

            'old_values' => null,
            'new_values' => $context,

            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'url' => request()?->fullUrl(),
            'method' => request()?->method(),
        ]);
    }

    private function write(
        string $action,
        Model $model,
        ?array $oldValues,
        ?array $newValues
    ): void {
        $actor = auth()->user();

        AuditLog::create([
            'user_id' => $actor?->id,
            'user_name' => $actor?->name,
            'role_name' => $actor?->role?->name,

            'action' => $action,

            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'auditable_label' => $this->modelLabel($model),
            'auditable_name' => $this->modelName($model),

            'description' => $this->description($action, $model),

            'old_values' => $oldValues,
            'new_values' => $newValues,

            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'url' => request()?->fullUrl(),
            'method' => request()?->method(),
        ]);
    }

    private function safeAttributes(array $attributes): array
    {
        $hiddenKeys = [
            'password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'current_team_id',
        ];

        return collect($attributes)
            ->reject(fn ($value, $key) => in_array($key, $hiddenKeys, true))
            ->map(fn ($value) => $this->normalizeValue($value))
            ->all();
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value) || is_null($value) || is_numeric($value) || is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            return $value;
        }

        return (string) $value;
    }

    private function modelLabel(Model $model): string
    {
        return match ($model::class) {
            User::class => 'User',
            Program::class => 'Program',
            ClassRoom::class => 'Kelas',
            TeacherProfile::class => 'Guru',
            ParentProfile::class => 'Orangtua',
            Student::class => 'Murid',
            HafalanRecord::class => 'Setoran Hafalan',
            MurajaahRecord::class => 'Murajaah',
            HafalanTarget::class => 'Target Hafalan',
            TahfizhExam::class => 'Ujian Tahfizh',
            Attendance::class => 'Absensi',
            StudentPoint::class => 'Poin Siswa',
            StudentReport::class => 'Rapor Siswa',
            AdabRecord::class => 'Catatan Adab',
            AdabMentorAssessment::class => 'Penilaian Pendamping Adab',
            Badge::class => 'Lencana',
            Setting::class => 'Pengaturan',
            default => class_basename($model),
        };
    }

    private function modelName(Model $model): string
    {
        foreach (['name', 'title', 'email', 'code', 'nis'] as $field) {
            if ($model->getAttribute($field)) {
                return (string) $model->getAttribute($field);
            }
        }

        if ($model instanceof HafalanRecord) {
            return 'Hafalan #'.$model->getKey();
        }

        if ($model instanceof MurajaahRecord) {
            return 'Murajaah #'.$model->getKey();
        }

        if ($model instanceof HafalanTarget) {
            return 'Target Hafalan #'.$model->getKey();
        }

        return class_basename($model).' #'.$model->getKey();
    }

    private function description(string $action, Model $model): string
    {
        $actionLabel = match ($action) {
            'created' => 'membuat',
            'updated' => 'mengubah',
            'deleted' => 'menghapus',
            'restored' => 'memulihkan',
            'force_deleted' => 'menghapus permanen',
            default => $action,
        };

        return trim(sprintf(
            '%s %s %s',
            auth()->user()?->name ?? 'System',
            $actionLabel,
            $this->modelLabel($model)
        ));
    }
}
