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
        // Remove duplicate tipes, keeping only the first (lowest id) for each unique nama
        DB::statement("
            DELETE FROM tipes 
            WHERE id NOT IN (
                SELECT MIN(id) 
                FROM (
                    SELECT MIN(id) as id FROM tipes GROUP BY nama
                ) as temp
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reliably reverse this migration as deleted data cannot be recovered
        // This is a data cleanup migration
    }
};
