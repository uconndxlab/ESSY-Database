<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ReportData;
use App\Services\CrossLoadedDomainService;
use Illuminate\Support\Facades\DB;

/**
 * Actual Field Mapping Validator
 * 
 * This script validates the actual field mapping situation by:
 * 1. Checking what fields exist in the ReportData model
 * 2. Checking what fields are configured in the code services
 * 3. Checking what fields actually have data in the database
 * 4. Identifying the root cause of the "unanswered items" bug
 */
class ActualFieldMappingValidator
{
    private array $modelFields;
    private array $codeConfiguredFields;
    private array $fieldsWithData = [];
    private CrossLoadedDomainService $crossLoadedService;

    public function __construct()
    {
        $this->modelFields = (new ReportData())->getFillable();
        $this->crossLoadedService = new CrossLoadedDomainService();
        $this->extractCodeConfiguredFields();
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
     * Check what fields actually have data in the database
     */
    private function checkFieldsWithData(): void
    {
        echo "Checking which fields have actual data in the database...\n";

        // Get a sample of reports to check field usage
        $sampleReports = ReportData::limit(10)->get();
        
        if ($sampleReports->isEmpty()) {
            echo "No report data found in database.\n";
            return;
        }

        $fieldDataCount = [];
        
        foreach ($sampleReports as $report) {
            foreach ($this->modelFields as $field) {
                $value = $report->getAttribute($field);
                if ($value !== null && $value !== '' && trim($value) !== '-99') {
                    $fieldDataCount[$field] = ($fieldDataCount[$field] ?? 0) + 1;
                }
            }
        }

        // Sort by frequency of data
        arsort($fieldDataCount);
        $this->fieldsWithData = $fieldDataCount;
    }

    /**
     * Analyze the root cause of unanswered items bug
     */
    public function analyzeUnansweredItemsBug(): array
    {
        echo "Analyzing root cause of unanswered items bug...\n\n";

        $this->checkFieldsWithData();

        $analysis = [
            'model_vs_code_mismatch' => [],
            'fields_with_data_not_configured' => [],
            'configured_fields_without_data' => [],
            'potential_excel_import_issues' => [],
            'cross_loaded_configuration_issues' => []
        ];

        // Check for model vs code mismatches
        foreach ($this->codeConfiguredFields as $codeField) {
            if (!in_array($codeField, $this->modelFields)) {
                $analysis['model_vs_code_mismatch'][] = [
                    'field' => $codeField,
                    'issue' => 'Code references field that does not exist in model',
                    'impact' => 'HIGH - Will cause null values and unanswered items'
                ];
            }
        }

        // Check for fields with data that are not configured
        foreach ($this->fieldsWithData as $field => $count) {
            if (!in_array($field, $this->codeConfiguredFields)) {
                // Skip system fields
                if (!$this->isSystemField($field)) {
                    $analysis['fields_with_data_not_configured'][] = [
                        'field' => $field,
                        'data_count' => $count,
                        'issue' => 'Field has data but is not configured in services',
                        'impact' => 'MEDIUM - Data exists but not processed'
                    ];
                }
            }
        }

        // Check for configured fields without data
        foreach ($this->codeConfiguredFields as $codeField) {
            if (in_array($codeField, $this->modelFields) && !isset($this->fieldsWithData[$codeField])) {
                $analysis['configured_fields_without_data'][] = [
                    'field' => $codeField,
                    'issue' => 'Field is configured but has no data in sample',
                    'impact' => 'LOW - Configuration exists but no data to process'
                ];
            }
        }

        // Analyze potential Excel import issues
        $this->analyzeExcelImportIssues($analysis);

        // Analyze cross-loaded configuration issues
        $this->analyzeCrossLoadedIssues($analysis);

        return $analysis;
    }

    /**
     * Analyze potential Excel import issues
     */
    private function analyzeExcelImportIssues(array &$analysis): void
    {
        // Based on the design document, these are the suspected Excel field names
        $suspectedExcelFields = [
            'A_B_DIRECTIONS_CL1' => 'A_DIRECTIONS',
            'A_B_DIRECTIONS_CL2' => 'A_DIRECTIONS',
            'B_VERBAGGRESS' => 'BEH_VERBAGGRESS',
            'B_PHYSAGGRESS' => 'BEH_PHYSAGGRESS',
            'P_ORAL' => 'A_ORAL',
            'P_PHYS' => 'A_PHYS',
            'O_P_hygiene_CL1' => 'O_P_hygiene_CL1',
            'O_P_hygiene_CL2' => 'O_P_HYGIENE_CL2',
            'S_O_COMMCONN_CL1' => 'S_COMMCONN',
            'S_O_COMMCONN_CL2' => 'S_COMMCONN',
            'A_P_S_ARTICULATE_CL3' => null, // Missing entirely
        ];

        foreach ($suspectedExcelFields as $excelField => $modelField) {
            if ($modelField && in_array($modelField, $this->modelFields)) {
                $hasData = isset($this->fieldsWithData[$modelField]);
                $analysis['potential_excel_import_issues'][] = [
                    'suspected_excel_field' => $excelField,
                    'model_field' => $modelField,
                    'model_has_data' => $hasData,
                    'data_count' => $this->fieldsWithData[$modelField] ?? 0,
                    'issue' => $hasData ? 
                        'Excel field name may differ from model field name' : 
                        'Model field exists but has no data - possible import mapping issue',
                    'impact' => $hasData ? 'LOW - Data is being imported correctly' : 'HIGH - Data not being imported'
                ];
            } elseif ($modelField === null) {
                $analysis['potential_excel_import_issues'][] = [
                    'suspected_excel_field' => $excelField,
                    'model_field' => 'MISSING',
                    'model_has_data' => false,
                    'data_count' => 0,
                    'issue' => 'Excel field exists but no corresponding model field',
                    'impact' => 'HIGH - Excel data being lost during import'
                ];
            }
        }
    }

    /**
     * Analyze cross-loaded configuration issues
     */
    private function analyzeCrossLoadedIssues(array &$analysis): void
    {
        $crossLoadedGroups = $this->crossLoadedService->getCrossLoadedItemGroups();
        
        foreach ($crossLoadedGroups as $groupIndex => $group) {
            $groupIssues = [];
            
            foreach ($group as $field) {
                $inModel = in_array($field, $this->modelFields);
                $hasData = isset($this->fieldsWithData[$field]);
                $configured = in_array($field, $this->codeConfiguredFields);
                
                if (!$inModel) {
                    $groupIssues[] = "Field '{$field}' not in model";
                } elseif (!$hasData) {
                    $groupIssues[] = "Field '{$field}' has no data";
                } elseif (!$configured) {
                    $groupIssues[] = "Field '{$field}' not configured in services";
                }
            }
            
            if (!empty($groupIssues)) {
                $analysis['cross_loaded_configuration_issues'][] = [
                    'group_index' => $groupIndex,
                    'group_fields' => $group,
                    'issues' => $groupIssues,
                    'impact' => 'MEDIUM - Cross-loaded functionality may not work correctly'
                ];
            }
        }
    }

    /**
     * Check if field is a system field (not assessment data)
     */
    private function isSystemField(string $field): bool
    {
        $systemPrefixes = [
            'StartDate', 'EndDate', 'Status', 'IPAddress', 'Progress', 'Duration', 
            'Finished', 'RecordedDate', 'ResponseId', 'Recipient', 'External',
            'Location', 'Distribution', 'User', 'FN_', 'LN_', 'SCHOOL',
            'COMMENTS_', 'TIMING_', 'SPEEDING_', 'DEM_', 'RELATION_',
            'batch_id', 'created_at', 'updated_at'
        ];

        foreach ($systemPrefixes as $prefix) {
            if (strpos($field, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate comprehensive analysis report
     */
    public function generateReport(): void
    {
        $analysis = $this->analyzeUnansweredItemsBug();

        echo str_repeat("=", 80) . "\n";
        echo "ACTUAL FIELD MAPPING ANALYSIS REPORT\n";
        echo str_repeat("=", 80) . "\n\n";

        echo "SUMMARY:\n";
        echo "--------\n";
        echo sprintf("Total model fields: %d\n", count($this->modelFields));
        echo sprintf("Total code configured fields: %d\n", count($this->codeConfiguredFields));
        echo sprintf("Fields with actual data: %d\n", count($this->fieldsWithData));
        echo sprintf("Model vs code mismatches: %d\n", count($analysis['model_vs_code_mismatch']));
        echo sprintf("Fields with data not configured: %d\n", count($analysis['fields_with_data_not_configured']));
        echo sprintf("Configured fields without data: %d\n", count($analysis['configured_fields_without_data']));
        echo sprintf("Potential Excel import issues: %d\n", count($analysis['potential_excel_import_issues']));
        echo sprintf("Cross-loaded configuration issues: %d\n", count($analysis['cross_loaded_configuration_issues']));
        echo "\n";

        // Model vs Code Mismatches (HIGH PRIORITY)
        if (!empty($analysis['model_vs_code_mismatch'])) {
            echo "🚨 CRITICAL: MODEL VS CODE MISMATCHES\n";
            echo str_repeat("-", 40) . "\n";
            foreach ($analysis['model_vs_code_mismatch'] as $i => $issue) {
                echo sprintf("%d. Field: %s\n", $i + 1, $issue['field']);
                echo sprintf("   Issue: %s\n", $issue['issue']);
                echo sprintf("   Impact: %s\n\n", $issue['impact']);
            }
        }

        // Fields with data not configured
        if (!empty($analysis['fields_with_data_not_configured'])) {
            echo "⚠️  FIELDS WITH DATA NOT CONFIGURED:\n";
            echo str_repeat("-", 40) . "\n";
            foreach ($analysis['fields_with_data_not_configured'] as $i => $issue) {
                echo sprintf("%d. Field: %s (data in %d/%d reports)\n", 
                    $i + 1, $issue['field'], $issue['data_count'], count($this->fieldsWithData));
                echo sprintf("   Issue: %s\n", $issue['issue']);
                echo sprintf("   Impact: %s\n\n", $issue['impact']);
            }
        }

        // Configured fields without data
        if (!empty($analysis['configured_fields_without_data'])) {
            echo "ℹ️  CONFIGURED FIELDS WITHOUT DATA:\n";
            echo str_repeat("-", 40) . "\n";
            foreach ($analysis['configured_fields_without_data'] as $i => $issue) {
                echo sprintf("%d. Field: %s\n", $i + 1, $issue['field']);
                echo sprintf("   Issue: %s\n", $issue['issue']);
                echo sprintf("   Impact: %s\n\n", $issue['impact']);
            }
        }

        // Excel import issues
        if (!empty($analysis['potential_excel_import_issues'])) {
            echo "📊 POTENTIAL EXCEL IMPORT ISSUES:\n";
            echo str_repeat("-", 40) . "\n";
            foreach ($analysis['potential_excel_import_issues'] as $i => $issue) {
                echo sprintf("%d. Excel Field: %s → Model Field: %s\n", 
                    $i + 1, $issue['suspected_excel_field'], $issue['model_field']);
                echo sprintf("   Has Data: %s (%d reports)\n", 
                    $issue['model_has_data'] ? 'YES' : 'NO', $issue['data_count']);
                echo sprintf("   Issue: %s\n", $issue['issue']);
                echo sprintf("   Impact: %s\n\n", $issue['impact']);
            }
        }

        // Cross-loaded issues
        if (!empty($analysis['cross_loaded_configuration_issues'])) {
            echo "🔗 CROSS-LOADED CONFIGURATION ISSUES:\n";
            echo str_repeat("-", 40) . "\n";
            foreach ($analysis['cross_loaded_configuration_issues'] as $i => $issue) {
                echo sprintf("%d. Group %s: [%s]\n", 
                    $i + 1, $issue['group_index'], implode(', ', $issue['group_fields']));
                echo "   Issues:\n";
                foreach ($issue['issues'] as $groupIssue) {
                    echo sprintf("   - %s\n", $groupIssue);
                }
                echo sprintf("   Impact: %s\n\n", $issue['impact']);
            }
        }

        // Top fields with data
        echo "📈 TOP FIELDS WITH DATA (Assessment Fields Only):\n";
        echo str_repeat("-", 40) . "\n";
        $assessmentFields = array_filter($this->fieldsWithData, function($field) {
            return !$this->isSystemField($field);
        }, ARRAY_FILTER_USE_KEY);
        
        $topFields = array_slice($assessmentFields, 0, 20, true);
        foreach ($topFields as $field => $count) {
            $configured = in_array($field, $this->codeConfiguredFields) ? '✅' : '❌';
            echo sprintf("   %s %s: %d reports\n", $configured, $field, $count);
        }

        echo "\n" . str_repeat("=", 80) . "\n";
        echo "ROOT CAUSE ANALYSIS COMPLETE\n";
        echo str_repeat("=", 80) . "\n";
    }

    /**
     * Create specific recommendations for fixing the unanswered items bug
     */
    public function createFixRecommendations(): void
    {
        $analysis = $this->analyzeUnansweredItemsBug();
        
        echo "\n🔧 SPECIFIC RECOMMENDATIONS TO FIX UNANSWERED ITEMS BUG:\n";
        echo str_repeat("=", 60) . "\n";

        $recommendations = [];

        // Critical fixes for model vs code mismatches
        if (!empty($analysis['model_vs_code_mismatch'])) {
            $recommendations[] = [
                'priority' => 'CRITICAL',
                'title' => 'Fix Model vs Code Field Name Mismatches',
                'description' => 'Code is looking for fields that do not exist in the model',
                'action' => 'Update code configuration to use correct field names that exist in ReportData model',
                'files' => ['app/Services/CrossLoadedDomainService.php'],
                'fields' => array_column($analysis['model_vs_code_mismatch'], 'field')
            ];
        }

        // High priority fixes for fields with data not configured
        $highImpactFields = array_filter($analysis['fields_with_data_not_configured'], 
            fn($item) => $item['data_count'] > 5);
        
        if (!empty($highImpactFields)) {
            $recommendations[] = [
                'priority' => 'HIGH',
                'title' => 'Configure Fields That Have Data',
                'description' => 'These fields have data but are not configured in services',
                'action' => 'Add field configuration to getFieldMessages() and buildFieldToDomainMap()',
                'files' => ['app/Services/CrossLoadedDomainService.php'],
                'fields' => array_column($highImpactFields, 'field')
            ];
        }

        // Medium priority fixes for Excel import issues
        $importIssues = array_filter($analysis['potential_excel_import_issues'], 
            fn($item) => $item['impact'] === 'HIGH - Excel data being lost during import');
        
        if (!empty($importIssues)) {
            $recommendations[] = [
                'priority' => 'MEDIUM',
                'title' => 'Fix Excel Import Field Mapping',
                'description' => 'Excel fields are not being imported to correct model fields',
                'action' => 'Update import process or add missing fields to model',
                'files' => ['app/Console/Commands/ImportData.php', 'database/migrations'],
                'fields' => array_column($importIssues, 'suspected_excel_field')
            ];
        }

        // Print recommendations
        foreach ($recommendations as $i => $rec) {
            echo sprintf("\n%d. [%s PRIORITY] %s\n", $i + 1, $rec['priority'], $rec['title']);
            echo sprintf("   Description: %s\n", $rec['description']);
            echo sprintf("   Action: %s\n", $rec['action']);
            echo "   Files to update:\n";
            foreach ($rec['files'] as $file) {
                echo sprintf("   - %s\n", $file);
            }
            echo "   Affected fields:\n";
            foreach ($rec['fields'] as $field) {
                echo sprintf("   - %s\n", $field);
            }
        }

        echo "\n" . str_repeat("=", 60) . "\n";
    }
}

// Run the validator if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $validator = new ActualFieldMappingValidator();
    $validator->generateReport();
    $validator->createFixRecommendations();
}