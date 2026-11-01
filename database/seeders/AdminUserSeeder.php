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

        User::firstOrCreate(
            ['email' => $email],
            [
                'name' => config('app.admin.name'),
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );
    }
}
