<?php

namespace App\Http\Controllers;

use App\Exports\VehiclePerformanceExport;
use App\Models\Vehicle;
use App\Services\VehiclePerformanceReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ReportController extends Controller
{
    public function __construct(private readonly VehiclePerformanceReport $report) {}

    public function vehicles(Request $request): Response
    {
        $filters = $request->only(['from', 'to', 'vehicle_id']);

        return Inertia::render('reports/vehicles', [
            'rows' => $this->report->rows($filters),
            'filters' => (object) $filters,
            'vehicles' => Vehicle::query()->orderBy('plat')->get(['id', 'plat']),
        ]);
    }

    public function pdf(Request $request): HttpResponse
    {
        $filters = $request->only(['from', 'to', 'vehicle_id']);

        return Pdf::loadView('pdf.vehicle-report', [
            'rows' => $this->report->rows($filters),
            'filters' => $filters,
        ])->download('laporan-kinerja-plat.pdf');
    }

    public function excel(Request $request): BinaryFileResponse
    {
        $rows = $this->report->rows($request->only(['from', 'to', 'vehicle_id']));

        return Excel::download(new VehiclePerformanceExport($rows), 'laporan-kinerja-plat.xlsx');
    }
}
