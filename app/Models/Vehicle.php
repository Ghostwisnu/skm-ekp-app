<?php

namespace App\Models;

use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $plat
 * @property string|null $jenis_kendaraan
 * @property string|null $model_kendaraan
 * @property string|null $nama_supir
 * @property string|null $no_hp_supir
 * @property string|null $keterangan
 */
#[Fillable(['plat', 'jenis_kendaraan', 'model_kendaraan', 'nama_supir', 'no_hp_supir', 'keterangan'])]
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    /**
     * @return HasMany<Trip, $this>
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
