<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->enum('status', ['draft', 'success', 'lost', 'po'])->default('draft')->change();
        });
    }

    public function down()
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->enum('status', ['draft', 'success', 'lost'])->default('draft')->change();
        });
    }
};
