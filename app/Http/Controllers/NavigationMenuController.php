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

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, NavigationMenu>  $menus
     * @return array<int, array{id: int, name: string, route: ?string, icon: ?string, sort_order: int, is_active: bool, role_ids: int[], children: array}>
     */
    private function buildTree($menus): array
    {
        return $menus->map(fn (NavigationMenu $root) => [
            'id' => $root->id,
            'name' => $root->name,
            'route' => $root->route,
            'icon' => $root->icon,
            'sort_order' => $root->sort_order,
            'is_active' => $root->is_active,
            'role_ids' => $root->roles->pluck('id')->toArray(),
            'children' => $root->children->map(fn (NavigationMenu $child) => $this->buildChild($child))->values()->toArray(),
        ])->values()->toArray();
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

    /**
     * Seed default menu data via web (no SSH needed).
     */
    public function seedDefaults(): RedirectResponse
    {
        if (NavigationMenu::count() > 0) {
            return back()->with('flash', ['type' => 'warning', 'message' => 'Menu sudah ada. Tidak dapat seed ulang.']);
        }

        $this->createDefaultMenus();

        return back()->with('flash', ['type' => 'success', 'message' => 'Menu default telah dibuat!']);
    }

    private function createDefaultMenus(): void
    {
        $menus = [
            ['name' => 'Dashboard', 'route' => '/dashboard', 'icon' => 'LayoutGrid', 'sort_order' => 1, 'roles' => ['admin', 'operator', 'keuangan']],
            ['name' => 'Trip', 'route' => '/trips', 'icon' => 'Truck', 'sort_order' => 2, 'roles' => ['admin', 'operator']],
            ['name' => 'Biaya Harian', 'route' => '/daily-costs', 'icon' => 'Wallet', 'sort_order' => 3, 'roles' => ['admin', 'operator']],
            ['name' => 'Client', 'route' => '/clients', 'icon' => 'Building2', 'sort_order' => 4, 'roles' => ['admin', 'operator', 'keuangan']],
            ['name' => 'Kendaraan', 'route' => '/vehicles', 'icon' => 'Truck', 'sort_order' => 5, 'roles' => ['admin', 'operator', 'keuangan']],
            ['name' => 'Invoice', 'route' => '/invoices', 'icon' => 'Receipt', 'sort_order' => 6, 'roles' => ['admin', 'keuangan']],
            ['name' => 'Laporan Plat', 'route' => '/reports/vehicles', 'icon' => 'BarChart3', 'sort_order' => 7, 'roles' => ['admin', 'keuangan']],
            ['name' => 'Admin', 'route' => '/admin/users', 'icon' => 'Settings', 'sort_order' => 8, 'roles' => ['admin']],
            ['name' => 'Pengguna & Pengaturan', 'route' => '/admin/users', 'icon' => null, 'sort_order' => 9, 'roles' => ['admin']],
            ['name' => 'Manajemen Menu', 'route' => '/admin/navigation-menus', 'icon' => null, 'sort_order' => 10, 'roles' => ['admin']],
            ['name' => 'Kelola Role', 'route' => '/admin/roles', 'icon' => null, 'sort_order' => 11, 'roles' => ['admin']],
        ];

        foreach ($menus as $menuData) {
            $menu = NavigationMenu::create([
                'name' => $menuData['name'],
                'route' => $menuData['route'],
                'icon' => $menuData['icon'],
                'sort_order' => $menuData['sort_order'],
                'is_active' => true,
                'parent_id' => null,
            ]);

            $roles = \Spatie\Permission\Models\Role::whereIn('name', $menuData['roles'])->get();
            $menu->roles()->sync($roles->pluck('id')->toArray());
        }
    }
}