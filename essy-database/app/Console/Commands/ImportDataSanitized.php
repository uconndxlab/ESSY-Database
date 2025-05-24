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

            // Load spreadsheet
            $spreadsheet = IOFactory::load($filePath);

            $sheet = $spreadsheet->getSheetByName('Qualtrics Output');
            if (!$sheet) {
                throw new Exception('Sheet "Qualtrics Output" not found.');
            }

            $rows = $sheet->toArray(null, true, true, true);
            $rawHeaders = $rows[1] ?? [];
            $headers = [];

            foreach ($rawHeaders as $key => $header) {
                if (!$header) continue;
                $headers[$key] = preg_replace('/[^a-zA-Z0-9_]/', '', $header);
            }

            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex == 1 || $rowIndex == 2 || collect($row)->every(fn($cell) => trim((string) $cell) === '')) {
                    continue;
                }

                $data = [];
                foreach ($headers as $col => $sanitizedKey) {
                    $data[$sanitizedKey] = $row[$col] ?? null;
                }

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
            return 1;
        }
    }
}
