<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable()->unique()->after('name');
        });

        // Isi username akun lama dari bagian email sebelum tanda @.
        DB::table('users')->select(['id', 'email'])->orderBy('id')->chunk(100, function ($users): void {
            foreach ($users as $user) {
                $base = strtolower((string) str($user->email)->before('@'));
                $base = preg_replace('/[^a-z0-9._-]/', '', $base) ?: 'user';
                $username = substr($base, 0, 50);
                $suffix = 1;

                while (DB::table('users')->where('username', $username)->exists()) {
                    $tail = (string) $suffix++;
                    $username = substr($base, 0, 50 - strlen($tail) - 1) . '-' . $tail;
                }

                DB::table('users')->where('id', $user->id)->update(['username' => $username]);
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
