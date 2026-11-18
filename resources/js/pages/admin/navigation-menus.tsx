import { useForm, usePage, router } from '@inertiajs/react';
import { useState } from 'react';
import * as Lucide from 'lucide-react';
import { confirmDelete } from '@/components/confirm-delete';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Toaster } from '@/components/ui/sonner';
import { store, update, destroy, seedDefaults, reseedDefaults } from '@/routes/navigation/menus';
import type { NavMenuItem, RoleOption } from '@/types/domain';

type MenuForm = {
    id?: number;
    name: string;
    route: string;
    icon: string;
    sort_order: number;
    is_active: boolean;
    role_ids: number[];
    parent_id: number | null;
};

const ICON_OPTIONS = [
    'LayoutGrid', 'Truck', 'Wallet', 'Building2', 'Receipt',
    'BarChart3', 'Settings', 'Users', 'Calendar', 'FileText',
    'Package', 'MapPin', 'Clock', 'Database', 'Shield',
    'Plus', 'Minus', 'Edit', 'Trash2', 'Search', 'Filter',
    'Download', 'Upload', 'RefreshCw', 'CheckCircle', 'AlertCircle',
];

// ── Dialog component ────────────────────────────────────────────────────────
function MenuDialog({
    menu,
    roles,
    parentId,
    onClose,
}: {
    menu: NavMenuItem | null;
    roles: RoleOption[];
    parentId?: number;
    onClose: () => void;
}) {
    const isError = (key: string): string | undefined =>
        (form.errors as Record<string, string | undefined>)[key];

    const form = useForm<MenuForm>({
        id: menu?.id,
        name: menu?.name ?? '',
        route: menu?.route ?? '',
        icon: menu?.icon ?? '',
        sort_order: menu?.sort_order ?? 0,
        is_active: menu?.is_active ?? true,
        role_ids: menu?.role_ids?.length ? [...menu.role_ids] : [],
        parent_id: parentId ?? null,
    });

    const toggleRole = (roleId: number) => {
        const current = form.data.role_ids;
        form.setData('role_ids', current.includes(roleId)
            ? current.filter((r) => r !== roleId)
            : [...current, roleId],
        );
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };

        if (form.data.id) {
            form.put(update.url(form.data.id), options);
        } else {
            form.post(store.url(), options);
        }
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-w-lg">
                <DialogHeader>
                    <DialogTitle>{menu ? 'Ubah Menu' : 'Tambah Menu'}</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    {/* Name & Route */}
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Nama Menu *</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                placeholder="Contoh: Dashboard"
                                autoFocus
                            />
                            {isError('name') && <p className="text-xs text-destructive">{isError('name')}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="route">Route (kosongkan jika parent)</Label>
                            <Input
                                id="route"
                                value={form.data.route}
                                onChange={(e) => form.setData('route', e.target.value)}
                                placeholder="/dashboard"
                            />
                            {isError('route') && <p className="text-xs text-destructive">{isError('route')}</p>}
                        </div>
                    </div>

                    {/* Icon & Sort */}
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="icon">Icon (Lucide)</Label>
                            <select
                                id="icon"
                                className="flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-sm transition-colors"
                                value={form.data.icon}
                                onChange={(e) => form.setData('icon', e.target.value)}
                            >
                                <option value="">-- Pilih --</option>
                                {ICON_OPTIONS.map((ic) => (
                                    <option key={ic} value={ic}>{ic}</option>
                                ))}
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="sort">Urutan (sort)</Label>
                            <Input
                                id="sort"
                                type="number"
                                min="0"
                                value={form.data.sort_order}
                                onChange={(e) => form.setData('sort_order', Number(e.target.value))}
                            />
                        </div>
                    </div>

                    {/* Active checkbox */}
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="is_active"
                            checked={form.data.is_active}
                            onCheckedChange={(v) => form.setData('is_active', v as boolean)}
                        />
                        <Label htmlFor="is_active" className="text-sm cursor-pointer">Aktif</Label>
                    </div>

                    {/* Role checkboxes */}
                    <div className="space-y-2">
                        <Label>Akses Role</Label>
                        <div className="flex flex-wrap gap-4">
                            {roles.map((role) => (
                                <div key={role.id} className="flex items-center gap-2">
                                    <Checkbox
                                        id={`role-${role.id}`}
                                        checked={form.data.role_ids.includes(role.id)}
                                        onCheckedChange={() => toggleRole(role.id)}
                                    />
                                    <Label htmlFor={`role-${role.id}`} className="text-sm cursor-pointer">
                                        {role.label}
                                    </Label>
                                </div>
                            ))}
                        </div>
                        {(isError('role_ids') || (!form.data.role_ids.length && form.processing === false)) && (
                            <p className="text-xs text-destructive">Pilih minimal satu role.</p>
                        )}
                    </div>

                    {/* Errors global */}
                    {isError('delete') && <p className="text-xs text-destructive">{isError('delete')}</p>}

                    <div className="flex justify-end gap-2 pt-2">
                        <Button variant="outline" type="button" onClick={onClose}>Batal</Button>
                        <Button type="submit" disabled={form.processing}>Simpan</Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}

// ── Main page ───────────────────────────────────────────────────────────────
export default function NavigationMenus({
    menus,
    roles,
}: {
    menus: NavMenuItem[];
    roles: RoleOption[];
}) {
    const basePath = (usePage<{ props: { basePath: string } }>().props as any).basePath ?? '';
    const [activeTab, setActiveTab] = useState<'menus' | 'roles'>('menus');
    const [dialog, setDialog] = useState<{ menu: NavMenuItem | null; parentId?: number } | null>(null);

    const flattenAll = (items: NavMenuItem[]): NavMenuItem[] =>
        items.flatMap((item) => [item, ...(item.children ? flattenAll(item.children) : [])]);

    const allMenus = flattenAll(menus);

    const openCreate = (parentId?: number) => {
        setDialog({ menu: null, parentId });
    };

    const openEdit = (menu: NavMenuItem) => {
        setDialog({ menu });
    };

    const handleDeleted = () => {
        window.location.reload();
    };

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Manajemen Menu & Akses Role</h1>
                    <p className="text-sm text-muted-foreground">Atur tampilan sidebar dan hak akses per role.</p>
                </div>
                <div className="flex gap-2">
                    {allMenus.length === 0 ? (
                        <form method="POST" action={`${basePath}/admin/navigation-menus/seed-defaults`}>
                            <input type="hidden" name="_token" value={(document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''} />
                            <Button type="submit" variant="outline">🌱 Seed Menu Default</Button>
                        </form>
                    ) : (
                        <form method="POST" action={`${basePath}/admin/navigation-menus/reseed-defaults`}>
                            <input type="hidden" name="_token" value={(document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''} />
                            <Button type="submit" variant="outline" size="sm">🔄 Re-seed Menu</Button>
                        </form>
                    )}
                </div>
            </div>

            <Tabs value={activeTab} onValueChange={(v) => setActiveTab(v as 'menus' | 'roles')} className="space-y-4">
                <TabsList>
                    <TabsTrigger value="menus">Menu Sidebar</TabsTrigger>
                    <TabsTrigger value="roles">Akses Role</TabsTrigger>
                </TabsList>

                {/* ===== TAB: MENU SIDEBAR ===== */}
                <TabsContent value="menus" className="space-y-4">
                    <Button onClick={() => openCreate()}>+ Tambah Menu Utama</Button>

                    {/* Menu List */}
                    <div className="space-y-2">
                        {menus.map((menu) => (
                            <div key={menu.id} className="rounded-lg border bg-card p-3">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <span className="text-lg">{menu.icon && getIconComponent(menu.icon)}</span>
                                        <div>
                                            <span className="font-medium">{menu.name}</span>
                                            {menu.route && (
                                                <span className="ml-2 text-xs text-muted-foreground font-mono">{menu.route}</span>
                                            )}
                                        </div>
                                        {!menu.is_active && <Badge variant="destructive" className="ml-2">Nonaktif</Badge>}
                                        <Badge variant="outline" className="ml-2">{formatRoles(menu.role_ids, roles)}</Badge>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Button variant="ghost" size="sm" onClick={() => openCreate(menu.id)}>+ Submenu</Button>
                                        <Button variant="ghost" size="sm" onClick={() => openEdit(menu)}>Ubah</Button>
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onClick={() =>
                                                confirmDelete(
                                                    destroy.url(menu.id),
                                                    `Hapus menu "${menu.name}"?`,
                                                    handleDeleted,
                                                )
                                            }
                                        >
                                            Hapus
                                        </Button>
                                    </div>
                                </div>
                                {menu.children && menu.children.length > 0 && (
                                    <div className="mt-2 ml-8 space-y-1 border-l-2 border-muted pl-3">
                                        {menu.children.map((child) => (
                                            <div key={child.id} className="flex items-center justify-between py-1">
                                                <div className="flex items-center gap-2 text-sm">
                                                    {child.icon && getIconComponent(child.icon)}
                                                    <span>{child.name}</span>
                                                    {child.route && <span className="text-xs text-muted-foreground font-mono">{child.route}</span>}
                                                    {!child.is_active && <Badge variant="destructive" className="text-[10px]">Nonaktif</Badge>}
                                                    <Badge variant="outline" className="text-[10px]">{formatRoles(child.role_ids, roles)}</Badge>
                                                </div>
                                                <div className="flex items-center gap-1">
                                                    <Button variant="ghost" size="sm" className="h-7 text-xs" onClick={() => openEdit(child)}>Ubah</Button>
                                                    <Button
                                                        variant="destructive"
                                                        size="sm"
                                                        className="h-7 text-xs"
                                                        onClick={() =>
                                                            confirmDelete(
                                                                destroy.url(child.id),
                                                                `Hapus submenu "${child.name}"?`,
                                                                handleDeleted,
                                                            )
                                                        }
                                                    >
                                                        Hapus
                                                    </Button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                </TabsContent>

                {/* ===== TAB: ROLE ACCESS ===== */}
                <TabsContent value="roles" className="space-y-4">
                    <p className="text-sm text-muted-foreground">
                        Atur role yang dapat mengakses setiap menu. Role yang tidak memiliki akses ke menu mana pun tidak akan melihat sidebar menu tersebut.
                    </p>
                    <div className="rounded-lg border bg-card">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/50">
                                    <th className="px-4 py-2 text-left font-medium">Menu</th>
                                    {roles.map((role) => (
                                        <th key={role.id} className="px-4 py-2 text-center font-medium">{role.label}</th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {allMenus.map((menu) => (
                                    <tr key={menu.id} className="border-b last:border-0">
                                        <td className="px-4 py-2 font-medium">{menu.name}</td>
                                        {roles.map((role) => (
                                            <td key={role.id} className="px-4 py-2 text-center">
                                                <Checkbox
                                                    checked={menu.role_ids.includes(role.id)}
                                                    onCheckedChange={() => {
                                                        const next = menu.role_ids.includes(role.id)
                                                            ? menu.role_ids.filter((r) => r !== role.id)
                                                            : [...menu.role_ids, role.id];
                                                        router.put(update.url(menu.id), {
                                                            ...menu,
                                                            role_ids: next,
                                                        }, { preserveScroll: true });
                                                    }}
                                                />
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </TabsContent>
            </Tabs>

            {dialog && (
                <MenuDialog
                    key={dialog.menu?.id ?? 'new'}
                    menu={dialog.menu}
                    roles={roles}
                    parentId={dialog.parentId}
                    onClose={() => setDialog(null)}
                />
            )}

            <Toaster />
        </div>
    );
}

// ── Helpers ─────────────────────────────────────────────────────────────────
function getIconComponent(name: string) {
    const Icon = (Lucide as Record<string, React.ElementType>)[name];
    return Icon ? <Icon /> : null;
}

function formatRoles(roleIds: number[], roles: RoleOption[]): string {
    if (!roles.length) return '';
    return roles
        .filter((r) => roleIds.includes(r.id))
        .map((r) => r.label)
        .join(', ') || '-';
}
