@extends('layouts.auth')

@section('title', 'Masuk')

@section('content')
<main class="auth-shell">
    <section class="auth-brand-panel">
        <div class="auth-brand-content">
            <img src="{{ asset('assets/icons/logo-light.svg') }}" alt="Kafeinarts" class="mb-4" width="64" height="64">
            <h1 class="display-5 fw-bold mb-3">Kasir cepat.<br>Stok rapi.<br>Laporan jelas.</h1>
            <p class="lead opacity-75 mb-4">Satu platform untuk Point of Sale, inventaris multi-outlet, pembelian, dan laporan keuangan kafe Anda.</p>
            <div class="d-flex flex-wrap gap-3">
                <span class="auth-chip"><i class="bi bi-cash-coin"></i> POS Offline</span>
                <span class="auth-chip"><i class="bi bi-box-seam"></i> Stok Real-time</span>
                <span class="auth-chip"><i class="bi bi-graph-up"></i> Analitik</span>
            </div>
        </div>
        <div class="auth-orbs"><span></span><span></span><span></span></div>
    </section>

    <section class="auth-form-panel">
        <div class="auth-form-wrap">
            <div class="mb-4">
                <span class="badge rounded-pill text-bg-success-subtle text-success-emphasis mb-2">Kafeinarts POS</span>
                <h2 class="fw-bold mb-1">Selamat datang 👋</h2>
                <p class="text-secondary mb-0">Masuk untuk membuka kasir dan mengelola outlet Anda.</p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger py-2 small">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="vstack gap-3" autocomplete="on">
                @csrf
                <div>
                    <label class="form-label fw-semibold" for="email">Email</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
                        <input type="email" id="email" name="email" value="{{ old('email', 'admin@kafeinarts.test') }}" class="form-control" placeholder="nama@kafeinarts.com" required autofocus>
                    </div>
                </div>
                <div>
                    <label class="form-label fw-semibold" for="password">Password</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                        <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                        <button class="btn btn-outline-secondary" type="button" data-toggle-password="password" aria-label="Lihat password"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
                        <label class="form-check-label small" for="remember">Ingat saya</label>
                    </div>
                    <span class="small text-secondary">Butuh bantuan? Hubungi admin</span>
                </div>
                <button class="btn btn-success btn-lg w-100 fw-semibold" type="submit">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Dashboard
                </button>
            </form>

            <div class="auth-demo mt-4">
                <div class="d-flex align-items-center gap-2 mb-2"><i class="bi bi-info-circle"></i><span class="fw-semibold">Akun demo</span></div>
                <div class="row g-2 small">
                    <div class="col-6"><span class="text-secondary d-block">Super Admin</span>admin@kafeinarts.test</div>
                    <div class="col-6"><span class="text-secondary d-block">Kasir</span>kasir@kafeinarts.test</div>
                    <div class="col-12"><span class="text-secondary">Password semua akun:</span> <code>password</code></div>
                </div>
            </div>
            <p class="text-center text-secondary small mt-4 mb-0">© {{ date('Y') }} Kafeinarts POS · Dibuat untuk operasional kafe modern.</p>
        </div>
    </section>
</main>
@endsection
