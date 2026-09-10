<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\ParentProfile;
use App\Models\Role;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeSuperAdmin();

        $query = User::query()->with([
            'role',
            'roles',
            'studentProfile.classRoom',
            'studentProfile.parents.user',
            'parentProfile.students.classRoom',
            'parentProfile.students.user',
        ]);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhereHas('studentProfile.parents.user', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                           ->orWhere('username', 'like', "%{$search}%");
                    })
                    ->orWhereHas('parentProfile.students.user', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%")
                           ->orWhere('username', 'like', "%{$search}%");
                    })
                    ->orWhereHas('parentProfile.students', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->integer('role_id'));
        }

        if ($request->filled('class_room_id')) {
            $classRoomId = $request->integer('class_room_id');
            $query->whereHas('studentProfile', function ($q) use ($classRoomId) {
                $q->where('class_room_id', $classRoomId);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $users = $query->orderBy('name')->paginate(20)->withQueryString();
        $roles = Role::orderBy('display_name')->get();
        $classRooms = \App\Models\ClassRoom::orderBy('name')->get();

        $roleCounts = DB::table('users')
            ->select('role_id', DB::raw('count(*) as total'))
            ->groupBy('role_id')
            ->pluck('total', 'role_id')
            ->toArray();

        $totalUsers = User::count();
        $inactiveUsers = User::where('status', 'inactive')->count();
        $activeUsers = User::where('status', 'active')->count();

        $allParentProfiles = ParentProfile::with('user')->get()->sortBy(fn($p) => strtolower($p->user?->name ?? ''))->values();
        $allStudentProfiles = Student::with(['user', 'classRoom'])->get()->sortBy('name')->values();

        return view('users.index', compact(
            'users',
            'roles',
            'classRooms',
            'roleCounts',
            'totalUsers',
            'inactiveUsers',
            'activeUsers',
            'allParentProfiles',
            'allStudentProfiles'
        ));
    }

    public function linkParents(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'parent_ids' => 'nullable|array',
            'parent_ids.*' => 'exists:parent_profiles,id',
        ]);

        $studentProfile = $user->studentProfile;
        if (! $studentProfile) {
            $studentProfile = Student::create([
                'user_id' => $user->id,
                'name' => $user->name,
            ]);
        }

        $parentIds = collect($validated['parent_ids'] ?? [])->filter()->unique()->values()->all();
        $studentProfile->parents()->sync($parentIds);

        return redirect()->back()->with('success', 'Relasi orang tua untuk murid "'.$user->name.'" berhasil diperbarui.');
    }

    public function linkStudents(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        $parentProfile = $user->parentProfile;
        if (! $parentProfile) {
            $parentProfile = ParentProfile::create([
                'user_id' => $user->id,
            ]);
        }

        $studentIds = collect($validated['student_ids'] ?? [])->filter()->unique()->values()->all();
        $parentProfile->students()->sync($studentIds);

        return redirect()->back()->with('success', 'Relasi murid/anak untuk orang tua "'.$user->name.'" berhasil diperbarui.');
    }

    public function edit(User $user): View
    {
        $this->authorizeSuperAdmin();

        $roles = Role::orderBy('display_name')->get();
        $allClassRooms = ClassRoom::with('program')->orderBy('name')->get();
        $user->loadMissing(['roles', 'pendampingClasses']);

        return view('users.edit', compact('user', 'roles', 'allClassRooms'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,'.$user->id,
            'role_id' => 'required|exists:roles,id',
            'additional_role_ids' => 'nullable|array',
            'additional_role_ids.*' => 'exists:roles,id',
            'pendamping_class_ids' => 'nullable|array',
            'pendamping_class_ids.*' => 'exists:class_rooms,id',
            'password' => 'nullable|string|min:6',
            'status' => 'required|in:active,inactive',
        ], [
            'username.unique' => 'Username ini sudah digunakan oleh pengguna lain. Silakan gunakan username yang berbeda.',
            'username.required' => 'Username tidak boleh kosong.',
        ]);

        DB::transaction(function () use ($validated, $user, $request) {
            $userData = [
                'name' => $validated['name'],
                'username' => $validated['username'],
                'role_id' => $validated['role_id'],
                'status' => $validated['status'],
            ];

            if (! empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
                $userData['plain_password'] = $validated['password'];
            }

            $user->update($userData);

            $allAssignedRoleIds = collect([$validated['role_id']])
                ->merge($validated['additional_role_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $user->roles()->sync($allAssignedRoleIds);

            $assignedRoles = Role::whereIn('id', $allAssignedRoleIds)->get();
            $assignedRoleNames = $assignedRoles->pluck('name')->all();

            if (in_array('teacher', $assignedRoleNames, true) && ! $user->teacherProfile()->exists()) {
                TeacherProfile::create(['user_id' => $user->id]);
            }
            if (in_array('parent', $assignedRoleNames, true) && ! $user->parentProfile()->exists()) {
                ParentProfile::create(['user_id' => $user->id]);
            }
            if (in_array('student', $assignedRoleNames, true) && ! $user->studentProfile()->exists()) {
                Student::create([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'status' => 'active',
                ]);
            }

            // Sync assigned pendamping classes if user has pendamping_adab role
            if (in_array('pendamping_adab', $assignedRoleNames, true)) {
                $classIds = collect($request->input('pendamping_class_ids', []))->map(fn ($id) => (int) $id)->unique()->values()->all();
                $user->pendampingClasses()->sync($classIds);
            } else {
                $user->pendampingClasses()->detach();
            }
        });

        return redirect()
            ->route('users.index')
            ->with('success', 'Data user '.$user->username.' berhasil diperbarui.');
    }

    public function create(): View
    {
        $this->authorizeSuperAdmin();

        $roles = Role::orderBy('display_name')->get();

        return view('users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'role_id' => 'required|exists:roles,id',
            'additional_role_ids' => 'nullable|array',
            'additional_role_ids.*' => 'exists:roles,id',
            'password' => 'required|string|min:6',
            'status' => 'required|in:active,inactive',
        ], [
            'username.unique' => 'Username ini sudah digunakan oleh pengguna lain. Silakan gunakan username yang berbeda.',
            'username.required' => 'Username tidak boleh kosong.',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'role_id' => $validated['role_id'],
                'password' => Hash::make($validated['password']),
                'plain_password' => $validated['password'],
                'status' => $validated['status'],
            ]);

            $allAssignedRoleIds = collect([$validated['role_id']])
                ->merge($validated['additional_role_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $user->roles()->sync($allAssignedRoleIds);

            $assignedRoles = Role::whereIn('id', $allAssignedRoleIds)->get();
            $assignedRoleNames = $assignedRoles->pluck('name')->all();

            if (in_array('teacher', $assignedRoleNames, true)) {
                TeacherProfile::create(['user_id' => $user->id]);
            }
            if (in_array('parent', $assignedRoleNames, true)) {
                ParentProfile::create(['user_id' => $user->id]);
            }
            if (in_array('student', $assignedRoleNames, true)) {
                Student::create([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'status' => 'active',
                ]);
            }
        });

        return redirect()
            ->route('users.index')
            ->with('success', 'User baru berhasil dibuat.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        if (auth()->id() === $user->id) {
            return redirect()
                ->route('users.index')
                ->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $username = $user->username;
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User '.$username.' berhasil dihapus.');
    }

    private function authorizeSuperAdmin(): void
    {
        if (! auth()->user()->hasRole('super_admin')) {
            abort(403, 'Aksi ini hanya dapat diakses oleh Super Admin.');
        }
    }
}
