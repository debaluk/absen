<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lepaskan relasi tabel registrasi dari tabel employees versi lama.
        if (Schema::hasTable('employee_device_registrations')) {
            Schema::table('employee_device_registrations', function ($table) {
                $table->dropForeign(['employee_id']);
            });
        }

        // Hapus tabel employees yang strukturnya masih sederhana.
        if (Schema::hasTable('employees')) {
            Schema::drop('employees');
        }

        // Gunakan struktur employees_old sebagai satu-satunya tabel employees.
        if (Schema::hasTable('employees_old') && ! Schema::hasTable('employees')) {
            Schema::rename('employees_old', 'employees');
        }

        // Arahkan kembali relasi registrasi perangkat ke tabel employees.
        if (Schema::hasTable('employee_device_registrations') && Schema::hasTable('employees')) {
            Schema::table('employee_device_registrations', function ($table) {
                $table->foreign('employee_id')
                    ->references('id')
                    ->on('employees')
                    ->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Perubahan nama dan struktur tabel ini tidak dibatalkan otomatis.
        throw new \RuntimeException('Migration penyatuan master karyawan tidak dapat di-rollback otomatis.');
    }
};
