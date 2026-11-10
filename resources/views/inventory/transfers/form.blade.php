@extends('layouts.app')

@section('title', 'Transfer Baru')
@section('page-title', 'Transfer Stok Baru')
@section('page-subtitle', 'Buat dokumen pengiriman stok ke outlet lain')
@section('page-key', 'transfer-form')

@section('page-actions')
    <a href="{{ route('inventory.transfers.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
@endsection

@section('content')
<form method="POST" action="{{ route('inventory.transfers.store') }}" data-confirm="Buat transfer stok?" data-confirm-text="Dokumen transfer akan dibuat dan dapat diterima oleh outlet tujuan." data-confirm-button="Ya, buat transfer">
    @csrf
    <div class="card">
        <div class="card-header">
            <p class="section-title mb-0">Informasi Transfer</p>
            <p class="section-subtitle">Stok baru berpindah setelah transfer diterima</p>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Outlet Asal <span class="text-danger">*</span></label>
                    <select name="from_outlet_id" class="form-select" required>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected((string) old('from_outlet_id', $currentOutlet?->id) === (string) $outlet->id)>{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Outlet Tujuan <span class="text-danger">*</span></label>
                    <select name="to_outlet_id" class="form-select" required>
                        <option value="">Pilih outlet tujuan</option>
                        @foreach($outlets as $outlet)
                            <option value="{{ $outlet->id }}" @selected((string) old('to_outlet_id') === (string) $outlet->id)>{{ $outlet->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-success w-100" data-add-row="#transfer-rows" data-template="#transfer-row-template"><i class="bi bi-plus-lg me-1"></i>Item</button>
                </div>
                <div class="col-12">
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control" rows="2" maxlength="500" placeholder="Contoh: restock mingguan">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><p class="section-title mb-0">Item Transfer</p></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Produk / Varian</th><th style="width:160px">Jumlah</th><th style="width:60px"></th></tr></thead>
                <tbody id="transfer-rows">
                    <tr>
                        <td>
                            <select name="items[0][variant_id]" class="form-select" required>
                                <option value="">Pilih produk</option>
                                @foreach($variants as $variant)
                                    <option value="{{ $variant->id }}">{{ $variant->display_name }} · stok {{ (int) ($variant->stocks->first()?->quantity ?? 0) }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" name="items[0][qty]" class="form-control" min="1" value="1" required></td>
                        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-remove-row><i class="bi bi-trash"></i></button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end gap-2">
            <a href="{{ route('inventory.transfers.index') }}" class="btn btn-light">Batal</a>
            <button class="btn btn-success"><i class="bi bi-send me-1"></i>Buat Transfer</button>
        </div>
    </div>
</form>

<template id="transfer-row-template">
    <tr>
        <td>
            <select name="items[__INDEX__][variant_id]" class="form-select" required>
                <option value="">Pilih produk</option>
                @foreach($variants as $variant)
                    <option value="{{ $variant->id }}">{{ $variant->display_name }} · stok {{ (int) ($variant->stocks->first()?->quantity ?? 0) }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" name="items[__INDEX__][qty]" class="form-control" min="1" value="1" required></td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-remove-row><i class="bi bi-trash"></i></button></td>
    </tr>
</template>
@endsection
