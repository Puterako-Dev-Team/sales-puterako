<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('penawaran_details', function (Blueprint $table) {
            $table->boolean('is_judul')->default(0)->after('is_mitra');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penawaran_details', function (Blueprint $table) {
            $table->dropColumn('is_judul');
        });
    }
};
