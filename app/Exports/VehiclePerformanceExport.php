<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VehiclePerformanceExport implements FromArray, WithHeadings
{
    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public function __construct(private readonly Collection $rows) {}

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['Plat', 'Trip', 'Qty Barang', 'Nilai Invoice', 'Biaya', 'Selisih'];
    }

    /**
     * @return list<list<int|float|string>>
     */
    public function array(): array
    {
        return $this->rows->map(fn (array $row): array => [
            $row['plat'],
            $row['trip'],
            $row['jumlah_barang'],
            $row['nilai_invoice'],
            $row['biaya'],
            $row['margin'],
        ])->values()->all();
    }
}
