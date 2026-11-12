<?php

namespace App\Services;

use App\Models\Trip;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VehiclePerformanceReport
{
    /**
     * Performance per vehicle: trips, item quantities, invoice value (before VAT), cost and margin.
     * Cost includes uang_jalan + all extra costs from daily_cost_extras.
     *
     * @param  array{from?: ?string, to?: ?string, vehicle_id?: ?int|?string}  $filters
     * @return Collection<int, array{vehicle_id: int, plat: string, trip: int, jumlah_barang: float, nilai_invoice: int, biaya_uang_jalan: int, biaya_lainnya: int, total_biaya: int, margin: int}>
     */
    public function rows(array $filters = []): Collection
    {
        $trips = Trip::query()
            ->with(['vehicle', 'items'])
            ->withSum('invoiceLines as nilai_invoice', 'jumlah')
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('tanggal', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('tanggal', '<=', $to))
            ->when($filters['vehicle_id'] ?? null, fn ($query, $id) => $query->where('vehicle_id', $id))
            ->get();

        $costs = DB::table('daily_costs')
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('tanggal', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('tanggal', '<=', $to))
            ->when($filters['vehicle_id'] ?? null, function ($query, $id) use ($trips): void {
                $plat = $trips->firstWhere('vehicle_id', (int) $id)?->vehicle->plat;

                $query->where('no_plat', $plat);
            })
            ->get();

        // Get extras with their associated costs
        $extraIds = $costs->pluck('id');
        $extras = DB::table('daily_cost_extras')
            ->whereIn('daily_cost_id', $extraIds)
            ->get();

        return $trips
            ->groupBy('vehicle_id')
            ->map(function (Collection $group) use ($costs, $extras): array {
                $nilaiInvoice = (int) $group->sum('nilai_invoice');

                $plat = $group->first()->vehicle->plat;

                $platCosts = $costs->where('no_plat', $plat);
                $uangJalan = (int) $platCosts->sum('uang_jalan');

                // Sum extras for this plat's costs
                $platExtraIds = $platCosts->pluck('id');
                $platExtras = $extras->whereIn('daily_cost_id', $platExtraIds);
                $biayaLainnya = (int) $platExtras->sum('nominal');

                return [
                    'vehicle_id' => $group->first()->vehicle_id,
                    'plat' => $plat,
                    'trip' => $group->count(),
                    'jumlah_barang' => (float) $group->sum(fn (Trip $trip): float => $trip->items->sum(fn ($item): float => $item->qty_barang ?? $item->tonase_kg)),
                    'nilai_invoice' => $nilaiInvoice,
                    'biaya_uang_jalan' => $uangJalan,
                    'biaya_lainnya' => $biayaLainnya,
                    'total_biaya' => $uangJalan + $biayaLainnya,
                    'margin' => $nilaiInvoice - ($uangJalan + $biayaLainnya),
                ];
            })
            ->sortBy('plat')
            ->values();
    }
}