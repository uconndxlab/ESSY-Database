<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ReportData;
use App\Services\CrossLoadedDomainService;

/**
 * Comprehensive Field Name Mapping Validator
 * 
 * This script validates field name consistency between:
 * - Excel data source field names (actual column names)
 * - Code configuration field names (in services)
 * - ReportData model fillable fields
 * 
 * Based on the design document analysis, this identifies specific
 * field name mismatches that cause items to appear as "unanswered"
 * when they actually have data in Excel.
 */
class FieldNameMappingValidator
{
    private array $modelFields;
    private array $codeConfiguredFields;
    private array $expectedExcelFields;
    private array $fieldMismatches = [];
    private array $missingFields = [];
    private array $spellingErrors = [];
    private array $crossLoadedIssues = [];
    private CrossLoadedDomainService $crossLoadedService;

    public function __construct()
    {
        $this->modelFields = (new ReportData())->getFillable();
        $this->crossLoadedService = new CrossLoadedDomainService();
        $this->initializeExpectedExcelFields();
        $this->extractCodeConfiguredFields();
    }

    /**
     * Initialize expected Excel field names based on design document analysis
     */
    private function initializeExpectedExcelFields(): void
    {
        // These are the actual field names that exist in Excel based on the design document
        $this->expectedExcelFields = [
            // Directions - Excel has cross-loaded variants, code only has single
            'A_B_DIRECTIONS_CL1' => 'understands directions.',
            'A_B_DIRECTIONS_CL2' => 'understands directions.',
            
            // Verbal/Physical Aggression - Excel has different prefix
            'B_VERBAGGRESS' => 'engages in verbally aggressive behavior toward others.',
            'B_PHYSAGGRESS' => 'engages in physically aggressive behavior toward others.',
            
            // Oral/Physical Health - Excel has different domain prefix
            'P_ORAL' => 'oral health appears to be addressed.',
            'P_PHYS' => 'physical health appears to be addressed.',
            
            // Hygiene - Excel has spelling difference
            'O_P_HYGEINE_CL1' => 'appears to have the resources to address basic hygiene needs.',
            'O_P_HYGEINE_CL2' => 'appears to have the resources to address basic hygiene needs.',
            
            // Articulate - Excel has CL3 variant missing in code
            'A_P_S_ARTICULATE_CL1' => 'articulates clearly enough to be understood.',
            'A_P_S_ARTICULATE_CL2' => 'articulates clearly enough to be understood.',
            'A_P_S_ARTICULATE_CL3' => 'articulates clearly enough to be understood.',
            
            // Community Connection - Excel has cross-loaded variants, code only has single
            'S_O_COMMCONN_CL1' => 'appears to experience a sense of connection in their community.',
            'S_O_COMMCONN_CL2' => 'appears to experience a sense of connection in their community.',
            
            // Fields that should exist in code but may be configured incorrectly
            'A_READ' => 'meets grade-level expectations for reading skills.',
            'A_WRITE' => 'meets expectations for grade-level writing skills.',
            'A_MATH' => 'meets expectations for grade-level math skills.',
            'A_S_ADULTCOMM_CL1' => 'communicates with adults effectively.',
            'A_S_ADULTCOMM_CL2' => 'communicates with adults effectively.',
            'A_INITIATE' => 'initiates academic tasks.',
            'A_PLANORG' => 'demonstrates ability to plan, organize, focus, and prioritize tasks.',
            'A_TURNIN' => 'completes and turns in assigned work.',
            'A_B_CLASSEXPECT_CL1' => 'follows classroom expectations.',
            'A_B_CLASSEXPECT_CL2' => 'follows classroom expectations.',
            'A_B_IMPULSE_CL1' => 'exhibits impulsivity.',
            'A_B_IMPULSE_CL2' => 'exhibits impulsivity.',
            'A_ENGAGE' => 'engaged in academic activities.',
            'A_INTEREST' => 'shows interest in learning activities.',
            'A_PERSIST' => 'persists with challenging tasks.',
            'A_GROWTH' => 'demonstrates a growth mindset.',
            'A_S_CONFIDENT_CL1' => 'displays confidence in self.',
            'A_S_CONFIDENT_CL2' => 'displays confidence in self.',
            'A_S_POSOUT_CL1' => 'demonstrates positive outlook.',
            'A_S_POSOUT_CL2' => 'demonstrates positive outlook.',
            'A_S_O_ACTIVITY_CL1' => 'engaged in at least one extracurricular activity.',
            'A_S_O_ACTIVITY_CL2' => 'engaged in at least one extracurricular activity.',
            'A_S_O_ACTIVITY_CL3' => 'engaged in at least one extracurricular activity.',
        ];
    }

