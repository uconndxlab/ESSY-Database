<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReportData;
use League\Csv\Reader;
use Carbon\Carbon;

class ImportReportData extends Command
{
    protected $signature = 'import:report_data {file}';
    protected $description = 'Import report data from a CSV file into the report_data table';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("File not found: $filePath");
            return;
        }

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        foreach ($csv as $record) {
            ReportData::updateOrCreate(
                ['ResponseId' => trim($record['ResponseId'])],
                [
                    'StartDate' => isset($record['StartDate']) ? Carbon::parse($record['StartDate']) : null,
                    'EndDate' => isset($record['EndDate']) ? Carbon::parse($record['EndDate']) : null,
                    'Status' => $record['Status'] ?? null,
                    'IPAddress' => $record['IPAddress'] ?? null,
                    'Progress' => $record['Progress'] ?? 0,
                    'Duration' => $record['Duration (in seconds)'] ?? null,
                    'Finished' => filter_var($record['Finished'], FILTER_VALIDATE_BOOLEAN),
                    'RecordedDate' => isset($record['RecordedDate']) ? Carbon::parse($record['RecordedDate']) : null,
                    'LocationLatitude' => $record['LocationLatitude'] ?? null,
                    'LocationLongitude' => $record['LocationLongitude'] ?? null,
                    'DistributionChannel' => $record['DistributionChannel'] ?? null,
                    'UserLanguage' => $record['UserLanguage'] ?? 'EN',
                    'INITIALS' => $record['INITIALS'] ?? null,
                    'AS_DOMAIN' => $record['AS_DOMAIN'] ?? null,
                    'BEH_DOMAIN' => $record['BEH_DOMAIN'] ?? null,
                    'SEW_DOMAIN' => $record['SEW_DOMAIN'] ?? null,
                    'PH2_DOMAIN' => $record['PH2_DOMAIN'] ?? null,
                    'SOS2_DOMAIN' => $record['SOS2_DOMAIN'] ?? null,
                    'ATT_C_DOMAIN' => $record['ATT_C_DOMAIN'] ?? null,
                    'CONF_GATE1' => $record['CONF_GATE1'] ?? null,
                    'RELATION_TIME' => $record['RELATION_TIME'] ?? null,
                    'RELATION_AMOUNT' => $record['RELATION_AMOUNT'] ?? null,
                    'RELATION_CLOSE' => $record['RELATION_CLOSE'] ?? null,
                    'RELATION_CONFLICT' => $record['RELATION_CONFLICT'] ?? null,
                    'Confidence_Level' => $record['Confidence_Level'] ?? null,
                    'TIMING_RELATION_A_First_Click' => $record['TIMING_RELATION_A_First Click'] ?? null,
                    'TIMING_RELATION_A_Last_Click' => $record['TIMING_RELATION_A_Last Click'] ?? null,
                    'TIMING_RELATION_A_Page_Submit' => $record['TIMING_RELATION_A_Page Submit'] ?? null,
                    'TIMING_RELATION_A_Click_Count' => $record['TIMING_RELATION_A_Click Count'] ?? null,
                ]
            );
        }

        $this->info('Report data imported successfully.');
    }
}
