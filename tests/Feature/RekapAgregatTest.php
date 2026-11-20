<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sprint3\InteractsWithLibrary;
use Tests\TestCase;

/**
 * SKKNI 4, 5, dan 6 — rekap agregat dashboard serta simulasi duplikat vs valid.
 *
 * Rekap memakai data koleksi per kategori sebagai adaptasi dari kartu soal
 * "Sistem Kas Kelas Digital" ke proyek Smart Library.
 */
class RekapAgregatTest extends TestCase
{
    use InteractsWithLibrary;
    use RefreshDatabase;

    public function test_dashboard_menampilkan_rekap_agregat_per_kategori(): void
    {
        $petugas = $this->buatPetugas();
        $pemrograman = Category::factory()->create(['nama' => 'Pemrograman']);
        $bahasa = Category::factory()->create(['nama' => 'Bahasa']);

        $this->buatBuku(['id_kategori' => $pemrograman->id_kategori, 'stok' => 2]);
        $this->buatBuku(['id_kategori' => $pemrograman->id_kategori, 'stok' => 3]);
        $this->buatBuku(['id_kategori' => $bahasa->id_kategori, 'stok' => 4]);

        $response = $this->actingAs($petugas)->get(route('petugas.dashboard'));

        $response->assertOk()
            ->assertSee('Rekap Koleksi per Kategori')
            ->assertSee('Pemrograman')
            ->assertSee('Bahasa');

        $response->assertViewHas('rekapKategori', function ($rekap) use ($pemrograman, $bahasa) {
            $p = $rekap->firstWhere('id_kategori', $pemrograman->id_kategori);
            $b = $rekap->firstWhere('id_kategori', $bahasa->id_kategori);

            return $p && $b
                && (int) $p->total_data === 2
                && (int) $p->total_stok === 5
                && (int) $b->total_data === 1
                && (int) $b->total_stok === 4;
        });

        $response->assertViewHas('totalJudul', 3);
        $response->assertViewHas('totalEksemplar', 9);
    }

    public function test_duplikat_isbn_ditolak_dengan_pesan_ramah_tanpa_error_500(): void
    {
        $petugas = $this->buatPetugas();
        $kategori = Category::factory()->create();

        $this->buatBuku([
            'id_kategori' => $kategori->id_kategori,
            'isbn' => '978-602-1234-01-1',
        ]);

        $response = $this->from(route('petugas.koleksi.create'))
            ->actingAs($petugas)
            ->post(route('petugas.koleksi.store'), [
                'kode_buku' => 'RPL-DUP-001',
                'judul' => 'Buku Duplikat',
                'id_kategori' => $kategori->id_kategori,
                'isbn' => '978-602-1234-01-1',
                'jurusan' => 'RPL',
                'stok' => 1,
                'status' => 'aktif',
            ]);

        $response->assertStatus(302)
            ->assertRedirect(route('petugas.koleksi.create'))
            ->assertSessionHasErrors([
                'isbn' => 'Data gagal disimpan! ISBN tersebut sudah digunakan oleh buku lain.',
            ]);

        $this->assertDatabaseCount('books', 1);
        $this->assertDatabaseMissing('books', ['kode_buku' => 'RPL-DUP-001']);

        $form = $this->actingAs($petugas)->get(route('petugas.koleksi.create'));
        $form->assertOk()
            ->assertSee('Peringatan Validasi:')
            ->assertSee('ISBN tersebut sudah digunakan oleh buku lain.');
    }

    public function test_input_valid_langsung_menambah_angka_rekap_agregat(): void
    {
        $petugas = $this->buatPetugas();
        $kategori = Category::factory()->create(['nama' => 'Pemrograman']);

        $this->buatBuku([
            'id_kategori' => $kategori->id_kategori,
            'stok' => 2,
        ]);

        $awal = $this->actingAs($petugas)->get(route('petugas.dashboard'));
        $awal->assertViewHas('rekapKategori', function ($rekap) use ($kategori) {
            $row = $rekap->firstWhere('id_kategori', $kategori->id_kategori);

            return $row && (int) $row->total_data === 1 && (int) $row->total_stok === 2;
        });

        $response = $this->actingAs($petugas)->post(route('petugas.koleksi.store'), [
            'kode_buku' => 'RPL-NEW-001',
            'judul' => 'Buku Valid Baru',
            'id_kategori' => $kategori->id_kategori,
            'isbn' => '978-602-1234-99-9',
            'jurusan' => 'RPL',
            'stok' => 3,
            'status' => 'aktif',
        ]);

        $response->assertRedirect(route('petugas.koleksi.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('books', [
            'kode_buku' => 'RPL-NEW-001',
            'isbn' => '978-602-1234-99-9',
        ]);

        $akhir = $this->actingAs($petugas)->get(route('petugas.dashboard'));
        $akhir->assertOk()
            ->assertSee('Rekap Koleksi per Kategori')
            ->assertViewHas('rekapKategori', function ($rekap) use ($kategori) {
                $row = $rekap->firstWhere('id_kategori', $kategori->id_kategori);

                return $row && (int) $row->total_data === 2 && (int) $row->total_stok === 5;
            })
            ->assertViewHas('totalJudul', 2)
            ->assertViewHas('totalEksemplar', 5);
    }

    public function test_admin_dapat_melihat_rekap_agregat_di_dashboard(): void
    {
        $admin = $this->buatAdmin();
        Category::factory()->create(['nama' => 'Pemrograman']);

        $this->actingAs($admin)
            ->get(route('petugas.dashboard'))
            ->assertOk()
            ->assertSee('Rekap Koleksi per Kategori');
    }
}
