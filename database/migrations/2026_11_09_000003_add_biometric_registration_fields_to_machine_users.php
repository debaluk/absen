<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('machine_users', function (Blueprint $table) {
            $table->unsignedTinyInteger('finger_count')->nullable()->after('updated')
                ->comment('Jumlah sidik jari; null jika belum diketahui');
            $table->string('biometric_status', 20)->default('unknown')->after('finger_count')
                ->comment('unknown, checked, unavailable');
            $table->dateTime('biometric_checked_at')->nullable()->after('biometric_status')
                ->comment('Waktu pemeriksaan registrasi biometrik terakhir');
        });
    }

    public function down(): void
    {
        Schema::table('machine_users', function (Blueprint $table) {
            $table->dropColumn([
                'finger_count',
                'biometric_status',
                'biometric_checked_at',
            ]);
        });
    }
};
