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
            // Drop the old text comment column
            $table->dropColumn('comment');
        });
        
        Schema::table('penawaran_details', function (Blueprint $table) {
            // Add comments as JSON: {"col_index": "comment text", ...}
            $table->json('comments')->nullable()->after('delivery_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penawaran_details', function (Blueprint $table) {
            $table->dropColumn('comments');
        });
        
        Schema::table('penawaran_details', function (Blueprint $table) {
            $table->text('comment')->nullable()->after('delivery_time');
        });
    }
};
