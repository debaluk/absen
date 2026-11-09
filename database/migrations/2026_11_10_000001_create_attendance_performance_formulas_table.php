<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_performance_formulas', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->string('status', 20)->default('draft');

            $table->decimal('attendance_weight', 5, 2)->default(50);
            $table->decimal('punctuality_weight', 5, 2)->default(30);
            $table->decimal('worktime_weight', 5, 2)->default(20);

            $table->unsignedSmallInteger('late_tolerance_minutes')->default(5);
            $table->unsignedSmallInteger('early_leave_tolerance_minutes')->default(0);
            $table->unsignedSmallInteger('late_penalty_unit_minutes')->default(30);
            $table->decimal('late_penalty_points', 6, 2)->default(1);
            $table->unsignedSmallInteger('early_leave_penalty_unit_minutes')->default(30);
            $table->decimal('early_leave_penalty_points', 6, 2)->default(1);

            $table->decimal('very_good_min', 5, 2)->default(98);
            $table->decimal('good_min', 5, 2)->default(95);
            $table->decimal('fair_min', 5, 2)->default(90);

            $table->timestamp('activated_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'effective_from', 'effective_until'], 'attendance_formula_validity_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_performance_formulas');
    }
};
