import { Head, Link, useForm } from '@inertiajs/react';
import tripRoutes from '@/routes/trips';
import { formatDate, formatNumber } from '@/lib/format';

interface Props {
    previewData: Array<{
        tanggal: string;
        plat: string;
        no_surat_jalan: string;
        category: string;
        client_name: string;
        destination_client?: string;
        tujuan?: string;
        jenis_barang: string;
        qty_barang: number;
        total_harga: number;
        is_auto_generated?: boolean;
    }>;
    grouped: Array<{
        no_surat_jalan: string;
        tanggal: string;
        plat: string;
        client_name: string;
        destination_client?: string;
        category: string;
        tujuan?: string;
        items: Array<{ jenis_barang: string; qty_barang: number; total_harga: number }>;
    }>;
    errors: string[];
}

export default function ImportPreviewTrip({ previewData, grouped, errors }: Props) {
    const { post, processing } = useForm();

    const handleConfirm = () => {
        post(tripRoutes.importStore.url());
    };

    return (
        <>
            <Head title="Konfirmasi Import Trip" />
            <div className="mx-auto max-w-5xl py-8">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold">Konfirmasi Import Data</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Ditemukan {grouped.length} trip dengan {previewData.length} item.
                        </p>
                    </div>
                    <Link
                        href={tripRoutes.import.url()}
                        className="text-sm text-blue-600 hover:underline"
                    >
                        ← Unggah Ulang
                    </Link>
                </div>

                {errors.length > 0 && (
                    <div className="mb-4 rounded-lg border border-destructive bg-destructive/10 p-4">
                        <h3 className="mb-2 font-semibold text-destructive">
                            Kesalahan Ditemukan ({errors.length})
                        </h3>
                        <ul className="list-disc pl-5 text-sm text-destructive">
                            {errors.map((error, i) => (
                                <li key={i}>{error}</li>
                            ))}
                        </ul>
                    </div>
                )}

                <div className="mb-6 space-y-4">
                    {grouped.map((group, index) => (
                        <div key={index} className="rounded-lg border bg-card p-4">
                            <div className="mb-2 flex items-center justify-between">
                                <h4 className="font-semibold">
                                    {group.no_surat_jalan}
                                    {previewData.find(d => d.no_surat_jalan === group.no_surat_jalan)?.is_auto_generated && (
                                        <span className="text-xs text-muted-foreground ml-2">(Otomatis)</span>
                                    )}
                                </h4>
                                <span className="text-xs text-muted-foreground">
                                    {group.tanggal} • {group.category}
                                </span>
                            </div>
                            <p className="text-sm">
                                <strong>Client:</strong> {group.client_name}
                                {group.destination_client && group.destination_client !== group.client_name && (
                                    <> → {group.destination_client}</>
                                )}
                            </p>
                            <p className="text-sm">
                                <strong>Plat:</strong> {group.plat}
                            </p>
                            <div className="mt-2">
                                <p className="text-xs font-medium mb-1">Item:</p>
                                <ul className="list-disc list-inside text-xs space-y-1">
                                    {group.items.map((item, i) => (
                                        <li key={i}>
                                            {item.jenis_barang}: {formatNumber(item.qty_barang)} pcs
                                            {item.total_harga > 0 && ` (Rp ${formatNumber(item.total_harga)})`}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="flex justify-end">
                    <button
                        onClick={handleConfirm}
                        disabled={processing}
                        className="rounded bg-green-600 px-6 py-2 font-bold text-white hover:bg-green-700 disabled:opacity-50"
                    >
                        {processing
                            ? 'Menyimpan...'
                            : `Konfirmasi & Simpan (${grouped.length} Trip)`}
                    </button>
                </div>
            </div>
        </>
    );
}