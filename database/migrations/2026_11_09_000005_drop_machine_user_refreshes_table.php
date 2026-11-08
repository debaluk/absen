<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('machine_user_refreshes');
    }

    public function down(): void
    {
        // Tabel antrean lama tidak dibuat ulang karena prosesnya sudah digantikan.
    }
};
