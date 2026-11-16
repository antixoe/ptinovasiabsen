<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Level;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'integer', 'exists:roles,id'],
            'level' => ['nullable', 'integer', 'exists:levels,id'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $users = User::query()
            ->with(['role', 'level', 'mentor'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $query->where(function ($inner) use ($request) {
                    $inner->where('name', 'like', '%'.$request->input('q').'%')
                        ->orWhere('email', 'like', '%'.$request->input('q').'%')
                        ->orWhere('department', 'like', '%'.$request->input('q').'%');
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role_id', $request->integer('role')))
            ->when($request->filled('level'), fn ($query) => $query->where('level_id', $request->integer('level')))
            ->when($request->input('status') === 'active', fn ($query) => $query->where('is_active', true))
            ->when($request->input('status') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::orderBy('name')->get(),
            'levels' => Level::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User(['is_active' => true]),
            'roles' => Role::orderBy('name')->get(),
            'levels' => Level::orderBy('name')->get(),
            'mentors' => $this->mentors(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->assertRoleAssignable($request->user(), Role::findOrFail($data['role_id']));
        $data['password'] = $request->input('password');
        $data['email_verified_at'] = now();

        $user = User::create($data);

        AuditLogger::log('user.created', $user, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->name,
        ]);

        return redirect()->route('admin.users.index')->with('status', 'Anggota '.$user->name.' berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
            'levels' => Level::orderBy('name')->get(),
            'mentors' => $this->mentors(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $data = $this->validated($request, $user);
        $this->assertRoleAssignable($actor, Role::findOrFail($data['role_id']));

        if ($user->id === $actor->id) {
            $data['role_id'] = $user->role_id;
            $data['level_id'] = $user->level_id;
            $data['is_active'] = true;
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if (isset($data['password'])) {
            $data['password'] = $request->input('password');
        }

        $wasSuperadmin = $user->isSuperadmin();
        $user->update($data);

        if ($wasSuperadmin && ! $user->fresh()->isSuperadmin() && User::whereHas('role', fn ($q) => $q->where('slug', 'superadmin'))->count() === 0) {
            $user->update(['role_id' => Role::where('slug', 'superadmin')->value('id')]);

            throw ValidationException::withMessages(['role_id' => 'Workspace harus memiliki minimal satu superadmin.']);
        }

        AuditLogger::log('user.updated', $user, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->name,
            'active' => $user->is_active,
        ]);

        return redirect()->route('admin.users.index')->with('status', 'Data anggota berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'Kamu tidak bisa menghapus akun sendiri.']);
        }

        if ($user->isSuperadmin() && User::whereHas('role', fn ($q) => $q->where('slug', 'superadmin'))->count() <= 1) {
            return back()->withErrors(['user' => 'Superadmin terakhir tidak boleh dihapus.']);
        }

        AuditLogger::log('user.deleted', $user, [
            'name' => $user->name,
            'email' => $user->email,
        ]);

        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'Anggota berhasil dihapus.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'level_id' => ['nullable', 'integer', 'exists:levels,id'],
            'mentor_id' => ['nullable', 'integer', 'exists:users,id'],
            'department' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        if (! empty($data['mentor_id'])) {
            $mentor = User::with('level')->find($data['mentor_id']);

            if (! $mentor?->isMentor()) {
                throw ValidationException::withMessages(['mentor_id' => 'Pembimbing harus memiliki level Pembimbing.']);
            }
        }

        return $data;
    }

    private function assertRoleAssignable(User $actor, Role $role): void
    {
        if ($actor->isSuperadmin()) {
            return;
        }

        if ($role->slug === 'superadmin') {
            throw ValidationException::withMessages(['role_id' => 'Hanya superadmin yang dapat menetapkan peran superadmin.']);
        }

        $actorPermissions = $actor->role?->permissions ?? [];

        foreach ($role->permissions ?? [] as $permission) {
            if (! in_array($permission, $actorPermissions, true)) {
                throw ValidationException::withMessages([
                    'role_id' => 'Kamu tidak dapat menetapkan peran dengan hak akses melebihi milikmu.',
                ]);
            }
        }
    }

    private function mentors()
    {
        return User::query()
            ->whereHas('level', fn ($query) => $query->where('slug', 'pembimbing'))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
