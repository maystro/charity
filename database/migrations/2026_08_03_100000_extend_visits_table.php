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
        Schema::table('visits', function (Blueprint $table) {
            $table->string('visit_number')->unique()->nullable()->after('id');
            $table->string('visit_type')->default('other')->after('visit_number');
            $table->string('priority')->default('medium')->after('visit_type');
            $table->text('purpose')->nullable()->after('priority');
            $table->foreignId('branch_id')->nullable()->after('aid_request_id')->constrained()->nullOnDelete();
            $table->integer('area_id')->nullable()->after('branch_id');
            $table->foreignId('representative_id')->nullable()->after('area_id')->constrained('fieldworkers')->nullOnDelete();
            $table->foreignId('researcher_id')->nullable()->after('representative_id')->constrained('fieldworkers')->nullOnDelete();
            $table->dateTime('scheduled_at')->nullable()->after('researcher_id');
            $table->dateTime('started_at')->nullable()->after('scheduled_at');
            $table->dateTime('completed_at')->nullable()->after('started_at');
            $table->integer('duration_minutes')->nullable()->after('completed_at');
            $table->string('contacted_person')->nullable()->after('duration_minutes');
            $table->string('contacted_person_relation')->nullable()->after('contacted_person');
            $table->text('address_snapshot')->nullable()->after('contacted_person_relation');
            $table->decimal('latitude', 10, 8)->nullable()->after('address_snapshot');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->boolean('location_verified')->default(false)->after('longitude');
            $table->text('outcome_summary')->nullable()->after('location_verified');
            $table->text('recommendations')->nullable()->after('outcome_summary');
            $table->dateTime('next_follow_up_at')->nullable()->after('recommendations');
            $table->text('not_completed_reason')->nullable()->after('next_follow_up_at');
            $table->foreignId('created_by')->nullable()->after('not_completed_reason')->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->softDeletes()->after('completed_by');
            $table->boolean('is_overdue')->default(false)->after('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropForeign(['completed_by']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['researcher_id']);
            $table->dropForeign(['representative_id']);
            $table->dropForeign(['branch_id']);
            $table->dropColumn([
                'visit_number', 'visit_type', 'priority', 'purpose',
                'branch_id', 'area_id', 'representative_id', 'researcher_id',
                'scheduled_at', 'started_at', 'completed_at', 'duration_minutes',
                'contacted_person', 'contacted_person_relation',
                'address_snapshot', 'latitude', 'longitude', 'location_verified',
                'outcome_summary', 'recommendations', 'next_follow_up_at',
                'not_completed_reason', 'created_by', 'completed_by',
                'deleted_at', 'is_overdue',
            ]);
        });
    }
};
