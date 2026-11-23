import { Link, useForm } from "@inertiajs/react";
import {
    index as tripIndex,
    store as storeTrip,
    update as updateTrip,
} from "@/actions/App/Http/Controllers/TripController";
import { Field, PageShell } from "@/components/page-shell";
import { RequiredBadge } from "@/components/required-badge";
import { NativeSelect } from "@/components/native-select";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { formatRupiah } from "@/lib/format";
import type { Client, ClientGood, Trip, TripItem, Vehicle } from "@/types/domain";

type FormItem = {
    client_good_id: string;
    jenis_barang: string;
    qty_barang: string;
    total_harga: string;
};

type FormData = {
    tanggal: string;
    plat: string;
    client_id: string;
    client_name: string;
    destination_client_id: string;
    destination_client_name: string;
    no_surat_jalan: string;
    category: string;
    tujuan: string;
    items: FormItem[];
};

function calculatedTotal(good: ClientGood | undefined, quantity: string): string {
    const qty = Number(quantity);

    if (!good || quantity === "" || qty <= 0) {
        return "";
    }

    return String(good.pricing_mode === "flat_trip" ? good.harga : Math.round(qty * good.harga));
}

export default function TripForm({
    trip,
    vehicles,
    clients,
}: {
    trip: Trip | null;
    vehicles: Vehicle[];
    clients: Client[];
}) {
    const tripClient = clients.find((client) => client.id === trip?.client_id);
    const destinationClient = clients.find(
        (client) => client.id === trip?.destination_client_id,
    );
    const category = trip?.category ?? "kontrak";
    const form = useForm<FormData>({
        tanggal: trip?.tanggal ?? new Date().toISOString().slice(0, 10),
        plat: trip?.vehicle?.plat ?? "",
        client_id: trip ? String(trip.client_id) : "",
        client_name: tripClient?.nama ?? "",
        destination_client_id: trip?.destination_client_id
            ? String(trip.destination_client_id)
            : "",
        destination_client_name: destinationClient?.nama ?? "",
        no_surat_jalan: trip?.no_surat_jalan ?? "",
        category: category,
        tujuan: trip?.tujuan ?? "",
        items: trip?.items?.map((item: TripItem) => {
            const goodsSourceClient =
                trip?.category === "outsourcing" ? tripClient : destinationClient;

            return {
                client_good_id: item.client_good_id ? String(item.client_good_id) : "",
                jenis_barang: item.jenis_barang,
                qty_barang: String(item.qty_barang ?? item.tonase_kg),
                total_harga:
                    item.total_harga !== null
                        ? String(item.total_harga)
                        : calculatedTotal(
                              goodsSourceClient?.goods?.find(
                                  (good) => good.id === item.client_good_id,
                              ),
                              String(item.qty_barang ?? item.tonase_kg),
                          ),
            };
        }) ?? [{ client_good_id: "", jenis_barang: "", qty_barang: "", total_harga: "" }],
    });

    const error = (key: string): string | undefined =>
        (form.errors as Record<string, string | undefined>)[key];

    const selectedClient = clients.find((client) => String(client.id) === form.data.client_id);
    const selectedDestinationClient = clients.find(
        (client) => String(client.id) === form.data.destination_client_id,
    );
    const selectedVehicle = vehicles.find(
        (vehicle) => vehicle.plat.toLowerCase() === form.data.plat.toLowerCase(),
    );

    // Kontrak: barang milik client tujuan. Outsourcing: barang milik client asal.
    const goodsClient =
        form.data.category === "kontrak" ? selectedDestinationClient : selectedClient;

    const updateClientInput = (field: "client" | "destination_client", value: string) => {
        const matchingClient = clients.find(
            (client) => client.nama.trim().toLowerCase() === value.trim().toLowerCase(),
        );
        const clientId = matchingClient ? String(matchingClient.id) : "";

        if (field === "client") {
            form.setData("client_name", value);
            form.setData("client_id", clientId);

            if (clientId && clientId === form.data.destination_client_id) {
                form.setData("destination_client_name", "");
                form.setData("destination_client_id", "");
            }

            if (clientId !== form.data.client_id) {
                form.setData("items", [
                    {
                        client_good_id: "",
                        jenis_barang: "",
                        qty_barang: "",
                        total_harga: "",
                    },
                ]);
            }

            return;
        }

        if (clientId !== form.data.destination_client_id) {
            form.setData("items", [
                {
                    client_good_id: "",
                    jenis_barang: "",
                    qty_barang: "",
                    total_harga: "",
                },
            ]);
        }

        form.setData("destination_client_name", value);
        form.setData("destination_client_id", clientId);

        // If destination client is set but source client changed, clear items to avoid invalid goods
        if (form.data.destination_client_id && clientId !== form.data.client_id) {
            form.setData("items", [
                {
                    client_good_id: "",
                    jenis_barang: "",
                    qty_barang: "",
                    total_harga: "",
                },
            ]);
        }
    };

    const setItem = (index: number, key: keyof FormItem, value: string) => {
        form.setData(
            "items",
            form.data.items.map((item, itemIndex) => {
                if (itemIndex !== index) {
                    return item;
                }

                const updatedItem = { ...item, [key]: value };
                const selectedGood =
                    key === "jenis_barang"
                        ? goodsClient?.goods?.find(
                              (good) => good.jenis_barang.toLowerCase() === value.toLowerCase(),
                          )
                        : (goodsClient?.goods?.find(
                              (good) => String(good.id) === item.client_good_id,
                          ) ??
                          goodsClient?.goods?.find(
                              (good) =>
                                  good.jenis_barang.toLowerCase() ===
                                  item.jenis_barang.toLowerCase(),
                          ));

                if (key === "jenis_barang") {
                    updatedItem.client_good_id =
                        goodsClient?.goods
                            ?.find(
                                (good) => good.jenis_barang.toLowerCase() === value.toLowerCase(),
                            )
                            ?.id.toString() ?? "";
                }

                if (key === "qty_barang" || key === "jenis_barang") {
                    updatedItem.total_harga = calculatedTotal(selectedGood, updatedItem.qty_barang);
                }

                return updatedItem;
            }),
        );
    };

    const submit = (event: React.FormEvent) => {
        event.preventDefault();

        if (trip) {
            form.put(updateTrip.url(trip.id));
        } else {
            form.post(storeTrip.url());
        }
    };

    return (
        <PageShell title={trip ? "Ubah Trip" : "Tambah Trip"}>
            <form onSubmit={submit} className="grid max-w-5xl gap-4 rounded-md border bg-card p-4">
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label={<><strong>Tgl</strong> <RequiredBadge /></>} error={form.errors.tanggal}>
                        <Input
                            type="date"
                            value={form.data.tanggal}
                            onChange={(event) => form.setData("tanggal", event.target.value)}
                        />
                    </Field>
                    <Field label={<><strong>No. Surat Jalan</strong> <RequiredBadge /></>} error={form.errors.no_surat_jalan}>
                        <Input
                            value={form.data.no_surat_jalan}
                            onChange={(event) => form.setData("no_surat_jalan", event.target.value)}
                        />
                    </Field>
                    <Field label={<><strong>Plat</strong> <RequiredBadge /></>} error={form.errors.plat}>
                        <Input
                            list="trip-vehicle-plates"
                            value={form.data.plat}
                            onChange={(event) => form.setData("plat", event.target.value)}
                            placeholder="Pilih atau ketik nomor plat"
                        />
                        <datalist id="trip-vehicle-plates">
                            {vehicles.map((vehicle) => (
                                <option key={vehicle.id} value={vehicle.plat} />
                            ))}
                        </datalist>
                    </Field>
                    <Field
                        label={
                            form.data.category === "outsourcing"
                                ? <><strong>Client</strong> <RequiredBadge /></>
                                : <><strong>Client asal</strong> <RequiredBadge /></>
                        }
                        error={form.errors.client_name ?? form.errors.client_id}
                    >
                        <Input
                            list="trip-clients"
                            value={form.data.client_name}
                            onChange={(event) => updateClientInput("client", event.target.value)}
                            placeholder="Pilih atau ketik nama client"
                        />
                    </Field>
                    <Field label={<><strong>Kategori</strong> <RequiredBadge /></>}>
                        <NativeSelect
                            value={form.data.category}
                            onChange={(event) => {
                                const next = event.target.value;
                                form.setData("category", next);
                                if (next === "outsourcing") {
                                    form.setData("destination_client_id", "");
                                    form.setData("destination_client_name", "");
                                    form.setData("tujuan", "");
                                    // Reset items when switching to outsourcing
                                    form.setData("items", [
                                        {
                                            client_good_id: "",
                                            jenis_barang: "",
                                            qty_barang: "",
                                            total_harga: "",
                                        },
                                    ]);
                                } else {
                                    // Kontrak: clear tujuan, reset items (will use destination client goods)
                                    form.setData("tujuan", "");
                                    form.setData("items", [
                                        {
                                            client_good_id: "",
                                            jenis_barang: "",
                                            qty_barang: "",
                                            total_harga: "",
                                        },
                                    ]);
                                }
                            }}
                        >
                            <option value="kontrak">Kontrak</option>
                            <option value="outsourcing">Outsourcing</option>
                        </NativeSelect>
                    </Field>
                    {form.data.category === "outsourcing" ? (
                        <Field label={<><strong>Tujuan</strong> <RequiredBadge /></>} error={form.errors.tujuan}>
                            <Input
                                value={form.data.tujuan}
                                onChange={(event) => form.setData("tujuan", event.target.value)}
                                placeholder="Ketik tujuan"
                            />
                        </Field>
                    ) : (
                        <Field
                            label={<><strong>Client tujuan</strong> <RequiredBadge /></>}
                            error={
                                form.errors.destination_client_name ??
                                form.errors.destination_client_id
                            }
                        >
                            <Input
                                list="trip-clients"
                                value={form.data.destination_client_name}
                                onChange={(event) =>
                                    updateClientInput("destination_client", event.target.value)
                                }
                                placeholder="Pilih atau ketik nama client"
                            />
                        </Field>
                    )}
                    <datalist id="trip-clients">
                        {clients.map((client) => (
                            <option key={client.id} value={client.nama} />
                        ))}
                    </datalist>
                    <div className="text-sm text-muted-foreground sm:col-span-2">
                        Supir: {selectedVehicle?.nama_supir || "-"}
                        {selectedVehicle?.no_hp_supir ? ` • ${selectedVehicle.no_hp_supir}` : ""}
                    </div>
                </div>

                <section className="grid gap-3">
                    <div className="flex items-center justify-between">
                        <h2 className="text-sm font-medium">Barang</h2>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={!goodsClient}
                            onClick={() =>
                                form.setData("items", [
                                    ...form.data.items,
                                    {
                                        client_good_id: "",
                                        jenis_barang: "",
                                        qty_barang: "",
                                        total_harga: "",
                                    },
                                ])
                            }
                        >
                            Tambah barang
                        </Button>
                    </div>
                    {error("items") && <p className="text-sm text-red-600">{error("items")}</p>}
                    {form.data.items.map((item, index) => {
                        const selectedGood = goodsClient?.goods?.find(
                            (good: ClientGood) =>
                                String(good.id) === item.client_good_id ||
                                good.jenis_barang.toLowerCase() === item.jenis_barang.toLowerCase(),
                        );
                        return (
                            <div
                                key={index}
                                className="grid items-start gap-2 rounded-md border p-3 sm:grid-cols-[minmax(12rem,2fr)_8rem_10rem_10rem_auto]"
                            >
                                <Field
                                    label={<><strong>Jenis barang</strong> <RequiredBadge /></>}
                                    error={
                                        error(`items.${index}.jenis_barang`) ??
                                        error(`items.${index}.client_good_id`)
                                    }
                                >
                                    <Input
                                        list={`trip-goods-${index}`}
                                        value={item.jenis_barang}
                                        onChange={(event) =>
                                            setItem(index, "jenis_barang", event.target.value)
                                        }
                                        placeholder="Pilih atau ketik jenis barang"
                                    />
                                    <datalist id={`trip-goods-${index}`}>
                                        {goodsClient?.goods?.map((good) => (
                                            <option key={good.id} value={good.jenis_barang} />
                                        ))}
                                    </datalist>
                                </Field>
                                <Field
                                    label={<><strong>Qty barang</strong> <RequiredBadge /></>}
                                    error={error(`items.${index}.qty_barang`)}
                                >
                                    <Input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        value={item.qty_barang}
                                        onChange={(event) =>
                                            setItem(index, "qty_barang", event.target.value)
                                        }
                                    />
                                </Field>
                                <Field
                                    label={
                                        selectedGood?.pricing_mode === "flat_trip"
                                            ? "Tarif flat"
                                            : "Harga satuan"
                                    }
                                >
                                    <div className="flex h-9 items-center rounded-md border bg-muted px-3 text-sm">
                                        {selectedGood ? formatRupiah(selectedGood.harga) : "-"}
                                    </div>
                                </Field>
                                <Field
                                    label="Total harga barang"
                                    error={error(`items.${index}.total_harga`)}
                                >
                                    <Input
                                        type="number"
                                        min="0"
                                        step="1"
                                        value={item.total_harga}
                                        onChange={(event) =>
                                            setItem(index, "total_harga", event.target.value)
                                        }
                                        placeholder="Otomatis dari qty"
                                    />
                                </Field>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    className="self-end"
                                    disabled={form.data.items.length === 1}
                                    onClick={() =>
                                        form.setData(
                                            "items",
                                            form.data.items.filter(
                                                (_, itemIndex) => itemIndex !== index,
                                            ),
                                        )
                                    }
                                >
                                    Hapus
                                </Button>
                            </div>
                        );
                    })}
                    {goodsClient && (
                        <p className="text-sm text-muted-foreground">
                            Jenis barang baru akan disimpan ke master client dengan tarif per qty
                            Rp0. Atur tarifnya di menu Client.
                        </p>
                    )}
                </section>

                <div className="flex gap-2">
                    <Button type="submit" disabled={form.processing}>
                        Simpan
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href={tripIndex.url()}>Batal</Link>
                    </Button>
                </div>
            </form>
        </PageShell>
    );
}

TripForm.layout = { breadcrumbs: [{ title: "Trip", href: tripIndex.url() }] };
