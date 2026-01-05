<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('penawaran_details', function (Blueprint $table) {
            Schema::table('penawaran_details', function (Blueprint $table) {
                $table->tinyInteger('color_code')
                    ->default(1)
                    ->after('is_mitra')
                    ->comment('1=Hitam (BOQ/Klien), 2=Ungu (Detail/Breakdown), 3=Biru (Rekomendasi Puterako)');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penawaran_details', function (Blueprint $table) {
            Schema::table('penawaran_details', function (Blueprint $table) {
                $table->dropColumn('color_code');
            });
        });
    }
};
