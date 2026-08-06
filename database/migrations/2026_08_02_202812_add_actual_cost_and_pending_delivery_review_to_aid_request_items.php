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
        Schema::table('aid_request_items', function (Blueprint $table) {
            $table->decimal('actual_cost', 12, 2)->nullable()->after('estimated_total');
            $table->date('purchase_date')->nullable()->after('actual_cost');
            $table->text('purchase_notes')->nullable()->after('purchase_date');
            $table->foreignId('purchased_by')->nullable()->after('purchase_notes')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aid_request_items', function (Blueprint $table) {
            $table->dropForeign(['purchased_by']);
            $table->dropColumn(['actual_cost', 'purchase_date', 'purchase_notes', 'purchased_by']);
        });
    }
};
