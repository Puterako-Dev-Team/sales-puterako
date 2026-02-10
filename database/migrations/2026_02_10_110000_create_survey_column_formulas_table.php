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
        Schema::create('survey_column_formulas', function (Blueprint $table) {
            $table->id();
            $table->string('column_key')->comment('Target column key that will be calculated (e.g., up_08)');
            $table->string('formula')->comment('Formula expression using column keys (e.g., (horizon + vertical) / 0.8)');
            $table->json('dependencies')->comment('Array of column keys that this formula depends on');
            $table->string('description')->nullable()->comment('Human-readable description of the formula');
            $table->string('group_name')->nullable()->comment('Optional: restrict formula to specific column group');
            $table->boolean('is_active')->default(true)->comment('Whether this formula is active');
            $table->integer('order')->default(0)->comment('Execution order for dependent formulas');
            $table->timestamps();

            $table->unique('column_key');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_column_formulas');
    }
};
