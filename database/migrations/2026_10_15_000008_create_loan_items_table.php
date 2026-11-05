<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_items', function (Blueprint $table) {
            $table->id('id_item');
            $table->foreignId('id_peminjaman')->constrained('loans', 'id_peminjaman')->cascadeOnDelete();
            $table->foreignId('id_buku')->constrained('books', 'id_buku')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['id_peminjaman', 'id_buku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_items');
    }
};
