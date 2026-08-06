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
        // aid_requests main table
        Schema::create('aid_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('area_id')->nullable();
            $table->foreignId('representative_id')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('submitted_by')->nullable()->constrained('users');
            $table->string('source_type');
            $table->string('applicant_name')->nullable();
            $table->string('applicant_relation')->nullable();
            $table->string('applicant_phone')->nullable();
            $table->enum('request_type', ['وقتية', 'دورية', 'طارئة']);
            $table->enum('priority', ['عادية', 'متوسطة', 'مرتفعة', 'عاجلة جداً']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('requested_at');
            $table->date('needed_by')->nullable();
            $table->foreignId('campaign_id')->nullable();
            $table->enum('status', ['draft', 'submitted', 'under_review', 'needs_completion', 'approved', 'partially_approved', 'rejected', 'cancelled', 'completed']);
            $table->text('internal_notes')->nullable();
            $table->text('exception_reason')->nullable();
            $table->text('duplicate_reason')->nullable();
            $table->decimal('total_estimated_amount', 12, 2)->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // aid_request_items table
        Schema::create('aid_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aid_request_id')->constrained('aid_requests')->onDelete('cascade');
            $table->foreignId('category_id');
            $table->foreignId('subcategory_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('execution_type');
            $table->decimal('quantity', 10, 2);
            $table->foreignId('unit_id')->nullable();
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('estimated_total', 12, 2);
            $table->enum('recurrence_type', ['وقتية', 'دورية']);
            $table->string('frequency')->nullable();
            $table->date('recurrence_start')->nullable();
            $table->date('recurrence_end')->nullable();
            $table->integer('installments_count')->nullable();
            $table->integer('preferred_due_day')->nullable();
            $table->boolean('stop_when_research_expires')->default(false);
            $table->boolean('reminder_enabled')->default(false);
            $table->integer('reminder_days')->nullable();
            $table->enum('priority', ['عادية', 'متوسطة', 'مرتفعة', 'عاجلة جداً']);
            $table->string('payee_type')->nullable();
            $table->string('payee_name')->nullable();
            $table->string('payee_phone')->nullable();
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // aid_request_attachments table
        Schema::create('aid_request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aid_request_id')->constrained('aid_requests')->onDelete('cascade');
            $table->foreignId('aid_request_item_id')->nullable()->constrained('aid_request_items')->nullOnDelete();
            $table->foreignId('attachment_type_id');
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('path');
            $table->string('disk')->default('local');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->date('document_date')->nullable();
            $table->date('expires_at')->nullable();
            $table->enum('verification_status', ['غير مراجع', 'صحيح', 'غير واضح', 'منتهي', 'مرفوض'])->default('غير مراجع');
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
        });

        // aid_request_status_histories table
        Schema::create('aid_request_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aid_request_id')->constrained('aid_requests')->onDelete('cascade');
            $table->enum('from_status', ['draft', 'submitted', 'under_review', 'needs_completion', 'approved', 'partially_approved', 'rejected', 'cancelled', 'completed'])->nullable();
            $table->enum('to_status', ['draft', 'submitted', 'under_review', 'needs_completion', 'approved', 'partially_approved', 'rejected', 'cancelled', 'completed']);
            $table->foreignId('changed_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aid_request_status_histories');
        Schema::dropIfExists('aid_request_attachments');
        Schema::dropIfExists('aid_request_items');
        Schema::dropIfExists('aid_requests');
    }
};
