<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekap_supporting_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_rekap');
            $table->string('file_path');
            $table->string('original_filename');
            $table->string('file_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->string('uploaded_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('id_rekap')
                ->references('id')
                ->on('rekaps')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_supporting_documents');
    }
};
