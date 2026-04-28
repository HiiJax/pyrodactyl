<?php

use Illuminate\Support\Facades\DB;
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
        switch (DB::connection()->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            case 'mysql':
                Schema::table('backups', function (Blueprint $table) {
                    // Modify the disk column to support elytra adapter
                    $table->enum('disk', ['wings', 's3', 'rustic_local', 'rustic_s3', 'elytra'])
                        ->default('wings')
                        ->change();
                });
                break;

            case 'pgsql':
                Schema::table('backups', function (Blueprint $table) {
                    // Drop the check constraint first
                    DB::statement("
                        ALTER TABLE backups 
                        DROP CONSTRAINT IF EXISTS backups_disk_check
                    ");

                    // Re-add the check constraint with elytra
                    DB::statement("
                        ALTER TABLE backups
                        ADD CONSTRAINT backups_disk_check
                        CHECK (disk IN ('wings', 's3', 'rustic_local', 'rustic_s3', 'elytra'))
                    ");
                });
                break;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        switch (DB::connection()->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME)) {
            case 'mysql':
                Schema::table('backups', function (Blueprint $table) {
                    // Revert disk column to original enum values
                    $table->enum('disk', ['wings', 's3', 'rustic_local', 'rustic_s3'])
                        ->default('wings')
                        ->change();
                });
                break;

            case 'pgsql':
                Schema::table('backups', function (Blueprint $table) {
                    // Drop the check constraint first
                    DB::statement("ALTER TABLE backups DROP CONSTRAINT backups_disk_check");

                    // Re-add original constraint
                    DB::statement("
                        ALTER TABLE backups
                        ADD CONSTRAINT backups_disk_check
                        CHECK (disk IN ('wings', 's3', 'rustic_local', 'rustic_s3'))
                    ");
                });
                break;
        }
    }
};
