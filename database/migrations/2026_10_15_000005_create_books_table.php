<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('kode_buku', 30)->unique();
            $table->string('judul');
            $table->foreignId('kategori_id')->constrained('categories')->restrictOnDelete();
            $table->string('penerbit')->nullable();
            $table->smallInteger('tahun')->nullable();
            $table->string('isbn', 30)->nullable();
            $table->smallInteger('tebal_halaman')->nullable();
            $table->string('bahasa', 30)->default('Indonesia');
            $table->text('deskripsi')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('nomor_panggil', 50)->nullable();
            $table->string('jurusan', 10)->default('Umum');
            $table->string('lokasi_rak', 50)->nullable();
            $table->unsignedInteger('stok')->default(1);
            $table->string('status', 20)->default('aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
