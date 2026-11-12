<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeders\DatabaseSeeder as BaseDatabaseSeeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends BaseDatabaseSeeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Model::preventLazyLoadinging(false);

        $this->call([
            RoleSeeder::class,
            NavigationMenuSeeder::class,
        ]);

        $this->seedUsers();
    }

    private function seedUsers(): void
    {
        $users = [
            ['name' => 'Administrator', 'email' => 'admin@example.com', 'roles' => ['admin']],
            ['name' => 'Operator', 'email' => 'operator@example.com', 'roles' => ['operator']],
            ['name' => 'Keuangan', 'email' => 'keuangan@example.com', 'roles' => ['keuangan']],
        ];

        foreach ($users as $userData) {
            $user = \App\Models\User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => bcrypt('password'),
                ]
            );

            foreach ($userData['roles'] as $roleName) {
                $role = Role::firstOrCreate(['name' => $roleName]);
                $user->syncRoles([$role]);
            }
        }
    }
}