<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_authors', function (Blueprint $table) {
            $table->foreignId('id_buku')->constrained('books', 'id_buku')->cascadeOnDelete();
            $table->foreignId('id_penulis')->constrained('authors', 'id_penulis')->cascadeOnDelete();
            $table->primary(['id_buku', 'id_penulis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_authors');
    }
};
