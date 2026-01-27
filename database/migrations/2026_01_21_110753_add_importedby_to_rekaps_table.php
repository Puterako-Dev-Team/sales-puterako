<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('rekaps', function (Blueprint $table) {
            $table->unsignedBigInteger('imported_by')->nullable()->after('status')->index();
            $table->timestamp('imported_at')->nullable()->after('imported_by');
        });
    }

    public function down()
    {
        Schema::table('rekaps', function (Blueprint $table) {
            $table->dropColumn(['imported_by', 'imported_at']);
        });
    }
};