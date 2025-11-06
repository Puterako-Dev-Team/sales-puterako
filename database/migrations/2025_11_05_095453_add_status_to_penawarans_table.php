<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->enum('status', ['draft', 'success', 'lost'])->default('draft')->after('note');
        });
    }

    public function down()
    {
        Schema::table('penawarans', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};