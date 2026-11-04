<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('application_settings')->updateOrInsert(
            ['key' => 'freedom_finger_auto_sync'],
            [
                'value' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('application_settings')
            ->where('key', 'freedom_finger_auto_sync')
            ->update([
                'value' => '0',
                'updated_at' => now(),
            ]);
    }
};
