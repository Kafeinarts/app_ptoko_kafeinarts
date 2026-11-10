@extends('layouts.app')

@section('title', 'Voucher & Promo')
@section('page-title', 'Voucher & Promo')
@section('page-subtitle', 'Atur kode diskon persentase maupun nominal')
@section('page-key', 'promotions')

@section('page-actions')
    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#create-voucher"><i class="bi bi-plus-lg me-1"></i>Voucher Baru</button>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <p class="section-title mb-0">Daftar Voucher</p>
            <p class="section-subtitle">{{ $vouchers->total() }} voucher terdaftar</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Kode</th><th>Nama</th><th>Tipe</th><th class="text-end">Nilai</th><th class="text-end">Min. Belanja</th><th>Masa Berlaku</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            @forelse($vouchers as $voucher)
                @php($active = $voucher->is_active && now()->between($voucher->valid_from, $voucher->valid_until))
                <tr>
                    <td><span class="badge badge-soft-success fs-6">{{ $voucher->code }}</span></td>
                    <td class="small fw-semibold">{{ $voucher->name }}</td>
                    <td class="small">{{ $voucher->type === 'PERCENTAGE' ? 'Persentase' : 'Nominal' }}</td>
                    <td class="text-end small fw-semibold">{{ $voucher->type === 'PERCENTAGE' ? number_format((float) $voucher->value, 1, ',', '.').'%' : \App\Helpers\CurrencyHelper::format($voucher->value) }}</td>
                    <td class="text-end small">{{ \App\Helpers\CurrencyHelper::format($voucher->min_purchase) }}</td>
                    <td class="small">{{ $voucher->valid_from->format('d/m/Y') }} — {{ $voucher->valid_until->format('d/m/Y') }}</td>
                    <td>
                        @if(! $voucher->is_active)
                            <span class="badge badge-soft-secondary">Nonaktif</span>
                        @elseif($active)
                            <span class="badge badge-soft-success">Aktif</span>
                        @else
                            <span class="badge badge-soft-warning">Kedaluwarsa</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#edit-voucher-{{ $voucher->id }}"><i class="bi bi-pencil"></i></button>
                            <form method="POST" action="{{ route('promotions.destroy', $voucher) }}" data-confirm="Hapus voucher {{ $voucher->code }}?" data-confirm-text="Voucher tidak dapat dipakai lagi pada transaksi baru." data-confirm-button="Ya, hapus">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="empty-state"><i class="bi bi-ticket-perforated"></i>Belum ada voucher.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $vouchers->links() }}</div>
</div>

@include('promotion._form', ['id' => 'create-voucher', 'action' => route('promotions.store'), 'method' => 'POST', 'voucher' => null, 'title' => 'Voucher Baru'])
@foreach($vouchers as $voucher)
    @include('promotion._form', ['id' => 'edit-voucher-'.$voucher->id, 'action' => route('promotions.update', $voucher), 'method' => 'PUT', 'voucher' => $voucher, 'title' => 'Edit Voucher '.$voucher->code])
@endforeach
@endsection
