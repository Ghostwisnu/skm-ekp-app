import { Head, Link, useForm } from '@inertiajs/react';
import tripRoutes from '@/routes/trips';

export default function ImportTrip() {
    const { setData, post, processing, errors } = useForm({
        file: null as File | null,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        const file = (e.target as HTMLFormElement).elements.namedItem('file') as HTMLInputElement;
        if (!file?.files?.[0]) return;
        const formData = new FormData();
        formData.append('file', file.files[0]);
        post(tripRoutes.importPreview.url(), formData);
    };

    return (
        <>
            <Head title="Import Trip" />
            <div className="mx-auto max-w-3xl py-8">
                <div className="mb-6 flex items-center justify-between">
                    <h2 className="text-xl font-semibold">Import Data Trip</h2>
                    <Link
                        href={tripRoutes.index.url()}
                        className="text-sm text-blue-600 hover:underline"
                    >
                        ← Kembali
                    </Link>
                </div>

                <div className="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <h3 className="mb-1 font-bold text-blue-800">
                        Belum punya format file?
                    </h3>
                    <p className="text-sm text-blue-700">
                        Unduh template Excel terlebih dahulu. Kolom dengan tanda{' '}
                        <b>(*)</b> wajib diisi.
                    </p>
                    <div className="mt-3">
                        <a
                            href={tripRoutes.template.url()}
                            download
                            className="inline-block rounded bg-green-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-green-700"
                        >
                            📥 Unduh Template
                        </a>
                    </div>
                </div>

                <form onSubmit={submit} className="space-y-4 rounded-lg border bg-card p-6 shadow-sm">
                    <div>
                        <label className="mb-1 block text-sm font-medium">
                            Pilih File Excel (xlsx, xls, csv)
                        </label>
                        <input
                            type="file"
                            name="file"
                            accept=".xlsx, .xls, .csv"
                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            required
                        />
                        {errors.file && (
                            <p className="mt-1 text-sm text-red-500">{errors.file}</p>
                        )}
                    </div>

                    <div className="flex justify-end border-t pt-4">
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded bg-blue-600 px-6 py-2 font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                        >
                            {processing ? 'Membaca File...' : 'Unggah dan Pratinjau Data'}
                        </button>
                    </div>
                </form>
            </div>
        </>
    );
}
