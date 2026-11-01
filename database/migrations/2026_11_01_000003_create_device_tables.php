<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->string('cloud_id', 32)->primary();
            $table->string('name', 100)->nullable();
            $table->text('info')->nullable()->comment('Informasi firmware/perangkat; dapat berisi JSON');
            $table->char('fk_time', 14)->nullable()->comment('Format YYYYMMDDHHMMSS');
            $table->dateTime('last_activity')->nullable();
            $table->timestamps();
        });

        Schema::create('machine_users', function (Blueprint $table) {
            $table->string('cloud_id', 32);
            $table->string('pin', 32);
            $table->string('name', 100)->nullable();
            $table->string('privilege', 20)->nullable();
            $table->binary('data')->nullable()->comment('Payload biner mesin: header, JSON, template biometrik');
            $table->dateTime('updated')->nullable();
            $table->primary(['cloud_id', 'pin']);
            $table->index('name');
            $table->index('pin');
        });

        // Gunakan MEDIUMBLOB MySQL agar payload biometrik dapat melebihi kapasitas BLOB biasa.
        DB::statement("ALTER TABLE machine_users MODIFY data MEDIUMBLOB NULL COMMENT 'Payload biner mesin: header, JSON, template biometrik'");

        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->string('cloud_id', 32);
            $table->string('pin', 32);
            $table->dateTime('scan_date');
            $table->tinyInteger('verify')->nullable();
            $table->tinyInteger('status_scan')->nullable()->comment('Nilai sesuai protokol mesin; verifikasi arti 0/1 terhadap perangkat');
            $table->unsignedInteger('io_mode')->nullable();
            $table->unsignedInteger('verify_mode')->nullable();
            $table->string('photo', 255)->nullable();
            $table->dateTime('received_at')->useCurrent();
            $table->index(['cloud_id', 'pin', 'scan_date'], 'idx_logs_device_pin_time');
            $table->index('scan_date');
            $table->index(['pin', 'scan_date'], 'idx_logs_pin_date');
        });

        Schema::create('cmds', function (Blueprint $table) {
            $table->string('trans_id', 32)->primary();
            $table->string('cloud_id', 32)->nullable();
            $table->string('type', 50)->nullable();
            $table->string('cmd_code', 50)->nullable();
            $table->binary('param')->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('result_code', 20)->nullable();
            $table->mediumText('data')->nullable()->comment('Respons mesin; umumnya JSON');
            $table->dateTime('created')->nullable();
            $table->dateTime('sent')->nullable();
            $table->dateTime('done')->nullable();
            $table->index(['cloud_id', 'status'], 'idx_cmds_cloud_status');
            $table->index('created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmds');
        Schema::dropIfExists('logs');
        Schema::dropIfExists('machine_users');
        Schema::dropIfExists('devices');
    }
};
