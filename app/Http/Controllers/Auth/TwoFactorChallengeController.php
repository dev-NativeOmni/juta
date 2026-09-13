<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('two_factor.user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request, TwoFactorAuthenticationService $twoFactor): RedirectResponse
    {
        $userId = $request->session()->get('two_factor.user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $request->validate(['code' => ['required', 'string']]);

        $user = User::find($userId);

        if (! $user) {
            $request->session()->forget(['two_factor.user_id', 'two_factor.remember']);

            return redirect()->route('login');
        }

        $code = trim((string) $request->string('code'));

        $verified = $twoFactor->verify((string) $user->two_factor_secret, $code)
            || $this->tryRecoveryCode($user, $code);

        if (! $verified) {
            throw ValidationException::withMessages([
                'code' => 'Kode verifikasi atau kode pemulihan tidak valid.',
            ]);
        }

        $remember = (bool) $request->session()->pull('two_factor.remember', false);
        $request->session()->forget('two_factor.user_id');

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function tryRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];

        if ($codes === []) {
            return false;
        }

        $normalized = Str::upper($code);
        $index = array_search($normalized, array_map(fn ($c) => Str::upper($c), $codes), true);

        if ($index === false) {
            return false;
        }

        unset($codes[$index]);

        $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

        return true;
    }
}
