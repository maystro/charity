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
            $table->date('next_due_date')->nullable()->after('execution_start_date');
            $table->index('next_due_date', 'aid_request_items_next_due_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aid_request_items', function (Blueprint $table) {
            $table->dropIndex('aid_request_items_next_due_date_index');
            $table->dropColumn('next_due_date');
        });
    }
};
