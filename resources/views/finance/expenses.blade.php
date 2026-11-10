@extends('layouts.app')

@section('title', 'Pengeluaran')
@section('page-title', 'Pengeluaran')
@section('page-subtitle', 'Catat seluruh biaya operasional outlet')
@section('page-key', 'expenses')

@section('page-actions')
    @if(auth()->user()->hasPermission('finance.manage'))
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#create-expense-category"><i class="bi bi-tags me-1"></i>Kategori</button>
        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#create-expense"><i class="bi bi-plus-lg me-1"></i>Catat Pengeluaran</button>
    @endif
@endsection

@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-label">Total Pengeluaran</div>
            <div class="stat-value text-danger">{{ \App\Helpers\CurrencyHelper::format($total) }}</div>
            <div class="stat-hint">{{ \Carbon\Carbon::parse($from)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($to)->format('d/m/Y') }}</div>
        </div>
    </div>
    <div class="col-md-8">
        <form method="GET" class="card h-100" data-spa-form>
            <div class="card-body d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label">Dari</label>
                    <input type="date" name="from" class="form-control" value="{{ $from }}">
                </div>
                <div>
                    <label class="form-label">Sampai</label>
                    <input type="date" name="to" class="form-control" value="{{ $to }}">
                </div>
                <button class="btn btn-success"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('finance.expenses') }}" class="btn btn-light"><i class="bi bi-arrow-counterclockwise"></i></a>
                <a href="{{ route('finance.incomes') }}" class="btn btn-outline-success ms-auto"><i class="bi bi-arrow-down-circle me-1"></i>Pemasukan</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <p class="section-title mb-0">Riwayat Pengeluaran</p>
            <p class="section-subtitle">{{ $expenses->total() }} transaksi pengeluaran</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Tanggal</th><th>Kategori</th><th>Catatan</th><th>Dicatat Oleh</th><th class="text-end">Nominal</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            @forelse($expenses as $expense)
                <tr>
                    <td class="small">{{ $expense->expense_date->format('d/m/Y') }}</td>
                    <td><span class="badge badge-soft-danger">{{ $expense->category?->name ?? '-' }}</span></td>
                    <td class="small text-secondary">{{ $expense->notes ?: '—' }}</td>
                    <td class="small">{{ $expense->user?->name ?? '-' }}</td>
                    <td class="text-end small fw-semibold text-danger">{{ \App\Helpers\CurrencyHelper::format($expense->amount) }}</td>
                    <td class="text-end">
                        @if(auth()->user()->hasPermission('finance.manage'))
                            <form method="POST" action="{{ route('finance.expenses.destroy', $expense) }}" data-confirm="Hapus pengeluaran ini?" data-confirm-text="Catatan pengeluaran akan dihapus permanen." data-confirm-button="Ya, hapus">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state"><i class="bi bi-arrow-up-circle"></i>Belum ada pengeluaran pada periode ini.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $expenses->links() }}</div>
</div>

<div class="modal fade" id="create-expense" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('finance.expenses.store') }}" class="modal-content border-0">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-up-circle me-1"></i>Catat Pengeluaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                    <select name="expense_category_id" class="form-select" required>
                        <option value="">Pilih kategori</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nominal <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="amount" class="form-control" min="1" step="any" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                    <input type="date" name="expense_date" class="form-control" value="{{ now()->toDateString() }}" required>
                </div>
                <div>
                    <label class="form-label">Catatan</label>
                    <textarea name="notes" class="form-control" rows="2" maxlength="500" placeholder="Contoh: pembelian gas dan es batu"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-danger"><i class="bi bi-check2 me-1"></i>Simpan</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="create-expense-category" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('finance.expenses.categories') }}" class="modal-content border-0">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Kategori Pengeluaran Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required maxlength="100" placeholder="Contoh: Operasional">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-success">Simpan Kategori</button>
            </div>
        </form>
    </div>
</div>
@endsection
