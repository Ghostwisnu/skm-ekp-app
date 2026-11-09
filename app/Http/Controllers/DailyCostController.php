<?php

namespace App\Http\Controllers;

use App\Http\Requests\DailyCostRequest;
use App\Models\DailyCost;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DailyCostController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['from', 'to', 'vehicle_id']);
        $vehicle = isset($filters['vehicle_id'])
            ? Vehicle::query()->find($filters['vehicle_id'])
            : null;

        return Inertia::render('daily-costs/index', [
            'dailyCosts' => DailyCost::query()
                ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('tanggal', '>=', $from))
                ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('tanggal', '<=', $to))
                ->when($vehicle, fn ($query, $vehicle) => $query->where('no_plat', $vehicle->plat))
                ->latest('tanggal')
                ->latest('id')
                ->paginate(15)
                ->withQueryString(),
            'filters' => (object) $filters,
            'vehicles' => Vehicle::query()
                ->orderBy('plat')
                ->get(['id', 'plat']),
        ]);
    }

    public function store(DailyCostRequest $request): RedirectResponse
    {

        DailyCost::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Biaya harian disimpan.']);

        return back();
    }

    public function update(DailyCostRequest $request, DailyCost $dailyCost): RedirectResponse
    {

        $dailyCost->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Biaya harian diperbarui.']);

        return back();
    }
}