    /**
     * Extract field names from code configuration
     */
    private function extractCodeConfiguredFields(): void
    {
        $fieldMessages = $this->crossLoadedService->getFieldMessages();
        $fieldToDomainMap = $this->crossLoadedService->getFieldToDomainMap();
        
        $this->codeConfiguredFields = array_merge(
            array_keys($fieldMessages),
            array_keys($fieldToDomainMap)
        );
        
        $this->codeConfiguredFields = array_unique($this->codeConfiguredFields);
    }

    /**
     * Validate field name mapping and identify issues
     */
    public function validateFieldMapping(): array
    {
        echo "Starting comprehensive field name mapping validation...\n\n";

        $this->identifyFieldMismatches();
        $this->identifyMissingFields();
        $this->identifySpellingErrors();
        $this->validateCrossLoadedGroups();
        $this->validateModelFieldConsistency();

        return $this->generateValidationReport();
    }

    /**
     * Identify field name mismatches between Excel and code
     */
    private function identifyFieldMismatches(): void
    {
        echo "Checking for field name mismatches...\n";

        // Known mismatches from design document
        $knownMismatches = [
            'A_DIRECTIONS' => ['A_B_DIRECTIONS_CL1', 'A_B_DIRECTIONS_CL2'],
            'BEH_VERBAGGRESS' => ['B_VERBAGGRESS'],
            'BEH_PHYSAGGRESS' => ['B_PHYSAGGRESS'],
            'A_ORAL' => ['P_ORAL'],
            'A_PHYS' => ['P_PHYS'],
            'O_P_HYGEINE_CL1' => ['O_P_HYGEINE_CL1'],
            'S_COMMCONN' => ['S_O_COMMCONN_CL1', 'S_O_COMMCONN_CL2'],
        ];

        foreach ($knownMismatches as $codeField => $excelFields) {
            $codeExists = in_array($codeField, $this->codeConfiguredFields);
            $modelHasCode = in_array($codeField, $this->modelFields);
            
            foreach ($excelFields as $excelField) {
                $modelHasExcel = in_array($excelField, $this->modelFields);
                
                $this->fieldMismatches[] = [
                    'code_field' => $codeField,
                    'excel_field' => $excelField,
                    'code_configured' => $codeExists,
                    'code_in_model' => $modelHasCode,
                    'excel_in_model' => $modelHasExcel,
                    'issue_type' => $this->determineMismatchType($codeExists, $modelHasCode, $modelHasExcel),
                    'description' => $this->describeMismatch($codeField, $excelField, $codeExists, $modelHasCode, $modelHasExcel)
                ];
            }
        }
    }

    /**
     * Identify missing fields that exist in Excel but not in code
     */
    private function identifyMissingFields(): void
    {
        echo "Checking for missing fields...\n";

        foreach ($this->expectedExcelFields as $excelField => $description) {
            $inModel = in_array($excelField, $this->modelFields);
            $inCode = in_array($excelField, $this->codeConfiguredFields);
            
            if ($inModel && !$inCode) {
                $this->missingFields[] = [
                    'field' => $excelField,
                    'description' => $description,
                    'in_model' => true,
                    'in_code' => false,
                    'issue' => 'Field exists in model but not configured in code services'
                ];
            } elseif (!$inModel && !$inCode) {
                $this->missingFields[] = [
                    'field' => $excelField,
                    'description' => $description,
                    'in_model' => false,
                    'in_code' => false,
                    'issue' => 'Field missing from both model and code configuration'
                ];
            }
        }
    }

