# S4-02 — PRODUCTION & STAGING DEPLOYMENT

| Item | Keterangan |
| --- | --- |
| Kode Tugas | S4-02 |
| Nama Tugas | Production & Staging Deployment |
| Proyek | Smart Library — SMK Budi Bakti Ciwidey |
| Tim | Tim Zero Bug — Firgi Faisal & Imaludin |
| Stack | Laravel 12.70, PHP 8.2.12, MySQL 8, SQLite, Nginx |
| Fokus | Deployment staging, runbook produksi, hardening, rollback |
| Tanggal | 16 November 2026 |
| Status | Staging TERVERIFIKASI — 20/20 smoke test lulus; produksi siap dijalankan sesuai runbook |
| URL staging | http://127.0.0.1:8010 |
| Environment | staging, APP_DEBUG=false, Asia/Jakarta, locale id |

## 1. Tujuan

Tugas ini menyiapkan dua jalur deployment:

1. **Staging** — lingkungan yang menyerupai produksi untuk menguji aplikasi sebelum Demo Day, tanpa menyentuh database pengembangan atau data sekolah.
2. **Produksi** — runbook terperinci untuk memasang aplikasi di server sekolah dengan MySQL, HTTPS, dan konfigurasi produksi.

Karena akses ke server sekolah belum tersedia pada saat pembekuan RC, staging dijalankan secara lokal dengan SQLite dan seluruh bukti eksekusi diambil dari lingkungan tersebut. Produksi tidak diklaim sudah online. Dokumen ini memisahkan dengan jelas mana yang **sudah dieksekusi** dan mana yang merupakan **prosedur siap pakai**.

## 2. Topologi

### Staging yang dieksekusi

```text
Browser penguji
      │ HTTP
      ▼
php artisan serve (127.0.0.1:8010)
      │
      ▼
Laravel 12 (APP_ENV=staging, APP_DEBUG=false)
      │
      ├── SQLite database/staging.sqlite
      ├── Session dan cache di database
      ├── DomPDF untuk surat dan laporan
      └── public/build hasil Vite
```

### Produksi yang ditargetkan

```text
Pengguna sekolah
      │ HTTPS
      ▼
Nginx (domain sekolah)
      │ PHP-FPM
      ▼
Laravel 12 (APP_ENV=production, APP_DEBUG=false)
      │
      ├── MySQL 8 (smart_library)
      ├── Session dan cache di database
      ├── DomPDF
      └── public/build dan public/covers
```

Tidak ada layanan WebSocket, queue worker wajib, atau object storage pada rilis ini. Queue dan cache memakai driver database, tetapi fitur inti berjalan sinkron.

## 3. Prasyarat

| Komponen | Versi minimum | Catatan |
| --- | --- | --- |
| PHP | 8.2 | Diuji pada PHP 8.2.12 |
| Ekstensi PHP | `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `dom`, `libxml` | Wajib untuk Laravel dan DomPDF |
| Composer | 2.x | Diuji pada Composer 2.10.3 |
| Node.js | 20.19+ atau 22.12+ | Untuk build aset |
| npm | 10+ | Mengikuti Node.js |
| MySQL | 8.0 | Produksi |
| SQLite | 3.x | Staging dan test |
| Web server | Nginx 1.18+ atau Apache 2.4+ | Produksi |
| Git | 2.x | Deployment dan rollback |

Periksa server:

```bash
php -v
php -m
composer --version
node -v
npm -v
mysql --version
nginx -v
```

## 4. Artefak Rilis

| Artefak | Lokasi | Keterangan |
| --- | --- | --- |
| Kode aplikasi | branch `main`, commit 2c60575 | RC v1.0.0 |
| Dependensi PHP | `composer.lock` | Versi terkunci |
| Dependensi frontend | `package-lock.json` | Versi terkunci |
| Aset produksi | `public/build/` | Hasil `npm run build` |
| Konfigurasi contoh | `.env.example` | MySQL, timezone, locale |
| Konfigurasi staging | `.env.staging` | Tidak di-commit |
| Database staging | `database/staging.sqlite` | Tidak di-commit |
| Dokumentasi | `dokument/Final/` | Dokumen dan bukti |

## 5. Deployment Staging yang Dieksekusi

### 5.1 Konfigurasi

Staging memakai berkas terpisah `.env.staging` agar tidak menimpa `.env` pengembangan. Nilai penting:

```dotenv
APP_NAME="Smart Library (Staging)"
APP_ENV=staging
APP_DEBUG=false
APP_URL=http://127.0.0.1:8010
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID

