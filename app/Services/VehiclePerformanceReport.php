<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VehiclePerformanceReport
{
    /**
     * Performance per vehicle: trips, item quantities, invoice value (before VAT), cost and margin.
     * Cost includes uang_jalan + all extra costs from daily_cost_extras.
     *
     * @param  array{from?: ?string, to?: ?string, vehicle_id?: ?int|?string, client_id?: ?int|?string}  $filters
     * @return Collection<int, array{vehicle_id: int, plat: string, trip: int, jumlah_barang: float, nilai_invoice: int, biaya_uang_jalan: int, biaya_lainnya: int, total_biaya: int, margin: int}>
     */
    public function rows(array $filters = []): Collection
    {
        // Get trips with invoice sums
        $trips = Trip::query()
            ->with(['vehicle', 'items'])
            ->withSum('invoiceLines as nilai_invoice', 'jumlah')
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('tanggal', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('tanggal', '<=', $to))
            ->when($filters['vehicle_id'] ?? null, fn ($query, $id) => $query->where('vehicle_id', $id))
            ->when($filters['client_id'] ?? null, fn ($query, $id) => $query->where('client_id', $id))
            ->get();

        // Get daily costs in the period
        $costsQuery = DB::table('daily_costs')
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('tanggal', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('tanggal', '<=', $to));

        // If vehicle_id filter is provided, filter costs by that vehicle's plat
        if ($filters['vehicle_id'] ?? null) {
            $plat = Vehicle::query()->where('id', $filters['vehicle_id'])->value('plat');
            if ($plat) {
                $costsQuery->where('no_plat', $plat);
            }
        }

        // If client_id filter is provided, filter costs by vehicles that have trips for that client
        if ($filters['client_id'] ?? null) {
            $plats = $trips->map(fn (Trip $trip): string => $trip->vehicle->plat)->unique()->values()->all();
            if ($plats) {
                $costsQuery->whereIn('no_plat', $plats);
            }
        }

        $costs = $costsQuery->get();

        // Get extras for these costs
        $extraIds = $costs->pluck('id');
        $extras = DB::table('daily_cost_extras')
            ->whereIn('daily_cost_id', $extraIds)
            ->get();

        // Build a combined index of all vehicles that appear in either trips or costs
        // Key: "v:{vehicle_id}" for vehicles from trips, "p:{plat}" for vehicles only in costs
        $vehicleIndex = [];

        // Add vehicles from trips
        foreach ($trips->groupBy('vehicle_id') as $vehicleId => $group) {
            $vehicle = $group->first()->vehicle;
            $key = "v:{$vehicleId}";
            $vehicleIndex[$key] = [
                'vehicle_id' => $vehicleId,
                'plat' => $vehicle->plat,
                'trips' => $group,
                'costs' => collect(),
            ];
        }

        // Add vehicles from costs (including those without trips)
        foreach ($costs->groupBy('no_plat') as $plat => $platCosts) {
            // Try to find existing entry from trips
            $existingKey = null;
            foreach ($vehicleIndex as $key => $entry) {
                if ($entry['plat'] === $plat) {
                    $existingKey = $key;
                    break;
                }
            }

            if ($existingKey) {
                // Merge costs into existing entry
                $vehicleIndex[$existingKey]['costs'] = $platCosts;
            } else {
                // Find vehicle in DB or create synthetic entry
                $vehicle = Vehicle::query()->where('plat', $plat)->first();
                $vehicleId = $vehicle?->id ?? 0;
                $key = $vehicle ? "v:{$vehicleId}" : "p:{$plat}";
                $vehicleIndex[$key] = [
                    'vehicle_id' => $vehicleId,
                    'plat' => $plat,
                    'trips' => collect(),
                    'costs' => $platCosts,
                ];
            }
        }

        return collect($vehicleIndex)
            ->map(function (array $entry) use ($extras): array {
                $plat = $entry['plat'];
                $trips = $entry['trips'];
                $costsForPlat = $entry['costs'];

                $nilaiInvoice = (int) $trips->sum('nilai_invoice');
                $uangJalan = (int) $costsForPlat->sum('uang_jalan');

                // Sum extras for this plat's costs
                $platExtraIds = $costsForPlat->pluck('id');
                $platExtras = $extras->whereIn('daily_cost_id', $platExtraIds);
                $biayaLainnya = (int) $platExtras->sum('nominal');

                return [
                    'vehicle_id' => $entry['vehicle_id'],
                    'plat' => $plat,
                    'trip' => $trips->count(),
                    'jumlah_barang' => (float) $trips->sum(fn (Trip $trip): float => $trip->items->sum(fn ($item): float => $item->qty_barang ?? $item->tonase_kg)),
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