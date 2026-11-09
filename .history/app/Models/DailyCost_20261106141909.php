<?php

namespace App\Models;

use Database\Factories\DailyCostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Carbon;

/**

 * @property Carbon $tanggal
 * @property string $no_plat
 * @property string $no_surat_jalan
 * @property int $uang_jalan
 */

#[Fillable(['tanggal', 'no_plat', 'no_surat_jalan', 'uang_jalan'])]
class DailyCost extends Model
{
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
}