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
        Schema::create('fieldworkers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();          // كود المندوب
            $table->string('name');                     // اسم المندوب
            $table->string('phone')->nullable();        // الهاتف
            $table->string('governorate')->nullable();   // المحافظة
            $table->string('status')->default('active'); // active / inactive
            $table->decimal('latitude', 10, 7)->nullable();   // خط العرض (تحديد الموقع على الخريطة)
            $table->decimal('longitude', 10, 7)->nullable();  // خط الطول
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fieldworkers');
    }
};
