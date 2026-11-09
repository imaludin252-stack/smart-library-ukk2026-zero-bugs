<?php

namespace Tests\Feature;

use App\Models\Loan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sprint3\InteractsWithLibrary;
use Tests\TestCase;

class DendaTest extends TestCase
{
    use InteractsWithLibrary;
    use RefreshDatabase;

    public function test_petugas_dapat_melihat_rekap_denda(): void
    {
        $petugas = $this->buatPetugas();
        [, $member] = $this->buatSiswa(['name' => 'Budi Santoso']);
        $book = $this->buatBuku(['judul' => 'Pemrograman Web Dasar']);

        $loan = $this->buatPinjaman($member, $book, Loan::STATUS_DIKEMBALIKAN, [
            'tanggal_kembali' => now()->toDateString(),
            'denda' => 5000,
        ]);

        $response = $this->actingAs($petugas)->get(route('petugas.denda.index'));

        $response->assertOk();
        $response->assertSeeText('Rekap Denda');
        $response->assertSeeText('Budi Santoso');
        $response->assertSeeText('Pemrograman Web Dasar');
        $response->assertSeeText('5.000');
        $response->assertSeeText('Belum Lunas');
        $response->assertSeeText($loan->kode_transaksi);
    }

    public function test_petugas_dapat_mengubah_denda_dan_status_pembayaran(): void
    {
        $petugas = $this->buatPetugas();
        [, $member] = $this->buatSiswa();
        $book = $this->buatBuku();

        $loan = $this->buatPinjaman($member, $book, Loan::STATUS_DIKEMBALIKAN, [
            'tanggal_kembali' => now()->toDateString(),
            'denda' => 3000,
        ]);

        $response = $this->actingAs($petugas)->put(route('petugas.denda.update', $loan), [
            'denda' => 7500,
            'status_denda' => Loan::STATUS_DENDA_LUNAS,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $loan->refresh();

        $this->assertSame(7500, $loan->denda);
        $this->assertSame(Loan::STATUS_DENDA_LUNAS, $loan->status_denda);
        $this->assertNotNull($loan->tanggal_bayar_denda);
    }

    public function test_status_denda_kembali_belum_lunas_saat_pembayaran_dibatalkan(): void
    {
        $petugas = $this->buatPetugas();
        [, $member] = $this->buatSiswa();
        $book = $this->buatBuku();

        $loan = $this->buatPinjaman($member, $book, Loan::STATUS_DIKEMBALIKAN, [
            'tanggal_kembali' => now()->toDateString(),
            'denda' => 4000,
            'status_denda' => Loan::STATUS_DENDA_LUNAS,
            'tanggal_bayar_denda' => now()->toDateString(),
        ]);

        $this->actingAs($petugas)->put(route('petugas.denda.update', $loan), [
            'denda' => 4000,
            'status_denda' => Loan::STATUS_DENDA_BELUM_LUNAS,
        ])->assertRedirect();

        $loan->refresh();

        $this->assertSame(Loan::STATUS_DENDA_BELUM_LUNAS, $loan->status_denda);
        $this->assertNull($loan->tanggal_bayar_denda);
    }

    public function test_nominal_denda_negatif_ditolak(): void
    {
        $petugas = $this->buatPetugas();
        [, $member] = $this->buatSiswa();
        $book = $this->buatBuku();

        $loan = $this->buatPinjaman($member, $book, Loan::STATUS_DIKEMBALIKAN, [
            'tanggal_kembali' => now()->toDateString(),
            'denda' => 5000,
        ]);

        $response = $this->actingAs($petugas)->put(route('petugas.denda.update', $loan), [
            'denda' => -1000,
            'status_denda' => Loan::STATUS_DENDA_BELUM_LUNAS,
        ]);

        $response->assertSessionHasErrors('denda');

        $this->assertSame(5000, $loan->refresh()->denda);
    }

    public function test_siswa_tidak_dapat_mengakses_menu_denda(): void
    {
        [$siswa] = $this->buatSiswa();

        $this->actingAs($siswa)
            ->get(route('petugas.denda.index'))
            ->assertForbidden();
    }
}
