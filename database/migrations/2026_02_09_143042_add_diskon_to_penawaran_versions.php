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
            $table->boolean('is_diskon')->default(0)->after('best_price');
            $table->decimal('diskon', 20, 2)->default(0)->after('is_diskon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penawaran_versions', function (Blueprint $table) {
            $table->dropColumn(['is_diskon', 'diskon']);
        });
    }
};
