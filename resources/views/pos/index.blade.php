@extends('layouts.app')

@section('title', 'Kasir / POS')
@section('page-title', 'Kasir / POS')
@section('page-subtitle', 'Transaksi cepat · '.($currentOutlet?->name ?? 'Outlet'))
@section('page-key', 'pos')

@section('page-actions')
    @if($activeShift)
        <span class="badge rounded-pill badge-soft-success px-3 py-2"><span class="stock-indicator bg-success"></span>Shift aktif sejak {{ $activeShift->opened_at->format('H:i') }}</span>
    @else
        <a href="{{ route('shifts.index') }}" class="btn btn-sm btn-warning"><i class="bi bi-exclamation-triangle me-1"></i>Shift Belum Dibuka</a>
    @endif
    <a href="{{ route('pos.history') }}" class="btn btn-sm btn-outline-success"><i class="bi bi-clock-history me-1"></i>Riwayat</a>
@endsection

@section('content')
<div id="pos-app" class="pos-layout" data-outlet-id="{{ $outletId }}" data-has-shift="{{ $activeShift ? '1' : '0' }}" data-tax="{{ (float) \App\Models\SystemSetting::get('tax_percentage', 0) }}" data-shift-url="{{ route('shifts.index') }}">
    <div class="pos-products-panel">
        <div class="pos-toolbar">
            <div class="pos-search">
                <i class="bi bi-search"></i>
                <input type="search" id="pos-search" class="form-control" placeholder="Cari nama, SKU, atau barcode..." autocomplete="off">
            </div>
            <button class="btn btn-outline-secondary d-lg-none" type="button" data-close-cart><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="category-pills mb-3">
            <button type="button" class="category-pill active" data-category="all"><i class="bi bi-grid me-1"></i>Semua</button>
            @foreach($categories as $category)
                <button type="button" class="category-pill" data-category="{{ $category->id }}">{{ $category->name }}</button>
            @endforeach
        </div>

        <div class="product-grid" id="product-grid">
            @forelse($products as $product)
                @php
                    $variants = $product->variants->map(function ($variant) use ($outletId) {
                        $price = $variant->prices->firstWhere('outlet_id', $outletId) ?? $variant->prices->first();
                        $stock = $variant->stocks->firstWhere('outlet_id', $outletId);
                        return [
                            'id' => $variant->id,
                            'name' => $variant->variant_name,
                            'price' => (float) ($price->selling_price ?? 0),
                            'stock' => (int) ($stock->quantity ?? 0),
                            'has_price' => (bool) $price,
                        ];
                    })->filter(fn ($v) => $v['has_price'])->values();
                    $totalStock = $variants->sum('stock');
                    $minPrice = $variants->min('price') ?? 0;
                    $payload = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'variants' => $variants->all(),
                        'modifiers' => $product->modifierGroups->map(fn ($group) => [
                            'id' => $group->id,
                            'name' => $group->name,
                            'required' => (bool) $group->is_required,
                            'items' => $group->modifiers->where('is_available', true)->map(fn ($modifier) => [
                                'id' => $modifier->id,
                                'name' => $modifier->name,
                                'price' => (float) $modifier->price,
                            ])->values()->all(),
                        ])->values()->all(),
                    ];
                @endphp
                @if($variants->isNotEmpty())
                    <button type="button" class="product-tile {{ $totalStock <= 0 ? 'is-out' : '' }}"
                            data-product="{{ json_encode($payload) }}"
                            data-category="{{ $product->category_id }}"
                            data-search="{{ strtolower($product->name.' '.$product->sku.' '.($product->barcode ?? '').' '.$product->category?->name) }}"
                            data-out="{{ $totalStock <= 0 ? '1' : '0' }}">
                        <div class="tile-img">
                            @if($product->image_path)
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy">
                            @else
                                <i class="bi bi-cup-hot"></i>
                            @endif
                        </div>
                        @if($totalStock <= 0)
                            <span class="tile-badge">Habis</span>
                        @elseif($totalStock <= 5)
                            <span class="tile-badge" style="color:#8a5a00;background:#fdf1db">Sisa {{ $totalStock }}</span>
                        @endif
                        <div class="tile-body">
                            <span class="tile-name">{{ $product->name }}</span>
                            <span class="tile-price">{{ \App\Helpers\CurrencyHelper::format($minPrice) }}</span>
                            <span class="tile-stock {{ $totalStock <= 5 ? 'low' : '' }}">Stok {{ $totalStock }} {{ $product->unit?->short_name }}</span>
                        </div>
                    </button>
                @endif
            @empty
                <div class="empty-state w-100"><i class="bi bi-cup-hot"></i>Belum ada produk aktif. Tambahkan produk terlebih dahulu.</div>
            @endforelse
        </div>
    </div>

    <aside class="pos-cart" id="pos-cart">
        <div class="pos-cart-header">
            <div>
                <div class="fw-bold"><i class="bi bi-basket me-1"></i> Keranjang</div>
                <div class="small text-secondary"><span data-cart-count>0</span> item dipilih</div>
            </div>
            <div class="d-flex gap-1">
                <button type="button" class="btn btn-sm btn-light btn-icon" id="btn-clear-cart" title="Kosongkan"><i class="bi bi-trash"></i></button>
                <button type="button" class="btn btn-sm btn-light btn-icon d-none pos-cart-close" data-close-cart title="Tutup"><i class="bi bi-x-lg"></i></button>
            </div>
        </div>
        <div class="pos-cart-items" id="cart-items"></div>
        <div class="pos-cart-summary">
            <div id="cart-shift-hint" class="alert alert-warning py-2 px-2 small mb-2">
                <i class="bi bi-exclamation-triangle me-1"></i> Shift belum dibuka. Transaksi akan diblokir sampai shift dibuka.
            </div>
            <div class="summary-row"><span>Subtotal</span><span id="cart-subtotal">Rp 0</span></div>
            <div class="summary-row"><span>Diskon</span><span id="cart-discount" class="text-danger">Rp 0</span></div>
            <div class="summary-row"><span id="cart-tax-label">Pajak (0%)</span><span id="cart-tax">Rp 0</span></div>
            <div class="summary-row total"><span>Total</span><span id="cart-total">Rp 0</span></div>
            <button class="btn btn-success w-100 py-2 mt-2" id="btn-payment" disabled>
                <i class="bi bi-credit-card me-1"></i> Bayar Sekarang
            </button>
            <div class="text-center small text-secondary mt-2"><i class="bi bi-shield-check me-1"></i>Pembayaran diproses aman</div>
        </div>
    </aside>
