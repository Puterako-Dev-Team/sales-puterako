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
            // Drop existing columns that are not in the new schema
            $table->dropColumn(['detail']);
            
            // Add new columns
            $table->string('nama_area')->after('tipes_id');
            $table->integer('jumlah')->after('nama_item');
            $table->unsignedBigInteger('satuan_id')->after('jumlah');
            
            // Add foreign key for satuan_id
            $table->foreign('satuan_id')->references('id')->on('satuans')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_items', function (Blueprint $table) {
            // Drop foreign key and new columns
            $table->dropForeign(['satuan_id']);
            $table->dropColumn(['nama_area', 'jumlah', 'satuan_id']);
            
            // Restore old columns
            $table->json('detail')->nullable();
        });
    }
};
