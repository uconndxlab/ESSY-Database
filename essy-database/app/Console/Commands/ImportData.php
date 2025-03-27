<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReportData;
use League\Csv\Reader;

class ImportData extends Command
{
    protected $signature = 'report-data:import {file}';
    protected $description = 'Import CSV data into the database';

    public function handle()
    {
        $filePath = $this->argument('file');

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0); // First row as headers
        $records = $csv->getRecords();

        foreach ($records as $record) {
            ReportData::create([
                'StartDate' => $record['StartDate'] ?? null,
                'EndDate' => $record['EndDate'] ?? null,
                'Status' => $record['Status'] ?? null,
                'IPAddress' => $record['IPAddress'] ?? null,
                'Progress' => $record['Progress'] ?? null,
                'Duration' => $record['Duration'] ?? null,
                'Finished' => $record['Finished'] ?? null,
                'RecordedDate' => $record['RecordedDate'] ?? null,
                'ResponseId' => $record['ResponseId'] ?? null,
                'RecipientLastName' => $record['RecipientLastName'] ?? null,
                'RecipientFirstName' => $record['RecipientFirstName'] ?? null,
                'RecipientEmail' => $record['RecipientEmail'] ?? null,
                'ExternalReference' => $record['ExternalReference'] ?? null,
                'LocationLatitude' => $record['LocationLatitude'] ?? null,
                'LocationLongitude' => $record['LocationLongitude'] ?? null,
                'DistributionChannel' => $record['DistributionChannel'] ?? null,
                'UserLanguage' => $record['UserLanguage'] ?? null,
                'INITIALS' => $record['INITIALS'] ?? null,
                'AS_DOMAIN' => $record['AS_DOMAIN'] ?? null,
                'BEH_DOMAIN' => $record['BEH_DOMAIN'] ?? null,
                'SEW_DOMAIN' => $record['SEW_DOMAIN'] ?? null,
                'PH2_DOMAIN' => $record['PH2_DOMAIN'] ?? null,
                'SOS2_DOMAIN' => $record['SOS2_DOMAIN'] ?? null,
                'ATT_C_DOMAIN' => $record['ATT_C_DOMAIN'] ?? null,
                'CONF_GATE1' => $record['CONF_GATE1'] ?? null,
                'AS_READING' => $record['AS_READING'] ?? null,
                'AS_WRITING' => $record['AS_WRITING'] ?? null,
                'AS_MATH' => $record['AS_MATH'] ?? null,
                'AS_ENGAGE' => $record['AS_ENGAGE'] ?? null,
                'AS_PLAN' => $record['AS_PLAN'] ?? null,
                'AS_TURNIN' => $record['AS_TURNIN'] ?? null,
                'AS_INTEREST' => $record['AS_INTEREST'] ?? null,
                'AS_PERSIST' => $record['AS_PERSIST'] ?? null,
                'AS_INITIATE' => $record['AS_INITIATE'] ?? null,
                'EWB_GROWTH' => $record['EWB_GROWTH'] ?? null,
                'AS_DIRECTIONS2' => $record['AS_DIRECTIONS2'] ?? null,
                'BEH_CLASSEXPECT_CL1' => $record['BEH_CLASSEXPECT_CL1'] ?? null,
                'BEH_IMPULSE' => $record['BEH_IMPULSE'] ?? null,
                'SS_ADULTSCOMM_1' => $record['SS_ADULTSCOMM_1'] ?? null,
                'EWB_CONFIDENT_1' => $record['EWB_CONFIDENT_1'] ?? null,
                'EWB_POSITIVE_1' => $record['EWB_POSITIVE_1'] ?? null,
                'PH_ARTICULATE' => $record['PH_ARTICULATE'] ?? null,
                'SSOS_ACTIVITY3_1' => $record['SSOS_ACTIVITY3_1'] ?? null,
                'EWB_CLINGY' => $record['EWB_CLINGY'] ?? null,
                'BEH_DESTRUCT' => $record['BEH_DESTRUCT'] ?? null,
                'BEH_PHYSAGGRESS' => $record['BEH_PHYSAGGRESS'] ?? null,
                'BEH_SNEAK' => $record['BEH_SNEAK'] ?? null,
                'BEH_VERBAGGRESS' => $record['BEH_VERBAGGRESS'] ?? null,
                'BEH_BULLY' => $record['BEH_BULLY'] ?? null,
                'SIB_PUNITIVE' => $record['SIB_PUNITIVE'] ?? null,
                'RELATION_TIME' => $record['RELATION_TIME'] ?? null,
                'RELATION_AMOUNT' => $record['RELATION_AMOUNT'] ?? null,
                'RELATION_CLOSE' => $record['RELATION_CLOSE'] ?? null,
                'RELATION_CONFLICT' => $record['RELATION_CONFLICT'] ?? null,
                'CONF_ALL' => $record['CONF_ALL'] ?? null,
                'DEM_GRADE' => $record['DEM_GRADE'] ?? null,
                'DEM_AGE' => $record['DEM_AGE'] ?? null,
                'DEM_GENDER' => $record['DEM_GENDER'] ?? null,
                'DEM_GENDER_8_TEXT' => $record['DEM_GENDER_8_TEXT'] ?? null,
                'DEM_LANG' => $record['DEM_LANG'] ?? null,
                'DEM_LANG_9_TEXT' => $record['DEM_LANG_9_TEXT'] ?? null,
                'DEM_ETHNIC' => $record['DEM_ETHNIC'] ?? null,
                'DEM_RACE' => $record['DEM_RACE'] ?? null,
                'DEM_RACE_14_TEXT' => $record['DEM_RACE_14_TEXT'] ?? null,
                'DEM_IEP' => $record['DEM_IEP'] ?? null,
                'DEM_504' => $record['DEM_504'] ?? null,
                'DEM_CI' => $record['DEM_CI'] ?? null,
                'DEM_ELL' => $record['DEM_ELL'] ?? null,
            ]);
        }

        $this->info('Data imported successfully.');
    }
}
