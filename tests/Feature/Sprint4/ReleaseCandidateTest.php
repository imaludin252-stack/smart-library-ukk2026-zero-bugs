<?php

namespace Tests\Feature\Sprint4;

use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sprint3\InteractsWithLibrary;
use Tests\TestCase;

/**
 * Sprint 4 — pengujian regresi Release Candidate (RC-01 … RC-10).
 *
 * Setiap tes mengunci satu bug yang ditemukan pada audit RC beserta
 * perilaku yang benar setelah perbaikan.
 */
class ReleaseCandidateTest extends TestCase
{
    use RefreshDatabase;
    use InteractsWithLibrary;

    // ------------------------------------------------------------------
    // RC-01 — zona waktu aplikasi mengikuti konfigurasi
    // ------------------------------------------------------------------

    public function test_zona_waktu_aplikasi_mengikuti_app_timezone_rc_01(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('Asia/Jakarta', now()->getTimezone()->getName());
    }

    // ------------------------------------------------------------------
    // RC-02 — denda belum lunas menahan surat bebas pustaka
    // ------------------------------------------------------------------

    public function test_denda_belum_lunas_memblokir_surat_bebas_pustaka_rc_02(): void
    {
        [$user, $member] = $this->buatSiswa();
        $book = $this->buatBuku();

        $loan = $this->buatPinjaman($member, $book, Loan::STATUS_DIKEMBALIKAN, [
            'tanggal_kembali' => now()->toDateString(),
            'denda' => 5000,
            'status_denda' => Loan::STATUS_DENDA_BELUM_LUNAS,
        ]);

        $this->actingAs($user)
            ->get(route('riwayat.index'))
            ->assertOk()
            ->assertSee('Surat Bebas Pustaka Terkunci')
            ->assertDontSee('Unduh Surat Bebas Pustaka');

        $this->actingAs($user)
            ->get(route('riwayat.bebas'))
            ->assertForbidden();

        // Setelah denda dilunasi, surat boleh diterbitkan.
        $loan->update([
            'status_denda' => Loan::STATUS_DENDA_LUNAS,
            'tanggal_bayar_denda' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('riwayat.bebas'));

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    // ------------------------------------------------------------------
    // RC-03 — laporan buku terlambat mengikuti periode
    // ------------------------------------------------------------------

    public function test_laporan_terlambat_hanya_memuat_transaksi_dalam_periode_rc_03(): void
    {
        $petugas = $this->buatPetugas();
        [, $member] = $this->buatSiswa();
        $bookA = $this->buatBuku();
        $bookB = $this->buatBuku();

        $this->buatPinjaman($member, $bookA, Loan::STATUS_TERLAMBAT, [
            'tanggal_pinjam' => now()->subDays(10)->toDateString(),
            'tanggal_jatuh_tempo' => now()->subDays(3)->toDateString(),
            'denda' => 3000,
        ]);

        $dalamPeriode = $this->buatPinjaman($member, $bookB, Loan::STATUS_TERLAMBAT, [
            'tanggal_pinjam' => now()->subDay()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(2)->toDateString(),
            'denda' => 1000,
        ]);

        $dataLaporan = null;

        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturnUsing(function (string $view, array $data) use (&$dataLaporan) {
                $dataLaporan = $data;

                return Pdf::getFacadeRoot();
            });
        Pdf::getFacadeRoot()->shouldReceive('setPaper')->andReturnSelf();
        Pdf::getFacadeRoot()->shouldReceive('download')
            ->andReturn(response('pdf', 200, ['content-type' => 'application/pdf']));

        $this->actingAs($petugas)
            ->get(route('petugas.laporan.pdf', [
                'jenis' => 'terlambat',
                'dari' => now()->subDays(2)->toDateString(),
                'sampai' => now()->toDateString(),
            ]))
            ->assertOk();

        $this->assertNotNull($dataLaporan);
        $this->assertSame('terlambat', $dataLaporan['jenis']);
        $this->assertSame(
            [$dalamPeriode->id_peminjaman],
            $dataLaporan['loans']->pluck('id_peminjaman')->all()
        );
    }

    // ------------------------------------------------------------------
    // RC-04 — perpanjangan memakai status yang sudah disegarkan
    // ------------------------------------------------------------------

    public function test_pinjaman_kedaluwarsa_dengan_status_basi_tidak_dapat_diperpanjang_rc_04(): void
    {
        [$user, $member] = $this->buatSiswa();
        $book = $this->buatBuku();
        $loan = $this->buatPinjaman($member, $book, Loan::STATUS_DIPINJAM, [
            'tanggal_jatuh_tempo' => now()->subDays(2)->toDateString(),
        ]);
        $jatuhTempoAwal = $loan->tanggal_jatuh_tempo->toDateString();

        $this->actingAs($user)
            ->patch(route('pinjaman.perpanjang', $loan))
            ->assertSessionHas('error');

        $loan->refresh();
        $this->assertSame($jatuhTempoAwal, $loan->tanggal_jatuh_tempo->toDateString());
        $this->assertFalse($loan->sudah_diperpanjang);
        $this->assertSame(Loan::STATUS_TERLAMBAT, $loan->status);
    }

    // ------------------------------------------------------------------
    // RC-05 — peminjaman manual tidak boleh menggandakan buku yang sama
    // ------------------------------------------------------------------

    public function test_peminjaman_manual_ganda_untuk_buku_yang_sama_ditolak_rc_05(): void
    {
        $petugas = $this->buatPetugas();
        [, $member] = $this->buatSiswa();
        $book = $this->buatBuku(['stok' => 2]);

        $this->buatPinjaman($member, $book, Loan::STATUS_DIAJUKAN);

        $this->actingAs($petugas)
            ->post(route('petugas.sirkulasi.pinjam'), [
                'id_anggota' => $member->id_anggota,
                'id_buku' => $book->id_buku,
            ])
            ->assertSessionHas('error');

        $this->assertSame(1, $member->loans()->count());
        $this->assertSame(1, $book->fresh()->jumlahDipinjam());
    }

    // ------------------------------------------------------------------
    // RC-06 — stok tidak boleh di bawah eksemplar yang sedang dipinjam
    // ------------------------------------------------------------------

    public function test_stok_buku_tidak_dapat_diturunkan_di_bawah_pinjaman_aktif_rc_06(): void
    {
        $petugas = $this->buatPetugas();
        [, $member] = $this->buatSiswa();
        $kategori = Category::factory()->create();
        $book = $this->buatBuku([
            'stok' => 3,
            'id_kategori' => $kategori->id_kategori,
        ]);

        $this->buatPinjaman($member, $book, Loan::STATUS_DIPINJAM);

        $this->actingAs($petugas)
            ->put(route('petugas.koleksi.update', $book), [
                'kode_buku' => $book->kode_buku,
                'judul' => $book->judul,
                'id_kategori' => $kategori->id_kategori,
                'jurusan' => $book->jurusan,
                'stok' => 0,
                'status' => Book::STATUS_AKTIF,
            ])
            ->assertSessionHas('error');

        $this->assertSame(3, $book->fresh()->stok);
    }

    // ------------------------------------------------------------------
    // RC-09 — ubah buku dengan kode yang sama tidak dianggap duplikat
    // ------------------------------------------------------------------

    public function test_ubah_buku_dengan_kode_yang_sama_tetap_tersimpan_rc_09(): void
    {
        $petugas = $this->buatPetugas();
        $kategori = Category::factory()->create();
        $book = $this->buatBuku([
            'stok' => 2,
            'id_kategori' => $kategori->id_kategori,
        ]);

        $this->actingAs($petugas)
            ->put(route('petugas.koleksi.update', $book), [
                'kode_buku' => $book->kode_buku,
                'judul' => 'Judul Setelah Perbaikan',
                'id_kategori' => $kategori->id_kategori,
                'jurusan' => $book->jurusan,
                'stok' => 4,
                'status' => Book::STATUS_AKTIF,
            ])
            ->assertRedirect(route('petugas.koleksi.index'))
            ->assertSessionHasNoErrors();

        $book->refresh();
        $this->assertSame('Judul Setelah Perbaikan', $book->judul);
        $this->assertSame(4, $book->stok);
    }

    // ------------------------------------------------------------------
    // RC-10 — halaman laporan portabel di SQLite (tanpa HAVING non-agregat)
    // ------------------------------------------------------------------

    public function test_halaman_laporan_dan_pdf_populer_tampil_di_sqlite_rc_10(): void
    {
        $petugas = $this->buatPetugas();
        [, $member] = $this->buatSiswa();
        $book = $this->buatBuku(['judul' => 'Buku Paling Populer']);

        $this->buatPinjaman($member, $book, Loan::STATUS_DIPINJAM, [
            'tanggal_pinjam' => now()->subDay()->toDateString(),
            'tanggal_jatuh_tempo' => now()->addDays(5)->toDateString(),
        ]);

        $this->actingAs($petugas)
            ->get(route('petugas.laporan.index', [
                'dari' => now()->subDays(7)->toDateString(),
                'sampai' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Buku Paling Populer');

        $pdf = $this->actingAs($petugas)->get(route('petugas.laporan.pdf', [
            'jenis' => 'populer',
            'dari' => now()->subDays(7)->toDateString(),
            'sampai' => now()->toDateString(),
        ]));

        $pdf->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $pdf->headers->get('content-type'));
    }

    // ------------------------------------------------------------------
    // RC-07 — pengajuan ditolak/dibatalkan tetap terlihat oleh siswa
    // ------------------------------------------------------------------

    public function test_pengajuan_ditolak_dan_dibatalkan_tampil_beserta_alasannya_rc_07(): void
    {
        [$user, $member] = $this->buatSiswa();
        $bookA = $this->buatBuku();
        $bookB = $this->buatBuku();
        $bookC = $this->buatBuku();

        $ditolak = $this->buatPinjaman($member, $bookA, Loan::STATUS_DITOLAK, [
            'catatan' => 'Stok sedang tidak memadai',
        ]);
        $dibatalkan = $this->buatPinjaman($member, $bookB, Loan::STATUS_DIBATALKAN);
        $aktif = $this->buatPinjaman($member, $bookC, Loan::STATUS_DIPINJAM);

        $semua = $this->actingAs($user)->get(route('pinjaman.index'));

        $semua->assertOk()
            ->assertSee($ditolak->kode_transaksi)
            ->assertSee($dibatalkan->kode_transaksi)
            ->assertSee('Alasan penolakan: Stok sedang tidak memadai')
            ->assertSee('Ditolak / Dibatalkan');

        $this->assertSame(3, $semua->viewData('jumlahSemua'));
        $this->assertSame(2, $semua->viewData('jumlahDitolak'));

        $this->actingAs($user)
            ->get(route('pinjaman.index', ['tab' => 'ditolak']))
            ->assertOk()
            ->assertSee($ditolak->kode_transaksi)
            ->assertSee($dibatalkan->kode_transaksi)
            ->assertDontSee($aktif->kode_transaksi);
    }

    // ------------------------------------------------------------------
    // RC-08 — hitungan tab "Semua" & badge denda
    // ------------------------------------------------------------------

    public function test_hitungan_tab_semua_tidak_menggandakan_pinjaman_terlambat_rc_08(): void
    {
        [$user, $member] = $this->buatSiswa();
        $bookA = $this->buatBuku();
        $bookB = $this->buatBuku();

        $this->buatPinjaman($member, $bookA, Loan::STATUS_DIPINJAM, [
            'tanggal_jatuh_tempo' => now()->addDays(3)->toDateString(),
        ]);
        $this->buatPinjaman($member, $bookB, Loan::STATUS_TERLAMBAT, [
            'tanggal_jatuh_tempo' => now()->subDays(2)->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('pinjaman.index'));

        $response->assertOk();
        $this->assertSame(2, $response->viewData('jumlahSemua'));
        $this->assertSame(1, $response->viewData('jumlahAktif'));
        $this->assertSame(1, $response->viewData('jumlahTerlambat'));
    }

    public function test_badge_denda_hanya_menghitung_denda_belum_lunas_rc_08(): void
    {
        [$user, $member] = $this->buatSiswa();
        $bookA = $this->buatBuku();
        $bookB = $this->buatBuku();

        $this->buatPinjaman($member, $bookA, Loan::STATUS_DIKEMBALIKAN, [
            'tanggal_kembali' => now()->subDays(2)->toDateString(),
            'denda' => 5000,
            'status_denda' => Loan::STATUS_DENDA_LUNAS,
            'tanggal_bayar_denda' => now()->subDay()->toDateString(),
        ]);
        $this->buatPinjaman($member, $bookB, Loan::STATUS_DIKEMBALIKAN, [
            'tanggal_kembali' => now()->toDateString(),
            'denda' => 2000,
            'status_denda' => Loan::STATUS_DENDA_BELUM_LUNAS,
        ]);

        $pinjaman = $this->actingAs($user)->get(route('pinjaman.index'));
        $pinjaman->assertOk();
        $this->assertSame(2000, $pinjaman->viewData('totalDenda'));

        $beranda = $this->actingAs($user)->get(route('beranda'));
        $beranda->assertOk();
        $this->assertSame(2000, $beranda->viewData('totalDenda'));
    }
}
