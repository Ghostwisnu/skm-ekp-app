<?php

namespace App\Models;

use Database\Factories\DailyCostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $trip_id
 * @property int $uang_jalan_1
 * @property int $uang_bongkar
 * @property int $bonus_tepat_waktu
 * @property int $bonus_tambahan
 * @property int $pinjaman
 * @property int $uang_jalan_2
 * @property int $simpanan
 * @property-read int $total
 */
#[Fillable(['trip_id', 'uang_jalan_1', 'uang_bongkar', 'bonus_tepat_waktu', 'bonus_tambahan', 'pinjaman', 'uang_jalan_2', 'simpanan'])]
class DailyCost extends Model
{
    /** @use HasFactory<DailyCostFactory> */
    use HasFactory;

    /**
     * The seven cost components that make up the daily total.
     *
     * @var list<string>
     */
    public const COMPONENTS = [
        'uang_jalan_1',
        'uang_bongkar',
        'bonus_tepat_waktu',
        'bonus_tambahan',
        'pinjaman',
        'uang_jalan_2',
        'simpanan',
    ];

    /**
     * @var list<string>
     */
    protected $appends = ['total'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_fill_keys(self::COMPONENTS, 'integer');
    }

    /**
     * Pure sum of all seven components (no subtraction).
     */
    public function getTotalAttribute(): int
    {
        return array_sum(array_map(fn (string $component): int => (int) $this->getAttribute($component), self::COMPONENTS));
    }

    /**
     * @return BelongsTo<Trip, $this>
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}