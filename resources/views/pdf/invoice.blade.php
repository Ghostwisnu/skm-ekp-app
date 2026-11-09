<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->no_invoice }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #263238; }
        .heading { text-align: center; margin-bottom: 24px; }
        .heading h1 { font-size: 15px; letter-spacing: .5px; margin: 0 0 4px; }
        .heading p { font-size: 11px; font-weight: bold; margin: 0; }
        .details { width: 100%; margin-bottom: 18px; }
        .details td { vertical-align: top; }
        .recipient { width: 65%; }
        .metadata { width: 35%; }
        .metadata table { width: 100%; border-collapse: collapse; }
        .metadata td { padding: 2px 0; }
        .attention { margin: 0 0 14px; }
        .attention-label { font-weight: bold; margin-bottom: 3px; }
        .attention-name { font-weight: bold; }
        .items { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .items th, .items td { border: 1px solid #87939a; padding: 5px 4px; }
        .items th { background: #f0f2f3; font-size: 9px; text-align: center; }
        .items td { vertical-align: top; }
        .items .center { text-align: center; }
        .items .right { text-align: right; white-space: nowrap; }
        .items .summary-label { text-align: center; font-weight: bold; }
        .items .grand-total td { font-weight: bold; }
        .footer { width: 100%; margin-top: 30px; }
        .footer td { width: 50%; vertical-align: top; }
        .payment-title { margin-bottom: 4px; }
        .payment-details { font-weight: bold; line-height: 1.6; }
        .signature { text-align: center; padding-top: 0; }
        .signature-space { height: 55px; }
        .signature-name { font-weight: bold; }
    </style>
</head>
<body>
    <div class="heading">
        <h1>INVOICE - {{ $invoice->destinationClient?->nama ?? '-' }}</h1>
        <p>PERIODE {{ $invoice->tanggal_awal->format('d/m/Y') }} - {{ $invoice->tanggal_akhir->format('d/m/Y') }}</p>
    </div>

    <table class="details">
        <tr>
            <td class="recipient">
                <strong>{{ $company['name'] ?: 'Perusahaan Pemilik' }}</strong><br>
                {{ $company['address'] }}
            </td>
            <td class="metadata">
                <table>
                    <tr>
                        <td>Date</td>
                        <td>:</td>
                        <td>{{ $invoice->tanggal_akhir->format('d F Y') }}</td>
                    </tr>
                    <tr>
                        <td>Kwitansi</td>
                        <td>:</td>
                        <td>{{ $invoice->no_invoice }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="attention">
        <div class="attention-label">Attention To:</div>
        <div class="attention-name">{{ $invoice->client->nama }}</div>
    </div>

    <table class="items">
        <colgroup>
            <col style="width: 11%">
            <col style="width: 14%">
            <col style="width: 18%">
            <col style="width: 17%">
            <col style="width: 8%">
            <col style="width: 16%">
            <col style="width: 16%">
        </colgroup>
        <thead>
            <tr>
                <th>TGL</th>
                <th>NO. POLISI</th>
                <th>SJ</th>
                <th>UKURAN</th>
                <th>QTY</th>
                <th>HARGA @</th>
                <th>TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->lines as $line)
                <tr>
                    <td class="center">{{ $line->tanggal->format('d-M') }}</td>
                    <td class="center">{{ $line->plat }}</td>
                    <td class="center">{{ $line->no_surat_jalan }}</td>
                    <td class="center">{{ $line->jenis_barang }}</td>
                    <td class="center">{{ number_format($line->qty_barang ?? $line->tonase_kg, 2, ',', '.') }}</td>
                    <td class="right">
                        {{ number_format($line->harga_satuan ?? $line->harga_per_kg, 0, ',', '.') }}
                        @if ($line->pricing_mode === 'flat_trip')
                            <span>(flat)</span>
                        @endif
                    </td>
                    <td class="right">{{ number_format($line->jumlah, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="summary-label">SUB TOTAL</td>
                <td class="right">{{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="6" class="summary-label">
                    PPN {{ rtrim(rtrim(number_format($invoice->ppn_persen, 2, ',', '.'), '0'), ',') }}%
                </td>
                <td class="right">{{ number_format($invoice->ppn_nominal, 0, ',', '.') }}</td>
            </tr>
            <tr class="grand-total">
                <td colspan="6" class="summary-label">GRAND TOTAL</td>
                <td class="right">{{ number_format($invoice->total, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="footer">
        <tr>
            <td>
                <div class="payment-title">Please remit in FULL AMOUNT directly to:</div>
                <div class="payment-details">
                    {{ $company['name'] }}<br>
                    @if ($company['bank_account'])
                        Acc. No: {{ $company['bank_account'] }}<br>
                    @endif
                    {{ $company['bank_name'] }}
                </div>
            </td>
            <td class="signature">
                <div>{{ $invoice->tanggal_akhir->format('d F Y') }}</div>
                <div class="signature-space"></div>
                <div class="signature-name">{{ $company['name'] ?: 'Perusahaan Pemilik' }}</div>
            </td>
        </tr>
    </table>
</body>
</html>
