<?php

namespace App\Services;

use App\Models\Trip;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel;

/**
 * Service for parsing Trip Excel imports and generating previews.
 */
class TripImportPreviewer
{
    /**
     * Parse Excel file and return preview data.
     *
     * @param  array  $previewRows  Parsed rows from Excel
     * @return array{rows: array<int, array>, groups: array<int, array>, totalTrips: int, totalItems: int, errors: array<int, string>}
     */
    public function parse(array $previewRows): array
    {
        $rows = [];
        $groupedRows = new Collection;
        $errors = [];

        foreach ($previewRows as $index => $row) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            $noSuratJalan = (string) ($row['no_surat_jalan'] ?? '');
            if ($noSuratJalan === '') {
                $errors[] = 'Baris '.($index + 1).': no_surat_jalan wajib diisi';

                continue;
            }

            $tanggal = $row['tanggal'] ?? now()->format('Y-m-d');
            $plat = trim((string) ($row['plat'] ?? ''));
            if ($plat === '') {
                $errors[] = 'Baris '.($index + 1).': plat/kendaraan wajib diisi';

                continue;
            }
            $clientName = trim((string) ($row['client_name'] ?? ''));
            if ($clientName === '') {
                $errors[] = 'Baris '.($index + 1).': nama client wajib diisi';

                continue;
            }

            $category = strtolower(trim((string) ($row['category'] ?? 'kontrak')));
            $category = in_array($category, ['kontrak', 'outsourcing']) ? $category : 'kontrak';

            $jenisBarang = trim((string) ($row['jenis_barang'] ?? ''));
            if ($jenisBarang === '') {
                $errors[] = 'Baris '.($index + 1).': jenis barang wajib diisi';

                continue;
            }

            $rowData = [
                'tanggal' => $tanggal,
                'plat' => $plat,
                'no_surat_jalan' => $noSuratJalan,
                'category' => $category,
                'client_name' => $clientName,
                'destination_client' => trim((string) ($row['destination_client'] ?? '')),
                'tujuan' => trim((string) ($row['tujuan'] ?? '')),
                'jenis_barang' => $jenisBarang,
                'qty_barang' => (float) ($row['qty_barang'] ?? 0),
                'total_harga' => (int) ($row['total_harga'] ?? 0),
            ];

            $rows[] = $rowData;

            // Group by no_surat_jalan for trip-level preview
            $groupedRows->put($noSuratJalan, $row);
        }

        // Process grouped rows into trip-level preview
        $groups = $this->processGroups($groupedRows);

        return [
            'rows' => $rows,
            'groups' => $groups,
            'totalTrips' => count($groups),
            'totalItems' => count($rows),
            'errors' => $errors,
        ];
    }

    /**
     * Process grouped rows into trip-level preview.
     *
     * @param  Collection<int, array>  $groupedRows
     * @return array<int, array{no_surat_jalan: string, tanggal: string, plat: string, client_name: string, destination_client: string, category: string, tujuan: string, items: array<int, array{jenis_barang: string, qty_barang: int|float, total_harga: int}>}>
     */
    protected function processGroups(Collection $groupedRows): array
    {
        $groups = [];

        foreach ($groupedRows->groupBy('no_surat_jalan') as $noSuratJalan => $rows) {
            $firstRow = $rows->first();

            $category = strtolower(trim((string) ($firstRow['category'] ?? 'kontrak')));
            $category = in_array($category, ['kontrak', 'outsourcing']) ? $category : 'kontrak';

            $clientName = trim((string) ($firstRow['client_name'] ?? ''));
            $plat = trim((string) ($firstRow['plat'] ?? ''));
            $destinationClient = trim((string) ($firstRow['destination_client'] ?? ''));
            $tujuan = trim((string) ($firstRow['tujuan'] ?? ''));

            $items = [];
            foreach ($rows as $row) {
                $items[] = [
                    'jenis_barang' => trim((string) ($row['jenis_barang'] ?? '')),
                    'qty_barang' => (float) ($row['qty_barang'] ?? 0),
                    'total_harga' => (int) ($row['total_harga'] ?? 0),
                ];
            }

            $groups[] = [
                'no_surat_jalan' => $noSuratJalan,
                'tanggal' => $firstRow['tanggal'] ?? now()->format('Y-m-d'),
                'plat' => $plat,
                'client_name' => $clientName,
                'destination_client' => $destinationClient,
                'category' => $category,
                'tujuan' => $category === 'outsourcing' ? $tujuan : '',
                'items' => $items,
            ];
        }

        return $groups;
    }

    /**
     * Calculate statistics from preview data.
     */
    public function statistics(array $data): array
    {
        $totalQty = 0;
        $totalHarga = 0;

        foreach ($data['rows'] as $row) {
            $totalQty += (float) ($row['qty_barang'] ?? 0);
            $totalHarga += (int) ($row['total_harga'] ?? 0);
        }

        return [
            'total_qty' => $totalQty,
            'total_harga' => $totalHarga,
        ];
    }

    /**
     * Get unique clients from rows.
     */
    public function getUniqueClients(array $rows): array
    {
        return collect($rows)
            ->map(fn ($row) => trim((string) ($row['client_name'] ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Get unique destinations from rows.
     */
    public function getUniqueDestinations(array $rows): array
    {
        return collect($rows)
            ->map(fn ($row) => trim((string) ($row['destination_client'] ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
