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
        Schema::table('rekaps', function (Blueprint $table) {
            $table->unsignedBigInteger('imported_into_penawaran_id')->nullable()->after('imported_by');
            $table->foreign('imported_into_penawaran_id')
                ->references('id_penawaran')->on('penawarans')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekaps', function (Blueprint $table) {
            $table->dropForeign(['imported_into_penawaran_id']);
            $table->dropColumn('imported_into_penawaran_id');
        });
    }
};
