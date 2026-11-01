<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices_location', function (Blueprint $table) {
            $table->string('cloud_id', 32)->primary();
            $table->unsignedBigInteger('work_location_id');

            $table->foreign('cloud_id')
                ->references('cloud_id')
                ->on('devices')
                ->cascadeOnDelete();

            $table->foreign('work_location_id')
                ->references('id')
                ->on('work_locations')
                ->cascadeOnDelete();

            $table->index('work_location_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices_location');
    }
};
