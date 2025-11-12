<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('penawaran_id');
            $table->string('nama');
            $table->text('deskripsi');
            $table->text('hasil_progress')->nullable();
            $table->enum('jenis', ['whatsapp', 'email', 'telepon', 'kunjungan']);
            $table->string('pic_perusahaan')->nullable();
            $table->enum('status', ['progress', 'deal', 'closed'])->default('progress');
            $table->timestamps();
            
            $table->foreign('penawaran_id')->references('id_penawaran')->on('penawarans')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('follow_ups');
    }
};