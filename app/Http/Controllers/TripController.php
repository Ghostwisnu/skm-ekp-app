<?php

namespace App\Http\Controllers;

use App\Exports\TripImportTemplate;
use App\Http\Requests\TripRequest;
use App\Imports\TripImport;
use App\Models\Client;
use App\Models\ClientGood;
use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Session;
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
                ->select(['id', 'tanggal', 'vehicle_id', 'client_id', 'destination_client_id', 'sopir', 'no_surat_jalan', 'category', 'tujuan', 'invoice_id'])
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
            $category = $request->validated('category') ?? 'kontrak';
            $data['category'] = $category;
            $data['tujuan'] = $category === 'outsourcing' ? ($request->validated('tujuan') ?? null) : null;

            $client = $this->resolveClient(
                empty($data['client_id']) ? null : (int) $data['client_id'],
                $request->validated('client_name'),
            );
            $data['client_id'] = $client->id;

            // Resolve destination client from ID or name (for kontrak only)
            if ($category !== 'outsourcing') {
                $destinationClient = $this->resolveClient(
                    empty($data['destination_client_id']) ? null : (int) $data['destination_client_id'],
                    $request->validated('destination_client_name'),
                );
                $data['destination_client_id'] = $destinationClient->id;
            } else {
                $data['destination_client_id'] = null;
            }

            $vehicle = Vehicle::query()->firstOrCreate(['plat' => $request->validated('plat')]);
            $data['vehicle_id'] = $vehicle->id;
            $data['sopir'] = $vehicle->nama_supir ?? '';

            $trip = Trip::query()->create($data);
            $trip->items()->createMany($this->resolveItems(
                (int) $data['client_id'],
                $data['destination_client_id'],
                $category,
                $data['tujuan'],
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
            $category = $request->validated('category') ?? 'kontrak';
            $data['category'] = $category;
            $data['tujuan'] = $category === 'outsourcing' ? ($request->validated('tujuan') ?? null) : null;

            $client = $this->resolveClient(
                empty($data['client_id']) ? null : (int) $data['client_id'],
                $request->validated('client_name'),
            );
            $data['client_id'] = $client->id;

            // Resolve destination client from ID or name
            if ($category !== 'outsourcing') {
                $destinationClient = $this->resolveClient(
                    isset($data['destination_client_id']) ? (int) $data['destination_client_id'] : null,
                    $request->validated('destination_client_name'),
                );
                $data['destination_client_id'] = $destinationClient->id;
            } else {
                $data['destination_client_id'] = null;
            }

            $trip->update($data);
            $trip->items()->delete();
            $trip->items()->createMany($this->resolveItems(
                (int) $data['client_id'],
                $data['destination_client_id'],
                $category,
                $data['tujuan'],
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

    /**
     * Download Trip import template.
     */
    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return Excel::download(new TripImportTemplate(), 'trip-import-template.xlsx');
    }

    /**
     * Show import form page.
     */
    public function importForm(): Response
    {
        return Inertia::render('trips/import');
    }

    /**
     * Preview trips from Excel file (store in session).
     */
    public function importPreview(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $import = new TripImport();
        \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

        Session::put('trips_import_preview', [
            'previewData' => $import->previewData,
            'errors' => $import->errors,
        ]);

        return redirect()->route('trips.import-confirm');
    }

    /**
     * Show import confirmation page.
     */
    public function importConfirm(): Response
    {
        $data = Session::get('trips_import_preview', ['previewData' => [], 'errors' => []]);

        if (empty($data['previewData'])) {
            return redirect()->route('trips.import')->with('error', 'Tidak ada data untuk dipreview.');
        }

        // Group by no_surat_jalan
        $grouped = collect($data['previewData'])
            ->groupBy('no_surat_jalan')
            ->map(function ($rows, $noSuratJalan) {
                $firstRow = $rows->first();
                return [
                    'no_surat_jalan' => $noSuratJalan,
                    'tanggal' => $firstRow['tanggal'],
                    'plat' => $firstRow['plat'],
                    'client_name' => $firstRow['client_name'],
                    'destination_client' => $firstRow['destination_client'] ?? null,
                    'category' => $firstRow['category'],
                    'tujuan' => $firstRow['tujuan'] ?? null,
                    'items' => $rows->map(fn ($row) => [
                        'jenis_barang' => $row['jenis_barang'],
                        'qty_barang' => $row['qty_barang'],
                        'total_harga' => $row['total_harga'],
                    ])->values()->toArray(),
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('trips/import-preview', [
            'previewData' => $data['previewData'],
            'grouped' => $grouped,
            'errors' => $data['errors'],
        ]);
    }

    /**
     * Store imported trips.
     */
    public function importStore(Request $request): RedirectResponse
    {
        $data = Session::pull('trips_import_preview', null);

        if ($data === null || empty($data['previewData'])) {
            return redirect()->route('trips.import')->with('error', 'Sesi data preview habis.');
        }

        DB::transaction(function () use ($data): void {
            $grouped = collect($data['previewData'])->groupBy('no_surat_jalan');

            foreach ($grouped as $noSuratJalan => $rows) {
                $firstRow = $rows->first();

                $category = strtolower(trim((string) ($firstRow['category'] ?? 'kontrak')));
                $category = in_array($category, ['kontrak', 'outsourcing']) ? $category : 'kontrak';

                $clientName = trim((string) ($firstRow['client_name'] ?? ''));
                if ($clientName === '') {
                    continue;
                }
                $client = Client::query()->firstOrCreate(['nama' => $clientName]);

                $plat = trim((string) ($firstRow['plat'] ?? ''));
                if ($plat === '') {
                    continue;
                }
                $vehicle = Vehicle::query()->firstOrCreate(['plat' => $plat]);

                $destinationClientId = null;
                if ($category === 'kontrak') {
                    $destinationClientName = trim((string) ($firstRow['destination_client'] ?? ''));
                    if ($destinationClientName !== '') {
                        $destinationClient = Client::query()
                            ->where('nama', $destinationClientName)
                            ->where('id', '!=', $client->id)
                            ->firstOrCreate(['nama' => $destinationClientName]);
                        if ($destinationClient) {
                            $destinationClientId = $destinationClient->id;
                        }
                    }
                }

                $tripData = [
                    'tanggal' => $firstRow['tanggal'] ?? now()->format('Y-m-d'),
                    'vehicle_id' => $vehicle->id,
                    'client_id' => $client->id,
                    'destination_client_id' => $destinationClientId,
                    'sopir' => $vehicle->nama_supir ?? '',
                    'no_surat_jalan' => $noSuratJalan,
                    'category' => $category,
                    'tujuan' => $category === 'outsourcing' ? ($firstRow['tujuan'] ?? null) : null,
                    'invoice_id' => null,
                ];

                $trip = Trip::query()->create($tripData);

                $items = [];
                $goodsClientId = $category === 'kontrak' ? $destinationClientId : $client->id;
                $sourceClient = $client;

                foreach ($rows as $row) {
                    $jenisBarang = trim((string) ($row['jenis_barang'] ?? ''));
                    if ($jenisBarang === '') {
                        continue;
                    }

                    $qty = (float) ($row['qty_barang'] ?? 0);
                    $totalHarga = (int) ($row['total_harga'] ?? 0);

                    $good = ClientGood::query()
                        ->where('client_id', $goodsClientId)
                        ->where('jenis_barang', $jenisBarang)
                        ->first();

                    if (! $good) {
                        $good = ClientGood::query()->create([
                            'client_id' => $goodsClientId,
                            'jenis_barang' => $jenisBarang,
                            'pricing_mode' => 'per_qty',
                            'harga' => 0,
                        ]);
                    }

                    $route = match ($category) {
                        'outsourcing' => $sourceClient->nama.' → '.($row['tujuan'] ?? '-'),
                        default => $destinationClientId
                            ? $sourceClient->nama.' → '.Client::query()->find($destinationClientId)->nama
                            : $sourceClient->nama,
                    };

                    $items[] = [
                        'client_good_id' => $good->id,
                        'jenis_barang' => $good->jenis_barang,
                        'rute' => $route,
                        'pricing_mode' => $good->pricing_mode,
                        'qty_barang' => $qty,
                        'tonase_kg' => $qty,
                        'harga_satuan' => $good->harga,
                        'total_harga' => $totalHarga,
                    ];
                }

                if (! empty($items)) {
                    $trip->items()->createMany($items);
                }
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Import trip berhasil.']);

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

        $name = trim((string) $clientName);
        if ($name === '') {
            throw ValidationException::withMessages([
                'client_name' => 'Client name is required.',
            ]);
        }

        return Client::query()->firstOrCreate(['nama' => $name]);
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
    private function resolveItems(int $clientId, ?int $destinationClientId, string $category, ?string $tujuanText, array $items): array
    {
        $sourceClient = Client::query()->findOrFail($clientId);
        $route = match ($category) {
            'outsourcing' => $sourceClient->nama.' → '.($tujuanText ?? '-'),
            default => $destinationClientId !== null
                ? "{$sourceClient->nama} → ".Client::query()->findOrFail($destinationClientId)->nama
                : $sourceClient->nama,
        };

        // For kontrak, goods belong to destination client; for outsourcing, goods belong to source client
        $goodsClientId = $category === 'kontrak' ? $destinationClientId : $clientId;
        $goodsClient = $goodsClientId ? Client::query()->findOrFail($goodsClientId) : $sourceClient;

        return array_map(function (array $item, int $index) use ($goodsClientId, $route): array {
            // client_good_id can be empty string from frontend - treat as "create new"
            $hasExistingGoodId = ! empty($item['client_good_id']);

            // For kontrak, find good from destination client; for outsourcing, from source client
            $good = $hasExistingGoodId
                ? ClientGood::query()
                    ->where('client_id', $goodsClientId)
                    ->find($item['client_good_id'])
                : ClientGood::query()->firstOrCreate(
                    ['client_id' => $goodsClientId, 'jenis_barang' => $item['jenis_barang']],
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
