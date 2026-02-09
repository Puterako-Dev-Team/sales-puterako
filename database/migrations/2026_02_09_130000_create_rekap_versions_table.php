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
        Schema::create('rekap_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rekap_id');
            $table->integer('version')->default(0);
            $table->text('notes')->nullable();
            $table->string('status')->default('draft'); // draft, done, loss
            $table->timestamps();

            $table->foreign('rekap_id')->references('id')->on('rekaps')->onDelete('cascade');
            $table->unique(['rekap_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekap_versions');
    }
};
