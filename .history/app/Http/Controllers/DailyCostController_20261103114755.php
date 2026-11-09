<?php

namespace App\Http\Controllers;

use App\Http\Requests\DailyCostRequest;
use App\Models\DailyCost;
use App\Models\Trip;
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

        return Inertia::render('daily-costs/index', [
            'trips' => Trip::query()
                ->with(['vehicle', 'dailyCost'])
                ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('tanggal', '>=', $from))
                ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('tanggal', '<=', $to))
                ->when($filters['vehicle_id'] ?? null, fn ($query, $id) => $query->where('vehicle_id', $id))
                ->latest('tanggal')->latest('id')
                ->paginate(15)
                ->withQueryString(),
            'filters' => (object) $filters,
            'vehicles' => Vehicle::query()->orderBy('plat')->get(['id', 'plat']),
            'components' => DailyCost::COMPONENTS,
        ]);
    }

    public function store(DailyCostRequest $request): RedirectResponse
    {
        DailyCost::query()->updateOrCreate(
            ['trip_id' => $request->validated('trip_id')],
            $request->validated(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Biaya harian disimpan.']);

        return back();
    }

    public function update(DailyCostRequest $request, DailyCost $dailyCost): RedirectResponse
    {
        $dailyCost->update($request->safe()->except('trip_id'));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Biaya harian diperbarui.']);

        return back();
    }
}
