# Kafeinarts POS

Aplikasi **Point of Sale (POS), manajemen inventaris, pembelian, dan laporan keuangan** untuk kafe & UMKM F&B — dirancang untuk **multi-outlet**, dengan PWA offline-first untuk kasir.

> Stack: **Laravel 13** (PHP 8.3+) · **MySQL/MariaDB** · **AdminLTE** (assets lokal, tanpa build tool) · Bootstrap 5 · Chart.js · SweetAlert2 · **dompdf** (PDF) · **maatwebsite/excel** (Import/Export Excel) · **Laravel PWA** (service worker + offline queue)

---

## 1. Tentang Proyek

Kafeinarts POS adalah aplikasi kasir & back-office terintegrasi untuk badan usaha kuliner (kafe, kedai kopi, bakery, UMKM F&B) yang ingin mengelola satu atau banyak **outlet/cabang** dari satu sistem:

- **Front office** — kasir POS yang cepat: cari produk, keranjang, diskon, voucher, poin loyalitas, berbagai metode pembayaran, cetak struk 58/80mm, shift kas, dan mode **offline** (transaksi tersimpan di browser, tersinkron saat online kembali).
- **Back office** — master produk, stok & gudang, pembelian (Purchase Order), promosi, keuangan (pemasukan/pengeluaran), serta **laporan penjualan & Laba-Rugi** dengan ekspor PDF/Excel.
- **Operasional** — manajemen pengguna, role & permission (RBAC), multi-outlet dengan **Penanggung Jawab (PJ)** per outlet, pengaturan umum, audit log, dan dashboard real-time.

Dokumentasi bisnis & teknis tambahan ada di folder [`Prompts/`](Prompts/): `PRD.md`, `Pattern.md`, `Rules.md`, `Changelog.md`.

## 2. Fitur Utama

| Modul | Deskripsi |
|---|---|
| **Kasir / POS** | Grid produk per kategori, pencarian cepat, keranjang persisten (LocalStorage), diskon per item & global, voucher, tukar poin loyalitas, multi metode bayar (Tunai, QRIS, Transfer, Debit, Kredit), hitung uang kembalian otomatis, **mode offline** dengan antrean transaksi. |
| **Shift & Kas** | Buka/tutup shift, arus kas masuk/keluar (CASH_IN/CASH_OUT), kas seharusnya vs aktual, selisih otomatis, ringkasan shift (PDF). |
| **Riwayat & Retur** | Cari transaksi, detail (modal), **void** (wajib alasan + approver, stok kembali otomatis), **retur/sebagian retur** (REFUND/PARTIAL_REFUND, stok kembali otomatis). |
| **Master Data** | Produk (multi-varian & multi-harga per outlet), kategori, brand, satuan, pelanggan (grup + diskon), supplier, modifier/add-on (mis. Level Gula, Extra Shot). **Setiap modul master tersedia: input, import Excel, export Excel, dan download template.** |
| **Inventaris** | Stok per outlet & variant, mutasi (IN/OUT/ADJUSTMENT/TRANSFER/VOID/RETURN), **stok minimum** (badge peringatan), **Stock Opname** (hitung fisik vs sistem), **Transfer antar-cabang** (PENDING→SENT→RECEIVED). |
| **Pembelian** | Purchase Order multi-item, terima barang (stok otomatis bertambah), retur pembelian (stok berkurang), riwayat PO. |
| **Promosi** | Voucher (% / nominal, min. belanja, maks. diskon, masa berlaku), validasi otomatis di kasir. |
| **Keuangan** | Pencatatan pengeluaran & pemasukan + kategori, terintegrasi ke laporan Laba-Rugi. |
| **Laporan** | Laporan penjualan (ringkasan, tren harian, top produk, breakdown pembayaran, per produk), **Laba-Rugi** (pendapatan, HPP, biaya operasional per kategori, laba bersih), ekspor **PDF** & **Excel**. |
| **Pengaturan** | Pengguna (CRUD + nonaktifkan), **Role & Akses** (31 permission), **Outlet + PJ** (registrasi outlet baru & penanggung jawab), Umum & Printer (58/80mm, pajak, metode bayar, loyalitas), **Audit Log**. |
| **PWA** | Installable (manifest + service worker), halaman offline, shortcut kasir. |

