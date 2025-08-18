<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('report_data', function (Blueprint $table) {
            // Add new fields that are missing from database
            $table->string('A_P_S_ARTICULATE_CL3')->nullable();
            $table->string('A_B_DIRECTIONS_CL1')->nullable();
            $table->string('A_B_DIRECTIONS_CL2')->nullable();
            $table->string('B_VERBAGGRESS')->nullable();
            $table->string('B_PHYSAGGRESS')->nullable();
            $table->string('P_ORAL')->nullable();
            $table->string('P_PHYS')->nullable();
            // O_P_hygiene_CL1 already exists in initial migration with correct spelling
            // O_P_HYGIENE_CL2 already exists, will be renamed to O_P_hygiene_CL2
            $table->string('S_O_COMMCONN_CL1')->nullable();
            $table->string('S_O_COMMCONN_CL2')->nullable();
        });

        // Migrate data from old field names to new field names
        DB::statement('UPDATE report_data SET A_B_DIRECTIONS_CL1 = A_DIRECTIONS WHERE A_DIRECTIONS IS NOT NULL');
        DB::statement('UPDATE report_data SET B_VERBAGGRESS = BEH_VERBAGGRESS WHERE BEH_VERBAGGRESS IS NOT NULL');
        DB::statement('UPDATE report_data SET B_PHYSAGGRESS = BEH_PHYSAGGRESS WHERE BEH_PHYSAGGRESS IS NOT NULL');
        DB::statement('UPDATE report_data SET P_ORAL = A_ORAL WHERE A_ORAL IS NOT NULL');
        DB::statement('UPDATE report_data SET P_PHYS = A_PHYS WHERE A_PHYS IS NOT NULL');
        // O_P_hygiene_CL1 already has correct spelling in initial migration
        DB::statement('UPDATE report_data SET S_O_COMMCONN_CL1 = S_COMMCONN WHERE S_COMMCONN IS NOT NULL');

        // Rename O_P_HYGIENE_CL2 to O_P_hygiene_CL2 (change case to match Excel)
        Schema::table('report_data', function (Blueprint $table) {
            $table->renameColumn('O_P_HYGIENE_CL2', 'O_P_hygiene_CL2');
        });

        // Drop old field names after data migration
        Schema::table('report_data', function (Blueprint $table) {
            $table->dropColumn([
                'A_DIRECTIONS',
                'BEH_VERBAGGRESS',
                'BEH_PHYSAGGRESS',
                'A_ORAL',
                'A_PHYS',
                // Don't drop O_P_HYGIENE_CL1 as it doesn't exist in initial migration
                // O_P_HYGIENE_CL2 was renamed to O_P_hygiene_CL2, not dropped
                'S_COMMCONN'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_data', function (Blueprint $table) {
            // Re-add old field names
            $table->string('A_DIRECTIONS')->nullable();
            $table->string('BEH_VERBAGGRESS')->nullable();
            $table->string('BEH_PHYSAGGRESS')->nullable();
            $table->string('A_ORAL')->nullable();
            $table->string('A_PHYS')->nullable();
            // Don't re-add O_P_HYGIENE_CL1 as it never existed (initial migration has O_P_hygiene_CL1)
            $table->string('S_COMMCONN')->nullable();
        });

        // Rename O_P_hygiene_CL2 back to O_P_HYGIENE_CL2
        Schema::table('report_data', function (Blueprint $table) {
            $table->renameColumn('O_P_hygiene_CL2', 'O_P_HYGIENE_CL2');
        });

        // Migrate data back from new field names to old field names
        DB::statement('UPDATE report_data SET A_DIRECTIONS = A_B_DIRECTIONS_CL1 WHERE A_B_DIRECTIONS_CL1 IS NOT NULL');
        DB::statement('UPDATE report_data SET BEH_VERBAGGRESS = B_VERBAGGRESS WHERE B_VERBAGGRESS IS NOT NULL');
        DB::statement('UPDATE report_data SET BEH_PHYSAGGRESS = B_PHYSAGGRESS WHERE B_PHYSAGGRESS IS NOT NULL');
        DB::statement('UPDATE report_data SET A_ORAL = P_ORAL WHERE P_ORAL IS NOT NULL');
        DB::statement('UPDATE report_data SET A_PHYS = P_PHYS WHERE P_PHYS IS NOT NULL');
        // O_P_hygiene_CL1 stays as is (correct spelling from initial migration)
        DB::statement('UPDATE report_data SET S_COMMCONN = S_O_COMMCONN_CL1 WHERE S_O_COMMCONN_CL1 IS NOT NULL');

        // Drop new field names (except O_P_hygiene_CL1 which existed in initial migration)
        Schema::table('report_data', function (Blueprint $table) {
            $table->dropColumn([
                'A_P_S_ARTICULATE_CL3',
                'A_B_DIRECTIONS_CL1',
                'A_B_DIRECTIONS_CL2',
                'B_VERBAGGRESS',
                'B_PHYSAGGRESS',
                'P_ORAL',
                'P_PHYS',
                // Don't drop O_P_hygiene_CL1 as it existed in initial migration
                // O_P_hygiene_CL2 was renamed back to O_P_HYGIENE_CL2, not dropped
                'S_O_COMMCONN_CL1',
                'S_O_COMMCONN_CL2'
            ]);
        });
    }
};