    /**
     * Identify spelling errors in field names
     */
    private function identifySpellingErrors(): void
    {
        echo "Checking for spelling errors...\n";

        $knownSpellingIssues = [
            'O_P_HYGEINE_CL1' => 'O_P_HYGEINE_CL1', // Code has HYGIENE, Excel has HYGEINE
            'O_P_HYGIENE_CL2' => 'O_P_HYGEINE_CL2',
        ];

        foreach ($knownSpellingIssues as $codeField => $excelField) {
            $codeInModel = in_array($codeField, $this->modelFields);
            $excelInModel = in_array($excelField, $this->modelFields);
            $codeConfigured = in_array($codeField, $this->codeConfiguredFields);
            
            $this->spellingErrors[] = [
                'code_field' => $codeField,
                'excel_field' => $excelField,
                'code_in_model' => $codeInModel,
                'excel_in_model' => $excelInModel,
                'code_configured' => $codeConfigured,
                'issue' => 'Spelling difference between code and Excel field names',
                'recommendation' => $excelInModel ? "Use Excel spelling: {$excelField}" : "Verify correct spelling"
            ];
        }
    }

    /**
     * Validate cross-loaded group configuration
     */
    private function validateCrossLoadedGroups(): void
    {
        echo "Validating cross-loaded groups...\n";

        $crossLoadedGroups = $this->crossLoadedService->getCrossLoadedItemGroups();
        
        foreach ($crossLoadedGroups as $groupIndex => $group) {
            foreach ($group as $field) {
                $inModel = in_array($field, $this->modelFields);
                $inCode = in_array($field, $this->codeConfiguredFields);
                
                if (!$inModel) {
                    $this->crossLoadedIssues[] = [
                        'group_index' => $groupIndex,
                        'field' => $field,
                        'issue' => 'Cross-loaded field not found in model',
                        'severity' => 'error'
                    ];
                }
                
                if (!$inCode) {
                    $this->crossLoadedIssues[] = [
                        'group_index' => $groupIndex,
                        'field' => $field,
                        'issue' => 'Cross-loaded field not configured in field messages',
                        'severity' => 'warning'
                    ];
                }
            }
            
            // Check for missing cross-loaded variants
            if (count($group) < 2) {
                $this->crossLoadedIssues[] = [
                    'group_index' => $groupIndex,
                    'field' => implode(', ', $group),
                    'issue' => 'Cross-loaded group has only one field - not truly cross-loaded',
                    'severity' => 'warning'
                ];
            }
        }
    }

    /**
     * Validate model field consistency
     */
    private function validateModelFieldConsistency(): void
    {
        echo "Validating model field consistency...\n";

        // Check if all configured fields exist in model
        foreach ($this->codeConfiguredFields as $field) {
            if (!in_array($field, $this->modelFields)) {
                $this->fieldMismatches[] = [
                    'code_field' => $field,
                    'excel_field' => 'unknown',
                    'code_configured' => true,
                    'code_in_model' => false,
                    'excel_in_model' => false,
                    'issue_type' => 'code_not_in_model',
                    'description' => "Code references field '{$field}' but it doesn't exist in ReportData model"
                ];
            }
        }
    }

    /**
     * Determine the type of mismatch
     */
    private function determineMismatchType(bool $codeExists, bool $modelHasCode, bool $modelHasExcel): string
    {
        if ($codeExists && !$modelHasCode && $modelHasExcel) {
            return 'field_name_mismatch';
        } elseif ($codeExists && $modelHasCode && !$modelHasExcel) {
            return 'excel_field_missing';
        } elseif (!$codeExists && $modelHasExcel) {
            return 'code_configuration_missing';
        } else {
            return 'complex_mismatch';
        }
    }

    /**
     * Describe the mismatch issue
     */
    private function describeMismatch(string $codeField, string $excelField, bool $codeExists, bool $modelHasCode, bool $modelHasExcel): string
    {
        if ($codeExists && !$modelHasCode && $modelHasExcel) {
            return "Code looks for '{$codeField}' but Excel data has '{$excelField}' - field name mismatch causes null values";
        } elseif (!$codeExists && $modelHasExcel) {
            return "Excel field '{$excelField}' exists in model but not configured in code services";
        } else {
            return "Complex mismatch between code field '{$codeField}' and Excel field '{$excelField}'";
        }
    }

