<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('report_data', function (Blueprint $table) {
            $table->id();
            $table->dateTime('StartDate')->nullable();
            $table->dateTime('EndDate')->nullable();
            $table->string('Status')->nullable();
            $table->ipAddress('IPAddress')->nullable();
            $table->integer('Progress')->nullable();
            $table->integer('Duration')->nullable();
            $table->boolean('Finished')->nullable();
            $table->dateTime('RecordedDate')->nullable();
            $table->string('ResponseId')->nullable();
            $table->string('RecipientLastName')->nullable();
            $table->string('RecipientFirstName')->nullable();
            $table->string('RecipientEmail')->nullable();
            $table->string('ExternalReference')->nullable();
            $table->decimal('LocationLatitude', 10, 7)->nullable();
            $table->decimal('LocationLongitude', 10, 7)->nullable();
            $table->string('DistributionChannel')->nullable();
            $table->string('UserLanguage')->nullable();
            $table->string('INITIALS')->nullable();

            // Domains
            $table->string('AS_DOMAIN')->nullable();
            $table->string('BEH_DOMAIN')->nullable();
            $table->string('SEW_DOMAIN')->nullable();
            $table->string('PH2_DOMAIN')->nullable();
            $table->string('SOS2_DOMAIN')->nullable();
            $table->string('ATT_C_DOMAIN')->nullable();

            // Assessment & Behavioral Scores
            $table->integer('CONF_GATE1')->nullable();
            $table->integer('AS_READING')->nullable();
            $table->integer('AS_WRITING')->nullable();
            $table->integer('AS_MATH')->nullable();
            $table->integer('AS_ENGAGE')->nullable();
            $table->integer('AS_PLAN')->nullable();
            $table->integer('AS_TURNIN')->nullable();
            $table->integer('AS_INTEREST')->nullable();
            $table->integer('AS_PERSIST')->nullable();
            $table->integer('AS_INITIATE')->nullable();
            $table->integer('EWB_GROWTH')->nullable();
            $table->integer('AS_DIRECTIONS2')->nullable();

            // Behavioral Indicators
            $table->integer('BEH_CLASSEXPECT_CL1')->nullable();
            $table->integer('BEH_IMPULSE_1')->nullable();
            $table->integer('SS_ADULTSCOMM_1')->nullable();
            $table->integer('EWB_CONFIDENT_1')->nullable();
            $table->integer('EWB_POSITIVE_1')->nullable();
            $table->integer('PH_ARTICULATE_1')->nullable();
            $table->integer('SSOS_ACTIVITY3_1')->nullable();
            $table->integer('EWB_CLINGY')->nullable();
            $table->integer('BEH_DESTRUCT')->nullable();
            $table->integer('BEH_PHYSAGGRESS')->nullable();
            $table->integer('BEH_SNEAK')->nullable();
            $table->integer('BEH_VERBAGGRESS')->nullable();
            $table->integer('BEH_BULLY')->nullable();
            $table->integer('SIB_PUNITIVE')->nullable();
            $table->integer('BEH_CLASSEXPECT_CL2')->nullable();
            $table->integer('BEH_IMPULSE_2')->nullable();
            $table->integer('SSOS_NBHDSTRESS_1')->nullable();
            $table->integer('SSOS_FAMSTRESS_1')->nullable();
            $table->integer('AMN_HOUSING_1')->nullable();

            // Social & Emotional Indicators
            $table->integer('SS_CONNECT')->nullable();
            $table->integer('SS_PROSOCIAL')->nullable();
            $table->integer('SS_PEERCOMM')->nullable();
            $table->integer('EWB_CONTENT')->nullable();
            $table->integer('SIB_FRIEND')->nullable();
            $table->integer('SIB_ADULT')->nullable();
            $table->integer('SEW_SCHOOLCONNECT')->nullable();
            $table->integer('SSOS_BELONG2')->nullable();
            $table->integer('EWB_NERVOUS')->nullable();
            $table->integer('EWB_SAD')->nullable();
            $table->integer('EWB_ACHES_1')->nullable();
            $table->integer('EWB_CONFIDENT_2')->nullable();
            $table->integer('EWB_POSITIVE_2')->nullable();
            $table->integer('SS_ADULTSCOMM_2')->nullable();
            $table->integer('SSOS_ACTIVITY3_2')->nullable();

            // Household & Resources
            $table->integer('AMN_RESOURCE')->nullable();
            $table->integer('SSOS_RECIPROCAL')->nullable();
            $table->integer('SSOS_POSADULT')->nullable();
            $table->integer('SSOS_ADULTBEST')->nullable();
            $table->integer('SSOS_TALK')->nullable();
            $table->integer('SSOS_FAMILY')->nullable();
            $table->integer('SSOS_ROUTINE')->nullable();
            $table->integer('AMN_HOUSING_2')->nullable();
            $table->integer('SSOS_FAMSTRESS_2')->nullable();
            $table->integer('SSOS_NBHDSTRESS_2')->nullable();
            $table->integer('AMN_CLOTHES_1')->nullable();
            $table->integer('AMN_HYGIENE')->nullable();
            $table->integer('AMN_HUNGER_1')->nullable();

            // Physical & Participation Indicators
            $table->integer('SSOS_ACTIVITY3')->nullable();
            $table->integer('PH_SIGHT')->nullable();
            $table->integer('PH_HEAR')->nullable();
            $table->integer('PH_PARTICIPATE')->nullable();
            $table->integer('AMN_HYGIENE')->nullable();
            $table->integer('AMN_ORAL')->nullable();
            $table->integer('AMN_PHYS')->nullable();
            $table->integer('AMN_HUNGER_2')->nullable();
            $table->integer('PH_ARTICULATE_2')->nullable();
            $table->integer('AMN_CLOTHES_2')->nullable();
            $table->integer('EWB_ACHES_2')->nullable();
            $table->integer('PH_RESTED1')->nullable();
            $table->integer('BEH_SH')->nullable();
            $table->integer('EWB_REGULATE')->nullable();
            $table->integer('EWB_WITHDRAW')->nullable();
            $table->integer('SIB_EXCLUDE')->nullable();
            $table->integer('SIB_BULLIED')->nullable();

            // Relationship Indicators
            $table->integer('RELATION_TIME')->nullable();
            $table->integer('RELATION_AMOUNT')->nullable();
            $table->integer('RELATION_CLOSE')->nullable();
            $table->integer('RELATION_CONFLICT')->nullable();

            // Demographics
            $table->integer('CONF_ALL')->nullable();
            $table->integer('DEM_GRADE')->nullable();
            $table->integer('DEM_AGE')->nullable();
            $table->string('DEM_GENDER')->nullable();
            $table->string('DEM_GENDER_8_TEXT')->nullable();
            $table->string('DEM_LANG')->nullable();
            $table->string('DEM_LANG_9_TEXT')->nullable();
            $table->string('DEM_ETHNIC')->nullable();
            $table->string('DEM_RACE')->nullable();
            $table->string('DEM_RACE_14_TEXT')->nullable();
            $table->boolean('DEM_IEP')->nullable();
            $table->boolean('DEM_504')->nullable();
            $table->boolean('DEM_CI')->nullable();
            $table->boolean('DEM_ELL')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('report_data');
    }
};
