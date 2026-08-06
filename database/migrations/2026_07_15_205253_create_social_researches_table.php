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
        Schema::create('social_researches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('research_number')->unique();
            $table->string('research_type')->default('initial');
            $table->date('conducted_at')->nullable();
            $table->date('approved_at')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('eligibility_degree')->nullable();
            $table->decimal('average_income', 12, 2)->default(0);
            $table->decimal('net_income', 12, 2)->default(0);
            $table->text('recommendation')->nullable();
            $table->text('committee_decision')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['family_id', 'status']);
            $table->index('expiry_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_researches');
    }
};
