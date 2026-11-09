<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->string('status_denda', 20)->default('belum_lunas')->after('denda');
            $table->date('tanggal_bayar_denda')->nullable()->after('status_denda');
            $table->index('status_denda');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex(['status_denda']);
            $table->dropColumn(['status_denda', 'tanggal_bayar_denda']);
        });
    }
};
