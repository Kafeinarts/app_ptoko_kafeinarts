@extends('layouts.app')

@section('title', 'Detail Purchase Order')
@section('page-title', 'Detail Purchase Order')
@section('page-subtitle', $order->po_number.' · '.$order->po_date?->format('d/m/Y'))
@section('page-key', 'purchase-show')

@section('page-actions')
    @if($order->status === 'PENDING' && auth()->user()->hasPermission('purchasing.manage'))
        <form method="POST" action="{{ route('purchasing.receive', $order) }}" data-confirm="Terima seluruh barang PO ini?" data-confirm-text="Stok inventaris akan ditambahkan otomatis sesuai qty pesanan." data-confirm-button="Ya, terima barang">
            @csrf
            <button class="btn btn-sm btn-success"><i class="bi bi-box-arrow-in-down me-1"></i>Terima Barang</button>
        </form>
        <form method="POST" action="{{ route('purchasing.cancel', $order) }}" data-confirm="Batalkan purchase order?" data-confirm-text="PO akan ditandai CANCELLED dan tidak dapat diterima." data-confirm-button="Ya, batalkan">
            @csrf
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-octagon me-1"></i>Batalkan</button>
        </form>
    @endif
    <a href="{{ route('purchasing.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
@endsection

@section('content')
@php
    $statusColor = ['PENDING' => 'warning', 'RECEIVED' => 'success', 'CANCELLED' => 'danger'][$order->status] ?? 'secondary';
@endphp
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="text-secondary small">No. Purchase Order</div>
                        <div class="fs-5 fw-bold">{{ $order->po_number }}</div>
                    </div>
                    <span class="badge badge-soft-{{ $statusColor }} px-3 py-2">{{ $order->status }}</span>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-secondary small">Supplier</div>
                        <div class="fw-semibold">{{ $order->supplier?->name ?? '-' }}</div>
                        <div class="small text-secondary">{{ $order->supplier?->phone }} · {{ $order->supplier?->email }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-secondary small">Dibuat Oleh</div>
                        <div class="fw-semibold">{{ $order->user?->name ?? '-' }}</div>
                        <div class="small text-secondary">{{ $order->po_date?->format('d/m/Y') }}</div>
                    </div>
                    <div class="col-12">
                        <div class="text-secondary small">Catatan</div>
                        <div class="small">{{ $order->notes ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <x-stat-card label="Total Pembelian" :value="\App\Helpers\CurrencyHelper::format($order->total_amount)" :hint="$order->items->count().' item · '.$order->items->sum('qty_ordered').' unit'" />
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><p class="section-title mb-0">Item Purchase Order</p></div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Produk</th><th class="text-end">Harga Satuan</th><th class="text-center">Qty Pesan</th><th class="text-center">Qty Diterima</th><th class="text-end">Subtotal</th></tr></thead>
            <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td class="small fw-semibold">{{ $item->variant?->display_name ?? '-' }}</td>
                    <td class="text-end small">{{ \App\Helpers\CurrencyHelper::format($item->unit_cost) }}</td>
                    <td class="text-center">{{ $item->qty_ordered }}</td>
                    <td class="text-center"><span class="badge badge-soft-{{ $item->qty_received >= $item->qty_ordered ? 'success' : 'secondary' }}">{{ $item->qty_received }}</span></td>
                    <td class="text-end small fw-semibold">{{ \App\Helpers\CurrencyHelper::format($item->subtotal) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

@if($order->returns->isNotEmpty())
    <div class="card">
        <div class="card-header"><p class="section-title mb-0">Retur Pembelian</p></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>No. Retur</th><th>Tanggal</th><th>Alasan</th><th class="text-center">Item</th><th class="text-end">Total</th></tr></thead>
                <tbody>
                @foreach($order->returns as $return)
                    <tr>
                        <td class="small fw-semibold">{{ $return->return_number }}</td>
                        <td class="small">{{ $return->return_date?->format('d/m/Y') }}</td>
                        <td class="small">{{ $return->reason ?: '—' }}</td>
                        <td class="text-center">{{ $return->items->sum('qty') }}</td>
                        <td class="text-end small">{{ \App\Helpers\CurrencyHelper::format($return->total_amount) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@if($order->status === 'RECEIVED' && auth()->user()->hasPermission('purchasing.manage'))
    <div class="text-end mt-3">
        <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#return-modal"><i class="bi bi-arrow-return-left me-1"></i>Retur Pembelian</button>
    </div>

    @php
        $returnedByItem = $order->returns->flatMap(fn ($return) => $return->items)->groupBy('purchase_order_item_id')->map(fn ($rows) => $rows->sum('qty'));
    @endphp
    <div class="modal fade" id="return-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form method="POST" action="{{ route('purchasing.return', $order) }}" class="modal-content border-0" data-confirm="Proses retur pembelian?" data-confirm-text="Stok akan dikurangi sesuai qty retur." data-confirm-button="Ya, retur">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-arrow-return-left me-1"></i>Retur Pembelian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead><tr><th>Produk</th><th class="text-center">Diterima</th><th class="text-center">Sudah Retur</th><th style="width:120px">Qty Retur</th></tr></thead>
                            <tbody>
                            @foreach($order->items as $index => $item)
                                @php
                                $already = (int) ($returnedByItem[$item->id] ?? 0);
                                $remaining = max(0, (int) $item->qty_received - $already);
                            @endphp
                                <tr>
                                    <td class="small">{{ $item->variant?->display_name }}</td>
                                    <td class="text-center small">{{ $item->qty_received }}</td>
                                    <td class="text-center small">{{ $already }}</td>
                                    <td>
                                        <input type="hidden" name="items[{{ $index }}][item_id]" value="{{ $item->id }}">
                                        <input type="number" name="items[{{ $index }}][qty]" class="form-control form-control-sm" min="0" max="{{ $remaining }}" value="0" @disabled($remaining === 0)>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <label class="form-label">Alasan Retur</label>
                    <textarea name="reason" class="form-control" rows="2" maxlength="500" placeholder="Contoh: barang rusak / tidak sesuai"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-warning"><i class="bi bi-check2 me-1"></i>Proses Retur</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
