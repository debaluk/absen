<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->text('address')->nullable()->after('name');
            $table->string('phone', 30)->nullable()->after('address');
            $table->string('birth_place', 100)->nullable()->after('phone');
            $table->date('birth_date')->nullable()->after('birth_place');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['address', 'phone', 'birth_place', 'birth_date']);
        });
    }
};
