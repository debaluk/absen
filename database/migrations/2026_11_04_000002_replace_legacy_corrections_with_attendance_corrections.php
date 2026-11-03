<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('corrections') && Schema::hasTable('attendance_corrections')) {
            $legacyRows = DB::table('corrections')
                ->selectRaw("pin, date, MIN(time) as check_in, CASE WHEN COUNT(*) > 1 THEN MAX(time) ELSE NULL END as check_out, GROUP_CONCAT(DISTINCT reason SEPARATOR '; ') as reason")
                ->groupBy('pin', 'date')
                ->orderBy('date')
                ->get();

            foreach ($legacyRows as $row) {
                $employees = DB::table('employees')
                    ->where('machine_user_pin', $row->pin)
                    ->pluck('id');

                // PIN lama tidak menyimpan cloud_id. Hentikan migrasi agar data
                // tidak salah dipindahkan atau hilang jika pemilik PIN ambigu.
                if ($employees->count() !== 1) {
                    throw new RuntimeException('Migrasi koreksi dihentikan: PIN '.$row->pin.' pada '.$row->date.' tidak terhubung tepat ke satu karyawan.');
                }

                $employeeId = (int) $employees->first();
                $exists = DB::table('attendance_corrections')
                    ->where('employee_id', $employeeId)
                    ->where('date', $row->date)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('attendance_corrections')->insert([
                    'employee_id' => $employeeId,
                    'date' => $row->date,
                    'check_in' => $row->check_in,
                    'check_out' => $row->check_out,
                    'reason' => $row->reason ?: 'Migrasi dari koreksi absensi lama',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::dropIfExists('corrections');
    }

    public function down(): void
    {
        if (! Schema::hasTable('corrections')) {
            Schema::create('corrections', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('pin', 32);
                $table->date('date');
                $table->time('time');
                $table->text('reason')->nullable();
                $table->timestamps();
                $table->index(['pin', 'date'], 'idx_corrections_pin_date');
                $table->index('date');
            });
        }

        if (Schema::hasTable('attendance_corrections')) {
            $rows = DB::table('attendance_corrections')
                ->join('employees', 'employees.id', '=', 'attendance_corrections.employee_id')
                ->get([
                    'employees.machine_user_pin as pin',
                    'attendance_corrections.date',
                    'attendance_corrections.check_in',
                    'attendance_corrections.check_out',
                    'attendance_corrections.reason',
                ]);

            foreach ($rows as $row) {
                foreach (array_filter([$row->check_in, $row->check_out]) as $time) {
                    DB::table('corrections')->insert([
                        'id' => (string) Str::uuid(),
                        'pin' => $row->pin,
                        'date' => $row->date,
                        'time' => $time,
                        'reason' => $row->reason,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
};
