<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holiday_work_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('holiday_id');
            $table->unsignedBigInteger('work_location_id');
            $table->timestamps();

            $table->foreign('holiday_id')->references('id')->on('holidays')->cascadeOnDelete();
            $table->foreign('work_location_id')->references('id')->on('work_locations')->restrictOnDelete();
            $table->unique(['holiday_id', 'work_location_id'], 'holiday_work_location_unique');
            $table->index('work_location_id');
        });

        // Gabungkan data lama dengan tanggal dan keterangan sama menjadi satu hari libur.
        $groups = DB::table('holidays')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($holiday) => $holiday->date . '|' . ($holiday->note ?? ''));

        foreach ($groups as $group) {
            $holidayIds = $group->pluck('id');
            $keepId = $holidayIds->first();
            $hasGlobalScope = $group->contains(fn ($holiday) => $holiday->work_location_id === null);

            if (! $hasGlobalScope) {
                $locationIds = $group->pluck('work_location_id')->filter()->unique();

                foreach ($locationIds as $locationId) {
                    DB::table('holiday_work_locations')->insertOrIgnore([
                        'holiday_id' => $keepId,
                        'work_location_id' => $locationId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            if ($holidayIds->count() > 1) {
                DB::table('holidays')->whereIn('id', $holidayIds->slice(1))->delete();
            }
        }

        DB::statement('ALTER TABLE holidays DROP INDEX holidays_date_scope_unique, DROP COLUMN scope_key');

        Schema::table('holidays', function (Blueprint $table) {
            $table->dropForeign(['work_location_id']);
            $table->dropColumn('work_location_id');
        });
    }

    public function down(): void
    {
        $multipleLocations = DB::table('holiday_work_locations')
            ->select('holiday_id')
            ->groupBy('holiday_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($multipleLocations) {
            throw new RuntimeException(
                'Rollback tidak dapat dilakukan: ada hari libur yang sudah berlaku untuk lebih dari satu cabang.'
            );
        }

        $duplicateScopes = DB::table('holidays')
            ->leftJoin('holiday_work_locations', 'holiday_work_locations.holiday_id', '=', 'holidays.id')
            ->select('holidays.date', DB::raw('IFNULL(holiday_work_locations.work_location_id, 0) as scope_key'))
            ->groupBy('holidays.date', DB::raw('IFNULL(holiday_work_locations.work_location_id, 0)'))
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicateScopes) {
            throw new RuntimeException(
                'Rollback tidak dapat dilakukan: terdapat tanggal dan cakupan cabang yang duplikat.'
            );
        }

        Schema::table('holidays', function (Blueprint $table) {
            $table->unsignedBigInteger('work_location_id')->nullable()->after('date');
        });

        DB::table('holidays')->orderBy('id')->chunkById(500, function ($holidays) {
            foreach ($holidays as $holiday) {
                $locationId = DB::table('holiday_work_locations')
                    ->where('holiday_id', $holiday->id)
                    ->value('work_location_id');

                DB::table('holidays')->where('id', $holiday->id)->update([
                    'work_location_id' => $locationId,
                ]);
            }
        });

        Schema::table('holidays', function (Blueprint $table) {
            $table->foreign('work_location_id')
                ->references('id')
                ->on('work_locations')
                ->restrictOnDelete();
        });

        DB::statement(
            'ALTER TABLE holidays
             ADD COLUMN scope_key BIGINT UNSIGNED
             GENERATED ALWAYS AS (IFNULL(work_location_id, 0)) STORED,
             ADD UNIQUE KEY holidays_date_scope_unique (date, scope_key)'
        );

        Schema::dropIfExists('holiday_work_locations');
    }
};
