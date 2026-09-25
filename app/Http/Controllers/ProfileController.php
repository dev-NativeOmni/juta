<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Support\Signatures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'canSign' => $this->canSign($user),
            'signaturePreview' => Signatures::dataUri($user->signature_path),
        ]);
    }

    /**
     * Unggah/ganti tanda tangan guru (untuk Laporan Triwulan).
     */
    public function updateSignature(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->canSign($user), 403);

        $request->validate(['signature' => ['required', ...array_slice(Signatures::UPLOAD_RULES, 1)]]);

        Signatures::delete($user->signature_path);
        $user->update(['signature_path' => Signatures::store($request->file('signature'), 'teachers')]);

        return Redirect::route('profile.edit')->with('status', 'signature-updated');
    }

    public function destroySignature(Request $request): RedirectResponse
    {
        $user = $request->user();
        Signatures::delete($user->signature_path);
        $user->update(['signature_path' => null]);

        return Redirect::route('profile.edit')->with('status', 'signature-deleted');
    }

    /**
     * Tanda tangan hanya relevan untuk akun yang terhubung ke profil guru.
     */
    private function canSign($user): bool
    {
        return $user?->teacherProfile !== null;
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($request->boolean('remove_avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = null;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        if ($user->hasAnyRole(['super_admin', 'admin'])) {
            if (isset($validated['name'])) {
                $user->name = $validated['name'];
            }
            if (isset($validated['username'])) {
                $user->username = $validated['username'];
            }
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account (Disabled: Only Admin / Super Admin can delete users via User Management).
     */
    public function destroy(Request $request): RedirectResponse
    {
        abort(403, 'Penghapusan akun mandiri tidak diizinkan. Hubungi Admin atau Super Admin.');
    }
}
