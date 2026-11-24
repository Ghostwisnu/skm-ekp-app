<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Excel template for Daily Cost (Biaya Harian) import.
 * Format: Each row represents ONE daily cost record.
 *
 * USAGE: Generate template via controller action that calls:
 *   (new DailyCostImportTemplate())->download('daily-cost-import-template.xlsx')
 *
 * IMPORT FLOW:
 * 1. Each row is a separate daily cost record
 * 2. Optional extras can be added via extra columns
 *
 * COLUMNS:
 * - tanggal: Date (YYYY-MM-DD format)
 * - plat: Vehicle plate number (auto-creates vehicle if not exists)
 * - no_surat_jalan: Delivery note number
 * - uang_jalan: Daily fuel/road cost (Rp)
 * - catatan: Optional notes
 * - extras__nama_biaya_1, extras__nominal_1: First extra cost
 */
class DailyCostImportTemplate implements FromArray, WithHeadings
{
    use Exportable;
{
    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'tanggal',
            'plat',
            'no_surat_jalan',
            'uang_jalan',
            'catatan',
            'extras__nama_biaya_1',
            'extras__nominal_1',
            'extras__nama_biaya_2',
            'extras__nominal_2',
        ];
    }

    /**
     * @return list<list<int|string>>
     */
    public function array(): array
    {
        return [
            // Example row
            [
                '2024-01-15',
                'B 1234 ABC',
                'SJ-2024-001',
                '500000',
                'Biaya operasional harian',
                'Parkir',
                '25000',
                '',
                '',
            ],
            // Instruction row (empty cells for spacing)
            ['', '', '', '', '', '', '', '', '', ''],
            // Notes
            ['Petunjuk: Setiap baris = satu rekaman biaya harian', '', '', '', '', '', '', '', '', ''],
            ['Uang Jalan: total biaya bahan bakar dan jalan', '', '', '', '', '', '', '', '', ''],
            ['Extras: biaya tambahan seperti parkir, tol, makanan supir', '', '', '', '', '', '', '', '', ''],
        ];
    }
}
