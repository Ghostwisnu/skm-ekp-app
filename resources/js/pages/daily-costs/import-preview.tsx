import { Head, Link, useForm } from '@inertiajs/react';
import dailyCostRoutes from '@/routes/daily-costs';
import { formatRupiah } from '@/lib/format';

interface Props {
    previewData: Array<{
        tanggal: string;
        plat: string;
        no_surat_jalan: string;
        uang_jalan: number;
        catatan: string;
        extras: Array<{ nama_biaya: string; nominal: number }>;
    }>;
    errors: string[];
}

export default function ImportPreviewDailyCost({ previewData, errors }: Props) {
    const { post, processing } = useForm();

    const handleConfirm = () => {
        post(dailyCostRoutes.importStore.url());
    };

    const totalUangJalan = previewData.reduce((sum, row) => sum + (row.uang_jalan ?? 0), 0);

    return (
        <>
            <Head title="Konfirmasi Import Biaya Harian" />
            <div className="mx-auto max-w-5xl py-8">
                <div className="mb-6 flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold">Konfirmasi Import Data</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Ditemukan {previewData.length} data biaya harian. Total Uang Jalan: {formatRupiah(totalUangJalan)}
                        </p>
                    </div>
                    <Link
                        href={dailyCostRoutes.import.url()}
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

                <div className="mb-6 overflow-x-auto rounded-lg border">
                    <table className="w-full text-left text-sm">
                        <thead>
                            <tr className="border-b bg-muted">
                                <th className="px-4 py-3">No</th>
                                <th className="px-4 py-3">Tanggal</th>
                                <th className="px-4 py-3">Plat</th>
                                <th className="px-4 py-3">No. Surat Jalan</th>
                                <th className="px-4 py-3 text-right">Uang Jalan</th>
                                <th className="px-4 py-3">Catatan</th>
                                <th className="px-4 py-3">Biaya Lainnya</th>
                            </tr>
                        </thead>
                        <tbody>
                            {previewData.map((row, index) => (
                                <tr key={index} className="border-b last:border-0">
                                    <td className="px-4 py-3">{index + 1}</td>
                                    <td className="px-4 py-3">{row.tanggal}</td>
                                    <td className="px-4 py-3 font-mono">{row.plat}</td>
                                    <td className="px-4 py-3">{row.no_surat_jalan}</td>
                                    <td className="px-4 py-3 text-right">{formatRupiah(row.uang_jalan)}</td>
                                    <td className="px-4 py-3">{row.catatan || '-'}</td>
                                    <td className="px-4 py-3">
                                        {row.extras && row.extras.length > 0 ? (
                                            <ul className="list-disc list-inside text-xs">
                                                {row.extras.map((extra, idx) => (
                                                    <li key={idx}>
                                                        {extra.nama_biaya}: {formatRupiah(extra.nominal)}
                                                    </li>
                                                ))}
                                            </ul>
                                        ) : (
                                            <span className="text-muted-foreground">-</span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <div className="flex justify-end">
                    <button
                        onClick={handleConfirm}
                        disabled={processing}
                        className="rounded bg-green-600 px-6 py-2 font-bold text-white hover:bg-green-700 disabled:opacity-50"
                    >
                        {processing
                            ? 'Menyimpan...'
                            : `Konfirmasi & Simpan (${previewData.length} Data)`}
                    </button>
                </div>
            </div>
        </>
    );
}