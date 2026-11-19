<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Model::preventLazyLoading(false);

        $this->call([
            RoleSeeder::class,
            NavigationMenuSeeder::class,
            MonthlyReportMenuSeeder::class,
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
            $user = User::updateOrCreate(
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
