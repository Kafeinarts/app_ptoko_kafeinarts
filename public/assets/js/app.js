/* Kafeinarts POS — SPA, PWA, POS & UI runtime */
(function () {
    'use strict';

    const App = {
        base: (window.__app?.baseUrl || window.location.origin).replace(/\/$/, ''),
        charts: [],
        cart: null,
        config: {
            outletId: null,
            taxPercent: Number(window.__app?.settings?.tax || 0),
            paper: window.__app?.settings?.paper || '58',
            autoPrint: !!window.__app?.settings?.autoPrint,
            hasShift: !!window.__app?.activeShift,
        },
        csrf() {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        },
        swalSuccess(message) {
            return Swal.fire({ icon: 'success', title: 'Berhasil', text: message, confirmButtonColor: '#2d6a4f' });
        },
        swalError(message) {
            return Swal.fire({ icon: 'error', title: 'Gagal', text: message, confirmButtonColor: '#2d6a4f' });
        },
    };

    window.Kafeinarts = App;

    /* ---------------------------------------------------------------- Flash */
    function showFlash() {
        const flash = window.__app?.flash || {};
        if (flash.success) Swal.fire({ icon: 'success', title: 'Berhasil', text: flash.success, timer: 3200, timerProgressBar: true, confirmButtonColor: '#2d6a4f' });
        if (flash.error) Swal.fire({ icon: 'error', title: 'Gagal', text: flash.error, confirmButtonColor: '#2d6a4f' });
        if (flash.validation) Swal.fire({ icon: 'warning', title: 'Periksa input', text: flash.validation, confirmButtonColor: '#2d6a4f' });
        if (window.__app) window.__app.flash = { success: null, error: null, validation: null };
    }

    /* -------------------------------------------------------------- Confirm */
    function bindConfirmForms(root = document) {
        root.querySelectorAll('form[data-confirm]').forEach((form) => {
            if (form.dataset.confirmBound) return;
            form.dataset.confirmBound = '1';
            form.addEventListener('submit', (event) => {
                if (form.dataset.confirmed === '1') return;
                event.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: form.dataset.confirm || 'Lanjutkan aksi ini?',
                    text: form.dataset.confirmText || 'Tindakan ini tidak dapat dibatalkan.',
                    showCancelButton: true,
                    confirmButtonText: form.dataset.confirmButton || 'Ya, lanjutkan',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#b02a37',
                    cancelButtonColor: '#6c757d',
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.dataset.confirmed = '1';
                        form.submit();
                    }
                });
            });
        });
    }

    /* -------------------------------------------------------------- Charts */
    function initCharts(root = document) {
        if (typeof Chart === 'undefined') return;
        Chart.defaults.font.family = "'Source Sans 3', system-ui, sans-serif";
        Chart.defaults.color = '#6c757d';

        root.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
            let config;
            try { config = JSON.parse(canvas.dataset.chart); } catch (e) { return; }
            const existing = Chart.getChart(canvas);
            if (existing) existing.destroy();
            config.options = Object.assign({
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { labels: { usePointStyle: true, boxWidth: 8, padding: 16 } } },
                scales: config.type === 'doughnut' || config.type === 'pie' ? undefined : {
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                    y: { grid: { color: 'rgba(0,0,0,.05)' }, ticks: { font: { size: 11 } }, beginAtZero: true },
                },
                elements: { line: { tension: .35, borderWidth: 2.5 }, point: { radius: 2, hoverRadius: 5 } },
            }, config.options || {});
            App.charts.push(new Chart(canvas, config));
        });
    }

    /* ----------------------------------------------------------------- POS */
    function currency(value) {
        return 'Rp ' + Math.round(Number(value) || 0).toLocaleString('id-ID');
    }

    function parseCurrency(text) {
        return Number(String(text).replace(/[^0-9]/g, '')) || 0;
    }

    function cartKey() {
        return 'kafeinarts.cart.' + App.config.outletId;
    }

    function loadCart() {
        try {
            App.cart = JSON.parse(localStorage.getItem(cartKey())) || { items: [], customer_id: '', voucher_code: '', discount: 0, notes: '' };
        } catch (e) {
            App.cart = { items: [], customer_id: '', voucher_code: '', discount: 0, notes: '' };
        }
        if (!Array.isArray(App.cart.items)) App.cart.items = [];
    }

    function saveCart() {
        localStorage.setItem(cartKey(), JSON.stringify(App.cart));
        renderCart();
    }

    function cartTotals() {
        const subtotal = App.cart.items.reduce((sum, item) => sum + (item.price * item.qty), 0);
        const itemDiscount = App.cart.items.reduce((sum, item) => sum + (Number(item.discount) || 0), 0);
        const grossDiscount = itemDiscount + (Number(App.cart.discount) || 0);
        const taxable = Math.max(0, subtotal - grossDiscount);
        const tax = taxable * (App.config.taxPercent / 100);
        return { subtotal, discount: grossDiscount, tax, total: Math.max(0, taxable + tax) };
    }

    function renderCart() {
        const wrap = document.getElementById('cart-items');
        if (!wrap) return;
        if (!App.cart.items.length) {
            wrap.innerHTML = `<div class="pos-cart-empty"><i class="bi bi-basket"></i><div class="fw-semibold">Keranjang kosong</div><div class="small">Pilih produk untuk mulai transaksi.</div></div>`;
        } else {
            wrap.innerHTML = App.cart.items.map((item, index) => `
                <div class="cart-item" data-index="${index}">
                    <div class="cart-item-info">
                        <div class="cart-item-name">${escapeHtml(item.name)}</div>
                        <div class="cart-item-meta">${currency(item.price)}${item.modifier_names?.length ? ' · ' + escapeHtml(item.modifier_names.join(', ')) : ''}</div>
                        ${item.notes ? `<div class="cart-item-meta fst-italic">“${escapeHtml(item.notes)}”</div>` : ''}
                        <div class="cart-item-price">${currency(item.price * item.qty - (Number(item.discount) || 0))}</div>
                    </div>
                    <div class="d-flex flex-column align-items-end justify-content-between gap-1">
                        <div class="qty-control">
                            <button type="button" data-cart-minus="${index}"><i class="bi bi-dash"></i></button>
                            <span>${item.qty}</span>
                            <button type="button" data-cart-plus="${index}"><i class="bi bi-plus"></i></button>
                        </div>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-light btn-icon" title="Catatan / diskon" data-cart-edit="${index}"><i class="bi bi-pencil"></i></button>
                            <button type="button" class="btn btn-sm btn-light btn-icon text-danger" title="Hapus" data-cart-remove="${index}"><i class="bi bi-trash"></i></button>
                        </div>
                    </div>
                </div>`).join('');
        }

        const totals = cartTotals();
        document.querySelectorAll('[data-cart-count]').forEach((el) => { el.textContent = App.cart.items.reduce((sum, item) => sum + item.qty, 0); });
        const setText = (id, value) => { const el = document.getElementById(id); if (el) el.textContent = value; };
        setText('cart-subtotal', currency(totals.subtotal));
        setText('cart-discount', currency(totals.discount));
        setText('cart-tax', currency(totals.tax));
        setText('cart-total', currency(totals.total));
        setText('cart-tax-label', `Pajak (${App.config.taxPercent}%)`);

        const payButton = document.getElementById('btn-payment');
        if (payButton) payButton.disabled = App.cart.items.length === 0;
        const hint = document.getElementById('cart-shift-hint');
        if (hint) hint.style.display = App.config.hasShift ? 'none' : 'block';
        updateMobileCartCount();
    }

    function updateMobileCartCount() {
        const badge = document.querySelector('[data-cart-count]');
        const fab = document.getElementById('mobile-cart-fab');
        if (fab) fab.classList.toggle('d-none', !App.cart.items.length);
        if (badge) badge.textContent = App.cart.items.reduce((sum, item) => sum + item.qty, 0);
    }

    function escapeHtml(text) {
        return String(text ?? '').replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
    }

    function addToCart(product, variant, modifiers) {
        if (!variant) return;
        const modifierTotal = (modifiers || []).reduce((sum, mod) => sum + Number(mod.price || 0), 0);
        const key = variant.id + '|' + (modifiers || []).map((m) => m.id).sort().join(',');
        const existing = App.cart.items.find((item) => item.key === key);
        if (existing) {
            if (existing.qty + 1 > variant.stock) return App.swalError('Stok tidak mencukupi.');
            existing.qty += 1;
        } else {
            if (variant.stock < 1) return App.swalError('Stok produk habis.');
            App.cart.items.push({
                key,
                variant_id: Number(variant.id),
                name: product.name + (variant.name && variant.name !== 'Default' ? ' - ' + variant.name : ''),
                price: Number(variant.price) + modifierTotal,
                stock: Number(variant.stock),
                qty: 1,
                discount: 0,
                notes: '',
                modifiers: (modifiers || []).map((m) => m.id),
                modifier_names: (modifiers || []).map((m) => m.name),
            });
        }
        saveCart();
    }

    function chooseOptions(product, callback) {
        const groups = (product.modifiers || []).filter((group) => (group.items || []).length);
        const variants = product.variants || [];
        let html = '';
        if (variants.length > 1) {
            html += `<div class="text-start mb-2"><label class="form-label">Varian</label><select id="swal-variant" class="form-select">${variants.map((v) => `<option value="${v.id}">${escapeHtml(v.name)} — ${currency(v.price)}</option>`).join('')}</select></div>`;
        }
        groups.forEach((group) => {
            html += `<div class="text-start mb-2"><div class="fw-semibold small mb-1">${escapeHtml(group.name)}${group.required ? ' *' : ''}</div>`;
            group.items.forEach((item) => {
                html += `<label class="d-flex justify-content-between align-items-center border rounded px-2 py-1 mb-1 small"><span><input type="checkbox" class="form-check-input me-2" value="${item.id}" data-price="${item.price}" data-name="${escapeHtml(item.name)}">${escapeHtml(item.name)}</span><span class="text-secondary">+${currency(item.price)}</span></label>`;
            });
            html += '</div>';
        });

        if (!html) {
            callback(variants[0] || null, []);
            return;
        }

        Swal.fire({
            title: escapeHtml(product.name),
            html: `<div class="text-start">${html}</div>`,
            showCancelButton: true,
            confirmButtonText: 'Tambahkan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#2d6a4f',
            preConfirm: () => {
                const variantId = document.getElementById('swal-variant')?.value;
                const variant = variants.find((v) => String(v.id) === String(variantId)) || variants[0];
                const modifiers = [...document.querySelectorAll('.swal2-html-container input[type=checkbox]:checked')].map((cb) => ({ id: Number(cb.value), price: Number(cb.dataset.price), name: cb.dataset.name }));
                return { variant, modifiers };
            },
        }).then((result) => {
            if (result.isConfirmed && result.value) callback(result.value.variant, result.value.modifiers);
        });
    }

    function bindPos() {
        const root = document.getElementById('pos-app');
        if (!root) return;

        App.config.outletId = root.dataset.outletId;
        App.config.hasShift = root.dataset.hasShift === '1';
        App.config.taxPercent = Number(root.dataset.tax || 0);
        loadCart();

        root.querySelectorAll('.product-tile').forEach((tile) => {
            tile.addEventListener('click', () => {
                if (tile.dataset.out === '1') return App.swalError('Stok produk habis.');
                const product = JSON.parse(tile.dataset.product);
                chooseOptions(product, (variant, modifiers) => addToCart(product, variant, modifiers));
            });
        });

        const search = document.getElementById('pos-search');
        const filterCategory = (category) => {
            const term = (search?.value || '').toLowerCase();
            root.querySelectorAll('.product-tile').forEach((tile) => {
                const matchText = tile.dataset.search.includes(term);
                const matchCat = !category || category === 'all' || tile.dataset.category === category;
                tile.classList.toggle('d-none', !(matchText && matchCat));
            });
        };
        search?.addEventListener('input', () => filterCategory(document.querySelector('.category-pill.active')?.dataset.category));
        root.querySelectorAll('.category-pill').forEach((pill) => {
            pill.addEventListener('click', () => {
                root.querySelectorAll('.category-pill').forEach((p) => p.classList.remove('active'));
                pill.classList.add('active');
                filterCategory(pill.dataset.category);
            });
        });

        const cartWrap = document.getElementById('cart-items');
        cartWrap?.addEventListener('click', (event) => {
            const plus = event.target.closest('[data-cart-plus]');
            const minus = event.target.closest('[data-cart-minus]');
            const remove = event.target.closest('[data-cart-remove]');
            const edit = event.target.closest('[data-cart-edit]');
            if (plus) {
                const item = App.cart.items[Number(plus.dataset.cartPlus)];
                if (item.qty + 1 > item.stock) return App.swalError('Stok tidak mencukupi.');
                item.qty++; saveCart();
            }
            if (minus) {
                const index = Number(minus.dataset.cartMinus);
                const item = App.cart.items[index];
                item.qty--; if (item.qty <= 0) App.cart.items.splice(index, 1);
                saveCart();
            }
            if (remove) {
                const index = Number(remove.dataset.cartRemove);
                Swal.fire({ icon: 'question', title: 'Hapus item?', text: 'Item akan dikeluarkan dari keranjang.', showCancelButton: true, confirmButtonText: 'Hapus', confirmButtonColor: '#b02a37', cancelButtonColor: '#6c757d' })
                    .then((r) => { if (r.isConfirmed) { App.cart.items.splice(index, 1); saveCart(); } });
            }
            if (edit) {
                const index = Number(edit.dataset.cartEdit);
                const item = App.cart.items[index];
                Swal.fire({
                    title: 'Catatan & Diskon Item',
                    html: `<input id="swal-note" class="form-control mb-2" placeholder="Catatan (mis. less sugar)" value="${escapeHtml(item.notes || '')}">
                           <input id="swal-discount" type="number" min="0" class="form-control" placeholder="Diskon item (Rp)" value="${Number(item.discount) || 0}">`,
                    showCancelButton: true, confirmButtonText: 'Simpan', confirmButtonColor: '#2d6a4f',
                    preConfirm: () => ({
                        notes: document.getElementById('swal-note').value,
                        discount: Math.max(0, Number(document.getElementById('swal-discount').value) || 0),
                    }),
                }).then((r) => {
                    if (r.isConfirmed) {
                        if (r.value.discount > item.price * item.qty) return App.swalError('Diskon melebihi subtotal item.');
                        item.notes = r.value.notes; item.discount = r.value.discount; saveCart();
                    }
                });
            }
        });

        const clearButton = document.getElementById('btn-clear-cart');
        clearButton?.addEventListener('click', () => {
            if (!App.cart.items.length) return;
            Swal.fire({ icon: 'warning', title: 'Kosongkan keranjang?', text: 'Semua item akan dihapus.', showCancelButton: true, confirmButtonText: 'Kosongkan', confirmButtonColor: '#b02a37', cancelButtonColor: '#6c757d' })
                .then((r) => { if (r.isConfirmed) { App.cart = { items: [], customer_id: '', voucher_code: '', discount: 0, notes: '' }; saveCart(); } });
        });

        const modalEl = document.getElementById('payment-modal');
        const modal = modalEl && window.bootstrap ? new bootstrap.Modal(modalEl) : null;

        document.getElementById('btn-payment')?.addEventListener('click', () => {
            if (!App.config.hasShift) {
                return Swal.fire({ icon: 'warning', title: 'Shift belum dibuka', text: 'Buka shift kasir terlebih dahulu sebelum memproses transaksi.', showCancelButton: true, confirmButtonText: 'Buka Shift', confirmButtonColor: '#2d6a4f' })
                    .then((r) => { if (r.isConfirmed) { window.location.href = root.dataset.shiftUrl; } });
            }
            if (!App.cart.items.length) return;
            const totals = cartTotals();
            document.getElementById('pay-total').textContent = currency(totals.total);
            document.getElementById('pay-subtotal').textContent = currency(totals.subtotal);
            document.getElementById('pay-discount-preview').textContent = currency(totals.discount);
            document.getElementById('pay-tax').textContent = currency(totals.tax);
            document.getElementById('pay-notes').value = App.cart.notes || '';
            const amountInput = document.getElementById('pay-amount');
            amountInput.value = Math.ceil(totals.total / 1000) * 1000;
            updateChange();
            modal?.show();
        });

        modalEl?.querySelectorAll('[data-method]').forEach((button) => {
            button.addEventListener('click', () => {
                modalEl.querySelectorAll('[data-method]').forEach((b) => b.classList.remove('active'));
                button.classList.add('active');
                modalEl.querySelector('[name="payment_method"]').value = button.dataset.method;
                document.getElementById('cash-only-fields').style.display = button.dataset.method === 'CASH' ? 'block' : 'none';
                if (button.dataset.method !== 'CASH') {
                    document.getElementById('pay-amount').value = cartTotals().total;
                    updateChange();
                }
            });
        });

        const amountInput = document.getElementById('pay-amount');
        amountInput?.addEventListener('input', updateChange);
        document.querySelectorAll('[data-quick-cash]').forEach((button) => {
            button.addEventListener('click', () => {
                amountInput.value = parseCurrency(amountInput.value) + Number(button.dataset.quickCash);
                updateChange();
            });
        });

        function updateChange() {
            const totals = cartTotals();
            const paid = parseCurrency(amountInput.value);
            const change = paid - totals.total;
            const el = document.getElementById('pay-change');
            if (el) {
                el.textContent = change >= 0 ? currency(change) : 'Kurang ' + currency(Math.abs(change));
                el.classList.toggle('text-danger', change < 0);
            }
            const place = document.getElementById('btn-place-order');
            if (place) place.disabled = paid < totals.total && modalEl.querySelector('[name="payment_method"]').value === 'CASH';
        }

        const checkoutForm = document.getElementById('checkout-form');
        checkoutForm?.addEventListener('submit', (event) => {
            event.preventDefault();
            const totals = cartTotals();
            const amountPaid = parseCurrency(document.getElementById('pay-amount').value);
            const method = modalEl.querySelector('[name="payment_method"]').value;
            App.cart.customer_id = document.getElementById('pay-customer')?.value || '';
            App.cart.voucher_code = document.getElementById('pay-voucher')?.value || '';
            App.cart.discount = Number(document.getElementById('pay-discount')?.value) || 0;
            App.cart.notes = document.getElementById('pay-notes')?.value || '';

            if (method === 'CASH' && amountPaid < totals.total) return App.swalError('Nominal pembayaran kurang dari total tagihan.');

            const payload = {
                items: App.cart.items.map((item) => ({ variant_id: item.variant_id, qty: item.qty, discount: item.discount, notes: item.notes, modifiers: item.modifiers })),
                payment_method: method,
                amount_paid: amountPaid,
                discount_amount: Number(App.cart.discount) || 0,
                voucher_code: App.cart.voucher_code || null,
                customer_id: App.cart.customer_id || null,
                points_used: Number(document.getElementById('pay-points')?.value) || 0,
                reference_note: document.getElementById('pay-reference')?.value || null,
                notes: App.cart.notes || null,
            };

            const place = document.getElementById('btn-place-order');
            place.disabled = true;
            place.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';

            fetch(`${App.base}/pos/checkout`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': App.csrf() },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            }).then(async (response) => {
                const json = await response.json().catch(() => ({ message: 'Respons server tidak valid.' }));
                if (!response.ok || !json.success) throw new Error(json.message || 'Transaksi gagal diproses.');
                return json.data;
            }).then((data) => {
                modal?.hide();
                App.cart = { items: [], customer_id: '', voucher_code: '', discount: 0, notes: '' };
                saveCart();
                checkoutForm.reset();
                notySuccess(data);
            }).catch((error) => {
                if (error instanceof TypeError) {
                    queueCheckout(payload);
                    modal?.hide();
                    App.cart = { items: [], customer_id: '', voucher_code: '', discount: 0, notes: '' };
                    saveCart();
                    Swal.fire({ icon: 'info', title: 'Disimpan offline', text: 'Koneksi terputus. Transaksi masuk antrean dan akan dikirim otomatis saat online.', confirmButtonColor: '#2d6a4f' });
                } else {
                    App.swalError(error.message);
                }
            }).finally(() => {
                place.disabled = false;
                place.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Bayar Sekarang';
            });
        });

        document.getElementById('mobile-cart-fab')?.addEventListener('click', () => {
            document.getElementById('pos-cart')?.classList.add('open');
        });
        document.querySelectorAll('[data-close-cart]').forEach((btn) => btn.addEventListener('click', () => {
            document.getElementById('pos-cart')?.classList.remove('open');
        }));

        renderCart();
        syncOfflineQueue();
    }

    function notySuccess(data) {
        const transaction = data.transaction;
        Swal.fire({
            icon: 'success',
            title: 'Pembayaran berhasil',
            html: `<div class="text-center"><div class="text-secondary small mb-1">${transaction.invoice_number}</div><div class="fs-3 fw-bold" style="color:#2d6a4f">${currency(transaction.grand_total)}</div><div class="small text-secondary">Kembalian ${currency(transaction.payments?.[0]?.change_returned || 0)}</div></div>`,
            showCancelButton: true,
            confirmButtonText: '<i class="bi bi-printer me-1"></i> Cetak Struk',
            cancelButtonText: 'Transaksi Baru',
            confirmButtonColor: '#2d6a4f',
            cancelButtonColor: '#6c757d',
            allowOutsideClick: false,
        }).then((result) => {
            if (result.isConfirmed) window.open(data.receipt_url + '?paper=' + App.config.paper, '_blank');
        });
        if (App.config.autoPrint) window.open(data.receipt_url + '?paper=' + App.config.paper, '_blank');
    }

    function queueCheckout(payload) {
        const queue = JSON.parse(localStorage.getItem('kafeinarts.offline.queue') || '[]');
        queue.push({ payload, queued_at: new Date().toISOString() });
        localStorage.setItem('kafeinarts.offline.queue', JSON.stringify(queue));
        updateOfflineBadge();
    }

    async function syncOfflineQueue() {
        if (!navigator.onLine) return;
        const queue = JSON.parse(localStorage.getItem('kafeinarts.offline.queue') || '[]');
        if (!queue.length) return;
        const remaining = [];
        for (const entry of queue) {
            try {
                const response = await fetch(`${App.base}/pos/checkout`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': App.csrf() },
                    credentials: 'same-origin',
                    body: JSON.stringify(entry.payload),
                });
                if (!response.ok) remaining.push(entry);
            } catch (e) {
                remaining.push(entry);
            }
        }
        localStorage.setItem('kafeinarts.offline.queue', JSON.stringify(remaining));
        updateOfflineBadge();
        if (queue.length !== remaining.length) {
            Swal.fire({ icon: 'success', title: 'Sinkronisasi selesai', text: `${queue.length - remaining.length} transaksi offline berhasil dikirim.`, confirmButtonColor: '#2d6a4f' });
        }
    }

    function updateOfflineBadge() {
        const queue = JSON.parse(localStorage.getItem('kafeinarts.offline.queue') || '[]');
        const badge = document.getElementById('offline-badge');
        if (!badge) return;
        badge.innerHTML = `<i class="bi bi-cloud-slash me-1"></i> Offline${queue.length ? ` · ${queue.length} transaksi menunggu` : ''}`;
        document.body.classList.toggle('is-offline', !navigator.onLine);
    }

    /* ----------------------------------------------------------- SPA router */
    function loadPage(url, push = true) {
        const progress = document.getElementById('spa-progress');
        if (progress) { progress.classList.add('loading'); progress.style.width = '15%'; }
        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-SPA': '1', 'Accept': 'text/html' },
            credentials: 'same-origin',
        }).then(async (response) => {
            const html = await response.text();
            if (response.redirected && response.url.includes('/login')) { window.location.href = response.url; return; }
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const fresh = doc.querySelector('#spa-content');
            const current = document.getElementById('spa-content');
            if (!fresh || !current) { window.location.href = url; return; }
            current.innerHTML = fresh.innerHTML;
            current.dataset.page = fresh.dataset.page;
            document.title = doc.title;
            if (push) history.pushState({ spa: true }, '', url);
            updateActiveNav(url);
            if (progress) { progress.style.width = '100%'; setTimeout(() => { progress.classList.remove('loading'); progress.style.width = '0'; }, 250); }
            window.scrollTo({ top: 0, behavior: 'smooth' });
            closeMobileSidebar();
            initPage();
        }).catch(() => { window.location.href = url; });
    }

    function updateActiveNav(url) {
        const path = new URL(url, window.location.origin).pathname.replace(/\/$/, '') || '/';
        document.querySelectorAll('.app-sidebar .nav-link').forEach((link) => link.classList.remove('active'));
        document.querySelectorAll('.app-sidebar .nav-item').forEach((item) => item.classList.remove('menu-open'));
        let best = null;
        document.querySelectorAll('.app-sidebar a.nav-link[href]').forEach((link) => {
            try {
                const linkPath = new URL(link.href, window.location.origin).pathname.replace(/\/$/, '') || '/';
                if (linkPath !== '/' && path.startsWith(linkPath)) {
                    if (!best || linkPath.length > new URL(best.href).pathname.length) best = link;
                }
            } catch (e) { /* ignore */ }
        });
        if (best) {
            best.classList.add('active');
            const parent = best.closest('.nav-item');
            parent?.classList.add('menu-open');
        }
    }

    function closeMobileSidebar() {
        if (window.innerWidth < 992 && document.body.classList.contains('sidebar-open')) {
            document.querySelector('[data-lte-toggle="sidebar"]')?.click();
        }
    }

    function interceptLinks() {
        if (document.body.dataset.spaBound) return;
        document.body.dataset.spaBound = '1';

        document.addEventListener('click', (event) => {
            const link = event.target.closest('a');
            if (!link) return;
            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            if (link.target || link.hasAttribute('download') || link.dataset.noSpa !== undefined) return;
            if (link.getAttribute('href')?.startsWith('#')) return;
            const url = new URL(link.href, window.location.origin);
            if (url.origin !== window.location.origin) return;
            if (!url.pathname.startsWith(new URL(App.base).pathname)) return;
            if (url.pathname.includes('/receipt') || url.pathname.includes('/pdf') || url.pathname.includes('/excel') || url.pathname.includes('/template')) return;
            if (link.closest('form') || link.dataset.lteToggle) return;
            event.preventDefault();
            loadPage(url.href);
        });

        window.addEventListener('popstate', () => loadPage(window.location.href, false));

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (!form.matches('form[data-spa-form]')) return;
            if (form.method.toLowerCase() !== 'get') return;
            event.preventDefault();
            loadPage(form.action + '?' + new URLSearchParams(new FormData(form)).toString());
        });
    }

    /* --------------------------------------------------------------- Misc */
    function bindCommon() {
        bindConfirmForms();
        document.querySelectorAll('[data-toggle-password]').forEach((button) => {
            if (button.dataset.bound) return;
            button.dataset.bound = '1';
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.togglePassword);
                if (!input) return;
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                button.innerHTML = `<i class="bi bi-eye${show ? '-slash' : ''}"></i>`;
            });
        });
        document.querySelectorAll('[data-toggle-checks]').forEach((button) => {
            if (button.dataset.bound) return;
            button.dataset.bound = '1';
            button.addEventListener('click', () => {
                const boxes = document.querySelectorAll(button.dataset.toggleChecks);
                const allChecked = Array.prototype.every.call(boxes, (box) => box.checked);
                boxes.forEach((box) => { box.checked = !allChecked; });
                button.textContent = allChecked ? 'Pilih semua' : 'Batalkan semua';
            });
        });
        document.querySelectorAll('[data-add-row]').forEach((button) => {
            if (button.dataset.bound) return;
            button.dataset.bound = '1';
            button.addEventListener('click', () => {
                const target = document.querySelector(button.dataset.addRow);
                const template = document.querySelector(button.dataset.template);
                if (target && template) {
                    const clone = template.content.cloneNode(true);
                    const index = target.children.length;
                    clone.querySelectorAll('[name]').forEach((input) => {
                        input.name = input.name.replaceAll('__INDEX__', index);
                    });
                    target.appendChild(clone);
                }
            });
        });
        document.addEventListener('click', (event) => {
            const remove = event.target.closest('[data-remove-row]');
            if (remove) remove.closest('tr')?.remove();
        });
    }

    function registerPwa() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register(`${App.base}/service-worker.js`).catch(() => {});
        }
        window.addEventListener('online', () => { updateOfflineBadge(); syncOfflineQueue(); });
        window.addEventListener('offline', updateOfflineBadge);
        updateOfflineBadge();
    }

    function initPage() {
        bindCommon();
        initCharts();
        bindPos();
    }

    document.addEventListener('DOMContentLoaded', () => {
        showFlash();
        interceptLinks();
        registerPwa();
        initPage();
    });

    window.addEventListener('load', () => setTimeout(updateOfflineBadge, 200));
})();
