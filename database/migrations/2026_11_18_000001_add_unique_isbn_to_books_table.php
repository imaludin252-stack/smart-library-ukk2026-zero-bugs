<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ISBN kosong dinormalisasi menjadi NULL agar tidak dianggap duplikat.
        DB::table('books')->where('isbn', '')->update(['isbn' => null]);

        // Amankan data lama: pertahankan ISBN pada baris pertama dan kosongkan
        // duplikatnya sebelum unique index dibuat agar migrasi tidak gagal.
        $duplikat = DB::table('books')
            ->select('isbn')
            ->whereNotNull('isbn')
            ->groupBy('isbn')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('isbn');

        foreach ($duplikat as $isbn) {
            $ids = DB::table('books')
                ->where('isbn', $isbn)
                ->orderBy('id_buku')
                ->pluck('id_buku');

            DB::table('books')
                ->whereIn('id_buku', $ids->slice(1)->all())
                ->update(['isbn' => null]);
        }

        Schema::table('books', function (Blueprint $table) {
            $table->unique('isbn');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropUnique(['isbn']);
        });
    }
};
