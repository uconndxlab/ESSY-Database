<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReportData;
use App\Models\DecisionRule;
use App\Services\DecisionRulesService;
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
            
            // Extract and store decision rules from the uploaded Excel file
            $this->extractAndStoreDecisionRules($spreadsheet, $filePath);
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
                // Normalize known misspellings: HYGEINE -> HYGIENE (handles any case)
                $sanitizedHeader = preg_replace('/HYGEINE/i', 'HYGIENE', $sanitizedHeader);
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

    /**
     * Extract decision rules from Excel file and store them in database
     */
    private function extractAndStoreDecisionRules($spreadsheet, $filePath): void
    {
        try {
            $this->info("Extracting decision rules from Excel file...");
            
            // Try to find the Decision Rules sheet
            $decisionRulesSheet = null;
            $sheetNames = ['Decision Rules', 'decision_rules', 'DecisionRules', 'Decision_Rules'];
            
            foreach ($sheetNames as $sheetName) {
                try {
                    $decisionRulesSheet = $spreadsheet->getSheetByName($sheetName);
                    $this->info("Found decision rules sheet: $sheetName");
                    break;
                } catch (Exception $e) {
                    // Continue trying other sheet names
                }
            }
            
            if (!$decisionRulesSheet) {
                $this->warn("No 'Decision Rules' sheet found. Available sheets: " . implode(', ', $spreadsheet->getSheetNames()));
                $this->warn("Decision rules will not be imported. PDFs will use Essential Items and database fallback.");
                return;
            }
            
            $rulesImported = 0;
            $rulesUpdated = 0;
            $row = 2; // Start from row 2 (assuming row 1 is headers)
            
            while (true) {
                $itemCode = trim($decisionRulesSheet->getCell('A' . $row)->getCalculatedValue() ?? '');
                $frequency = trim($decisionRulesSheet->getCell('B' . $row)->getCalculatedValue() ?? '');
                $decisionText = trim($decisionRulesSheet->getCell('C' . $row)->getCalculatedValue() ?? '');
                
                // Stop if we hit an empty row
                if (empty($itemCode) && empty($frequency) && empty($decisionText)) {
                    break;
                }
                
                // Normalize item_code to uppercase for consistency (handles hygiene, HYGIENE, etc.)
                $itemCode = strtoupper($itemCode);
                
                // Skip incomplete rows
                if (empty($itemCode) || empty($frequency) || empty($decisionText)) {
                    $this->warn("Skipping incomplete row $row: itemCode='$itemCode', frequency='$frequency', decisionText='" . substr($decisionText, 0, 50) . "'");
                    $row++;
                    continue;
                }
                
                // Skip the header/placeholder rows
                if ($itemCode === 'QUALTRICS COLUMN') {
                    $row++;
                    continue;
                }
                
                // Create or update decision rule
                $decisionRule = DecisionRule::updateOrCreate(
                    [
                        'item_code' => $itemCode,
                        'frequency' => $frequency
                    ],
                    [
                        'decision_text' => $decisionText,
                        'domain' => $this->getDomainFromItemCode($itemCode)
                    ]
                );
                
                if ($decisionRule->wasRecentlyCreated) {
                    $rulesImported++;
                } else {
                    $rulesUpdated++;
                }
                
                $row++;
            }
            
            $this->info("Decision rules extraction complete:");
            $this->info("- Rules imported: $rulesImported");
            $this->info("- Rules updated: $rulesUpdated");
            $this->info("- Total rows processed: " . ($row - 2));
            
        } catch (Exception $e) {
            $this->error("Failed to extract decision rules: " . $e->getMessage());
            $this->warn("Continuing with student data import. PDFs will use Essential Items and database fallback.");
        }
    }

    /**
     * Determine domain from item code
     */
    private function getDomainFromItemCode(string $itemCode): string
    {
        $prefix = substr($itemCode, 0, 1);
        
        switch ($prefix) {
            case 'A': return 'Academic Skills';
            case 'B': return 'Behavior';
            case 'P': return 'Physical Health';
            case 'S': return 'Social & Emotional Well-Being';
            case 'O': return 'Supports Outside of School';
            case 'E': return 'Essential Items';
            default: return 'Unknown';
        }
    }
}
