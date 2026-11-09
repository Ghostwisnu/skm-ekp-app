<?php

namespace App\Http\Controllers;

use App\Exports\InvoiceExport;
use App\Http\Requests\InvoiceRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Setting;
use App\Services\InvoiceBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceBuilder $builder) {}

    public function index(Request $request): Response
    {
        return Inertia::render('invoices/index', [
            'invoices' => Invoice::query()
                ->with(['client', 'destinationClient'])
                ->when($request->query('client_id'), fn ($query, $id) => $query->where('client_id', $id))
                ->latest('id')
                ->paginate(15)
                ->withQueryString(),
            'clients' => Client::query()->orderBy('nama')->get(['id', 'nama']),
            'filters' => (object) $request->only(['client_id']),
        ]);
    }

    public function create(Request $request): Response
    {
        $filters = $request->only(['client_id', 'destination_client_id', 'tanggal_awal', 'tanggal_akhir']);
        $ready = ! empty($filters['client_id'])
            && ! empty($filters['destination_client_id'])
            && ! empty($filters['tanggal_awal'])
            && ! empty($filters['tanggal_akhir']);

        return Inertia::render('invoices/create', [
            'clients' => Client::query()->orderBy('nama')->get(['id', 'nama']),
            'filters' => (object) $filters,
            'ppnDefault' => Setting::ppnDefault(),
            'pending' => $ready
                ? $this->builder->pendingItems(
                    (int) $filters['client_id'],
                    (int) $filters['destination_client_id'],
                    $filters['tanggal_awal'],
                    $filters['tanggal_akhir'],
                )
                    ->map(fn ($item): array => [
                        'id' => $item->id,
                        'tanggal' => $item->trip->tanggal->format('Y-m-d'),
                        'plat' => $item->trip->vehicle->plat,
                        'no_surat_jalan' => $item->trip->no_surat_jalan,
                        'jenis_barang' => $item->jenis_barang,
                        'rute' => $item->trip->destination_client_id !== null
                            ? "{$item->trip->client->nama} → {$item->trip->destinationClient->nama}"
                            : $item->rute,
                        'qty_barang' => $item->qty_barang ?? $item->tonase_kg,
                        'pricing_mode' => $item->pricing_mode,
                        'harga_satuan' => $item->harga_satuan,
                        'total_harga' => $item->total_harga,
                    ])->values()
                : [],
        ]);
    }

    public function store(InvoiceRequest $request): RedirectResponse
    {
        $invoice = $this->builder->build($request->validated(), $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Invoice dibuat.']);

        return to_route('invoices.show', $invoice);
    }

    public function show(Invoice $invoice): Response
    {
        return Inertia::render('invoices/show', [
            'invoice' => $invoice->load(['client', 'destinationClient', 'lines']),
            'company' => $this->companyDetails($invoice),
        ]);
    }

    public function finalize(Invoice $invoice): RedirectResponse
    {
        $invoice->update(['status' => Invoice::STATUS_FINAL]);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Invoice difinalkan.']);

        return back();
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        if (! $invoice->isDraft()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Hanya invoice draft yang dapat dihapus.']);

            return back();
        }

        $invoice->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Invoice dihapus.']);

        return to_route('invoices.index');
    }

    public function pdf(Invoice $invoice): HttpResponse
    {
        $invoice->load(['client', 'destinationClient', 'lines']);

        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company' => $this->companyDetails($invoice),
        ])
            ->download($this->fileName($invoice, 'pdf'));
    }

    public function excel(Invoice $invoice): BinaryFileResponse
    {
        $invoice->load('lines');

        return Excel::download(new InvoiceExport($invoice), $this->fileName($invoice, 'xlsx'));
    }

    private function fileName(Invoice $invoice, string $extension): string
    {
        return Str::slug($invoice->no_invoice, '-').'.'.$extension;
    }

    /**
     * @return array{name: string, address: string, bank_name: string, bank_account: string}
     */
    private function companyDetails(Invoice $invoice): array
    {
        $settings = Setting::companyDetails();

        return [
            'name' => $invoice->company_name ?? $settings['company_name'],
            'address' => $invoice->company_address ?? $settings['company_address'],
            'bank_name' => $invoice->company_bank_name ?? $settings['company_bank_name'],
            'bank_account' => $invoice->company_bank_account ?? $settings['company_bank_account'],
        ];
    }
}
