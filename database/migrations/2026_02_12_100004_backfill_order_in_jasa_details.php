<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class BackfillOrderInJasaDetails extends Migration
{
    public function up()
    {
        // Backfill order column based on id_jasa_detail to maintain existing row order
        // This ensures existing data is not affected
        DB::statement("
            UPDATE jasa_details
            SET `order` = id_jasa_detail
            WHERE `order` = 0
        ");
    }

    public function down()
    {
        DB::statement("UPDATE jasa_details SET `order` = 0 WHERE `order` > 0");
    }
}
