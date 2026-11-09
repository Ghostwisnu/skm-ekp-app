<?php

namespace App\Http\Controllers;

use App\Models\DailyCost;
use App\Models\Invoice;
use App\Models\Trip;
use App\Models\TripItem;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $costColumns = collect(DB::getSchemaBuilder()->getColumnListing((new DailyCost())->getTable()))
            ->filter(fn (string $column) => str_starts_with(strtolower($column), 'biaya_'))
            ->values();
        $costSql = $costColumns->isEmpty()
            ? '0'
            : $costColumns->map(fn (string $column) => "COALESCE(`{$column}`, 0)")->implode(' + ');

        return Inertia::render('dashboard', [
            'periode' => 'Semua data',
            'stats' => [
                'trips' => Trip::query()->count(),
                'jumlah_barang' => (float) TripItem::query()->sum('qty_barang'),
                'biaya' => (int) DailyCost::query()->sum(DB::raw($costSql)),
                'invoice_count' => Invoice::query()->count(),
                'invoice_total' => (int) Invoice::query()->sum('total'),
            ],
            'recentTrips' => Trip::query()
                ->with(['vehicle', 'client'])
                ->withSum('items as total_qty', 'qty_barang')
                ->latest('tanggal')->latest('id')
                ->limit(5)
                ->get(),
        ]);
    }
}