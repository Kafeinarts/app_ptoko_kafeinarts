@props([
    'action',
    'confirm' => 'Hapus data ini?',
    'confirmText' => 'Data yang dihapus tidak dapat dikembalikan.',
    'confirmButton' => 'Ya, hapus',
])
<form method="POST" action="{{ $action }}" data-confirm="{{ $confirm }}" data-confirm-text="{{ $confirmText }}" data-confirm-button="{{ $confirmButton }}">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
</form>
