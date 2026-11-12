<?php

namespace Database\Seeders;

use App\Models\NavigationMenu;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class NavigationMenuSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'admin')->first();
        $operatorRole = Role::where('name', 'operator')->first();
        $keuanganRole = Role::where('name', 'keuangan')->first();

        // Clear existing menus
        NavigationMenu::query()->delete();

        $menus = [
            // Dashboard - all roles
            ['name' => 'Dashboard', 'route' => '/dashboard', 'icon' => 'LayoutGrid', 'sort_order' => 1, 'roles' => ['admin', 'operator', 'keuangan']],
            // Trip
            ['name' => 'Trip', 'route' => '/trips', 'icon' => 'Truck', 'sort_order' => 2, 'roles' => ['admin', 'operator']],
            // Biaya Harian
            ['name' => 'Biaya Harian', 'route' => '/daily-costs', 'icon' => 'Wallet', 'sort_order' => 3, 'roles' => ['admin', 'operator']],
            // Client
            ['name' => 'Client', 'route' => '/clients', 'icon' => 'Building2', 'sort_order' => 4, 'roles' => ['admin', 'operator', 'keuangan']],
            // Kendaraan
            ['name' => 'Kendaraan', 'route' => '/vehicles', 'icon' => 'Truck', 'sort_order' => 5, 'roles' => ['admin', 'operator', 'keuangan']],
            // Invoice
            ['name' => 'Invoice', 'route' => '/invoices', 'icon' => 'Receipt', 'sort_order' => 6, 'roles' => ['admin', 'keuangan']],
            // Laporan
            ['name' => 'Laporan Plat', 'route' => '/reports/vehicles', 'icon' => 'BarChart3', 'sort_order' => 7, 'roles' => ['admin', 'keuangan']],
            // Admin section (parent)
            ['name' => 'Admin', 'route' => null, 'icon' => 'Settings', 'sort_order' => 8, 'roles' => ['admin']],
            // Admin sub-menus
            ['name' => 'Pengguna & Pengaturan', 'route' => '/admin/users', 'icon' => null, 'sort_order' => 1, 'parent_route' => 'admin', 'roles' => ['admin']],
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

            if (isset($menuData['parent_route'])) {
                $parent = NavigationMenu::where('route', $menuData['parent_route'])->first();
                if ($parent) {
                    $menu->update(['parent_id' => $parent->id]);
                }
            }

            // Sync roles
            $roleNames = $menuData['roles'];
            $roles = Role::whereIn('name', $roleNames)->get();
            $menu->roles()->sync($roles->pluck('id')->toArray());
        }

        $this->command->info('Navigation menus seeded successfully!');
    }
}