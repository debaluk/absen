<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_schedule_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->default(1)->primary();
            $table->json('days');
            $table->unsignedSmallInteger('tolerance')->default(15);
            $table->json('overtime');
            $table->unsignedSmallInteger('overtime_min')->default(60);
            $table->json('overtime_max');
            $table->unsignedSmallInteger('min_gap')->default(60);
            $table->timestamps();
        });

        Schema::create('schedules', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('name', 100);
            $table->string('color', 20)->nullable();
            $table->json('days');
            $table->json('overtime');
            $table->json('overtime_max');
            $table->timestamps();
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->date('date')->primary();
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('leaves', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('pin', 32);
            $table->date('from');
            $table->date('to');
            $table->string('kind', 20)->comment('izin, sakit, cuti, dinas');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['pin', 'from', 'to'], 'idx_leaves_pin_dates');
            $table->index(['from', 'to'], 'idx_leaves_dates');
        });

        Schema::create('corrections', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('pin', 32);
            $table->date('date');
            $table->time('time');
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index(['pin', 'date'], 'idx_corrections_pin_date');
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corrections');
        Schema::dropIfExists('leaves');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('work_schedule_settings');
    }
};
