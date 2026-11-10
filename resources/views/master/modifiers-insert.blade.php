@extends('layouts.app')

@section('title', 'Tambah Grup Modifier')
@section('page-title', 'Tambah Grup Modifier')
@section('page-subtitle', 'Buat grup modifier (mis. Level Gula, Add-on) beserta isi-nya dalam satu halaman')
@section('page-key', 'modifier-insert')

@section('page-actions')
    <a href="{{ route('master.modifiers.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke Modifier
    </a>
@endsection

@section('content')
<form method="POST" action="{{ route('master.modifiers.groups.store') }}">
    @csrf
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center gap-2">
                    <div class="stat-icon bg-soft-green" style="width:42px;height:42px;border-radius:12px"><i class="bi bi-sliders fs-5"></i></div>
                    <div>
                        <p class="section-title mb-0">Informasi Grup</p>
                        <p class="section-subtitle">Nama grup &amp; aturan pemilihan oleh pelanggan</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="group-name">Nama Grup <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="group-name" class="form-control" required maxlength="100" placeholder="Contoh: Level Gula" value="{{ old('name') }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="group-min">Min Dipilih</label>
                            <input type="number" name="min_selection" id="group-min" class="form-control" min="0" value="{{ old('min_selection', 0) }}">
                        </div>
                        <div class="col-6">
                            <label class="form-label" for="group-max">Maks Dipilih</label>
                            <input type="number" name="max_selection" id="group-max" class="form-control" min="1" value="{{ old('max_selection', 1) }}">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="is_required" id="group-required" value="1" @checked(old('is_required'))>
                                <label class="form-check-label" for="group-required">Wajib dipilih pelanggan</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="group-products">Produk Terkait</label>
                            <select name="product_ids[]" id="group-products" class="form-select" multiple size="9">
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" @selected(collect(old('product_ids', []))->contains((string) $product->id))>{{ $product->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Tahan Ctrl / Cmd untuk memilih lebih dari satu produk. Kosongkan bila berlaku untuk semua produk.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <p class="section-title mb-0">Modifier Awal <span class="text-secondary fw-normal">(opsional)</span></p>
                        <p class="section-subtitle">Tambahkan opsi pertama sekarang — sisanya bisa menyusul di halaman Modifier</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success" data-add-row="#modifier-rows" data-template="#modifier-row-template">
                        <i class="bi bi-plus-lg me-1"></i>Tambah Baris
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Nama Opsi</th><th style="width:130px">Harga (Rp)</th><th class="text-center" style="width:90px">Tersedia</th><th style="width:50px"></th></tr></thead>
                            <tbody id="modifier-rows"></tbody>
                        </table>
                    </div>
                    <div class="form-text mt-2">Baris yang nama-nya dikosongkan otomatis diabaikan saat disimpan.</div>
                </div>
                <template id="modifier-row-template">
                    <tr>
                        <td><input type="text" name="items[__INDEX__][name]" class="form-control form-control-sm" placeholder="Contoh: Extra Shot"></td>
                        <td><input type="number" name="items[__INDEX__][price]" class="form-control form-control-sm" min="0" step="any" placeholder="0"></td>
                        <td class="text-center"><input class="form-check-input" type="checkbox" name="items[__INDEX__][is_available]" value="1" checked></td>
                        <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-remove-row><i class="bi bi-trash"></i></button></td>
                    </tr>
                </template>
            </div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 justify-content-end mt-3">
        <a href="{{ route('master.modifiers.index') }}" class="btn btn-light"><i class="bi bi-x-lg me-1"></i>Batal</a>
        <button class="btn btn-success px-4"><i class="bi bi-check2 me-1"></i>Simpan Grup Modifier</button>
    </div>
</form>
@endsection
