<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'hire_date')) {
                $table->date('hire_date')->nullable()->after('employment_status');
            }

            if (! Schema::hasColumn('employees', 'attendance_start_date')) {
                $table->date('attendance_start_date')->nullable()->after('hire_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('employees', 'hire_date')) {
                $columns[] = 'hire_date';
            }

            if (Schema::hasColumn('employees', 'attendance_start_date')) {
                $columns[] = 'attendance_start_date';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
