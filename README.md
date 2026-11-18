# Smart Library — Sistem Informasi Perpustakaan Sekolah

**Tim Zero Bug** ·  Imaludin  
SMK Budi Bakti Ciwidey · PPLG/RPL · UKK 2026/2027

Smart Library adalah aplikasi perpustakaan sekolah berbasis web untuk mengelola katalog, anggota, peminjaman, pengembalian, denda, dan laporan sirkulasi dalam satu alur kerja. Aplikasi ini menggantikan pencatatan manual yang rawan duplikasi, kehilangan riwayat, dan kesalahan rekap.

## Daftar Isi

- [Latar Belakang](#latar-belakang)
- [Sekilas Produk](#sekilas-produk)
- [Fitur Utama](#fitur-utama)
- [Peran Pengguna](#peran-pengguna)
- [Matriks Teknologi](#matriks-teknologi)
- [Akun Demo](#akun-demo)
- [Panduan Instalasi Lokal](#panduan-instalasi-lokal)
- [Konfigurasi Environment](#konfigurasi-environment)
- [Menjalankan Aplikasi](#menjalankan-aplikasi)
- [Pengujian dan Kualitas](#pengujian-dan-kualitas)
- [Staging dan Deployment](#staging-dan-deployment)
- [Struktur Proyek](#struktur-proyek)
- [Aturan Bisnis](#aturan-bisnis)
- [Keamanan](#keamanan)
- [Keterbatasan yang Diketahui](#keterbatasan-yang-diketahui)
- [Dokumentasi](#dokumentasi)
- [Tim](#tim)
- [Lisensi](#lisensi)

## Latar Belakang

Perpustakaan SMK Budi Bakti Ciwidey melayani kebutuhan literasi dan referensi praktikum seluruh program keahlian. Sebelumnya, peminjaman dicatat pada buku agenda, status keterlambatan dihitung manual, dan rekap bulanan disusun ulang dari catatan tersebut. Pola ini menimbulkan beberapa masalah:

- stok buku tidak selalu mencerminkan jumlah eksemplar yang benar-benar tersedia;
- satu siswa dapat tercatat meminjam buku yang sama lebih dari sekali;
- keterlambatan dan denda sulit ditelusuri kembali;
- laporan per kelas, per jurusan, dan per periode membutuhkan waktu lama;
- riwayat transaksi berisiko hilang ketika data buku atau anggota dihapus.

Smart Library menjawab masalah tersebut dengan satu sumber data. Setiap pengajuan, verifikasi, pengembalian, denda, dan laporan dibaca dari tabel transaksi yang sama. Aturan stok, batas pinjam, keterlambatan, dan hak akses ditegakkan di sisi server, bukan hanya di tampilan.

## Sekilas Produk

| Aspek | Keterangan |
| --- | --- |
| Nama produk | Smart Library |
| Jenis | Sistem informasi perpustakaan sekolah berbasis web |
| Pengguna | Siswa, petugas perpustakaan, administrator |
| Alur inti | Cari buku → ajukan pinjam → verifikasi petugas → pinjam → kembalikan → denda → laporan |
| Antarmuka | Blade + Tailwind CSS 4, mobile-first, bahasa Indonesia |
| Database | MySQL/MariaDB untuk produksi dan pengembangan, SQLite untuk staging dan pengujian |
| Laporan | PDF A4 melalui DomPDF |
| Status rilis | Release Candidate v1.0.0 — 10 temuan RC ditutup, 54 test lulus, 20/20 smoke test |
| Staging | http://127.0.0.1:8010 (lokal, SQLite) |
| Repositori | Git, branch `main` |

## Fitur Utama

### Untuk siswa

- Registrasi akun dan login multi-peran.
- Beranda dengan Ringkasan pinjaman aktif, jatuh tempo terdekat, riwayat selesai, dan status denda.
- Katalog dengan pencarian judul, penulis, kategori, ISBN, kode buku, filter jurusan, dan status ketersediaan.
- Detail buku dengan stok, lokasi rak, sinopsis, dan status ketersediaan.
- Pengajuan peminjaman dengan validasi stok, batas pinjam, keterlambatan, dan duplikasi.
- Tab peminjaman: Semua, Sedang Dipinjam, Menunggu Verifikasi, Terlambat, Selesai, Ditolak/Dibatalkan.
- Pembatalan pengajuan dan perpanjangan satu kali.
- Riwayat pengembalian dengan filter judul, status, dan rentang tanggal.
- Unduh surat bebas pustaka PDF bila tidak ada pinjaman aktif, keterlambatan, atau denda belum lunas.
- Profil dan perubahan kata sandi.

### Untuk petugas perpustakaan

- Dashboard sirkulasi: permohonan menunggu verifikasi, pinjaman aktif, jatuh tempo, keterlambatan, dan distribusi koleksi.
- CRUD koleksi buku, kategori, dan penulis.
- Unggah sampul dengan validasi MIME, ukuran, dan nama acak.
- Manajemen data anggota siswa.
- Meja sirkulasi: verifikasi permohonan, tolak dengan alasan, catat peminjaman manual, dan proses pengembalian.
- Perhitungan denda otomatis berdasarkan tarif per hari.
- Rekap denda dengan filter status dan pembaruan status lunas.
- Laporan sirkulasi: metrik, tren 6 bulan, buku terpopuler, rekap kelas, distribusi jurusan.
- Unduh PDF transaksi, buku terlambat, dan buku terpopuler.

### Untuk administrator

- Manajemen akun petugas dan administrator.
- Aktivasi/nonaktivasi akun.
- Perlindungan akun sendiri dari penghapusan atau penonaktifan.
- Konfigurasi sistem: durasi pinjam, batas pinjam aktif, tarif denda, identitas sekolah, tahun ajaran, dan jam buka.
- Akses seluruh menu petugas.

## Modul Denda

Menu **Denda** tersedia pada sidebar petugas dan menampilkan transaksi yang sudah dikembalikan dengan nominal denda lebih dari nol.

| Kolom | Isi |
| --- | --- |
| Anggota | Nama anggota, NISN, dan kode transaksi |
| Kelas | Kelas anggota |
| Judul Buku | Judul dan kode buku |
| Tanggal Kembali | Tanggal pengembalian |
| Denda | Nominal rupiah |
| Status | Belum Lunas atau Lunas beserta tanggal pembayaran |
| Aksi | Tombol Edit untuk mengubah nominal dan status |

Perilaku utama:

- Kartu metrik menampilkan total denda, denda belum lunas, denda lunas, dan jumlah transaksi.
- Pencarian mencakup nama anggota, NISN, kelas, judul buku, dan kode transaksi.
- Filter status membatasi tampilan pada Belum Lunas atau Lunas.
- Modal edit memvalidasi nominal 0–100.000.000 dan status pembayaran.
- Status Lunas otomatis mengisi tanggal pembayaran; pembatalan status Lunas mengosongkan tanggal tersebut.
- Denda belum lunas menahan penerbitan Surat Bebas Pustaka.

## Peran Pengguna

| Peran | Halaman awal | Hak akses utama |
| --- | --- | --- |
| Siswa | `/beranda` | Katalog, pengajuan pinjam, pembatalan, perpanjangan, riwayat, surat bebas pustaka, profil |
| Petugas | `/petugas` | Dashboard, koleksi, kategori/penulis, anggota, sirkulasi, denda, laporan |
| Administrator | `/admin/pengguna` | Seluruh menu petugas, manajemen pengguna, konfigurasi sistem |

Pembatasan akses diterapkan berlapis: middleware `auth`, middleware `role`, dan `LoanPolicy` untuk kepemilikan transaksi. Siswa tidak dapat membuka atau mengubah transaksi milik siswa lain, meskipun mengetahui URL-nya.

## Matriks Teknologi

| Lapisan | Teknologi | Digunakan untuk |
| --- | --- | --- |
| Bahasa | PHP 8.2+ | Logika aplikasi |
| Framework | Laravel 12.70 | Routing, MVC, validasi, session, ORM, testing |
| Tampilan | Blade, Tailwind CSS 4, JavaScript vanilla | Antarmuka responsif dan komponen |
| Build | Vite 7 | Bundling CSS/JS produksi |
| Database produksi | MySQL 8 / MariaDB 10.4 | Data master dan transaksi |
| Database staging/testing | SQLite | Deployment lokal dan test suite |
| ORM | Eloquent | Relasi, scope, transaksi database |
| PDF | barryvdh/laravel-dompdf 3.1 | Surat bebas pustaka dan laporan |
| Pengujian | PHPUnit 11.5 | Test fungsional dan regresi |
| Versi kontrol | Git | Riwayat perubahan dan audit commit |

## Akun Demo

Semua akun demo menggunakan kata sandi `password`. Akun ini hanya untuk lokal, staging, dan Demo Day. Ganti atau nonaktifkan sebelum produksi.

| Peran | Email | Halaman awal |
| --- | --- | --- |
| Administrator | `admin@smartlibrary.sch.id` | `/admin/pengguna` |
| Petugas | `petugas@smartlibrary.sch.id` | `/petugas` |
| Petugas 2 | `petugas2@smartlibrary.sch.id` | `/petugas` |
| Siswa | `rizky@siswa.smkbb.sch.id` | `/beranda` |
| Siswa | `aisyah@siswa.smkbb.sch.id` | `/beranda` |
| Siswa | `fajar@siswa.smkbb.sch.id` | `/beranda` |
| Siswa | `dewi@siswa.smkbb.sch.id` | `/beranda` |

Data seed mencakup 11 pengguna, 8 anggota siswa, 7 kategori, 16 penulis, 18 buku, dan 19 transaksi contoh dengan status yang beragam; 8 transaksi di antaranya memiliki denda.

## Panduan Instalasi Lokal

### 1. Prasyarat

- PHP 8.2 atau lebih baru dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `dom`, `libxml`.
- Composer 2.x.
- Node.js 20.19+ atau 22.12+ dan npm.
- MySQL 8 untuk produksi. SQLite cukup untuk mencoba aplikasi tanpa MySQL.
- Git.

Periksa versi:

```bash
php -v
composer --version
node -v
npm -v
```

### 2. Clone dan instal dependensi

```bash
git clone <URL-REPOSITORI> smart-library-app
cd smart-library-app
composer install
npm install
```

### 3. Siapkan environment

```bash
cp .env.example .env
php artisan key:generate
```

Sesuaikan bagian database pada `.env`:

```dotenv
APP_NAME="Smart Library"
APP_ENV=local
APP_DEBUG=false
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smart_library
DB_USERNAME=root
DB_PASSWORD=
```

Buat database `smart_library` di MySQL, lalu jalankan migrasi dan seeder:

```bash
php artisan migrate --seed
```

Untuk mencoba tanpa MySQL, ubah:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/path/absolut/ke/database/database.sqlite
```

Lalu buat berkas dan jalankan migrasi:

```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
```

### 4. Build aset frontend

```bash
npm run build
```

Untuk pengembangan:

```bash
npm run dev
```

### 5. Jalankan server

```bash
php artisan serve
```

Buka `http://127.0.0.1:8000`. Halaman root akan mengarahkan pengunjung ke katalog publik.

## Konfigurasi Environment

| Variabel | Nilai contoh | Keterangan |
| --- | --- | --- |
| `APP_NAME` | `Smart Library` | Nama aplikasi pada tampilan dan PDF |
| `APP_ENV` | `local` / `staging` / `production` | Lingkungan aktif |
| `APP_DEBUG` | `false` | Wajib `false` di staging dan produksi |
| `APP_URL` | `http://127.0.0.1:8000` | URL dasar aplikasi |
| `APP_TIMEZONE` | `Asia/Jakarta` | Zona waktu tanggal, jatuh tempo, dan denda |
| `APP_LOCALE` | `id` | Bahasa antarmuka dan format tanggal |
| `DB_CONNECTION` | `mysql` / `sqlite` | Driver database |
| `SESSION_DRIVER` | `database` | Sesi disimpan di tabel `sessions` |
| `CACHE_STORE` | `database` | Cache pengaturan dan konfigurasi |
| `QUEUE_CONNECTION` | `database` | Antrean tidak dipakai untuk fitur inti |
| `MAIL_MAILER` | `log` | Email belum dikirim ke SMTP pada rilis ini |

Setelah mengubah `.env`, bersihkan cache:

```bash
php artisan optimize:clear
```

## Menjalankan Aplikasi

### Alur siswa

1. Login di `/login` atau registrasi di `/registrasi`.
2. Buka `/katalog`, cari buku, lalu buka detail.
3. Klik **Ajukan Pinjam**, periksa detail, lalu konfirmasi.
4. Pantau status di `/pinjaman`.
5. Setelah petugas memverifikasi, buku berstatus **Sedang Dipinjam**.
6. Ajukan perpanjangan satu kali bila belum terlambat.
7. Setelah dikembalikan, unduh surat bebas pustaka di `/riwayat` bila memenuhi syarat.

### Alur petugas

1. Login dan buka `/petugas`.
2. Buka **Meja Sirkulasi** untuk melihat permohonan.
3. Klik **Serahkan Buku** untuk verifikasi atau **Tolak** dengan alasan.
4. Catat peminjaman manual bila siswa datang langsung ke meja.
5. Proses pengembalian, isi denda dan kondisi buku.
6. Kelola denda di `/petugas/denda`.
7. Unduh laporan di `/petugas/laporan`.

### Alur administrator

1. Login dan buka `/admin/pengguna`.
2. Tambah, ubah, aktifkan/nonaktifkan akun petugas.
3. Buka **Konfigurasi Sistem** untuk mengubah durasi pinjam, batas pinjam, tarif denda, dan identitas sekolah.

## Pengujian dan Kualitas

Jalankan seluruh test:

```bash
php artisan test
```

Hasil rilis:

```text
Tests:    54 passed (178 assertions)
Duration: 6.62s
```

Cakupan utama:

| Area | Yang diuji |
| --- | --- |
| Autentikasi dan role | Login siswa/petugas/admin, kata sandi salah, SQL injection, akses lintas peran |
| Katalog | Pencarian, filter, detail, kode tidak dikenal |
| Peminjaman | Pengajuan, stok habis, batas pinjam, keterlambatan, duplikasi, pembatalan |
| Sirkulasi | Verifikasi, penolakan, peminjaman manual, pengembalian, denda |
| Denda | Rekap, pembaruan status, nominal negatif, akses siswa |
| Laporan | PDF transaksi, parameter tidak valid, periode terlambat, buku populer |
| Admin | Pembuatan akun, email ganda, penghapusan akun berriwayat |
| Keamanan | XSS, header keamanan, unggahan PHP, rate limit login |
| Regresi RC | 11 test untuk RC-01 sampai RC-10 |

Test regresi RC berada di `tests/Feature/Sprint4/ReleaseCandidateTest.php`. Test fungsional Sprint 3 berada di `tests/Feature/Sprint3/FunctionalTest.php`.

### Perintah test yang sering dipakai

```bash
php artisan test --filter=ReleaseCandidateTest
php artisan test --filter=test_surat_bebas_pustaka_diblokir_saat_masih_ada_pinjaman_aktif
php artisan test --filter=test_laporan_pdf_dapat_diunduh_dan_parameter_tidak_valid_dinormalisasi
```

## Staging dan Deployment

### Staging lokal

Staging dijalankan terpisah dari database pengembangan:

```bash
$env:APP_ENV='staging'
php artisan migrate:fresh --seed --force
php artisan optimize
php artisan serve --host=127.0.0.1 --port=8010
```

Konfigurasi staging memakai SQLite `database/staging.sqlite`, `APP_ENV=staging`, `APP_DEBUG=false`, dan `APP_TIMEZONE=Asia/Jakarta`. Konfigurasi ini tidak di-commit karena berisi kunci aplikasi.

Hasil verifikasi staging:

- 15 migrasi berstatus `Ran`.
- Seeder berhasil dijalankan.
- 20 dari 20 smoke test lulus.
- Halaman staging menampilkan environment `staging`, timezone `Asia/Jakarta`, locale `id`.
- Respons PDF memiliki magic bytes `%PDF`.

### Produksi

Produksi ditargetkan pada server sekolah dengan MySQL 8, PHP 8.2+, Nginx/Apache, dan HTTPS. Langkah lengkap, konfigurasi Nginx, hardening, backup, dan rollback didokumentasikan pada arsip Sprint 4 di luar repositori.

Ringkasan perintah produksi:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
```

Pada produksi:

- set `APP_ENV=production` dan `APP_DEBUG=false`;
- gunakan MySQL, bukan SQLite staging;
- ganti kata sandi akun demo;
- aktifkan HTTPS dan `SESSION_SECURE_COOKIE=true` bila memakai HTTPS;
- backup database sebelum migrasi;
- jalankan `php artisan optimize:clear` setelah mengubah `.env`.

## Struktur Proyek

```text
smart-library-app/
├── app/
│   ├── Http/Controllers/          # Controller siswa, petugas, admin, auth
│   ├── Http/Middleware/           # EnsureRole, SecurityHeaders
│   ├── Models/                    # User, Member, Book, Loan, LoanItem, ...
│   ├── Policies/                  # LoanPolicy (anti-IDOR)
│   └── Providers/                 # Rate limiter login, policy binding
├── bootstrap/app.php              # Middleware dan routing
├── config/                        # Konfigurasi Laravel
├── database/
│   ├── migrations/                # 15 migrasi
│   ├── seeders/                   # Setting, User, Book, Loan
│   └── staging.sqlite             # Database staging (tidak di-commit)
├── public/
│   ├── build/                     # Hasil Vite
│   └── covers/                    # Sampul buku
├── resources/
│   ├── css/                       # Tailwind
│   ├── js/                        # Interaksi UI
│   └── views/                     # Blade: siswa, petugas, admin, auth, PDF
├── routes/web.php                 # 54 rute aplikasi + 3 infrastruktur
├── storage/                       # Log dan cache
├── tests/
│   ├── Feature/Sprint3/           # 36 test fungsional
│   ├── Feature/Sprint4/           # 11 test regresi RC
│   └── Feature/DendaTest.php      # 5 test denda
├── .env.example
├── composer.json
├── package.json
└── README.md
```

## Aturan Bisnis

| ID | Aturan | Implementasi |
| --- | --- | --- |
| BR-01 | Buku hanya dapat dipinjam jika aktif dan stok tersedia | `Book::isTersedia()`, `PinjamanController::store` |
| BR-02 | Setiap transaksi memiliki kode unik, anggota, buku, tanggal pinjam, dan jatuh tempo | `Loan::generateKodeTransaksi()`, tabel `loans` dan `loan_items` |
| BR-03 | Durasi pinjam default 7 hari dan dapat diubah administrator | `Setting::get('durasi_pinjam')` |
| BR-04 | Maksimal 3 pinjaman aktif per siswa | `Setting::get('batas_pinjam_aktif')`, `Member::pinjamanAktif()` |
| BR-05 | Transaksi melewati jatuh tempo berstatus Terlambat | `Loan::segarkanKeterlambatan()` |
| BR-06 | Denda dihitung dari hari terlambat dikali tarif harian | `Loan::hitungDenda()` |
| BR-07 | Pengembalian menutup transaksi dan mengembalikan stok | `Loan::kembalikan()`, status `dikembalikan` |
| BR-08 | Buku dengan transaksi aktif atau riwayat tidak dapat dihapus | `BookController::destroy()` |
| BR-09 | Siswa terlambat tidak dapat mengajukan pinjaman baru | `Member::punyaKeterlambatan()`, `PinjamanController::store` |

Aturan tambahan hasil RC:

- Denda yang belum lunas menahan surat bebas pustaka.
- Satu anggota tidak boleh memiliki dua transaksi aktif untuk buku yang sama.
- Stok tidak boleh diturunkan di bawah jumlah eksemplar yang sedang dipinjam.
- Pengajuan yang ditolak atau dibatalkan tetap terlihat di tab khusus.

## Keamanan

- Kata sandi di-hash dengan bcrypt.
- Login dibatasi 5 percobaan per menit per kombinasi email dan IP.
- Session ID diregenerasi setelah login.
- Middleware `EnsureRole` menolak akses lintas peran.
- `LoanPolicy` mencegah IDOR pada transaksi siswa.
- Semua input divalidasi di server.
- Output Blade di-escape, termasuk judul halaman.
- Unggahan sampul dibatasi tipe MIME asli, ukuran 2 MB, dan nama acak.
- Header keamanan: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `X-Permitted-Cross-Domain-Policies`.
- Halaman error 403, 404, 419, 429, 500, dan 503 tersedia.
- `APP_DEBUG=false` pada staging dan produksi.

## Keterbatasan yang Diketahui

- Aplikasi ini **server-rendered** dengan Blade. Tidak ada REST API JSON publik. Dokumen API menjelaskan endpoint web dan kontrak data yang setara.
- Staging rilis ini berjalan lokal dengan SQLite. Produksi ditargetkan memakai MySQL di server sekolah dan belum memiliki URL publik pada saat pembekuan RC.
- Email notifikasi belum dikirim; `MAIL_MAILER=log`.
- Antrean (`QUEUE_CONNECTION=database`) belum dipakai untuk proses latar belakang.
- Satu transaksi pada antarmuka saat ini memuat satu buku. Skema `loan_items` sudah mendukung banyak buku per transaksi.
- Akun demo memakai kata sandi statis dan wajib diganti sebelum produksi.
- Belum ada integrasi barcode scanner perangkat keras; kolom pencarian menerima input kode buku/ISBN secara manual.

## Dokumentasi

Dokumentasi Sprint 4, PRD, desain UI/UX, screenshot demo, dan log pengujian disimpan di luar repositori agar repositori tetap ringkas. Arsip tersebut mencakup:

- S4-01 Final Bug Fixing & Release Candidate
- S4-02 Production & Staging Deployment
- S4-03 Technical Docs
- S4-04 Repository README
- S4-05 API Documentation
- S4-08 Persiapan Pertanggungjawaban Individu
- PRD, desain UI/UX, screenshot demo, dan lampiran pengujian

README ini adalah satu-satunya dokumen proyek yang disimpan di repositori.

## Tim

| Nama | Peran | Tanggung jawab |
| --- | --- | --- |
| imaludin | Frontend & UI/UX | Wireframe, Blade, Tailwind, komponen, responsif, usability, integrasi tampilan |
| Imaludin | Backend & Database | ERD, migrasi, controller, model, policy, validasi, keamanan, testing, deployment |

Pembimbing: guru produktif PPLG/RPL SMK Budi Bakti Ciwidey.  
Mitra: perpustakaan SMK Budi Bakti Ciwidey.

## Lisensi

Proyek ini dilisensikan di bawah MIT License. Lihat `composer.json` untuk identitas paket.

© 2026 Tim Zero Bug — SMK Budi Bakti Ciwidey.
