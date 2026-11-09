<?php

namespace App\Exports;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InvoiceExport implements FromArray, WithHeadings
{
    public function __construct(private readonly Invoice $invoice) {}

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return ['No. Surat Jalan', 'Tanggal', 'Plat', 'Barang', 'Rute', 'Qty', 'Jenis Tarif', 'Harga', 'Jumlah'];
    }

    /**
     * @return list<list<int|float|string>>
     */
    public function array(): array
    {
        $rows = $this->invoice->lines->map(fn ($line): array => [
            $line->no_surat_jalan,
            $line->tanggal->format('Y-m-d'),
            $line->plat,
            $line->jenis_barang,
            $line->rute ?? '',
            $line->qty_barang ?? $line->tonase_kg,
            $line->pricing_mode === 'flat_trip' ? 'Flat per trip' : 'Per qty',
            $line->harga_satuan ?? $line->harga_per_kg,
            $line->jumlah,
        ])->all();

        $rows[] = ['', '', '', '', '', '', '', 'Subtotal', $this->invoice->subtotal];
        $rows[] = ['', '', '', '', '', '', '', "PPN {$this->invoice->ppn_persen}%", $this->invoice->ppn_nominal];
        $rows[] = ['', '', '', '', '', '', '', 'Total', $this->invoice->total];

        return $rows;
    }
}
