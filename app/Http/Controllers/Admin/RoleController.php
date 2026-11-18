<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.roles.index', [
            'roles' => Role::withCount('users')->orderBy('is_system', 'desc')->orderBy('name')->get(),
            'permissions' => Role::PERMISSIONS,
            'assignablePermissions' => $this->assignablePermissions($request->user()),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.roles.form', [
            'editing' => null,
            'permissions' => Role::PERMISSIONS,
            'assignablePermissions' => $this->assignablePermissions($request->user()),
        ]);
    }

    public function edit(Request $request, Role $role): View
    {
        return view('admin.roles.form', [
            'editing' => $role,
            'permissions' => Role::PERMISSIONS,
            'assignablePermissions' => $this->assignablePermissions($request->user()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['permissions'] = $this->permissionPayload($request);
        $data['is_system'] = false;

        $role = Role::create($data);

        AuditLogger::log('role.created', $role, [
            'name' => $role->name,
            'permissions' => $role->permissions,
        ]);

        return redirect()->route('admin.roles.index')->with('status', 'Peran '.$role->name.' berhasil dibuat.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($role->is_system && ! $request->user()->isSuperadmin()) {
            abort(403, 'Peran sistem hanya dapat diubah oleh superadmin.');
        }

        $data = $this->validated($request, $role);

        if (! $role->is_system) {
            $data['permissions'] = $this->permissionPayload($request);
        } else {
            unset($data['permissions']);
        }

        $role->update($data);

        AuditLogger::log('role.updated', $role, [
            'name' => $role->name,
            'permissions' => $role->permissions,
        ]);

        return redirect()->route('admin.roles.index')->with('status', 'Peran '.$role->name.' berhasil diperbarui.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        if ($role->is_system) {
            return back()->withErrors(['role' => 'Peran sistem tidak dapat dihapus.']);
        }

        if ($role->users()->exists()) {
            return back()->withErrors(['role' => 'Peran masih dipakai oleh anggota. Pindahkan anggota terlebih dahulu.']);
        }

        AuditLogger::log('role.deleted', $role, ['name' => $role->name]);
        $role->delete();

        return redirect()->route('admin.roles.index')->with('status', 'Peran berhasil dihapus.');
    }

    private function validated(Request $request, ?Role $role = null): array
    {
        $request->merge([
            'slug' => $request->filled('slug')
                ? Str::slug($request->input('slug'))
                : Str::slug($request->input('name', '')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'max:80', Rule::unique('roles', 'slug')->ignore($role?->id)],
            'description' => ['nullable', 'string', 'max:200'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(array_keys(Role::PERMISSIONS))],
        ]);

        if ($role?->is_system) {
            unset($data['slug']);
        }

        return $data;
    }

    private function permissionPayload(Request $request): array
    {
        $permissions = array_values($request->input('permissions', []));
        $allowed = $this->assignablePermissions($request->user());

        foreach ($permissions as $permission) {
            if (! in_array($permission, $allowed, true)) {
                throw ValidationException::withMessages([
                    'permissions' => 'Kamu tidak dapat memberikan hak akses yang tidak kamu miliki.',
                ]);
            }
        }

        return $permissions;
    }

    private function assignablePermissions($user): array
    {
        if ($user->isSuperadmin()) {
            return array_keys(Role::PERMISSIONS);
        }

        return $user->role?->permissions ?? [];
    }
}
