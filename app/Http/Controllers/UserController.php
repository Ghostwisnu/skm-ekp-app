<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::with('roles')->get(['id', 'name', 'email']);
        $roles = Role::all();

        return Inertia::render('admin/users', [
            'users' => $users,
            'roles' => $roles->map(fn ($role) => ['value' => $role->name, 'label' => ucfirst($role->name)]),
            'ppnDefault' => Setting::ppnDefault(),
            'companyDetails' => Setting::companyDetails(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

        // Assign role
        $role = Role::findByName($data['role']);
        if ($role) {
            $user->assignRole($role);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'User ditambahkan.']);

        return back();
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = bcrypt($data['password']);
        }

        $user->update($updateData);

        // Sync role
        $role = Role::findByName($data['role']);
        if ($role) {
            $user->syncRoles([$role]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'User diperbarui.']);

        return back();
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Anda tidak dapat menghapus akun sendiri.']);

            return back();
        }

        $user->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'User dihapus.']);

        return back();
    }
}
