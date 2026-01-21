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
        Schema::table('rekap_items', function (Blueprint $table) {
            // Add unique constraint on rekap_id and nama_item combination
            $table->unique(['rekap_id', 'nama_item'], 'unique_rekap_nama_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_items', function (Blueprint $table) {
            $table->dropUnique('unique_rekap_nama_item');
        });
    }
};
