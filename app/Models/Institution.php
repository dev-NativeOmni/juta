<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Institution extends Model
{
    use HasFactory, SoftDeletes;

    public const AVAILABLE_FEATURES = [
        'feature_tahfizh' => [
            'name' => 'Tahfizh & Al-Qur\'an',
            'description' => 'Setoran hafalan, muraja\'ah, target bulanan/triwulan, ujian tahfizh, dan mushaf digital.',
            'default' => true,
        ],
        'feature_adab' => [
            'name' => 'Adab & Pembinaan Karakter',
            'description' => 'Kuesioner adab harian santri, kalender adab, dan materi pembinaan adab.',
            'default' => true,
        ],
        'feature_ketahanan' => [
            'name' => 'Ketahanan Sekolah (Poin & Disiplin)',
            'description' => 'Pencatatan pelanggaran, poin kebaikan/prestasi, dan analisis kedisiplinan santri.',
            'default' => true,
        ],
        'feature_rapor' => [
            'name' => 'Rapor Digital & Analitik',
            'description' => 'Rapor triwulan/semester, grafik periodik, capaian kompetensi, dan ekspor PDF/Excel.',
            'default' => true,
        ],
        'feature_badges' => [
            'name' => 'Gamifikasi & Lencana (Badge)',
            'description' => 'Sistem badge pencapaian otomatis saat santri mencapai milestone hafalan.',
            'default' => true,
        ],
        'feature_whatsapp' => [
            'name' => 'Laporan WhatsApp Harian',
            'description' => 'Format siap kirim laporan progres santri ke nomor WhatsApp orang tua.',
            'default' => true,
        ],
    ];

    public function isFeatureEnabled(string $featureKey, bool $default = true): bool
    {
        $features = $this->settings['features'] ?? [];

        if (array_key_exists($featureKey, $features)) {
            return (bool) $features[$featureKey];
        }

        return self::AVAILABLE_FEATURES[$featureKey]['default'] ?? $default;
    }

    protected $fillable = [
        'code',
        'name',
        'slug',
        'logo_path',
        'login_bg',
        'landing_bg',
        'address',
        'phone',
        'email',
        'is_active',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Institution $institution) {
            $institution->code = strtoupper(trim((string) $institution->code));
            if (empty($institution->slug)) {
                $institution->slug = Str::slug($institution->name ?: $institution->code);
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByCodeOrSlug(Builder $query, string $identifier): Builder
    {
        $clean = trim($identifier);
        $upper = strtoupper($clean);
        $slug = Str::slug($clean);

        return $query->where(function (Builder $q) use ($upper, $slug, $clean) {
            $q->where('code', $upper)
                ->orWhere('slug', $slug)
                ->orWhere('code', $clean);
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(TeacherProfile::class);
    }

    public function classRooms(): HasMany
    {
        return $this->hasMany(ClassRoom::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo_path) {
            return null;
        }

        if (str_starts_with($this->logo_path, 'http://') || str_starts_with($this->logo_path, 'https://')) {
            return $this->logo_path;
        }

        if (Storage::disk('public')->exists($this->logo_path)) {
            return Storage::disk('public')->url($this->logo_path);
        }

        return asset($this->logo_path);
    }

    public function getLoginBgUrlAttribute(): ?string
    {
        if (! $this->login_bg) {
            return null;
        }

        if (str_starts_with($this->login_bg, 'http://') || str_starts_with($this->login_bg, 'https://')) {
            return $this->login_bg;
        }

        if (Storage::disk('public')->exists($this->login_bg)) {
            return Storage::disk('public')->url($this->login_bg);
        }

        return asset($this->login_bg);
    }

    public function getLandingBgUrlAttribute(): ?string
    {
        if (! $this->landing_bg) {
            return null;
        }

        if (str_starts_with($this->landing_bg, 'http://') || str_starts_with($this->landing_bg, 'https://')) {
            return $this->landing_bg;
        }

        if (Storage::disk('public')->exists($this->landing_bg)) {
            return Storage::disk('public')->url($this->landing_bg);
        }

        return asset($this->landing_bg);
    }
}
