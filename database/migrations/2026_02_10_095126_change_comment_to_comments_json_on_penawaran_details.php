<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('penawaran_details', function (Blueprint $table) {
            if (Schema::hasColumn('penawaran_details', 'comment')) {
                $table->dropColumn('comment');
            }

            if (!Schema::hasColumn('penawaran_details', 'comments')) {
                $table->json('comments')->nullable()->after('delivery_time');
            }
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penawaran_details', function (Blueprint $table) {
            if (Schema::hasColumn('penawaran_details', 'comments')) {
                $table->dropColumn('comments');
            }

            if (!Schema::hasColumn('penawaran_details', 'comment')) {
                $table->text('comment')->nullable()->after('delivery_time');
            }
        });
    }
};
