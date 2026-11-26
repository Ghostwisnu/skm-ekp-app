<?php

namespace App\Imports;

use App\Models\DailyCost;
use App\Models\Vehicle;
use App\Services\NoSuratJalanGenerator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

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
            $rowData = $row instanceof Collection ? $row->toArray() : $row;
            if (empty(array_filter($rowData))) {
                continue;
            }

            $tanggal = $this->transformDate($rowData['tanggal'] ?? null);
            $plat = strtoupper(trim((string) ($rowData['plat'] ?? '')));
            
            // Skip rows without required plat field
            if ($plat === '') {
                $this->errors[] = "Baris " . ($index + 1) . ": plat/kendaraan wajib diisi";
                continue;
            }

            $uangJalan = (int) ($rowData['uang_jalan'] ?? 0);
            $catatan = trim((string) ($rowData['catatan'] ?? ''));

            // Auto-generate no_surat_jalan if not provided
            $noSuratJalan = strtoupper(trim((string) ($rowData['no_surat_jalan'] ?? '')));
            if ($noSuratJalan === '') {
                $noSuratJalan = NoSuratJalanGenerator::generateForDailyCost(
                    $tanggal ?? now()->format('Y-m-d'),
                    $plat
                );
            }

            // Extract extras
            $extras = [];
            $i = 1;
            while (true) {
                $namaKey = "extras__nama_biaya_{$i}";
                $nominalKey = "extras__nominal_{$i}";
                $namaBiaya = trim((string) ($rowData[$namaKey] ?? ''));
                $nominal = (int) ($rowData[$nominalKey] ?? 0);
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
     * Transform Excel date to Y-m-d format.
     */
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

    /**
     * Store all valid daily costs from preview data.
     */
    public function store(): void
    {
        if (empty($this->previewData)) {
            return;
        }

        foreach ($this->previewData as $row) {
            $plat = strtoupper(trim((string) ($row['plat'] ?? '')));
            if ($plat === '') {
                continue;
            }

            // Auto-generate no_surat_jalan if not provided
            $noSuratJalan = strtoupper(trim((string) ($row['no_surat_jalan'] ?? '')));
            if ($noSuratJalan === '') {
                $noSuratJalan = NoSuratJalanGenerator::generateForDailyCost($row['tanggal'] ?? now()->format('Y-m-d'), $plat);
            }

            $vehicle = Vehicle::query()->firstOrCreate(['plat' => $plat]);

            $dailyCost = DailyCost::query()->create([
                'tanggal' => $row['tanggal'] ?? now()->format('Y-m-d'),
                'no_plat' => $plat,
                'no_surat_jalan' => $noSuratJalan,
                'uang_jalan' => (int) ($row['uang_jalan'] ?? 0),
                'catatan' => trim((string) ($row['catatan'] ?? '')),
            ]);

            if (! empty($row['extras'])) {
                $dailyCost->extras()->createMany($row['extras']);
            }
        }
    }
}
