<?php

namespace App\Exports;

use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Excel template for Trip import.
 * Format: Multiple trips can be imported in a single file.
 * Each trip can have multiple jenis_barang (items).
 *
 * USAGE: Generate template via controller action that calls:
 *   (new TripImportTemplate())->download('trip-import-template.xlsx')
 *
 * IMPORT FLOW:
 * 1. Each row represents ONE trip item (trip + item combined)
 * 2. All rows with same no_surat_jalan belong to same trip
 * 3. Items are grouped and processed accordingly
 *
 * COLUMNS:
 * - tanggal: Trip date (YYYY-MM-DD format)
 * - plat: Vehicle plate number (auto-creates vehicle if not exists)
 * - no_surat_jalan: Delivery note number (groups items into trips)
 * - category: "kontrak" or "outsourcing"
 * - client_name: Source client name (for Outsourcing) or Client asal (for Kontrak)
 * - destination_client: For Kontrak: destination client name
 * - tujuan: For Outsourcing: destination location
 * - jenis_barang: Item type (creates new badan if not exists)
 * - qty_barang: Quantity of goods
 * - total_harga: Total price for this item
 */
class TripImportTemplate implements FromArray, WithHeadings
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
            'category',
            'client_name',
            'destination_client',
            'tujuan',
            'jenis_barang',
            'qty_barang',
            'total_harga',
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
                'Kontrak',
                'PT A',
                'PT B',
                '',
                'Bbm',
                '100',
                '150000',
            ],
            // Instruction row (empty cells for spacing)
            ['', '', '', '', '', '', '', '', '', ''],
            // Notes
            ['Petunjuk: Setiap baris = satu item perjalanan', '', '', '', '', '', '', '', '', ''],
            ['no_surat_jalan yang sama = satu trip', '', '', '', '', '', '', '', '', ''],
            ['Kategori: Kontrak atau Outsourcing', '', '', '', '', '', '', '', '', ''],
            ['Kontrak: isi destination_client untuk barang', '', '', '', '', '', '', '', '', ''],
            ['Outsourcing: isi client sebagai lokasi tujuan', '', '', '', '', '', '', '', '', ''],
            ['Jenis barang baru = dibuat otomatis di master client', '', '', '', '', '', '', '', '', ''],
        ];
    }
}