DB_CONNECTION=sqlite
DB_DATABASE=C:/Users/acera/OneDrive/Documents/imal-ukk2026/smart-library-app/database/staging.sqlite

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
MAIL_MAILER=log
```

`APP_KEY` staging diisi dengan `php artisan key:generate` dan tidak ditampilkan pada dokumen ini. Berkas `.env.staging` dan `database/staging.sqlite` sudah masuk `.gitignore`.

### 5.2 Perintah yang dijalankan

PowerShell:

```powershell
$env:APP_ENV='staging'
php artisan migrate:fresh --seed --force
php artisan optimize
php artisan serve --host=127.0.0.1 --port=8010
```

Perintah `migrate:fresh --seed` dipakai karena staging memang database terisolasi yang boleh dihapus. Pada produksi, perintah yang dipakai adalah `migrate --force`, bukan `migrate:fresh`.

### 5.3 Hasil migrasi

Seluruh 14 migrasi berstatus `Ran`:

```text
0001_01_01_000000_create_users_table ............................. Ran
0001_01_01_000001_create_cache_table ............................. Ran
0001_01_01_000002_create_jobs_table .............................. Ran
2026_10_15_000001_add_role_status_to_users_table ................. Ran
2026_10_15_000002_create_members_table ........................... Ran
2026_10_15_000003_create_categories_table ........................ Ran
2026_10_15_000004_create_authors_table ........................... Ran
2026_10_15_000005_create_books_table ............................. Ran
2026_10_15_000006_create_book_authors_table ...................... Ran
2026_10_15_000007_create_loans_table ............................. Ran
2026_10_15_000008_create_loan_items_table ........................ Ran
2026_10_15_000009_create_settings_table .......................... Ran
2026_10_16_000001_add_performance_indexes ........................ Ran
2026_11_05_000001_normalize_returned_loan_status ................. Ran
2026_11_09_000001_add_status_denda_to_loans_table ................ Ran
```

### 5.4 Hasil seeder

```text
SettingSeeder .................................................... DONE
UserSeeder ....................................................... DONE
BookSeeder ....................................................... DONE
LoanSeeder ....................................................... DONE
```

Data staging setelah seeder:

| Tabel | Jumlah |
| --- | ---: |
| users | 11 |
| members | 8 |
| categories | 7 |
| authors | 15 |
| books | 18 |
| book_authors | 21 |
| loans | 14 |
| loan_items | 14 |
| settings | 6 |

### 5.5 Optimasi

```text
INFO  Configuration cached successfully.
INFO  Routes cached successfully.
INFO  Blade templates cached successfully.
INFO  Events cached successfully.
```

### 5.6 Verifikasi environment

Output `php artisan about` pada staging:

```text
Application Name ........................................ Smart Library (Staging)
Laravel Version ..................................................... 12.70.0
PHP Version .......................................................... 8.2.12
Composer Version ..................................................... 2.10.3
Environment ......................................................... staging
Debug Mode .............................................................. OFF
URL ........................................................ 127.0.0.1:8010
Maintenance Mode ........................................................ OFF
Timezone ....................................................... Asia/Jakarta
Locale ................................................................... id
```

Empat hal yang dibuktikan:

1. Aplikasi benar-benar berjalan pada environment `staging`.
2. Debug mati sehingga stack trace tidak terbuka.
3. Zona waktu `Asia/Jakarta` aktif.
4. Locale `id` aktif.

### 5.7 Smoke test staging

Smoke test dijalankan dengan `lampiran/staging-smoke.ps1`. Skrip memakai `HttpClient` dengan cookie container, mengikuti redirect, dan memeriksa status, isi halaman, header, serta magic bytes PDF.

| No | Pemeriksaan | Hasil |
| :---: | --- | :---: |
| 1 | `GET /` mengarah ke katalog | PASS |
| 2 | `GET /katalog` merespons 200 | PASS |
| 3 | Katalog memuat judul aplikasi | PASS |
| 4 | Header `X-Content-Type-Options: nosniff` | PASS |
| 5 | Header `X-Frame-Options: SAMEORIGIN` | PASS |
| 6 | `GET /login` merespons 200 | PASS |
| 7 | Login siswa berhasil | PASS |
| 8 | `GET /beranda` merespons 200 | PASS |
| 9 | Beranda memuat nama siswa | PASS |
| 10 | `GET /pinjaman` merespons 200 | PASS |
| 11 | Tab Ditolak/Dibatalkan tampil | PASS |
| 12 | `GET /riwayat` merespons 200 | PASS |
| 13 | Bebas pustaka diblokir untuk anggota berpinjaman (RC-02) | PASS |
| 14 | Login petugas berhasil | PASS |
| 15 | `GET /petugas` merespons 200 | PASS |
| 16 | `GET /petugas/sirkulasi` merespons 200 | PASS |
| 17 | Unduh laporan PDF berhasil | PASS |
| 18 | PDF memiliki magic bytes `%PDF` | PASS |
| 19 | Login admin berhasil | PASS |
| 20 | `GET /admin/pengguna` merespons 200 | PASS |

Ringkasan:

```text
Ringkasan: 20 lulus, 0 gagal
```

### 5.8 Bukti pendukung

| Berkas | Isi |
| --- | --- |
| `lampiran/staging-migrate-seed.log` | Log migrasi dan seeder |
| `lampiran/staging-optimize.log` | Log cache konfigurasi, route, view, event |
| `lampiran/staging-about.txt` | Output `php artisan about` |
| `lampiran/staging-smoke.ps1` | Skrip smoke test yang dapat dijalankan ulang |
| `lampiran/staging-smoke.log` | Hasil 20/20 PASS |
| `lampiran/migrate-status.txt` | Status migrasi |
| `lampiran/route-list.txt` | 57 rute pada staging |

## 6. Runbook Produksi

Bagian ini adalah prosedur yang harus dijalankan pada server sekolah. Perintah belum dieksekusi karena akses server belum tersedia; jangan menandainya selesai sebelum benar-benar dijalankan.

### 6.1 Siapkan server

Contoh Ubuntu 22.04/24.04:

```bash
sudo apt update
sudo apt install -y nginx mysql-server php8.2-fpm php8.2-cli php8.2-mysql \
  php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-gd \
  php8.2-bcmath php8.2-intl unzip git
