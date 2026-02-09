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
            $table->unsignedBigInteger('version_id')->nullable()->after('rekap_id');
            $table->foreign('version_id')->references('id')->on('rekap_versions')->onDelete('cascade');
            $table->index('version_id');
        });

        Schema::table('rekap_surveys', function (Blueprint $table) {
            $table->unsignedBigInteger('version_id')->nullable()->after('rekap_id');
            $table->foreign('version_id')->references('id')->on('rekap_versions')->onDelete('cascade');
            $table->index('version_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_items', function (Blueprint $table) {
            $table->dropForeign(['version_id']);
            $table->dropColumn('version_id');
        });

        Schema::table('rekap_surveys', function (Blueprint $table) {
            $table->dropForeign(['version_id']);
            $table->dropColumn('version_id');
        });
    }
};
