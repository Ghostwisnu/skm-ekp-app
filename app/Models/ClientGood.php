<?php

namespace App\Models;

use Database\Factories\ClientGoodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $client_id
 * @property string $jenis_barang
 * @property string $pricing_mode
 * @property int $harga
 */
#[Fillable(['client_id', 'jenis_barang', 'pricing_mode', 'harga'])]
class ClientGood extends Model
{
    /** @use HasFactory<ClientGoodFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'harga' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return HasMany<TripItem, $this>
     */
    public function tripItems(): HasMany
    {
        return $this->hasMany(TripItem::class);
    }
}
