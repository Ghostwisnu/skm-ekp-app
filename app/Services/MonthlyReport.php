<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\Invoice;
use App\Models\DailyCost;
use App\Models\DailyCostExtra;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MonthlyReport
{
    /**
     * Get monthly report data as a calendar-like table.
     * Returns daily breakdown for a given month/year or date range.
     *
     * @param  array{from?: ?string, to?: ?string, vehicle_id?: ?int|?string, client_id?: ?int|?string, month?: ?int, year?: ?int}  $filters
     * @return array{
     *     days: array<int, array{tanggal: string, trip: int, biaya_harian: int, invoice: int, profit: int}>,
     *     summary: array{total_trip: int, total_biaya: int, total_invoice: int, total_profit: int},
     *     month: int,
     *     year: int
     * }
     */
    public function dailyBreakdown(array $filters = []): array
    {
        // Determine month/year from filters or use current
        $month = $filters['month'] ?? (int) Carbon::now()->format('m');
        $year = $filters['year'] ?? (int) Carbon::now()->format('Y');
        
        // If from/to provided, use those instead
        $from = $filters['from'] ?? Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $to = $filters['to'] ?? Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
        
        // If month/year explicitly provided and no from/to, override
        if (isset($filters['month']) && isset($filters['year']) && !isset($filters['from']) && !isset($filters['to'])) {
            $from = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
            $to = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();
        }

        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();
        $month = (int) Carbon::parse($from)->format('m');
        $year = (int) Carbon::parse($from)->format('Y');

        // Get all dates in the range
        $dates = [];
        $current = $fromDate->copy();
        while ($current->lte($toDate)) {
            $dateStr = $current->format('Y-m-d');
            if (!isset($dates[$dateStr])) {
                $dates[$dateStr] = [
                    'tanggal' => $dateStr,
                    'trip' => 0,
                    'biaya_harian' => 0,
                    'invoice' => 0,
                    'profit' => 0,
                ];
            }
            $current->addDay();
        }

        // Get trips in date range
        $trips = Trip::query()
            ->withSum('invoiceLines as nilai_invoice', 'jumlah')
            ->when($from, fn($q) => $q->whereDate('tanggal', '>=', $fromDate))
            ->when($to, fn($q) => $q->whereDate('tanggal', '<=', $toDate))
            ->when($filters['vehicle_id'] ?? null, fn($q, $id) => $q->where('vehicle_id', $id))
            ->when($filters['client_id'] ?? null, fn($q, $id) => $q->where('client_id', $id))
            ->get();

        // Get invoices in date range
        $invoices = Invoice::query()
            ->when($from, fn($q) => $q->whereDate('tanggal_awal', '>=', $fromDate))
            ->when($to, fn($q) => $q->whereDate('tanggal_akhir', '<=', $toDate))
            ->get();

        // Get daily costs in date range
        $costs = DailyCost::query()
            ->with('extras')
            ->when($from, fn($q) => $q->whereDate('tanggal', '>=', $fromDate))
            ->when($to, fn($q) => $q->whereDate('tanggal', '<=', $toDate))
            ->get();

        // Filter costs by vehicle if vehicle_id provided
        if ($filters['vehicle_id'] ?? null) {
            $plat = \App\Models\Vehicle::query()->where('id', $filters['vehicle_id'])->value('plat');
            if ($plat) {
                $costs = $costs->where('no_plat', $plat);
            }
        }

        // Aggregate trips by date
        foreach ($trips as $trip) {
            $date = $trip->tanggal->toDateString();
            if (isset($dates[$date])) {
                $dates[$date]['trip'] += 1;
                $dates[$date]['invoice'] += (int) $trip->nilai_invoice;
            } else {
                // Date not in range, skip
            }
        }

        // Aggregate invoices by date (using tanggal_awal as reference)
        foreach ($invoices as $invoice) {
            $date = $invoice->tanggal_awal->toDateString();
            if (isset($dates[$date])) {
                $dates[$date]['invoice'] += (int) $invoice->total;
            }
        }

        // Aggregate daily costs by date
        foreach ($costs as $cost) {
            $date = $cost->tanggal->toDateString();
            if (isset($dates[$date])) {
                $biayaLainnya = $cost->extras->sum('nominal');
                $dates[$date]['biaya_harian'] += $cost->uang_jalan + $biayaLainnya;
            }
        }

        // Calculate profit for each day
        foreach ($dates as $date => &$data) {
            $data['profit'] = $data['invoice'] - $data['biaya_harian'];
        }
        unset($data);

        // Calculate summary
        $summary = [
            'total_trip' => 0,
            'total_biaya' => 0,
            'total_invoice' => 0,
            'total_profit' => 0,
        ];

        foreach ($dates as $data) {
            $summary['total_trip'] += $data['trip'];
            $summary['total_biaya'] += $data['biaya_harian'];
            $summary['total_invoice'] += $data['invoice'];
            $summary['total_profit'] += $data['profit'];
        }

        return [
            'days' => array_values($dates),
            'summary' => $summary,
            'month' => $month,
            'year' => $year,
        ];
    }
}