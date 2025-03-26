<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\YourModel;
use League\Csv\Reader;
use League\Csv\Exception;

class ImportData extends Command
{
    protected $signature = 'report-data:import {file}';
    protected $description = 'Import CSV data into the database';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath) || !is_readable($filePath)) {
            $this->error("File not found or not readable: $filePath");
            return;
        }

        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0); // First row as headers

            $records = $csv->getRecords();

            foreach ($records as $record) {
                YourModel::create([
                    'StartDate' => $record['StartDate'] ?? null,
                    'EndDate' => $record['EndDate'] ?? null,
                    'Status' => $record['Status'] ?? null,
                    'IPAddress' => $record['IPAddress'] ?? null,
                    'Progress' => $record['Progress'] ?? null,
                    'Duration' => $record['Duration (in seconds)'] ?? null,
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
                    'BEH_CLASSEXPECT_CL2' => $record['BEH_CLASSEXPECT_CL2'] ?? null,
                    'SSOS_NBHDSTRESS_1' => $record['SSOS_NBHDSTRESS_1'] ?? null,
                    'SSOS_FAMSTRESS_1' => $record['SSOS_FAMSTRESS_1'] ?? null,
                    'AMN_HOUSING_1' => $record['AMN_HOUSING_1'] ?? null,
                    'SS_CONNECT' => $record['SS_CONNECT'] ?? null,
                    'SS_PROSOCIAL' => $record['SS_PROSOCIAL'] ?? null,
                    'SS_PEERCOMM' => $record['SS_PEERCOMM'] ?? null,
                    'EWB_CONTENT' => $record['EWB_CONTENT'] ?? null,
                    'SIB_FRIEND' => $record['SIB_FRIEND'] ?? null,
                    'SIB_ADULT' => $record['SIB_ADULT'] ?? null,
                    'SEW_SCHOOLCONNECT' => $record['SEW_SCHOOLCONNECT'] ?? null,
                    'SSOS_BELONG2' => $record['SSOS_BELONG2'] ?? null,
                    'EWB_NERVOUS' => $record['EWB_NERVOUS'] ?? null,
                    'EWB_SAD' => $record['EWB_SAD'] ?? null,
                    'EWB_ACHES' => $record['EWB_ACHES'] ?? null,
                    'EWB_CONFIDENT_2' => $record['EWB_CONFIDENT_2'] ?? null,
                    'EWB_POSITIVE_2' => $record['EWB_POSITIVE_2'] ?? null,
                    'CONF_ALL' => $record['CONF_ALL'] ?? null,
                    'DEM_GRADE' => $record['DEM_GRADE'] ?? null,
                    'DEM_AGE' => $record['DEM_AGE'] ?? null,
                    'DEM_GENDER' => $record['DEM_GENDER'] ?? null,
                    'DEM_RACE' => $record['DEM_RACE'] ?? null,
                    'DEM_IEP' => $record['DEM_IEP'] ?? null,
                    'DEM_504' => $record['DEM_504'] ?? null,
                    'DEM_CI' => $record['DEM_CI'] ?? null,
                    'DEM_ELL' => $record['DEM_ELL'] ?? null,
                ]);
            }

            $this->info('Data imported successfully.');
        } catch (Exception $e) {
            $this->error("Error processing CSV: " . $e->getMessage());
        }
    }
}