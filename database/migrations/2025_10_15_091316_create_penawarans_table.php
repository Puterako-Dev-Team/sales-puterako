<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePenawaransTable extends Migration
{
    public function up()
    {
        Schema::create('penawarans', function (Blueprint $table) {
            $table->id('id_penawaran');
            $table->string('perihal');
            $table->string('nama_perusahaan');
            $table->string('pic_perusahaan');
            $table->string('no_penawaran');
            $table->string('lokasi');
            $table->string('tiket')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('penawarans');
    }
}