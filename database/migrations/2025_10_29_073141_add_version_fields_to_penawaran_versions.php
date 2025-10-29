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
        Schema::table('penawaran_versions', function (Blueprint $table) {
            $table->decimal('ppn_persen', 5, 2)->default(11)->after('notes');
            $table->boolean('is_best_price')->default(0)->after('ppn_persen');
            $table->decimal('best_price', 20, 2)->default(0)->after('is_best_price');
            $table->decimal('ppn_nominal', 20, 2)->default(0)->after('best_price');
            $table->decimal('grand_total', 20, 2)->default(0)->after('ppn_nominal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penawaran_versions', function (Blueprint $table) {
            $table->dropColumn('ppn_persen');
            $table->dropColumn('is_best_price');
            $table->dropColumn('best_price');
            $table->dropColumn('ppn_nominal');
            $table->dropColumn('grand_total');
        });
    }
};
