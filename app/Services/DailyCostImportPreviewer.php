<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientGood;
use App\Models\DailyCost;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Readers\ExcelReader;

/**
 * Service for parsing Daily Cost Excel imports and generating previews.
 */
class DailyCostImportPreviewer
{
    /**
     * Parse Excel file and return preview data.
     *
     * @param array $previewRows Parsed rows from Excel
     * @return array{rows: array<int, array>, total: int, errors: array<int, string>}
     */
    public function parse(array $previewRows): array
    {
        $rows = [];
        $errors = [];

        foreach ($previewRows as $index => $row) {
            $tanggal = $row['tanggal'] ?? now()->format('Y-m-d');
            $plat = strtoupper(trim((string) ($row['plat'] ?? '')));
            $noSuratJalan = strtoupper(trim((string) ($row['no_surat_jalan'] ?? '')));
            $uangJalan = (int) ($row['uang_jalan'] ?? 0);
            $catatan = trim((string) ($row['catatan'] ?? ''));

            // Extract extras
            $extras = $this->extractExtras($row);

            $rows[] = [
                'tanggal' => $tanggal,
                'plat' => $plat,
                'no_surat_jalan' => $noSuratJalan,
                'uang_jalan' => $uangJalan,
                'catatan' => $catatan,
                'extras' => $extras,
            ];
        }

        return [
            'rows' => $rows,
            'total' => count($rows),
            'errors' => $errors,
        ];
    }

    /**
     * Extract extras from row data.
     *
     * @return array<int, array{nama_biaya: string, nominal: int}>
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

            if ($namaBiaya !== '') {
                $extras[] = [
                    'nama_biaya' => $namaBiaya,
                    'nominal' => $nominal,
                ];
            }

            // Stop if both are empty
            if ($namaBiaya === '' && $nominal === 0) {
                break;
            }

            $index++;

            // Safety limit
            if ($index > 20) {
                break;
            }
        }

        return $extras;
    }

    /**
     * Read Excel file and return rows for preview.
     */
    public function readExcel(string $path): array
    {
        $reader = \Maatwebsite\Excel\ExcelReader::create($path, Excel::XLSX);
        $reader->withHeadingRow();
        $reader->sheet('Daily Cost Import');

        $rows = [];
        while ($reader->valid()) {
            $rows[] = $reader->current();
            $reader->next();
        }
        $reader->close();

        return $rows;
    }

    /**
     * Calculate sum of uang_jalan from rows.
     */
    public function totalUangJalan(array $rows): int
    {
        return collect($rows)->sum(fn ($row) => (int) ($row['uang_jalan'] ?? 0));
    }

    /**
     * Get vehicles from rows.
     *
     * @return array<int, string>
     */
    public function getUniquePlats(array $rows): array
    {
        return collect($rows)
            ->map(fn ($row) => strtoupper(trim((string) ($row['plat'] ?? ''))))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}