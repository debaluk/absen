<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machine_users', function (Blueprint $table) {
            $table->dateTime('biometric_source_updated_at')->nullable()->after('biometric_checked_at')
                ->comment('Timestamp cache mesin yang menjadi patokan pemeriksaan biometrik terakhir');
        });
    }

    public function down(): void
    {
        Schema::table('machine_users', function (Blueprint $table) {
            $table->dropColumn('biometric_source_updated_at');
        });
    }
};
