<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Services\InstitutionContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstitutionGateController extends Controller
{
    public function __construct(
        protected InstitutionContext $context
    ) {}

    /**
     * Show the gatekeeper portal where users input their institution/school code.
     */
    public function index(Request $request): View
    {
        $current = $this->context->get();

        return view('portal.gate', [
            'currentInstitution' => $current,
        ]);
    }

    /**
     * Verify the entered institution code and initiate the tenant session.
     */
    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_code' => ['required', 'string', 'max:50'],
        ], [
            'school_code.required' => 'Masukkan kode lembaga/sekolah terlebih dahulu.',
        ]);

        $code = trim($validated['school_code']);
        $institution = $this->context->resolveByCode($code);

        if (! $institution) {
            return back()
                ->withInput(['school_code' => $code])
                ->withErrors([
                    'school_code' => 'Kode lembaga tidak ditemukan atau akun lembaga sedang nonaktif. Pastikan kode yang Anda masukkan benar.',
                ]);
        }

        $this->context->set($institution);

        return redirect()
            ->route('login')
            ->with('status', "Selamat datang di Portal {$institution->name}. Silakan masuk dengan akun Anda.");
    }

    /**
     * Direct link access via URL slug or code (e.g. /s/SMAIT01 or /portal/smait-01).
     */
    public function directAccess(string $code): RedirectResponse
    {
        $institution = $this->context->resolveByCode($code);

        if (! $institution) {
            return redirect()
                ->route('portal.gate')
                ->withInput(['school_code' => $code])
                ->withErrors([
                    'school_code' => "Kode lembaga '{$code}' tidak ditemukan atau nonaktif.",
                ]);
        }

        $this->context->set($institution);

        return redirect()
            ->route('login')
            ->with('status', "Selamat datang di Portal {$institution->name}. Silakan masuk dengan akun Anda.");
    }

    /**
     * Clear the current institution session to switch to another school/institution.
     */
    public function exit(Request $request): RedirectResponse
    {
        $this->context->clear();

        return redirect()
            ->route('portal.gate')
            ->with('status', 'Sesi lembaga berhasil direset. Silakan masukkan kode lembaga yang baru.');
    }
}
