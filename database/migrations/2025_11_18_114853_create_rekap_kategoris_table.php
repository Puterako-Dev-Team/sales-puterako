<?php
// database/migrations/xxxx_xx_xx_create_rekap_kategoris_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rekap_kategoris', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('rekap_kategoris');
    }
};