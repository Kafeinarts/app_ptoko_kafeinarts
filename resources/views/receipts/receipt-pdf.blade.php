<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Struk {{ $transaction->invoice_number }}</title>
    <style>
        @page { margin: 4px 5px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: {{ $paper === '80' ? '9px' : '7.6px' }}; color: #111; margin: 0; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .muted { color: #555; }
        .store { font-size: {{ $paper === '80' ? '12px' : '10px' }}; font-weight: bold; letter-spacing: .5px; }
        hr { border: 0; border-top: 1px dashed #666; margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 1px 0; }
        .items td { padding: 2px 0; }
        .item-name { font-weight: bold; }
        .totals td { padding: 1px 0; }
        .grand { font-size: {{ $paper === '80' ? '11px' : '9.5px' }}; font-weight: bold; }
        .footer { margin-top: 6px; font-size: {{ $paper === '80' ? '8px' : '6.6px' }}; }
    </style>
</head>
<body>
    <div class="center">
        <div class="store">{{ $transaction->outlet?->name ?? \App\Models\SystemSetting::get('store_name', 'Kafeinarts') }}</div>
        @if($transaction->outlet?->address)<div class="muted">{{ $transaction->outlet->address }}</div>@endif
        @if($transaction->outlet?->phone)<div class="muted">Telp. {{ $transaction->outlet->phone }}</div>@endif
    </div>
    <hr>
    <table>
        <tr><td>No. Invoice</td><td class="right">{{ $transaction->invoice_number }}</td></tr>
        <tr><td>Tanggal</td><td class="right">{{ $transaction->transaction_date->format('d/m/Y H:i') }}</td></tr>
        <tr><td>Kasir</td><td class="right">{{ $transaction->user?->name ?? '-' }}</td></tr>
        <tr><td>Pelanggan</td><td class="right">{{ $transaction->customer?->name ?? 'Umum' }}</td></tr>
    </table>
    <hr>
    <table class="items">
        @foreach($transaction->items as $item)
            <tr>
                <td colspan="2" class="item-name">{{ $item->variant?->display_name ?? $item->variant?->product?->name ?? 'Produk' }}</td>
            </tr>
            <tr>
                <td>{{ $item->qty }} x {{ \App\Helpers\CurrencyHelper::format($item->selling_price) }}</td>
                <td class="right">{{ \App\Helpers\CurrencyHelper::format($item->total) }}</td>
            </tr>
            @if($item->modifiers->isNotEmpty())
                <tr><td colspan="2" class="muted">+ {{ $item->modifiers->map(fn ($m) => $m->modifier?->name)->filter()->join(', ') }}</td></tr>
            @endif
            @if($item->notes)
                <tr><td colspan="2" class="muted">Catatan: {{ $item->notes }}</td></tr>
            @endif
        @endforeach
    </table>
    <hr>
    <table class="totals">
        <tr><td>Subtotal</td><td class="right">{{ \App\Helpers\CurrencyHelper::format($transaction->subtotal) }}</td></tr>
        @if((float) $transaction->discount_amount > 0)
            <tr><td>Diskon</td><td class="right">-{{ \App\Helpers\CurrencyHelper::format($transaction->discount_amount) }}</td></tr>
        @endif
        @if((float) $transaction->tax_amount > 0)
            <tr><td>Pajak</td><td class="right">{{ \App\Helpers\CurrencyHelper::format($transaction->tax_amount) }}</td></tr>
        @endif
        <tr class="grand"><td>Grand Total</td><td class="right">{{ \App\Helpers\CurrencyHelper::format($transaction->grand_total) }}</td></tr>
        @php($payment = $transaction->payments->first())
        @if($payment)
            <tr><td>{{ $payment->payment_method }}</td><td class="right">{{ \App\Helpers\CurrencyHelper::format($payment->amount_paid) }}</td></tr>
            <tr><td>Kembalian</td><td class="right">{{ \App\Helpers\CurrencyHelper::format($payment->change_returned) }}</td></tr>
        @endif
    </table>
    <hr>
    <div class="center footer">
        <div class="bold">Terima kasih atas kunjungan Anda</div>
        <div>{{ $transaction->outlet?->receipt_footer ?? 'Selamat menikmati!' }}</div>
        <div class="muted">{{ \App\Models\SystemSetting::get('store_name', 'Kafeinarts') }} · Powered by Kafeinarts POS</div>
    </div>
</body>
</html>
