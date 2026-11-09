<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleRequest;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('vehicles/index', [
            'vehicles' => Vehicle::query()
                ->orderBy('plat')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function store(VehicleRequest $request): RedirectResponse
    {
        Vehicle::query()->create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kendaraan ditambahkan.']);

        return back();
    }

    public function update(VehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kendaraan diperbarui.']);

        return back();
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        if ($vehicle->trips()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Kendaraan sudah dipakai trip dan tidak dapat dihapus.']);

            return back();
        }

        $vehicle->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kendaraan dihapus.']);

        return back();
    }
}
