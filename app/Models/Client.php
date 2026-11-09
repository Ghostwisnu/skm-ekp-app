<?php

namespace App\Models;

use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $nama
 * @property string|null $alamat
 * @property string|null $npwp
 * @property string|null $kontak
 */
#[Fillable(['nama', 'alamat', 'npwp', 'kontak'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    /**
     * @return HasMany<Trip, $this>
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * @return HasMany<Trip, $this>
     */
    public function destinationTrips(): HasMany
    {
        return $this->hasMany(Trip::class, 'destination_client_id');
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany<ClientGood, $this>
     */
    public function goods(): HasMany
    {
        return $this->hasMany(ClientGood::class);
    }
}
