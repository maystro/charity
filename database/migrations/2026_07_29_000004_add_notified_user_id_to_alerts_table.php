<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            if (! Schema::hasColumn('alerts', 'notified_user_id')) {
                $table->foreignId('notified_user_id')
                    ->nullable()
                    ->after('created_by')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('alerts', 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('dismissed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            if (Schema::hasColumn('alerts', 'read_at')) {
                $table->dropColumn('read_at');
            }
            if (Schema::hasColumn('alerts', 'notified_user_id')) {
                $table->dropForeign(['notified_user_id']);
                $table->dropColumn('notified_user_id');
            }
        });
    }
};
