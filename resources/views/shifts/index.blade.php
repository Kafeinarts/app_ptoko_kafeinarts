@extends('layouts.app')

@section('title', 'Shift & Kas')
@section('page-title', 'Shift & Kas')
@section('page-subtitle', 'Kelola buka/tutup shift, arus kas, dan rekonsiliasi kasir')
@section('page-key', 'shifts')

@section('page-actions')
    @if($activeShift)
        <span class="badge rounded-pill badge-soft-success px-3 py-2"><span class="stock-indicator bg-success"></span>Shift terbuka · {{ $activeShift->opened_at->format('H:i') }}</span>
    @else
        <span class="badge rounded-pill badge-soft-warning px-3 py-2"><span class="stock-indicator bg-warning"></span>Belum ada shift aktif</span>
    @endif
    <a href="{{ route('pos.index') }}" class="btn btn-sm btn-success"><i class="bi bi-cash-coin me-1"></i>Kasir</a>
@endsection

@section('content')
@if($activeShift)
    <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label">Modal Awal</div>
                <div class="stat-value">{{ \App\Helpers\CurrencyHelper::format($activeShift->starting_cash) }}</div>
                <div class="stat-hint">Dibuka {{ $activeShift->opened_at->format('d/m/Y H:i') }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label">Kas Seharusnya</div>
                <div class="stat-value">{{ \App\Helpers\CurrencyHelper::format($expectedCash) }}</div>
                <div class="stat-hint">Modal + penjualan tunai ± arus kas</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label">Transaksi Shift</div>
                <div class="stat-value">{{ number_format($shiftTransactions->count(), 0, ',', '.') }}</div>
                <div class="stat-hint">Ditampilkan maks. 10 terakhir</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-label">Arus Kas</div>
                <div class="stat-value">{{ \App\Helpers\CurrencyHelper::format($cashLogs->sum(fn ($log) => $log->type === 'CASH_IN' ? (float) $log->amount : -(float) $log->amount)) }}</div>
                <div class="stat-hint">{{ $cashLogs->count() }} catatan kas</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <p class="section-title mb-0">Tutup Shift</p>
                    <p class="section-subtitle">Hitung kas fisik di laci</p>
                </div>
                <form method="POST" action="{{ route('shifts.close') }}" class="card-body" data-confirm="Tutup shift sekarang?" data-confirm-text="Pastikan kas fisik sudah dihitung. Shift yang ditutup tidak dapat dibuka kembali." data-confirm-button="Ya, tutup shift">
                    @csrf
                    <input type="hidden" name="shift_id" value="{{ $activeShift->id }}">
                    <div class="mb-3">
                        <label class="form-label">Kas Fisik Aktual <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="actual_cash" class="form-control form-control-lg" min="0" step="100" value="{{ (int) $expectedCash }}" required>
                        </div>
                        <div class="form-text">Kas seharusnya {{ \App\Helpers\CurrencyHelper::format($expectedCash) }}</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2" maxlength="500" placeholder="Opsional"></textarea>
                    </div>
                    <button class="btn btn-danger w-100"><i class="bi bi-lock me-1"></i>Tutup Shift & Cetak Ringkasan</button>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <p class="section-title mb-0">Catat Arus Kas</p>
                    <p class="section-subtitle">Kas masuk / keluar di luar penjualan</p>
                </div>
                <form method="POST" action="{{ route('shifts.cash-log') }}" class="card-body">
                    @csrf
                    <input type="hidden" name="shift_id" value="{{ $activeShift->id }}">
                    <div class="mb-3">
                        <label class="form-label">Jenis</label>
                        <select name="type" class="form-select" required>
                            <option value="CASH_IN">Kas Masuk (CASH IN)</option>
                            <option value="CASH_OUT">Kas Keluar (CASH OUT)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nominal <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="amount" class="form-control" min="1" step="100" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Keterangan <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control" maxlength="255" required placeholder="Contoh: beli es batu">
                    </div>
                    <button class="btn btn-outline-success w-100"><i class="bi bi-plus-circle me-1"></i>Simpan Arus Kas</button>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <p class="section-title mb-0">Arus Kas Shift</p>
                    <p class="section-subtitle">Riwayat kas masuk & keluar</p>
                </div>
                <div class="list-group list-group-flush" style="max-height:360px;overflow:auto">
                    @forelse($cashLogs as $log)
                        <div class="list-group-item d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-semibold small {{ $log->type === 'CASH_IN' ? 'text-success' : 'text-danger' }}">
                                    {{ $log->type === 'CASH_IN' ? '+' : '-' }}{{ \App\Helpers\CurrencyHelper::format($log->amount) }}
                                </div>
                                <div class="small text-secondary">{{ $log->reason }}</div>
                                <div class="text-secondary" style="font-size:.7rem">{{ $log->created_at?->format('d/m/Y H:i') }}</div>
                            </div>
                            <span class="badge badge-soft-{{ $log->type === 'CASH_IN' ? 'success' : 'danger' }}">{{ $log->type }}</span>
                        </div>
                    @empty
                        <div class="empty-state py-4"><i class="bi bi-cash-stack"></i>Belum ada arus kas.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            <p class="section-title mb-0">Transaksi Shift Ini</p>
            <p class="section-subtitle">10 transaksi terakhir pada shift aktif</p>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Invoice</th><th>Waktu</th><th>Metode</th><th class="text-end">Total</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($shiftTransactions as $trx)
                    <tr>
                        <td class="small fw-semibold">{{ $trx->invoice_number }}</td>
                        <td class="small">{{ $trx->transaction_date->format('H:i') }}</td>
                        <td><span class="badge badge-soft-secondary">{{ $trx->payments->first()?->payment_method ?? '-' }}</span></td>
                        <td class="text-end small fw-semibold">{{ \App\Helpers\CurrencyHelper::format($trx->grand_total) }}</td>
                        <td><span class="badge badge-soft-{{ $trx->status === 'PAID' ? 'success' : 'warning' }}">{{ $trx->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state py-3"><i class="bi bi-receipt"></i>Belum ada transaksi pada shift ini.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <p class="section-title mb-0">Buka Shift</p>
                    <p class="section-subtitle">Mulai sesi kasir dengan modal awal</p>
                </div>
                <form method="POST" action="{{ route('shifts.open') }}" class="card-body">
                    @csrf
                    <div class="text-center mb-3">
                        <div class="stat-icon bg-soft-green mx-auto mb-2" style="width:64px;height:64px;font-size:1.6rem"><i class="bi bi-unlock"></i></div>
                        <p class="text-secondary small mb-0">Shift diperlukan sebelum transaksi POS dapat diproses.</p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Modal Awal Kas <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="starting_cash" class="form-control" min="0" step="100" value="0" required>
                        </div>
                    </div>
                    <button class="btn btn-success w-100"><i class="bi bi-unlock me-1"></i>Buka Shift</button>
                </form>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="empty-state py-4">
                        <i class="bi bi-clock-history"></i>
                        <div class="fw-semibold">Belum ada shift aktif</div>
                        <div class="small">Buka shift terlebih dahulu. Semua transaksi akan tercatat pada shift yang sedang aktif.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <p class="section-title mb-0">Riwayat Shift</p>
            <p class="section-subtitle">Seluruh shift pada outlet {{ $currentOutlet?->name }}</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr><th>Kasir</th><th>Dibuka</th><th>Ditutup</th><th class="text-end">Modal</th><th class="text-end">Kas Seharusnya</th><th class="text-end">Kas Fisik</th><th class="text-end">Selisih</th><th class="text-center">Transaksi</th><th>Status</th><th class="text-end">Aksi</th></tr>
            </thead>
            <tbody>
            @forelse($shifts as $shift)
                <tr>
                    <td class="small fw-semibold">{{ $shift->user?->name ?? '-' }}</td>
                    <td class="small">{{ $shift->opened_at?->format('d/m/Y H:i') }}</td>
                    <td class="small">{{ $shift->closed_at?->format('d/m/Y H:i') ?? '-' }}</td>
                    <td class="text-end small">{{ \App\Helpers\CurrencyHelper::format($shift->starting_cash) }}</td>
                    <td class="text-end small">{{ $shift->ending_cash !== null ? \App\Helpers\CurrencyHelper::format($shift->ending_cash) : '-' }}</td>
                    <td class="text-end small">{{ $shift->actual_cash !== null ? \App\Helpers\CurrencyHelper::format($shift->actual_cash) : '-' }}</td>
                    <td class="text-end small fw-semibold {{ (float) $shift->difference < 0 ? 'text-danger' : ((float) $shift->difference > 0 ? 'text-success' : '') }}">
                        {{ $shift->difference !== null ? \App\Helpers\CurrencyHelper::format($shift->difference) : '-' }}
                    </td>
                    <td class="text-center small">{{ $shift->transactions_count }}</td>
                    <td><span class="badge badge-soft-{{ $shift->status === 'OPEN' ? 'success' : 'secondary' }}">{{ $shift->status }}</span></td>
                    <td class="text-end">
                        @if($shift->status === 'CLOSED')
                            <a href="{{ route('shifts.receipt', $shift) }}" target="_blank" class="btn btn-sm btn-outline-secondary" data-no-spa title="Cetak ringkasan"><i class="bi bi-printer"></i></a>
                        @else
                            <span class="text-secondary small">Aktif</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="10"><div class="empty-state"><i class="bi bi-clock-history"></i>Belum ada riwayat shift.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $shifts->links() }}</div>
</div>
@endsection
