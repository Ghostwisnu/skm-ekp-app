<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $daily_cost_id
 * @property string $nama_biaya
 * @property int $nominal
 */
#[Fillable(['daily_cost_id', 'nama_biaya', 'nominal'])]
class DailyCostExtra extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nominal' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<DailyCost, $this>
     */
    public function dailyCost(): BelongsTo
    {
        return $this->belongsTo(DailyCost::class);
    }
}