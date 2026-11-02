<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('position_id')
                ->nullable()
                ->after('name')
                ->constrained('positions')
                ->nullOnDelete();

            $table->foreignId('work_location_id')
                ->nullable()
                ->after('position_id')
                ->constrained('work_locations')
                ->nullOnDelete();

            // Identitas PIN dipasangkan dengan cloud_id karena PIN dapat berulang di mesin berbeda.
            $table->string('machine_user_cloud_id', 32)->nullable()->after('work_location_id');
            $table->string('machine_user_pin', 32)->nullable()->after('machine_user_cloud_id');

            $table->unique(
                ['machine_user_cloud_id', 'machine_user_pin'],
                'employees_machine_user_unique'
            );

            $table->foreign(
                ['machine_user_cloud_id', 'machine_user_pin'],
                'employees_machine_user_fk'
            )->references(['cloud_id', 'pin'])
                ->on('machine_users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign('employees_machine_user_fk');
            $table->dropUnique('employees_machine_user_unique');
            $table->dropConstrainedForeignId('work_location_id');
            $table->dropConstrainedForeignId('position_id');
            $table->dropColumn(['machine_user_cloud_id', 'machine_user_pin']);
        });
    }
};
