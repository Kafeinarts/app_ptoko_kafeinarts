document.addEventListener('DOMContentLoaded', () => {
    const flash = window.__app?.flash || {};

    document.querySelectorAll('[data-toggle-password]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.togglePassword);
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.innerHTML = `<i class="bi bi-eye${show ? '-slash' : ''}"></i>`;
        });
    });

    if (flash.success) {
        Swal.fire({icon: 'success', title: 'Berhasil', text: flash.success, timer: 3200, timerProgressBar: true, confirmButtonColor: '#2d6a4f'});
    }
    if (flash.error) {
        Swal.fire({icon: 'error', title: 'Gagal', text: flash.error, confirmButtonColor: '#2d6a4f'});
    }

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register(`${window.__app?.baseUrl || ''}/service-worker.js`).catch(() => {});
    }
});
