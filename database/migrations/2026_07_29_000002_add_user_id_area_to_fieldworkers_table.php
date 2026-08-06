<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fieldworkers', function (Blueprint $table) {
            if (! Schema::hasColumn('fieldworkers', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->unique()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('fieldworkers', 'area')) {
                $table->string('area')->nullable()->after('governorate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fieldworkers', function (Blueprint $table) {
            if (Schema::hasColumn('fieldworkers', 'area')) {
                $table->dropColumn('area');
            }
            if (Schema::hasColumn('fieldworkers', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
