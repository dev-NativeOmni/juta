<?php

namespace App\Http\Controllers;

use App\Models\Institution;
use App\Services\AuditLogService;
use App\Services\InstitutionContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InstitutionController extends Controller
{
    public function __construct(
        private readonly InstitutionContext $context,
        private readonly AuditLogService $auditLog
    ) {}

    /**
     * Display a listing of the institutions.
     */
    public function index(Request $request): View
    {
        $query = Institution::query()
            ->withCount(['users', 'students', 'teachers', 'classRooms', 'programs']);

        if ($request->filled('search')) {
            $search = trim((string) $request->string('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $institutions = $query->orderBy('name')->paginate(15)->withQueryString();

        $stats = [
            'total' => Institution::count(),
            'active' => Institution::where('is_active', true)->count(),
            'inactive' => Institution::where('is_active', false)->count(),
            'total_students' => \App\Models\Student::withoutInstitution()->count(),
            'total_users' => \App\Models\User::count(),
        ];

        $activeInstitutionId = $this->context->id();

        return view('institutions.index', compact('institutions', 'stats', 'activeInstitutionId'));
    }

    /**
     * Show the form for creating a new institution.
     */
    public function create(): View
    {
        return view('institutions.create');
    }

    /**
     * Store a newly created institution in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'alpha_num', 'unique:institutions,code'],
            'slug' => ['nullable', 'string', 'max:100', 'alpha_dash', 'unique:institutions,slug'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'login_bg' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'landing_bg' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }
        $validated['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('institutions/logos', 'public');
            $validated['logo_path'] = $path;
        }

        if ($request->hasFile('login_bg')) {
            $path = $request->file('login_bg')->store('institutions/backgrounds', 'public');
            $validated['login_bg'] = $path;
        }

        if ($request->hasFile('landing_bg')) {
            $path = $request->file('landing_bg')->store('institutions/backgrounds', 'public');
            $validated['landing_bg'] = $path;
        }

        unset($validated['logo']);

        $settings = $institution->settings ?? [];
        $featureSettings = [];
        foreach (Institution::AVAILABLE_FEATURES as $key => $meta) {
            $featureSettings[$key] = $request->boolean("features.{$key}", false);
        }
        $settings['features'] = $featureSettings;
        $validated['settings'] = $settings;

        $institution = Institution::create($validated);

        $this->auditLog->logAction(
            'created',
            "Membuat lembaga baru: {$institution->name} ({$institution->code})"
        );

        return redirect()->route('institutions.index')
            ->with('status', "Lembaga {$institution->name} berhasil ditambahkan!");
    }

    /**
     * Display the specified institution.
     */
    public function show(Institution $institution): RedirectResponse
    {
        return redirect()->route('institutions.edit', $institution);
    }

    /**
     * Show the form for editing the specified institution.
     */
    public function edit(Institution $institution): View
    {
        $institution->loadCount(['users', 'students', 'teachers', 'classRooms', 'programs']);

        return view('institutions.edit', compact('institution'));
    }

    /**
     * Update the specified institution in storage.
     */
    public function update(Request $request, Institution $institution): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_num',
                Rule::unique('institutions', 'code')->ignore($institution->id),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('institutions', 'slug')->ignore($institution->id),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'login_bg' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'landing_bg' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }
        $validated['is_active'] = $request->boolean('is_active', true);

        if ($request->hasFile('logo')) {
            if ($institution->logo_path && Storage::disk('public')->exists($institution->logo_path)) {
                Storage::disk('public')->delete($institution->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('institutions/logos', 'public');
        }

        if ($request->hasFile('login_bg')) {
            if ($institution->login_bg && Storage::disk('public')->exists($institution->login_bg)) {
                Storage::disk('public')->delete($institution->login_bg);
            }
            $validated['login_bg'] = $request->file('login_bg')->store('institutions/backgrounds', 'public');
        }

        if ($request->hasFile('landing_bg')) {
            if ($institution->landing_bg && Storage::disk('public')->exists($institution->landing_bg)) {
                Storage::disk('public')->delete($institution->landing_bg);
            }
            $validated['landing_bg'] = $request->file('landing_bg')->store('institutions/backgrounds', 'public');
        }

        unset($validated['logo']);

        $settings = $institution->settings ?? [];
        $featureSettings = [];
        foreach (Institution::AVAILABLE_FEATURES as $key => $meta) {
            $featureSettings[$key] = $request->boolean("features.{$key}", false);
        }
        $settings['features'] = $featureSettings;
        $validated['settings'] = $settings;

        $institution->update($validated);

        $this->auditLog->logAction(
            'updated',
            "Memperbarui lembaga: {$institution->name} ({$institution->code})"
        );

        return redirect()->route('institutions.index')
            ->with('status', "Lembaga {$institution->name} berhasil diperbarui!");
    }

    /**
     * Remove the specified institution from storage.
     */
    public function destroy(Institution $institution): RedirectResponse
    {
        $hasData = $institution->students()->exists()
            || $institution->classRooms()->exists()
            || $institution->teachers()->exists();

        if ($hasData) {
            $institution->update(['is_active' => false]);
            $msg = "Lembaga {$institution->name} memiliki data santri/kelas aktif sehingga dinonaktifkan (bukan dihapus permanen).";
        } else {
            $institution->delete();
            $msg = "Lembaga {$institution->name} berhasil dihapus!";
        }

        if ($this->context->id() === $institution->id) {
            $this->context->clear();
        }

        $this->auditLog->logAction('deleted', "Menghapus/menonaktifkan lembaga: {$institution->name}");

        return redirect()->route('institutions.index')->with('status', $msg);
    }

    /**
     * Switch active institution context for super admin.
     */
    public function switch(Request $request): RedirectResponse
    {
        $institutionId = $request->input('institution_id');

        if ($institutionId === 'global' || empty($institutionId)) {
            $this->context->clear();

            return back()->with('status', 'Beralih ke Tampilan Global (Semua Lembaga).');
        }

        $institution = Institution::query()->findOrFail((int) $institutionId);

        if (! $institution->is_active) {
            return back()->with('error', "Lembaga {$institution->name} sedang nonaktif.");
        }

        $this->context->set($institution);

        return back()->with('status', "Konteks aktif beralih ke lembaga: {$institution->name} ({$institution->code})");
    }
}
