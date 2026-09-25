<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'role_name',
        'action',
        'auditable_type',
        'auditable_id',
        'auditable_label',
        'auditable_name',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'method',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'created' => 'Dibuat',
            'updated' => 'Diubah',
            'deleted' => 'Dihapus',
            'restored' => 'Dipulihkan',
            'force_deleted' => 'Dihapus Permanen',
            'backup_requested' => 'Backup Dijadwalkan',
            'backup_downloaded' => 'Backup Diunduh',
            'backup_deleted' => 'Backup Dihapus',
            'impersonation_started' => 'Impersonasi Dimulai',
            'impersonation_stopped' => 'Impersonasi Diakhiri',
            default => ucfirst((string) $this->action),
        };
    }

    public function getEventAttribute(): ?string
    {
        return $this->action;
    }

    /**
     * Backward-compatible alias.
     */
    public function getEventLabelAttribute(): string
    {
        return $this->action_label;
    }

    public function getAuditableNameAttribute(): string
    {
        return $this->auditable_label
            ?: class_basename((string) $this->auditable_type).' #'.$this->auditable_id;
    }

    public function getAuditableTypeLabelAttribute(): string
    {
        return match ($this->auditable_type) {
            HafalanRecord::class => 'Setoran Hafalan',
            MurajaahRecord::class => 'Murajaah',
            HafalanTarget::class => 'Target Hafalan',
            Student::class => 'Murid',
            Program::class => 'Program',
            ClassRoom::class => 'Kelas',
            TeacherProfile::class => 'Guru',
            ParentProfile::class => 'Orangtua',
            User::class => 'User',
            TahfizhExam::class => 'Ujian Tahfizh',
            Attendance::class => 'Absensi',
            StudentPoint::class => 'Poin Siswa',
            StudentReport::class => 'Rapor Siswa',
            AdabRecord::class => 'Catatan Adab',
            AdabMentorAssessment::class => 'Penilaian Pendamping Adab',
            Badge::class => 'Lencana',
            Setting::class => 'Pengaturan',
            CalendarMonthLock::class => 'Kunci Kalender',
            ClassWeekSchedule::class => 'Jadwal Pekanan',
            default => class_basename((string) $this->auditable_type),
        };
    }
}
