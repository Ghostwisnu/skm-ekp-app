<?php

namespace Database\Seeders;

use Illuminate\Database\Seeders;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'operator']);
        Role::firstOrCreate(['name' => 'keuangan']);

        $this->command->info('Roles seeded successfully!');
    }
}