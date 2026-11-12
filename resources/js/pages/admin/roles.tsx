import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import { ConfirmDelete } from '@/components/confirm-delete-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Toaster } from '@/components/ui/sonner';

type RoleForm = {
    id?: number;
    name: string;
    permissions: string[];
};

export default function RoleManager({ roles, permissions }: { roles: any[]; permissions: string[] }) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<any | null>(null);

    const form = useForm<RoleForm>({
        name: '',
        permissions: [],
    });

    const openCreate = () => {
        setEditing(null);
        form.reset();
        setOpen(true);
    };

    const openEdit = (role: any) => {
        setEditing(role);
        form.setData({
            id: role.id,
            name: role.name,
            permissions: [...role.permissions],
        });
        setOpen(true);
    };

    const togglePermission = (perm: string) => {
        const current = form.data.permissions;
        const next = current.includes(perm)
            ? current.filter((p) => p !== perm)
            : [...current, perm];
        form.setData('permissions', next);
    };

    const submit = () => {
        if (form.data.id) {
            form.put(`/admin/roles/${form.data.id}`, {
                onSuccess: () => { setOpen(false); form.reset(); },
            });
        } else {
            form.post('/admin/roles', {
                onSuccess: () => { setOpen(false); form.reset(); },
            });
        }
    };

    const groupedPerms = permissions.reduce((acc: Record<string, string[]>, p) => {
        const prefix = p.split('.')[0] || 'umum';
        (acc[prefix] ||= []).push(p);
        return acc;
    }, {});

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Kelola Role & Akses</h1>
                    <p className="text-sm text-muted-foreground">Buat, edit, atau hapus role. Atur permission per role.</p>
                </div>
                <Button onClick={openCreate}>+ Tambah Role</Button>
            </div>

            <div className="rounded-lg border bg-card">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b bg-muted/50">
                            <th className="px-4 py-2 text-left font-medium">Role</th>
                            <th className="px-4 py-2 text-left font-medium">Permission</th>
                            <th className="px-4 py-2 text-right font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        {roles.map((role) => (
                            <tr key={role.id} className="border-b last:border-0">
                                <td className="px-4 py-2 font-medium capitalize">{role.name}</td>
                                <td className="px-4 py-2">
                                    <div className="flex flex-wrap gap-1">
                                        {role.permissions.slice(0, 6).map((p: string) => (
                                            <Badge key={p} variant="secondary" className="text-[10px]">{p}</Badge>
                                        ))}
                                        {role.permissions.length > 6 && (
                                            <Badge variant="outline" className="text-[10px]">+{role.permissions.length - 6} lagi</Badge>
                                        )}
                                    </div>
                                </td>
                                <td className="px-4 py-2 text-right">
                                    <Button variant="ghost" size="sm" onClick={() => openEdit(role)}>Ubah</Button>
                                    {role.name !== 'admin' && (
                                        <ConfirmDelete
                                            url={`/admin/roles/${role.id}`}
                                            onDeleted={() => window.location.reload()}
                                        >
                                            <Button variant="destructive" size="sm">Hapus</Button>
                                        </ConfirmDelete>
                                    )}
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle>{form.data.id ? 'Ubah Role' : 'Role Baru'}</DialogTitle>
                    </DialogHeader>

                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label>Role</Label>
                            <Input
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                placeholder="Contoh: admin, operator, keuangan"
                            />
                            {form.errors.name && <p className="text-xs text-destructive">{form.errors.name}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label>Permission</Label>
                            <div className="max-h-60 overflow-y-auto border rounded-md p-3 space-y-3">
                                {Object.entries(groupedPerms).map(([group, perms]) => (
                                    <div key={group} className="space-y-1">
                                        <p className="text-xs font-semibold text-muted-foreground uppercase">{group}</p>
                                        <div className="flex flex-wrap gap-3">
                                            {perms.map((perm) => (
                                                <div key={perm} className="flex items-center gap-2">
                                                    <Checkbox
                                                        id={`perm-${perm}`}
                                                        checked={form.data.permissions.includes(perm)}
                                                        onCheckedChange={() => togglePermission(perm)}
                                                    />
                                                    <Label htmlFor={`perm-${perm}`} className="text-xs cursor-pointer">
                                                        {perm}
                                                    </Label>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setOpen(false)}>Batal</Button>
                        <Button onClick={submit} disabled={form.processing}>Simpan</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Toaster />
        </div>
    );
}