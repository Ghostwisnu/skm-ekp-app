<?php

namespace App\Http\Controllers;

use App\Http\Requests\TripRequest;
use App\Models\Client;
use App\Models\ClientGood;
use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TripController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only(['from', 'to', 'vehicle_id', 'client_id']);

        return Inertia::render('trips/index', [
            'trips' => Trip::query()
                ->with(['vehicle', 'client', 'destinationClient'])
                ->withSum('items as total_qty', 'qty_barang')
                ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('tanggal', '>=', $from))
                ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('tanggal', '<=', $to))
                ->when($filters['vehicle_id'] ?? null, fn ($query, $id) => $query->where('vehicle_id', $id))
                ->when($filters['client_id'] ?? null, fn ($query, $id) => $query->where('client_id', $id))
                ->latest('tanggal')->latest('id')
                ->paginate(15)
                ->withQueryString(),
            'filters' => (object) $filters,
            'vehicles' => Vehicle::query()->orderBy('plat')->get(['id', 'plat', 'nama_supir']),
            'clients' => Client::query()->orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('trips/form', [
            'trip' => null,
            ...$this->formOptions(),
        ]);
    }

    public function store(TripRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $data = $request->safe()->except([
                'items',
                'plat',
                'client_name',
                'destination_client_name',
            ]);
            $client = $this->resolveClient(
                isset($data['client_id']) ? (int) $data['client_id'] : null,
                $request->validated('client_name'),
            );
            $destinationClient = $this->resolveClient(
                isset($data['destination_client_id']) ? (int) $data['destination_client_id'] : null,
                $request->validated('destination_client_name'),
            );
            $data['client_id'] = $client->id;
            $data['destination_client_id'] = $destinationClient->id;
            $vehicle = Vehicle::query()->firstOrCreate(['plat' => $request->validated('plat')]);
            $data['vehicle_id'] = $vehicle->id;
            $data['sopir'] = $vehicle->nama_supir ?? '';

            $trip = Trip::query()->create($data);
            $trip->items()->createMany($this->resolveItems(
                (int) $data['client_id'],
                (int) $data['destination_client_id'],
                $request->validated('items'),
            ));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Trip disimpan.']);

        return to_route('trips.index');
    }

    public function edit(Trip $trip): Response|RedirectResponse
    {
        if ($trip->invoice_id !== null) {
            return $this->lockedResponse();
        }

        return Inertia::render('trips/form', [
            'trip' => $trip->load(['items', 'vehicle']),
            ...$this->formOptions(),
        ]);
    }

    public function update(TripRequest $request, Trip $trip): RedirectResponse
    {
        if ($trip->invoice_id !== null) {
            return $this->lockedResponse();
        }

        DB::transaction(function () use ($request, $trip): void {
            $data = $request->safe()->except([
                'items',
                'plat',
                'client_name',
                'destination_client_name',
            ]);
            $client = $this->resolveClient(
                isset($data['client_id']) ? (int) $data['client_id'] : null,
                $request->validated('client_name'),
            );
            $destinationClient = $this->resolveClient(
                isset($data['destination_client_id']) ? (int) $data['destination_client_id'] : null,
                $request->validated('destination_client_name'),
            );
            $data['client_id'] = $client->id;
            $data['destination_client_id'] = $destinationClient->id;
            $vehicle = Vehicle::query()->firstOrCreate(['plat' => $request->validated('plat')]);
            $data['vehicle_id'] = $vehicle->id;
            $data['sopir'] = $vehicle->nama_supir ?? '';

            $trip->update($data);
            $trip->items()->delete();
            $trip->items()->createMany($this->resolveItems(
                (int) $data['client_id'],
                (int) $data['destination_client_id'],
                $request->validated('items'),
            ));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Trip diperbarui.']);

        return to_route('trips.index');
    }

    public function destroy(Trip $trip): RedirectResponse
    {
        if ($trip->invoice_id !== null) {
            return $this->lockedResponse();
        }

        $trip->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Trip dihapus.']);

        return to_route('trips.index');
    }

    private function lockedResponse(): RedirectResponse
    {
        Inertia::flash('toast', ['type' => 'error', 'message' => 'Trip sudah masuk invoice dan tidak dapat diubah.']);

        return to_route('trips.index');
    }

    private function resolveClient(?int $clientId, ?string $clientName): Client
    {
        if ($clientId !== null) {
            return Client::query()->findOrFail($clientId);
        }

        return Client::query()->firstOrCreate(['nama' => trim((string) $clientName)]);
    }

    /**
     * @return array{vehicles: Collection<int, Vehicle>, clients: Collection<int, Client>}
     */
    private function formOptions(): array
    {
        return [
            'vehicles' => Vehicle::query()
                ->orderBy('plat')
                ->get(['id', 'plat', 'nama_supir', 'no_hp_supir']),
            'clients' => Client::query()
                ->with('goods')
                ->orderBy('nama')
                ->get(),
        ];
    }

    /**
     * @param  list<array{client_good_id: int|null, jenis_barang: string, qty_barang: int|float|string, total_harga?: int|string|null}>  $items
     * @return list<array<string, int|float|string|null>>
     */
    private function resolveItems(int $clientId, int $destinationClientId, array $items): array
    {
        $sourceClient = Client::query()->findOrFail($clientId);
        $destinationClient = Client::query()->findOrFail($destinationClientId);
        $route = "{$sourceClient->nama} → {$destinationClient->nama}";

        return array_map(function (array $item, int $index) use ($clientId, $route): array {
            $good = isset($item['client_good_id'])
                ? ClientGood::query()
                    ->where('client_id', $clientId)
                    ->find($item['client_good_id'])
                : ClientGood::query()->firstOrCreate(
                    ['client_id' => $clientId, 'jenis_barang' => $item['jenis_barang']],
                    ['pricing_mode' => 'per_qty', 'harga' => 0],
                );

            if ($good === null) {
                throw ValidationException::withMessages([
                    "items.{$index}.client_good_id" => 'Jenis barang tidak sesuai dengan client yang dipilih.',
                ]);
            }

            $qty = (float) $item['qty_barang'];
            $total = $good->pricing_mode === 'flat_trip'
                ? $good->harga
                : (int) round($qty * $good->harga);
            $total = $item['total_harga'] ?? $total;

            return [
                'client_good_id' => $good->id,
                'jenis_barang' => $good->jenis_barang,
                'rute' => $route,
                'pricing_mode' => $good->pricing_mode,
                'qty_barang' => $qty,
                'tonase_kg' => $qty,
                'harga_satuan' => $good->harga,
                'total_harga' => $total,
            ];
        }, $items, array_keys($items));
    }
}
