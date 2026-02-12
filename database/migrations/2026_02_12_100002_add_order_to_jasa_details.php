<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderToJasaDetails extends Migration
{
    public function up()
    {
        Schema::table('jasa_details', function (Blueprint $table) {
            if (!Schema::hasColumn('jasa_details', 'order')) {
                $table->integer('order')->default(0)->after('id_jasa_detail');
            }
        });
    }

    public function down()
    {
        Schema::table('jasa_details', function (Blueprint $table) {
            if (Schema::hasColumn('jasa_details', 'order')) {
                $table->dropColumn('order');
            }
        });
    }
}
