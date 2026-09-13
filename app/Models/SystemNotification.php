<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SystemNotification extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'created_by',
        'unique_hash',
        'title',
        'message',
        'type',
        'severity',
        'source_type',
        'source_id',
        'target_role',
        'action_url',
        'is_read',
        'read_at',
        'published_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'created_by' => 'integer',
            'source_id' => 'integer',
            'is_read' => 'boolean',
            'read_at' => 'datetime',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SystemNotification $notification) {
            if (blank($notification->unique_hash)) {
                $notification->unique_hash = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The record (HafalanTarget, HafalanRecord, MurajaahRecord, ...) that
     * triggered this notification, when it was generated internally.
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where(function (Builder $subQuery) {
            $subQuery->where('is_read', false)
                ->orWhereNull('read_at');
        });
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->where(function (Builder $subQuery) {
            $subQuery->where('is_read', true)
                ->orWhereNotNull('read_at');
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $subQuery) {
                $subQuery->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->where(function (Builder $subQuery) {
                $subQuery->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            });
    }

    public function isUnread(): bool
    {
        return ! $this->is_read || is_null($this->read_at);
    }

    public function markAsRead(): void
    {
        $this->forceFill([
            'is_read' => true,
            'read_at' => $this->read_at ?? now(),
        ])->save();
    }

    public function markAsUnread(): void
    {
        $this->forceFill([
            'is_read' => false,
            'read_at' => null,
        ])->save();
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->severity ?? $this->type) {
            'success' => 'Sukses',
            'warning' => 'Peringatan',
            'danger' => 'Bahaya',
            'error' => 'Error',
            'info' => 'Informasi',
            default => ucfirst((string) $this->type),
        };
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->severity ?? $this->type) {
            'success' => 'bg-emerald-100 text-emerald-800',
            'warning' => 'bg-amber-100 text-amber-800',
            'danger', 'error' => 'bg-red-100 text-red-800',
            default => 'bg-blue-100 text-blue-800',
        };
    }

    public function getTargetRoleLabelAttribute(): string
    {
        return match ($this->target_role) {
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'teacher' => 'Guru',
            'parent' => 'Orangtua',
            'student' => 'Murid',
            null, '' => 'Pengguna tertentu',
            default => ucfirst(str_replace('_', ' ', (string) $this->target_role)),
        };
    }
}
