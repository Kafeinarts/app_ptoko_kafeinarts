@extends('layouts.app')

@section('title', 'Opname Baru')
@section('page-title', 'Stock Opname Baru')
@section('page-subtitle', 'Masukkan jumlah fisik hasil hitung di gudang')
@section('page-key', 'opname-form')

@section('page-actions')
    <a href="{{ route('inventory.opnames.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
@endsection

@section('content')
<form method="POST" action="{{ route('inventory.opnames.store') }}" data-confirm="Simpan stock opname?" data-confirm-text="Stok sistem akan langsung disesuaikan dengan jumlah fisik yang Anda masukkan." data-confirm-button="Ya, sesuaikan stok">
    @csrf
    <div class="card">
        <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div>
                <p class="section-title mb-0">Form Penghitungan Fisik</p>
                <p class="section-subtitle">Outlet {{ $currentOutlet?->name }} · {{ $stocks->count() }} varian</p>
            </div>
            <div class="d-flex gap-2">
                <input type="search" id="opname-search" class="form-control form-control-sm" placeholder="Cari produk..." style="min-width:200px" oninput="const t=this.value.toLowerCase();document.querySelectorAll('[data-opname-row]').forEach(r=>r.classList.toggle('d-none',!r.dataset.name.includes(t)))">
                <a href="{{ route('inventory.opnames.index') }}" class="btn btn-sm btn-light">Batal</a>
                <button class="btn btn-sm btn-success"><i class="bi bi-check2-circle me-1"></i>Simpan Opname</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr><th>Produk</th><th>Kategori</th><th class="text-center">Stok Sistem</th><th style="width:160px">Stok Fisik</th><th class="text-center">Selisih</th></tr>
                </thead>
                <tbody>
                @forelse($stocks as $index => $stock)
                    <tr data-opname-row data-name="{{ strtolower(($stock->variant?->display_name ?? '').' '.($stock->variant?->sku_variant ?? '')) }}">
                        <td>
                            <div class="small fw-semibold">{{ $stock->variant?->display_name ?? '-' }}</div>
                            <div class="text-secondary" style="font-size:.72rem">{{ $stock->variant?->sku_variant }}</div>
                        </td>
                        <td class="small">{{ $stock->variant?->product?->category?->name ?? '-' }}</td>
                        <td class="text-center fw-semibold">{{ $stock->quantity }}</td>
                        <td>
                            <input type="hidden" name="items[{{ $index }}][variant_id]" value="{{ $stock->product_variant_id }}">
                            <input type="number" name="items[{{ $index }}][physical_qty]" class="form-control form-control-sm text-center" min="0" value="{{ $stock->quantity }}" required data-system="{{ $stock->quantity }}" oninput="const d=Number(this.value||0)-Number(this.dataset.system);const c=this.closest('tr').querySelector('[data-diff]');c.textContent=(d>0?'+':'')+d;c.className='badge badge-soft-'+(d===0?'secondary':(d>0?'success':'danger'));">
                        </td>
                        <td class="text-center"><span class="badge badge-soft-secondary" data-diff>0</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state icon="bi bi-box-seam" message="Belum ada stok untuk diopname." /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body border-top">
            <label class="form-label">Catatan Opname</label>
            <textarea name="notes" class="form-control" rows="2" maxlength="500" placeholder="Contoh: opname akhir bulan">{{ old('notes') }}</textarea>
        </div>
    </div>
</form>
@endsection
