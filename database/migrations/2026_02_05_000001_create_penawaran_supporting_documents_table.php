<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penawaran_supporting_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_penawaran');
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('file_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->string('uploaded_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('id_penawaran')
                ->references('id_penawaran')
                ->on('penawarans')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penawaran_supporting_documents');
    }
};
