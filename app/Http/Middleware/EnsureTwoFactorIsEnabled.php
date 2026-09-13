<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorIsEnabled
{
    /**
     * Roles required to enroll 2FA are blocked from the rest of the app
     * until they finish setup, so no super-admin session goes unprotected.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->mustUseTwoFactor() && ! $user->hasEnabledTwoFactorAuthentication()) {
            return redirect()->route('two-factor.show')
                ->with('warning', 'Role Anda mewajibkan autentikasi dua faktor. Silakan aktifkan terlebih dahulu.');
        }

        return $next($request);
    }
}
