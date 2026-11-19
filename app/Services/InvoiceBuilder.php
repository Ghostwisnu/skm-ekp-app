<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Setting;
use App\Models\Trip;
use App\Models\TripItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceBuilder
{
    /**
     * @param  int|null  $destinationClientId
     * @return Collection<int, TripItem>
     */
    public function pendingItems(int $clientId, ?int $destinationClientId, string $from, string $to): Collection
    {
        return TripItem::query()
            ->whereHas('trip', fn ($query) => $query
                ->where('client_id', $clientId)
                ->when($destinationClientId !== null, fn ($q) => $q->where('destination_client_id', $destinationClientId))
                ->whereNull('invoice_id')
                ->whereBetween('tanggal', [$from, $to]))
            ->with(['trip.vehicle', 'trip.client', 'trip.destinationClient'])
            ->orderBy('trip_id')
            ->orderBy('id')
            ->get();
    }

    /**
     * Build an invoice (with snapshot lines) from all pending trips of the period.
     *
     * @param  array{client_id: int, destination_client_id?: int|null, tanggal_awal: string, tanggal_akhir: string, no_invoice: string, ppn_persen: float|int|string, pph_persen?: float|int|string, prices?: array<int|string, int|string>}  $data
     */
    public function build(array $data, ?User $creator = null): Invoice
    {
        $destinationId = $data['destination_client_id'] ?? null;
        $items = $this->pendingItems(
            (int) $data['client_id'],
            $destinationId,
            $data['tanggal_awal'],
            $data['tanggal_akhir'],
        );

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'tanggal_awal' => 'Tidak ada trip yang belum di-invoice pada periode ini.',
            ]);
        }

        foreach ($items as $item) {
            if ($item->total_harga === null
                && (! isset($data['prices'][$item->id]) || (int) $data['prices'][$item->id] < 1)) {
                throw ValidationException::withMessages([
                    "prices.{$item->id}" => 'Harga wajib diisi untuk data barang lama yang belum memiliki tarif client.',
                ]);
            }
        }

        return DB::transaction(function () use ($data, $items, $creator): Invoice {
            $ppnPersen = (float) ($data['ppn_persen'] ?? 0);
            $pphPersen = (float) ($data['pph_persen'] ?? 0);
            $category = 'kontrak';
            $companyDetails = Setting::companyDetails();
            $firstTrip = $items->first()?->trip;

            $invoice = Invoice::query()->create([
                'no_invoice' => $data['no_invoice'],
                'client_id' => $data['client_id'],
                'destination_client_id' => $destinationId,
                'tanggal_awal' => $data['tanggal_awal'],
                'tanggal_akhir' => $data['tanggal_akhir'],
                ...$companyDetails,
                'ppn_persen' => $ppnPersen,
                'pph_persen' => $pphPersen,
                'category' => $firstTrip?->category ?? $category,
                'status' => Invoice::STATUS_DRAFT,
                'created_by' => $creator?->id,
            ]);

            $subtotal = 0;

            foreach ($items as $item) {
                $qty = $item->qty_barang ?? $item->tonase_kg;
                $pricingMode = $item->pricing_mode ?? 'per_qty';
                $harga = $item->harga_satuan ?? (int) ($data['prices'][$item->id] ?? 0);
                $jumlah = $item->total_harga ?? (int) round($qty * $harga);
                $subtotal += $jumlah;

                $invoice->lines()->create([
                    'trip_id' => $item->trip_id,
                    'trip_item_id' => $item->id,
                    'no_surat_jalan' => $item->trip->no_surat_jalan,
                    'tanggal' => $item->trip->tanggal,
                    'plat' => $item->trip->vehicle->plat,
                    'jenis_barang' => $item->jenis_barang,
                    'tonase_kg' => $item->tonase_kg,
                    'harga_per_kg' => $harga,
                    'rute' => $item->trip->destination_client_id !== null
                        ? "{$item->trip->client->nama} → {$item->trip->destinationClient->nama}"
                        : $item->rute,
                    'tujuan' => $item->trip->tujuan,
                    'qty_barang' => $qty,
                    'pricing_mode' => $pricingMode,
                    'harga_satuan' => $harga,
                    'jumlah' => $jumlah,
                ]);
            }

            $ppnNominal = (int) round($subtotal * $ppnPersen / 100);
            $pphNominal = (int) round($subtotal * $pphPersen / 100);

            $invoice->update([
                'subtotal' => $subtotal,
                'ppn_nominal' => $ppnNominal,
                'total' => $subtotal + $ppnNominal + $pphNominal,
            ]);

            Trip::query()
                ->whereIn('id', $items->pluck('trip_id')->unique())
                ->update(['invoice_id' => $invoice->id]);

            return $invoice;
        });
    }
}
