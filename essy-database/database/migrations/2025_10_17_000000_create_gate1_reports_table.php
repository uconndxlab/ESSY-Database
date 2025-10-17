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
        Schema::create('gate1_reports', function (Blueprint $table) {
            $table->id();
            $table->string('batch_id')->nullable()->index();
            $table->string('excel_file_path')->nullable();
            
            // Basic Qualtrics fields
            $table->string('StartDate')->nullable();
            $table->string('EndDate')->nullable();
            $table->string('Status')->nullable();
            $table->string('IPAddress')->nullable();
            $table->string('Progress')->nullable();
            $table->string('Duration')->nullable();
            $table->string('Finished')->nullable();
            $table->string('RecordedDate')->nullable();
            $table->string('ResponseId')->nullable();
            $table->string('RecipientLastName')->nullable();
            $table->string('RecipientFirstName')->nullable();
            $table->string('RecipientEmail')->nullable();
            $table->string('ExternalReference')->nullable();
            $table->string('LocationLatitude')->nullable();
            $table->string('LocationLongitude')->nullable();
            $table->string('DistributionChannel')->nullable();
            $table->string('UserLanguage')->nullable();
            
            // Student and teacher info
            $table->string('FN_STUDENT')->nullable();
            $table->string('LN_STUDENT')->nullable();
            $table->string('FN_TEACHER')->nullable();
            $table->string('LN_TEACHER')->nullable();
            $table->string('SCHOOL')->nullable();
            
            // Gate 1 Domain Ratings (the 6 broad screening domains)
            $table->text('A_DOMAIN')->nullable();      // Academic Skills
            $table->text('ATT_DOMAIN')->nullable();    // Attendance
            $table->text('B_DOMAIN')->nullable();      // Behavior
            $table->text('P_DOMAIN')->nullable();      // Physical Health
            $table->text('S_DOMAIN')->nullable();      // Social & Emotional Well-Being
            $table->text('O_DOMAIN')->nullable();      // Supports Outside of School
            
            // Gate 1 specific fields
            $table->text('COMMENTS_GATE1')->nullable();
            $table->string('TIMING_GATE1_FirstClick')->nullable();
            $table->string('TIMING_GATE1_LastClick')->nullable();
            $table->string('TIMING_GATE1_PageSubmit')->nullable();
            $table->string('TIMING_GATE1_ClickCount')->nullable();
            
            // Demographics
            $table->string('DEM_RACE')->nullable();
            $table->string('DEM_RACE_14_TEXT')->nullable();
            $table->string('DEM_ETHNIC')->nullable();
            $table->string('DEM_GENDER')->nullable();
            $table->string('DEM_ELL')->nullable();
            $table->string('DEM_IEP')->nullable();
            $table->string('DEM_504')->nullable();
            $table->string('DEM_CI')->nullable();
            $table->string('DEM_GRADE')->nullable();
            $table->string('DEM_CLASSTEACH')->nullable();
            $table->string('SPEEDING_GATE1')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gate1_reports');
    }
};

