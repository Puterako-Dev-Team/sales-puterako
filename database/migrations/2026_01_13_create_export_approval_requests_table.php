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
        // Add export_approval_status to penawaran_versions
        Schema::table('penawaran_versions', function (Blueprint $table) {
            $table->enum('export_approval_status', ['pending', 'approved', 'rejected'])->default('pending')->after('jasa_use_bpjs');
        });

        // Create export_approval_requests table for tracking 3-step approval
        Schema::create('export_approval_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('penawaran_id');
            $table->unsignedBigInteger('version_id');
            $table->unsignedBigInteger('requested_by'); // staff user_id

            // 3-Step Approval: Supervisor -> Manager -> Direktur
            $table->unsignedBigInteger('approved_by_supervisor')->nullable();
            $table->timestamp('approved_at_supervisor')->nullable();
            
            $table->unsignedBigInteger('approved_by_manager')->nullable();
            $table->timestamp('approved_at_manager')->nullable();
            
            $table->unsignedBigInteger('approved_by_direktur')->nullable();
            $table->timestamp('approved_at_direktur')->nullable();

            // Overall status
            $table->enum('status', ['pending', 'supervisor_approved', 'manager_approved', 'fully_approved'])->default('pending');
            
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamps();

            $table->foreign('penawaran_id')->references('id_penawaran')->on('penawarans')->onDelete('cascade');
            $table->foreign('version_id')->references('id')->on('penawaran_versions')->onDelete('cascade');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by_supervisor')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by_manager')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by_direktur')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_approval_requests');
        Schema::table('penawaran_versions', function (Blueprint $table) {
            $table->dropColumn(['export_approval_status']);
        });
    }
};

