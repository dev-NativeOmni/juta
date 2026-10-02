<?php

namespace App\Services;

use App\Models\Institution;
use Illuminate\Support\Facades\Session;

class InstitutionContext
{
    public const SESSION_KEY = 'active_institution_id';

    protected ?Institution $cachedInstitution = null;

    protected bool $resolved = false;

    /**
     * Get the currently active institution from context/session.
     */
    public function get(): ?Institution
    {
        $institutionId = Session::get(self::SESSION_KEY);

        if ($this->resolved && $this->cachedInstitution?->id === $institutionId) {
            return $this->cachedInstitution;
        }

        if ($institutionId) {
            $this->cachedInstitution = Institution::query()
                ->where('id', $institutionId)
                ->where('is_active', true)
                ->first();
        } else {
            $this->cachedInstitution = null;
        }

        $this->resolved = true;

        return $this->cachedInstitution;
    }

    /**
     * Get the ID of the active institution.
     */
    public function id(): ?int
    {
        return $this->get()?->id;
    }

    /**
     * Set the active institution in context and session.
     */
    public function set(?Institution $institution): void
    {
        $this->cachedInstitution = $institution;
        $this->resolved = true;

        if ($institution && $institution->is_active) {
            Session::put(self::SESSION_KEY, $institution->id);
        } else {
            Session::forget(self::SESSION_KEY);
        }
    }

    /**
     * Clear the active institution from context and session.
     */
    public function clear(): void
    {
        $this->cachedInstitution = null;
        $this->resolved = true;
        Session::forget(self::SESSION_KEY);
    }

    /**
     * Find an active institution by its code or slug.
     */
    public function resolveByCode(string $code): ?Institution
    {
        return Institution::query()
            ->active()
            ->byCodeOrSlug($code)
            ->first();
    }
}
