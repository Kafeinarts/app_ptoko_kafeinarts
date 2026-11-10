@php
    $isEdit = (bool) $user;
    $selectedRole = old('role_id', $user?->role_id);
    $selectedOutlet = old('outlet_id', $user?->outlet_id);
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="user-name-{{ $user?->id ?? 'create' }}">Nama <span class="text-danger">*</span></label>
        <input type="text" name="name" id="user-name-{{ $user?->id ?? 'create' }}" class="form-control" value="{{ old('name', $user?->name) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="user-email-{{ $user?->id ?? 'create' }}">Email <span class="text-danger">*</span></label>
        <input type="email" name="email" id="user-email-{{ $user?->id ?? 'create' }}" class="form-control" value="{{ old('email', $user?->email) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="user-phone-{{ $user?->id ?? 'create' }}">Telepon</label>
        <input type="text" name="phone" id="user-phone-{{ $user?->id ?? 'create' }}" class="form-control" value="{{ old('phone', $user?->phone) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="user-role-{{ $user?->id ?? 'create' }}">Role <span class="text-danger">*</span></label>
        <select name="role_id" id="user-role-{{ $user?->id ?? 'create' }}" class="form-select" required>
            <option value="">Pilih role</option>
            @foreach($roles as $role)
                <option value="{{ $role->id }}" @selected((string) $selectedRole === (string) $role->id)>{{ $role->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="user-outlet-{{ $user?->id ?? 'create' }}">Outlet</label>
        <select name="outlet_id" id="user-outlet-{{ $user?->id ?? 'create' }}" class="form-select">
            <option value="">Semua outlet</option>
            @foreach($outlets as $outlet)
                <option value="{{ $outlet->id }}" @selected((string) $selectedOutlet === (string) $outlet->id)>{{ $outlet->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="user-password-{{ $user?->id ?? 'create' }}">
            Password @if(! $isEdit)<span class="text-danger">*</span>@endif
        </label>
        <input type="password" name="password" id="user-password-{{ $user?->id ?? 'create' }}" class="form-control" autocomplete="new-password" @required(! $isEdit)>
        @if($isEdit)
            <div class="form-text">Kosongkan bila tidak ingin mengganti password.</div>
        @endif
    </div>
    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="user-active-{{ $user?->id ?? 'create' }}" value="1" @checked(old('is_active', $user?->is_active ?? true))>
            <label class="form-check-label" for="user-active-{{ $user?->id ?? 'create' }}">Akun aktif (dapat login)</label>
        </div>
    </div>
</div>
