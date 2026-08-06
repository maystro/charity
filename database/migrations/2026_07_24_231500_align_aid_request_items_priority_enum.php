<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Align the aid_request_items.priority enum with AidRequestPriority
     * values ('عادية','متوسطة','مرتفعة','عاجلة جداً') matching aid_requests.priority.
     *
     * SQLite does not support ALTER COLUMN; rebuild the table instead.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            // تعذر تعديل enum مباشرة في SQLite؛ أعد بناء الجدول.
            Schema::dropIfExists('aid_request_items');

            Schema::create('aid_request_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('aid_request_id')->constrained('aid_requests')->onDelete('cascade');
                $table->foreignId('category_id');
                $table->foreignId('subcategory_id')->nullable();
                $table->string('aid_type')->nullable();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('execution_type');
                $table->decimal('quantity', 10, 2);
                $table->foreignId('unit_id')->nullable();
                $table->decimal('unit_cost', 12, 2);
                $table->decimal('estimated_total', 12, 2);
                $table->enum('recurrence_type', ['وقتية', 'دورية']);
                $table->string('frequency')->nullable();
                $table->integer('recurrence_interval_days')->nullable();
                $table->date('recurrence_start')->nullable();
                $table->date('recurrence_end')->nullable();
                $table->date('execution_start_date')->nullable();
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

            return;
        }

        Schema::table('aid_request_items', function (Blueprint $table) {
            $table->enum('priority', ['عادية', 'متوسطة', 'مرتفعة', 'عاجلة جداً'])->change();
        });
    }

    public function down(): void
    {
        // Adjustment is forward-only; leave the enum aligned on rollback.
    }
};
