<?php

namespace Tests\Feature\Sprint3;

use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * S3-03 — Pengujian fungsional alur utama Smart Library.
 *
 * Setiap skenario memetakan kebutuhan (BR) dan risiko yang diuji:
 * autentikasi, otorisasi/IDOR, katalog, sirkulasi, validasi, keamanan,
 * rendering PDF, dan regresi bug Sprint 3.
 */
class FunctionalTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithLibrary;

    // ------------------------------------------------------------------
    // Autentikasi & Otorisasi (S3-01)
    // ------------------------------------------------------------------

    public function test_login_siswa_berhasil_diarahkan_ke_beranda(): void
    {
        [$user] = $this->buatSiswa(['email' => 'siswa@example.test']);

        $response = $this->post(route('login.attempt'), [
            'email' => 'siswa@example.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('beranda'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_dengan_kata_sandi_salah_ditolak(): void
    {
        $this->buatSiswa(['email' => 'siswa@example.test']);

        $response = $this->post(route('login.attempt'), [
            'email' => 'siswa@example.test',
            'password' => 'kata-sandi-salah',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_dengan_payload_sql_injection_tidak_membobol_autentikasi(): void
    {
        $this->buatSiswa(['email' => 'siswa@example.test']);

        $response = $this->post(route('login.attempt'), [
            'email' => "' OR '1'='1",
            'password' => "' OR '1'='1",
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_tamu_diarahkan_ke_halaman_login_saat_mengakses_area_siswa(): void
    {
        $this->get(route('pinjaman.index'))->assertRedirect(route('login'));
    }

    public function test_siswa_tidak_dapat_mengakses_area_petugas(): void
    {
        [$user] = $this->buatSiswa();

        $this->actingAs($user)
            ->get(route('petugas.dashboard'))
            ->assertForbidden();
    }

    public function test_petugas_tidak_dapat_mengakses_area_administrator(): void
    {
        $petugas = $this->buatPetugas();

        $this->actingAs($petugas)
            ->get(route('admin.pengguna'))
            ->assertForbidden();
    }

    public function test_siswa_tidak_dapat_membatalkan_pinjaman_milik_siswa_lain(): void
    {
        [$pemilik, $memberPemilik] = $this->buatSiswa();
        [$penyusup] = $this->buatSiswa();
        $book = $this->buatBuku();
        $loan = $this->buatPinjaman($memberPemilik, $book);

        $this->actingAs($penyusup)
            ->patch(route('pinjaman.batal', $loan))
            ->assertForbidden();

        $this->assertDatabaseHas('loans', [
            'id_peminjaman' => $loan->id_peminjaman,
            'status' => Loan::STATUS_DIAJUKAN,
        ]);
    }

    // ------------------------------------------------------------------
    // Katalog publik
    // ------------------------------------------------------------------

    public function test_pencarian_katalog_menyaring_judul_buku(): void
    {
        $kategori = Category::factory()->create();

        $this->buatBuku([
            'judul' => 'Algoritma Dasar Pemrograman',
            'id_kategori' => $kategori->id_kategori,
        ]);
        $this->buatBuku([
            'judul' => 'Jaringan Komputer Lanjut',
            'id_kategori' => $kategori->id_kategori,
        ]);

        $response = $this->get(route('catalog.index', ['q' => 'Algoritma']));

        $response->assertOk()
            ->assertSee('Algoritma Dasar Pemrograman')
            ->assertDontSee('Jaringan Komputer Lanjut');
    }

    public function test_detail_buku_dapat_dibuka_dan_kode_tidak_dikenal_menghasilkan_404(): void
    {
        $book = $this->buatBuku(['judul' => 'Pemrograman Web Modern']);

        $this->get(route('catalog.show', $book->kode_buku))
            ->assertOk()
            ->assertSee('Pemrograman Web Modern');

        $this->get(route('catalog.show', 'TIDAK-ADA-999'))
            ->assertNotFound();
    }

    // ------------------------------------------------------------------
    // Sirkulasi & aturan bisnis
    // ------------------------------------------------------------------

    public function test_siswa_dapat_mengajukan_peminjaman_dan_stok_tertahan(): void
    {
        [$user, $member] = $this->buatSiswa();
        $book = $this->buatBuku(['stok' => 2]);

        $response = $this->actingAs($user)
            ->post(route('pinjaman.store'), ['id_buku' => $book->id_buku]);

        $response->assertRedirect(route('pinjaman.index'));

        $this->assertDatabaseHas('loans', [
            'id_anggota' => $member->id_anggota,
            'status' => Loan::STATUS_DIAJUKAN,
        ]);
        $this->assertDatabaseHas('loan_items', ['id_buku' => $book->id_buku]);
    }

    public function test_pengajuan_ditolak_saat_stok_buku_habis(): void
    {
        [, $peminjamLain] = $this->buatSiswa();
        [$user, $member] = $this->buatSiswa();
        $book = $this->buatBuku(['stok' => 1]);

        $this->buatPinjaman($peminjamLain, $book, Loan::STATUS_DIPINJAM);

        $response = $this->actingAs($user)
            ->post(route('pinjaman.store'), ['id_buku' => $book->id_buku]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('loans', ['id_anggota' => $member->id_anggota]);
    }

    public function test_siswa_terlambat_tidak_dapat_mengajukan_pinjaman_baru_br_09(): void
    {
        [$user, $member] = $this->buatSiswa();
        $bukuLama = $this->buatBuku();
        $bukuBaru = $this->buatBuku();

        $this->buatPinjaman($member, $bukuLama, Loan::STATUS_TERLAMBAT, [
            'tanggal_jatuh_tempo' => now()->subDays(3)->toDateString(),
        ]);

        $response = $this->actingAs($user)
            ->post(route('pinjaman.store'), ['id_buku' => $bukuBaru->id_buku]);

        $response->assertSessionHas('error');
        $this->assertSame(1, $member->loans()->count());
    }

    public function test_batas_maksimal_pinjaman_aktif_ditegakkan_br_04(): void
    {
        Setting::set('batas_pinjam_aktif', '1');

        [$user, $member] = $this->buatSiswa();
        $bukuPertama = $this->buatBuku();
        $bukuKedua = $this->buatBuku();

        $this->buatPinjaman($member, $bukuPertama, Loan::STATUS_DIPINJAM);

        $response = $this->actingAs($user)
            ->post(route('pinjaman.store'), ['id_buku' => $bukuKedua->id_buku]);

        $response->assertSessionHas('error');
        $this->assertSame(1, $member->loans()->count());
    }

    public function test_pengajuan_ganda_untuk_buku_yang_sama_ditolak(): void
    {
        [$user, $member] = $this->buatSiswa();
        $book = $this->buatBuku();

        $this->buatPinjaman($member, $book, Loan::STATUS_DIAJUKAN);

        $response = $this->actingAs($user)
            ->post(route('pinjaman.store'), ['id_buku' => $book->id_buku]);

        $response->assertSessionHas('error');
        $this->assertSame(1, $member->loans()->count());
    }

    public function test_siswa_dapat_membatalkan_permohonan_miliknya_sendiri(): void
    {
        [$user, $member] = $this->buatSiswa();
        $book = $this->buatBuku();
        $loan = $this->buatPinjaman($member, $book);

        $this->actingAs($user)
            ->patch(route('pinjaman.batal', $loan))
            ->assertRedirect();

        $this->assertDatabaseHas('loans', [
            'id_peminjaman' => $loan->id_peminjaman,
            'status' => Loan::STATUS_DIBATALKAN,
        ]);
    }

    public function test_petugas_dapat_memverifikasi_permohonan(): void
    {
        $petugas = $this->buatPetugas();
        [, $member] = $this->buatSiswa();
        $book = $this->buatBuku();
        $loan = $this->buatPinjaman($member, $book);

        $this->actingAs($petugas)
            ->patch(route('petugas.sirkulasi.verifikasi', $loan))
            ->assertRedirect();

        $this->assertDatabaseHas('loans', [
            'id_peminjaman' => $loan->id_peminjaman,
            'status' => Loan::STATUS_DIPINJAM,
            'id_user' => $petugas->id_user,
        ]);
    }

    public function test_petugas_dapat_menolak_permohonan_dengan_alasan(): void
    {
        $petugas = $this->buatPetugas();
        [, $member] = $this->buatSiswa();
        $book = $this->buatBuku();
        $loan = $this->buatPinjaman($member, $book);

        $this->actingAs($petugas)
            ->patch(route('petugas.sirkulasi.tolak', $loan), ['alasan' => 'Stok sedang tidak memadai'])
            ->assertRedirect();

        $this->assertDatabaseHas('loans', [
            'id_peminjaman' => $loan->id_peminjaman,
            'status' => Loan::STATUS_DITOLAK,
            'catatan' => 'Stok sedang tidak memadai',
        ]);
    }

    public function test_petugas_dapat_mencatat_pengembalian_tanpa_denda(): void
    {
        $petugas = $this->buatPetugas();
        [, $member] = $this->buatSiswa();
        $book = $this->buatBuku();
        $loan = $this->buatPinjaman($member, $book, Loan::STATUS_DIPINJAM, [
            'tanggal_jatuh_tempo' => now()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($petugas)
            ->patch(route('petugas.sirkulasi.kembalikan', $loan), [
                'denda' => 0,
                'kondisi_buku' => 'baik',
            ])
            ->assertRedirect();

        $loan->refresh();
        $this->assertSame(Loan::STATUS_DIKEMBALIKAN, $loan->status);
        $this->assertNotNull($loan->tanggal_kembali);
    }

    public function test_pengembalian_terlambat_menghitung_denda(): void
    {
        $petugas = $this->buatPetugas();
        [, $member] = $this->buatSiswa();
        $book = $this->buatBuku();
        $loan = $this->buatPinjaman($member, $book, Loan::STATUS_DIPINJAM, [
            'tanggal_jatuh_tempo' => now()->subDays(3)->toDateString(),
        ]);

        $this->actingAs($petugas)
            ->patch(route('petugas.sirkulasi.kembalikan', $loan), [
                'denda' => 3000,
                'kondisi_buku' => 'baik',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('loans', [
            'id_peminjaman' => $loan->id_peminjaman,
            'status' => Loan::STATUS_DIKEMBALIKAN,
            'denda' => 3000,
        ]);

        $this->assertNotNull($loan->fresh()->tanggal_kembali);
    }

    public function test_pengembalian_terlambat_keluar_dari_daftar_pinjaman_aktif(): void
    {
        $petugas = $this->buatPetugas();
        [, $member] = $this->buatSiswa();
        $book = $this->buatBuku();
        $loan = $this->buatPinjaman($member, $book, Loan::STATUS_DIPINJAM, [
            'tanggal_jatuh_tempo' => now()->subDays(2)->toDateString(),
        ]);

        $this->assertSame(1, $member->pinjamanAktif());

        $this->actingAs($petugas)
            ->patch(route('petugas.sirkulasi.kembalikan', $loan), [
                'denda' => 2000,
                'kondisi_buku' => 'baik',
            ])
            ->assertRedirect(route('petugas.sirkulasi.index'));

        $this->assertSame(0, $member->pinjamanAktif());
        $this->assertFalse($member->punyaKeterlambatan());

        // Hapus flash message agar kode transaksi tidak terbaca dari notifikasi.
        $this->flushSession();

        $this->actingAs($petugas)
            ->get(route('petugas.sirkulasi.index', ['tab' => 'aktif']))
            ->assertDontSee($loan->kode_transaksi);

        $this->actingAs($petugas)
            ->get(route('petugas.sirkulasi.index', ['tab' => 'selesai']))
            ->assertSee($loan->kode_transaksi);
    }

    public function test_petugas_dapat_mencatat_peminjaman_manual_dari_meja_sirkulasi(): void
    {
        $petugas = $this->buatPetugas();
        [, $member] = $this->buatSiswa();
        $book = $this->buatBuku(['stok' => 2]);

        $this->actingAs($petugas)
            ->post(route('petugas.sirkulasi.pinjam'), [
                'id_anggota' => $member->id_anggota,
                'id_buku' => $book->id_buku,
            ])
            ->assertRedirect(route('petugas.sirkulasi.index'));

        $this->assertDatabaseHas('loans', [
            'id_anggota' => $member->id_anggota,
            'status' => Loan::STATUS_DIPINJAM,
            'id_user' => $petugas->id_user,
        ]);
    }

    // ------------------------------------------------------------------
    // Manajemen pengguna
    // ------------------------------------------------------------------

    public function test_admin_dapat_membuat_akun_petugas_baru(): void
    {
        $admin = $this->buatAdmin();

        $this->actingAs($admin)
            ->post(route('admin.pengguna.store'), [
                'name' => 'Petugas Baru',
                'email' => 'petugas.baru@example.test',
                'role' => User::ROLE_PETUGAS,
                'password' => 'rahasia123',
            ])
            ->assertRedirect();

        $baru = User::where('email', 'petugas.baru@example.test')->firstOrFail();
        $this->assertSame(User::ROLE_PETUGAS, $baru->role);
        $this->assertTrue(Hash::check('rahasia123', $baru->password));
    }

    public function test_admin_tidak_dapat_membuat_akun_dengan_email_ganda(): void
    {
        $admin = $this->buatAdmin();
        User::factory()->create(['email' => 'sudah.ada@example.test']);

        $response = $this->actingAs($admin)
            ->post(route('admin.pengguna.store'), [
                'name' => 'Duplikat',
                'email' => 'sudah.ada@example.test',
                'role' => User::ROLE_PETUGAS,
                'password' => 'rahasia123',
            ]);

        $response->assertSessionHasErrors('email');
    }

    // ------------------------------------------------------------------
    // Keamanan (S3-06)
    // ------------------------------------------------------------------

    public function test_konten_pengguna_diescape_sehingga_tidak_menjadi_xss(): void
    {
        $book = $this->buatBuku([
            'judul' => '<script>alert("xss")</script>',
            'penerbit' => '<img src=x onerror=alert(1)>',
        ]);

        $response = $this->get(route('catalog.show', $book->kode_buku));

        $response->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert', false)
            ->assertDontSee('<img src=x onerror', false);
    }

    public function test_header_keamanan_diterapkan_pada_respons_web(): void
    {
        $response = $this->get(route('catalog.index'));

        $response->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_unggahan_berkas_php_sebagai_sampul_ditolak(): void
    {
        $petugas = $this->buatPetugas();
        $kategori = Category::factory()->create();
        $sebelum = Book::count();

        $response = $this->actingAs($petugas)
            ->post(route('petugas.koleksi.store'), [
                'kode_buku' => 'UJI-001',
                'judul' => 'Buku dengan Sampul Berbahaya',
                'id_kategori' => $kategori->id_kategori,
                'jurusan' => 'RPL',
                'stok' => 1,
                'status' => 'aktif',
                'cover' => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
            ]);

        $response->assertSessionHasErrors('cover');
        $this->assertDatabaseCount('books', $sebelum);
    }

    public function test_percobaan_login_dibatasi_setelah_lima_kegagalan(): void
    {
        $this->buatSiswa(['email' => 'bruteforce@example.test']);

        $response = null;
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post(route('login.attempt'), [
                'email' => 'bruteforce@example.test',
                'password' => 'salah-terus',
            ]);
        }

        $response->assertStatus(429);
        $response->assertSee('Terlalu Banyak Percobaan');
    }

    // ------------------------------------------------------------------
    // Laporan PDF
    // ------------------------------------------------------------------

    public function test_surat_bebas_pustaka_dapat_diunduh_untuk_anggota_bersih(): void
    {
        [$user, $member] = $this->buatSiswa();
        $book = $this->buatBuku();

        $this->buatPinjaman($member, $book, Loan::STATUS_DIKEMBALIKAN, [
            'tanggal_kembali' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('riwayat.bebas'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_surat_bebas_pustaka_diblokir_saat_masih_ada_pinjaman_aktif(): void
    {
        [$user, $member] = $this->buatSiswa();
        $book = $this->buatBuku();

        $this->buatPinjaman($member, $book, Loan::STATUS_DIPINJAM, [
            'tanggal_jatuh_tempo' => now()->addDays(3)->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('riwayat.bebas'))
            ->assertForbidden();
    }

    public function test_laporan_pdf_dapat_diunduh_dan_parameter_tidak_valid_dinormalisasi(): void
    {
        $petugas = $this->buatPetugas();

        $normal = $this->actingAs($petugas)->get(route('petugas.laporan.pdf', [
            'jenis' => 'transaksi',
            'dari' => now()->subMonth()->toDateString(),
            'sampai' => now()->toDateString(),
        ]));

        $normal->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $normal->headers->get('content-type'));

        // BUG-06: jenis tidak dikenal dan rentang terbalik tetap menghasilkan PDF valid.
        $aneh = $this->actingAs($petugas)->get(route('petugas.laporan.pdf', [
            'jenis' => 'jenis-palsu',
            'dari' => now()->addMonth()->toDateString(),
            'sampai' => now()->subMonth()->toDateString(),
        ]));

        $aneh->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $aneh->headers->get('content-type'));
    }

    // ------------------------------------------------------------------
    // Regresi bug Sprint 3 (S3-04) & usability (S3-07)
    // ------------------------------------------------------------------

    public function test_buku_dengan_riwayat_transaksi_tidak_dapat_dihapus(): void
    {
        $petugas = $this->buatPetugas();
        [, $member] = $this->buatSiswa();
        $book = $this->buatBuku();

        $this->buatPinjaman($member, $book, Loan::STATUS_DIKEMBALIKAN, [
            'tanggal_kembali' => now()->toDateString(),
        ]);

        $this->actingAs($petugas)
            ->delete(route('petugas.koleksi.destroy', $book))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('books', ['id_buku' => $book->id_buku]);
    }

    public function test_anggota_dengan_riwayat_transaksi_tidak_dapat_dihapus(): void
    {
        $petugas = $this->buatPetugas();
        [, $member] = $this->buatSiswa();
        $book = $this->buatBuku();

        $this->buatPinjaman($member, $book, Loan::STATUS_DIKEMBALIKAN, [
            'tanggal_kembali' => now()->toDateString(),
        ]);

        $this->actingAs($petugas)
            ->delete(route('petugas.anggota.destroy', $member))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('members', ['id_anggota' => $member->id_anggota]);
    }

    public function test_admin_tidak_dapat_menghapus_akun_siswa_berriwayat(): void
    {
        $admin = $this->buatAdmin();
        [$user, $member] = $this->buatSiswa();
        $book = $this->buatBuku();

        $this->buatPinjaman($member, $book, Loan::STATUS_DIKEMBALIKAN, [
            'tanggal_kembali' => now()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.pengguna.destroy', $user))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id_user' => $user->id_user]);
    }

    public function test_pinjaman_terlambat_tidak_dapat_diperpanjang_bug_05(): void
    {
        [$user, $member] = $this->buatSiswa();
        $book = $this->buatBuku();
        $loan = $this->buatPinjaman($member, $book, Loan::STATUS_TERLAMBAT, [
            'tanggal_jatuh_tempo' => now()->subDays(3)->toDateString(),
        ]);
        $jatuhTempoAwal = $loan->tanggal_jatuh_tempo->toDateString();

        $this->actingAs($user)
            ->patch(route('pinjaman.perpanjang', $loan))
            ->assertSessionHas('error');

        $loan->refresh();
        $this->assertSame($jatuhTempoAwal, $loan->tanggal_jatuh_tempo->toDateString());
        $this->assertFalse($loan->sudah_diperpanjang);
    }

    public function test_breadcrumb_tampil_pada_halaman_utama_siswa(): void
    {
        [$user] = $this->buatSiswa();

        $this->actingAs($user)
            ->get(route('pinjaman.index'))
            ->assertOk()
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSee('Beranda');
    }
}
