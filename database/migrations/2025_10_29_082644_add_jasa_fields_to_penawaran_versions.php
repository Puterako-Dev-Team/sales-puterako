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
            $table->decimal('jasa_profit_percent', 8, 2)->nullable()->default(0)->after('status');
            $table->decimal('jasa_profit_value', 15, 2)->nullable()->default(0)->after('jasa_profit_percent');
            $table->decimal('jasa_pph_percent', 8, 2)->nullable()->default(0)->after('jasa_profit_value');
            $table->decimal('jasa_pph_value', 15, 2)->nullable()->default(0)->after('jasa_pph_percent');
            $table->decimal('jasa_bpjsk_percent', 8, 2)->nullable()->default(0)->after('jasa_pph_value');
            $table->decimal('jasa_bpjsk_value', 15, 2)->nullable()->default(0)->after('jasa_bpjsk_percent');
            $table->decimal('jasa_grand_total', 15, 2)->nullable()->default(0)->after('jasa_bpjsk_value');
            $table->text('jasa_ringkasan')->nullable()->after('jasa_grand_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penawaran_versions', function (Blueprint $table) {
            $table->dropColumn([
                'jasa_profit_percent',
                'jasa_profit_value',
                'jasa_pph_percent',
                'jasa_pph_value',
                'jasa_bpjsk_percent',
                'jasa_bpjsk_value',
                'jasa_grand_total',
                'jasa_ringkasan'
            ]);
        });
    }
};
