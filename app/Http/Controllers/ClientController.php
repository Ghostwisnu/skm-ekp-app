<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('clients/index', [
            'clients' => Client::query()
                ->with('goods')
                ->orderBy('nama')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function store(ClientRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $client = Client::query()->create($request->safe()->except('goods'));
            $client->goods()->createMany($request->validated('goods', []));
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Client ditambahkan.']);

        return back();
    }

    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        DB::transaction(function () use ($request, $client): void {
            $client->update($request->safe()->except('goods'));

            if ($request->has('goods')) {
                $goods = $request->validated('goods', []);
                $retainedIds = [];

                foreach ($goods as $good) {
                    $goodId = $good['id'] ?? null;
                    unset($good['id']);

                    if ($goodId !== null) {
                        $client->goods()->whereKey($goodId)->update($good);
                        $retainedIds[] = $goodId;
                    } else {
                        $client->goods()->create($good);
                    }
                }

                $client->goods()->whereNotIn('id', $retainedIds)->delete();
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Client diperbarui.']);

        return back();
    }

    public function destroy(Client $client): RedirectResponse
    {
        if ($client->trips()->exists() || $client->destinationTrips()->exists() || $client->invoices()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Client sudah dipakai trip/invoice dan tidak dapat dihapus.']);

            return back();
        }

        $client->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Client dihapus.']);

        return back();
    }
}
