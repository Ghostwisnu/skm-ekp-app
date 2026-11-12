<?php

namespace Database\Seeders;

use App\Models\NavigationMenu;
use Illuminate\Database\Seeders;
use Spatie\Permission\Models\Role;

class NavigationMenuSeeder extends Seeder
{
    public function run(): void
    {
        // Only seed if no menus exist yet (idempotent)
        if (NavigationMenu::count() > 0) {
            $this->command->info('Navigation menus already exist, skipping.');
            return;
        }

        $menus = [
            ['name' => 'Dashboard', 'route' => '/dashboard', 'icon' => 'LayoutGrid', 'sort_order' => 1, 'roles' => ['admin', 'operator', 'keuangan']],
            ['name' => 'Trip', 'route' => '/trips', 'icon' => 'Truck', 'sort_order' => 2, 'roles' => ['admin', 'operator']],
            ['name' => 'Biaya Harian', 'route' => '/daily-costs', 'icon' => 'Wallet', 'sort_order' => 3, 'roles' => ['admin', 'operator']],
            ['name' => 'Client', 'route' => '/clients', 'icon' => 'Building2', 'sort_order' => 4, 'roles' => ['admin', 'operator', 'keuangan']],
            ['name' => 'Kendaraan', 'route' => '/vehicles', 'icon' => 'Truck', 'sort_order' => 5, 'roles' => ['admin', 'operator', 'keuangan']],
            ['name' => 'Invoice', 'route' => '/invoices', 'icon' => 'Receipt', 'sort_order' => 6, 'roles' => ['admin', 'keuangan']],
            ['name' => 'Laporan Plat', 'route' => '/reports/vehicles', 'icon' => 'BarChart3', 'sort_order' => 7, 'roles' => ['admin', 'keuangan']],
            ['name' => 'Admin', 'route' => null, 'icon' => 'Settings', 'sort_order' => 8, 'roles' => ['admin']],
            ['name' => 'Pengguna & Pengaturan', 'route' => '/admin/users', 'icon' => null, 'sort_order' => 1, 'parent_route' => 'admin', 'roles' => ['admin']],
        ];

        $created = [];
        foreach ($menus as $menuData) {
            $menu = NavigationMenu::create([
                'name' => $menuData['name'],
                'route' => $menuData['route'],
                'icon' => $menuData['icon'],
                'sort_order' => $menuData['sort_order'],
                'is_active' => true,
                'parent_id' => null,
            ]);

            if (isset($menuData['parent_route'])) {
                $parent = $created[$menuData['parent_route']] ?? null;
                if ($parent) {
                    $menu->update(['parent_id' => $parent->id]);
                }
            }

            $created[$menuData['name']] = $menu;

            // Sync roles
            $roles = Role::whereIn('name', $menuData['roles'])->get();
            $menu->roles()->sync($roles->pluck('id')->toArray());
        }

        $this->command->info('Navigation menus seeded successfully!');
    }
}