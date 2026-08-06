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
            $table->boolean('delivered')->default(false)->after('approved');
            $table->date('delivery_date')->nullable()->after('delivered');
            $table->text('delivery_notes')->nullable()->after('delivery_date');
            $table->foreignId('delivered_by')->nullable()->after('delivery_notes')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aid_request_items', function (Blueprint $table) {
            $table->dropForeign(['delivered_by']);
            $table->dropColumn(['delivered', 'delivery_date', 'delivery_notes', 'delivered_by']);
        });
    }
};
