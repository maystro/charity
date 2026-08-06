<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('round')->default(1);
            $table->string('status')->default('draft');

            // Family data snapshot
            $table->string('case_type');
            $table->string('case_name');
            $table->string('community')->nullable();
            $table->text('detailed_address')->nullable();
            $table->string('phone')->nullable();
            $table->enum('family_type', ['بسيطة', 'مركبة']);
            $table->unsignedInteger('members_count')->default(0);
            $table->decimal('total_income', 12, 2)->default(0);
            $table->decimal('average_income_per_person', 12, 2)->default(0);
            $table->text('review_notes')->nullable();
            $table->text('rejection_reason')->nullable();

            // User tracking
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('submitted_at')->nullable();
            $table->datetime('approved_at')->nullable();
            $table->datetime('rejected_at')->nullable();
            $table->timestamps();

            $table->unique(['family_id', 'round']);
            $table->index('family_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_assessments');
    }
};
