@extends('layouts.app')

@section('title', 'Transfer Cabang')
@section('page-title', 'Transfer Stok Antar Cabang')
@section('page-subtitle', 'Kirim dan terima persediaan antar outlet')
@section('page-key', 'transfers')

@section('page-actions')
    <a href="{{ route('inventory.transfers.create') }}" class="btn btn-sm btn-success"><i class="bi bi-plus-lg me-1"></i>Transfer Baru</a>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <p class="section-title mb-0">Riwayat Transfer</p>
            <p class="section-subtitle">{{ $transfers->total() }} dokumen transfer</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>No. Transfer</th><th>Tanggal</th><th>Dari</th><th>Tujuan</th><th class="text-center">Item</th><th>Pengirim</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            @forelse($transfers as $transfer)
                @php($statusColor = ['PENDING' => 'secondary', 'SENT' => 'warning', 'RECEIVED' => 'success', 'CANCELLED' => 'danger'][$transfer->status] ?? 'secondary')
                <tr>
                    <td class="small fw-semibold">{{ $transfer->transfer_number }}</td>
                    <td class="small">{{ $transfer->transfer_date?->format('d/m/Y') }}</td>
                    <td class="small">{{ $transfer->fromOutlet?->name ?? '-' }}</td>
                    <td class="small">{{ $transfer->toOutlet?->name ?? '-' }}</td>
                    <td class="text-center"><span class="badge badge-soft-secondary">{{ $transfer->details_count }}</span></td>
                    <td class="small">{{ $transfer->user?->name ?? '-' }}</td>
                    <td><span class="badge badge-soft-{{ $statusColor }}">{{ $transfer->status }}</span></td>
                    <td class="text-end">
                        @if($transfer->status === 'SENT' && $transfer->to_outlet_id === $currentOutlet?->id)
                            <form method="POST" action="{{ route('inventory.transfers.receive', $transfer) }}" data-confirm="Terima transfer ini?" data-confirm-text="Stok tujuan akan ditambahkan dan stok asal dikurangi." data-confirm-button="Ya, terima">
                                @csrf
                                <button class="btn btn-sm btn-outline-success"><i class="bi bi-box-arrow-in-down me-1"></i>Terima</button>
                            </form>
                        @else
                            <span class="text-secondary small">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="empty-state"><i class="bi bi-arrow-left-right"></i>Belum ada transfer stok.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $transfers->links() }}</div>
</div>
@endsection
