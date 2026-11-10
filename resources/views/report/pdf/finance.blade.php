<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Laba Rugi</title>
    <style>
        @page { margin: 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .muted { color: #666; }
        .header { border-bottom: 2px solid #2d6a4f; padding-bottom: 8px; margin-bottom: 14px; }
        .header table { width: 100%; }
        .header td { vertical-align: top; }
        .right { text-align: right; }
        table.report { width: 100%; border-collapse: collapse; }
        table.report th { background: #1b4332; color: #fff; padding: 7px; text-align: left; }
        table.report td { padding: 6px 8px; border-bottom: 1px solid #e5e5e5; }
        table.report tr.section td { background: #f1f5f3; font-weight: bold; }
        table.report tr.total td { font-weight: bold; border-top: 1px solid #999; }
        table.report tr.net td { background: #1b4332; color: #fff; font-size: 12px; font-weight: bold; }
        .indent { padding-left: 20px !important; }
        .footer { margin-top: 16px; font-size: 8px; color: #777; text-align: right; }
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
                    <div style="font-size:13px;font-weight:bold">LAPORAN LABA RUGI</div>
                    <div class="muted">Periode {{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</div>
                    <div class="muted">Dicetak {{ now()->format('d/m/Y H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="report">
        <tbody>
            <tr class="section"><td colspan="2">PENDAPATAN</td></tr>
            <tr><td>Penjualan Bruto</td><td class="right">{{ \App\Helpers\CurrencyHelper::format($summary['subtotal']) }}</td></tr>
            <tr><td>Diskon</td><td class="right">({{ \App\Helpers\CurrencyHelper::format($summary['discount']) }})</td></tr>
            <tr><td>Pajak</td><td class="right">{{ \App\Helpers\CurrencyHelper::format($summary['tax']) }}</td></tr>
            <tr class="total"><td>Penjualan Bersih</td><td class="right">{{ \App\Helpers\CurrencyHelper::format($summary['net_sales']) }}</td></tr>
            <tr class="section"><td colspan="2">HARGA POKOK PENJUALAN</td></tr>
            <tr><td>HPP</td><td class="right">({{ \App\Helpers\CurrencyHelper::format($summary['cogs']) }})</td></tr>
            <tr class="total"><td>Laba Kotor</td><td class="right">{{ \App\Helpers\CurrencyHelper::format($summary['gross_profit']) }}</td></tr>
            <tr class="section"><td colspan="2">BIAYA OPERASIONAL</td></tr>
            @forelse($expensesByCategory as $expense)
                <tr><td class="indent">{{ $expense->category }}</td><td class="right">{{ \App\Helpers\CurrencyHelper::format($expense->total) }}</td></tr>
            @empty
                <tr><td class="indent muted">Tidak ada pengeluaran</td><td class="right">—</td></tr>
            @endforelse
            <tr class="total"><td>Total Biaya</td><td class="right">({{ \App\Helpers\CurrencyHelper::format($summary['expenses']) }})</td></tr>
            <tr class="section"><td colspan="2">PENDAPATAN LAIN</td></tr>
            @forelse($incomesByCategory as $income)
                <tr><td class="indent">{{ $income->category }}</td><td class="right">{{ \App\Helpers\CurrencyHelper::format($income->total) }}</td></tr>
            @empty
                <tr><td class="indent muted">Tidak ada pemasukan lain</td><td class="right">—</td></tr>
            @endforelse
            <tr class="total"><td>Total Pemasukan Lain</td><td class="right">{{ \App\Helpers\CurrencyHelper::format($summary['incomes']) }}</td></tr>
            <tr class="net"><td>LABA BERSIH</td><td class="right">{{ \App\Helpers\CurrencyHelper::format($summary['net_profit']) }}</td></tr>
        </tbody>
    </table>

    <div class="footer">Kafeinarts POS · Laporan digenerate otomatis oleh sistem</div>
</body>
</html>