## 3. Tech Stack

- **Backend:** Laravel 13 (PHP ^8.3), service layer (`app/Services/*`), Form Requests, custom permission middleware (`permission:*`).
- **Frontend:** Blade + SPA ringan (fetch + DOMParser menukar `#spa-content`), Bootstrap 5 / AdminLTE lokal, SweetAlert2, Chart.js — **tidak ada build tool (tidak perlu npm)**.
- **Database:** MySQL 8 / MariaDB 10.4+ (44 tabel aplikasi + tabel bawaan Laravel).
- **PDF:** barryvdh/dompdf — struk thermal 58/80mm (custom page size), laporan A4.
- **Excel:** maatwebsite/excel — import/export semua master data & laporan.
- **Testing:** PHPUnit (Feature + Unit), `LazilyRefreshDatabase`, database test `db_pos_kafeinarts_test`.
- **Code style:** Laravel Pint.

## 4. Arsitektur & Struktur Folder

Alur request: `Route → permission middleware (EnsurePermission) → Controller (tipis) → Form Request (validasi) → Service (business logic, DB::transaction) → Model → Blade view`.

```
app/
├── Exports/  Imports/   # Excel (RowsExport generik, RowsImport, ProductsExport/Export rep. dsb)
├── Helpers/             # CurrencyHelper, InvoiceNumberHelper, ResponseHelper
├── Http/
│   ├── Controllers/     # Auth, Dashboard, POS, Master, Inventory, Purchasing,
│   │                    # Promotion, Finance, Report, Setting
│   ├── Middleware/      # EnsurePermission (RBAC)
│   └── Requests/        # Validasi Form Request (POS, products, dst.)
├── Models/              # 40+ Eloquent models (relasi & cast lengkap)
├── Services/            # CheckoutService, ShiftService, ReturnService, StockManager,
│                        # PurchaseService, SalesReportService, LoyaltyPointService, dst.
└── Support/             # AuditLogger, OutletContext (konteks outlet dari session)
database/
├── migrations/          # 47 file (urutan timestamp rapi)
├── seeders/             # DatabaseSeeder (demo) · RbacSeeder (RBAC) · ProductionSeeder (badan usaha)
public/
├── assets/              # CSS/JS/vendor (adminlte, chart.js, sweetalert2, bootstrap-icons), PWA icons
├── manifest.webmanifest · service-worker.js · offline.html
resources/views/         # Blade views per modul (layouts, master, pos, inventory, purchasing, setting, dll.)
routes/web.php           # ~120 route, semua dengan middleware auth + permission
tests/                   # Feature (Auth, CheckoutFlow, ShiftFlow, Master*, Settings, SmokePages…) + Unit
Prompts/                 # PRD.md · Pattern.md · Rules.md · Changelog.md (aturan main proyek)
```

**Contoh alur penting — checkout kasir** (`POS\CheckoutController@store` → `CheckoutService::processCheckout`):
validasi stok & harga per outlet → hitung subtotal, diskon (item + global + grup + voucher + poin) → pajak → total → `DB::transaction` menyimpan transaksi + item + modifier + payment, **mengurangi stok + `stock_logs`**, menambah poin loyalitas, menulis **audit log**. Void/retur mengembalikan stok & mencatat log.

## 5. Skema Database (44 tabel aplikasi + 4 bawaan)

Semua transaksi memakai `outlet_id` (per cabang) dan **snapshot harga/HPP** pada `transaction_items`.

