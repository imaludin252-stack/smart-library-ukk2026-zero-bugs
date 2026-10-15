<?php

use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\Petugas\AnggotaController;
use App\Http\Controllers\Petugas\BookController;
use App\Http\Controllers\Petugas\DashboardController;
use App\Http\Controllers\Petugas\KategoriController;
use App\Http\Controllers\Petugas\LaporanController;
use App\Http\Controllers\Petugas\PenulisController;
use App\Http\Controllers\Petugas\SirkulasiController;
use App\Http\Controllers\PinjamanController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\RiwayatController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->dashboardRoute());
    }

    return redirect()->route('catalog.index');
})->name('home');

Route::view('/panduan', 'pages.panduan')->name('panduan');

// ---------- Autentikasi ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.attempt');
    Route::get('/registrasi', [RegisterController::class, 'create'])->name('register');
    Route::post('/registrasi', [RegisterController::class, 'store'])->name('register.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// ---------- Katalog publik ----------
Route::get('/katalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/katalog/{book:kode_buku}', [CatalogController::class, 'show'])->name('catalog.show');

// ---------- Area Siswa ----------
Route::middleware(['auth', 'role:siswa'])->group(function () {
    Route::get('/beranda', BerandaController::class)->name('beranda');
    Route::get('/pinjaman', [PinjamanController::class, 'index'])->name('pinjaman.index');
    Route::post('/pinjaman', [PinjamanController::class, 'store'])->name('pinjaman.store');
    Route::patch('/pinjaman/{loan}/batalkan', [PinjamanController::class, 'batalkan'])->name('pinjaman.batal');
    Route::patch('/pinjaman/{loan}/perpanjang', [PinjamanController::class, 'perpanjang'])->name('pinjaman.perpanjang');
    Route::get('/riwayat', [RiwayatController::class, 'index'])->name('riwayat.index');
    Route::get('/riwayat/bebas-pustaka', [RiwayatController::class, 'bebasPustaka'])->name('riwayat.bebas');
});

// ---------- Profil (semua peran) ----------
Route::middleware('auth')->group(function () {
    Route::get('/profil', [ProfilController::class, 'index'])->name('profil.index');
    Route::put('/profil/kata-sandi', [ProfilController::class, 'updatePassword'])->name('profil.password');
});

// ---------- Area Petugas (petugas & admin) ----------
Route::middleware(['auth', 'role:petugas,admin'])->prefix('petugas')->name('petugas.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/koleksi', [BookController::class, 'index'])->name('koleksi.index');
    Route::get('/koleksi/tambah', [BookController::class, 'create'])->name('koleksi.create');
    Route::post('/koleksi', [BookController::class, 'store'])->name('koleksi.store');
    Route::get('/koleksi/{book}/ubah', [BookController::class, 'edit'])->name('koleksi.edit');
    Route::put('/koleksi/{book}', [BookController::class, 'update'])->name('koleksi.update');
    Route::patch('/koleksi/{book}/status', [BookController::class, 'toggleStatus'])->name('koleksi.status');
    Route::delete('/koleksi/{book}', [BookController::class, 'destroy'])->name('koleksi.destroy');

    Route::get('/kategori', [KategoriController::class, 'index'])->name('kategori.index');
    Route::post('/kategori', [KategoriController::class, 'store'])->name('kategori.store');
    Route::put('/kategori/{category}', [KategoriController::class, 'update'])->name('kategori.update');
    Route::delete('/kategori/{category}', [KategoriController::class, 'destroy'])->name('kategori.destroy');

    Route::post('/penulis', [PenulisController::class, 'store'])->name('penulis.store');
    Route::put('/penulis/{author}', [PenulisController::class, 'update'])->name('penulis.update');
    Route::delete('/penulis/{author}', [PenulisController::class, 'destroy'])->name('penulis.destroy');

    Route::get('/anggota', [AnggotaController::class, 'index'])->name('anggota.index');
    Route::post('/anggota', [AnggotaController::class, 'store'])->name('anggota.store');
    Route::put('/anggota/{member}', [AnggotaController::class, 'update'])->name('anggota.update');
    Route::delete('/anggota/{member}', [AnggotaController::class, 'destroy'])->name('anggota.destroy');

    Route::get('/sirkulasi', [SirkulasiController::class, 'index'])->name('sirkulasi.index');
    Route::post('/sirkulasi/pinjam', [SirkulasiController::class, 'pinjam'])->name('sirkulasi.pinjam');
    Route::patch('/sirkulasi/{loan}/verifikasi', [SirkulasiController::class, 'verifikasi'])->name('sirkulasi.verifikasi');
    Route::patch('/sirkulasi/{loan}/tolak', [SirkulasiController::class, 'tolak'])->name('sirkulasi.tolak');
    Route::patch('/sirkulasi/{loan}/kembalikan', [SirkulasiController::class, 'kembalikan'])->name('sirkulasi.kembalikan');

    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/pdf', [LaporanController::class, 'pdf'])->name('laporan.pdf');
});

// ---------- Area Administrator ----------
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/pengguna');

    Route::get('/pengguna', [AdminUserController::class, 'index'])->name('pengguna');
    Route::post('/pengguna', [AdminUserController::class, 'store'])->name('pengguna.store');
    Route::put('/pengguna/{user}', [AdminUserController::class, 'update'])->name('pengguna.update');
    Route::patch('/pengguna/{user}/status', [AdminUserController::class, 'toggleStatus'])->name('pengguna.status');
    Route::delete('/pengguna/{user}', [AdminUserController::class, 'destroy'])->name('pengguna.destroy');

    Route::get('/pengaturan', [AdminSettingController::class, 'edit'])->name('pengaturan');
    Route::put('/pengaturan', [AdminSettingController::class, 'update'])->name('pengaturan.update');
});
