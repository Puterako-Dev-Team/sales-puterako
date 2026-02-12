<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderToPenawaranDetails extends Migration
{
    public function up()
    {
        Schema::table('penawaran_details', function (Blueprint $table) {
            if (!Schema::hasColumn('penawaran_details', 'order')) {
                $table->integer('order')->default(0)->after('id_penawaran_detail');
            }
        });
    }

    public function down()
    {
        Schema::table('penawaran_details', function (Blueprint $table) {
            if (Schema::hasColumn('penawaran_details', 'order')) {
                $table->dropColumn('order');
            }
        });
    }
}
