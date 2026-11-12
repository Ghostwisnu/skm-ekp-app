<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role;

#[ObservedBy([\App\Observers\NavigationMenuObserver::class])]
class NavigationMenu extends Model
{
    /** @use HasFactory<\Database\Factories\NavigationMenuFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'route',
        'icon',
        'sort_order',
        'is_active',
        'parent_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(NavigationMenu::class, 'parent_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_navigation_menu');
    }

    public function canAccess(string $role): bool
    {
        if ($this->roles()->where('roles.name', $role)->exists()) {
            return true;
        }

        // Parent menus without route → visible if any child is accessible
        if ($this->route === null && $this->children()->whereRelation('roles', 'roles.name', $role)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Get all accessible menu items for a user as a flat array (no nesting).
     */
    public static function accessibleMenus(User $user): \Illuminate\Support\Collection
    {
        $role = $user->getRoleNames()->first();
        if (!$role) {
            return collect();
        }

        return self::query()
            ->where('is_active', true)
            ->with(['roles'])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn(self $menu) => $menu->canAccess($role))
            ->map(fn(self $menu) => [
                'id'          => $menu->id,
                'name'        => $menu->name,
                'route'       => $menu->route,
                'icon'        => $menu->icon,
                'sortOrder'   => $menu->sort_order,
                'isActive'    => $menu->is_active,
                'children'    => collect(),
            ]);
    }
}
