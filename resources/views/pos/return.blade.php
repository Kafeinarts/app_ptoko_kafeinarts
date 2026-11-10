@extends('layouts.app')

@section('title', 'Retur Penjualan')
@section('page-title', 'Retur Penjualan')
@section('page-subtitle', 'Proses pengembalian item dari invoice '.$transaction->invoice_number)
@section('page-key', 'return')

@section('page-actions')
    <a href="{{ route('pos.history') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-8">
        <form method="POST" action="{{ route('pos.history.return.store', $transaction) }}" class="card" data-confirm="Proses retur penjualan?" data-confirm-text="Stok item yang diretur akan dikembalikan dan status transaksi diperbarui." data-confirm-button="Ya, proses retur">
            @csrf
            <div class="card-header">
                <p class="section-title mb-0">Item Transaksi</p>
                <p class="section-subtitle">Isi jumlah yang akan diretur. Kosongkan atau isi 0 bila tidak diretur.</p>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th class="text-center">Terjual</th>
                            <th class="text-center">Sudah Retur</th>
                            <th class="text-center">Sisa</th>
                            <th class="text-end">Harga</th>
                            <th style="width:120px">Qty Retur</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($transaction->items as $index => $item)
                        @php($remaining = max(0, $item->qty - (int) $item->returned_qty))
                        <tr>
                            <td>
                                <div class="fw-semibold small">{{ $item->variant?->display_name ?? '-' }}</div>
                                @if($item->modifiers->isNotEmpty())
                                    <div class="text-secondary" style="font-size:.72rem">{{ $item->modifiers->map(fn ($m) => $m->modifier?->name)->filter()->join(', ') }}</div>
                                @endif
                            </td>
                            <td class="text-center">{{ $item->qty }}</td>
                            <td class="text-center">{{ (int) $item->returned_qty }}</td>
                            <td class="text-center"><span class="badge badge-soft-secondary">{{ $remaining }}</span></td>
                            <td class="text-end small">{{ \App\Helpers\CurrencyHelper::format($item->selling_price) }}</td>
                            <td>
                                <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $item->id }}">
                                <input type="number" name="items[{{ $index }}][qty]" class="form-control form-control-sm text-center" min="0" max="{{ $remaining }}" value="0" @disabled($remaining === 0)>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-body border-top">
                <label class="form-label">Alasan Retur</label>
                <textarea name="reason" class="form-control" rows="2" maxlength="500" placeholder="Contoh: produk tidak sesuai pesanan"></textarea>
            </div>
            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('pos.history') }}" class="btn btn-light">Batal</a>
                <button class="btn btn-warning"><i class="bi bi-arrow-return-left me-1"></i>Proses Retur</button>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header"><p class="section-title mb-0">Ringkasan Invoice</p></div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-1"><span class="text-secondary">Invoice</span><span class="fw-semibold small">{{ $transaction->invoice_number }}</span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-secondary">Tanggal</span><span class="small">{{ $transaction->transaction_date->format('d/m/Y H:i') }}</span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-secondary">Kasir</span><span class="small">{{ $transaction->user?->name }}</span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-secondary">Pelanggan</span><span class="small">{{ $transaction->customer?->name ?? 'Umum' }}</span></div>
                <hr>
                <div class="d-flex justify-content-between py-1"><span class="text-secondary">Subtotal</span><span class="small">{{ \App\Helpers\CurrencyHelper::format($transaction->subtotal) }}</span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-secondary">Diskon</span><span class="small text-danger">{{ \App\Helpers\CurrencyHelper::format($transaction->discount_amount) }}</span></div>
                <div class="d-flex justify-content-between py-1"><span class="text-secondary">Pajak</span><span class="small">{{ \App\Helpers\CurrencyHelper::format($transaction->tax_amount) }}</span></div>
                <div class="d-flex justify-content-between py-1 fw-bold"><span>Grand Total</span><span>{{ \App\Helpers\CurrencyHelper::format($transaction->grand_total) }}</span></div>
            </div>
        </div>

        @if($transaction->returns->isNotEmpty())
            <div class="card">
                <div class="card-header"><p class="section-title mb-0">Riwayat Retur</p></div>
                <div class="list-group list-group-flush">
                    @foreach($transaction->returns as $return)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold small">{{ $return->return_number }}</span>
                                <span class="small text-danger">{{ \App\Helpers\CurrencyHelper::format($return->total_amount) }}</span>
                            </div>
                            <div class="small text-secondary">{{ $return->return_date->format('d/m/Y H:i') }} · {{ $return->items->sum('qty') }} item</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
