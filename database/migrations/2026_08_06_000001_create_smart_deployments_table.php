<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smart_deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('mode', ['local', 'server'])->default('local');
            $table->enum('status', ['pending', 'deploying', 'success', 'failed'])->default('pending');
            $table->integer('files_count')->default(0);
            $table->bigInteger('total_size')->default(0); // bytes
            $table->text('files_list')->nullable(); // JSON array of deployed files
            $table->text('notes')->nullable();
            $table->longText('server_response')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_deployments');
    }
};
