<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class BackfillOrderInPenawaranDetails extends Migration
{
    public function up()
    {
        // Backfill order column based on id_penawaran_detail to maintain existing row order
        // This ensures existing data is not affected
        DB::statement("
            UPDATE penawaran_details
            SET `order` = id_penawaran_detail
            WHERE `order` = 0
        ");
    }

    public function down()
    {
        DB::statement("UPDATE penawaran_details SET `order` = 0 WHERE `order` > 0");
    }
}
