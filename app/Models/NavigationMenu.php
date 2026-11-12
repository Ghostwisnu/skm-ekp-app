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

    /**
     * @return BelongsTo<NavigationMenu, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'parent_id');
    }

    /**
     * @return HasMany<NavigationMenu, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(NavigationMenu::class, 'parent_id');
    }

    /**
     * @return BelongsToMany<Role>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_navigation_menu');
    }

    /**
     * Check if menu is accessible by the given role.
     */
    public function canAccess(string $role): bool
    {
        if ($this->roles()->where('roles.name', $role)->exists()) {
            return true;
        }

        // Parent menus without specific role restriction are visible to all allowed roles
        if ($this->route === null && $this->children()->whereRelation('roles', 'roles.name', $role)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Get all accessible menu items for a user, built as a flat array with hierarchy.
     *
     * @return \Illuminate\Support\Collection<int, array{id: int, name: string, route: ?string, icon: ?string, sortOrder: int, isActive: bool, children: \Illuminate\Support\Collection}>
     */
    public static function accessibleMenus(\App\Models\User $user): \Illuminate\Support\Collection
    {
        $role = $user->getRoleNames()->first();
        if (! $role) {
            return collect();
        }

        return self::query()
            ->where('is_active', true)
            ->with(['children' => function ($q) use ($role) {
                $q->where('is_active', true)
                    ->orderBy('sort_order')
                    ->whereHas('roles', fn ($qr) => $qr->where('name', $role));
            }, 'roles'])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (self $menu) => $menu->canAccess($role))
            ->map(fn (self $menu) => [
                'id' => $menu->id,
                'name' => $menu->name,
                'route' => $menu->route,
                'icon' => $menu->icon,
                'sortOrder' => $menu->sort_order,
                'isActive' => $menu->is_active,
                'children' => $menu->children->map(fn (self $child) => [
                    'id' => $child->id,
                    'name' => $child->name,
                    'route' => $child->route,
                    'icon' => $child->icon,
                    'sortOrder' => $child->sort_order,
                    'isActive' => $child->is_active,
                ]),
            ]);
    }
}