**Akses & Keamanan**
| Tabel | Isi |
|---|---|
| `users` | Akun (kasir/manajer/admin), role, outlet penempatan, status aktif |
| `roles`, `permissions`, `role_permissions` | RBAC kustom (31 permission; Super Admin = akses penuh) |
| `password_reset_tokens`, `sessions`, `cache`, `jobs` | Bawaan Laravel (session & cache DB) |

**Outlet & PJ**
| Tabel | Isi |
|---|---|
| `outlets` | Cabang: kode, nama, alamat, kontak, `receipt_footer`, **`manager_id` = Penanggung Jawab (PJ)** (FK → users, nullOnDelete), status |

**Master Produk**
| Tabel | Isi |
|---|---|
| `categories` (+slug), `brands`, `units` | Master dasar produk |
| `products` | SKU & barcode unik, kategori, brand, satuan, status aktif, foto |
| `product_variants` | Varian (Regular/Large/dll), `sku_variant` unik |
| `product_prices` | Harga beli/jual **per outlet × variant** (unik) |
| `product_modifier_groups` | Grup modifier (mis. "Add-on Signature", "Level Gula": required, min/max) |
| `product_modifiers` | Isi grup (nama, harga, tersedia) |
| `product_modifier_group_product` | Pivot grup ↔ produk |

**Inventaris**
| Tabel | Isi |
|---|---|
| `stocks` | Stok per outlet × variant (+ `min_stock`) — unik per kombinasi |
| `stock_logs` | Kartu stok: IN/OUT/ADJUSTMENT/TRANSFER/VOID/RETURN, saldo, referensi |
| `stock_opnames`, `stock_opname_details` | Stok opname: sistem vs fisik, selisih & nilai |
| `stock_transfers`, `stock_transfer_details` | Transfer antar outlet (PENDING/SENT/RECEIVED/CANCELLED) |

**Pelanggan & Promosi**
| Tabel | Isi |
|---|---|
| `customer_groups` | Reguler / Member / VIP (+ diskon %) |
| `customers` | Data pelanggan, telepon unik, poin loyalitas |
| `customer_point_logs` | Riwayat poin EARNED/REDEEMED/EXPIRED |
| `suppliers` | Data supplier |
| `vouchers` | Voucher % / nominal, min. belanja, maks. diskon, masa berlaku |

**Shift & Transaksi**
| Tabel | Isi |
|---|---|
| `shifts` | Shift kasir: modal awal, expected/ending/actual cash, selisih, status OPEN/CLOSED |
| `shift_cash_logs` | Arus kas CASH_IN / CASH_OUT + alasan |
| `transactions` | Nota (invoice TRX-xxx), outlet, shift, kasir, pelanggan, voucher, status PAID/VOID/REFUND/PARTIAL_REFUND |
| `transaction_items` | Item nota: HPP, harga jual, qty, diskon, `returned_qty` |
| `transaction_item_modifiers` | Modifier yang dipilih (harga saat itu) |
| `transaction_payments` | Pembayaran (CASH/QRIS/TRANSFER/DEBIT/CREDIT, uang dibayar, kembalian) |
| `transaction_voids` | Audit void: alasan, yang memproses, approver |
| `transaction_returns`, `transaction_return_items` | Retur: nomor RET-xxx, alasan, item retur |

**Pembelian**
| Tabel | Isi |
|---|---|
| `purchase_orders`, `purchase_order_items` | PO: nomor PO-xxx, supplier, total, status PENDING/RECEIVED/CANCELLED, qty order/terima |
| `purchase_returns`, `purchase_return_items` | Retur pembelian: nomor PUR-xxx, alasan |

**Keuangan & Sistem**
| Tabel | Isi |
|---|---|
| `expense_categories`, `expenses` | Kategori & pencatatan pengeluaran per outlet |
| `income_categories`, `incomes` | Kategori & pencatatan pemasukan lain per outlet |
| `system_settings` | Key-value per grup (general, printer, payment, loyalty) |
| `audit_logs` | Jejak aksi penting (event, type, old/new values JSON, IP, user) |

## 6. Instalasi (Pengembangan Lokal)

