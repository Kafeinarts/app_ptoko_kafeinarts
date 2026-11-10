@extends('layouts.app')

@section('title', 'Pengaturan Umum')
@section('page-title', 'Pengaturan Umum')
@section('page-subtitle', 'Identitas toko, pajak, printer struk, pembayaran, dan loyalitas')
@section('page-key', 'settings-general')

@section('content')
@php
    $all = $settings->flatten()->keyBy('key');
    $val = fn ($key, $default = '') => old($key, data_get($all, $key.'.value', $default));
@endphp

<form method="POST" action="{{ route('settings.general.update') }}">
    @csrf
    @method('PUT')

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <p class="section-title mb-0"><i class="bi bi-shop me-1"></i>Identitas Toko</p>
                    <p class="section-subtitle">Nama yang ditampilkan pada struk dan laporan</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="store_name">Nama Toko</label>
                            <input type="text" name="store_name" id="store_name" class="form-control" value="{{ $val('store_name', 'Kafeinarts') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="tax_percentage">Pajak / PPN (%)</label>
                            <input type="number" name="tax_percentage" id="tax_percentage" class="form-control" step="0.01" min="0" max="100" value="{{ $val('tax_percentage', 0) }}">
                            <div class="form-text">Nilai 0 bila tidak menerapkan pajak.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <p class="section-title mb-0"><i class="bi bi-printer me-1"></i>Printer & Struk</p>
                    <p class="section-subtitle">Ukuran kertas dan perilaku cetak otomatis</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="receipt_paper">Ukuran Kertas</label>
                            <select name="receipt_paper" id="receipt_paper" class="form-select">
                                <option value="58" @selected($val('receipt_paper', '58') === '58')>58 mm (Thermal)</option>
                                <option value="80" @selected($val('receipt_paper', '58') === '80')>80 mm (Thermal)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="receipt_printer">Nama Printer</label>
                            <input type="text" name="receipt_printer" id="receipt_printer" class="form-control" value="{{ $val('receipt_printer') }}" placeholder="Default sistem">
                        </div>
                        <div class="col-12">
                            <input type="hidden" name="auto_print_receipt" value="0">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" name="auto_print_receipt" id="auto_print_receipt" value="1" @checked($val('auto_print_receipt', '0') === '1')>
                                <label class="form-check-label" for="auto_print_receipt">Cetak struk otomatis setelah pembayaran</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <p class="section-title mb-0"><i class="bi bi-credit-card me-1"></i>Metode Pembayaran</p>
                    <p class="section-subtitle">Aktifkan metode yang tersedia di kasir</p>
                </div>
                <div class="card-body">
                    @foreach(['payment_cash' => 'Tunai', 'payment_qris' => 'QRIS', 'payment_transfer' => 'Transfer Bank', 'payment_debit' => 'Kartu Debit'] as $key => $label)
                        <input type="hidden" name="{{ $key }}" value="0">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch" name="{{ $key }}" id="{{ $key }}" value="1" @checked($val($key, $key === 'payment_cash' ? '1' : '0') === '1')>
                            <label class="form-check-label" for="{{ $key }}">{{ $label }}</label>
                        </div>
                    @endforeach
                    <hr>
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label" for="bank_name">Nama Bank</label>
                            <input type="text" name="bank_name" id="bank_name" class="form-control form-control-sm" value="{{ $val('bank_name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="bank_account_number">No. Rekening</label>
                            <input type="text" name="bank_account_number" id="bank_account_number" class="form-control form-control-sm" value="{{ $val('bank_account_number') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="bank_account_name">Atas Nama</label>
                            <input type="text" name="bank_account_name" id="bank_account_name" class="form-control form-control-sm" value="{{ $val('bank_account_name') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="qris_payload">Payload QRIS</label>
                            <textarea name="qris_payload" id="qris_payload" class="form-control form-control-sm" rows="2" placeholder="Tempel payload QRIS statis di sini">{{ $val('qris_payload') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <p class="section-title mb-0"><i class="bi bi-stars me-1"></i>Program Loyalitas</p>
                    <p class="section-subtitle">Atur perolehan dan nilai tukar poin pelanggan</p>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="loyalty_earn_per_amount">Perolehan Poin per Nominal</label>
                            <input type="number" name="loyalty_earn_per_amount" id="loyalty_earn_per_amount" class="form-control" step="1" min="0" value="{{ $val('loyalty_earn_per_amount', 10000) }}">
                            <div class="form-text">Contoh: 10000 berarti setiap Rp 10.000 belanja mendapat 1 poin.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="loyalty_point_value">Nilai Tukar 1 Poin (Rp)</label>
                            <input type="number" name="loyalty_point_value" id="loyalty_point_value" class="form-control" step="1" min="0" value="{{ $val('loyalty_point_value', 100) }}">
                            <div class="form-text">Nilai potongan harga untuk setiap 1 poin yang ditukar.</div>
                        </div>
                    </div>
                    <div class="alert alert-warning small mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>Perubahan pengaturan berlaku untuk transaksi berikutnya.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mt-3">
        <button class="btn btn-success px-4">
            <i class="bi bi-check2 me-1"></i>Simpan Pengaturan
        </button>
    </div>
</form>
@endsection
