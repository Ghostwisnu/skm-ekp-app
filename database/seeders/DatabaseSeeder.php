<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user if not exists
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'password' => bcrypt('password'),
            ]
        );

        // Assign admin role using Spatie
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $admin->assignRole($adminRole);

        // Seed other test users
        foreach (['operator', 'keuangan'] as $roleName) {
            $user = User::updateOrCreate(
                ['email' => "{$roleName}@example.com"],
                [
                    'name' => ucfirst($roleName),
                    'password' => bcrypt('password'),
                ]
            );

            $role = Role::firstOrCreate(['name' => $roleName]);
            $user->assignRole($role);
        }
    }
}
