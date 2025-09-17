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
        Schema::create('reports', function (Blueprint $table) {
            $table->id('report_id');
            $table->string('report_name', 255);
            $table->enum('report_type', ['user_progress', 'system_analytics', 'workout_performance', 'group_activity', 'ml_performance', 'service_health']);
            $table->unsignedBigInteger('generated_by');
            $table->json('report_parameters')->nullable();
            $table->json('report_data')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->enum('file_format', ['pdf', 'excel', 'csv', 'json'])->default('pdf');
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();

            $table->index(['report_type', 'generated_at']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
