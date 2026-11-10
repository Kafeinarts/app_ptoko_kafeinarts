@extends('layouts.app')

@section('title', $product->exists ? 'Edit Produk' : 'Tambah Produk')
@section('page-title', $product->exists ? 'Edit Produk' : 'Tambah Produk')
@section('page-subtitle', $product->exists ? 'Perbarui data '.$product->name : 'Lengkapi data produk baru')
@section('page-key', 'product-form')

@section('page-actions')
    <a href="{{ route('master.products.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
@endsection

@section('content')
@php
    $variant = $product->exists ? $product->variants->first() : null;
    $price = $variant?->prices?->first();
    $stock = $variant?->stocks?->first();
@endphp
<form method="POST" action="{{ $product->exists ? route('master.products.update', $product) : route('master.products.store') }}" enctype="multipart/form-data">
    @csrf
    @if($product->exists)
        @method('PUT')
    @endif
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">
                    <p class="section-title mb-0">Informasi Produk</p>
                    <p class="section-subtitle">Data dasar yang tampil di katalog kasir</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nama Produk <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $product->name) }}" required maxlength="150" placeholder="Contoh: Es Kopi Susu">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch mt-2">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="is_active" @checked(old('is_active', $product->exists ? $product->is_active : true))>
                                <label class="form-check-label" for="is_active">Produk aktif dijual</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Pilih kategori</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected((string) old('category_id', $product->category_id) === (string) $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Brand</label>
                            <select name="brand_id" class="form-select">
                                <option value="">Tanpa brand</option>
                                @foreach($brands as $brand)
                                    <option value="{{ $brand->id }}" @selected((string) old('brand_id', $product->brand_id) === (string) $brand->id)>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Satuan <span class="text-danger">*</span></label>
                            <select name="unit_id" class="form-select" required>
                                <option value="">Pilih satuan</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" @selected((string) old('unit_id', $product->unit_id) === (string) $unit->id)>{{ $unit->name }} ({{ $unit->short_name }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SKU</label>
                            <input type="text" name="sku" class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku', $product->sku) }}" maxlength="50" placeholder="Otomatis bila kosong">
                            @error('sku')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Barcode</label>
                            <input type="text" name="barcode" class="form-control @error('barcode') is-invalid @enderror" value="{{ old('barcode', $product->barcode) }}" maxlength="100" placeholder="Opsional">
                            @error('barcode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control" rows="2" maxlength="1000" placeholder="Deskripsi singkat produk">{{ old('description', $product->description) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <p class="section-title mb-0">Harga & Stok</p>
                    <p class="section-subtitle">{{ $product->exists ? 'Harga dan stok untuk varian utama di outlet ini' : 'Harga dasar produk baru' }}</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Harga Modal <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="cost_price" class="form-control" min="0" step="any" value="{{ old('cost_price', $price?->cost_price ?? 0) }}" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Harga Jual <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" name="selling_price" class="form-control" min="0" step="any" value="{{ old('selling_price', $price?->selling_price ?? 0) }}" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Stok</label>
                            <input type="number" name="stock" class="form-control" min="0" value="{{ old('stock', $stock?->quantity ?? 0) }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Stok Min.</label>
                            <input type="number" name="min_stock" class="form-control" min="0" value="{{ old('min_stock', $stock?->min_stock ?? 5) }}">
                        </div>
                    </div>
                </div>
            </div>

            @unless($product->exists)
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <p class="section-title mb-0">Varian Produk</p>
                            <p class="section-subtitle">Opsional. Biarkan kosong untuk varian Default.</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-success" data-add-row="#variant-rows" data-template="#variant-row-template"><i class="bi bi-plus-lg me-1"></i>Tambah Varian</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Nama Varian</th><th>Harga Modal</th><th>Harga Jual</th><th></th></tr></thead>
                            <tbody id="variant-rows"></tbody>
                        </table>
                    </div>
                </div>
                <template id="variant-row-template">
                    <tr>
                        <td><input type="text" name="variants[__INDEX__][name]" class="form-control form-control-sm" placeholder="Contoh: Large"></td>
                        <td><input type="number" name="variants[__INDEX__][cost_price]" class="form-control form-control-sm" min="0" step="any" placeholder="0"></td>
                        <td><input type="number" name="variants[__INDEX__][selling_price]" class="form-control form-control-sm" min="0" step="any" placeholder="0"></td>
                        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-remove-row><i class="bi bi-trash"></i></button></td>
                    </tr>
                </template>
            @endunless
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header"><p class="section-title mb-0">Foto Produk</p></div>
                <div class="card-body text-center">
                    <img src="{{ $product->image_url }}" alt="Preview" id="image-preview" class="mb-3" style="width:100%;max-width:220px;height:180px;object-fit:cover;border-radius:14px">
                    <input type="file" name="image" class="form-control" accept="image/*" onchange="if(this.files[0]){document.getElementById('image-preview').src=URL.createObjectURL(this.files[0])}">
                    <div class="form-text">JPG/PNG maksimal 2 MB. Rasio 1:1 disarankan.</div>
                </div>
            </div>

            @if($product->exists && $product->variants->count() > 1)
                <div class="card mb-3">
                    <div class="card-header"><p class="section-title mb-0">Varian Terdaftar</p></div>
                    <div class="list-group list-group-flush">
                        @foreach($product->variants as $item)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="small fw-semibold">{{ $item->variant_name }}</div>
                                    <div class="text-secondary" style="font-size:.72rem">{{ $item->sku_variant }}</div>
                                </div>
                                <span class="badge badge-soft-secondary">{{ \App\Helpers\CurrencyHelper::format($item->prices->first()?->selling_price ?? 0) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <button class="btn btn-success w-100 py-2"><i class="bi bi-check2-circle me-1"></i>{{ $product->exists ? 'Simpan Perubahan' : 'Simpan Produk' }}</button>
                    <a href="{{ route('master.products.index') }}" class="btn btn-light w-100 mt-2">Batal</a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
