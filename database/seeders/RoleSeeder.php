<?php

namespace Database\Seeders;

use Illuminate\Database\Seeders;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['admin', 'operator', 'keuangan'];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r]);
        }

        // Default permissions
        $permissions = [
            'dashboard.view',
            'trips.view', 'trips.create', 'trips.edit', 'trips.delete',
            'daily-costs.view', 'daily-costs.create', 'daily-costs.edit', 'daily-costs.delete',
            'clients.view', 'clients.create', 'clients.edit', 'clients.delete',
            'vehicles.view', 'vehicles.create', 'vehicles.edit', 'vehicles.delete',
            'invoices.view', 'invoices.create', 'invoices.edit', 'invoices.delete', 'invoices.finalize',
            'reports.view',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'settings.view', 'settings.edit',
            'navigation-menus.view', 'navigation-menus.create', 'navigation-menus.edit', 'navigation-menus.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Assign permissions to roles
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $adminRole->syncPermissions(Permission::all());
        }

        $operatorRole = Role::where('name', 'operator')->first();
        if ($operatorRole) {
            $operatorRole->syncPermissions([
                'dashboard.view',
                'trips.view', 'trips.create', 'trips.edit',
                'daily-costs.view', 'daily-costs.create', 'daily-costs.edit',
                'clients.view', 'clients.create', 'clients.edit',
                'vehicles.view', 'vehicles.create', 'vehicles.edit',
            ]);
        }

        $keuanganRole = Role::where('name', 'keuangan')->first();
        if ($keuanganRole) {
            $keuanganRole->syncPermissions([
                'dashboard.view',
                'clients.view', 'vehicles.view',
                'invoices.view', 'invoices.create', 'invoices.finalize',
                'reports.view',
            ]);
        }

        $this->command->info('Roles and permissions seeded successfully!');
    }
}