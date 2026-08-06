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
        Schema::create('family_housing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('housing_type')->nullable();
            $table->string('housing_type_other')->nullable();
            $table->string('residence_status')->nullable();
            $table->integer('floors_count')->nullable();
            $table->integer('rooms_count')->nullable();
            $table->string('roof_type')->nullable();
            $table->boolean('has_water')->default(false);
            $table->boolean('has_electricity')->default(false);
            $table->boolean('has_sewage')->default(false);
            $table->text('finishing_description')->nullable();
            $table->text('electrical_appliances')->nullable();
            $table->text('home_furniture')->nullable();
            $table->text('other_equipment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_housing');
    }
};
