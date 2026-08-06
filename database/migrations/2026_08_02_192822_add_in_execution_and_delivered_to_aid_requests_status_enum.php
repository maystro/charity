<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite does not support ALTER TABLE MODIFY COLUMN for enums.
        // We recreate the table with the updated status CHECK constraint.
        DB::statement('PRAGMA foreign_keys=off');

        Schema::table('aid_requests', function (Blueprint $table) {
            $table->string('status', 50)->change();
        });

        DB::statement('PRAGMA foreign_keys=on');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot revert string back to enum in SQLite without data loss.
        // Keeping as string is safe since the application enforces valid values.
    }
};
