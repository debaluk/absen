<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->string('pin', 32)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('leaves')->whereNull('pin')->update(['pin' => '']);

        Schema::table('leaves', function (Blueprint $table) {
            $table->string('pin', 32)->nullable(false)->change();
        });
    }
};
