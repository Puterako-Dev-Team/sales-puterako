<?php
// database/migrations/add_fields_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->after('email');
            $table->string('departemen')->nullable()->after('role');
            $table->string('kantor')->nullable()->after('departemen');
            $table->string('nohp')->nullable()->after('kantor');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'departemen', 'kantor', 'nohp']);
        });
    }
};