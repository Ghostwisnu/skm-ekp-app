import { router, useForm } from "@inertiajs/react";
import { useState } from "react";
import { DataTable, Field, PageShell, Td, Th } from "@/components/page-shell";
import { NativeSelect } from "@/components/native-select";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { cleanQuery, formatDate, formatNumber, formatRupiah } from "@/lib/format";
import type { Option } from "@/types/domain";

type Pending = {
    id: number;
    tanggal: string;
    plat: string;
    no_surat_jalan: string;
    jenis_barang: string;
    rute: string | null;
    qty_barang: number;
    pricing_mode: "per_qty" | "flat_trip" | null;
    harga_satuan: number | null;
    total_harga: number | null;
};

type InvoiceForm = {
    client_id: string;
    destination_client_id: string;
    tanggal_awal: string;
    tanggal_akhir: string;
    no_invoice: string;
    ppn_persen: string;
    prices: Record<string, string>;
};

export default function InvoiceCreate({
    clients,
    filters,
    pending,
    ppnDefault,
}: {
    clients: Option[];
    filters: Record<string, string>;
    pending: Pending[];
    ppnDefault: number;
}) {
    const [period, setPeriod] = useState({
        client_id: filters.client_id ?? "",
        destination_client_id: filters.destination_client_id ?? "",
        tanggal_awal: filters.tanggal_awal ?? "",
        tanggal_akhir: filters.tanggal_akhir ?? "",
    });

    const form = useForm<InvoiceForm>({
        ...period,
        no_invoice: "",
        ppn_persen: String(ppnDefault),
        prices: {},
    });
    const errors = form.errors as Record<string, string | undefined>;

    const search = (event: React.FormEvent) => {
        event.preventDefault();
        router.get("/invoices/create", cleanQuery(period), {
            preserveState: true,
        });
    };

    const subtotal = pending.reduce(
        (sum, item) =>
            sum +
            (item.total_harga ??
                Math.round(item.qty_barang * Number(form.data.prices[item.id] || 0))),
        0,
    );
    const ppn = Math.round((subtotal * Number(form.data.ppn_persen || 0)) / 100);

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, ...period }));
        form.post("/invoices");
    };

    return (
        <PageShell
            title="Buat Invoice"
            description="Tarif barang diambil dari pengaturan client; tarif lama yang belum diset dapat diisi di sini"
        >
            <form
                onSubmit={search}
                className="flex flex-wrap items-end gap-3 rounded-md border bg-card p-3"
            >
                <Field label="Client asal" error={errors.client_id}>
                    <NativeSelect
                        value={period.client_id}
                        onChange={(e) =>
                            setPeriod({
                                ...period,
                                client_id: e.target.value,
                                destination_client_id:
                                    e.target.value === period.destination_client_id
                                        ? ""
                                        : period.destination_client_id,
                            })
                        }
                    >
                        <option value="">Pilih client</option>
                        {clients.map((c) => (
                            <option key={c.id} value={c.id}>
                                {c.nama}
                            </option>
                        ))}
                    </NativeSelect>
                </Field>
                <Field label="Client tujuan" error={errors.destination_client_id}>
                    <NativeSelect
                        value={period.destination_client_id}
                        onChange={(e) =>
                            setPeriod({ ...period, destination_client_id: e.target.value })
                        }
                    >
                        <option value="">Pilih client tujuan</option>
                        {clients.map((client) => (
                            <option
                                key={client.id}
                                value={client.id}
                                disabled={String(client.id) === period.client_id}
                            >
                                {client.nama}
                            </option>
                        ))}
                    </NativeSelect>
                </Field>
                <Field label="Tanggal awal" error={errors.tanggal_awal}>
                    <Input
                        type="date"
                        value={period.tanggal_awal}
                        onChange={(e) =>
                            setPeriod({
                                ...period,
                                tanggal_awal: e.target.value,
                            })
                        }
                    />
                </Field>
                <Field label="Tanggal akhir" error={errors.tanggal_akhir}>
                    <Input
                        type="date"
                        value={period.tanggal_akhir}
                        onChange={(e) =>
                            setPeriod({
                                ...period,
                                tanggal_akhir: e.target.value,
                            })
                        }
                    />
                </Field>
                <Button type="submit" variant="outline">
                    Tampilkan trip
                </Button>
            </form>

            <form onSubmit={submit} className="grid gap-4">
                <DataTable>
                    <thead>
                        <tr>
                            <Th>Tanggal</Th>
                            <Th>Plat</Th>
                            <Th>No. Surat Jalan</Th>
                            <Th>Barang</Th>
                            <Th>Rute</Th>
                            <Th right>Qty</Th>
                            <Th right>Harga satuan / flat</Th>
                            <Th right>Jumlah</Th>
                        </tr>
                    </thead>
                    <tbody>
                        {pending.length === 0 && (
                            <tr>
                                <Td colSpan={8} className="py-8 text-center text-muted-foreground">
                                    {period.client_id &&
                                    period.destination_client_id &&
                                    period.tanggal_awal &&
                                    period.tanggal_akhir
                                        ? "Tidak ada trip pending untuk pasangan client asal dan tujuan pada rentang tanggal ini. Trip yang sudah di-invoice tidak ditampilkan."
                                        : "Pilih client asal, client tujuan, dan rentang tanggal, lalu klik Tampilkan trip."}
                                </Td>
                            </tr>
                        )}
                        {pending.map((item) =>
                            (() => {
                                const legacy = item.total_harga === null;
                                const price =
                                    item.harga_satuan ?? Number(form.data.prices[item.id] || 0);
                                const amount =
                                    item.total_harga ?? Math.round(item.qty_barang * price);

                                return (
                                    <tr key={item.id}>
                                        <Td>{formatDate(item.tanggal)}</Td>
                                        <Td>{item.plat}</Td>
                                        <Td>{item.no_surat_jalan}</Td>
                                        <Td>{item.jenis_barang}</Td>
                                        <Td>{item.rute ?? "-"}</Td>
                                        <Td right>{formatNumber(item.qty_barang)}</Td>
                                        <Td right>
                                            {legacy ? (
                                                <>
                                                    <Input
                                                        type="number"
                                                        min="1"
                                                        className="ml-auto w-32 text-right"
                                                        value={form.data.prices[item.id] ?? ""}
                                                        onChange={(event) =>
                                                            form.setData("prices", {
                                                                ...form.data.prices,
                                                                [item.id]: event.target.value,
                                                            })
                                                        }
                                                    />
                                                    {errors[`prices.${item.id}`] && (
                                                        <p className="text-xs text-red-600">
                                                            {errors[`prices.${item.id}`]}
                                                        </p>
                                                    )}
                                                </>
                                            ) : (
                                                <span>
                                                    {formatRupiah(price)}
                                                    {item.pricing_mode === "flat_trip" && " (flat)"}
                                                </span>
                                            )}
                                        </Td>
                                        <Td right>{formatRupiah(amount)}</Td>
                                    </tr>
                                );
                            })(),
                        )}
                    </tbody>
                </DataTable>

                {pending.length > 0 && (
                    <div className="grid max-w-xl gap-3 rounded-md border bg-card p-4">
                        <Field label="No. Invoice" error={errors.no_invoice}>
                            <Input
                                value={form.data.no_invoice}
                                onChange={(e) => form.setData("no_invoice", e.target.value)}
                            />
                        </Field>
                        <Field label="PPN (%)" error={errors.ppn_persen}>
                            <Input
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                value={form.data.ppn_persen}
                                onChange={(e) => form.setData("ppn_persen", e.target.value)}
                            />
                        </Field>
                        <dl className="grid grid-cols-2 gap-1 text-sm tabular-nums">
                            <dt>Subtotal</dt>
                            <dd className="text-right">{formatRupiah(subtotal)}</dd>
                            <dt>PPN</dt>
                            <dd className="text-right">{formatRupiah(ppn)}</dd>
                            <dt className="font-semibold">Total</dt>
                            <dd className="text-right font-semibold">
                                {formatRupiah(subtotal + ppn)}
                            </dd>
                        </dl>
                        <Button type="submit" disabled={form.processing}>
                            Buat Invoice (Draft)
                        </Button>
                    </div>
                )}
            </form>
        </PageShell>
    );
}

InvoiceCreate.layout = {
    breadcrumbs: [
        { title: "Invoice", href: "/invoices" },
        { title: "Buat Invoice", href: "/invoices/create" },
    ],
};
