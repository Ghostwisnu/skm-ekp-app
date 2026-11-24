<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Excel template for Trip import.
 * Format: Multiple trips can be imported in a single file.
 * Each trip can have multiple jenis_barang (items).
 */
class TripImportTemplate implements FromArray, WithHeadings
{
    use Exportable;

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