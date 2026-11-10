@extends('layouts.app')

@section('title', 'Draf Harga')
@section('page-title', 'Draf Harga Produk')
@section('page-subtitle', 'Rekap harga modal, harga jual, dan margin per varian')
@section('page-key', 'price-draft')

@section('page-actions')
    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Cetak</button>
    <a href="{{ route('master.products.index') }}" class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <p class="section-title mb-0">Draf Harga</p>
            <p class="section-subtitle">Outlet {{ $currentOutlet?->name }} · diperbarui {{ now()->format('d/m/Y H:i') }}</p>
        </div>
        <span class="badge badge-soft-secondary">{{ $products->count() }} produk</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Satuan</th>
                    <th>Varian</th>
                    <th class="text-end">Harga Modal</th>
                    <th class="text-end">Harga Jual</th>
                    <th class="text-end">Margin</th>
                    <th class="text-end">Margin %</th>
                </tr>
            </thead>
            <tbody>
            @forelse($products as $product)
                @foreach($product->variants as $variant)
                    @php
                        $price = $variant->prices->first();
                        $cost = (float) ($price?->cost_price ?? 0);
                        $selling = (float) ($price?->selling_price ?? 0);
                        $margin = $selling - $cost;
                        $percent = $selling > 0 ? ($margin / $selling) * 100 : 0;
                    @endphp
                    <tr>
                        <td class="small fw-semibold">{{ $product->name }}</td>
                        <td class="small">{{ $product->category?->name ?? '-' }}</td>
                        <td class="small">{{ $product->unit?->short_name ?? '-' }}</td>
                        <td class="small">{{ $variant->variant_name }}</td>
                        <td class="text-end small">{{ \App\Helpers\CurrencyHelper::format($cost) }}</td>
                        <td class="text-end small fw-semibold">{{ \App\Helpers\CurrencyHelper::format($selling) }}</td>
                        <td class="text-end small {{ $margin < 0 ? 'text-danger' : 'text-success' }}">{{ \App\Helpers\CurrencyHelper::format($margin) }}</td>
                        <td class="text-end small">
                            <span class="badge badge-soft-{{ $percent < 0 ? 'danger' : ($percent < 20 ? 'warning' : 'success') }}">{{ number_format($percent, 1, ',', '.') }}%</span>
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="8"><div class="empty-state"><i class="bi bi-tags"></i>Belum ada produk.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
