<?php

use Illuminate\\Database\\Migrations\\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel company_profiles, job_positions, dan work_locations
        // sudah tersedia pada database yang digunakan.
    }

    public function down(): void
    {
        // Tabel organisasi merupakan struktur inti dan tidak dihapus saat rollback.
    }
};
