<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rapikan transaksi lama yang sudah dikembalikan tetapi statusnya masih
     * "terlambat"/"dipinjam"/"diajukan" sehingga tetap muncul di meja sirkulasi.
     */
    public function up(): void
    {
        DB::table('loans')
            ->whereNotNull('tanggal_kembali')
            ->whereIn('status', ['diajukan', 'dipinjam', 'terlambat'])
            ->update(['status' => 'dikembalikan']);
    }

    public function down(): void
    {
        // Tidak dibalik: status pengembalian adalah koreksi data, bukan perubahan skema.
    }
};
