import { useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import * as Lucide from 'lucide-react';
import { ConfirmDelete } from '@/components/confirm-delete-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Toaster } from '@/components/ui/sonner';
import type { NavMenuItem, RoleOption } from '@/types/domain';

type MenuForm = {
    id?: number;
    name: string;
    route: string;
    icon: string;
    sort_order: number;
    is_active: boolean;
    role_ids: number[];
    parent_id?: number | null;
};

const ICON_OPTIONS = [
    'LayoutGrid', 'Truck', 'Wallet', 'Building2', 'Receipt',
    'BarChart3', 'Settings', 'Users', 'Calendar', 'FileText',
    'Package', 'MapPin', 'Clock', 'Database', 'Shield',
    'Plus', 'Minus', 'Edit', 'Trash2', 'Search', 'Filter',
    'Download', 'Upload', 'RefreshCw', 'CheckCircle', 'AlertCircle',
];

export default function NavigationMenus({
    menus,
    roles,
}: {
    menus: NavMenuItem[];
    roles: RoleOption[];
}) {
    const basePath = (usePage<{ props: { basePath: string } }>().props as any).basePath ?? '';
    const [activeTab, setActiveTab] = useState<'menus' | 'roles'>('menus');
    const [editingMenu, setEditingMenu] = useState<NavMenuItem | null>(null);
    const [creatingParent, setCreatingParent] = useState(false);
    const [deleteTarget, setDeleteTarget] = useState<NavMenuItem | null>(null);

    const resetForm = (): MenuForm => ({
        name: '',
        route: '',
        icon: '',
        sort_order: 0,
        is_active: true,
        role_ids: [],
        parent_id: null,
    });

    const form = useForm<MenuForm>(editingMenu ? {
        id: editingMenu.id,
        name: editingMenu.name,
        route: editingMenu.route ?? '',
        icon: editingMenu.icon ?? '',
        sort_order: editingMenu.sort_order,
        is_active: editingMenu.is_active,
        role_ids: editingMenu.role_ids,
        parent_id: editingMenu.children?.length ? null : null,
    } : resetForm());

    const openEdit = (menu: NavMenuItem) => {
        setEditingMenu(menu);
        form.reset();
        form.setData({
            id: menu.id,
            name: menu.name,
            route: menu.route ?? '',
            icon: menu.icon ?? '',
            sort_order: menu.sort_order,
            is_active: menu.is_active,
            role_ids: [...menu.role_ids],
            parent_id: null,
        });
    };

    const openCreate = (parentId?: number) => {
        setEditingMenu(null);
        form.reset();
        const defaults: Partial<MenuForm> = parentId
            ? { parent_id: parentId, name: '', route: '', icon: '', sort_order: 0, is_active: true, role_ids: [] }
            : resetForm();
        form.setData(defaults as MenuForm);
    };

    const submit = () => {
        if (form.data.id) {
            form.put(`/admin/navigation-menus/${form.data.id}`, {
                onSuccess: () => { setEditingMenu(null); },
            });
        } else {
            form.post('/admin/navigation-menus', {
                onSuccess: () => { setEditingMenu(null); },
            });
        }
    };

    const toggleRole = (roleId: number) => {
        const current = form.data.role_ids;
        const next = current.includes(roleId)
            ? current.filter((r) => r !== roleId)
            : [...current, roleId];
        form.setData('role_ids', next);
    };

    const flattenAll = (items: NavMenuItem[]): NavMenuItem[] =>
        items.flatMap((item) => [item, ...(item.children ? flattenAll(item.children) : [])]);

    const allMenus = flattenAll(menus);

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold tracking-tight">Manajemen Menu & Akses Role</h1>
                <p className="text-sm text-muted-foreground">Atur tampilan sidebar dan hak akses per role.</p>
            </div>

            <Tabs value={activeTab} onValueChange={(v) => setActiveTab(v as 'menus' | 'roles')} className="space-y-4">
                <TabsList>
                    <TabsTrigger value="menus">Menu Sidebar</TabsTrigger>
                    <TabsTrigger value="roles">Akses Role</TabsTrigger>
                </TabsList>

                {/* ===== TAB: MENU SIDEBAR ===== */}
                <TabsContent value="menus" className="space-y-4">
                    <Button onClick={() => openCreate()}>+ Tambah Menu Utama</Button>

                    {allMenus.length === 0 && (
                        <p className="text-sm text-muted-foreground">Belum ada menu. Klik tombol di atas untuk menambah.</p>
                    )}

                    {/* Edit/Create Modal */}
                    {editingMenu && (
                        <div className="rounded-lg border bg-card p-4 space-y-4">
                            <h3 className="font-semibold">{form.data.id ? 'Ubah Menu' : 'Tambah Menu'}</h3>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label>Nama Menu</Label>
                                    <Input
                                        value={form.data.name}
                                        onChange={(e) => form.setData('name', e.target.value)}
                                        placeholder="Contoh: Dashboard"
                                    />
                                    {form.errors.name && <p className="text-xs text-destructive">{form.errors.name}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label>Route (kosongkan jika parent)</Label>
                                    <Input
                                        value={form.data.route}
                                        onChange={(e) => form.setData('route', e.target.value)}
                                        placeholder="/dashboard"
                                    />
                                    {form.errors.route && <p className="text-xs text-destructive">{form.errors.route}</p>}
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label>Icon (Lucide)</Label>
                                    <select
                                        className="flex h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm shadow-sm transition-colors"
                                        value={form.data.icon}
                                        onChange={(e) => form.setData('icon', e.target.value)}
                                    >
                                        <option value="">-- Pilih Icon --</option>
                                        {ICON_OPTIONS.map((ic) => (
                                            <option key={ic} value={ic}>{ic}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="space-y-2">
                                    <Label>Urutan (sort)</Label>
                                    <Input
                                        type="number"
                                        min="0"
                                        value={form.data.sort_order}
                                        onChange={(e) => form.setData('sort_order', Number(e.target.value))}
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-3">
                                <Checkbox
                                    checked={form.data.is_active}
                                    onCheckedChange={(v) => form.setData('is_active', v as boolean)}
                                />
                                <Label>Aktif</Label>
                            </div>

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
                                {(form.errors.role_ids || !form.data.role_ids?.length) && (
                                    <p className="text-xs text-destructive">Pilih minimal satu role.</p>
                                )}
                            </div>

                            <div className="flex justify-end gap-2">
                                <Button variant="outline" onClick={() => setEditingMenu(null)}>Batal</Button>
                                <Button onClick={submit} disabled={form.processing}>Simpan</Button>
                            </div>
                        </div>
                    )}

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
                                        <ConfirmDelete
                                            url={`${basePath}/admin/navigation-menus/${menu.id}`}
                                            onDeleted={() => window.location.reload()}
                                        >
                                            <Button variant="destructive" size="sm">Hapus</Button>
                                        </ConfirmDelete>
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
                                                    <ConfirmDelete
                                                        url={`${basePath}/admin/navigation-menus/${child.id}`}
                                                        onDeleted={() => window.location.reload()}
                                                    >
                                                        <Button variant="destructive" size="sm" className="h-7 text-xs">Hapus</Button>
                                                    </ConfirmDelete>
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
                                    <th className="px-4 py-2 text-center font-medium">Admin</th>
                                    <th className="px-4 py-2 text-center font-medium">Operator</th>
                                    <th className="px-4 py-2 text-center font-medium">Keuangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                {allMenus.map((menu) => (
                                    <tr key={menu.id} className="border-b last:border-0">
                                        <td className="px-4 py-2 font-medium">{menu.name}</td>
                                        {[roles[0], roles[1], roles[2]].map((role) => (
                                            <td key={role.id} className="px-4 py-2 text-center">
                                                <Checkbox
                                                    checked={menu.role_ids.includes(role.id)}
                                                    onCheckedChange={() => {
                                                        // Update via PUT request
                                                        form.setData({
                                                            id: menu.id,
                                                            name: menu.name,
                                                            route: menu.route ?? '',
                                                            icon: menu.icon ?? '',
                                                            sort_order: menu.sort_order,
                                                            is_active: menu.is_active,
                                                            role_ids: menu.role_ids.includes(role.id)
                                                                ? menu.role_ids.filter((r) => r !== role.id)
                                                                : [...menu.role_ids, role.id],
                                                        });
                                                        form.put(`/admin/navigation-menus/${menu.id}`, {});
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

            <Toaster />
        </div>
    );
}

// Helper to render icons by name using lucide-react dynamically
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