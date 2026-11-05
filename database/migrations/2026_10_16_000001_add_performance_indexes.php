<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * S3-07: indeks untuk query sirkulasi, katalog, dan manajemen pengguna.
     */
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->index(['id_anggota', 'status'], 'loans_member_status_idx');
            $table->index(['status', 'tanggal_jatuh_tempo'], 'loans_status_due_idx');
        });

        Schema::table('books', function (Blueprint $table) {
            $table->index(['status', 'id_kategori'], 'books_status_category_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'status'], 'users_role_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex('loans_member_status_idx');
            $table->dropIndex('loans_status_due_idx');
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex('books_status_category_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_status_idx');
        });
    }
};
