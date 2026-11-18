<?php

namespace Database\Seeders;

use App\Models\NavigationMenu;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Ensures the "Laporan Bulanan" menu exists and is accessible by admin/keuangan.
 * Safe to re-run on existing installs (uses updateOrCreate + role sync).
 */
class MonthlyReportMenuSeeder extends Seeder
{
    public function run(): void
    {
        $menu = NavigationMenu::query()->updateOrCreate(
            ['route' => '/reports/monthly'],
            [
                'name' => 'Laporan Bulanan',
                'icon' => 'Calendar',
                'sort_order' => 8,
                'is_active' => true,
                'parent_id' => null,
            ]
        );

        $roles = Role::whereIn('name', ['admin', 'keuangan'])->get();
        $menu->roles()->sync($roles->pluck('id')->toArray());

        $this->command->info('Laporan Bulanan menu ensured!');
    }
}