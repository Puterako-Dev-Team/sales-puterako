<?php
// database/migrations/xxxx_xx_xx_create_rekap_items_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rekap_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rekap_id');
            $table->unsignedBigInteger('rekap_kategori_id');
            $table->string('nama_item');
            $table->json('detail')->nullable(); // Kolom JSON untuk detail per item
            $table->timestamps();

            $table->foreign('rekap_id')->references('id')->on('rekaps')->onDelete('cascade');
            $table->foreign('rekap_kategori_id')->references('id')->on('rekap_kategoris')->onDelete('cascade');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('rekap_items');
    }
};