    /**
     * Generate comprehensive validation report
     */
    private function generateValidationReport(): array
    {
        $report = [
            'summary' => [
                'total_model_fields' => count($this->modelFields),
                'total_code_configured_fields' => count($this->codeConfiguredFields),
                'total_expected_excel_fields' => count($this->expectedExcelFields),
                'field_mismatches' => count($this->fieldMismatches),
                'missing_fields' => count($this->missingFields),
                'spelling_errors' => count($this->spellingErrors),
                'cross_loaded_issues' => count($this->crossLoadedIssues),
            ],
            'field_mismatches' => $this->fieldMismatches,
            'missing_fields' => $this->missingFields,
            'spelling_errors' => $this->spellingErrors,
            'cross_loaded_issues' => $this->crossLoadedIssues,
            'recommendations' => $this->generateRecommendations()
        ];

        return $report;
    }

    /**
     * Generate specific recommendations for fixing issues
     */
    private function generateRecommendations(): array
    {
        $recommendations = [];

        // Field name mismatch recommendations
        foreach ($this->fieldMismatches as $mismatch) {
            if ($mismatch['issue_type'] === 'field_name_mismatch') {
                $recommendations[] = [
                    'type' => 'field_name_update',
                    'priority' => 'high',
                    'action' => "Update code configuration to use '{$mismatch['excel_field']}' instead of '{$mismatch['code_field']}'",
                    'files_to_update' => [
                        'app/Services/CrossLoadedDomainService.php (getFieldMessages and buildFieldToDomainMap methods)',
                    ]
                ];
            }
        }

        // Missing field recommendations
        foreach ($this->missingFields as $missing) {
            if ($missing['in_model'] && !$missing['in_code']) {
                $recommendations[] = [
                    'type' => 'add_field_configuration',
                    'priority' => 'medium',
                    'action' => "Add field '{$missing['field']}' to code configuration",
                    'files_to_update' => [
                        'app/Services/CrossLoadedDomainService.php (getFieldMessages and buildFieldToDomainMap methods)',
                    ]
                ];
            }
        }

        // Spelling error recommendations
        foreach ($this->spellingErrors as $spelling) {
            $recommendations[] = [
                'type' => 'spelling_correction',
                'priority' => 'high',
                'action' => "Update code to use Excel spelling: '{$spelling['excel_field']}' instead of '{$spelling['code_field']}'",
                'files_to_update' => [
                    'app/Services/CrossLoadedDomainService.php',
                ]
            ];
        }

        // Cross-loaded group recommendations
        $errorGroups = array_filter($this->crossLoadedIssues, fn($issue) => $issue['severity'] === 'error');
        if (!empty($errorGroups)) {
            $recommendations[] = [
                'type' => 'cross_loaded_group_fix',
                'priority' => 'high',
                'action' => 'Fix cross-loaded group configuration to use correct field names',
                'files_to_update' => [
                    'app/Services/CrossLoadedDomainService.php (initializeCrossLoadedConfiguration method)',
                ]
            ];
        }

        return $recommendations;
    }

