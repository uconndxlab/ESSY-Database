<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('report_data', function (Blueprint $table) {
            // Rename columns to fix spelling: hygiene -> HYGIENE
            $table->renameColumn('O_P_hygiene_CL1', 'O_P_HYGIENE_CL1');
            $table->renameColumn('O_P_hygiene_CL2', 'O_P_HYGIENE_CL2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('report_data', function (Blueprint $table) {
            // Revert the column names back to original spelling
            $table->renameColumn('O_P_HYGIENE_CL1', 'O_P_hygiene_CL1');
            $table->renameColumn('O_P_HYGIENE_CL2', 'O_P_hygiene_CL2');
        });
    }
};
