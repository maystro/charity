<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('case_number')->unique();
            $table->string('case_type');
            $table->string('case_name');
            $table->string('community')->nullable();
            $table->text('detailed_address')->nullable();
            $table->string('phone')->nullable();
            $table->enum('family_type', ['بسيطة', 'مركبة']);
            $table->unsignedInteger('members_count')->default(0);
            $table->decimal('total_income', 12, 2)->default(0);
            $table->decimal('average_income_per_person', 12, 2)->default(0);
            $table->enum('status', ['draft', 'under_review', 'needs_completion', 'approved', 'rejected'])->default('draft');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('community');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('families');
    }
};
