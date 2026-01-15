<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rekaps', function (Blueprint $table) {
            $table->unsignedBigInteger('penawaran_id')->nullable()->change();
            $table->string('no_penawaran')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('rekaps', function (Blueprint $table) {
            $table->unsignedBigInteger('penawaran_id')->nullable(false)->change();
            $table->string('no_penawaran')->nullable(false)->change();
        });
    }
};
