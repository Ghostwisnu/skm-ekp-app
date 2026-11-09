import { useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    destroy as destroyClient,
    store as storeClient,
    update as updateClient,
} from '@/actions/App/Http/Controllers/ClientController';
import { confirmDelete } from '@/components/confirm-delete';
import {
    DataTable,
    EmptyRow,
    Field,
    PageShell,
    Td,
    Th,
} from '@/components/page-shell';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { NativeSelect } from '@/components/native-select';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { formatRupiah } from '@/lib/format';
import type { Auth } from '@/types';
import type { Client, ClientGood, Paginated } from '@/types/domain';

type ClientGoodForm = {
    id?: number;
    jenis_barang: string;
    pricing_mode: 'per_qty' | 'flat_trip';
    harga: string;
};
type ClientForm = {
    nama: string;
    alamat: string;
    npwp: string;
    kontak: string;
    goods: ClientGoodForm[];
};

function ClientDialog({
    client,
    onClose,
}: {
    client: Client | null;
    onClose: () => void;
}) {
    const form = useForm<ClientForm>({
        nama: client?.nama ?? '',
        alamat: client?.alamat ?? '',
        npwp: client?.npwp ?? '',
        kontak: client?.kontak ?? '',
        goods:
            client?.goods?.map((good: ClientGood) => ({
                id: good.id,
                jenis_barang: good.jenis_barang,
                pricing_mode: good.pricing_mode,
                harga: String(good.harga),
            })) ?? [],
    });
    const error = (key: string): string | undefined =>
        (form.errors as Record<string, string | undefined>)[key];

    const setGood = <Key extends 'jenis_barang' | 'pricing_mode' | 'harga'>(
        index: number,
        key: Key,
        value: ClientGoodForm[Key],
    ) =>
        form.setData(
            'goods',
            form.data.goods.map((good, goodIndex) =>
                goodIndex === index ? { ...good, [key]: value } : good,
            ),
        );

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };

        if (client) {
            form.put(updateClient.url(client.id), options);
        } else {
            form.post(storeClient.url(), options);
        }
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {client ? 'Ubah Client' : 'Tambah Client'}
                    </DialogTitle>
                </DialogHeader>
                <form
                    onSubmit={submit}
                    className="grid max-h-[75vh] gap-3 overflow-y-auto"
                >
                    <Field label="Nama *" error={form.errors.nama}>
                        <Input
                            value={form.data.nama}
                            onChange={(e) =>
                                form.setData('nama', e.target.value)
                            }
                        />
                    </Field>
                    <Field label="Alamat (opsional)" error={form.errors.alamat}>
                        <Input
                            value={form.data.alamat}
                            onChange={(e) =>
                                form.setData('alamat', e.target.value)
                            }
                        />
                    </Field>
                    <Field label="NPWP (opsional)" error={form.errors.npwp}>
                        <Input
                            value={form.data.npwp}
                            onChange={(e) =>
                                form.setData('npwp', e.target.value)
                            }
                        />
                    </Field>
                    <Field
                        label="Kontak / Telepon (opsional)"
                        error={form.errors.kontak}
                    >
                        <Input
                            value={form.data.kontak}
                            onChange={(e) =>
                                form.setData('kontak', e.target.value)
                            }
                        />
                    </Field>
                    <section className="grid gap-3 border-t pt-3">
                        <div className="flex items-center justify-between gap-2">
                            <div>
                                <h3 className="text-sm font-semibold">
                                    Barang (opsional)
                                </h3>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    form.setData('goods', [
                                        ...form.data.goods,
                                        {
                                            jenis_barang: '',
                                            pricing_mode: 'per_qty',
                                            harga: '',
                                        },
                                    ])
                                }
                            >
                                Tambah barang
                            </Button>
                        </div>
                        {form.data.goods.map((good, index) => (
                            <div
                                key={good.id ?? `new-${index}`}
                                className="grid gap-2 rounded-md border p-3"
                            >
                                <Field
                                    label="Jenis barang *"
                                    error={error(`goods.${index}.jenis_barang`)}
                                >
                                    <Input
                                        value={good.jenis_barang}
                                        onChange={(event) =>
                                            setGood(
                                                index,
                                                'jenis_barang',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </Field>
                                <Field
                                    label="Jenis tarif *"
                                    error={error(`goods.${index}.pricing_mode`)}
                                >
                                    <NativeSelect
                                        value={good.pricing_mode}
                                        onChange={(event) =>
                                            setGood(
                                                index,
                                                'pricing_mode',
                                                event.target
                                                    .value as ClientGoodForm['pricing_mode'],
                                            )
                                        }
                                    >
                                        <option value="per_qty">Per qty</option>
                                        <option value="flat_trip">
                                            Flat per trip
                                        </option>
                                    </NativeSelect>
                                </Field>
                                <Field
                                    label={
                                        good.pricing_mode === 'flat_trip'
                                            ? 'Tarif flat (Rp) *'
                                            : 'Harga satuan (Rp) *'
                                    }
                                    error={error(`goods.${index}.harga`)}
                                >
                                    <Input
                                        type="number"
                                        min="0"
                                        step="1"
                                        value={good.harga}
                                        onChange={(event) =>
                                            setGood(
                                                index,
                                                'harga',
                                                event.target.value,
                                            )
                                        }
                                    />
                                </Field>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={() =>
                                        form.setData(
                                            'goods',
                                            form.data.goods.filter(
                                                (_, goodIndex) =>
                                                    goodIndex !== index,
                                            ),
                                        )
                                    }
                                >
                                    Hapus barang
                                </Button>
                            </div>
                        ))}
                    </section>
                    <Button type="submit" disabled={form.processing}>
                        Simpan
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function ClientIndex({
    clients,
}: {
    clients: Paginated<Client>;
}) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const canEdit = auth.user.role !== 'keuangan';
    const [dialog, setDialog] = useState<{ client: Client | null } | null>(
        null,
    );

    return (
        <PageShell
            title="Client"
            description="Master client"
            actions={
                canEdit && (
                    <Button onClick={() => setDialog({ client: null })}>
                        Tambah Client
                    </Button>
                )
            }
        >
            <DataTable>
                <thead>
                    <tr>
                        <Th>Nama</Th>
                        <Th>Alamat</Th>
                        <Th>NPWP</Th>
                        <Th>Kontak</Th>
                        <Th>Barang dan tarif</Th>
                        <Th />
                    </tr>
                </thead>
                <tbody>
                    {clients.data.length === 0 && <EmptyRow colSpan={6} />}
                    {clients.data.map((client) => (
                        <tr key={client.id}>
                            <Td>{client.nama}</Td>
                            <Td>{client.alamat}</Td>
                            <Td>{client.npwp ?? '-'}</Td>
                            <Td>{client.kontak ?? '-'}</Td>
                            <Td>
                                {client.goods?.length
                                    ? client.goods
                                          .map(
                                              (good) =>
                                                  `${good.jenis_barang} • ${good.pricing_mode === 'flat_trip' ? 'Flat' : 'Per qty'} ${formatRupiah(good.harga)}`,
                                          )
                                          .join('; ')
                                    : 'Belum diatur'}
                            </Td>
                            <Td className="space-x-2 text-right whitespace-nowrap">
                                {canEdit && (
                                    <>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                setDialog({ client })
                                            }
                                        >
                                            Ubah
                                        </Button>
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onClick={() =>
                                                confirmDelete(
                                                    destroyClient.url(
                                                        client.id,
                                                    ),
                                                    'Hapus client ini?',
                                                )
                                            }
                                        >
                                            Hapus
                                        </Button>
                                    </>
                                )}
                            </Td>
                        </tr>
                    ))}
                </tbody>
            </DataTable>
            <Pagination paginator={clients} />
            {dialog && (
                <ClientDialog
                    key={dialog.client?.id ?? 'new'}
                    client={dialog.client}
                    onClose={() => setDialog(null)}
                />
            )}
        </PageShell>
    );
}

ClientIndex.layout = { breadcrumbs: [{ title: 'Client', href: '/clients' }] };
