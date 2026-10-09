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
        Schema::table('aid_requests', function (Blueprint $table) {
            $table->index('status', 'aid_requests_status_index');
            $table->index(['status', 'created_at'], 'aid_requests_status_created_at_index');
            $table->index(['status', 'needed_by'], 'aid_requests_status_needed_by_index');
            $table->index(['submitted_by', 'status'], 'aid_requests_submitted_by_status_index');
        });

        Schema::table('aid_request_items', function (Blueprint $table) {
            $table->index(['aid_request_id', 'approved'], 'aid_request_items_request_approved_index');
        });

        Schema::table('family_assessments', function (Blueprint $table) {
            $table->index('approved_at', 'family_assessments_approved_at_index');
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->index('scheduled_at', 'visits_scheduled_at_index');
            $table->index('status', 'visits_status_index');
            $table->index(['status', 'scheduled_at'], 'visits_status_scheduled_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aid_requests', function (Blueprint $table) {
            $table->dropIndex('aid_requests_status_index');
            $table->dropIndex('aid_requests_status_created_at_index');
            $table->dropIndex('aid_requests_status_needed_by_index');
            $table->dropIndex('aid_requests_submitted_by_status_index');
        });

        Schema::table('aid_request_items', function (Blueprint $table) {
            $table->dropIndex('aid_request_items_request_approved_index');
        });

        Schema::table('family_assessments', function (Blueprint $table) {
            $table->dropIndex('family_assessments_approved_at_index');
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->dropIndex('visits_scheduled_at_index');
            $table->dropIndex('visits_status_index');
            $table->dropIndex('visits_status_scheduled_at_index');
        });
    }
};
