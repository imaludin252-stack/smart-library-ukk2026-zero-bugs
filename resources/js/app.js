import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    // ---- Modal ----
    const openModal = (name) => {
        document.querySelectorAll(`[data-modal="${name}"]`).forEach((el) => el.classList.remove('hidden'));
        document.body.classList.add('overflow-hidden');
    };

    const closeModal = (name) => {
        document.querySelectorAll(`[data-modal="${name}"]`).forEach((el) => el.classList.add('hidden'));
        document.body.classList.remove('overflow-hidden');
    };

    document.querySelectorAll('[data-modal-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => openModal(btn.dataset.modalToggle));
    });

    document.querySelectorAll('[data-modal-close]').forEach((btn) => {
        btn.addEventListener('click', () => closeModal(btn.dataset.modalClose));
    });

    document.querySelectorAll('[data-modal]').forEach((overlay) => {
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                closeModal(overlay.dataset.modal);
            }
        });
    });

    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            document.querySelectorAll('[data-modal]').forEach((el) => closeModal(el.dataset.modal));
        }
    });

    // ---- Konfirmasi aksi destruktif ----
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    // ---- Toast auto hide ----
    document.querySelectorAll('[data-toast]').forEach((toast) => {
        setTimeout(() => {
            toast.style.transition = 'opacity .4s ease, transform .4s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translate(-50%, 1rem)';
            setTimeout(() => toast.remove(), 400);
        }, 4200);
    });

    // ---- Ctrl + K fokus pencarian ----
    const searchInput = document.querySelector('[data-search-input]');
    window.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k' && searchInput) {
            event.preventDefault();
            searchInput.focus();
            searchInput.select();
        }
    });

    // ---- Toggle lihat kata sandi ----
    document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.togglePassword);
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });

    // ---- Pilih semua (bulk action) ----
    const master = document.querySelector('[data-check-all]');
    if (master) {
        const boxes = () => document.querySelectorAll('[data-check-item]');
        master.addEventListener('change', () => {
            boxes().forEach((box) => (box.checked = master.checked));
            document.dispatchEvent(new Event('bulk:change'));
        });
        boxes().forEach((box) => box.addEventListener('change', () => document.dispatchEvent(new Event('bulk:change'))));
    }

    // ---- Cetak ----
    document.querySelectorAll('[data-print]').forEach((btn) => {
        btn.addEventListener('click', () => window.print());
    });

    // ---- Modal pinjam: aktifkan tombol setelah setuju tata tertib ----
    const setuju = document.querySelector('[data-setuju]');
    if (setuju) {
        const konfirmasi = document.querySelector('[data-konfirmasi-pinjam]');
        setuju.addEventListener('change', () => {
            if (konfirmasi) konfirmasi.disabled = !setuju.checked;
        });
    }
});
