@extends('layouts.app')

@section('title', 'Purchase Order')
@section('page-title', 'Purchase Order')
@section('page-subtitle', 'Kelola pembelian barang ke supplier')
@section('page-key', 'purchasing')

@section('page-actions')
    @if(auth()->user()->hasPermission('purchasing.manage'))
        <a href="{{ route('purchasing.create') }}" class="btn btn-sm btn-success"><i class="bi bi-plus-lg me-1"></i>PO Baru</a>
    @endif
@endsection

@section('content')
<form method="GET" class="card mb-3" data-spa-form>
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Cari No. PO</label>
                <input type="search" name="q" class="form-control" value="{{ request('q') }}" placeholder="PO-...">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua status</option>
                    @foreach(['PENDING' => 'Pending', 'RECEIVED' => 'Diterima', 'CANCELLED' => 'Dibatalkan'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-success flex-grow-1"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('purchasing.index') }}" class="btn btn-light"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <p class="section-title mb-0">Daftar Purchase Order</p>
            <p class="section-subtitle">{{ $orders->total() }} dokumen pembelian</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>No. PO</th><th>Tanggal</th><th>Supplier</th><th>Dibuat Oleh</th><th class="text-center">Item</th><th class="text-end">Total</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            @forelse($orders as $order)
                @php($statusColor = ['PENDING' => 'warning', 'RECEIVED' => 'success', 'CANCELLED' => 'danger'][$order->status] ?? 'secondary')
                <tr>
                    <td class="small fw-semibold">{{ $order->po_number }}</td>
                    <td class="small">{{ $order->po_date?->format('d/m/Y') }}</td>
                    <td class="small">{{ $order->supplier?->name ?? '-' }}</td>
                    <td class="small">{{ $order->user?->name ?? '-' }}</td>
                    <td class="text-center"><span class="badge badge-soft-secondary">{{ $order->items_count }}</span></td>
                    <td class="text-end small fw-semibold">{{ \App\Helpers\CurrencyHelper::format($order->total_amount) }}</td>
                    <td><span class="badge badge-soft-{{ $statusColor }}">{{ $order->status }}</span></td>
                    <td class="text-end"><a href="{{ route('purchasing.show', $order) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-eye me-1"></i>Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="8"><x-empty-state icon="bi bi-cart-plus" message="Belum ada purchase order." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $orders->links() }}</div>
</div>
@endsection
