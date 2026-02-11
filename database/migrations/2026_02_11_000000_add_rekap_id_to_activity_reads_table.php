<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if rekap_id column already exists (from partial migration)
        $hasRekapId = Schema::hasColumn('activity_reads', 'rekap_id');
        
        if (!$hasRekapId) {
            // Check and drop foreign keys that may be using the unique index
            $userFkExists = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'activity_reads' 
                AND CONSTRAINT_NAME = 'activity_reads_user_id_foreign'
            ");
            
            if (!empty($userFkExists)) {
                DB::statement('ALTER TABLE `activity_reads` DROP FOREIGN KEY `activity_reads_user_id_foreign`');
            }
            
            $penawaranFkExists = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'activity_reads' 
                AND CONSTRAINT_NAME = 'activity_reads_penawaran_id_foreign'
            ");
            
            if (!empty($penawaranFkExists)) {
                DB::statement('ALTER TABLE `activity_reads` DROP FOREIGN KEY `activity_reads_penawaran_id_foreign`');
            }
            
            // Check if unique constraint exists
            $uniqueExists = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'activity_reads' 
                AND CONSTRAINT_NAME = 'activity_reads_user_id_penawaran_id_unique'
            ");
            
            if (!empty($uniqueExists)) {
                Schema::table('activity_reads', function (Blueprint $table) {
                    $table->dropUnique(['user_id', 'penawaran_id']);
                });
            }
            
            // Now make the changes
            Schema::table('activity_reads', function (Blueprint $table) {
                // Make penawaran_id nullable
                $table->unsignedBigInteger('penawaran_id')->nullable()->change();
                
                // Add rekap_id column
                $table->unsignedBigInteger('rekap_id')->nullable()->after('penawaran_id');
            });
            
            // Add back the foreign keys
            Schema::table('activity_reads', function (Blueprint $table) {
                // Add index for user_id to support foreign key
                $table->index('user_id', 'activity_reads_user_id_index');
                
                // Re-add the foreign key for user_id
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                
                // Re-add the foreign key for penawaran_id
                $table->foreign('penawaran_id')->references('id_penawaran')->on('penawarans')->onDelete('cascade');
                
                // Add foreign key for rekap_id
                $table->foreign('rekap_id')->references('id')->on('rekaps')->onDelete('cascade');
            });
        }
        
        // Check if the new unique constraint already exists
        $newUniqueExists = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'activity_reads' 
            AND CONSTRAINT_NAME = 'activity_reads_unique'
        ");
        
        if (empty($newUniqueExists)) {
            // Drop old unique constraint if it exists (with foreign key handling)
            $oldUniqueExists = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'activity_reads' 
                AND CONSTRAINT_NAME = 'activity_reads_user_id_penawaran_id_unique'
            ");
            
            if (!empty($oldUniqueExists)) {
                // Check and drop foreign keys that use this index
                $userFkExists = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.TABLE_CONSTRAINTS 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'activity_reads' 
                    AND CONSTRAINT_NAME = 'activity_reads_user_id_foreign'
                ");
                
                $needReaddUserFk = false;
                if (!empty($userFkExists)) {
                    DB::statement('ALTER TABLE `activity_reads` DROP FOREIGN KEY `activity_reads_user_id_foreign`');
                    $needReaddUserFk = true;
                }
                
                Schema::table('activity_reads', function (Blueprint $table) {
                    $table->dropUnique(['user_id', 'penawaran_id']);
                });
                
                if ($needReaddUserFk) {
                    // Add index for user_id if not exists
                    $userIndexExists = DB::select("
                        SELECT INDEX_NAME 
                        FROM information_schema.STATISTICS 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'activity_reads' 
                        AND INDEX_NAME = 'activity_reads_user_id_index'
                    ");
                    
                    Schema::table('activity_reads', function (Blueprint $table) use ($userIndexExists) {
                        if (empty($userIndexExists)) {
                            $table->index('user_id', 'activity_reads_user_id_index');
                        }
                        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    });
                }
            }
            
            // Add new unique constraint
            Schema::table('activity_reads', function (Blueprint $table) {
                $table->unique(['user_id', 'penawaran_id', 'rekap_id'], 'activity_reads_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if the new unique constraint exists
        $newUniqueExists = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'activity_reads' 
            AND CONSTRAINT_NAME = 'activity_reads_unique'
        ");
        
        if (!empty($newUniqueExists)) {
            Schema::table('activity_reads', function (Blueprint $table) {
                $table->dropUnique('activity_reads_unique');
            });
        }
        
        // Check if rekap_id column exists
        if (Schema::hasColumn('activity_reads', 'rekap_id')) {
            // Check if foreign key exists
            $rekapFkExists = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'activity_reads' 
                AND CONSTRAINT_NAME = 'activity_reads_rekap_id_foreign'
            ");
            
            if (!empty($rekapFkExists)) {
                Schema::table('activity_reads', function (Blueprint $table) {
                    $table->dropForeign(['rekap_id']);
                });
            }
            
            Schema::table('activity_reads', function (Blueprint $table) {
                $table->dropColumn('rekap_id');
            });
        }
        
        // Restore original unique constraint
        $oldUniqueExists = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'activity_reads' 
            AND CONSTRAINT_NAME = 'activity_reads_user_id_penawaran_id_unique'
        ");
        
        if (empty($oldUniqueExists)) {
            Schema::table('activity_reads', function (Blueprint $table) {
                $table->unique(['user_id', 'penawaran_id']);
            });
        }
    }
};
