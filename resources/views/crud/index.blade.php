@extends('layouts.app')

@section('title', $title)
@section('page-title', $title)
@section('page-subtitle', 'Kelola data '.strtolower($title).' pada outlet '.($currentOutlet?->name ?? '-'))
@section('page-key', 'crud-index')

@section('page-actions')
    <a href="{{ route($routePrefix.'.template') }}" class="btn btn-sm btn-outline-secondary" data-no-spa title="Unduh template import Excel">
        <i class="bi bi-download me-1"></i>Template
    </a>
    <a href="{{ route($routePrefix.'.export') }}" class="btn btn-sm btn-outline-success" data-no-spa title="Export seluruh data ke Excel">
        <i class="bi bi-file-earmark-excel me-1"></i>Export
    </a>
    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#import-modal">
        <i class="bi bi-upload me-1"></i>Import
    </button>
    <a href="{{ route($routePrefix.'.insert') }}" class="btn btn-sm btn-success">
        <i class="bi bi-plus-lg me-1"></i>Tambah {{ $singular }}
    </a>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div>
            <p class="section-title mb-0"><i class="{{ $icon }} me-1"></i>{{ $title }}</p>
            <p class="section-subtitle">{{ $records->total() }} data terdaftar</p>
        </div>
        <form method="GET" class="d-flex gap-2" data-spa-form>
            <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Cari...">
            <button class="btn btn-sm btn-outline-success"><i class="bi bi-search"></i></button>
            @if(request('q'))
                <a href="{{ route($routePrefix.'.index') }}" class="btn btn-sm btn-light"><i class="bi bi-x-lg"></i></a>
            @endif
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:50px">#</th>
                    @foreach($columns as $label)
                        <th>{{ $label }}</th>
                    @endforeach
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
            @forelse($records as $record)
                <tr>
                    <td class="text-secondary small">{{ $records->firstItem() + $loop->index }}</td>
                    @foreach($columns as $column => $label)
                        @php($value = data_get($record, $column))
                        <td class="small">
                            @if(is_bool($value))
                                <x-badge-soft :type="$value ? 'success' : 'secondary'">{{ $value ? 'Aktif' : 'Nonaktif' }}</x-badge-soft>
                            @elseif($value instanceof \Illuminate\Support\Carbon)
                                {{ $value->format('d/m/Y H:i') }}
                            @elseif($column === 'loyalty_points')
                                <x-badge-soft type="warning">{{ number_format((int) $value, 0, ',', '.') }} poin</x-badge-soft>
                            @elseif($column === 'is_active')
                                <x-badge-soft :type="$value ? 'success' : 'secondary'">{{ $value ? 'Aktif' : 'Nonaktif' }}</x-badge-soft>
                            @else
                                {{ filled($value) ? $value : '—' }}
                            @endif
                        </td>
                    @endforeach
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#edit-{{ $record->id }}" title="Edit"><i class="bi bi-pencil"></i></button>
                            <x-confirm-button :action="route($routePrefix.'.destroy', $record->id)" :confirm="'Hapus '.strtolower($singular).' ini?'" />
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ count($columns) + 2 }}"><x-empty-state :icon="$icon" :message="'Belum ada data '.strtolower($title).'.'" /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $records->links() }}</div>
</div>

@foreach($records as $record)
    @include('crud._form-modal', ['id' => 'edit-'.$record->id, 'action' => route($routePrefix.'.update', $record->id), 'method' => 'PUT', 'record' => $record, 'title' => 'Edit '.$singular])
@endforeach

@include('crud._import-modal', ['routePrefix' => $routePrefix, 'title' => $title])
@endsection
