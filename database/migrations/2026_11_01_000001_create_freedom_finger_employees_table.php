<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freedom_finger_employees', function (Blueprint $table) {
            $table->id();
            $table->string('cloud_id', 100);
            $table->string('pin', 50);
            $table->string('name')->nullable();
            $table->dateTime('last_synced_at')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->unique(['cloud_id', 'pin']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freedom_finger_employees');
    }
};
