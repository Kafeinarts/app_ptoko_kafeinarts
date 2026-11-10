@extends('layouts.app')

@section('title', 'Modifier')
@section('page-title', 'Modifier Produk')
@section('page-subtitle', 'Atur opsi tambahan seperti topping, level gula, dan ukuran')
@section('page-key', 'modifiers')

@section('page-actions')
    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#create-group"><i class="bi bi-plus-lg me-1"></i>Tambah Grup</button>
@endsection

@section('content')
<div class="row g-3">
    @forelse($groups as $group)
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-start">
                    <div>
                        <p class="section-title mb-0">{{ $group->name }}</p>
                        <p class="section-subtitle">
                            {{ $group->is_required ? 'Wajib dipilih' : 'Opsional' }} · min {{ $group->min_selection }} / maks {{ $group->max_selection }}
                            · {{ $group->products_count }} produk
                        </p>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#edit-group-{{ $group->id }}"><i class="bi bi-pencil"></i></button>
                        <form method="POST" action="{{ route('master.modifiers.groups.destroy', $group) }}" data-confirm="Hapus grup {{ $group->name }}?" data-confirm-text="Seluruh modifier pada grup ini akan dihapus." data-confirm-button="Ya, hapus">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($group->modifiers as $modifier)
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small fw-semibold">{{ $modifier->name }}</div>
                                <div class="text-secondary" style="font-size:.72rem">{{ \App\Helpers\CurrencyHelper::format($modifier->price) }} · {{ $modifier->is_available ? 'Tersedia' : 'Tidak tersedia' }}</div>
                            </div>
                            <form method="POST" action="{{ route('master.modifiers.items.destroy', $modifier) }}" data-confirm="Hapus modifier {{ $modifier->name }}?" data-confirm-text="Modifier akan dihapus dari grup ini." data-confirm-button="Ya, hapus">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    @empty
                        <div class="empty-state py-3"><i class="bi bi-sliders"></i>Belum ada item modifier.</div>
                    @endforelse
                </div>
                <div class="card-footer bg-white">
                    <form method="POST" action="{{ route('master.modifiers.items.store', $group) }}" class="d-flex gap-2">
                        @csrf
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="Nama modifier" required maxlength="100">
                        <input type="number" name="price" class="form-control form-control-sm" placeholder="Harga" min="0" step="any" required style="max-width:120px">
                        <button class="btn btn-sm btn-outline-success"><i class="bi bi-plus-lg"></i></button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12"><div class="card"><div class="empty-state"><i class="bi bi-sliders"></i>Belum ada grup modifier. Tambahkan grup pertama Anda.</div></div></div>
    @endforelse
</div>

<div class="modal fade" id="create-group" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" action="{{ route('master.modifiers.groups.store') }}" class="modal-content border-0">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Tambah Grup Modifier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Grup <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required maxlength="100" placeholder="Contoh: Level Gula">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Min</label>
                        <input type="number" name="min_selection" class="form-control" min="0" value="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Maks</label>
                        <input type="number" name="max_selection" class="form-control" min="1" value="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Wajib</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_required" value="1">
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Produk Terkait</label>
                        <select name="product_ids[]" class="form-select" multiple size="8">
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Tahan Ctrl / Cmd untuk memilih lebih dari satu produk.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-success"><i class="bi bi-check2 me-1"></i>Simpan</button>
            </div>
        </form>
    </div>
</div>

@foreach($groups as $group)
    <div class="modal fade" id="edit-group-{{ $group->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form method="POST" action="{{ route('master.modifiers.groups.update', $group) }}" class="modal-content border-0">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Grup {{ $group->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Grup <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ $group->name }}" required maxlength="100">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Min</label>
                            <input type="number" name="min_selection" class="form-control" min="0" value="{{ $group->min_selection }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Maks</label>
                            <input type="number" name="max_selection" class="form-control" min="1" value="{{ $group->max_selection }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Wajib</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" role="switch" name="is_required" value="1" @checked($group->is_required)>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Produk Terkait</label>
                            <select name="product_ids[]" class="form-select" multiple size="8">
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" @selected($group->products->contains($product->id))>{{ $product->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-success"><i class="bi bi-check2 me-1"></i>Simpan</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
@endsection
