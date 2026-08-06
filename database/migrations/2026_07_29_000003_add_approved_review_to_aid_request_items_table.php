<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aid_request_items', function (Blueprint $table) {
            if (! Schema::hasColumn('aid_request_items', 'approved')) {
                $table->boolean('approved')->default(false)->after('sort_order');
            }
            if (! Schema::hasColumn('aid_request_items', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('approved');
            }
            if (! Schema::hasColumn('aid_request_items', 'reviewer_id')) {
                $table->foreignId('reviewer_id')
                    ->nullable()
                    ->after('reviewed_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('aid_request_items', function (Blueprint $table) {
            if (Schema::hasColumn('aid_request_items', 'reviewer_id')) {
                $table->dropForeign(['reviewer_id']);
                $table->dropColumn('reviewer_id');
            }
            if (Schema::hasColumn('aid_request_items', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }
            if (Schema::hasColumn('aid_request_items', 'approved')) {
                $table->dropColumn('approved');
            }
        });
    }
};
