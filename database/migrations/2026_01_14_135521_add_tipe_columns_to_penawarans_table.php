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
            $table->foreignId('tipe_id')->nullable()->constrained('tipes')->onDelete('set null');
            $table->string('tipe_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penawaran_details', function (Blueprint $table) {
            $table->dropForeign(['tipe_id']);
            $table->dropColumn('tipe_id');
            $table->dropColumn('tipe_name');
        });
    }
};
