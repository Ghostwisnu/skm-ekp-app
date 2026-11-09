import { useForm, usePage } from "@inertiajs/react";
import { useState } from "react";
import { confirmDelete } from "@/components/confirm-delete";
import { NativeSelect } from "@/components/native-select";
import { DataTable, Field, PageShell, Td, Th } from "@/components/page-shell";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import type { Auth } from "@/types";
import type { Role } from "@/types/domain";

type ManagedUser = { id: number; name: string; email: string; role: Role };
type RoleOption = { value: Role; label: string };
type UserForm = { name: string; email: string; role: string; password: string };

function UserDialog({
    user,
    roles,
    onClose,
}: {
    user: ManagedUser | null;
    roles: RoleOption[];
    onClose: () => void;
}) {
    const form = useForm<UserForm>({
        name: user?.name ?? "",
        email: user?.email ?? "",
        role: user?.role ?? "operator",
        password: "",
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };

        if (user) {
            form.put(`/admin/users/${user.id}`, options);
        } else {
            form.post("/admin/users", options);
        }
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{user ? "Ubah User" : "Tambah User"}</DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="grid gap-3">
                    <Field label="Nama" error={form.errors.name}>
                        <Input
                            value={form.data.name}
                            onChange={(e) => form.setData("name", e.target.value)}
                        />
                    </Field>
                    <Field label="Email" error={form.errors.email}>
                        <Input
                            type="email"
                            value={form.data.email}
                            onChange={(e) => form.setData("email", e.target.value)}
                        />
                    </Field>
                    <Field label="Role" error={form.errors.role}>
                        <NativeSelect
                            value={form.data.role}
                            onChange={(e) => form.setData("role", e.target.value)}
                        >
                            {roles.map((role) => (
                                <option key={role.value} value={role.value}>
                                    {role.label}
                                </option>
                            ))}
                        </NativeSelect>
                    </Field>
                    <Field
                        label={user ? "Password baru (kosongkan jika tidak diubah)" : "Password"}
                        error={form.errors.password}
                    >
                        <Input
                            type="password"
                            value={form.data.password}
                            onChange={(e) => form.setData("password", e.target.value)}
                        />
                    </Field>
                    <Button type="submit" disabled={form.processing}>
                        Simpan
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function PpnForm({ ppnDefault }: { ppnDefault: number }) {
    const form = useForm({ ppn_default: String(ppnDefault) });

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.put("/admin/settings", { preserveScroll: true });
            }}
            className="flex max-w-sm items-end gap-3 rounded-md border bg-card p-3"
        >
            <Field label="PPN default (%)" error={form.errors.ppn_default}>
                <Input
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    value={form.data.ppn_default}
                    onChange={(e) => form.setData("ppn_default", e.target.value)}
                />
            </Field>
            <Button type="submit" disabled={form.processing}>
                Simpan
            </Button>
        </form>
    );
}

type CompanyDetails = {
    company_name: string;
    company_address: string;
    company_bank_name: string;
    company_bank_account: string;
};

function CompanyDetailsForm({ companyDetails }: { companyDetails: CompanyDetails }) {
    const form = useForm(companyDetails);

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                form.put("/admin/settings", { preserveScroll: true });
            }}
            className="grid max-w-2xl gap-3 rounded-md border bg-card p-4"
        >
            <h2 className="text-sm font-semibold">Identitas Perusahaan Pemilik</h2>
            <Field label="Nama perusahaan" error={form.errors.company_name}>
                <Input
                    value={form.data.company_name}
                    onChange={(event) => form.setData("company_name", event.target.value)}
                />
            </Field>
            <Field label="Alamat perusahaan" error={form.errors.company_address}>
                <Input
                    value={form.data.company_address}
                    onChange={(event) => form.setData("company_address", event.target.value)}
                />
            </Field>
            <Field label="Nama bank pembayaran" error={form.errors.company_bank_name}>
                <Input
                    value={form.data.company_bank_name}
                    onChange={(event) => form.setData("company_bank_name", event.target.value)}
                />
            </Field>
            <Field label="Nomor rekening" error={form.errors.company_bank_account}>
                <Input
                    value={form.data.company_bank_account}
                    onChange={(event) => form.setData("company_bank_account", event.target.value)}
                />
            </Field>
            <Button type="submit" disabled={form.processing}>
                Simpan Identitas Perusahaan
            </Button>
        </form>
    );
}

export default function Users({
    users,
    roles,
    ppnDefault,
    companyDetails,
}: {
    users: ManagedUser[];
    roles: RoleOption[];
    ppnDefault: number;
    companyDetails: CompanyDetails;
}) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const [dialog, setDialog] = useState<{ user: ManagedUser | null } | null>(null);

    return (
        <PageShell
            title="Pengguna & Pengaturan"
            description="Kelola user, identitas perusahaan, dan PPN default"
            actions={<Button onClick={() => setDialog({ user: null })}>Tambah User</Button>}
        >
            <PpnForm ppnDefault={ppnDefault} />
            <CompanyDetailsForm companyDetails={companyDetails} />
            <DataTable>
                <thead>
                    <tr>
                        <Th>Nama</Th>
                        <Th>Email</Th>
                        <Th>Role</Th>
                        <Th />
                    </tr>
                </thead>
                <tbody>
                    {users.map((user) => (
                        <tr key={user.id}>
                            <Td>{user.name}</Td>
                            <Td>{user.email}</Td>
                            <Td className="capitalize">{user.role}</Td>
                            <Td className="space-x-2 text-right whitespace-nowrap">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setDialog({ user })}
                                >
                                    Ubah
                                </Button>
                                {user.id !== auth.user.id && (
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        onClick={() =>
                                            confirmDelete(
                                                `/admin/users/${user.id}`,
                                                "Hapus user ini?",
                                            )
                                        }
                                    >
                                        Hapus
                                    </Button>
                                )}
                            </Td>
                        </tr>
                    ))}
                </tbody>
            </DataTable>
            {dialog && (
                <UserDialog
                    key={dialog.user?.id ?? "new"}
                    user={dialog.user}
                    roles={roles}
                    onClose={() => setDialog(null)}
                />
            )}
        </PageShell>
    );
}

Users.layout = {
    breadcrumbs: [{ title: "Pengguna & Pengaturan", href: "/admin/users" }],
};
