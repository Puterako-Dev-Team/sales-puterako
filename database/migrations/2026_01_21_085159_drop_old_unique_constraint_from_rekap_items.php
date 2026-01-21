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
        // Drop the old incorrect unique constraint if it exists
        try {
            Schema::table('rekap_items', function (Blueprint $table) {
                $table->dropUnique('unique_rekap_nama_item');
            });
        } catch (\Exception $e) {
            // If that doesn't work, try with raw SQL
            try {
                DB::statement('ALTER TABLE rekap_items DROP INDEX unique_rekap_nama_item');
            } catch (\Exception $e2) {
                // Index might not exist, that's okay
                \Log::info('Could not drop unique_rekap_nama_item: ' . $e2->getMessage());
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't want to restore the bad constraint
        // Leave this empty
    }
};
