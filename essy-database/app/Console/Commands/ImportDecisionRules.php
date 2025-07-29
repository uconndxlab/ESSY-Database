<?php

namespace App\Console\Commands;

use App\Models\DecisionRule;
use App\ValueObjects\ImportResult;
use Exception;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportDecisionRules extends Command
{
    protected $signature = 'essy:import-decision-rules {file : Path to Excel file}';
    protected $description = 'Import decision rules from Excel file';

    private const SHEET_NAME = 'Decision Rules';
    private const HEADER_ROW = 39;
    private const DATA_START_ROW = 40;
    private const DATA_END_ROW = 115;
    
    // Expected frequency columns in the Excel file
    private const FREQUENCY_COLUMNS = [
        'Almost Always',
        'Frequently', 
        'Sometimes',
        'Occasionally',
        'Almost Never'
    ];

    // Cross-loaded domain mappings based on existing system
    private const CROSS_LOADED_DOMAINS = [
        'AS_' => 'Academic Skills',
        'BEH_' => 'Behavior',
        'EWB_' => 'Social-Emotional Well-being',
        'PH_' => 'Physical Health',
        'SSOS_' => 'School/Social Support',
        'ATT_' => 'Attendance'
    ];

    public function handle(): int
    {
        $filePath = $this->argument('file');

        try {
            $this->info("Starting import from: {$filePath}");
            
            // Validate file exists
            if (!file_exists($filePath)) {
                $this->error("File not found: {$filePath}");
                return 1;
            }

            // Process Excel file
            $result = $this->processExcelFile($filePath);
            
            // Display results
            $this->displayResults($result);
            
            // Return 0 if we had any successful imports, 1 only if complete failure
            return ($result->getSuccessCount() > 0) ? 0 : 1;
            
        } catch (Exception $e) {
            $this->error("Import failed: {$e->getMessage()}");
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Process the Excel file and import decision rules
     */
    private function processExcelFile(string $filePath): ImportResult
    {
        $imported = 0;
        $updated = 0;
        $errors = 0;
        $errorMessages = [];

        try {
            // Load spreadsheet
            $this->info("Loading Excel file...");
            $spreadsheet = IOFactory::load($filePath);
            
            // Get Decision Rules sheet
            $sheet = $spreadsheet->getSheetByName(self::SHEET_NAME);
            if (!$sheet) {
                throw new Exception("Sheet '" . self::SHEET_NAME . "' not found. Available sheets: " . 
                    implode(', ', $spreadsheet->getSheetNames()));
            }

            // Validate Excel structure
            if (!$this->validateExcelStructure($sheet)) {
                throw new Exception("Excel file structure validation failed");
            }

            $this->info("Excel structure validated successfully");

            // Get headers from row 39
            $headers = $this->getHeaders($sheet);
            $this->info("Found headers: " . implode(', ', array_keys($headers)));

            // Process data rows 40-115
            $totalRows = self::DATA_END_ROW - self::DATA_START_ROW + 1;
            $this->info("Processing {$totalRows} rows...");
            
            $progressBar = $this->output->createProgressBar($totalRows);
            $progressBar->start();

            for ($row = self::DATA_START_ROW; $row <= self::DATA_END_ROW; $row++) {
                try {
                    $result = $this->processRow($sheet, $row, $headers);
                    
                    if ($result['action'] === 'imported') {
                        $imported++;
                    } elseif ($result['action'] === 'updated') {
                        $updated++;
                    }
                    
                } catch (Exception $e) {
                    $errors++;
                    $errorMessages[] = "Row {$row}: {$e->getMessage()}";
                    $this->warn("Error processing row {$row}: {$e->getMessage()}");
                }
                
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

        } catch (Exception $e) {
            $errors++;
            $errorMessages[] = "File processing error: {$e->getMessage()}";
        }

        return new ImportResult($imported, $updated, $errors, $errorMessages);
    }

    /**
     * Validate the Excel file structure
     */
    private function validateExcelStructure(Worksheet $sheet): bool
    {
        try {
            // Check if header row exists
            $headerRow = $sheet->rangeToArray("A" . self::HEADER_ROW . ":Z" . self::HEADER_ROW)[0];
            
            if (empty(array_filter($headerRow))) {
                $this->error("Header row " . self::HEADER_ROW . " is empty");
                return false;
            }

            // Check for required frequency columns - need at least one
            $headerString = implode('|', array_filter($headerRow));
            $foundColumns = [];
            
            foreach (self::FREQUENCY_COLUMNS as $frequency) {
                if (stripos($headerString, $frequency) !== false) {
                    $foundColumns[] = $frequency;
                }
            }

            if (empty($foundColumns)) {
                $this->error("No frequency columns found. Expected at least one of: " . implode(', ', self::FREQUENCY_COLUMNS));
                return false;
            }

            // Check if Question Column exists (should be first column)
            if (empty(trim($headerRow[0] ?? ''))) {
                $this->error("Question Column (first column) is missing or empty");
                return false;
            }

            return true;
            
        } catch (Exception $e) {
            $this->error("Structure validation error: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Get headers mapping from the Excel sheet
     */
    private function getHeaders(Worksheet $sheet): array
    {
        $headerRow = $sheet->rangeToArray("A" . self::HEADER_ROW . ":Z" . self::HEADER_ROW)[0];
        $headers = [];
        
        foreach ($headerRow as $index => $header) {
            $cleanHeader = trim($header ?? '');
            if (!empty($cleanHeader)) {
                $headers[$index] = $cleanHeader;
            }
        }
        
        return $headers;
    }

    /**
     * Process a single row and create/update decision rules
     */
    private function processRow(Worksheet $sheet, int $rowNumber, array $headers): array
    {
        $rowData = $sheet->rangeToArray("A{$rowNumber}:Z{$rowNumber}")[0];
        
        // Get item code from first column (Question Column)
        $itemCode = trim($rowData[0] ?? '');
        
        if (empty($itemCode)) {
            throw new Exception("Item code is empty");
        }

        // Determine domain from item code prefix
        $domain = $this->getDomainFromItemCode($itemCode);
        
        $actions = [];
        
        // Process each frequency column
        foreach ($headers as $columnIndex => $columnHeader) {
            if (in_array($columnHeader, self::FREQUENCY_COLUMNS)) {
                $decisionText = trim($rowData[$columnIndex] ?? '');
                
                if (!empty($decisionText)) {
                    $action = $this->createOrUpdateDecisionRule(
                        $itemCode,
                        $columnHeader,
                        $domain,
                        $decisionText
                    );
                    $actions[] = $action;
                }
            }
        }
        
        // Return the most significant action (imported > updated)
        if (in_array('imported', $actions)) {
            return ['action' => 'imported'];
        } elseif (in_array('updated', $actions)) {
            return ['action' => 'updated'];
        }
        
        return ['action' => 'skipped'];
    }

    /**
     * Create or update a decision rule
     */
    private function createOrUpdateDecisionRule(string $itemCode, string $frequency, string $domain, string $decisionText): string
    {
        $existingRule = DecisionRule::where('item_code', $itemCode)
            ->where('frequency', $frequency)
            ->first();

        if ($existingRule) {
            // Update existing rule
            $existingRule->update([
                'domain' => $domain,
                'decision_text' => $decisionText
            ]);
            return 'updated';
        } else {
            // Create new rule
            DecisionRule::create([
                'item_code' => $itemCode,
                'frequency' => $frequency,
                'domain' => $domain,
                'decision_text' => $decisionText
            ]);
            return 'imported';
        }
    }

    /**
     * Determine domain from item code prefix
     */
    private function getDomainFromItemCode(string $itemCode): string
    {
        foreach (self::CROSS_LOADED_DOMAINS as $prefix => $domain) {
            if (str_starts_with($itemCode, $prefix)) {
                return $domain;
            }
        }
        
        // Default domain mapping for other prefixes
        $otherMappings = [
            'SS_' => 'Social Support',
            'SIB_' => 'Sibling Relations',
            'RELATION_' => 'Relationships',
            'CONF_' => 'Confidence',
            'DEM_' => 'Demographics'
        ];
        
        foreach ($otherMappings as $prefix => $domain) {
            if (str_starts_with($itemCode, $prefix)) {
                return $domain;
            }
        }
        
        // Fallback to generic domain
        return 'General';
    }

    /**
     * Display import results
     */
    private function displayResults(ImportResult $result): void
    {
        $this->newLine();
        $this->info("=== Import Complete ===");
        $this->info("Imported: {$result->imported}");
        $this->info("Updated: {$result->updated}");
        $this->info("Errors: {$result->errors}");
        $this->info("Total Processed: {$result->getTotalProcessed()}");
        
        if ($result->hasErrors()) {
            $this->newLine();
            $this->error("Errors encountered:");
            foreach ($result->getFormattedErrors() as $error) {
                $this->error("- {$error}");
            }
        }
        
        if ($result->isSuccessful()) {
            $this->newLine();
            $this->info("✅ Import completed successfully!");
        } else {
            $this->newLine();
            $this->warn("⚠️  Import completed with errors. Please review the error messages above.");
        }
    }
}