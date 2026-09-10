<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RoleSwitchController extends Controller
{
    public function switch(Request $request, ?Role $role = null): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $roleId = $role?->id ?? $request->integer('role_id');
        $targetRole = $user->assignedRoles()->firstWhere('id', (int) $roleId);

        if (! $targetRole) {
            return redirect()->back()->with('error', 'Peran tersebut tidak valid atau belum diberikan untuk akun Anda.');
        }

        session(['active_role_id' => $targetRole->id]);

        return redirect()->route('dashboard')->with('success', 'Berhasil beralih ke peran ' . $targetRole->display_name . '.');
    }
}
