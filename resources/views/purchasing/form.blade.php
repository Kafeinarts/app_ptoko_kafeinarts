@extends('layouts.app')

@section('title', 'PO Baru')
@section('page-title', 'Purchase Order Baru')
@section('page-subtitle', 'Buat pesanan pembelian ke supplier')
@section('page-key', 'purchase-form')

@section('page-actions')
    <a href="{{ route('purchasing.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
@endsection

@section('content')
<form method="POST" action="{{ route('purchasing.store') }}" data-confirm="Simpan purchase order?" data-confirm-text="PO akan dibuat dengan status PENDING dan dapat diterima saat barang datang." data-confirm-button="Ya, simpan PO">
    @csrf
    <div class="card">
        <div class="card-header">
            <p class="section-title mb-0">Informasi Pembelian</p>
            <p class="section-subtitle">Pilih supplier dan item yang dipesan</p>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Supplier <span class="text-danger">*</span></label>
                    <select name="supplier_id" class="form-select" required>
                        <option value="">Pilih supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}" @selected((string) old('supplier_id') === (string) $supplier->id)>{{ $supplier->name }} · {{ $supplier->phone }}</option>
                        @endforeach
                    </select>
                    @if($suppliers->isEmpty())
                        <div class="form-text text-danger">Belum ada supplier. Tambahkan supplier terlebih dahulu di menu Master Supplier.</div>
                    @endif
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tanggal</label>
                    <input type="text" class="form-control" value="{{ now()->format('d/m/Y') }}" disabled>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-success w-100" data-add-row="#po-rows" data-template="#po-row-template"><i class="bi bi-plus-lg me-1"></i>Item</button>
                </div>
                <div class="col-12">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control" rows="2" maxlength="500" placeholder="Contoh: pengiriman setiap Senin">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><p class="section-title mb-0">Item Pembelian</p></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Produk / Varian</th><th style="width:160px">Harga Satuan</th><th style="width:120px">Qty</th><th class="text-end" style="width:160px">Subtotal</th><th style="width:60px"></th></tr></thead>
                <tbody id="po-rows">
                    <tr>
                        <td>
                            <select name="items[0][variant_id]" class="form-select" required>
                                <option value="">Pilih produk</option>
                                @foreach($variants as $variant)
                                    <option value="{{ $variant->id }}" data-cost="{{ (float) ($variant->prices->first()?->cost_price ?? 0) }}">{{ $variant->display_name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" name="items[0][unit_cost]" class="form-control" min="0" step="any" value="0" required></td>
                        <td><input type="number" name="items[0][qty]" class="form-control" min="1" value="1" required></td>
                        <td class="text-end"><input type="text" class="form-control text-end" value="0" disabled></td>
                        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-remove-row><i class="bi bi-trash"></i></button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <a href="{{ route('purchasing.index') }}" class="btn btn-light">Batal</a>
            <button class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Simpan PO</button>
        </div>
    </div>
</form>

<template id="po-row-template">
    <tr>
        <td>
            <select name="items[__INDEX__][variant_id]" class="form-select" required>
                <option value="">Pilih produk</option>
                @foreach($variants as $variant)
                    <option value="{{ $variant->id }}">{{ $variant->display_name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" name="items[__INDEX__][unit_cost]" class="form-control" min="0" step="any" value="0" required></td>
        <td><input type="number" name="items[__INDEX__][qty]" class="form-control" min="1" value="1" required></td>
        <td class="text-end"><input type="text" class="form-control text-end" value="0" disabled></td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-remove-row><i class="bi bi-trash"></i></button></td>
    </tr>
</template>
@endsection
