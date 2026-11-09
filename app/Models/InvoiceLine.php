<?php

namespace App\Models;

use Database\Factories\InvoiceLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $invoice_id
 * @property int $trip_id
 * @property int $trip_item_id
 * @property string $no_surat_jalan
 * @property Carbon $tanggal
 * @property string $plat
 * @property string $jenis_barang
 * @property float $tonase_kg
 * @property int $harga_per_kg
 * @property string|null $rute
 * @property float|null $qty_barang
 * @property string|null $pricing_mode
 * @property int|null $harga_satuan
 * @property int $jumlah
 */
#[Fillable(['trip_id', 'trip_item_id', 'no_surat_jalan', 'tanggal', 'plat', 'jenis_barang', 'tonase_kg', 'harga_per_kg', 'rute', 'qty_barang', 'pricing_mode', 'harga_satuan', 'jumlah'])]
class InvoiceLine extends Model
{
    /** @use HasFactory<InvoiceLineFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date:Y-m-d',
            'tonase_kg' => 'float',
            'qty_barang' => 'float',
            'harga_per_kg' => 'integer',
            'harga_satuan' => 'integer',
            'jumlah' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
