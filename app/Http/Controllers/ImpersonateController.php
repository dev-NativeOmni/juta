<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLog) {}

    /**
     * Start impersonating a user (Super Admin only).
     */
    public function start(Request $request, User $user): RedirectResponse
    {
        $currentUser = Auth::user();

        // Ensure current user is Super Admin or is already impersonating
        if (! $currentUser || ($currentUser->role?->name !== 'super_admin' && ! session()->has('impersonated_by'))) {
            abort(403, 'Hanya Super Admin yang dapat menggunakan fitur impersonasi.');
        }

        if ($currentUser->id === $user->id) {
            return back()->with('error', 'Anda sudah masuk dengan akun ini.');
        }

        if (! $user->isActive()) {
            return back()->with('error', 'Tidak dapat masuk sebagai pengguna ini karena akun sedang nonaktif.');
        }

        // Save original admin ID if not already impersonating
        if (! session()->has('impersonated_by')) {
            session(['impersonated_by' => $currentUser->id]);
        }

        $this->auditLog->logAction(
            action: 'impersonation_started',
            description: $currentUser->name.' mulai impersonasi sebagai '.$user->name.' ('.($user->role?->display_name ?? $user->role?->name).').',
            context: [
                'admin_id' => $currentUser->id,
                'target_user_id' => $user->id,
            ],
        );

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Berhasil masuk sebagai '.$user->name.' ('.$user->role?->display_name.').');
    }

    /**
     * Stop impersonating and return to the original Super Admin account.
     */
    public function stop(Request $request): RedirectResponse
    {
        if (! session()->has('impersonated_by')) {
            return redirect()->route('dashboard');
        }

        $impersonatedUser = Auth::user();
        $originalUserId = session('impersonated_by');
        session()->forget('impersonated_by');

        $originalUser = User::find($originalUserId);

        if ($originalUser) {
            $this->auditLog->logAction(
                action: 'impersonation_stopped',
                description: $originalUser->name.' mengakhiri impersonasi sebagai '.($impersonatedUser?->name ?? 'pengguna #'.$originalUserId).'.',
                context: [
                    'admin_id' => $originalUser->id,
                    'target_user_id' => $impersonatedUser?->id,
                ],
            );

            Auth::login($originalUser);

            return redirect()->route('users.index')->with('success', 'Kembali ke sesi Super Admin ('.$originalUser->name.').');
        }

        return redirect()->route('dashboard')->with('info', 'Sesi impersonasi diakhiri.');
    }
}
