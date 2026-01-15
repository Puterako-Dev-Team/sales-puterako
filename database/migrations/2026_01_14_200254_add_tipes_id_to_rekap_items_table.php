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
            $table->unsignedBigInteger('tipes_id')->nullable()->after('rekap_kategori_id');
            $table->foreign('tipes_id')->references('id')->on('tipes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_items', function (Blueprint $table) {
            $table->dropForeign(['tipes_id']);
            $table->dropColumn('tipes_id');
        });
    }
};
