<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Disable foreign key constraints temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // First clean up records that might violate the unique constraint during column drop
        // Delete all records since we're transitioning to a new data model using tipes_id only
        DB::statement('TRUNCATE TABLE rekap_items');
        
        // Drop the column directly
        Schema::table('rekap_items', function (Blueprint $table) {
            $table->dropColumn('nama_item');
        });
        
        // Re-enable foreign key constraints
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_items', function (Blueprint $table) {
            $table->string('nama_item')->nullable();
            $table->unique(['rekap_id', 'nama_item'], 'unique_rekap_nama_item');
        });
    }
};
