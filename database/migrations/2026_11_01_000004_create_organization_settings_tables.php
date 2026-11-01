<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Struktur organisasi diselaraskan melalui migration fondasi terpusat.
    }

    public function down(): void
    {
        // Tabel organisasi merupakan data inti dan tidak dihapus otomatis.
    }
};
