<?php

namespace App\Models;

use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $no_invoice
 * @property int $client_id
 * @property int|null $destination_client_id
 * @property string|null $company_name
 * @property string|null $company_address
 * @property string|null $company_bank_name
 * @property string|null $company_bank_account
 * @property Carbon $tanggal_awal
 * @property Carbon $tanggal_akhir
 * @property float $ppn_persen
 * @property int $subtotal
 * @property int $ppn_nominal
 * @property int $total
 * @property string $status
 */
#[Fillable(['no_invoice', 'client_id', 'destination_client_id', 'tanggal_awal', 'tanggal_akhir', 'company_name', 'company_address', 'company_bank_name', 'company_bank_account', 'ppn_persen', 'subtotal', 'ppn_nominal', 'total', 'status', 'created_by'])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_FINAL = 'final';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_awal' => 'date:Y-m-d',
            'tanggal_akhir' => 'date:Y-m-d',
            'ppn_persen' => 'float',
            'subtotal' => 'integer',
            'ppn_nominal' => 'integer',
            'total' => 'integer',
        ];
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function destinationClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'destination_client_id');
    }

    /**
     * @return HasMany<InvoiceLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * @return HasMany<Trip, $this>
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }
}
