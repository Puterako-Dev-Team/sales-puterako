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
        Schema::table('jasas', function (Blueprint $table) {
            $table->unsignedBigInteger('version_id')->nullable()->after('id_jasa');
        });

        Schema::table('jasa_details', function (Blueprint $table) {
            $table->unsignedBigInteger('version_id')->nullable()->after('id_jasa_detail');
        });

        Schema::table('penawaran_details', function (Blueprint $table) {
            $table->unsignedBigInteger('version_id')->nullable()->after('id_penawaran_detail');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jasas', function (Blueprint $table) {
            $table->dropColumn('version_id');
        });

        Schema::table('jasa_details', function (Blueprint $table) {
            $table->dropColumn('version_id');
        });

        Schema::table('penawaran_details', function (Blueprint $table) {
            $table->dropColumn('version_id');
        });
    }
};
