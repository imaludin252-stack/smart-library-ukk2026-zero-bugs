<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Sprint3\InteractsWithLibrary;
use Tests\TestCase;

/**
 * SKKNI 3 — validasi anti-duplikat saat tambah data baru.
 *
 * Fokus pengujian: kombinasi data buku (kode_buku + ISBN) pada alur
 * tambah dan ubah koleksi, termasuk memastikan ISBN kosong tetap boleh
 * lebih dari satu karena kolomnya nullable.
 */
class AntiDuplicateTest extends TestCase
{
    use InteractsWithLibrary;
    use RefreshDatabase;

    public function test_buku_dengan_isbn_sama_ditolak(): void
    {
        $petugas = $this->buatPetugas();
        $kategori = Category::factory()->create();

        $this->buatBuku([
            'isbn' => '978-602-1234-01-1',
            'id_kategori' => $kategori->id_kategori,
        ]);

        $response = $this->actingAs($petugas)
            ->post(route('petugas.koleksi.store'), [
                'kode_buku' => 'RPL-DUP-001',
                'judul' => 'Buku Duplikat',
                'id_kategori' => $kategori->id_kategori,
                'isbn' => '978-602-1234-01-1',
                'jurusan' => 'RPL',
                'stok' => 1,
                'status' => 'aktif',
            ]);

        $response->assertSessionHasErrors('isbn');
        $this->assertDatabaseCount('books', 1);
        $this->assertDatabaseMissing('books', ['kode_buku' => 'RPL-DUP-001']);
    }

    public function test_buku_dengan_isbn_berbeda_diterima(): void
    {
        $petugas = $this->buatPetugas();
        $kategori = Category::factory()->create();

        $response = $this->actingAs($petugas)
            ->post(route('petugas.koleksi.store'), [
                'kode_buku' => 'RPL-NEW-001',
                'judul' => 'Buku Baru',
                'id_kategori' => $kategori->id_kategori,
                'isbn' => '978-602-1234-99-9',
                'jurusan' => 'RPL',
                'stok' => 1,
                'status' => 'aktif',
            ]);

        $response->assertRedirect(route('petugas.koleksi.index'));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('books', [
            'kode_buku' => 'RPL-NEW-001',
            'isbn' => '978-602-1234-99-9',
        ]);
    }

    public function test_ubah_buku_dengan_isbn_sendiri_tetap_tersimpan(): void
    {
        $petugas = $this->buatPetugas();
        $book = $this->buatBuku(['isbn' => '978-602-1234-01-1']);

        $response = $this->actingAs($petugas)
            ->put(route('petugas.koleksi.update', $book), [
                'kode_buku' => $book->kode_buku,
                'judul' => 'Judul Setelah Perubahan',
                'id_kategori' => $book->id_kategori,
                'isbn' => $book->isbn,
                'jurusan' => $book->jurusan,
                'stok' => $book->stok,
                'status' => $book->status,
            ]);

        $response->assertRedirect(route('petugas.koleksi.index'));
        $response->assertSessionHasNoErrors();

        $this->assertSame('Judul Setelah Perubahan', $book->fresh()->judul);
        $this->assertSame('978-602-1234-01-1', $book->fresh()->isbn);
    }

    public function test_beberapa_buku_tanpa_isbn_diperbolehkan(): void
    {
        $petugas = $this->buatPetugas();
        $kategori = Category::factory()->create();

        $this->buatBuku([
            'isbn' => null,
            'id_kategori' => $kategori->id_kategori,
        ]);

        $response = $this->actingAs($petugas)
            ->post(route('petugas.koleksi.store'), [
                'kode_buku' => 'RPL-NOISBN-002',
                'judul' => 'Buku Tanpa ISBN Kedua',
                'id_kategori' => $kategori->id_kategori,
                'isbn' => '',
                'jurusan' => 'RPL',
                'stok' => 1,
                'status' => 'aktif',
            ]);

        $response->assertRedirect(route('petugas.koleksi.index'));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('books', 2);
    }
}
