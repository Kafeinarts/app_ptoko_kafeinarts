<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan</title>
    <style>
        @page { margin: 22px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .muted { color: #666; }
        .header { border-bottom: 2px solid #2d6a4f; padding-bottom: 8px; margin-bottom: 12px; }
        .header table { width: 100%; }
        .header td { vertical-align: top; }
        .right { text-align: right; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .summary td { padding: 6px 8px; border: 1px solid #ddd; }
        .summary .label { color: #555; background: #f5f7f6; width: 18%; }
        .summary .value { font-weight: bold; width: 32%; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #1b4332; color: #fff; padding: 6px; text-align: left; font-size: 9px; }
        table.data td { padding: 5px 6px; border-bottom: 1px solid #e5e5e5; }
        table.data tr:nth-child(even) td { background: #fafafa; }
        .footer { margin-top: 14px; font-size: 8px; color: #777; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td>
                    <h1>{{ $outlet?->name ?? 'Kafeinarts' }}</h1>
                    <div class="muted">{{ $outlet?->address }}</div>
                    <div class="muted">{{ $outlet?->phone }}{{ $outlet?->email ? ' · '.$outlet->email : '' }}</div>
                </td>
                <td class="right">
                    <div style="font-size:13px;font-weight:bold">LAPORAN PENJUALAN</div>
                    <div class="muted">Periode {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</div>
                    <div class="muted">Dicetak {{ now()->format('d/m/Y H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="summary">
        <tr>
            <td class="label">Penjualan Bersih</td>
            <td class="value">{{ \App\Helpers\CurrencyHelper::format($summary['net_sales']) }}</td>
            <td class="label">Transaksi</td>
            <td class="value">{{ number_format($summary['transactions'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Diskon</td>
            <td class="value">{{ \App\Helpers\CurrencyHelper::format($summary['discount']) }}</td>
            <td class="label">Pajak</td>
            <td class="value">{{ \App\Helpers\CurrencyHelper::format($summary['tax']) }}</td>
        </tr>
        <tr>
            <td class="label">HPP</td>
            <td class="value">{{ \App\Helpers\CurrencyHelper::format($summary['cogs']) }}</td>
            <td class="label">Laba Kotor</td>
            <td class="value">{{ \App\Helpers\CurrencyHelper::format($summary['gross_profit']) }}</td>
        </tr>
        <tr>
            <td class="label">Rata-rata Nota</td>
            <td class="value">{{ \App\Helpers\CurrencyHelper::format($summary['average_ticket']) }}</td>
            <td class="label">Laba Bersih</td>
            <td class="value">{{ \App\Helpers\CurrencyHelper::format($summary['net_profit']) }}</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width:28px">#</th>
                <th>Produk</th>
                <th>Varian</th>
                <th>Kategori</th>
                <th class="right">Qty</th>
                <th class="right">Omzet</th>
                <th class="right">HPP</th>
                <th class="right">Margin</th>
            </tr>
        </thead>
        <tbody>
        @forelse($products as $index => $product)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $product['product'] }}</td>
                <td>{{ $product['variant'] }}</td>
                <td>{{ $product['category'] }}</td>
                <td class="right">{{ number_format($product['qty'], 0, ',', '.') }}</td>
                <td class="right">{{ \App\Helpers\CurrencyHelper::format($product['revenue']) }}</td>
                <td class="right">{{ \App\Helpers\CurrencyHelper::format($product['cogs']) }}</td>
                <td class="right">{{ \App\Helpers\CurrencyHelper::format($product['margin']) }}</td>
            </tr>
        @empty
            <tr><td colspan="8" style="text-align:center;padding:18px;color:#777">Tidak ada penjualan pada periode ini.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="footer">Kafeinarts POS · Laporan digenerate otomatis oleh sistem</div>
</body>
</html>
