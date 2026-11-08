<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('employees', 'include_in_reports')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->boolean('include_in_reports')->default(true)->after('employment_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('employees', 'include_in_reports')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('include_in_reports');
            });
        }
    }
};
