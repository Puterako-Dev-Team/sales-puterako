<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('follow_up_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('penawaran_id');
            $table->integer('cycle_number')->default(1);
            $table->integer('current_reminder_count')->default(0); // Sudah berapa kali reminder
            $table->integer('max_reminders_per_cycle')->default(5); // Maksimal reminder per cycle
            $table->integer('interval_days')->default(7); // Interval hari antar reminder
            $table->date('cycle_start_date')->nullable(); // Tanggal mulai cycle
            $table->date('next_reminder_date')->nullable(); // Tanggal reminder berikutnya
            $table->boolean('is_active')->default(true); // Auto reminder aktif/tidak
            $table->enum('status', ['running', 'paused', 'completed'])->default('running');
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->timestamps();

            $table->foreign('penawaran_id')->references('id_penawaran')->on('penawarans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_up_schedules');
    }
};
