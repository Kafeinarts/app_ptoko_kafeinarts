@extends('layouts.app')

@section('title', 'Detail Opname')
@section('page-title', 'Detail Stock Opname')
@section('page-subtitle', $opname->opname_number.' · '.$opname->opname_date->format('d/m/Y'))
@section('page-key', 'opname-show')

@section('page-actions')
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Cetak</button>
    <a href="{{ route('inventory.opnames.index') }}" class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
@endsection

@section('content')
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Total Item</div>
            <div class="stat-value">{{ $opname->details->count() }}</div>
            <div class="stat-hint">Varian dihitung</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Selisih Unit</div>
            <div class="stat-value">{{ $opname->details->sum('difference_qty') }}</div>
            <div class="stat-hint">Fisik − sistem</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Nilai Selisih</div>
            <div class="stat-value">{{ \App\Helpers\CurrencyHelper::format($opname->details->sum('difference_value')) }}</div>
            <div class="stat-hint">Berdasarkan harga modal</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-label">Petugas</div>
            <div class="stat-value" style="font-size:1.1rem">{{ $opname->user?->name ?? '-' }}</div>
            <div class="stat-hint">{{ $opname->outlet?->name }}</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <p class="section-title mb-0">Rincian Opname</p>
            <p class="section-subtitle">{{ $opname->notes ?: 'Tanpa catatan' }}</p>
        </div>
        <span class="badge badge-soft-secondary">{{ $opname->opname_number }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Produk</th><th class="text-center">Stok Sistem</th><th class="text-center">Stok Fisik</th><th class="text-center">Selisih</th><th class="text-end">Nilai Selisih</th></tr></thead>
            <tbody>
            @foreach($opname->details as $detail)
                @php($diff = (int) $detail->difference_qty)
                <tr>
                    <td class="small fw-semibold">{{ $detail->variant?->display_name ?? '-' }}</td>
                    <td class="text-center">{{ $detail->system_qty }}</td>
                    <td class="text-center fw-semibold">{{ $detail->physical_qty }}</td>
                    <td class="text-center"><span class="badge badge-soft-{{ $diff === 0 ? 'secondary' : ($diff > 0 ? 'success' : 'danger') }}">{{ $diff > 0 ? '+' : '' }}{{ $diff }}</span></td>
                    <td class="text-end small {{ (float) $detail->difference_value < 0 ? 'text-danger' : '' }}">{{ \App\Helpers\CurrencyHelper::format($detail->difference_value) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
