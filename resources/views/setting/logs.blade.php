@extends('layouts.app')

@section('title', 'Audit Log')
@section('page-title', 'Audit Log')
@section('page-subtitle', 'Jejak aktivitas penting di dalam sistem')
@section('page-key', 'settings-logs')

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div>
            <p class="section-title mb-0"><i class="bi bi-journal-text me-1"></i>Riwayat Aktivitas</p>
            <p class="section-subtitle">{{ $logs->total() }} catatan log</p>
        </div>
        <form method="GET" class="d-flex gap-2" data-spa-form>
            <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Cari event...">
            <button class="btn btn-sm btn-outline-success"><i class="bi bi-search"></i></button>
            @if(request('q'))
                <a href="{{ route('settings.logs') }}" class="btn btn-sm btn-light"><i class="bi bi-x-lg"></i></a>
            @endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:60px">#</th>
                    <th>Waktu</th>
                    <th>Event</th>
                    <th>Entitas</th>
                    <th>Pengguna</th>
                    <th>IP</th>
                    <th class="text-end">Detail</th>
                </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
                @php
                    $old = filled($log->old_values) ? (is_array($log->old_values) ? $log->old_values : json_decode($log->old_values, true)) : null;
                    $new = filled($log->new_values) ? (is_array($log->new_values) ? $log->new_values : json_decode($log->new_values, true)) : null;
                @endphp
                <tr>
                    <td class="text-secondary small">{{ $logs->firstItem() + $loop->index }}</td>
                    <td class="small text-nowrap">{{ $log->created_at?->format('d/m/Y H:i:s') ?? '—' }}</td>
                    <td>
                        <span class="badge badge-soft-{{ str_contains($log->event, 'delete') || str_contains($log->event, 'void') ? 'danger' : (str_contains($log->event, 'create') ? 'success' : 'info') }}">
                            {{ $log->event }}
                        </span>
                    </td>
                    <td class="small">
                        @if($log->auditable_type)
                            {{ class_basename($log->auditable_type) }}
                            @if($log->auditable_id)
                                <span class="text-secondary">#{{ $log->auditable_id }}</span>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td class="small">{{ $log->user?->name ?? 'Sistem' }}</td>
                    <td class="small text-secondary">{{ $log->ip_address ?: '—' }}</td>
                    <td class="text-end">
                        @if($old || $new)
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#log-detail-{{ $log->id }}">
                                <i class="bi bi-code-square"></i>
                            </button>
                        @else
                            <span class="text-secondary small">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state"><i class="bi bi-journal-text"></i>Belum ada aktivitas tercatat.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $logs->links() }}</div>
</div>

@foreach($logs as $log)
    @php
        $old = filled($log->old_values) ? (is_array($log->old_values) ? $log->old_values : json_decode($log->old_values, true)) : null;
        $new = filled($log->new_values) ? (is_array($log->new_values) ? $log->new_values : json_decode($log->new_values, true)) : null;
    @endphp
    @if($old || $new)
        <div class="modal fade" id="log-detail-{{ $log->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0">
                    <div class="modal-header">
                        <h5 class="modal-title">Detail Log — {{ $log->event }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            @if($old)
                                <div class="col-md-6">
                                    <p class="fw-semibold small mb-1">Nilai Sebelum</p>
                                    <pre class="bg-body-tertiary rounded p-2 small mb-0" style="max-height:280px;overflow:auto">{{ json_encode($old, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                            @endif
                            @if($new)
                                <div class="col-md-6">
                                    <p class="fw-semibold small mb-1">Nilai Sesudah</p>
                                    <pre class="bg-body-tertiary rounded p-2 small mb-0" style="max-height:280px;overflow:auto">{{ json_encode($new, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach
@endsection
