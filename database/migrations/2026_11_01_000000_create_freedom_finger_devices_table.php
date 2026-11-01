<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freedom_finger_devices', function (Blueprint $table) {
            $table->id();
            $table->string('cloud_id', 100)->unique();
            $table->string('device_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('is_connected')->default(false);
            $table->dateTime('last_activity')->nullable();
            $table->dateTime('last_synced_at')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->index('is_connected');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freedom_finger_devices');
    }
};
