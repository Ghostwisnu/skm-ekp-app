<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\ClientGood;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Services\NoSuratJalanGenerator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Import multiple trips from Excel file.
 * Each row represents ONE trip item (trip + item combined).
 * Rows with the same no_surat_jalan are grouped as one trip.
 */
class TripImport implements ToCollection, WithHeadingRow
{
    /** @var array<int, array> */
    public array $previewData = [];

    /** @var list<string> */
    public array $errors = [];

    public function collection(Collection $rows): void
    {
        $this->previewData = [];
        $groupedRows = [];

        foreach ($rows as $index => $row) {
            // Skip empty rows
            $rowData = $row instanceof Collection ? $row->toArray() : $row;
            if (empty(array_filter($rowData))) {
                continue;
            }

            $tanggal = $this->transformDate($rowData['tanggal'] ?? null);
            $plat = trim((string) ($rowData['plat'] ?? ''));
            if ($plat === '') {
                $this->errors[] = "Baris ".($index+1).": plat/kendaraan wajib diisi";
                continue;
            }

            $noSuratJalan = strtoupper(trim((string) ($rowData['no_surat_jalan'] ?? '')));
            
            // Auto-generate no_surat_jalan if not provided
            if ($noSuratJalan === '') {
                $noSuratJalan = NoSuratJalanGenerator::generate($tanggal, $plat);
            }

            $clientName = trim((string) ($rowData['client_name'] ?? ''));
            if ($clientName === '') {
                $this->errors[] = "Baris ".($index+1).": nama client wajib diisi";
                continue;
            }

            $category = strtolower(trim((string) ($rowData['category'] ?? 'kontrak')));
            $category = in_array($category, ['kontrak', 'outsourcing']) ? $category : 'kontrak';

            $jenisBarang = trim((string) ($rowData['jenis_barang'] ?? ''));
            if ($jenisBarang === '') {
                $this->errors[] = "Baris ".($index+1).": jenis barang wajib diisi";
                continue;
            }

            $rowData = [
                'tanggal' => $tanggal,
                'plat' => $plat,
                'no_surat_jalan' => $noSuratJalan,
                'category' => $category,
                'client_name' => $clientName,
                'destination_client' => trim((string) ($rowData['destination_client'] ?? '')),
                'tujuan' => trim((string) ($rowData['tujuan'] ?? '')),
                'jenis_barang' => $jenisBarang,
                'qty_barang' => (float) ($rowData['qty_barang'] ?? 0),
                'total_harga' => (int) ($rowData['total_harga'] ?? 0),
            ];

            $this->previewData[] = $rowData;
            $groupedRows[$noSuratJalan][] = $rowData;
        }
    }

    /**
     * Store all valid trips from preview data.
     */
    public function store(): void
    {
        if (empty($this->previewData)) {
            return;
        }

        $grouped = collect($this->previewData)->groupBy('no_surat_jalan');

        foreach ($grouped as $noSuratJalan => $rows) {
            $firstRow = $rows->first();

            $category = strtolower(trim((string) ($firstRow['category'] ?? 'kontrak')));
            $category = in_array($category, ['kontrak', 'outsourcing']) ? $category : 'kontrak';

            $clientName = trim((string) ($firstRow['client_name'] ?? ''));
            if ($clientName === '') {
                continue;
            }
            $client = Client::query()->firstOrCreate(['nama' => $clientName]);

            $plat = trim((string) ($firstRow['plat'] ?? ''));
            if ($plat === '') {
                continue;
            }
            $vehicle = Vehicle::query()->firstOrCreate(['plat' => $plat]);

            $destinationClientId = null;
            if ($category === 'kontrak') {
                $destinationClientName = trim((string) ($firstRow['destination_client'] ?? ''));
                if ($destinationClientName !== '') {
                    $destinationClient = Client::query()
                        ->where('nama', $destinationClientName)
                        ->where('id', '!=', $client->id)
                        ->firstOrCreate(['nama' => $destinationClientName]);
                    if ($destinationClient) {
                        $destinationClientId = $destinationClient->id;
                    }
                }
            }

            $tripData = [
                'tanggal' => $firstRow['tanggal'],
                'vehicle_id' => $vehicle->id,
                'client_id' => $client->id,
                'destination_client_id' => $destinationClientId,
                'sopir' => $vehicle->nama_supir ?? '',
                'no_surat_jalan' => $noSuratJalan,
                'category' => $category,
                'tujuan' => $category === 'outsourcing' ? ($firstRow['tujuan'] ?? null) : null,
                'invoice_id' => null,
            ];

            $trip = Trip::query()->create($tripData);

            $items = $this->resolveItems($trip, $rows, $category, $destinationClientId);
            if (! empty($items)) {
                $trip->items()->createMany($items);
            }
        }
    }

    protected function resolveItems(Trip $trip, Collection $rows, string $category, ?int $destinationClientId): array
    {
        $clientId = $trip->client_id;
        $goodsClientId = $category === 'kontrak' ? $destinationClientId : $clientId;
        $sourceClient = Client::query()->find($clientId);

        if ($sourceClient === null) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            $jenisBarang = trim((string) ($row['jenis_barang'] ?? ''));
            if ($jenisBarang === '') {
                continue;
            }

            $qty = (float) ($row['qty_barang'] ?? 0);
            $totalHarga = (int) ($row['total_harga'] ?? 0);

            $good = ClientGood::query()
                ->where('client_id', $goodsClientId)
                ->where('jenis_barang', $jenisBarang)
                ->first();

            if (! $good) {
                $good = ClientGood::query()->create([
                    'client_id' => $goodsClientId,
                    'jenis_barang' => $jenisBarang,
                    'pricing_mode' => 'per_qty',
                    'harga' => 0,
                ]);
            }

            $route = match ($category) {
                'outsourcing' => $sourceClient->nama.' → '.($row['tujuan'] ?? '-'),
                default => $destinationClientId
                    ? $sourceClient->nama.' → '.Client::query()->find($destinationClientId)->nama
                    : $sourceClient->nama,
            };

            $items[] = [
                'client_good_id' => $good->id,
                'jenis_barang' => $good->jenis_barang,
                'rute' => $route,
                'pricing_mode' => $good->pricing_mode,
                'qty_barang' => $qty,
                'tonase_kg' => $qty,
                'harga_satuan' => $good->harga,
                'total_harga' => $totalHarga,
            ];
        }

        return $items;
    }

    protected function transformDate(mixed $value): string
    {
        if (empty($value)) {
            return now()->format('Y-m-d');
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return now()->format('Y-m-d');
        }
    }
}
