<?php

namespace App\Http\Controllers;

use App\Exports\DailyCostImportTemplate;
use App\Http\Requests\DailyCostRequest;
use App\Imports\DailyCostImport;
use App\Models\DailyCost;
use App\Models\Vehicle;
use App\Services\NoSuratJalanGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Session;
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

        // Auto-generate no_surat_jalan if not provided
        $noSuratJalan = trim((string) ($data['no_surat_jalan'] ?? ''));
        if ($noSuratJalan === '') {
            $noSuratJalan = NoSuratJalanGenerator::generateForDailyCost($data['tanggal'], $data['no_plat']);
        }

        $dailyCost = DailyCost::query()->create([
            'tanggal' => $data['tanggal'],
            'no_plat' => $data['no_plat'],
            'no_surat_jalan' => $noSuratJalan,
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

        // Auto-generate no_surat_jalan if not provided
        $noSuratJalan = trim((string) ($data["no_surat_jalan"] ?? ""));
        if ($noSuratJalan === "") {
            $noSuratJalan = NoSuratJalanGenerator::generateForDailyCost($data['tanggal'], $data['no_plat']);
        }

        $dailyCost->update([
            'tanggal' => $data['tanggal'],
            'no_plat' => $data['no_plat'],
            'no_surat_jalan' => $noSuratJalan,
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
    public function downloadTemplate(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        return Excel::download(new DailyCostImportTemplate(), 'daily-cost-import-template.xlsx');
    }

    /**
     * Show import form page.
     */
    public function importForm(): Response
    {
        return Inertia::render('daily-costs/import');
    }

    /**
     * Preview daily costs from Excel file (store in session).
     */
    public function importPreview(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $import = new DailyCostImport();
        \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

        Session::put('daily_costs_import_preview', [
            'previewData' => $import->previewData,
            'errors' => $import->errors,
        ]);

        return redirect()->route('daily-costs.import-confirm');
    }

    /**
     * Show import confirmation page.
     */
    public function importConfirm(): Response
    {
        $data = Session::get('daily_costs_import_preview', ['previewData' => [], 'errors' => []]);

        if (empty($data['previewData'])) {
            return redirect()->route('daily-costs.import')->with('error', 'Tidak ada data untuk dipreview.');
        }

        return Inertia::render('daily-costs/import-preview', [
            'previewData' => $data['previewData'],
            'errors' => $data['errors'],
        ]);
    }

    /**
     * Store imported daily costs.
     */
    public function importStore(Request $request): RedirectResponse
    {
        $data = Session::pull('daily_costs_import_preview', null);

        if ($data === null || empty($data['previewData'])) {
            return redirect()->route('daily-costs.import')->with('error', 'Sesi data preview habis.');
        }

        $errors = [];

        DB::transaction(function () use ($data, &$errors): void {
            foreach ($data['previewData'] as $row) {
                $plat = strtoupper(trim((string) ($row['plat'] ?? '')));
                if ($plat === '') {
                    continue;
                }

                $tanggal = $row['tanggal'] ?? now()->format('Y-m-d');
                $noSuratJalan = strtoupper(trim((string) ($row['no_surat_jalan'] ?? '')));
                
                // Auto-generate if somehow missing at store time
                if ($noSuratJalan === '') {
                    $noSuratJalan = NoSuratJalanGenerator::generateForDailyCost($tanggal, $plat);
                }

                // Check for duplicate no_surat_jalan before creating
                if (DailyCost::query()->where('no_surat_jalan', $noSuratJalan)->exists()) {
                    $errors[] = "No Surat Jalan {$noSuratJalan}: sudah ada di database";
                    continue;
                }

                $vehicle = Vehicle::query()->firstOrCreate(['plat' => $plat]);

                try {
                    $dailyCost = DailyCost::query()->create([
                        'tanggal' => $tanggal,
                        'no_plat' => $plat,
                        'no_surat_jalan' => $noSuratJalan,
                        'uang_jalan' => (int) ($row['uang_jalan'] ?? 0),
                        'catatan' => trim((string) ($row['catatan'] ?? '')),
                    ]);

                    if (! empty($row['extras'])) {
                        $dailyCost->extras()->createMany($row['extras']);
                    }
                } catch (\Exception $e) {
                    $errors[] = "Baris untuk {$noSuratJalan}: " . $e->getMessage();
                }
            }
        });

        if (! empty($errors)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Beberapa data gagal diimport: ' . implode('; ', $errors)]);
        } else {
            Inertia::flash('toast', ['type' => 'success', 'message' => 'Import biaya harian berhasil.']);
        }

        return to_route('daily-costs.index');
    }
}
