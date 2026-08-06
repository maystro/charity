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
            // عدد الأيام الفاصلة بين مرات تنفيذ المساعدة الدورية
            $table->integer('recurrence_interval_days')->nullable()->after('frequency');
            // تاريخ بدء التنفيذ (الفارق الزمني للمساعدة الدورية)
            $table->date('execution_start_date')->nullable()->after('recurrence_end');
            // نوع المساعدة (يخزن قيمة AidType enum)
            $table->string('aid_type')->nullable()->after('aid_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aid_request_items', function (Blueprint $table) {
            $table->dropColumn(['recurrence_interval_days', 'execution_start_date', 'aid_type']);
        });
    }
};
