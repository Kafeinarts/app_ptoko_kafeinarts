<div class="modal fade" id="import-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route($routePrefix.'.import') }}" enctype="multipart/form-data" class="modal-content border-0">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Import {{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="import-file">File Excel (.xlsx, .xls, .csv)</label>
                <input type="file" name="file" id="import-file" class="form-control" accept=".xlsx,.xls,.csv" required>
                <div class="form-text">
                    Gunakan template agar kolom sesuai. Maksimal 5 MB.
                    <a href="{{ route($routePrefix.'.template') }}" data-no-spa>Unduh template import</a>.
                    Baris dengan kata kunci yang sama akan diperbarui (upsert).
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button class="btn btn-success"><i class="bi bi-upload me-1"></i>Import Data</button>
            </div>
        </form>
    </div>
</div>
