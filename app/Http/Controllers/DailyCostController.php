<?php

namespace App\Http\Controllers;

use App\Exports\DailyCostImportTemplate;
use App\Http\Requests\DailyCostRequest;
use App\Imports\DailyCostImport;
use App\Models\DailyCost;
use App\Models\Vehicle;
use App\Services\DailyCostImportPreviewer;
use Illuminate\Http\ImportResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\ExcelReader;

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
            'catatan' => $data['catatan'] ?? null,
        ]);

        // Auto-create or update Vehicle record (similar to Trip controller)
        $vehicle = Vehicle::query()->firstOrCreate(
            ['plat' => $data['no_plat']],
            ['plat' => $data['no_plat']]
        );

        // Save extras if provided
        if (! empty($data['extras'])) {
            $extras = collect($data['extras'])
                ->filter(fn ($extra) => ! empty($extra['nama_biaya']))
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
            'catatan' => $data['catatan'] ?? null,
        ]);

        // Auto-create or update Vehicle record (similar to Trip controller)
        $vehicle = Vehicle::query()->firstOrCreate(
            ['plat' => $data['no_plat']],
            ['plat' => $data['no_plat']]
        );

        // Sync extras: remove old, add new
        $dailyCost->extras()->delete();

        if (! empty($data['extras'])) {
            $extras = collect($data['extras'])
                ->filter(fn ($extra) => ! empty($extra['nama_biaya']))
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

    /**
     * Download Daily Cost import template.
     */
    public function downloadTemplate(): ImportResponse
    {
        return (new DailyCostImportTemplate)->download('daily-cost-import-template.xlsx');
    }

    /**
     * Import daily costs from Excel file.
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $path = $request->file('file')->getRealPath();

        (new DailyCostImport)->import($path, Excel::XLSX);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Import biaya harian berhasil.']);

        return back();
    }

    /**
     * Preview daily costs from Excel file.
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $path = $request->file('file')->getRealPath();

        // Use Maatwebsite Excel to read the file
        $reader = ExcelReader::create($path, Excel::XLSX);
        $reader->withHeadingRow();
        $reader->sheet('Daily Cost Import');

        $rows = [];
        while ($reader->valid()) {
            $rows[] = $reader->current();
            $reader->next();
        }
        $reader->close();

        $previewer = new DailyCostImportPreviewer;
        $preview = $previewer->parse($rows);

        $totalUangJalan = $previewer->totalUangJalan($preview['rows']);

        return response()->json([
            'rows' => $preview['rows'],
            'total' => $preview['total'],
            'totalUangJalan' => $totalUangJalan,
            'errors' => $preview['errors'],
            'plats' => $previewer->getUniquePlats($preview['rows']),
        ]);
    }
}
