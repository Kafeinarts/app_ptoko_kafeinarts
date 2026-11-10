@extends('layouts.app')

@section('title', 'Laporan Laba Rugi')
@section('page-title', 'Laporan Laba Rugi')
@section('page-subtitle', 'Ringkasan pendapatan, HPP, biaya, dan laba bersih')
@section('page-key', 'report-finance')

@section('page-actions')
    @if(auth()->user()->hasPermission('report.export'))
        <a href="{{ route('reports.finance.pdf', ['from' => $from, 'to' => $to]) }}" target="_blank" class="btn btn-sm btn-outline-danger" data-no-spa><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
    @endif
    <a href="{{ route('reports.sales', ['from' => $from, 'to' => $to]) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-graph-up-arrow me-1"></i>Laporan Penjualan</a>
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
        <a href="{{ route('reports.finance') }}" class="btn btn-light"><i class="bi bi-arrow-counterclockwise"></i></a>
        <div class="ms-auto text-secondary small"><i class="bi bi-calendar3 me-1"></i>{{ \Carbon\Carbon::parse($from)->format('d M Y') }} — {{ \Carbon\Carbon::parse($to)->format('d M Y') }}</div>
    </div>
</form>

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Pendapatan Penjualan</div>
            <div class="stat-value">{{ \App\Helpers\CurrencyHelper::format($summary['net_sales']) }}</div>
            <div class="stat-hint">{{ $summary['transactions'] }} transaksi</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Harga Pokok Penjualan</div>
            <div class="stat-value text-danger">{{ \App\Helpers\CurrencyHelper::format($summary['cogs']) }}</div>
            <div class="stat-hint">Biaya bahan & produk</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Laba Kotor</div>
            <div class="stat-value text-success">{{ \App\Helpers\CurrencyHelper::format($summary['gross_profit']) }}</div>
            <div class="stat-hint">Pendapatan − HPP</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-label">Laba Bersih</div>
            <div class="stat-value {{ $summary['net_profit'] < 0 ? 'text-danger' : 'text-success' }}">{{ \App\Helpers\CurrencyHelper::format($summary['net_profit']) }}</div>
            <div class="stat-hint">Setelah biaya & pemasukan lain</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <p class="section-title mb-0">Rincian Laba Rugi</p>
                <p class="section-subtitle">Disusun dari jurnal transaksi outlet</p>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <tbody>
                        <tr class="table-light"><td class="fw-bold" colspan="2">PENDAPATAN</td></tr>
                        <tr><td>Penjualan Bruto</td><td class="text-end">{{ \App\Helpers\CurrencyHelper::format($summary['subtotal']) }}</td></tr>
                        <tr><td>Diskon</td><td class="text-end text-danger">({{ \App\Helpers\CurrencyHelper::format($summary['discount']) }})</td></tr>
                        <tr><td>Pajak</td><td class="text-end">{{ \App\Helpers\CurrencyHelper::format($summary['tax']) }}</td></tr>
                        <tr class="fw-semibold"><td>Penjualan Bersih</td><td class="text-end">{{ \App\Helpers\CurrencyHelper::format($summary['net_sales']) }}</td></tr>
                        <tr class="table-light"><td class="fw-bold" colspan="2">HARGA POKOK PENJUALAN</td></tr>
                        <tr><td>HPP</td><td class="text-end text-danger">({{ \App\Helpers\CurrencyHelper::format($summary['cogs']) }})</td></tr>
                        <tr class="fw-bold"><td>Laba Kotor</td><td class="text-end {{ $summary['gross_profit'] < 0 ? 'text-danger' : 'text-success' }}">{{ \App\Helpers\CurrencyHelper::format($summary['gross_profit']) }}</td></tr>
                        <tr class="table-light"><td class="fw-bold" colspan="2">BIAYA OPERASIONAL</td></tr>
                        @forelse($expensesByCategory as $expense)
                            <tr><td class="small ps-4">{{ $expense->category }}</td><td class="text-end text-danger">({{ \App\Helpers\CurrencyHelper::format($expense->total) }})</td></tr>
                        @empty
                            <tr><td class="text-secondary small ps-4">Tidak ada pengeluaran</td><td class="text-end">—</td></tr>
                        @endforelse
                        <tr class="fw-semibold"><td>Total Biaya</td><td class="text-end text-danger">({{ \App\Helpers\CurrencyHelper::format($summary['expenses']) }})</td></tr>
                        <tr class="table-light"><td class="fw-bold" colspan="2">PENDAPATAN LAIN</td></tr>
                        @forelse($incomesByCategory as $income)
                            <tr><td class="small ps-4">{{ $income->category }}</td><td class="text-end text-success">{{ \App\Helpers\CurrencyHelper::format($income->total) }}</td></tr>
                        @empty
                            <tr><td class="text-secondary small ps-4">Tidak ada pemasukan lain</td><td class="text-end">—</td></tr>
                        @endforelse
                        <tr class="fw-semibold"><td>Total Pemasukan Lain</td><td class="text-end text-success">{{ \App\Helpers\CurrencyHelper::format($summary['incomes']) }}</td></tr>
                    </tbody>
                    <tfoot>
                        <tr class="table-success">
                            <td class="fw-bold fs-6">LABA BERSIH</td>
                            <td class="text-end fw-bold fs-5">{{ \App\Helpers\CurrencyHelper::format($summary['net_profit']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><p class="section-title mb-0">Komposisi Biaya</p></div>
            <div class="card-body">
                @if($expensesByCategory->isNotEmpty())
                    <div class="chart-wrap sm">
                        <canvas data-chart="{{ json_encode([
                            'type' => 'doughnut',
                            'data' => [
                                'labels' => $expensesByCategory->pluck('category')->all(),
                                'datasets' => [[
                                    'data' => $expensesByCategory->pluck('total')->map(fn ($v) => (float) $v)->all(),
                                    'backgroundColor' => ['#1b4332', '#2d6a4f', '#40916c', '#74c69d', '#d4a373', '#95a5a6'],
                                    'borderWidth' => 0,
                                ]],
                            ],
                            'options' => ['cutout' => '65%'],
                        ]) }}"></canvas>
                    </div>
                @else
                    <x-empty-state icon="bi bi-pie-chart" message="Belum ada biaya tercatat." class="py-4" />
                @endif
            </div>
        </div>
        <div class="card">
            <div class="card-header"><p class="section-title mb-0">Indikator</p></div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-secondary small">Margin kotor</span>
                    <span class="fw-semibold">{{ $summary['net_sales'] > 0 ? number_format(($summary['gross_profit'] / $summary['net_sales']) * 100, 1, ',', '.') : '0' }}%</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-secondary small">Margin bersih</span>
                    <span class="fw-semibold">{{ $summary['net_sales'] > 0 ? number_format(($summary['net_profit'] / $summary['net_sales']) * 100, 1, ',', '.') : '0' }}%</span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-secondary small">Rata-rata nota</span>
                    <span class="fw-semibold">{{ \App\Helpers\CurrencyHelper::format($summary['average_ticket']) }}</span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-secondary small">Rasio biaya</span>
                    <span class="fw-semibold">{{ $summary['net_sales'] > 0 ? number_format(($summary['expenses'] / $summary['net_sales']) * 100, 1, ',', '.') : '0' }}%</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
