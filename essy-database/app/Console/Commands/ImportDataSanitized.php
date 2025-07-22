<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReportData;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Str;
use Exception;

class ImportDataSanitized extends Command
{
    protected $signature = 'report-data:importxlsx {file}';
    protected $description = 'Import XLSX data into the database, starting from row 3, sanitizing column headers';

    public function handle()
    {
        try {
            $batchId = (string) Str::uuid();
            $filePath = $this->argument('file');

            $this->info("File path provided: $filePath");

            if (!file_exists($filePath)) {
                $this->error("File not found: $filePath");
                return 1;
            }

            $spreadsheet = IOFactory::load($filePath);
            $this->info("Spreadsheet loaded successfully.");

            $sheet = $spreadsheet->getSheetByName('Qualtrics Output');
            if (!$sheet) {
                $this->error('Sheet "Qualtrics Output" not found.');
                $this->info("Available sheets: " . json_encode($spreadsheet->getSheetNames()));
                return 1;
            }

            $rows = $sheet->toArray(null, true, true, true);
            $this->info("Row data: " . json_encode($rows));

            $rawHeaders = $rows[1] ?? [];
            $headers = [];
            foreach ($rawHeaders as $key => $header) {
                $sanitizedHeader = preg_replace('/[^a-zA-Z0-9_]/', '', $header);
                $this->info("Sanitized header: $sanitizedHeader");
                $headers[$key] = $sanitizedHeader;
            }

            foreach ($rows as $rowIndex => $row) {
                $this->info("Processing row $rowIndex: " . json_encode($row));
                if ($rowIndex == 1 || $rowIndex == 2 || collect($row)->every(fn($cell) => trim((string) $cell) === '')) {
                    continue;
                }

                $data = [];
                foreach ($headers as $col => $sanitizedKey) {
                    $data[$sanitizedKey] = $row[$col] ?? null;
                }

                $this->info("Data being inserted: " . json_encode($data));

                if (empty(trim($data['ResponseId'] ?? ''))) {
                    continue;
                }

                if (isset($data['StartDate']) && str_contains($data['StartDate'], 'Ignore')) {
                    continue;
                }

                $data['created_at'] = now();
                $data['updated_at'] = now();
                $data['batch_id'] = $batchId;

                ReportData::create($data);
            }

            $this->info("Batch import complete. Batch ID: $batchId");
            file_put_contents(storage_path("app/last_batch.txt"), $batchId);
        } catch (Exception $e) {
            $this->error('Error Importing Spreadsheet: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