```

Pasang Composer dan Node.js sesuai dokumentasi resmi, lalu pastikan versi:

```bash
php -v
composer --version
node -v
```

### 6.2 Buat database dan pengguna MySQL

```sql
CREATE DATABASE smart_library
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER 'smartlib'@'localhost' IDENTIFIED BY 'GANTI_DENGAN_SANDI_KUAT';
GRANT ALL PRIVILEGES ON smart_library.* TO 'smartlib'@'localhost';
FLUSH PRIVILEGES;
```

Jangan memakai `root` tanpa kata sandi di produksi. Simpan kata sandi di pengelola rahasia, bukan di repository.

### 6.3 Unggah kode

```bash
sudo mkdir -p /var/www/smart-library
sudo chown -R $USER:www-data /var/www/smart-library
git clone <URL-REPOSITORI> /var/www/smart-library
cd /var/www/smart-library
git checkout main
git pull origin main
```

Jika server tidak memiliki akses Git, unggah arsip rilis dan ekstrak ke direktori yang sama.

### 6.4 Instal dependensi dan build

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Build dilakukan di server atau di mesin build lalu `public/build` diunggah. Jangan mengunggah `node_modules`.

### 6.5 Konfigurasi environment produksi

```bash
cp .env.example .env
php artisan key:generate
```

Isi `.env` produksi:

```dotenv
APP_NAME="Smart Library"
APP_ENV=production
APP_KEY=base64:HASIL_KEY_GENERATE
APP_DEBUG=false
APP_URL=https://perpustakaan.smkbudibakti.sch.id
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smart_library
DB_USERNAME=smartlib
DB_PASSWORD=GANTI_DENGAN_SANDI_KUAT

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=database
MAIL_MAILER=log
MAIL_FROM_ADDRESS="perpustakaan@smkbudibakti.sch.id"
MAIL_FROM_NAME="${APP_NAME}"

