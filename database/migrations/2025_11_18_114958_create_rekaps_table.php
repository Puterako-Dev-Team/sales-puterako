<?php
// database/migrations/xxxx_xx_xx_create_rekaps_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rekaps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('penawaran_id');
            $table->unsignedBigInteger('user_id');
            $table->string('nama');
            $table->string('no_penawaran');
            $table->string('nama_perusahaan');
            $table->timestamps();

            $table->foreign('penawaran_id')->references('id_penawaran')->on('penawarans')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('rekaps');
    }
};