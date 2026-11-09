<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\UserRequest;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/users', [
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email', 'role']),
            'roles' => array_map(fn (Role $role): array => ['value' => $role->value, 'label' => $role->label()], Role::cases()),
            'ppnDefault' => Setting::ppnDefault(),
            'companyDetails' => Setting::companyDetails(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        User::query()->create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'User ditambahkan.']);

        return back();
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);
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
