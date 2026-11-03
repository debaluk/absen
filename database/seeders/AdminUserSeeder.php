<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('app.admin.email');
        $password = config('app.admin.password');

        if (! is_string($password) || $password === '') {
            throw new RuntimeException('ADMIN_PASSWORD wajib diisi sebelum menjalankan seeder admin.');
        }

        $username = strtolower((string) str($email)->before('@'));
        $username = preg_replace('/[^a-z0-9._-]/', '', $username) ?: 'admin';

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => config('app.admin.name'),
                'username' => $username,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'level' => 0,
            ]
        );

        if ((int) $user->level !== 0) {
            $user->forceFill(['level' => 0])->save();
        }
    }
}