LOG_CHANNEL=stack
LOG_LEVEL=warning
```

Catatan:

- `APP_DEBUG=false` wajib.
- `SESSION_SECURE_COOKIE=true` hanya jika HTTPS sudah aktif.
- Jangan menyalin `APP_KEY` staging ke produksi.
- Jangan commit `.env`.

### 6.6 Migrasi

```bash
php artisan migrate --force
```

Untuk server demo yang memang membutuhkan data contoh:

```bash
php artisan db:seed --force
```

Untuk produksi dengan data nyata, **jangan** menjalankan `migrate:fresh` atau seeder demo. Impor data anggota dan buku melalui aplikasi atau seeder khusus yang sudah dibersihkan.

### 6.7 Permission

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R ug+rwX storage bootstrap/cache
sudo chmod -R 755 public
```

Aplikasi menulis sampul ke `public/covers`. Pastikan direktori tersebut dapat ditulis oleh pengguna web server:

```bash
sudo mkdir -p public/covers
sudo chown -R www-data:www-data public/covers
```

### 6.8 Optimasi produksi

```bash
php artisan optimize
```

Atau terpisah:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Setelah mengubah `.env` atau kode:

```bash
php artisan optimize:clear
php artisan optimize
```

### 6.9 Nginx

```nginx
server {
    listen 80;
    server_name perpustakaan.smkbudibakti.sch.id;
    root /var/www/smart-library/public;

    index index.php;
    charset utf-8;

    client_max_body_size 3M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ { return 404; }

    location ~ /\.(?!well-known).* { deny all; }
}
```

Aktifkan dan uji:

```bash
sudo ln -s /etc/nginx/sites-available/smart-library /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 6.10 HTTPS

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d perpustakaan.smkbudibakti.sch.id
```

Setelah HTTPS aktif, ubah `APP_URL` ke `https://...` dan `SESSION_SECURE_COOKIE=true`, lalu:

```bash
php artisan optimize:clear
php artisan optimize
```

### 6.11 Health check

Laravel menyediakan endpoint `/up`. Uji dari server:

```bash
curl -fsS https://perpustakaan.smkbudibakti.sch.id/up || echo "DOWN"
curl -I https://perpustakaan.smkbudibakti.sch.id/katalog
```

Yang harus terlihat:

- `/up` merespons 200.
- `/katalog` merespons 200.
- Header keamanan muncul.
- Tidak ada stack trace pada halaman error.

## 7. Hardening Produksi

| Kontrol | Tindakan | Status target |
| --- | --- | --- |
| Debug | `APP_DEBUG=false`, `APP_ENV=production` | Wajib |
| Kata sandi demo | Ganti atau nonaktifkan seluruh akun demo | Wajib |
| HTTPS | Sertifikat valid dan redirect HTTP ke HTTPS | Wajib |
| Cookie | `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true` | Wajib bila HTTPS |
| Database | Pengguna khusus, bukan root; akses dibatasi localhost | Wajib |
| File `.env` | Permission 600, tidak dapat diakses dari web | Wajib |
| Direktori | `storage` dan `bootstrap/cache` hanya dapat ditulis web server | Wajib |
| Header | Middleware SecurityHeaders aktif | Sudah ada |
| Rate limit | Login 5 percobaan per menit | Sudah ada |
| Upload | MIME asli, ukuran 2 MB, nama acak | Sudah ada |
| Log | `LOG_LEVEL=warning`, rotasi log | Disarankan |
| Backup | Dump MySQL harian, simpan di luar server | Wajib |
| Monitoring | Cek `/up` dan disk setiap 5 menit | Disarankan |
| Update | Patch PHP, Laravel, dan dependensi berkala | Disarankan |

Perintah permission berkas rahasia:

```bash
sudo chmod 600 /var/www/smart-library/.env
sudo chown www-data:www-data /var/www/smart-library/.env
```

## 8. Backup dan Restore

### Backup

```bash
mysqldump --single-transaction --routines --triggers \
  -u smartlib -p smart_library > backup-smart-library-$(date +%F).sql
```

Simpan juga:

```bash
tar -czf smart-library-files-$(date +%F).tar.gz \
  public/covers storage/app public/build .env
```

Simpan minimal 7 salinan harian dan 4 salinan mingguan di lokasi terpisah.

### Restore

```bash
mysql -u smartlib -p smart_library < backup-smart-library-2026-11-16.sql
php artisan optimize:clear
php artisan optimize
```

Uji restore di staging sebelum benar-benar dibutuhkan.

## 9. Rollback

### Rollback kode

```bash
cd /var/www/smart-library
git fetch --all
git log --oneline -5
git checkout <commit-stabil>
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan optimize:clear
php artisan optimize
```

