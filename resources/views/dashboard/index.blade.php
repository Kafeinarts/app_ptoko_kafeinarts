@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Ringkasan '.($currentOutlet?->name ?? 'outlet').' · '.now()->translatedFormat('l, d F Y'))

@section('page-actions')
    @if($activeShift)
        <span class="badge rounded-pill badge-soft-success px-3 py-2"><span class="pulse-dot"></span>Shift aktif</span>
    @else
        <a href="{{ route('shifts.index') }}" class="btn btn-sm btn-outline-success"><i class="bi bi-clock me-1"></i>Buka Shift</a>
    @endif
    <a href="{{ route('pos.index') }}" class="btn btn-sm btn-success"><i class="bi bi-cash-coin me-1"></i>Buka Kasir</a>
@endsection

@php($paymentColors = ['#1b4332', '#40916c', '#d4a373', '#74c69d', '#95a5a6'])

@section('content')
<div class="row g-3 dash-grid mb-3">
    {{-- Hero: Penjualan hari ini --}}
    <div class="col-xl-8">
        <div class="hero-panel">
            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                <div>
                    <p class="hero-eyebrow">Ringkasan Penjualan · {{ $currentOutlet?->name ?? 'Outlet' }}</p>
                    <div class="hero-value">{{ \App\Helpers\CurrencyHelper::format($todaySales) }}</div>
                    <p class="hero-sub">Omset bersih hari ini · {{ now()->translatedFormat('l, d F Y') }}</p>
                </div>
                @if($activeShift)
                    <span class="hero-chip"><span class="pulse-dot"></span>Shift #{{ $activeShift->id }} aktif</span>
                @else
                    <span class="hero-chip hero-chip--warn"><i class="bi bi-clock-history"></i>Shift belum dibuka</span>
                @endif
            </div>

            <div class="hero-trend">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="hero-trend-label">Kinerja {{ request('days', 7) }} Hari Terakhir</span>
                    <span class="hero-trend-value">{{ \App\Helpers\CurrencyHelper::format(array_sum($series['sales'])) }}<small> total periode</small></span>
                </div>
                <div class="hero-sparkline">
                    <canvas data-chart="{{ json_encode([
                        'type' => 'line',
                        'data' => [
                            'labels' => $series['labels'],
                            'datasets' => [[
                                'label' => 'Penjualan',
                                'data' => $series['sales'],
                                'borderColor' => '#d4a373',
                                'backgroundColor' => 'rgba(212,163,115,.16)',
                                'fill' => true,
                                'pointRadius' => 0,
                                'pointHoverRadius' => 4,
                                'pointBackgroundColor' => '#f3d9b0',
                            ]],
                        ],
                        'options' => [
                            'plugins' => ['legend' => ['display' => false]],
                            'scales' => [
                                'x' => ['display' => false],
                                'y' => ['display' => false],
                            ],
                        ],
                    ]) }}"></canvas>
                </div>
            </div>

            <div class="hero-bottom">
                <div class="hero-stats">
                    <div>
                        <span>Transaksi</span>
                        <b>{{ number_format($todayTransactions, 0, ',', '.') }}</b>
                    </div>
                    <div>
                        <span>Rata-rata</span>
                        <b>{{ \App\Helpers\CurrencyHelper::format($summary['average_ticket']) }}</b>
                    </div>
                    <div>
                        <span>Laba Bersih</span>
                        <b>{{ \App\Helpers\CurrencyHelper::format($todayProfit) }}</b>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('pos.index') }}" class="btn btn-accent"><i class="bi bi-cash-coin me-1"></i>Buka Kasir</a>
                    <a href="{{ route('reports.sales') }}" class="btn btn-ghostlight"><i class="bi bi-graph-up me-1"></i>Laporan</a>
                </div>
            </div>
        </div>
    </div>

    {{-- Metode pembayaran --}}
    <div class="col-xl-4">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Metode Pembayaran</h2>
                    <p class="panel-sub">30 hari terakhir</p>
                </div>
                <i class="bi bi-wallet2 text-secondary"></i>
            </div>
            <div class="panel-body">
                @if(count($payments))
                    <div class="chart-fill chart-fill--doughnut">
                        <canvas data-chart="{{ json_encode([
                            'type' => 'doughnut',
                            'data' => [
                                'labels' => collect($payments)->pluck('method')->all(),
                                'datasets' => [[
                                    'data' => collect($payments)->pluck('total')->all(),
                                    'backgroundColor' => $paymentColors,
                                    'borderWidth' => 2,
                                    'borderColor' => '#ffffff',
                                    'hoverOffset' => 8,
                                ]],
                            ],
                            'options' => ['cutout' => '66%', 'plugins' => ['legend' => ['display' => false]]],
                        ]) }}"></canvas>
                    </div>
                    <div class="pay-legend mt-2">
                        @foreach($payments as $index => $payment)
                            <div class="pay-legend-row">
                                <span class="pay-dot" style="background:{{ $paymentColors[$index % count($paymentColors)] }}"></span>
                                <div>
                                    <div class="pay-method">{{ $payment['method'] }}</div>
                                    <div class="pay-count">{{ $payment['count'] }} transaksi</div>
                                </div>
                                <span class="pay-total">{{ \App\Helpers\CurrencyHelper::format($payment['total']) }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="panel-empty"><i class="bi bi-credit-card"></i>Belum ada pembayaran tercatat.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-3 dash-grid mb-3">
    {{-- Tren penjualan --}}
    <div class="col-xl-8">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Tren Penjualan</h2>
                    <p class="panel-sub">Omset per hari · {{ request('days', 7) }} hari terakhir</p>
                </div>
                <div class="btn-group btn-group-sm" role="group" aria-label="Rentang waktu">
                    <a href="{{ route('dashboard', ['days' => 7]) }}" class="btn btn-outline-success {{ request('days', 7) == 7 ? 'active' : '' }}">7D</a>
                    <a href="{{ route('dashboard', ['days' => 30]) }}" class="btn btn-outline-success {{ request('days') == 30 ? 'active' : '' }}">30D</a>
                </div>
            </div>
            <div class="panel-body">
                <div class="chart-fill">
                    <canvas data-chart="{{ json_encode([
                        'type' => 'line',
                        'data' => [
                            'labels' => $series['labels'],
                            'datasets' => [[
                                'label' => 'Penjualan',
                                'data' => $series['sales'],
                                'borderColor' => '#1b4332',
                                'backgroundColor' => 'rgba(64,145,108,.14)',
                                'fill' => true,
                                'pointRadius' => 3,
                                'pointBackgroundColor' => '#d4a373',
                                'pointBorderColor' => '#fff',
                                'pointBorderWidth' => 1.5,
                            ]],
                        ],
                        'options' => [
                            'plugins' => ['legend' => ['display' => false]],
                        ],
                    ]) }}"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Produk terlaris --}}
    <div class="col-xl-4">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Produk Terlaris</h2>
                    <p class="panel-sub">Top 5 berdasarkan qty terjual</p>
                </div>
                <i class="bi bi-fire text-danger"></i>
            </div>
            <div class="panel-body">
                @if(count($topProducts))
                    <div class="chart-fill">
                        <canvas data-chart="{{ json_encode([
                            'type' => 'bar',
                            'data' => [
                                'labels' => collect($topProducts)->pluck('name')->all(),
                                'datasets' => [[
                                    'label' => 'Qty',
                                    'data' => collect($topProducts)->pluck('qty')->all(),
                                    'backgroundColor' => ['#1b4332', '#2d6a4f', '#40916c', '#74c69d', '#d4a373'],
                                    'borderRadius' => 6,
                                    'maxBarThickness' => 26,
                                ]],
                            ],
                            'options' => ['indexAxis' => 'y', 'plugins' => ['legend' => ['display' => false]], 'scales' => ['x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]]],
                        ]) }}"></canvas>
                    </div>
                @else
                    <div class="panel-empty"><i class="bi bi-bar-chart"></i>Belum ada penjualan tercatat.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-3 dash-grid mb-3">
    {{-- Transaksi terbaru --}}
    <div class="col-xl-7">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title">Aktivitas Terbaru</h2>
                    <p class="panel-sub">Transaksi kasir terakhir</p>
                </div>
                <a href="{{ route('pos.history') }}" class="btn btn-sm btn-outline-success">Lihat semua</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Kasir</th>
                            <th>Metode</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($recentTransactions as $trx)
                        <tr>
                            <td>
                                <div class="fw-bold text-body-emphasis">{{ $trx->invoice_number }}</div>
                                <div class="text-secondary" style="font-size:.7rem">{{ $trx->transaction_date->format('d/m/Y · H:i') }}</div>
                            </td>
                            <td class="text-secondary">{{ $trx->user?->name }}</td>
                            <td><span class="badge badge-soft-secondary">{{ $trx->payments->first()?->payment_method ?? '—' }}</span></td>
                            <td class="text-end fw-bold" style="font-variant-numeric:tabular-nums">{{ \App\Helpers\CurrencyHelper::format($trx->grand_total) }}</td>
                            <td class="text-end">
                                @php($statusColor = ['PAID' => 'success', 'VOID' => 'danger', 'REFUND' => 'warning', 'PARTIAL_REFUND' => 'warning'][$trx->status] ?? 'secondary')
                                <span class="badge badge-soft-{{ $statusColor }}">{{ $trx->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><div class="panel-empty"><i class="bi bi-receipt"></i>Belum ada transaksi hari ini.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Stok minimum --}}
    <div class="col-xl-5">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2 class="panel-title"><i class="bi bi-exclamation-circle text-danger me-1"></i>Stok Minimum</h2>
                    <p class="panel-sub">{{ $lowStockCount }} produk di bawah batas minimum</p>
                </div>
                <a href="{{ route('inventory.stocks', ['filter' => 'low']) }}" class="btn btn-sm btn-outline-danger">Kelola</a>
            </div>
            <div class="panel-body pt-2">
                @if($lowStocks->count())
                    <ul class="stock-minmax">
                        @foreach($lowStocks as $stock)
                            <li class="stock-row">
                                <div class="stock-row-body">
                                    <div class="stock-name">{{ $stock->variant?->display_name }}</div>
                                    <div class="stock-cat">{{ $stock->variant?->product?->category?->name }}</div>
                                </div>
                                <span class="stock-min">Min {{ $stock->min_stock }}</span>
                                <span class="stock-qty">{{ $stock->quantity }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="panel-empty"><i class="bi bi-check2-circle" style="color:#1b6b44;opacity:.55"></i>Semua stok dalam kondisi aman.</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Quick actions strip --}}
