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
        Schema::table('penawaran_versions', function (Blueprint $table) {
            $table->boolean('jasa_use_pembulatan')->default(false)->after('jasa_use_bpjs');
            $table->decimal('jasa_pembulatan_final', 15, 2)->nullable()->default(0)->after('jasa_use_pembulatan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penawaran_versions', function (Blueprint $table) {
            $table->dropColumn(['jasa_use_pembulatan', 'jasa_pembulatan_final']);
        });
    }
};
