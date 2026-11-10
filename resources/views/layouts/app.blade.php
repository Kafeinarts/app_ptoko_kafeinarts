<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1b4332">
    <meta name="description" content="Kafeinarts POS — Point of Sale, Inventaris, dan Laporan Keuangan Kafe.">
    <title>@yield('title', 'Dashboard') · Kafeinarts POS</title>
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('assets/icons/favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('assets/icons/icon-192.png') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/adminlte.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vendor/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}?v=1.4.0">
    @stack('styles')
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-white border-bottom">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Buka menu">
                        <i class="bi bi-list fs-4"></i>
                    </a>
                </li>
                <li class="nav-item d-none d-md-block">
                    <a href="{{ route('pos.index') }}" class="nav-link">Kasir</a>
                </li>
                <li class="nav-item d-none d-md-block">
                    <a href="{{ route('pos.history') }}" class="nav-link">Riwayat</a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item d-none d-lg-block me-2">
                    <form action="{{ route('outlets.switch') }}" method="POST" class="d-flex align-items-center gap-2">
                        @csrf
                        <span class="text-secondary small">Outlet</span>
                        <select name="outlet_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:205px">
                            @foreach($outlets as $outlet)
                                <option value="{{ $outlet->id }}" @selected($currentOutlet?->id === $outlet->id)>{{ $outlet->name }}</option>
                            @endforeach
                        </select>
                    </form>
                </li>
                <li class="nav-item">
                    <a href="{{ route('inventory.stocks', ['filter' => 'low']) }}" class="nav-link position-relative" title="Stok minimum">
                        <i class="bi bi-bell fs-5"></i>
                        @if($sidebarLowStock > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $sidebarLowStock }}</span>
                        @endif
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" href="#">
                        <span class="avatar-circle">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                        <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><span class="dropdown-item-text small text-secondary">{{ auth()->user()->role?->name }} · {{ $currentOutlet?->name }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('shifts.index') }}"><i class="bi bi-clock-history me-2"></i>Shift Kasir</a></li>
                        @if(auth()->user()->hasPermission('settings.general'))
                            <li><a class="dropdown-item" href="{{ route('settings.general') }}"><i class="bi bi-gear me-2"></i>Pengaturan</a></li>
                        @endif
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST" data-confirm="Keluar dari aplikasi?" data-confirm-text="Sesi Anda akan diakhiri.">
                                @csrf
                                <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>

    <aside class="app-sidebar bg-dark text-white" data-bs-theme="dark">
        <div class="sidebar-brand">
            <a href="{{ route('dashboard') }}" class="brand-link d-flex align-items-center gap-2">
                <img src="{{ asset('assets/icons/logo-mark.svg') }}" alt="Kafeinarts" width="34" height="34">
                <span class="brand-text fw-bold">Kafeinarts<small class="d-block fw-normal text-secondary" style="font-size:.65rem">POS & INVENTORY</small></span>
            </a>
        </div>
        <div class="sidebar-wrapper">
            <nav class="mt-2">
                <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="navigation">
                    @php($u = auth()->user())
                    <li class="nav-header">UTAMA</li>
                    @if($u->hasPermission('dashboard.view'))
                        <li class="nav-item">
                            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-speedometer2"></i><p>Dashboard</p>
                            </a>
                        </li>
                    @endif
                    @if($u->hasPermission('pos.access'))
                        <li class="nav-item">
                            <a href="{{ route('pos.index') }}" class="nav-link {{ request()->routeIs('pos.index') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-cash-coin"></i><p>Kasir / POS</p>
                            </a>
                        </li>
                    @endif
                    @if($u->hasPermission('pos.history'))
                        <li class="nav-item">
                            <a href="{{ route('pos.history') }}" class="nav-link {{ request()->routeIs('pos.history*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-receipt-cutoff"></i><p>Riwayat Transaksi</p>
                            </a>
                        </li>
                    @endif
                    @if($u->hasPermission('shift.manage'))
                        <li class="nav-item">
                            <a href="{{ route('shifts.index') }}" class="nav-link {{ request()->routeIs('shifts.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-clock-history"></i><p>Shift & Kas</p>
                            </a>
                        </li>
                    @endif

                    @if($u->hasPermission('master.products') || $u->hasPermission('master.customers') || $u->hasPermission('master.suppliers'))
                        <li class="nav-header">MASTER DATA</li>
                        @if($u->hasPermission('master.products'))
                            <li class="nav-item {{ request()->routeIs('master.products.*') ? 'menu-open' : '' }}">
                                <a href="#" class="nav-link {{ request()->routeIs('master.products.*') ? 'active' : '' }}">
                                    <i class="nav-icon bi bi-cup-hot"></i><p>Produk <i class="nav-arrow bi bi-chevron-right"></i></p>
                                </a>
                                <ul class="nav nav-treeview">
                                    <li class="nav-item"><a href="{{ route('master.products.index') }}" class="nav-link {{ request()->routeIs('master.products.index') ? 'active' : '' }}"><i class="nav-icon bi bi-circle"></i><p>Daftar Produk</p></a></li>
                                    <li class="nav-item"><a href="{{ route('master.products.create') }}" class="nav-link {{ request()->routeIs('master.products.create') ? 'active' : '' }}"><i class="nav-icon bi bi-circle"></i><p>Tambah Produk</p></a></li>
                                    <li class="nav-item"><a href="{{ route('master.products.price-draft') }}" class="nav-link {{ request()->routeIs('master.products.price-draft') ? 'active' : '' }}"><i class="nav-icon bi bi-circle"></i><p>Draf Harga</p></a></li>
                                </ul>
                            </li>
                        @endif
                        @if($u->hasPermission('master.categories'))
                            <li class="nav-item"><a href="{{ route('master.categories.index') }}" class="nav-link {{ request()->routeIs('master.categories.*') ? 'active' : '' }}"><i class="nav-icon bi bi-tags"></i><p>Kategori</p></a></li>
                        @endif
                        @if($u->hasPermission('master.brands'))
                            <li class="nav-item"><a href="{{ route('master.brands.index') }}" class="nav-link {{ request()->routeIs('master.brands.*') ? 'active' : '' }}"><i class="nav-icon bi bi-award"></i><p>Brand</p></a></li>
                        @endif
                        @if($u->hasPermission('master.units'))
                            <li class="nav-item"><a href="{{ route('master.units.index') }}" class="nav-link {{ request()->routeIs('master.units.*') ? 'active' : '' }}"><i class="nav-icon bi bi-rulers"></i><p>Satuan</p></a></li>
                        @endif
                        @if($u->hasPermission('master.modifiers'))
                            <li class="nav-item"><a href="{{ route('master.modifiers.index') }}" class="nav-link {{ request()->routeIs('master.modifiers.*') ? 'active' : '' }}"><i class="nav-icon bi bi-sliders"></i><p>Modifier</p></a></li>
                        @endif
                        @if($u->hasPermission('master.customers'))
                            <li class="nav-item"><a href="{{ route('master.customers.index') }}" class="nav-link {{ request()->routeIs('master.customers.*') ? 'active' : '' }}"><i class="nav-icon bi bi-people"></i><p>Pelanggan</p></a></li>
                        @endif
                        @if($u->hasPermission('master.suppliers'))
                            <li class="nav-item"><a href="{{ route('master.suppliers.index') }}" class="nav-link {{ request()->routeIs('master.suppliers.*') ? 'active' : '' }}"><i class="nav-icon bi bi-truck"></i><p>Supplier</p></a></li>
                        @endif
                    @endif

                    @if($u->hasPermission('inventory.view'))
                        <li class="nav-header">INVENTARIS</li>
                        <li class="nav-item {{ request()->routeIs('inventory.*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-box-seam"></i><p>Gudang <i class="nav-arrow bi bi-chevron-right"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item"><a href="{{ route('inventory.stocks') }}" class="nav-link {{ request()->routeIs('inventory.stocks') ? 'active' : '' }}"><i class="nav-icon bi bi-circle"></i><p>Stok Barang</p></a></li>
                                @if($u->hasPermission('inventory.adjust'))
                                    <li class="nav-item"><a href="{{ route('inventory.stocks.create') }}" class="nav-link {{ request()->routeIs('inventory.stocks.create') ? 'active' : '' }}"><i class="nav-icon bi bi-circle"></i><p>Mutasi Stok</p></a></li>
                                @endif
                                @if($u->hasPermission('inventory.opname'))
                                    <li class="nav-item"><a href="{{ route('inventory.opnames.index') }}" class="nav-link {{ request()->routeIs('inventory.opnames.*') ? 'active' : '' }}"><i class="nav-icon bi bi-circle"></i><p>Stock Opname</p></a></li>
                                @endif
                                @if($u->hasPermission('inventory.transfer'))
                                    <li class="nav-item"><a href="{{ route('inventory.transfers.index') }}" class="nav-link {{ request()->routeIs('inventory.transfers.*') ? 'active' : '' }}"><i class="nav-icon bi bi-circle"></i><p>Transfer Cabang</p></a></li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    @if($u->hasPermission('purchasing.view'))
                        <li class="nav-header">PEMBELIAN</li>
                        <li class="nav-item"><a href="{{ route('purchasing.index') }}" class="nav-link {{ request()->routeIs('purchasing.*') ? 'active' : '' }}"><i class="nav-icon bi bi-cart-plus"></i><p>Purchase Order</p></a></li>
                    @endif

                    @if($u->hasPermission('promotion.manage'))
                        <li class="nav-item"><a href="{{ route('promotions.index') }}" class="nav-link {{ request()->routeIs('promotions.*') ? 'active' : '' }}"><i class="nav-icon bi bi-ticket-perforated"></i><p>Voucher & Promo</p></a></li>
                    @endif

                    @if($u->hasPermission('finance.view'))
                        <li class="nav-header">KEUANGAN</li>
                        <li class="nav-item"><a href="{{ route('finance.expenses') }}" class="nav-link {{ request()->routeIs('finance.expenses') ? 'active' : '' }}"><i class="nav-icon bi bi-arrow-up-circle"></i><p>Pengeluaran</p></a></li>
                        <li class="nav-item"><a href="{{ route('finance.incomes') }}" class="nav-link {{ request()->routeIs('finance.incomes') ? 'active' : '' }}"><i class="nav-icon bi bi-arrow-down-circle"></i><p>Pemasukan</p></a></li>
                    @endif

                    @if($u->hasPermission('report.sales') || $u->hasPermission('report.finance'))
                        <li class="nav-header">LAPORAN</li>
                        @if($u->hasPermission('report.sales'))
                            <li class="nav-item"><a href="{{ route('reports.sales') }}" class="nav-link {{ request()->routeIs('reports.sales') ? 'active' : '' }}"><i class="nav-icon bi bi-graph-up-arrow"></i><p>Laporan Penjualan</p></a></li>
                        @endif
                        @if($u->hasPermission('report.finance'))
                            <li class="nav-item"><a href="{{ route('reports.finance') }}" class="nav-link {{ request()->routeIs('reports.finance') ? 'active' : '' }}"><i class="nav-icon bi bi-wallet2"></i><p>Laba Rugi</p></a></li>
                        @endif
                    @endif

                    @if($u->hasPermission('settings.users') || $u->hasPermission('settings.general'))
                        <li class="nav-header">PENGATURAN</li>
                        @if($u->hasPermission('settings.users'))
                            <li class="nav-item"><a href="{{ route('settings.users') }}" class="nav-link {{ request()->routeIs('settings.users') ? 'active' : '' }}"><i class="nav-icon bi bi-person-gear"></i><p>Pengguna</p></a></li>
                        @endif
                        @if($u->hasPermission('settings.roles'))
                            <li class="nav-item"><a href="{{ route('settings.roles') }}" class="nav-link {{ request()->routeIs('settings.roles') ? 'active' : '' }}"><i class="nav-icon bi bi-shield-lock"></i><p>Role & Akses</p></a></li>
                        @endif
                        @if($u->hasPermission('settings.outlets'))
                            <li class="nav-item"><a href="{{ route('settings.outlets') }}" class="nav-link {{ request()->routeIs('settings.outlets') ? 'active' : '' }}"><i class="nav-icon bi bi-shop"></i><p>Outlet</p></a></li>
                        @endif
                        @if($u->hasPermission('settings.general'))
                            <li class="nav-item"><a href="{{ route('settings.general') }}" class="nav-link {{ request()->routeIs('settings.general') ? 'active' : '' }}"><i class="nav-icon bi bi-gear"></i><p>Umum & Printer</p></a></li>
                        @endif
                        @if($u->hasPermission('settings.logs'))
                            <li class="nav-item"><a href="{{ route('settings.logs') }}" class="nav-link {{ request()->routeIs('settings.logs') ? 'active' : '' }}"><i class="nav-icon bi bi-journal-text"></i><p>Audit Log</p></a></li>
                        @endif
                    @endif
                </ul>
            </nav>
        </div>
        <div class="sidebar-footer d-none d-lg-flex align-items-center gap-2 px-3 py-2 small text-secondary">
            <span class="pulse-dot"></span> Sistem POS aktif
        </div>
    </aside>

    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h1 class="page-title mb-0">@yield('page-title', View::getSection('title', 'Dashboard'))</h1>
                        <p class="text-secondary small mb-0">@yield('page-subtitle', now()->translatedFormat('l, d F Y'))</p>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex justify-content-sm-end align-items-center gap-2 mt-2 mt-sm-0">
                            @yield('page-actions')
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid" id="spa-content" data-page="@yield('page-key', request()->route()?->getName())">
                @yield('content')
            </div>
        </div>
    </main>

    <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">v1.4.0</div>
        <strong>Kafeinarts POS</strong> · Point of Sale & Inventory
    </footer>
</div>

<div id="spa-progress" aria-hidden="true"></div>

<script>
    window.__app = {
        baseUrl: @json(url('/')),
        userId: @json(auth()->id()),
        activeShift: @json(auth()->user()->activeShift()?->id),
        flash: {
            success: @json(session('swal_success')),
            error: @json(session('swal_error')),
            validation: @json($errors->any() ? $errors->first() : null)
        },
        settings: {
            paper: @json(\App\Models\SystemSetting::get('receipt_paper', '58')),
            autoPrint: @json((bool) \App\Models\SystemSetting::get('auto_print_receipt', false)),
            tax: @json((float) \App\Models\SystemSetting::get('tax_percentage', 0))
        }
    };
</script>
<script src="{{ asset('assets/vendor/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/vendor/adminlte.min.js') }}"></script>
<script src="{{ asset('assets/vendor/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('assets/vendor/chart.umd.min.js') }}"></script>
<script src="{{ asset('assets/js/app.js') }}?v=1.4.0"></script>
@stack('scripts')
</body>
</html>
