@extends('layouts.app')

@section('title', 'Produk')
@section('page-title', 'Master Produk')
@section('page-subtitle', 'Kelola produk, varian, harga, dan stok')
@section('page-key', 'products')

@section('page-actions')
    <a href="{{ route('master.products.export') }}" class="btn btn-sm btn-outline-success" data-no-spa><i class="bi bi-file-earmark-excel me-1"></i>Export</a>
    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#import-products"><i class="bi bi-upload me-1"></i>Import</button>
    <a href="{{ route('master.products.create') }}" class="btn btn-sm btn-success"><i class="bi bi-plus-lg me-1"></i>Tambah Produk</a>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div>
            <p class="section-title mb-0">Daftar Produk</p>
            <p class="section-subtitle">{{ $products->total() }} produk · outlet {{ $currentOutlet?->name }}</p>
        </div>
        <form method="GET" class="d-flex flex-wrap gap-2" data-spa-form>
            <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Nama / SKU / barcode" style="min-width:180px">
            <select name="category" class="form-select form-select-sm" style="min-width:150px">
                <option value="">Semua kategori</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="status" class="form-select form-select-sm">
                <option value="">Semua status</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
            </select>
            <button class="btn btn-sm btn-outline-success"><i class="bi bi-search"></i></button>
            @if(request()->hasAny(['q', 'category', 'status']))
                <a href="{{ route('master.products.index') }}" class="btn btn-sm btn-light"><i class="bi bi-x-lg"></i></a>
            @endif
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Varian</th>
                    <th class="text-end">Harga Jual</th>
                    <th class="text-center">Stok</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($products as $product)
                @php
                    $price = $product->variants->first()?->prices->first();
                    $totalStock = $product->variants->sum(fn ($variant) => (int) ($variant->stocks->first()?->quantity ?? 0));
                    $minStock = (int) ($product->variants->first()?->stocks->first()?->min_stock ?? 5);
                @endphp
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="product-thumb" style="width:44px;height:44px;object-fit:cover;border-radius:10px">
                            <div>
                                <div class="fw-semibold small">{{ $product->name }}</div>
                                <div class="text-secondary" style="font-size:.72rem">{{ $product->sku }}{{ $product->barcode ? ' · '.$product->barcode : '' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="small">{{ $product->category?->name ?? '-' }}</td>
                    <td class="small">
                        <span class="badge badge-soft-secondary">{{ $product->variants->count() }} varian</span>
                        <div class="text-secondary" style="font-size:.72rem">{{ $product->variants->take(2)->map(fn ($v) => $v->variant_name)->join(', ') }}{{ $product->variants->count() > 2 ? ', ...' : '' }}</div>
                    </td>
                    <td class="text-end small fw-semibold">{{ \App\Helpers\CurrencyHelper::format($price?->selling_price ?? 0) }}</td>
                    <td class="text-center">
                        <span class="badge badge-soft-{{ $totalStock <= $minStock ? 'danger' : 'success' }}">{{ $totalStock }}</span>
                        <div class="text-secondary" style="font-size:.7rem">min {{ $minStock }}</div>
                    </td>
                    <td><span class="badge badge-soft-{{ $product->is_active ? 'success' : 'secondary' }}">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('master.products.edit', $product) }}" class="btn btn-outline-secondary" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="{{ route('master.products.destroy', $product) }}" data-confirm="Hapus produk {{ $product->name }}?" data-confirm-text="Produk dan seluruh variannya akan dihapus permanen." data-confirm-button="Ya, hapus">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state"><i class="bi bi-cup-hot"></i>Belum ada produk. Tambahkan produk pertama Anda.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $products->links() }}</div>
</div>

<div class="modal fade" id="import-products" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('master.products.import') }}" enctype="multipart/form-data" class="modal-content border-0">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-upload me-1"></i>Import Produk</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">File Excel / CSV <span class="text-danger">*</span></label>
                    <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    <div class="form-text">Gunakan template agar kolom sesuai. Maksimal 5 MB.</div>
                </div>
                <a href="{{ route('master.products.template') }}" class="btn btn-sm btn-outline-success" data-no-spa><i class="bi bi-download me-1"></i>Unduh Template</a>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-success"><i class="bi bi-upload me-1"></i>Import Sekarang</button>
            </div>
        </form>
    </div>
</div>
@endsection
