<?php

namespace App\Models;

use Database\Factories\TripItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $trip_id
 * @property int|null $client_good_id
 * @property string $jenis_barang
 * @property float $tonase_kg
 * @property string|null $rute
 * @property float|null $qty_barang
 * @property string|null $pricing_mode
 * @property int|null $harga_satuan
 * @property int|null $total_harga
 */
#[Fillable(['client_good_id', 'jenis_barang', 'tonase_kg', 'rute', 'qty_barang', 'pricing_mode', 'harga_satuan', 'total_harga'])]
class TripItem extends Model
{
    /** @use HasFactory<TripItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tonase_kg' => 'float',
            'qty_barang' => 'float',
            'harga_satuan' => 'integer',
            'total_harga' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * @return BelongsTo<ClientGood, $this>
     */
    public function clientGood(): BelongsTo
    {
        return $this->belongsTo(ClientGood::class);
    }
}
