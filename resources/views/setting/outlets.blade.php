@extends('layouts.app')

@section('title', 'Outlet')
@section('page-title', 'Outlet')
@section('page-subtitle', 'Kelola cabang dan identitas struk setiap outlet')
@section('page-key', 'settings-outlets')

@section('page-actions')
    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#create-outlet">
        <i class="bi bi-shop me-1"></i>Tambah Outlet
    </button>
@endsection

@section('content')
<div class="row g-3">
    @forelse($outlets as $outlet)
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <p class="section-title mb-0">{{ $outlet->name }}</p>
                            <span class="badge badge-soft-secondary">{{ $outlet->code }}</span>
                        </div>
                        <span class="badge badge-soft-{{ $outlet->is_active ? 'success' : 'secondary' }}">
                            {{ $outlet->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>
                    <ul class="list-unstyled small text-secondary mb-3">
                        <li class="mb-1"><i class="bi bi-geo-alt me-1"></i>{{ $outlet->address ?: 'Alamat belum diisi' }}</li>
                        <li class="mb-1"><i class="bi bi-telephone me-1"></i>{{ $outlet->phone ?: '—' }}</li>
                        <li class="mb-1"><i class="bi bi-envelope me-1"></i>{{ $outlet->email ?: '—' }}</li>
                        <li><i class="bi bi-people me-1"></i>{{ $outlet->users_count }} pengguna</li>
                    </ul>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" data-bs-toggle="modal" data-bs-target="#edit-outlet-{{ $outlet->id }}">
                            <i class="bi bi-pencil me-1"></i>Edit
                        </button>
                        @if($outlets->count() > 1 && $outlet->is_active)
                            <form method="POST" action="{{ route('settings.outlets.destroy', $outlet) }}" class="flex-fill" data-confirm="Nonaktifkan outlet {{ $outlet->name }}?" data-confirm-text="Outlet akan dinonaktifkan dan tidak dapat dipilih pada transaksi baru." data-confirm-button="Ya, nonaktifkan">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-slash-circle me-1"></i>Nonaktifkan</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card"><div class="card-body"><div class="empty-state"><i class="bi bi-shop"></i>Belum ada outlet terdaftar.</div></div></div>
        </div>
    @endforelse
</div>

<div class="modal fade" id="create-outlet" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" action="{{ route('settings.outlets.store') }}" class="modal-content border-0">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Tambah Outlet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @include('setting._outlet-fields', ['outlet' => null])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-success"><i class="bi bi-check2 me-1"></i>Simpan</button>
            </div>
        </form>
    </div>
</div>

@foreach($outlets as $outlet)
    <div class="modal fade" id="edit-outlet-{{ $outlet->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form method="POST" action="{{ route('settings.outlets.update', $outlet) }}" class="modal-content border-0">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Outlet — {{ $outlet->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('setting._outlet-fields', ['outlet' => $outlet])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-success"><i class="bi bi-check2 me-1"></i>Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
@endforeach
@endsection
