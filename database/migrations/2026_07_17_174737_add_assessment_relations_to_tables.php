<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add current_assessment_id to families
        Schema::table('families', function (Blueprint $table) {
            $table->foreignId('current_assessment_id')
                ->nullable()
                ->after('case_number')
                ->constrained('family_assessments')
                ->nullOnDelete();
        });

        // Add family_assessment_id to all sub-tables
        $subTables = [
            'family_members',
            'family_income_sources',
            'family_resources',
            'family_burdens',
            'family_housing',
            'family_aids',
        ];

        foreach ($subTables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('family_assessment_id')
                    ->nullable()
                    ->after('family_id')
                    ->constrained('family_assessments')
                    ->cascadeOnDelete();
                $blueprint->index('family_assessment_id');
            });
        }
    }

    public function down(): void
    {
        $subTables = [
            'family_aids',
            'family_housing',
            'family_burdens',
            'family_resources',
            'family_income_sources',
            'family_members',
        ];

        foreach ($subTables as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('family_assessment_id');
            });
        }

        Schema::table('families', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_assessment_id');
        });
    }
};
