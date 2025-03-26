<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('report_data', function (Blueprint $table) {
            $table->id();
            $table->dateTime('StartDate')->nullable();
            $table->dateTime('EndDate')->nullable();
            $table->string('Status')->nullable();
            $table->string('IPAddress')->nullable();
            $table->integer('Progress')->default(0);
            $table->integer('Duration')->nullable();
            $table->boolean('Finished')->default(false);
            $table->dateTime('RecordedDate')->nullable();
            $table->string('ResponseId')->nullable();
            $table->float('LocationLatitude')->nullable();
            $table->float('LocationLongitude')->nullable();
            $table->string('DistributionChannel')->nullable();
            $table->string('UserLanguage')->default('EN');
            $table->string('INITIALS')->nullable();
            $table->string('AS_DOMAIN')->nullable();
            $table->string('BEH_DOMAIN')->nullable();
            $table->string('SEW_DOMAIN')->nullable();
            $table->string('PH2_DOMAIN')->nullable();
            $table->string('SOS2_DOMAIN')->nullable();
            $table->string('ATT_C_DOMAIN')->nullable();
            $table->string('CONF_GATE1')->nullable();
            $table->float('RELATION_TIME')->nullable();
            $table->float('RELATION_AMOUNT')->nullable();
            $table->float('RELATION_CLOSE')->nullable();
            $table->integer('RELATION_CONFLICT')->nullable();
            $table->string('Confidence_Level')->nullable();
            $table->float('TIMING_RELATION_A_First_Click')->nullable();
            $table->float('TIMING_RELATION_A_Last_Click')->nullable();
            $table->float('TIMING_RELATION_A_Page_Submit')->nullable();
            $table->integer('TIMING_RELATION_A_Click_Count')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_data');
    }
};
