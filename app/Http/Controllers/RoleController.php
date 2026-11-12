<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): Response
    {
        $roles = Role::with(['permissions' => function ($q) {
            $q->orderBy('name');
        }])->orderBy('name')->get();

        $permissions = Permission::orderBy('name')->get();

        return Inertia::render('admin/roles', [
            'roles' => $roles->map(fn (Role $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'permissions' => $r->permissions->pluck('name')->toArray(),
            ]),
            'permissions' => $permissions->pluck('name')->toArray(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role = Role::create(['name' => $data['name']]);

        if (! empty($data['permissions'] ?? [])) {
            $role->syncPermissions($data['permissions']);
        }

        return back()->with('flash', ['type' => 'success', 'message' => "Role {$data['name']} dibuat."]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        $data = $request->validate([
            'name' => "required|string|max:255|unique:roles,name,{$id},id",
            'permissions' => 'sometimes|array',
            'permissions.*' => 'exists:permissions,name',
        ]);

        $role->update(['name' => $data['name']]);

        $role->syncPermissions($data['permissions'] ?? []);

        return back()->with('flash', ['type' => 'success', 'message' => "Role {$data['name']} diperbarui."]);
    }

    public function destroy(int $id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'admin') {
            return back()->withErrors(['delete' => 'Role admin tidak dapat dihapus.']);
        }

        $role->delete();

        return back()->with('flash', ['type' => 'success', 'message' => 'Role dihapus.']);
    }
}