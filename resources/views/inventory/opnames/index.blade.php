@extends('layouts.app')

@section('title', 'Stock Opname')
@section('page-title', 'Stock Opname')
@section('page-subtitle', 'Riwayat penghitungan fisik dan penyesuaian stok')
@section('page-key', 'opnames')

@section('page-actions')
    <a href="{{ route('inventory.opnames.create') }}" class="btn btn-sm btn-success"><i class="bi bi-plus-lg me-1"></i>Opname Baru</a>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <p class="section-title mb-0">Riwayat Opname</p>
            <p class="section-subtitle">{{ $opnames->total() }} dokumen opname</p>
        </div>
        <a href="{{ route('inventory.stocks') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-seam me-1"></i>Stok Barang</a>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>No. Opname</th><th>Tanggal</th><th>Petugas</th><th class="text-center">Item</th><th>Catatan</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            @forelse($opnames as $opname)
                <tr>
                    <td class="small fw-semibold">{{ $opname->opname_number }}</td>
                    <td class="small">{{ $opname->opname_date->format('d/m/Y') }}</td>
                    <td class="small">{{ $opname->user?->name ?? '-' }}</td>
                    <td class="text-center"><span class="badge badge-soft-secondary">{{ $opname->details_count }}</span></td>
                    <td class="small text-secondary">{{ \Illuminate\Support\Str::limit($opname->notes, 60) ?: '—' }}</td>
                    <td class="text-end"><a href="{{ route('inventory.opnames.show', $opname) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-eye me-1"></i>Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="6"><x-empty-state icon="bi bi-clipboard-check" message="Belum ada stock opname." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $opnames->links() }}</div>
</div>
@endsection
