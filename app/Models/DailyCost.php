<?php

namespace App\Models;

use Database\Factories\DailyCostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $tanggal
 * @property string $no_plat
 * @property string $no_surat_jalan
 * @property int $uang_jalan
 * @property string $catatan
 * @property int $total
 */
#[Fillable(['tanggal', 'no_plat', 'no_surat_jalan', 'uang_jalan', 'catatan'])]
class DailyCost extends Model
{
    /**
     * The components that make up the daily cost total.
     */
    public const COMPONENTS = ['pak_bensin', 'minyak', 'obongan', 'uang_makan', 'uang_surat', 'komponen_lain_1', 'komponen_lain_2'];

    /** @use HasFactory<DailyCostFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date:Y-m-d',
            'uang_jalan' => 'integer',
        ];
    }

    /**
     * Get the total daily cost including extras.
     */
    public function getTotalAttribute(): int
    {
        return $this->uang_jalan + $this->extras->sum('nominal');
    }

    /**
     * @return HasMany<DailyCostExtra, $this>
     */
    public function extras(): HasMany
    {
        return $this->hasMany(DailyCostExtra::class);
    }
}
