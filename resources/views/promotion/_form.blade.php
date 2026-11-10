<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" action="{{ $action }}" class="modal-content border-0">
            @csrf
            @if($method !== 'POST')
                @method($method)
            @endif
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-ticket-perforated me-1"></i>{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Kode Voucher <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control text-uppercase" value="{{ old('code', $voucher->code ?? '') }}" required maxlength="30" placeholder="HEMAT10">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Nama Promo <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $voucher->name ?? '') }}" required maxlength="100" placeholder="Diskon pembukaan cabang">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tipe Diskon <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="PERCENTAGE" @selected(old('type', $voucher->type ?? 'PERCENTAGE') === 'PERCENTAGE')>Persentase (%)</option>
                            <option value="FIXED_AMOUNT" @selected(old('type', $voucher->type ?? '') === 'FIXED_AMOUNT')>Nominal (Rp)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nilai <span class="text-danger">*</span></label>
                        <input type="number" name="value" class="form-control" min="0" step="any" value="{{ old('value', $voucher->value ?? 0) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Maks. Diskon</label>
                        <input type="number" name="max_discount" class="form-control" min="0" step="any" value="{{ old('max_discount', $voucher->max_discount ?? 0) }}" placeholder="0 = tanpa batas">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Min. Belanja</label>
                        <input type="number" name="min_purchase" class="form-control" min="0" step="any" value="{{ old('min_purchase', $voucher->min_purchase ?? 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Berlaku Dari <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="valid_from" class="form-control" value="{{ old('valid_from', isset($voucher) ? $voucher->valid_from?->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Berlaku Sampai <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="valid_until" class="form-control" value="{{ old('valid_until', isset($voucher) ? $voucher->valid_until?->format('Y-m-d\TH:i') : now()->addMonth()->format('Y-m-d\TH:i')) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" @checked(old('is_active', $voucher->is_active ?? true))>
                            <label class="form-check-label">Aktif</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-success"><i class="bi bi-check2 me-1"></i>Simpan Voucher</button>
            </div>
        </form>
    </div>
</div>
