<?php

namespace App\Traits;

use App\Models\Institution;
use App\Models\Scopes\InstitutionScope;
use App\Services\InstitutionContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToInstitution
{
    /**
     * Boot the BelongsToInstitution trait for a model.
     */
    public static function bootBelongsToInstitution(): void
    {
        static::addGlobalScope(new InstitutionScope);

        static::creating(function ($model) {
            if (empty($model->institution_id)) {
                $activeId = app(InstitutionContext::class)->id();
                if ($activeId !== null) {
                    $model->institution_id = $activeId;
                }
            }
        });
    }

    /**
     * Get the institution that the model belongs to.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}
