<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('employee_no', 30)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Nomor karyawan yang masih kosong harus dilengkapi sebelum rollback.
        if (\\Illuminate\\Support\\Facades\\DB::table('employees')->whereNull('employee_no')->exists()) {
            throw new \\RuntimeException('Lengkapi No. Karyawan yang kosong sebelum rollback migration ini.');
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->string('employee_no', 30)->nullable(false)->change();
        });
    }
};
