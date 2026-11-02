<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Simpan satu record untuk setiap identitas log yang sama sebelum membuat unique key.
        DB::statement('
            DELETE duplicate_log
            FROM logs AS duplicate_log
            INNER JOIN logs AS original_log
                ON original_log.cloud_id = duplicate_log.cloud_id
                AND original_log.pin = duplicate_log.pin
                AND original_log.scan_date = duplicate_log.scan_date
                AND original_log.id < duplicate_log.id
        ');

        Schema::table('logs', function (Blueprint $table) {
            $table->unique(
                ['cloud_id', 'pin', 'scan_date'],
                'uq_logs_device_pin_time'
            );
        });
    }

    public function down(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            $table->dropUnique('uq_logs_device_pin_time');
        });
    }
};