**Kebutuhan:** Windows/macOS/Linux, PHP ≥ 8.3 (`pdo_mysql`, `mbstring`, `intl`, `gd`, `zip`), Composer 2, MySQL/MariaDB. Laragon/XAMPP cukup. **Tidak perlu Node/npm.**

```bash
# 1. Clone & dependency
git clone <repo-url> kafeinarts && cd kafeinarts
composer install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database (buat db_pos_kafeinarts terlebih dahulu)
#    isi .env: DB_DATABASE=db_pos_kafeinarts DB_USERNAME=root DB_PASSWORD=...

# 4. Migrasi + seed demo
php artisan migrate:fresh --seed

# 5. Storage link (foto produk)
php artisan storage:link

# 6. Jalankan
php artisan serve
# buka http://127.0.0.1:8000
```

**Akun demo** (password semua: `password`)

| Email | Role | Outlet |
|---|---|---|
| `admin@kafeinarts.test` | Super Admin | Gatot Subroto |
| `manager@kafeinarts.test` | Manajer | Gatot Subroto |
| `manager.dago@kafeinarts.test` | Manajer | Dago Bandung |
| `kasir@kafeinarts.test` | Kasir | Gatot Subroto |
| `kasir.dago@kafeinarts.test` | Kasir | Dago Bandung |

Demo berisi 2 outlet, 19 produk (+varian & harga), stok per outlet, voucher `KAFE10`/`HEMAT20`/`KAFEBARU`, transaksi 6 hari terakhir, dan shift yang sudah ditutup — cukup untuk mencoba seluruh fitur.

**Menjalankan test:**

```bash
# buat sekali: CREATE DATABASE db_pos_kafeinarts_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
php artisan test              # 49 test (Feature + Unit)
vendor/bin/pint               # code style
```

## 7. Setup untuk Badan Usaha (Produksi)

### 7.1 Kebutuhan server
- VPS/cloud (Ubuntu 22.04+ direkomendasikan) atau shared hosting yang support Laravel, min. 1 vCPU / 2 GB RAM, 20 GB disk
- PHP ≥ 8.3 + ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `intl`, `gd`, `zip`, `fileinfo`, `xml`
- MySQL 8 / MariaDB 10.4+
- Nginx atau Apache + PHP-FPM, **HTTPS wajib**

### 7.2 Langkah deploy

```bash
cd /var/www
git clone <repo-url> kafeinarts && cd kafeinarts
composer install --no-dev --optimize-autoloader
cp .env.example .env && php artisan key:generate
```

Edit `.env` produksi:

```env
APP_NAME="Kafeinarts POS"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pos.perusahaananda.com
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_kafeinarts_prod
DB_USERNAME=kafeinarts
DB_PASSWORD=<password-kuat>

SESSION_DRIVER=database      # atau file untuk trafik rendah
CACHE_STORE=database         # atau file
QUEUE_CONNECTION=sync
LOG_LEVEL=warning
```

Buat database & user terbatas (jangan pakai root aplikasi):

```sql
CREATE DATABASE db_kafeinarts_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'kafeinarts'@'localhost' IDENTIFIED BY '<password-kuat>';
GRANT ALL PRIVILEGES ON db_kafeinarts_prod.* TO 'kafeinarts'@'localhost';
FLUSH PRIVILEGES;
```

**Instalasi bersih (tanpa data contoh)** — `ProductionSeeder` hanya membuat RBAC, 1 outlet "Kantor Pusat", 1 akun admin, satuan & kategori kas dasar, pengaturan dasar:

```bash
php artisan migrate:fresh --force
ADMIN_NAME="Administrator" ADMIN_EMAIL="admin@perusahaananda.com" ADMIN_PASSWORD='ganti-ini-sekarang' \
OUTLET_NAME="Kantor Pusat" OUTLET_CODE="UT-01" OUTLET_ADDRESS="Alamat lengkap" \
php artisan db:seed --class=ProductionSeeder --force

php artisan storage:link
php artisan config:cache route:cache event:cache view:cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

> Kredensial seed juga bisa diatur via `.env` (`ADMIN_NAME`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`, `ADMIN_PHONE`, `OUTLET_*`, `STORE_NAME`, `TAX_PERCENTAGE`, `RECEIPT_PAPER`, `LOYALTY_*`).

