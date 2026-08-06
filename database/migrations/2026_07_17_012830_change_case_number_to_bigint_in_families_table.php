<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Change case_number from string to bigint (8-digit number).
     */
    public function up(): void
    {
        // SQLite doesn't support altering column type directly,
        // so we drop and recreate (data is regenerated anyway).
        Schema::table('families', function (Blueprint $table) {
            $table->dropUnique('families_case_number_unique');
        });

        Schema::table('families', function (Blueprint $table) {
            $table->bigInteger('case_number')->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->dropUnique('families_case_number_unique');
        });

        Schema::table('families', function (Blueprint $table) {
            $table->string('case_number')->unique()->change();
        });
    }
};
