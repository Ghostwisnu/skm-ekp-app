<?php

namespace App\Http\Controllers;

use App\Http\Requests\DailyCostRequest;
use App\Models\DailyCost;
use App\Models\DailyCostExtra;
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
                ->with('extras')
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
        $data = $request->validated();

        $dailyCost = DailyCost::query()->create([
            'tanggal' => $data['tanggal'],
            'no_plat' => $data['no_plat'],
            'no_surat_jalan' => $data['no_surat_jalan'],
            'uang_jalan' => $data['uang_jalan'],
        ]);

        // Save extras if provided
        if (!empty($data['extras'])) {
            $extras = collect($data['extras'])
                ->filter(fn ($extra) => !empty($extra['nama_biaya']))
                ->map(fn ($extra) => [
                    'nama_biaya' => $extra['nama_biaya'],
                    'nominal' => $extra['nominal'] ?? 0,
                ]);

            $dailyCost->extras()->createMany($extras->all());
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Biaya harian disimpan.']);

        return back();
    }

    public function update(DailyCostRequest $request, DailyCost $dailyCost): RedirectResponse
    {
        $data = $request->validated();

        $dailyCost->update([
            'tanggal' => $data['tanggal'],
            'no_plat' => $data['no_plat'],
            'no_surat_jalan' => $data['no_surat_jalan'],
            'uang_jalan' => $data['uang_jalan'],
        ]);

        // Sync extras: remove old, add new
        $dailyCost->extras()->delete();

        if (!empty($data['extras'])) {
            $extras = collect($data['extras'])
                ->filter(fn ($extra) => !empty($extra['nama_biaya']))
                ->map(fn ($extra) => [
                    'nama_biaya' => $extra['nama_biaya'],
                    'nominal' => $extra['nominal'] ?? 0,
                ]);

            $dailyCost->extras()->createMany($extras->all());
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Biaya harian diperbarui.']);

        return back();
    }

    public function destroy(DailyCost $dailyCost): RedirectResponse
    {
        $dailyCost->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Biaya harian dihapus.']);

        return back();
    }
}