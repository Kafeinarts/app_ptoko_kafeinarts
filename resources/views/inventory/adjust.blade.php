@extends('layouts.app')

@section('title', 'Mutasi Stok')
@section('page-title', 'Mutasi Stok')
@section('page-subtitle', 'Catat barang masuk, keluar, penyesuaian, atau retur')
@section('page-key', 'stock-adjust')

@section('page-actions')
    <a href="{{ route('inventory.stocks') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-7">
        <form method="POST" action="{{ route('inventory.stocks.store') }}" class="card">
            @csrf
            <div class="card-header">
                <p class="section-title mb-0">Form Mutasi Stok</p>
                <p class="section-subtitle">Semua mutasi tercatat pada kartu stok dan audit log</p>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Produk / Varian <span class="text-danger">*</span></label>
                        <select name="variant_id" id="variant_id" class="form-select" required onchange="const o=this.selectedOptions[0];document.getElementById('current-stock').textContent=(o && o.dataset.stock!==undefined && this.value)?o.dataset.stock+' unit':'—'">
                            <option value="">Pilih produk</option>
                            @foreach($variants as $variant)
                                <option value="{{ $variant->id }}" data-stock="{{ (int) ($variant->stocks->first()?->quantity ?? 0) }}" @selected((string) old('variant_id') === (string) $variant->id)>
                                    {{ $variant->display_name }} · stok {{ (int) ($variant->stocks->first()?->quantity ?? 0) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Jenis Mutasi <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="IN" @selected(old('type') === 'IN')>Barang Masuk (IN)</option>
                            <option value="OUT" @selected(old('type') === 'OUT')>Barang Keluar (OUT)</option>
                            <option value="ADJUSTMENT" @selected(old('type') === 'ADJUSTMENT')>Penyesuaian (ADJUSTMENT)</option>
                            <option value="RETURN" @selected(old('type') === 'RETURN')>Retur (RETURN)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                        <input type="number" name="qty" class="form-control" min="1" value="{{ old('qty', 1) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Stok Minimum</label>
                        <input type="number" name="min_stock" class="form-control" min="0" value="{{ old('min_stock') }}" placeholder="Biarkan tetap">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Catatan / Alasan <span class="text-danger">*</span></label>
                        <textarea name="notes" class="form-control" rows="3" maxlength="500" required placeholder="Contoh: penerimaan susu dari supplier">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white d-flex justify-content-end gap-2">
                <a href="{{ route('inventory.stocks') }}" class="btn btn-light">Batal</a>
                <button class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Simpan Mutasi</button>
            </div>
        </form>
    </div>
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><p class="section-title mb-0">Panduan Jenis Mutasi</p></div>
            <div class="card-body">
                <div class="d-flex gap-2 mb-3">
                    <span class="badge badge-soft-success">IN</span>
                    <div class="small text-secondary">Menambah stok, misal penerimaan barang dari supplier atau produksi.</div>
                </div>
                <div class="d-flex gap-2 mb-3">
                    <span class="badge badge-soft-danger">OUT</span>
                    <div class="small text-secondary">Mengurangi stok, misal barang rusak, tumpah, atau pemakaian internal.</div>
                </div>
                <div class="d-flex gap-2 mb-3">
                    <span class="badge badge-soft-warning">ADJUSTMENT</span>
                    <div class="small text-secondary">Menambah stok hasil koreksi. Gunakan stock opname untuk penyesuaian menyeluruh.</div>
                </div>
                <div class="d-flex gap-2">
                    <span class="badge badge-soft-secondary">RETURN</span>
                    <div class="small text-secondary">Menambah stok dari barang retur pelanggan yang layak dijual kembali.</div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="stat-icon bg-soft-blue"><i class="bi bi-info-circle"></i></span>
                    <div class="fw-semibold">Stok terpilih</div>
                </div>
                <div class="d-flex justify-content-between"><span class="text-secondary small">Stok saat ini</span><span class="fw-bold" id="current-stock">—</span></div>
                <div class="d-flex justify-content-between"><span class="text-secondary small">Outlet</span><span class="small">{{ $currentOutlet?->name }}</span></div>
                <hr>
                <div class="small text-secondary">Mutasi tidak dapat dihapus. Pastikan jumlah dan catatan sudah benar sebelum menyimpan.</div>
            </div>
        </div>
    </div>
</div>
@endsection