**Nginx vhost (ringkas):**

```nginx
server {
    listen 80;
    server_name pos.perusahaananda.com;
    root /var/www/kafeinarts/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ { include snippets/fastcgi-php.conf; fastcgi_pass unix:/run/php/php8.3-fpm.sock; }
    location ~ /\.(?!well-known).* { deny all; }
}
```

Lalu aktifkan HTTPS: `sudo certbot --nginx -d pos.perusahaananda.com`.

### 7.3 Checklist pasca-instalasi (wajib)

1. **Login** `admin@perusahaananda.com` → langsung **ganti password** (Pengaturan → Pengguna).
2. Isi identitas outlet (Pengaturan → Outlet: nama, PJ, alamat, telepon, footer struk) & tambah outlet cabang bila ada.
3. Isi **Pengaturan → Umum & Printer**: nama toko, pajak (mis. 11%), ukuran kertas (58/80mm), QRIS payload, info rekening bank, loyalty rate.
4. Tambahkan **user kasir & manajer** per outlet, tentukan role (Kasir / Manajer / Super Admin).
5. Isi **Master Data** lewat UI atau **import template Excel** (menu master mana pun → tombol Template/Export/Import).
6. Uji transaksi end-to-end: buka shift → beli 1 item tunai → cetak struk → tutup shift → cek laporan.
7. Atur **backup otomatis** (lihat 7.4) dan monitoring uptime.
8. Pastikan `APP_DEBUG=false`, `.env` tidak bisa diakses publik, dan HTTPS aktif.

### 7.4 Backup & restore (contoh cron harian)

```bash
# /etc/cron.d/kafeinarts-backup — dump DB tiap 02:00, simpan 30 hari
0 2 * * * root mysqldump -u kafeinarts -p'<password>' db_kafeinarts_prod | gzip > /var/backups/kafeinarts/db-$(date +\%F).sql.gz && find /var/backups/kafeinarts -name 'db-*.gz' -mtime +30 -delete
```

Restore: `gunzip < backup.sql.gz | mysql -u kafeinarts -p db_kafeinarts_prod`. Backup `storage/app/public` (foto produk) secara terpisah (rsync/s3).

### 7.5 Update aplikasi

```bash
cd /var/www/kafeinarts
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force          # aman: hanya jalankan migrasi baru
php artisan config:cache route:cache view:cache
```

### 7.6 Percetakan struk & laporan

- **Struk thermal**: atur `receipt_paper` (58/80mm) di Pengaturan → Umum. Cetak via dialog print browser (thermal printer sebagai default system) atau **unduh PDF** dari halaman struk.
- **Laporan**: PDF (A4) & Excel unduh langsung dari halaman laporan.

## 8. Flow Pengembangan Lanjutan (Roadmap)

**Fase 1 — Stabilisasi (sekarang)**
- Inisialisasi git, push ke repo privat, proteksi branch `main`.
- CI (GitHub Actions): job `tests` (MySQL service, `php artisan test`), job `lint` (`pint --test`), gate coverage ≥ 85% (target `Rules.md`).
- Adopsi **Laravel Boost** (panduan Laravel spesifik aplikasi), pertimbangkan **Larastan** (level 4+) untuk static analysis.

**Fase 2 — Fitur bisnis terdekat (P0)**
- **KDS** (Kitchen Display System) untuk F&B: layar dapur realtime via Laravel Reverb/polling.
- **QRIS dinamis** (Midtrans/Xendit/DOKU: generate QR per transaksi + webhook verifikasi otomatis).
- **Barcode scanner** di POS — kolom `barcode` sudah ada di `products`, tinggal listening keyboard di halaman kasir.
- **ESC/POS langsung** ke thermal printer (QZ Tray / ws proxy) untuk cetak tanpa dialog print.
- Notifikasi **stok menipis** ke WhatsApp/Telegram manajer (webhook).

