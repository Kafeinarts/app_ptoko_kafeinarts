@php($isEdit = (bool) $outlet)
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label" for="outlet-code-{{ $outlet?->id ?? 'create' }}">Kode <span class="text-danger">*</span></label>
        <input type="text" name="code" id="outlet-code-{{ $outlet?->id ?? 'create' }}" class="form-control text-uppercase" value="{{ old('code', $outlet?->code) }}" maxlength="20" required>
    </div>
    <div class="col-md-8">
        <label class="form-label" for="outlet-name-{{ $outlet?->id ?? 'create' }}">Nama Outlet <span class="text-danger">*</span></label>
        <input type="text" name="name" id="outlet-name-{{ $outlet?->id ?? 'create' }}" class="form-control" value="{{ old('name', $outlet?->name) }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="outlet-phone-{{ $outlet?->id ?? 'create' }}">Telepon</label>
        <input type="text" name="phone" id="outlet-phone-{{ $outlet?->id ?? 'create' }}" class="form-control" value="{{ old('phone', $outlet?->phone) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="outlet-email-{{ $outlet?->id ?? 'create' }}">Email</label>
        <input type="email" name="email" id="outlet-email-{{ $outlet?->id ?? 'create' }}" class="form-control" value="{{ old('email', $outlet?->email) }}">
    </div>
    <div class="col-12">
        <label class="form-label" for="outlet-manager-{{ $outlet?->id ?? 'create' }}">Penanggung Jawab (PJ)</label>
        <select name="manager_id" id="outlet-manager-{{ $outlet?->id ?? 'create' }}" class="form-select">
            <option value="">— Belum ditunjuk —</option>
            @foreach($managers as $manager)
                <option value="{{ $manager->id }}" @selected((string) old('manager_id', $outlet?->manager_id) === (string) $manager->id)>
                    {{ $manager->name }}{{ $manager->role ? ' — '.$manager->role->name : '' }}
                </option>
            @endforeach
        </select>
        <div class="form-text">PJ adalah pengguna yang bertanggung jawab atas operasional outlet ini.</div>
    </div>
    <div class="col-12">
        <label class="form-label" for="outlet-address-{{ $outlet?->id ?? 'create' }}">Alamat</label>
        <textarea name="address" id="outlet-address-{{ $outlet?->id ?? 'create' }}" class="form-control" rows="2">{{ old('address', $outlet?->address) }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label" for="outlet-footer-{{ $outlet?->id ?? 'create' }}">Footer Struk</label>
        <input type="text" name="receipt_footer" id="outlet-footer-{{ $outlet?->id ?? 'create' }}" class="form-control" value="{{ old('receipt_footer', $outlet?->receipt_footer) }}" placeholder="Terima kasih atas kunjungan Anda">
    </div>
    <div class="col-12">
        <input type="hidden" name="is_active" value="0">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="outlet-active-{{ $outlet?->id ?? 'create' }}" value="1" @checked(old('is_active', $outlet?->is_active ?? true))>
            <label class="form-check-label" for="outlet-active-{{ $outlet?->id ?? 'create' }}">Outlet aktif</label>
        </div>
    </div>
</div>
