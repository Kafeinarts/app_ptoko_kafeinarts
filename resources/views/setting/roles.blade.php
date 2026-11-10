@extends('layouts.app')

@section('title', 'Role & Akses')
@section('page-title', 'Role & Akses')
@section('page-subtitle', 'Atur hak akses setiap role berdasarkan permission')
@section('page-key', 'settings-roles')

@section('content')
<div class="row g-3">
    @forelse($roles as $role)
        @php($rolePermissions = $role->permissions->pluck('id')->all())
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <div>
                        <p class="section-title mb-0">
                            <i class="bi bi-shield-lock me-1"></i>{{ $role->name }}
                            @if($role->name === 'Super Admin')
                                <span class="badge badge-soft-success ms-1">Akses penuh</span>
                            @endif
                        </p>
                        <p class="section-subtitle">{{ count($rolePermissions) }} permission aktif · {{ $role->users()->count() }} pengguna</p>
                    </div>
                </div>

                @if($role->name === 'Super Admin')
                    <div class="card-body">
                        <div class="alert alert-success mb-0">
                            <i class="bi bi-info-circle me-1"></i>Super Admin selalu memiliki akses ke seluruh fitur, sehingga permission tidak dapat dibatasi.
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('settings.roles.update', $role) }}">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="row g-4">
                                @foreach($permissions as $group => $items)
                                    <div class="col-md-6 col-xl-4">
                                        <div class="border rounded-3 h-100 p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="fw-semibold text-uppercase small text-secondary">{{ $group ?: 'Umum' }}</span>
                                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" data-toggle-checks="input[data-group='{{ $role->id }}-{{ Str::slug($group) }}']">Pilih semua</button>
                                            </div>
                                            @foreach($items as $permission)
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                        name="permissions[]"
                                                        value="{{ $permission->id }}"
                                                        id="perm-{{ $role->id }}-{{ $permission->id }}"
                                                        data-group="{{ $role->id }}-{{ Str::slug($group) }}"
                                                        @checked(in_array($permission->id, $rolePermissions, true))>
                                                    <label class="form-check-label small" for="perm-{{ $role->id }}-{{ $permission->id }}">
                                                        {{ $permission->name }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="card-footer bg-white d-flex justify-content-end">
                            <button class="btn btn-success" data-confirm="Simpan perubahan hak akses?" data-confirm-text="Permission role {{ $role->name }} akan diperbarui." data-confirm-button="Ya, simpan">
                                <i class="bi bi-check2 me-1"></i>Simpan Hak Akses
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card"><div class="card-body"><x-empty-state icon="bi bi-shield-lock" message="Belum ada role terdaftar." /></div></div>
        </div>
    @endforelse
</div>
@endsection
