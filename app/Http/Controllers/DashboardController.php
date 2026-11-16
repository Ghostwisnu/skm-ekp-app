<?php

namespace App\Http\Controllers;

use App\Models\DailyCost;
use App\Models\Invoice;
use App\Models\Trip;
use App\Models\TripItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $from = $request->query('from');
        $to = $request->query('to');

        $tripQuery = Trip::query();
        $dailyCostQuery = DailyCost::query();
        $invoiceQuery = Invoice::query();
        $tripItemQuery = TripItem::query();

        if ($from) {
            $fromDate = Carbon::parse($from)->startOfDay();
            $tripQuery->whereDate('tanggal', '>=', $fromDate);
            $dailyCostQuery->whereDate('tanggal', '>=', $fromDate);
            $invoiceQuery->whereDate('tanggal_awal', '>=', $fromDate);
        }

        if ($to) {
            $toDate = Carbon::parse($to)->endOfDay();
            $tripQuery->whereDate('tanggal', '<=', $toDate);
            $dailyCostQuery->whereDate('tanggal', '<=', $toDate);
            $invoiceQuery->whereDate('tanggal_akhir', '<=', $toDate);
        }

        $periode = $this->buildPeriodeString($from, $to);

        return Inertia::render('dashboard', [
            'periode' => $periode,
            'filters' => ['from' => $from, 'to' => $to],
            'stats' => [
                'trips' => (clone $tripQuery)->count(),
                'jumlah_barang' => (float) (clone $tripItemQuery)
                    ->join('trips', 'trip_items.trip_id', '=', 'trips.id')
                    ->when($from, fn($q) => $q->whereDate('trips.tanggal', '>=', Carbon::parse($from)->startOfDay()))
                    ->when($to, fn($q) => $q->whereDate('trips.tanggal', '<=', Carbon::parse($to)->endOfDay()))
                    ->sum('trip_items.qty_barang'),
                'biaya' => (int) (clone $dailyCostQuery)->sum('uang_jalan'),
                'invoice_count' => (clone $invoiceQuery)->count(),
                'invoice_total' => (int) (clone $invoiceQuery)->sum('total'),
            ],
            'recentTrips' => (clone $tripQuery)
                ->with(['vehicle', 'client'])
                ->withSum('items as total_qty', 'qty_barang')
                ->latest('tanggal')->latest('id')
                ->limit(5)
                ->get(),
        ]);
    }

    private function buildPeriodeString(?string $from, ?string $to): string
    {
        if ($from && $to) {
            return Carbon::parse($from)->format('d M Y') . ' - ' . Carbon::parse($to)->format('d M Y');
        }
        if ($from) {
            return 'Mulai ' . Carbon::parse($from)->format('d M Y');
        }
        if ($to) {
            return 'Sampai ' . Carbon::parse($to)->format('d M Y');
        }
        return 'Semua data';
    }
}
