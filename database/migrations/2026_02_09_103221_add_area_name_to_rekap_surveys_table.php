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
        Schema::table('rekap_surveys', function (Blueprint $table) {
            $table->string('area_name')->nullable()->after('rekap_id')->comment('Area name e.g. ACCESS POINT, CCTV');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rekap_surveys', function (Blueprint $table) {
            $table->dropColumn('area_name');
        });
    }
};
