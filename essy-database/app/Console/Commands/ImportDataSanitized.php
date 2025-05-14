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

        // Load the spreadsheet
        $spreadsheet = IOFactory::load($filePath);

        // Attempt to get the desired sheet
        $sheet = $spreadsheet->getSheetByName('Qualtrics Output');

        if (!$sheet) {
            $this->error('Sheet "output-sample" not found.');
            $sheetNames = $spreadsheet->getSheetNames();
            $this->warn('Available sheets: ' . implode(', ', $sheetNames));
            return 1;
        }

        // Convert sheet to array
        $rows = $sheet->toArray(null, true, true, true);

        // Row 1 = headers, Row 2 = description, Row 3+ = data
        $rawHeaders = $rows[1] ?? [];
        $headers = [];

        foreach ($rawHeaders as $key => $header) {
            if (!$header) continue;
            $headers[$key] = preg_replace('/[^a-zA-Z0-9_]/', '', $header);
        }

        foreach ($rows as $rowIndex => $row) {
            // Explicitly skip rows 1 and 2
            if ($rowIndex == 1 || $rowIndex == 2) {
                continue;
            }
        
            // Skip empty or whitespace-only rows
            if (collect($row)->every(fn($cell) => trim((string) $cell) === '')) {
                continue;
            }
        
            $data = [];
            foreach ($headers as $col => $sanitizedKey) {
                $data[$sanitizedKey] = $row[$col] ?? null;
            }
        
            ReportData::create($data);
        }        

        $this->info('Data Imported Successfully.');
    }
}
