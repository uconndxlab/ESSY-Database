<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReportData;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportDataSanitized extends Command
{
    protected $signature = 'report-data:importxlsx {file}';
    protected $description = 'Import XLSX data into the database, starting from row 3, sanitizing column headers';

    public function handle()
    {
        $filePath = $this->argument('file');
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        $rawHeaders = $rows[1] ?? [];
        $headers = [];

        foreach ($rawHeaders as $key => $header) {
            if (!$header) continue;
            $headers[$key] = preg_replace('/[^a-zA-Z0-9_]/', '', $header);
        }

        for ($i = 3; $i <= count($rows); $i++) {
            $row = $rows[$i] ?? null;
            if (!$row || empty(array_filter($row))) continue;

            $data = [];
            foreach ($headers as $col => $sanitizedKey) {
                $data[$sanitizedKey] = $row[$col] ?? null;
            }

            ReportData::create($data);
        }

        $this->info('Data Imported Successfully.');
    }
}
