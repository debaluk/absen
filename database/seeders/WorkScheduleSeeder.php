<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkScheduleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('work_schedule_settings')->updateOrInsert(
            ['id' => 1],
            [
                'days' => json_encode([
                    null,
                    ['start' => '08:00', 'end' => '17:00'],
                    ['start' => '08:00', 'end' => '17:00'],
                    ['start' => '08:00', 'end' => '17:00'],
                    ['start' => '08:00', 'end' => '17:00'],
                    ['start' => '08:00', 'end' => '17:00'],
                    null,
                ], JSON_THROW_ON_ERROR),
                'tolerance' => 15,
                'overtime' => json_encode(array_fill(0, 7, true), JSON_THROW_ON_ERROR),
                'overtime_min' => 60,
                'overtime_max' => json_encode(array_fill(0, 7, 0), JSON_THROW_ON_ERROR),
                'min_gap' => 60,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