    /**
     * Print detailed validation report
     */
    public function printReport(): void
    {
        $report = $this->validateFieldMapping();

        echo "\n" . str_repeat("=", 80) . "\n";
        echo "COMPREHENSIVE FIELD NAME MAPPING VALIDATION REPORT\n";
        echo str_repeat("=", 80) . "\n\n";

        // Summary
        echo "SUMMARY:\n";
        echo "--------\n";
        foreach ($report['summary'] as $key => $value) {
            echo sprintf("%-35s: %d\n", ucwords(str_replace('_', ' ', $key)), $value);
        }
        echo "\n";

        // Field Mismatches
        if (!empty($report['field_mismatches'])) {
            echo "FIELD NAME MISMATCHES:\n";
            echo "---------------------\n";
            foreach ($report['field_mismatches'] as $i => $mismatch) {
                echo sprintf("%d. %s\n", $i + 1, $mismatch['description']);
                echo sprintf("   Code Field: %s (configured: %s, in model: %s)\n", 
                    $mismatch['code_field'], 
                    $mismatch['code_configured'] ? 'YES' : 'NO',
                    $mismatch['code_in_model'] ? 'YES' : 'NO'
                );
                echo sprintf("   Excel Field: %s (in model: %s)\n", 
                    $mismatch['excel_field'], 
                    $mismatch['excel_in_model'] ? 'YES' : 'NO'
                );
                echo sprintf("   Issue Type: %s\n\n", $mismatch['issue_type']);
            }
        }

        // Missing Fields
        if (!empty($report['missing_fields'])) {
            echo "MISSING FIELDS:\n";
            echo "---------------\n";
            foreach ($report['missing_fields'] as $i => $missing) {
                echo sprintf("%d. %s\n", $i + 1, $missing['field']);
                echo sprintf("   Description: %s\n", $missing['description']);
                echo sprintf("   In Model: %s, In Code: %s\n", 
                    $missing['in_model'] ? 'YES' : 'NO',
                    $missing['in_code'] ? 'YES' : 'NO'
                );
                echo sprintf("   Issue: %s\n\n", $missing['issue']);
            }
        }

        // Spelling Errors
        if (!empty($report['spelling_errors'])) {
            echo "SPELLING ERRORS:\n";
            echo "----------------\n";
            foreach ($report['spelling_errors'] as $i => $spelling) {
                echo sprintf("%d. Code: %s → Excel: %s\n", $i + 1, $spelling['code_field'], $spelling['excel_field']);
                echo sprintf("   Recommendation: %s\n\n", $spelling['recommendation']);
            }
        }

        // Cross-loaded Issues
        if (!empty($report['cross_loaded_issues'])) {
            echo "CROSS-LOADED GROUP ISSUES:\n";
            echo "--------------------------\n";
            foreach ($report['cross_loaded_issues'] as $i => $issue) {
                echo sprintf("%d. [%s] %s\n", $i + 1, strtoupper($issue['severity']), $issue['issue']);
                echo sprintf("   Group: %s, Field: %s\n\n", $issue['group_index'], $issue['field']);
            }
        }

        // Recommendations
        if (!empty($report['recommendations'])) {
            echo "RECOMMENDATIONS:\n";
            echo "----------------\n";
            foreach ($report['recommendations'] as $i => $rec) {
                echo sprintf("%d. [%s PRIORITY] %s\n", $i + 1, strtoupper($rec['priority']), $rec['action']);
                echo sprintf("   Type: %s\n", $rec['type']);
                echo "   Files to update:\n";
                foreach ($rec['files_to_update'] as $file) {
                    echo sprintf("   - %s\n", $file);
                }
                echo "\n";
            }
        }

        echo str_repeat("=", 80) . "\n";
        echo "VALIDATION COMPLETE\n";
        echo str_repeat("=", 80) . "\n";
    }

    /**
     * Export validation results to JSON file
     */
    public function exportToJson(string $filename = 'field-name-validation-results.json'): void
    {
        $report = $this->validateFieldMapping();
        $jsonPath = __DIR__ . '/../storage/app/' . $filename;
        
        file_put_contents($jsonPath, json_encode($report, JSON_PRETTY_PRINT));
        echo "Validation results exported to: {$jsonPath}\n";
    }

    /**
     * Create field mapping documentation
     */
    public function createMappingDocumentation(): void
    {
        $documentation = "# Field Name Mapping Documentation\n\n";
        $documentation .= "Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        
        $documentation .= "## Known Field Name Mismatches\n\n";
        $documentation .= "| Code Field | Excel Field | Status | Issue |\n";
        $documentation .= "|------------|-------------|--------|-------|\n";
        
        foreach ($this->fieldMismatches as $mismatch) {
            $status = $mismatch['excel_in_model'] ? '✅ In Model' : '❌ Missing';
            $documentation .= sprintf("| %s | %s | %s | %s |\n",
                $mismatch['code_field'],
                $mismatch['excel_field'],
                $status,
                $mismatch['issue_type']
            );
        }
        
        $documentation .= "\n## Missing Fields\n\n";
        $documentation .= "| Field | Description | In Model | In Code |\n";
        $documentation .= "|-------|-------------|----------|----------|\n";
        
        foreach ($this->missingFields as $missing) {
            $documentation .= sprintf("| %s | %s | %s | %s |\n",
                $missing['field'],
                substr($missing['description'], 0, 50) . '...',
                $missing['in_model'] ? '✅' : '❌',
                $missing['in_code'] ? '✅' : '❌'
            );
        }
        
        $docPath = __DIR__ . '/../storage/app/field-name-mapping-documentation.md';
        file_put_contents($docPath, $documentation);
        echo "Field mapping documentation created: {$docPath}\n";
    }
}

// Run the validator if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $validator = new FieldNameMappingValidator();
    $validator->printReport();
    $validator->exportToJson();
    $validator->createMappingDocumentation();
}