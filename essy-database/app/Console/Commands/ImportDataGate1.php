<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Gate1Report;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Str;
use Exception;

class ImportDataGate1 extends Command
{
    protected $signature = 'report-data:importgate1 {file}';
    protected $description = 'Import XLSX data for Gate 1 processing only';

    public function handle()
    {
        try {
            $batchId = (string) Str::uuid();
            $filePath = $this->argument('file');
            $importDate = now();

            $this->info("Gate 1 Import - File path provided: $filePath");

            if (!file_exists($filePath)) {
                $this->error("File not found: $filePath");
                return 1;
            }

            $spreadsheet = IOFactory::load($filePath);
            $this->info("Spreadsheet loaded successfully for Gate 1 processing.");

            $sheet = $spreadsheet->getSheetByName('Qualtrics Output');
            if (!$sheet) {
                $this->error('Sheet "Qualtrics Output" not found.');
                $this->info("Available sheets: " . json_encode($spreadsheet->getSheetNames()));
                return 1;
            }

            $rows = $sheet->toArray(null, true, true, true);

            $rawHeaders = $rows[1] ?? [];
            $headers = [];
            foreach ($rawHeaders as $key => $header) {
                $sanitizedHeader = preg_replace('/[^a-zA-Z0-9_]/', '', $header);
                // Normalize known misspellings: HYGEINE -> HYGIENE (handles any case)
                $sanitizedHeader = preg_replace('/HYGEINE/i', 'HYGIENE', $sanitizedHeader);
                $headers[$key] = $sanitizedHeader;
            }

            $recordsProcessed = 0;

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

                $data['created_at'] = $importDate;
                $data['updated_at'] = $importDate;
                $data['batch_id'] = $batchId;

                Gate1Report::create($data);
                $recordsProcessed++;
            }

            $this->info("Gate 1 batch import complete. Batch ID: $batchId");
            $this->info("Records processed: $recordsProcessed");
            
            // Store batch ID for Gate 1
            file_put_contents(storage_path("app/last_gate1_batch.txt"), $batchId);
            
            return 0;
        } catch (Exception $e) {
            $this->error('Error Importing Gate 1 Spreadsheet: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}

