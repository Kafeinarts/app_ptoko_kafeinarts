@extends('layouts.app')

@section('title', 'Pengguna')
@section('page-title', 'Pengguna')
@section('page-subtitle', 'Kelola akun, role, dan penempatan outlet pengguna')
@section('page-key', 'settings-users')

@section('page-actions')
    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#create-user">
        <i class="bi bi-person-plus me-1"></i>Tambah Pengguna
    </button>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div>
            <p class="section-title mb-0"><i class="bi bi-person-gear me-1"></i>Daftar Pengguna</p>
            <p class="section-subtitle">{{ $users->total() }} akun terdaftar</p>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:50px">#</th>
                    <th>Nama</th>
                    <th>Kontak</th>
                    <th>Role</th>
                    <th>Outlet</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td class="text-secondary small">{{ $users->firstItem() + $loop->index }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-circle">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            <div>
                                <div class="fw-semibold small">{{ $user->name }}</div>
                                <div class="text-secondary" style="font-size:.72rem">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="small">{{ $user->phone ?: '—' }}</td>
                    <td><span class="badge badge-soft-info">{{ $user->role?->name ?? '—' }}</span></td>
                    <td class="small">{{ $user->outlet?->name ?? 'Semua outlet' }}</td>
                    <td>
                        <span class="badge badge-soft-{{ $user->is_active ? 'success' : 'secondary' }}">
                            {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#edit-user-{{ $user->id }}" title="Edit"><i class="bi bi-pencil"></i></button>
                            @if(auth()->id() !== $user->id)
                                <form method="POST" action="{{ route('settings.users.destroy', $user) }}" data-confirm="Hapus pengguna {{ $user->name }}?" data-confirm-text="Akun yang dihapus tidak dapat dikembalikan." data-confirm-button="Ya, hapus">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state"><i class="bi bi-people"></i>Belum ada pengguna terdaftar.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $users->links() }}</div>
</div>

<div class="modal fade" id="create-user" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" action="{{ route('settings.users.store') }}" class="modal-content border-0">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Tambah Pengguna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                @include('setting._user-fields', ['user' => null])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-success"><i class="bi bi-check2 me-1"></i>Simpan</button>
            </div>
        </form>
    </div>
</div>

@foreach($users as $user)
    <div class="modal fade" id="edit-user-{{ $user->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form method="POST" action="{{ route('settings.users.update', $user) }}" class="modal-content border-0">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Pengguna — {{ $user->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('setting._user-fields', ['user' => $user])
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
