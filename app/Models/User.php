<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'role_id',
        'name',
        'username',
        'avatar',
        'signature_path',
        'password',
        'plain_password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'signature_path',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'plain_password' => 'encrypted',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
    }

    /**
     * Get all roles assigned to this user.
     * Includes both pivot roles and fallback primary role.
     */
    public function assignedRoles(): Collection
    {
        $roles = $this->relationLoaded('roles') ? $this->roles : $this->roles()->get();

        if ($roles->isEmpty() && $this->role) {
            $roles = collect([$this->role]);
        } elseif ($this->role && ! $roles->contains('id', $this->role->id)) {
            $roles = $roles->prepend($this->role);
        }

        // Cache on Eloquent relation so subsequent checks in the same request are 0ms in-memory
        if (! $this->relationLoaded('roles')) {
            $this->setRelation('roles', $roles);
        }

        return $roles;
    }

    /**
     * Get the currently active role for the user's session.
     * If active_role_id session is set and valid, returns that role.
     * Otherwise returns the primary role.
     */
    public function currentRole(): ?Role
    {
        $activeRoleId = (int) (session('active_role_id') ?: 0);

        if ($activeRoleId > 0) {
            $matched = $this->assignedRoles()->firstWhere('id', $activeRoleId);
            if ($matched) {
                return $matched;
            }
        }

        return $this->role;
    }

    public function teacherProfile(): HasOne
    {
        return $this->hasOne(TeacherProfile::class);
    }

    public function parentProfile(): HasOne
    {
        return $this->hasOne(ParentProfile::class);
    }

    public function waliKelasClassRoom(): HasOne
    {
        return $this->hasOne(ClassRoom::class, 'wali_kelas_user_id');
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function systemNotifications(): HasMany
    {
        return $this->hasMany(SystemNotification::class);
    }

    public function createdSystemNotifications(): HasMany
    {
        return $this->hasMany(SystemNotification::class, 'created_by');
    }

    public function unreadSystemNotifications(): HasMany
    {
        return $this->systemNotifications()
            ->unread()
            ->published();
    }

    public function adabMaterials(): HasMany
    {
        return $this->hasMany(AdabMaterial::class, 'created_by');
    }

    public function pendampingClasses(): BelongsToMany
    {
        return $this->belongsToMany(ClassRoom::class, 'class_room_pendamping_adab', 'user_id', 'class_room_id')->withTimestamps();
    }

    public function isAssignedPendampingForClass(int $classRoomId): bool
    {
        return $this->pendampingClasses()->where('class_rooms.id', $classRoomId)->exists()
            || ClassRoom::where('id', $classRoomId)->where('pendamping_adab_id', $this->id)->exists();
    }

    /**
     * Check if the currently active role matches the given role name.
     */
    public function hasRole(string $role): bool
    {
        return $this->currentRole()?->name === $role;
    }

    /**
     * Check if the currently active role matches any of the given role names.
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->currentRole()?->name, $roles, true);
    }

    /**
     * Check if the user is assigned the given role name (regardless of current active session).
     */
    public function hasAssignedRole(string $role): bool
    {
        return $this->assignedRoles()->contains('name', $role);
    }

    /**
     * Check if the user is assigned any of the given role names.
     */
    public function hasAnyAssignedRole(array $roles): bool
    {
        return $this->assignedRoles()->whereIn('name', $roles)->isNotEmpty();
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Roles like super_admin must have 2FA confirmed before they can use the app.
     */
    public function mustUseTwoFactor(): bool
    {
        if (! config('hafizplus.two_factor.enforce', true)) {
            return false;
        }

        return $this->hasAnyAssignedRole(config('hafizplus.two_factor.required_roles', ['super_admin']));
    }

    public function hasEnabledTwoFactorAuthentication(): bool
    {
        return ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }
}
