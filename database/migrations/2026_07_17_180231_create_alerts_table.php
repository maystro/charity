<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // reassessment_due, reassessment_overdue, etc.
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('severity')->default('info'); // info, warning, critical
            $table->string('status')->default('active'); // active, dismissed, resolved
            $table->morphs('alertable'); // e.g., Family
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'severity']);
            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
