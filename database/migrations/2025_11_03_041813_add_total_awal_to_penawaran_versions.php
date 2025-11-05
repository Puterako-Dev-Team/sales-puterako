<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('penawaran_versions', function (Blueprint $table) {
            $table->decimal('penawaran_total_awal', 15, 2)->default(0)->after('status');
            $table->decimal('jasa_total_awal', 15, 2)->default(0)->after('penawaran_total_awal');
        });
    }

    public function down()
    {
        Schema::table('penawaran_versions', function (Blueprint $table) {
            $table->dropColumn('penawaran_total_awal');
            $table->dropColumn('jasa_total_awal');
        });
    }
};