<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->enum('template_type', ['template_puterako', 'template_boq'])->default('template_puterako')->after('tipe');
            $table->string('boq_file_path')->nullable()->after('template_type');
        });
    }

    public function down(): void
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->dropColumn(['template_type', 'boq_file_path']);
        });
    }
};
