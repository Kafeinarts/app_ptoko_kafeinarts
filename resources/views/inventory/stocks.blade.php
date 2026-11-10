@extends('layouts.app')

@section('title', 'Stok Barang')
@section('page-title', 'Stok Barang')
@section('page-subtitle', 'Pantau persediaan dan mutasi stok outlet '.($currentOutlet?->name ?? '-'))
@section('page-key', 'stocks')

@section('page-actions')
    @if(auth()->user()->hasPermission('inventory.adjust'))
        <a href="{{ route('inventory.stocks.create') }}" class="btn btn-sm btn-success"><i class="bi bi-arrow-left-right me-1"></i>Mutasi Stok</a>
    @endif
    @if(auth()->user()->hasPermission('inventory.opname'))
        <a href="{{ route('inventory.opnames.create') }}" class="btn btn-sm btn-outline-success"><i class="bi bi-clipboard-check me-1"></i>Stock Opname</a>
    @endif
@endsection

@section('content')
<form method="GET" class="card mb-3" data-spa-form>
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Cari Produk</label>
                <input type="search" name="q" class="form-control" value="{{ request('q') }}" placeholder="Nama produk atau SKU">
            </div>
            <div class="col-md-3">
                <label class="form-label">Tampilkan</label>
                <select name="filter" class="form-select">
                    <option value="">Semua stok</option>
                    <option value="low" @selected(request('filter') === 'low')>Stok minimum / habis</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Jenis Mutasi</label>
                <select name="type" class="form-select">
                    <option value="">Semua jenis</option>
                    @foreach(['IN' => 'Masuk', 'OUT' => 'Keluar', 'ADJUSTMENT' => 'Penyesuaian', 'TRANSFER' => 'Transfer', 'VOID' => 'Void', 'RETURN' => 'Retur'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-success flex-grow-1"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('inventory.stocks') }}" class="btn btn-light"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </div>
    </div>
</form>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <p class="section-title mb-0">Persediaan Saat Ini</p>
                    <p class="section-subtitle">{{ $stocks->total() }} varian produk</p>
                </div>
                @if(request('filter') === 'low')
                    <span class="badge badge-soft-danger"><i class="bi bi-exclamation-triangle me-1"></i>Filter stok minimum</span>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Produk</th><th>Kategori</th><th class="text-center">Stok</th><th class="text-center">Min</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($stocks as $stock)
                        <tr>
                            <td>
                                <div class="small fw-semibold">{{ $stock->variant?->display_name }}</div>
                                <div class="text-secondary" style="font-size:.72rem">{{ $stock->variant?->sku_variant }}</div>
                            </td>
                            <td class="small">{{ $stock->variant?->product?->category?->name ?? '-' }}</td>
                            <td class="text-center fw-semibold">{{ number_format($stock->quantity, 0, ',', '.') }}</td>
                            <td class="text-center small text-secondary">{{ $stock->min_stock }}</td>
                            <td>
                                @if($stock->quantity <= 0)
                                    <span class="badge badge-soft-danger">Habis</span>
                                @elseif($stock->isLow())
                                    <span class="badge badge-soft-warning">Menipis</span>
                                @else
                                    <span class="badge badge-soft-success">Aman</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state icon="bi bi-box-seam" message="Belum ada data stok." /></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white">{{ $stocks->links() }}</div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header">
                <p class="section-title mb-0">Mutasi Stok Terakhir</p>
                <p class="section-subtitle">50 pergerakan terbaru</p>
            </div>
            <div class="list-group list-group-flush" style="max-height:620px;overflow:auto">
                @forelse($logs as $log)
                    @php($positive = $log->qty_change >= 0)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="small fw-semibold">{{ $log->variant?->display_name ?? '-' }}</div>
                                <div class="text-secondary" style="font-size:.72rem">
                                    {{ $log->type }} · {{ $log->reference_number ?? '-' }} · {{ $log->user?->name ?? 'Sistem' }}
                                </div>
                                @if($log->notes)<div class="small text-secondary fst-italic">{{ $log->notes }}</div>@endif
                            </div>
                            <div class="text-end">
                                <span class="badge badge-soft-{{ $positive ? 'success' : 'danger' }}">{{ $positive ? '+' : '' }}{{ $log->qty_change }}</span>
                                <div class="text-secondary" style="font-size:.7rem">Sisa {{ $log->qty_balance }}</div>
                                <div class="text-secondary" style="font-size:.7rem">{{ $log->created_at?->format('d/m H:i') }}</div>
                            </div>
                        </div>
                    </div>
                @empty
                    <x-empty-state icon="bi bi-arrow-left-right" message="Belum ada mutasi stok." class="py-4" />
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
