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
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id('api_log_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('endpoint', 255);
            $table->enum('http_method', ['GET', 'POST', 'PUT', 'DELETE', 'PATCH']);
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->integer('status_code');
            $table->integer('response_time_ms')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('service_from', 50)->nullable();
            $table->string('service_to', 50)->nullable();
            $table->timestamp('timestamp')->useCurrent();

            $table->index(['timestamp', 'service_to']);
            $table->index(['status_code', 'timestamp']);
            $table->index(['response_time_ms', 'timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
