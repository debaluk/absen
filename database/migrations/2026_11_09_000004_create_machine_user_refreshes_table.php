<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_user_refreshes', function (Blueprint $table) {
            $table->string('cloud_id', 32)->primary();
            $table->string('trans_id', 32)->nullable();
            $table->string('status', 30)->default('idle');
            $table->longText('pin_list')->nullable();
            $table->unsignedInteger('next_pin_index')->default(0);
            $table->string('current_pin', 32)->nullable();
            $table->dateTime('requested_at')->nullable();
            $table->unsignedInteger('total_pins')->default(0);
            $table->unsignedInteger('processed_pins')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->text('message')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'updated_at'], 'idx_machine_user_refresh_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_user_refreshes');
    }
};
