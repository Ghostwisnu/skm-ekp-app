<?php

namespace App\Imports;

use App\Models\DailyCost;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Import daily costs from Excel file.
 * Each row represents ONE daily cost record with optional extras.
 */
class DailyCostImport implements ToCollection, WithHeadingRow
{
    /** @var array<int, array> */
    public array $previewData = [];

    /** @var list<string> */
    public array $errors = [];

    public function collection(Collection $rows): void
    {
        $this->previewData = [];
        $this->errors = [];

        foreach ($rows as $index => $row) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            $tanggal = $row['tanggal'] ?? now()->format('Y-m-d');
            $plat = strtoupper(trim((string) ($row['plat'] ?? '')));
            $noSuratJalan = strtoupper(trim((string) ($row['no_surat_jalan'] ?? '')));
            $uangJalan = (int) ($row['uang_jalan'] ?? 0);
            $catatan = trim((string) ($row['catatan'] ?? ''));

            // Extract extras
            $extras = [];
            $i = 1;
            while (true) {
                $namaKey = "extras__nama_biaya_{$i}";
                $nominalKey = "extras__nominal_{$i}";
                $namaBiaya = trim((string) ($row[$namaKey] ?? ''));
                $nominal = (int) ($row[$nominalKey] ?? 0);
                if ($namaBiaya !== '') {
                    $extras[] = ['nama_biaya' => $namaBiaya, 'nominal' => $nominal];
                }
                if ($namaBiaya === '' && $nominal === 0) {
                    break;
                }
                $i++;
                if ($i > 20) {
                    break;
                }
            }

            $this->previewData[] = [
                'tanggal' => $tanggal,
                'plat' => $plat,
                'no_surat_jalan' => $noSuratJalan,
                'uang_jalan' => $uangJalan,
                'catatan' => $catatan,
                'extras' => $extras,
            ];
        }
    }

    /**
     * Store all valid daily costs from preview data.
     */
    public function store(): void
    {
        if (empty($this->previewData)) {
            return;
        }

        foreach ($this->previewData as $row) {
            $plat = strtoupper(trim((string) $row['plat']));
            if ($plat === '') {
                continue;
            }
            $vehicle = Vehicle::query()->firstOrCreate(['plat' => $plat]);

            $dailyCost = DailyCost::query()->create([
                'tanggal' => $row['tanggal'] ?? now()->format('Y-m-d'),
                'no_plat' => $plat,
                'no_surat_jalan' => strtoupper(trim((string) $row['no_surat_jalan'])),
                'uang_jalan' => (int) ($row['uang_jalan'] ?? 0),
                'catatan' => trim((string) $row['catatan']),
            ]);

            if (! empty($row['extras'])) {
                $dailyCost->extras()->createMany($row['extras']);
            }
        }
    }
}
