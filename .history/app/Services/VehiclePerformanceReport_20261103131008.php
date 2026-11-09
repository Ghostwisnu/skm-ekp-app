<?php

namespace App\Services;

use App\Models\Trip;
use Illuminate\Support\Collection;

class VehiclePerformanceReport
{
    /**
     * Performance per vehicle: trips, item quantities, invoice value (before VAT), cost and margin.
     *
     * @param  array{from?: ?string, to?: ?string, vehicle_id?: ?int|?string, client_id?: ?int|?string}  $filters
     * @return Collection<int, array{vehicle_id: int, plat: string, trip: int, jumlah_barang: float, nilai_invoice: int, biaya: int, margin: int}>
     */
    public function rows(array $filters = []): Collection
    {
        $trips = Trip::query()
            ->with(['vehicle', 'items', 'dailyCost'])
            ->withSum('invoiceLines as nilai_invoice', 'jumlah')
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('tanggal', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('tanggal', '<=', $to))
            ->when($filters['vehicle_id'] ?? null, fn ($query, $id) => $query->where('vehicle_id', $id))
            ->when($filters['client_id'] ?? null, fn ($query, $id) => $query->where('client_id', $id))
            ->get();

        return $trips
            ->groupBy('vehicle_id')
            ->map(function (Collection $group): array {
                $nilaiInvoice = (int) $group->sum('nilai_invoice');
                $biaya = (int) $group->sum(fn (Trip $trip): int => $trip->dailyCost?->total ?? 0);

                return [
                    'vehicle_id' => $group->first()->vehicle_id,
                    'plat' => $group->first()->vehicle->plat,
                    'trip' => $group->count(),
                    'jumlah_barang' => (float) $group->sum(fn (Trip $trip): float => $trip->items->sum(fn ($item): float => $item->qty_barang ?? $item->tonase_kg)),
                    'nilai_invoice' => $nilaiInvoice,
                    'biaya' => $biaya,
                    'margin' => $nilaiInvoice - $biaya,
                ];
            })
            ->sortBy('plat')
            ->values();
    }
}
