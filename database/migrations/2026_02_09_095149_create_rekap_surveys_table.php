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
        Schema::create('rekap_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekap_id')->constrained('rekaps')->onDelete('cascade');
            $table->json('headers')->comment('Dynamic column headers with grouping structure');
            $table->json('data')->comment('Spreadsheet row data');
            $table->json('totals')->nullable()->comment('Total calculations per column');
            $table->string('version')->default('1.0')->comment('Data schema version');
            $table->timestamps();
            
            $table->index('rekap_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekap_surveys');
    }
};