<div class="row g-3">
    <div class="col-12">
        <div class="panel">
            <div class="panel-body py-2">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="quick-strip flex-grow-1">
                        @if(auth()->user()->hasPermission('pos.access'))
                            <a href="{{ route('pos.index') }}" class="quick-action">
                                <i class="bi bi-cash-coin"></i>
                                <span><b>Buka Kasir</b><small>Mulai transaksi baru</small></span>
                            </a>
                        @endif
                        @if(auth()->user()->hasPermission('master.products'))
                            <a href="{{ route('master.products.create') }}" class="quick-action">
                                <i class="bi bi-plus-circle"></i>
                                <span><b>Tambah Produk</b><small>Master data menu</small></span>
                            </a>
                        @endif
                        @if(auth()->user()->hasPermission('inventory.opname'))
                            <a href="{{ route('inventory.opnames.create') }}" class="quick-action">
                                <i class="bi bi-clipboard-check"></i>
                                <span><b>Stock Opname</b><small>Cek fisik barang</small></span>
                            </a>
                        @endif
                        @if(auth()->user()->hasPermission('report.sales'))
                            <a href="{{ route('reports.sales') }}" class="quick-action">
                                <i class="bi bi-graph-up"></i>
                                <span><b>Laporan</b><small>Analisis penjualan</small></span>
                            </a>
                        @endif
                    </div>
                    <div class="totals-inline">
                        <div>
                            <span>Varian</span>
                            <b>{{ number_format($totalProducts, 0, ',', '.') }}</b>
                        </div>
                        <div>
                            <span>Pelanggan</span>
                            <b>{{ number_format($totalCustomers, 0, ',', '.') }}</b>
                        </div>
                        <div>
                            <span>Shift Aktif</span>
                            <b>{{ number_format($openShifts, 0, ',', '.') }}</b>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
