<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Kinerja Plat</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #e2e8f0; padding: 4px 6px; }
        th { background: #f8fafc; text-align: left; }
        .r { text-align: right; }
        .muted { color: #64748b; }
    </style>
</head>
<body>
    <h1>Laporan Kinerja Plat</h1>
    <div class="muted">
        Periode: {{ $filters['from'] ?? '-' }} s/d {{ $filters['to'] ?? '-' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Plat</th><th class="r">Trip</th><th class="r">Qty Barang</th>
                <th class="r">Nilai Invoice</th><th class="r">Biaya</th><th class="r">Selisih</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['plat'] }}</td>
                    <td class="r">{{ $row['trip'] }}</td>
                    <td class="r">{{ number_format($row['jumlah_barang'], 2, ',', '.') }}</td>
                    <td class="r">Rp {{ number_format($row['nilai_invoice'], 0, ',', '.') }}</td>
                    <td class="r">Rp {{ number_format($row['biaya'], 0, ',', '.') }}</td>
                    <td class="r">Rp {{ number_format($row['margin'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>