</div>

<button type="button" class="mobile-cart-fab d-none" id="mobile-cart-fab">
    <i class="bi bi-basket"></i> Keranjang <span class="badge" data-cart-count>0</span>
</button>

<div class="modal fade" id="payment-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0" style="border-radius:18px">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-credit-card me-1"></i> Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="checkout-form">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-lg-5">
                            <div class="p-3 rounded-3" style="background:#f4f8f6">
                                <div class="small text-secondary">Total Tagihan</div>
                                <div class="fs-3 fw-bold text-success" id="pay-total">Rp 0</div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between small"><span>Subtotal</span><span id="pay-subtotal">Rp 0</span></div>
                                <div class="d-flex justify-content-between small"><span>Diskon</span><span id="pay-discount-preview" class="text-danger">Rp 0</span></div>
                                <div class="d-flex justify-content-between small"><span>Pajak</span><span id="pay-tax">Rp 0</span></div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Pelanggan</label>
                                <select class="form-select" id="pay-customer">
                                    <option value="">Umum / Tanpa pelanggan</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }} · {{ $customer->phone }} ({{ $customer->loyalty_points }} poin)</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mt-2">
                                <label class="form-label">Voucher</label>
                                <select class="form-select" id="pay-voucher">
                                    <option value="">Tanpa voucher</option>
                                    @foreach($vouchers as $voucher)
                                        <option value="{{ $voucher->code }}">{{ $voucher->code }} · {{ $voucher->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-2 mt-1">
                                <div class="col-7">
                                    <label class="form-label">Diskon Transaksi (Rp)</label>
                                    <input type="number" min="0" step="100" class="form-control" id="pay-discount" value="0">
                                </div>
                                <div class="col-5">
                                    <label class="form-label">Poin Dipakai</label>
                                    <input type="number" min="0" class="form-control" id="pay-points" value="0">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <label class="form-label">Metode Pembayaran</label>
                            <div class="payment-methods">
                                <button type="button" class="payment-method active" data-method="CASH"><i class="bi bi-cash-stack"></i>Cash</button>
                                <button type="button" class="payment-method" data-method="QRIS"><i class="bi bi-qr-code"></i>QRIS</button>
                                <button type="button" class="payment-method" data-method="TRANSFER"><i class="bi bi-bank"></i>Transfer</button>
                                <button type="button" class="payment-method" data-method="DEBIT"><i class="bi bi-credit-card"></i>Debit</button>
                                <button type="button" class="payment-method" data-method="CREDIT"><i class="bi bi-credit-card-2-front"></i>Kredit</button>
                            </div>
                            <input type="hidden" name="payment_method" value="CASH">
                            <div id="cash-only-fields">
                                <label class="form-label">Nominal Diterima</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" inputmode="numeric" class="form-control" id="pay-amount" value="0">
                                </div>
                                <div class="quick-cash">
                                    <button type="button" data-quick-cash="5000">+5rb</button>
                                    <button type="button" data-quick-cash="10000">+10rb</button>
                                    <button type="button" data-quick-cash="20000">+20rb</button>
                                    <button type="button" data-quick-cash="50000">+50rb</button>
                                    <button type="button" data-quick-cash="100000">+100rb</button>
                                </div>
                                <div class="change-preview"><span>Kembalian</span><span id="pay-change">Rp 0</span></div>
                            </div>
                            <div class="row g-2 mt-2">
                                <div class="col-md-6">
                                    <label class="form-label">No. Referensi</label>
                                    <input type="text" class="form-control" id="pay-reference" placeholder="Opsional">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Catatan</label>
                                    <input type="text" class="form-control" id="pay-notes" placeholder="Opsional">
                                </div>
                            </div>
                            <div class="alert alert-light border mt-3 small mb-0">
                                <i class="bi bi-info-circle me-1"></i>Stok akan otomatis berkurang dan struk dapat dicetak setelah pembayaran berhasil.
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success px-4" id="btn-place-order"><i class="bi bi-check2-circle me-1"></i> Bayar Sekarang</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
