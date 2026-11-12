<?php

namespace App\Observers;

use App\Models\NavigationMenu;

class NavigationMenuObserver
{
    public function saved(NavigationMenu $menu): void
    {
        cache()->forget('navigation_menus_'.$menu->getAttribute('parent_id') ?? 'root');
    }

    public function deleted(NavigationMenu $menu): void
    {
        cache()->forget('navigation_menus_'.$menu->getAttribute('parent_id') ?? 'root');
    }
}