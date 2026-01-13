<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            // Link ke schedule yang menghasilkan reminder ini
            $table->unsignedBigInteger('follow_up_schedule_id')->nullable()->after('penawaran_id');
            $table->integer('cycle_number')->nullable()->after('is_system_generated');
            $table->integer('reminder_sequence')->nullable()->after('cycle_number'); 
            
            $table->foreign('follow_up_schedule_id')
                  ->references('id')
                  ->on('follow_up_schedules')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('follow_ups', function (Blueprint $table) {
            $table->dropForeign(['follow_up_schedule_id']);
            $table->dropColumn(['follow_up_schedule_id', 'cycle_number', 'reminder_sequence']);
        });
    }
};