@extends('layouts.app')

@section('title', 'Laporan Penjualan')
@section('page-title', 'Laporan Penjualan')
@section('page-subtitle', 'Analisis omzet, produk, dan metode pembayaran')
@section('page-key', 'report-sales')

@section('page-actions')
    @if(auth()->user()->hasPermission('report.export'))
        <a href="{{ route('reports.sales.pdf', ['from' => $from, 'to' => $to]) }}" target="_blank" class="btn btn-sm btn-outline-danger" data-no-spa><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
        <a href="{{ route('reports.sales.excel', ['from' => $from, 'to' => $to]) }}" class="btn btn-sm btn-outline-success" data-no-spa><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
    @endif
    <a href="{{ route('reports.finance', ['from' => $from, 'to' => $to]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-wallet2 me-1"></i>Laba Rugi</a>
@endsection

@section('content')
<form method="GET" class="card mb-3" data-spa-form>
    <div class="card-body d-flex flex-wrap gap-2 align-items-end">
        <div>
            <label class="form-label">Dari Tanggal</label>
            <input type="date" name="from" class="form-control" value="{{ $from }}">
        </div>
        <div>
            <label class="form-label">Sampai Tanggal</label>
            <input type="date" name="to" class="form-control" value="{{ $to }}">
        </div>
        <button class="btn btn-success"><i class="bi bi-funnel me-1"></i>Terapkan</button>
        <a href="{{ route('reports.sales') }}" class="btn btn-light"><i class="bi bi-arrow-counterclockwise"></i></a>
        <div class="ms-auto text-secondary small">
            <i class="bi bi-calendar3 me-1"></i>{{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}
        </div>
    </div>
</form>

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Omzet Bersih</div>
            <div class="stat-value">{{ \App\Helpers\CurrencyHelper::format($summary['net_sales']) }}</div>
            <div class="stat-hint">{{ number_format($summary['transactions'], 0, ',', '.') }} transaksi</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Rata-rata / Nota</div>
            <div class="stat-value">{{ \App\Helpers\CurrencyHelper::format($summary['average_ticket']) }}</div>
            <div class="stat-hint">Average ticket</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Total Diskon</div>
            <div class="stat-value text-danger">{{ \App\Helpers\CurrencyHelper::format($summary['discount']) }}</div>
            <div class="stat-hint">Voucher & diskon manual</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Laba Kotor</div>
            <div class="stat-value text-success">{{ \App\Helpers\CurrencyHelper::format($summary['gross_profit']) }}</div>
            <div class="stat-hint">Omzet − HPP {{ \App\Helpers\CurrencyHelper::format($summary['cogs']) }}</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <p class="section-title mb-0">Tren Penjualan</p>
                <p class="section-subtitle">30 hari terakhir</p>
            </div>
            <div class="card-body">
                <div class="chart-wrap">
                    <canvas data-chart="{{ json_encode([
                        'type' => 'line',
                        'data' => [
                            'labels' => $series['labels'],
                            'datasets' => [[
                                'label' => 'Omzet',
                                'data' => $series['sales'],
                                'borderColor' => '#2d6a4f',
                                'backgroundColor' => 'rgba(64,145,108,.12)',
                                'fill' => true,
                                'pointBackgroundColor' => '#d4a373',
                            ]],
                        ],
                        'options' => ['plugins' => ['legend' => ['display' => false]]],
                    ]) }}"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <p class="section-title mb-0">Metode Pembayaran</p>
                <p class="section-subtitle">Periode terpilih</p>
            </div>
            <div class="card-body">
                @if(count($payments))
                    <div class="chart-wrap sm">
                        <canvas data-chart="{{ json_encode([
                            'type' => 'doughnut',
                            'data' => [
                                'labels' => collect($payments)->pluck('method')->all(),
                                'datasets' => [[
                                    'data' => collect($payments)->pluck('total')->all(),
                                    'backgroundColor' => ['#2d6a4f', '#40916c', '#d4a373', '#74c69d', '#95a5a6'],
                                    'borderWidth' => 0,
                                ]],
                            ],
                            'options' => ['cutout' => '68%'],
                        ]) }}"></canvas>
                    </div>
                    <div class="mt-3">
                        @foreach($payments as $payment)
                            <div class="d-flex justify-content-between small py-1">
                                <span class="text-secondary">{{ $payment['method'] }} · {{ $payment['count'] }}x</span>
                                <span class="fw-semibold">{{ \App\Helpers\CurrencyHelper::format($payment['total']) }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state"><i class="bi bi-credit-card"></i>Belum ada pembayaran.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <p class="section-title mb-0">Penjualan per Kategori</p>
                <p class="section-subtitle">Kontribusi omzet per kategori</p>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Kategori</th><th class="text-center">Qty</th><th class="text-end">Omzet</th></tr></thead>
                    <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td class="small fw-semibold">{{ $category['category'] }}</td>
                            <td class="text-center small">{{ number_format($category['qty'], 0, ',', '.') }}</td>
                            <td class="text-end small fw-semibold">{{ \App\Helpers\CurrencyHelper::format($category['revenue']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><div class="empty-state py-3"><i class="bi bi-tags"></i>Belum ada penjualan.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header">
                <p class="section-title mb-0">Produk Terlaris</p>
                <p class="section-subtitle">Top 8 berdasarkan qty</p>
            </div>
            <div class="card-body">
                @if(count($topProducts))
                    <div class="chart-wrap sm">
                        <canvas data-chart="{{ json_encode([
                            'type' => 'bar',
                            'data' => [
                                'labels' => collect($topProducts)->pluck('name')->all(),
                                'datasets' => [[
                                    'label' => 'Qty',
                                    'data' => collect($topProducts)->pluck('qty')->all(),
                                    'backgroundColor' => '#40916c',
                                    'borderRadius' => 8,
                                    'maxBarThickness' => 26,
                                ]],
                            ],
                            'options' => ['plugins' => ['legend' => ['display' => false]], 'indexAxis' => 'y'],
                        ]) }}"></canvas>
                    </div>
                @else
                    <div class="empty-state"><i class="bi bi-bar-chart"></i>Belum ada produk terjual.</div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <p class="section-title mb-0">Detail Penjualan Produk</p>
            <p class="section-subtitle">{{ count($products) }} baris produk/varian</p>
        </div>
        <span class="badge badge-soft-secondary">HPP {{ \App\Helpers\CurrencyHelper::format($summary['cogs']) }}</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Produk</th><th>Varian</th><th>Kategori</th><th class="text-center">Qty</th><th class="text-end">Omzet</th><th class="text-end">HPP</th><th class="text-end">Margin</th></tr></thead>
            <tbody>
            @forelse($products as $product)
                <tr>
                    <td class="small fw-semibold">{{ $product['product'] }}</td>
                    <td class="small">{{ $product['variant'] }}</td>
                    <td class="small">{{ $product['category'] }}</td>
                    <td class="text-center small">{{ number_format($product['qty'], 0, ',', '.') }}</td>
                    <td class="text-end small fw-semibold">{{ \App\Helpers\CurrencyHelper::format($product['revenue']) }}</td>
                    <td class="text-end small text-secondary">{{ \App\Helpers\CurrencyHelper::format($product['cogs']) }}</td>
                    <td class="text-end small {{ $product['margin'] < 0 ? 'text-danger' : 'text-success' }}">{{ \App\Helpers\CurrencyHelper::format($product['margin']) }}</td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state"><i class="bi bi-graph-up"></i>Belum ada data penjualan pada periode ini.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
