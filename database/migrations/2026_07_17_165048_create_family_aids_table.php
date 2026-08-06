<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_aids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('aid_type'); // AidType enum value
            $table->boolean('eligible')->default(false);
            $table->text('reasons')->nullable();
            $table->timestamps();

            $table->unique(['family_id', 'aid_type']);
            $table->index('family_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('family_aids');
    }
};
