<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->string('lokasi_pengerjaan', 10)->default('SBY')->after('nama_perusahaan');
        });
    }

    public function down()
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->dropColumn('lokasi_pengerjaan');
        });
    }
};
