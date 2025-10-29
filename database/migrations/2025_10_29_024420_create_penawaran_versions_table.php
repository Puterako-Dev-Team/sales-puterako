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
        Schema::create('penawaran_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('penawaran_id');
            $table->integer('version');
            $table->string('notes')->nullable();
            $table->string('status')->default('draft'); // draft/done/loss
            $table->timestamps();

            $table->foreign('penawaran_id')->references('id_penawaran')->on('penawarans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penawaran_versions');
    }
};
