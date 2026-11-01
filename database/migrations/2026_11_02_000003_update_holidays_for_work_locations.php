<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            $table->dropPrimary();
            $table->id()->first();
            $table->unsignedBigInteger('work_location_id')->nullable()->after('date');
        });

        Schema::table('holidays', function (Blueprint $table) {
            $table->foreign('work_location_id')
                ->references('id')
                ->on('work_locations')
                ->restrictOnDelete();
        });

        // NULL mewakili seluruh cabang. Kolom ini membuat kombinasi tanggal dan cakupan unik,
        // termasuk untuk baris global yang work_location_id-nya NULL.
        DB::statement(
            'ALTER TABLE holidays
             ADD COLUMN scope_key BIGINT UNSIGNED
             GENERATED ALWAYS AS (IFNULL(work_location_id, 0)) STORED,
             ADD UNIQUE KEY holidays_date_scope_unique (date, scope_key)'
        );
    }

    public function down(): void
    {
        $duplicates = DB::table('holidays')
            ->select('date')
            ->groupBy('date')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicates) {
            throw new RuntimeException(
                'Rollback tidak dapat dilakukan: terdapat tanggal libur yang digunakan oleh lebih dari satu cakupan cabang.'
            );
        }

        DB::statement('ALTER TABLE holidays DROP INDEX holidays_date_scope_unique, DROP COLUMN scope_key');

        Schema::table('holidays', function (Blueprint $table) {
            $table->dropForeign(['work_location_id']);
            $table->dropColumn('work_location_id');
            $table->dropPrimary();
            $table->dropColumn('id');
            $table->primary('date');
        });
    }
};
