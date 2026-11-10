<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Ringkasan Shift #{{ $shift->id }}</title>
    <style>
        @page { margin: 26px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .muted { color: #666; }
        .right { text-align: right; }
        .header { border-bottom: 2px solid #2d6a4f; padding-bottom: 8px; margin-bottom: 14px; }
        .header table { width: 100%; }
        .header td { vertical-align: top; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.data th { background: #1b4332; color: #fff; padding: 6px; text-align: left; font-size: 9px; }
        table.data td { padding: 6px; border-bottom: 1px solid #e5e5e5; }
        .summary { width: 100%; border-collapse: collapse; }
        .summary td { padding: 7px 8px; border: 1px solid #ddd; }
        .summary .label { background: #f5f7f6; color: #555; width: 40%; }
        .summary .value { font-weight: bold; text-align: right; }
        .diff { background: #1b4332; color: #fff; }
        .footer { margin-top: 16px; font-size: 8px; color: #777; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td>
                    <h1>{{ $shift->outlet?->name ?? 'Kafeinarts' }}</h1>
                    <div class="muted">{{ $shift->outlet?->address }}</div>
                    <div class="muted">{{ $shift->outlet?->phone }}</div>
                </td>
                <td class="right">
                    <div style="font-size:13px;font-weight:bold">RINGKASAN SHIFT</div>
                    <div class="muted">Shift #{{ $shift->id }} · {{ $shift->user?->name }}</div>
                    <div class="muted">{{ $shift->opened_at?->format('d/m/Y H:i') }} — {{ $shift->closed_at?->format('d/m/Y H:i') ?? 'Aktif' }}</div>
                </td>
            </tr>
        </table>
    </div>

    @php
        $cashSales = $shift->transactions->whereIn('status', ['PAID', 'PARTIAL_REFUND'])->filter(fn ($trx) => $trx->payments->contains('payment_method', 'CASH'))->sum('grand_total');
        $cashIn = $shift->cashLogs->where('type', 'CASH_IN')->sum('amount');
        $cashOut = $shift->cashLogs->where('type', 'CASH_OUT')->sum('amount');
        $expected = (float) $shift->starting_cash + (float) $cashSales + (float) $cashIn - (float) $cashOut;
        $difference = (float) ($shift->actual_cash ?? 0) - $expected;
    @endphp

    <table class="summary">
        <tr><td class="label">Modal Awal</td><td class="value">{{ \App\Helpers\CurrencyHelper::format($shift->starting_cash) }}</td></tr>
        <tr><td class="label">Penjualan Tunai</td><td class="value">{{ \App\Helpers\CurrencyHelper::format($cashSales) }}</td></tr>
        <tr><td class="label">Kas Masuk</td><td class="value">{{ \App\Helpers\CurrencyHelper::format($cashIn) }}</td></tr>
        <tr><td class="label">Kas Keluar</td><td class="value">({{ \App\Helpers\CurrencyHelper::format($cashOut) }})</td></tr>
        <tr><td class="label">Kas Seharusnya</td><td class="value">{{ \App\Helpers\CurrencyHelper::format($expected) }}</td></tr>
        <tr><td class="label">Kas Fisik Aktual</td><td class="value">{{ \App\Helpers\CurrencyHelper::format($shift->actual_cash) }}</td></tr>
        <tr class="diff"><td class="label" style="color:#fff">Selisih</td><td class="value">{{ \App\Helpers\CurrencyHelper::format($difference) }}</td></tr>
    </table>

    <h3 style="font-size:11px;margin:14px 0 6px">Transaksi Shift</h3>
    <table class="data">
        <thead><tr><th>Invoice</th><th>Waktu</th><th>Metode</th><th class="right">Total</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($shift->transactions as $trx)
            <tr>
                <td>{{ $trx->invoice_number }}</td>
                <td>{{ $trx->transaction_date->format('H:i') }}</td>
                <td>{{ $trx->payments->first()?->payment_method ?? '-' }}</td>
                <td class="right">{{ \App\Helpers\CurrencyHelper::format($trx->grand_total) }}</td>
                <td>{{ $trx->status }}</td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center;color:#777;padding:16px">Tidak ada transaksi pada shift ini.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h3 style="font-size:11px;margin:14px 0 6px">Arus Kas</h3>
    <table class="data">
        <thead><tr><th>Waktu</th><th>Jenis</th><th>Keterangan</th><th class="right">Nominal</th></tr></thead>
        <tbody>
        @forelse($shift->cashLogs as $log)
            <tr>
                <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                <td>{{ $log->type }}</td>
                <td>{{ $log->reason }}</td>
                <td class="right">{{ \App\Helpers\CurrencyHelper::format($log->amount) }}</td>
            </tr>
        @empty
            <tr><td colspan="4" style="text-align:center;color:#777;padding:16px">Tidak ada arus kas.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="footer">Kafeinarts POS · Ringkasan shift digenerate otomatis</div>
</body>
</html>
