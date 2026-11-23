<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\ClientGood;
use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Events\EndBatch;
use Maatwebsite\Excel\Events\StartBatch;

/**
 * Import multiple trips from Excel file.
 * Each row represents ONE trip item (trip + item combined).
 * Rows with the same no_surat_jalan are grouped as one trip.
 */
class TripImport implements ToModel, WithHeadingRow, WithMultipleSheets
{
    use Importable;

    /** @var Collection<int, array> */
    protected Collection $groupedRows;

    /**
     * @return Model|null
     */
    public function model(array $row): ?object
    {
        // Skip empty rows
        if (empty(array_filter($row))) {
            return null;
        }

        // Initialize collection if not set
        if (! isset($this->groupedRows)) {
            $this->groupedRows = new Collection;
        }

        // Group rows by no_surat_jalan
        $noSuratJalan = (string) ($row['no_surat_jalan'] ?? '');
        if ($noSuratJalan === '') {
            return null;
        }

        $this->groupedRows->put($noSuratJalan, $row);

        // We'll process grouped rows in afterBatch
        return null;
    }

    /**
     * @return array<int, string>
     */
    public function sheets(): array
    {
        return ['Trip Import'];
    }

    public static function startBatch(StartBatch $event): void
    {
        // Reset state
    }

    public function batchEnd(EndBatch $endBatch): void
    {
        // Process grouped rows into trips
        $this->processTrips();
    }

    /**
     * Process all grouped rows into trip records.
     */
    protected function processTrips(): void
    {
        if (! isset($this->groupedRows) || $this->groupedRows->isEmpty()) {
            return;
        }

        $grouped = $this->groupedRows->groupBy('no_surat_jalan');

        foreach ($grouped as $noSuratJalan => $rows) {
            $firstRow = $rows->first();

            // Determine category and destination
            $category = strtolower(trim((string) ($firstRow['category'] ?? 'kontrak')));
            $category = in_array($category, ['kontrak', 'outsourcing']) ? $category : 'kontrak';

            // Resolve client
            $clientName = trim((string) ($firstRow['client_name'] ?? ''));
            if ($clientName === '') {
                continue;
            }
            $client = $this->resolveClient($clientName);

            // Resolve vehicle
            $plat = trim((string) ($firstRow['plat'] ?? ''));
            if ($plat === '') {
                continue;
            }
            $vehicle = Vehicle::query()->firstOrCreate(['plat' => $plat]);

            // For kontrak, get destination client
            $destinationClientId = null;
            if ($category === 'kontrak') {
                $destinationClientName = trim((string) ($firstRow['destination_client'] ?? ''));
                if ($destinationClientName !== '') {
                    $destinationClient = $this->resolveDestinationClient($destinationClientName, $client->id);
                    if ($destinationClient) {
                        $destinationClientId = $destinationClient->id;
                    }
                }
            }

            // Build trip data
            $tripData = [
                'tanggal' => $firstRow['tanggal'] ?? now()->format('Y-m-d'),
                'vehicle_id' => $vehicle->id,
                'client_id' => $client->id,
                'destination_client_id' => $destinationClientId,
                'sopir' => $vehicle->nama_supir ?? '',
                'no_surat_jalan' => $noSuratJalan,
                'category' => $category,
                'tujuan' => $category === 'outsourcing' ? ($firstRow['tujuan'] ?? null) : null,
                'invoice_id' => null,
            ];

            // Create trip
            $trip = Trip::query()->create($tripData);

            // Process items
            $items = $this->resolveItems($trip, $rows, $category, $destinationClientId);

            if (! empty($items)) {
                $trip->items()->createMany($items);
            }
        }
    }

    /**
     * Resolve client from name or ID.
     */
    protected function resolveClient(string $clientName): Client
    {
        return Client::query()->firstOrCreate(['nama' => $clientName]);
    }

    /**
     * Resolve destination client for kontrak trips.
     */
    protected function resolveDestinationClient(string $clientName, int $sourceClientId): ?Client
    {
        if ($clientName === '') {
            return null;
        }

        return Client::query()
            ->where('nama', $clientName)
            ->where('id', '!=', $sourceClientId)
            ->firstOrCreate(['nama' => $clientName]);
    }

    /**
     * Resolve items from grouped rows.
     *
     * @param  Collection<int, array>  $rows
     * @return array<int, array>
     */
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

            // Find or create the client good
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
}
