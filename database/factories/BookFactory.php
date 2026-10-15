<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        return [
            'kode_buku' => strtoupper(fake()->unique()->bothify('???-???-###')),
            'judul' => fake()->sentence(3),
            'kategori_id' => Category::factory(),
            'penerbit' => fake()->company(),
            'tahun' => fake()->numberBetween(2018, 2026),
            'isbn' => fake()->unique()->numerify('978-602-########-#'),
            'tebal_halaman' => fake()->numberBetween(80, 400),
            'bahasa' => 'Indonesia',
            'deskripsi' => fake()->paragraph(),
            'nomor_panggil' => fake()->bothify('00#.### ??? #'),
            'jurusan' => fake()->randomElement(['Umum', 'RPL', 'TKJ', 'AKL', 'OTKP']),
            'lokasi_rak' => fake()->randomElement(['Lantai 1 – Rak RPL Blok B Nomor 01', 'Lantai 2 – Rak Umum Blok F Nomor 02']),
            'stok' => 3,
            'status' => Book::STATUS_AKTIF,
        ];
    }
}
