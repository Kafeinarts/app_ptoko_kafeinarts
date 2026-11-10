@extends('layouts.app')

@section('title', 'Tambah '.$singular)
@section('page-title', 'Tambah '.$singular)
@section('page-subtitle', 'Isi formulir di bawah ini lalu klik Simpan — data langsung masuk ke sistem.')
@section('page-key', 'crud-insert')

@section('page-actions')
    <a href="{{ route($routePrefix.'.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke Daftar
    </a>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-9 col-xl-7">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <div class="stat-icon bg-soft-green" style="width:42px;height:42px;border-radius:12px">
                    <i class="{{ $icon }} fs-5"></i>
                </div>
                <div>
                    <p class="section-title mb-0">Data {{ $title }} Baru</p>
                    <p class="section-subtitle mb-0">Field bertanda <span class="text-danger">*</span> wajib diisi</p>
                </div>
            </div>
            <form method="POST" action="{{ route($routePrefix.'.store') }}">
                @csrf
                <div class="card-body">
                    @include('crud._fields', ['id' => 'insert', 'record' => null, 'fields' => $fields])
                </div>
                <div class="card-footer bg-white d-flex flex-wrap gap-2 justify-content-end">
                    <a href="{{ route($routePrefix.'.index') }}" class="btn btn-light">
                        <i class="bi bi-x-lg me-1"></i>Batal
                    </a>
                    <button class="btn btn-success">
                        <i class="bi bi-check2 me-1"></i>Simpan {{ $singular }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
