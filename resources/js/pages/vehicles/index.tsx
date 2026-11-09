import { useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    destroy as destroyVehicle,
    store as storeVehicle,
    update as updateVehicle,
} from '@/actions/App/Http/Controllers/VehicleController';
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
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import type { Auth } from '@/types';
import type { Paginated, Vehicle } from '@/types/domain';

type VehicleForm = {
    plat: string;
    jenis_kendaraan: string;
    model_kendaraan: string;
    nama_supir: string;
    no_hp_supir: string;
    keterangan: string;
};

function VehicleDialog({
    vehicle,
    onClose,
}: {
    vehicle: Vehicle | null;
    onClose: () => void;
}) {
    const form = useForm<VehicleForm>({
        plat: vehicle?.plat ?? '',
        jenis_kendaraan: vehicle?.jenis_kendaraan ?? '',
        model_kendaraan: vehicle?.model_kendaraan ?? '',
        nama_supir: vehicle?.nama_supir ?? '',
        no_hp_supir: vehicle?.no_hp_supir ?? '',
        keterangan: vehicle?.keterangan ?? '',
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };

        if (vehicle) {
            form.put(updateVehicle.url(vehicle.id), options);
        } else {
            form.post(storeVehicle.url(), options);
        }
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {vehicle ? 'Ubah Kendaraan' : 'Tambah Kendaraan'}
                    </DialogTitle>
                </DialogHeader>
                <form onSubmit={submit} className="grid gap-3">
                    <Field label="Plat nomor" error={form.errors.plat}>
                        <Input
                            value={form.data.plat}
                            onChange={(e) =>
                                form.setData('plat', e.target.value)
                            }
                        />
                    </Field>
                    <Field
                        label="Jenis kendaraan"
                        error={form.errors.jenis_kendaraan}
                    >
                        <Input
                            value={form.data.jenis_kendaraan}
                            onChange={(e) =>
                                form.setData('jenis_kendaraan', e.target.value)
                            }
                        />
                    </Field>
                    <Field
                        label="Model kendaraan"
                        error={form.errors.model_kendaraan}
                    >
                        <Input
                            value={form.data.model_kendaraan}
                            onChange={(e) =>
                                form.setData('model_kendaraan', e.target.value)
                            }
                        />
                    </Field>
                    <Field label="Nama supir" error={form.errors.nama_supir}>
                        <Input
                            value={form.data.nama_supir}
                            onChange={(e) =>
                                form.setData('nama_supir', e.target.value)
                            }
                        />
                    </Field>
                    <Field
                        label="Nomor HP supir"
                        error={form.errors.no_hp_supir}
                    >
                        <Input
                            type="tel"
                            value={form.data.no_hp_supir}
                            onChange={(e) =>
                                form.setData('no_hp_supir', e.target.value)
                            }
                        />
                    </Field>
                    <Field
                        label="Keterangan (opsional)"
                        error={form.errors.keterangan}
                    >
                        <Input
                            value={form.data.keterangan}
                            onChange={(e) =>
                                form.setData('keterangan', e.target.value)
                            }
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

export default function VehicleIndex({
    vehicles,
}: {
    vehicles: Paginated<Vehicle>;
}) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const canEdit = auth.user.role !== 'keuangan';
    const [dialog, setDialog] = useState<{ vehicle: Vehicle | null } | null>(
        null,
    );

    return (
        <PageShell
            title="Kendaraan (Plat)"
            description="Master plat nomor"
            actions={
                canEdit && (
                    <Button onClick={() => setDialog({ vehicle: null })}>
                        Tambah Kendaraan
                    </Button>
                )
            }
        >
            <DataTable>
                <thead>
                    <tr>
                        <Th>Plat</Th>
                        <Th>Jenis</Th>
                        <Th>Model</Th>
                        <Th>Nama supir</Th>
                        <Th>No. HP supir</Th>
                        <Th>Keterangan</Th>
                        <Th />
                    </tr>
                </thead>
                <tbody>
                    {vehicles.data.length === 0 && <EmptyRow colSpan={7} />}
                    {vehicles.data.map((vehicle) => (
                        <tr key={vehicle.id}>
                            <Td>{vehicle.plat}</Td>
                            <Td>{vehicle.jenis_kendaraan ?? '-'}</Td>
                            <Td>{vehicle.model_kendaraan ?? '-'}</Td>
                            <Td>{vehicle.nama_supir ?? '-'}</Td>
                            <Td>{vehicle.no_hp_supir ?? '-'}</Td>
                            <Td>{vehicle.keterangan ?? '-'}</Td>
                            <Td className="space-x-2 text-right whitespace-nowrap">
                                {canEdit && (
                                    <>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                setDialog({ vehicle })
                                            }
                                        >
                                            Ubah
                                        </Button>
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onClick={() =>
                                                confirmDelete(
                                                    destroyVehicle.url(
                                                        vehicle.id,
                                                    ),
                                                    'Hapus kendaraan ini?',
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
            <Pagination paginator={vehicles} />
            {dialog && (
                <VehicleDialog
                    key={dialog.vehicle?.id ?? 'new'}
                    vehicle={dialog.vehicle}
                    onClose={() => setDialog(null)}
                />
            )}
        </PageShell>
    );
}

VehicleIndex.layout = {
    breadcrumbs: [{ title: 'Kendaraan', href: '/vehicles' }],
};
