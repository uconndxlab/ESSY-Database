<?php

namespace App\Console\Commands;

use App\Models\DecisionRule;
use App\Models\ReportData;
use App\ValueObjects\ImportResult;
use Exception;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportDecisionRules extends Command
{
    protected $signature = 'essy:import-decision-rules {file : Path to Excel file} {--show-mapping : Show detailed field name mapping}';
    protected $description = 'Import decision rules from Excel file with corrected field name mapping';

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

    // Mapping from Excel decision rule item codes (short codes) to corrected field names
    // This ensures decision rules are imported with proper field name references
    private const ITEM_CODE_TO_FIELD_MAPPING = [
        // Academic Skills mappings (AS = A_READ, AT = A_WRITE, etc.)
        'AS' => 'A_READ',
        'AT' => 'A_WRITE', 
        'AU' => 'A_MATH',
        'AV' => 'A_P_S_ARTICULATE_CL1', // Primary cross-loaded field
        'AW' => 'A_S_ADULTCOMM_CL1',
        'AX' => 'A_B_DIRECTIONS_CL1', // Updated to match Excel field names
        'AY' => 'A_INITIATE',
        'AZ' => 'A_PLANORG',
        'BA' => 'A_TURNIN',
        'BB' => 'A_B_CLASSEXPECT_CL1',
        'BC' => 'A_B_IMPULSE_CL1',
        'BD' => 'A_ENGAGE',
        'BE' => 'A_INTEREST',
        'BF' => 'A_PERSIST',
        'BG' => 'A_GROWTH',
        'BH' => 'A_S_CONFIDENT_CL1',
        'BI' => 'A_S_POSOUT_CL1',
        'BJ' => 'A_S_O_ACTIVITY_CL1',

        // Academic Skills mappings (continued)
        'BK' => 'COMMENTS_AS',
        'BL' => 'TIMING_AS_First Click',
        'BM' => 'TIMING_AS_Last Click',
        'BN' => 'TIMING_AS_Page Submit',
        'BO' => 'TIMING_AS_Click Count',
        'BP' => 'A_B_CLASSEXPECT_CL2',
        'BQ' => 'A_B_DIRECTIONS_CL2',
        'BR' => 'A_B_IMPULSE_CL2',

        // Behavior mappings
        'BS' => 'B_CLINGY',
        'BT' => 'B_SNEAK',
        'BU' => 'B_VERBAGGRESS',
        'BV' => 'B_PHYSAGGRESS',
        'BW' => 'B_DESTRUCT',
        'BX' => 'B_BULLY',
        'BY' => 'B_PUNITIVE',
        'BZ' => 'B_O_HOUSING_CL1',
        'CA' => 'B_O_FAMSTRESS_CL1',
        'CB' => 'B_O_NBHDSTRESS_CL1',
        'CC' => 'COMMENTS_BEH',
        'CD' => 'TIMING_BEH_First Click',
        'CE' => 'TIMING_BEH_Last Click',
        'CF' => 'TIMING_BEH_Page Submit',
        'CG' => 'TIMING_BEH_Click Count',

        // Physical Health mappings
        'CH' => 'P_SIGHT',
        'CI' => 'P_HEAR',
        'CJ' => 'A_P_S_ARTICULATE_CL2',
        'CK' => 'P_ORAL',
        'CL' => 'P_PHYS',
        'CM' => 'P_PARTICIPATE',
        'CN' => 'S_P_ACHES_CL1',
        'CO' => 'O_P_HUNGER_CL1',
        'CP' => 'O_P_HYGEINE_CL1',
        'CQ' => 'O_P_CLOTHES_CL1',
        'CR' => 'COMMENTS_PH',
        'CS' => 'TIMING_PH_First Click',
        'CT' => 'TIMING_PH_Last Click',
        'CU' => 'TIMING_PH_Page Submit',
        'CV' => 'TIMING_PH_Click Count',

        // Social & Emotional Well-Being mappings
        'CW' => 'S_CONTENT',
        'CX' => 'A_S_CONFIDENT_CL2',
        'CY' => 'A_S_POSOUT_CL2',
        'CZ' => 'S_P_ACHES_CL2',
        'DA' => 'S_NERVOUS',
        'DB' => 'S_SAD',
        'DC' => 'S_SOCIALCONN',
        'DD' => 'S_FRIEND',
        'DE' => 'S_PROSOCIAL',
        'DF' => 'S_PEERCOMM',
        'DG' => 'A_S_ADULTCOMM_CL2',
        'DH' => 'A_P_S_ARTICULATE_CL3',
        'DI' => 'S_POSADULT',
        'DJ' => 'S_SCHOOLCONN',
        'DK' => 'S_O_COMMCONN_CL1',
        'DL' => 'A_S_O_ACTIVITY_CL2',
        'DM' => 'COMMENTS_SEW',
        'DN' => 'TIMING_SEW_First Click',
        'DO' => 'TIMING_SEW_Last Click',
        'DP' => 'TIMING_SEW_Page Submit',
        'DQ' => 'TIMING_SEW_Click Count',

        // Supports Outside of School mappings
        'DR' => 'O_RECIPROCAL',
        'DS' => 'O_POSADULT',
        'DT' => 'O_ADULTBEST',
        'DU' => 'O_TALK',
        'DV' => 'O_ROUTINE',
        'DW' => 'O_FAMILY',
        'DX' => 'O_P_HUNGER_CL2',
        'DY' => 'O_P_HYGIENE_CL2',
        'DZ' => 'O_P_CLOTHES_CL2',
        'EA' => 'O_RESOURCE',
        'EB' => 'B_O_HOUSING_CL2',
        'EC' => 'B_O_FAMSTRESS_CL2',
        'ED' => 'B_O_NBHDSTRESS_CL2',
        'EE' => 'A_S_O_ACTIVITY_CL3',
        'EF' => 'S_O_COMMCONN_CL2',
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
            
            // Check if Qualtrics Column exists (should be second column)
            if (empty(trim($headerRow[1] ?? ''))) {
                $this->error("Qualtrics Column (second column) is missing or empty");
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
        
        // Get item code from second column (Qualtrics Column) instead of first column
        // First column contains field messages, second column contains actual item codes
        $rawItemCode = trim($rowData[1] ?? '');
        
        if (empty($rawItemCode)) {
            throw new Exception("Item code is empty in Qualtrics Column");
        }

        // Extract main item code (before parentheses) for cross-loaded items
        // Example: "DH (AV, CJ)" becomes "DH"
        $itemCode = preg_replace('/\s*\([^)]*\)/', '', $rawItemCode);
        $itemCode = trim($itemCode);

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
     * Create or update a decision rule with corrected field name mapping
     */
    private function createOrUpdateDecisionRule(string $itemCode, string $frequency, string $domain, string $decisionText): string
    {
        // Map item code to corrected field name if mapping exists
        $correctedFieldName = self::ITEM_CODE_TO_FIELD_MAPPING[$itemCode] ?? $itemCode;
        
        // Validate field name consistency during import
        if (isset(self::ITEM_CODE_TO_FIELD_MAPPING[$itemCode])) {
            $this->validateFieldNameConsistency($itemCode, $correctedFieldName);
        }

        $existingRule = DecisionRule::where('item_code', $correctedFieldName)
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
            // Create new rule with corrected field name
            DecisionRule::create([
                'item_code' => $correctedFieldName,
                'frequency' => $frequency,
                'domain' => $domain,
                'decision_text' => $decisionText
            ]);
            return 'imported';
        }
    }

    /**
     * Validate field name consistency during import
     */
    private function validateFieldNameConsistency(string $originalItemCode, string $correctedFieldName): void
    {
        // Check if the corrected field name exists in the ReportData model
        $reportDataModel = new ReportData();
        $fillableFields = $reportDataModel->getFillable();
        
        if (!in_array($correctedFieldName, $fillableFields)) {
            $this->warn("Warning: Corrected field name '{$correctedFieldName}' (from '{$originalItemCode}') not found in ReportData model fillable fields");
        }
        
        // Log the mapping for debugging
        if ($this->option('show-mapping')) {
            $this->info("Mapping: {$originalItemCode} → {$correctedFieldName}");
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
        
        // Display field name mapping summary
        $this->displayFieldNameMappingSummary();
        
        if ($result->hasErrors()) {
            $this->newLine();
            $this->error("Errors encountered:");
            foreach ($result->getFormattedErrors() as $error) {
                $this->error("- {$error}");
            }
        }
        
        if ($result->isSuccessful()) {
            $this->newLine();
            $this->info("✅ Import completed successfully with corrected field name mapping!");
        } else {
            $this->newLine();
            $this->warn("⚠️  Import completed with errors. Please review the error messages above.");
        }
    }

    /**
     * Display field name mapping summary
     */
    private function displayFieldNameMappingSummary(): void
    {
        $this->newLine();
        $this->info("=== Field Name Mapping Summary ===");
        $this->info("Total field mappings available: " . count(self::ITEM_CODE_TO_FIELD_MAPPING));
        
        if ($this->option('show-mapping')) {
            $this->newLine();
            $this->info("Field name mappings used:");
            foreach (self::ITEM_CODE_TO_FIELD_MAPPING as $itemCode => $fieldName) {
                $this->line("  {$itemCode} → {$fieldName}");
            }
        } else {
            $this->info("Use --show-mapping to see detailed field name mappings");
        }
        
        $this->newLine();
        $this->info("Key corrections applied:");
        $this->info("- BO → B_VERBAGGRESS (prefix correction from BEH_VERBAGGRESS)");
        $this->info("- BP → B_PHYSAGGRESS (prefix correction from BEH_PHYSAGGRESS)");
        $this->info("- AX → A_B_DIRECTIONS_CL1 (cross-loaded variant from A_DIRECTIONS)");
        $this->info("- BZ → P_ORAL (domain prefix correction from A_ORAL)");
        $this->info("- CA → P_PHYS (domain prefix correction from A_PHYS)");
        $this->info("- CT → S_O_COMMCONN_CL1 (cross-loaded variant from S_COMMCONN)");
        $this->info("- CE/DC → *_HYGEINE (spelling preserved from Excel)");
    }
}