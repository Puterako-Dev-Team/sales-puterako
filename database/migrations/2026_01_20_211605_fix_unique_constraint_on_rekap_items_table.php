<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Try to drop the old constraint if it exists
        try {
            DB::statement('ALTER TABLE rekap_items DROP INDEX unique_rekap_nama_item');
        } catch (\Exception $e) {
            // Index might not exist, that's okay
        }
        
        // Add new unique constraint that includes nama_area and kategori
        // This allows the same item (tipes_id) to appear multiple times but unique per area+kategori combination
        DB::statement('
            ALTER TABLE rekap_items 
            ADD UNIQUE KEY unique_rekap_area_kategori_item (rekap_id, nama_area, rekap_kategori_id, tipes_id)
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_items', function (Blueprint $table) {
            // Drop the new constraint
            $table->dropUnique('unique_rekap_area_kategori_item');
            
            // Restore old constraint (even though nama_item column might not exist)
            // This is just for rollback purposes
        });
    }
};