### Rollback migrasi

```bash
php artisan migrate:rollback --step=1
```

Rollback migrasi hanya aman bila perubahan skema terakhir memang reversibel. Untuk perubahan yang menghapus kolom atau mengubah data, restore dari backup lebih aman.

### Rollback darurat saat Demo Day

1. Hentikan server staging.
2. Jalankan `php artisan migrate:fresh --seed --force` pada database staging.
3. Jalankan `php artisan optimize:clear`.
4. Jalankan `php artisan serve --host=127.0.0.1 --port=8010`.
5. Buka halaman login dan pastikan akun demo dapat masuk.

## 10. Troubleshooting

| Gejala | Penyebab umum | Solusi |
| --- | --- | --- |
| 500 setelah deploy | `.env`, `APP_KEY`, atau permission | Baca `storage/logs/laravel.log`, jalankan `key:generate`, perbaiki permission |
| CSS/JS 404 | `public/build` tidak ikut terunggah | Jalankan `npm run build`, pastikan `public/build/manifest.json` ada |
| Halaman blank | Cache config lama | `php artisan optimize:clear` lalu `optimize` |
| Login gagal | Tabel `sessions` belum ada | Jalankan migrasi |
| Upload sampul gagal | `public/covers` tidak writable | Buat direktori dan ubah owner |
| PDF error | Ekstensi `dom`, `xml`, atau `mbstring` tidak aktif | Aktifkan ekstensi PHP |
| Laporan kosong | Periode tidak memuat transaksi | Pilih periode yang benar |
| 419 Page Expired | CSRF token kedaluwarsa | Muat ulang halaman login |
| 429 Too Many Requests | Rate limit login | Tunggu satu menit |
| 403 saat buka surat bebas pustaka | Masih ada pinjaman, keterlambatan, atau denda | Selesaikan kewajiban anggota |
| Error SQLite `HAVING` | Menjalankan kode lama di staging | Pastikan commit 08b1c13 sudah aktif |

## 11. Checklist Go-Live

| No | Item | Bukti |
| :---: | --- | --- |
| 1 | Database produksi dibuat dan diuji koneksi | `php artisan db:show` |
| 2 | Migrasi berhasil | `php artisan migrate:status` |
| 3 | Seeder demo tidak dijalankan pada data nyata | Catatan deployment |
| 4 | `APP_ENV=production`, `APP_DEBUG=false` | `php artisan about` |
| 5 | `APP_URL` dan HTTPS benar | Buka domain |
| 6 | Aset Vite tampil | Halaman katalog |
| 7 | Login tiga peran berhasil | Uji manual |
| 8 | PDF surat dan laporan dapat diunduh | Uji manual |
| 9 | Akun demo dinonaktifkan | Halaman admin |
| 10 | Backup pertama dibuat | Berkas dump |
| 11 | Monitoring `/up` aktif | Log monitoring |
| 12 | Rollback diuji di staging | Catatan uji |

## 12. Definition of Done

| Kriteria DoD | Status | Bukti |
| --- | :---: | --- |
| Staging dapat diakses dan berjalan | Terpenuhi | `http://127.0.0.1:8010` |
| Staging memakai environment dan debug yang benar | Terpenuhi | `staging-about.txt` |
| Migrasi dan seeder staging berhasil | Terpenuhi | `staging-migrate-seed.log` |
| Smoke test staging lulus | Terpenuhi | 20/20 PASS |
| Aset produksi dibangun | Terpenuhi | `public/build` |
| Runbook produksi tersedia | Terpenuhi | Bagian 6–11 |
| Hardening dan rollback terdokumentasi | Terpenuhi | Bagian 7–9 |
| Produksi online | Belum dieksekusi | Menunggu akses server sekolah |

## 13. Kesimpulan

Staging Smart Library berhasil di-deploy secara lokal dengan konfigurasi terpisah, database SQLite, debug mati, zona waktu `Asia/Jakarta`, dan 20 smoke test yang seluruhnya lulus. Bukti eksekusi tersimpan lengkap di `dokument/Final/lampiran/`.

Produksi belum diklaim online. Runbook produksi, konfigurasi Nginx, hardening, backup, rollback, dan checklist go-live sudah disiapkan sehingga deployment ke server sekolah dapat dilakukan tanpa menebak langkah. Batasan ini dicatat secara terbuka agar dokumen tetap jujur dan dapat diaudit.
