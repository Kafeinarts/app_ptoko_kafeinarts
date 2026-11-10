@extends('layouts.app')

@section('title', 'Riwayat Transaksi')
@section('page-title', 'Riwayat Transaksi')
@section('page-subtitle', 'Cari, cetak ulang struk, retur, dan void transaksi')
@section('page-key', 'history')

@section('page-actions')
    <a href="{{ route('pos.index') }}" class="btn btn-sm btn-success"><i class="bi bi-cash-coin me-1"></i>Kasir</a>
@endsection

@section('content')
<form method="GET" action="{{ route('pos.history') }}" class="card mb-3" data-spa-form>
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label">Dari Tanggal</label>
                <input type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Sampai Tanggal</label>
                <input type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua status</option>
                    @foreach(['PAID' => 'PAID', 'VOID' => 'VOID', 'REFUND' => 'REFUND', 'PARTIAL_REFUND' => 'PARTIAL REFUND'] as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label">Kasir</label>
                <select name="cashier" class="form-select">
                    <option value="">Semua kasir</option>
                    @foreach($cashiers as $cashier)
                        <option value="{{ $cashier->id }}" @selected((string) ($filters['cashier'] ?? '') === (string) $cashier->id)>{{ $cashier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label">Invoice</label>
                <input type="search" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="INV-...">
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button class="btn btn-success flex-grow-1"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('pos.history') }}" class="btn btn-light" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <p class="section-title mb-0">Daftar Transaksi</p>
            <p class="section-subtitle">{{ $transactions->total() }} transaksi ditemukan</p>
        </div>
        <span class="badge badge-soft-secondary">{{ $currentOutlet?->name }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Tanggal</th>
                    <th>Kasir</th>
                    <th>Pelanggan</th>
                    <th>Metode</th>
                    <th class="text-end">Total</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($transactions as $trx)
                @php($statusColor = ['PAID' => 'success', 'VOID' => 'danger', 'REFUND' => 'warning', 'PARTIAL_REFUND' => 'warning'][$trx->status] ?? 'secondary')
                <tr>
                    <td>
                        <div class="fw-semibold small">{{ $trx->invoice_number }}</div>
                        <div class="text-secondary" style="font-size:.72rem">Shift #{{ $trx->shift_id }}</div>
                    </td>
                    <td class="small">{{ $trx->transaction_date->format('d/m/Y') }}<div class="text-secondary" style="font-size:.72rem">{{ $trx->transaction_date->format('H:i') }}</div></td>
                    <td class="small">{{ $trx->user?->name ?? '-' }}</td>
                    <td class="small">{{ $trx->customer?->name ?? 'Umum' }}</td>
                    <td><span class="badge badge-soft-secondary">{{ $trx->payments->first()?->payment_method ?? '-' }}</span></td>
                    <td class="text-end fw-semibold small">{{ \App\Helpers\CurrencyHelper::format($trx->grand_total) }}</td>
                    <td><span class="badge badge-soft-{{ $statusColor }}">{{ str_replace('_', ' ', $trx->status) }}</span></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ route('pos.receipt', $trx) }}" target="_blank" class="btn btn-outline-secondary" data-no-spa title="Cetak struk"><i class="bi bi-printer"></i></a>
                            @if(in_array($trx->status, ['PAID', 'PARTIAL_REFUND'], true) && auth()->user()->hasPermission('pos.return'))
                                <a href="{{ route('pos.history.return', $trx) }}" class="btn btn-outline-warning" title="Retur"><i class="bi bi-arrow-return-left"></i></a>
                            @endif
                            @if($trx->status === 'PAID' && auth()->user()->hasPermission('pos.void'))
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#void-{{ $trx->id }}" title="Void"><i class="bi bi-x-octagon"></i></button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><x-empty-state icon="bi bi-receipt" message="Belum ada transaksi sesuai filter." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $transactions->links() }}</div>
</div>

@if(auth()->user()->hasPermission('pos.void'))
    @foreach($transactions as $trx)
        @if($trx->status === 'PAID')
            <div class="modal fade" id="void-{{ $trx->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form method="POST" action="{{ route('pos.history.void', $trx) }}" class="modal-content border-0" data-confirm="Void transaksi {{ $trx->invoice_number }}?" data-confirm-text="Stok akan dikembalikan dan transaksi tidak dapat dipulihkan." data-confirm-button="Ya, void">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="bi bi-x-octagon text-danger me-1"></i> Void Transaksi</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger py-2 small">
                                <strong>{{ $trx->invoice_number }}</strong> · {{ \App\Helpers\CurrencyHelper::format($trx->grand_total) }}
                                <div>Stok produk akan otomatis dikembalikan ke gudang.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alasan Void <span class="text-danger">*</span></label>
                                <textarea name="reason" class="form-control" rows="3" minlength="5" maxlength="500" required placeholder="Contoh: salah input pesanan pelanggan"></textarea>
                            </div>
                            <div>
                                <label class="form-label">Disetujui Oleh <span class="text-danger">*</span></label>
                                <select name="approved_by" class="form-select" required>
                                    <option value="">Pilih penanggung jawab</option>
                                    @foreach($cashiers as $approver)
                                        <option value="{{ $approver->id }}">{{ $approver->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                            <button class="btn btn-danger"><i class="bi bi-x-octagon me-1"></i>Void Transaksi</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endforeach
@endif
@endsection
