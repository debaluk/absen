<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_data_syncs', function (Blueprint $table) {
            $table->string('cloud_id', 32)->primary();
            $table->string('status', 20)->default('queued');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('checked')->default(0);
            $table->unsignedInteger('unavailable')->default(0);
            $table->unsignedInteger('pending')->default(0);
            $table->text('message')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_data_syncs');
    }
};
