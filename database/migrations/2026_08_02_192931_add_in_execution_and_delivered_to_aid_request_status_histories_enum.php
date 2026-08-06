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
        DB::statement('PRAGMA foreign_keys=off');

        Schema::table('aid_request_status_histories', function (Blueprint $table) {
            $table->string('from_status', 50)->nullable()->change();
            $table->string('to_status', 50)->change();
        });

        DB::statement('PRAGMA foreign_keys=on');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot revert string back to enum in SQLite without data loss.
    }
};