**Fase 3 — Skala & teknis (P1)**
- **REST API v1** (Laravel Sanctum) + aplikasi kasir Android/iOS (Flutter/React Native).
- Queue worker + Redis untuk generate PDF laporan besar; cache `config/route/view` di deploy.
- `spatie/laravel-backup` untuk backup DB + `storage` otomatis ke S3/lokal + notifikasi gagal.
- Observability: log terpusat (Grafana/Loki), uptime monitor, error tracking (Sentry).
- PWA push notification (pesanan masuk, stok habis).

**Fase 4 — Produk & skala bisnis (P2)**
- **Multi-tenant SaaS**: satu instance, banyak badan usaha (tenant resolver dari subdomain, isolasi data per `tenant_id`).
- Integrasi **akuntansi**: export jurnal ke Jurnal.id / Accurate / Klikpajak (e-Faktur).
- Program **loyalitas**: member app, tier & reward otomatis, referral.
- **Analytics**: forecasting penjualan, menu engineering (BCG matrix) dari data historis.
- Aplikasi **customer-facing** (self order via QR meja).

**Konvensi kontribusi** (ikuti `Prompts/Rules.md` & `Prompts/Pattern.md`):
1. Buat branch `feature/<nama>` → 2. migration → 3. model → 4. service → 5. controller (tipis) → 6. Form Request → 7. view (+ SPA-aware) → 8. route + permission → 9. test (wajib) → 10. `pint` → 11. update `Prompts/Changelog.md` → 12. PR ke `main`.

## 9. Testing & Kualitas

```bash
php artisan test            # 49 test, ~60 detik, database db_pos_kafeinarts_test
php artisan test --coverage # target ≥ 85% (Rules.md)
```

Test mencakup: login/logout, seluruh halaman utama (smoke test 44 halaman), checkout (stok berkurang, kembalian, voucher, stok kurang, shift belum buka), void & retur (stok kembali), shift (buka/tutup/selisih/arus kas), CRUD master + **bulk import/export/template** (per kategori, brand, satuan, pelanggan, supplier, modifier), pengaturan (user, role/permission, outlet + PJ), export PDF & Excel, serta proteksi akses per permission (403).

## 10. FAQ & Troubleshooting

| Masalah | Solusi |
|---|---|
| `419 Page Expired` | CSRF/session: cek `SESSION_DOMAIN`, bersihkan cookies, `php artisan config:clear` |
| Gambar produk tidak tampil | Jalankan `php artisan storage:link` |
| `500` saat export/import Excel | Cek `storage/logs/laravel.log`; pastikan ekstensi `zip`/`gd` aktif & folder temp writable |
| Struk PDF kosong/gagal | Naikkan `memory_limit` PHP (≥ 256M); cek `storage/logs` |
| Jam transaksi meleset | `APP_TIMEZONE=Asia/Jakarta` lalu `php artisan config:cache` |
| "Shift kasir belum dibuka" saat checkout | Buka Shift dulu (menu Shift & Kas) |
| Stok tidak berkurang setelah jual | Periksa `stock_logs`; pastikan varian/outlet dipilih benar |
| Baris import gagal | Pesan `swal_error` menyebut `Baris ke-N: alasan` — perbaiki sesuai template (heading harus sama, nilai boolean 1/0) |
| Kasir melihat menu admin | Cek role & permission (Pengaturan → Role & Akses); role Kasir dibatasi ke POS/shift/dashboard |
| Ingin reset demo | `php artisan migrate:fresh --seed` (hanya untuk development!) |

## 11. Lisensi

MIT (lihat `composer.json`). © 2026 Kafeinarts — dibuat untuk kebutuhan operasional kafe & UMKM Indonesia.
