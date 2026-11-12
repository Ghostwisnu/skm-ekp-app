<?php

namespace App\Http\Controllers;

use App\Models\NavigationMenu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class NavigationMenuController extends Controller
{
    public function index(): Response
    {
        $menus = NavigationMenu::with(['children.roles', 'roles'])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('admin/navigation-menus', [
            'menus' => $this->buildTree($menus),
            'roles' => Role::all(['id', 'name'])->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'label' => ucfirst($r->name),
            ])->values(),
        ]);
    }

    private function buildTree(NavigationMenu $root): array
    {
        return [
            'id' => $root->id,
            'name' => $root->name,
            'route' => $root->route,
            'icon' => $root->icon,
            'sort_order' => $root->sort_order,
            'is_active' => $root->is_active,
            'role_ids' => $root->roles->pluck('id')->toArray(),
            'children' => $root->children->map(fn (NavigationMenu $child) => $this->buildChild($child))->values()->toArray(),
        ];
    }

    private function buildChild(NavigationMenu $menu): array
    {
        return [
            'id' => $menu->id,
            'name' => $menu->name,
            'route' => $menu->route,
            'icon' => $menu->icon,
            'sort_order' => $menu->sort_order,
            'is_active' => $menu->is_active,
            'role_ids' => $menu->roles->pluck('id')->toArray(),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'route' => 'nullable|string|max:255|different:name',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'parent_id' => 'nullable|exists:navigation_menus,id',
            'role_ids' => 'sometimes|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $menu = NavigationMenu::create([
            'name' => $data['name'],
            'route' => $data['route'] ?? null,
            'icon' => $data['icon'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        $menu->roles()->sync($data['role_ids'] ?? []);

        return back();
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $menu = NavigationMenu::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'route' => 'nullable|string|max:255|different:name',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'parent_id' => 'nullable|exists:navigation_menus,id',
            'role_ids' => 'sometimes|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        $menu->update([
            'name' => $data['name'],
            'route' => $data['route'] ?? null,
            'icon' => $data['icon'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        $menu->roles()->sync($data['role_ids'] ?? []);

        return back();
    }

    public function destroy(int $id): RedirectResponse
    {
        $menu = NavigationMenu::findOrFail($id);

        // Prevent deleting if has children
        if ($menu->children()->exists()) {
            return back()->withErrors(['delete' => 'Menu memiliki submenu. Hapus submenu terlebih dahulu.']);
        }

        $menu->delete();

        return back();
    }
}