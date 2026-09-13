<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorAuthenticationController extends Controller
{
    public function __construct(
        private readonly TwoFactorAuthenticationService $twoFactor,
        private readonly AuditLogService $auditLog,
    ) {}

    public function show(Request $request): View
    {
        $user = $request->user();

        $pendingSecret = $request->session()->get('two_factor.pending_secret');
        $qrCodeSvg = null;

        if (! $user->hasEnabledTwoFactorAuthentication() && $pendingSecret) {
            $qrCodeSvg = $this->twoFactor->qrCodeSvg($user, $pendingSecret);
        }

        return view('auth.two-factor-authentication', [
            'enabled' => $user->hasEnabledTwoFactorAuthentication(),
            'required' => $user->mustUseTwoFactor(),
            'pendingSecret' => $pendingSecret,
            'qrCodeSvg' => $qrCodeSvg,
            'recoveryCodes' => $request->session()->get('two_factor.fresh_recovery_codes'),
        ]);
    }

    /**
     * Start enrollment: generate a pending secret held only in the session
     * until the user proves they can produce a valid code for it.
     */
    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasEnabledTwoFactorAuthentication()) {
            return redirect()->route('two-factor.show');
        }

        $request->session()->put('two_factor.pending_secret', $this->twoFactor->generateSecretKey());

        return redirect()->route('two-factor.show');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $user = $request->user();

        $secret = $request->session()->get('two_factor.pending_secret');

        abort_if(! $secret, 419, 'Sesi aktivasi 2FA telah kedaluwarsa, silakan mulai ulang.');

        $request->validate(['code' => ['required', 'string']]);

        if (! $this->twoFactor->verify($secret, (string) $request->string('code'))) {
            throw ValidationException::withMessages([
                'code' => 'Kode verifikasi tidak valid. Pastikan waktu di perangkat Anda akurat.',
            ]);
        }

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('two_factor.pending_secret');
        $request->session()->put('two_factor.fresh_recovery_codes', $recoveryCodes);

        $this->auditLog->logAction(
            action: 'two_factor_enabled',
            description: $user->name.' mengaktifkan autentikasi dua faktor.',
        );

        return redirect()->route('two-factor.show')->with('success', 'Autentikasi dua faktor berhasil diaktifkan. Simpan kode pemulihan di bawah ini di tempat yang aman.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->mustUseTwoFactor()) {
            abort(403, 'Role Anda mewajibkan autentikasi dua faktor tetap aktif.');
        }

        $request->validate(['password' => ['required', 'current_password']]);

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->auditLog->logAction(
            action: 'two_factor_disabled',
            description: $user->name.' menonaktifkan autentikasi dua faktor.',
        );

        return redirect()->route('two-factor.show')->with('success', 'Autentikasi dua faktor dinonaktifkan.');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->hasEnabledTwoFactorAuthentication(), 403);

        $request->validate(['password' => ['required', 'current_password']]);

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();

        $user->forceFill(['two_factor_recovery_codes' => $recoveryCodes])->save();

        $request->session()->put('two_factor.fresh_recovery_codes', $recoveryCodes);

        $this->auditLog->logAction(
            action: 'two_factor_recovery_codes_regenerated',
            description: $user->name.' membuat ulang kode pemulihan 2FA.',
        );

        return redirect()->route('two-factor.show')->with('success', 'Kode pemulihan baru berhasil dibuat. Kode lama sudah tidak berlaku.');
    }
}
