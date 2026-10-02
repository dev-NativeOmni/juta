<?php

namespace App\Models\Scopes;

use App\Services\InstitutionContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class InstitutionScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(InstitutionContext::class);
        $institutionId = $context->id();

        if ($institutionId !== null) {
            $builder->where($model->qualifyColumn('institution_id'), $institutionId);
        }
    }

    /**
     * Extend the query builder with the needed functions.
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('withoutInstitution', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('forInstitution', function (Builder $builder, int $institutionId) {
            return $builder->withoutGlobalScope($this)->where($builder->getModel()->qualifyColumn('institution_id'), $institutionId);
        });
    }
}
