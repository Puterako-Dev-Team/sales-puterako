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
            $table->string('jenis')->nullable();
            $table->string('pic_perusahaan')->nullable();
            $table->string('status')->default('progress');
            $table->boolean('is_system_generated')->default(false);
            $table->timestamps();
            
            $table->foreign('penawaran_id')->references('id_penawaran')->on('penawarans')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('follow_ups');
    }
};