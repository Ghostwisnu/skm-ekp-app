<?php

namespace App\Imports;

use App\Models\DailyCost;
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
 * Import daily costs from Excel file.
 * Each row represents ONE daily cost record with optional extras.
 */
class DailyCostImport implements ToModel, WithHeadingRow, WithMultipleSheets
{
    use Importable;

    /** @var Collection<int, array> */
    protected Collection $rowsData;

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
        if (! isset($this->rowsData)) {
            $this->rowsData = new Collection;
        }

        $this->rowsData->push($row);

        // We'll process in batchEnd
        return null;
    }

    /**
     * @return array<int, string>
     */
    public function sheets(): array
    {
        return ['Daily Cost Import'];
    }

    public static function startBatch(StartBatch $event): void
    {
        // Reset state
    }

    public function batchEnd(EndBatch $endBatch): void
    {
        // Process all collected rows into daily cost records
        $this->processDailyCosts();
    }

    /**
     * Process all collected rows into daily cost records.
     */
    protected function processDailyCosts(): void
    {
        if (! isset($this->rowsData) || $this->rowsData->isEmpty()) {
            return;
        }

        foreach ($this->rowsData as $row) {
            $tanggal = $row['tanggal'] ?? now()->format('Y-m-d');
            $plat = strtoupper(trim((string) ($row['plat'] ?? '')));
            $noSuratJalan = strtoupper(trim((string) ($row['no_surat_jalan'] ?? '')));
            $uangJalan = (int) ($row['uang_jalan'] ?? 0);
            $catatan = trim((string) ($row['catatan'] ?? ''));

            // Resolve or create vehicle
            $vehicle = Vehicle::query()->firstOrCreate(['plat' => $plat]);

            // Create daily cost
            $dailyCost = DailyCost::query()->create([
                'tanggal' => $tanggal,
                'no_plat' => $plat,
                'no_surat_jalan' => $noSuratJalan,
                'uang_jalan' => $uangJalan,
                'catatan' => $catatan,
            ]);

            // Process extras - look for extras__nama_biaya_1, extras__nominal_1, etc.
            $extras = $this->extractExtras($row);

            if (! empty($extras)) {
                $dailyCost->extras()->createMany($extras);
            }
        }
    }

    /**
     * Extract extras from row data.
     *
     * @return array<int, array>
     */
    protected function extractExtras(array $row): array
    {
        $extras = [];
        $index = 1;

        while (true) {
            $namaBiayaKey = "extras__nama_biaya_{$index}";
            $nominalKey = "extras__nominal_{$index}";

            $namaBiaya = trim((string) ($row[$namaBiayaKey] ?? ''));
            $nominal = (int) ($row[$nominalKey] ?? 0);

            if ($namaBiaya === '' && $nominal === 0) {
                break;
            }

            if ($namaBiaya !== '') {
                $extras[] = [
                    'nama_biaya' => $namaBiaya,
                    'nominal' => $nominal,
                ];
            }

            $index++;

            // Safety limit
            if ($index > 20) {
                break;
            }
        }

        return $extras;
    }
}
