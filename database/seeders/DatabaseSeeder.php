<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with one development user per role.
     */
    public function run(): void
    {
        foreach (Role::cases() as $role) {
            User::query()->updateOrCreate(
                ['email' => "{$role->value}@example.com"],
                ['name' => $role->label(), 'password' => 'password', 'role' => $role],
            );
        }

        Setting::updatePpnDefault(Setting::FALLBACK_PPN);
    }
}
