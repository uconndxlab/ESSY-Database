<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\CrossLoadedDomainService;
use App\Services\ReportTemplateHelper;
use App\Models\ReportData;
use App\Constants\ReportDataFields;
use App\ValueObjects\ValidationResult;

class CrossLoadedImplementationValidator
{
    private CrossLoadedDomainService $crossLoadedService;
    private ReportTemplateHelper $templateHelper;
    private array $errors = [];
    private array $warnings = [];
    private array $validationResults = [];

    public function __construct()
    {
        $this->crossLoadedService = app(CrossLoadedDomainService::class);
        $this->templateHelper = app(ReportTemplateHelper::class);
    }

    /**
     * Run comprehensive validation of the cross-loaded domain implementation
     */
    public function validateImplementation(): array
    {
        echo "Starting comprehensive cross-loaded domain validation...\n\n";

        // 1. Validate service configuration
        $this->validateServiceConfiguration();

        // 2. Validate field mappings
        $this->validateFieldMappings();

        // 3. Validate cross-loaded groups
        $this->validateCrossLoadedGroups();

        // 4. Validate domain indicators
        $this->validateDomainIndicators();

        // 5. Validate constants consistency
        $this->validateConstantsConsistency();

        // 6. Validate model integration
        $this->validateModelIntegration();

        // 7. Test core functionality
        $this->testCoreFunctionality();

        return [
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'validation_results' => $this->validationResults
        ];
    }

    /**
     * Validate service configuration
     */
    private function validateServiceConfiguration(): void
    {
        echo "1. Validating service configuration...\n";

        try {
            $result = $this->crossLoadedService->validateCrossLoadedConfiguration();
            $this->validationResults['service_config'] = $result;

            if (!$result->isValid) {
                $this->errors = array_merge($this->errors, $result->errors);
            }

            if ($result->hasWarnings()) {
                $this->warnings = array_merge($this->warnings, $result->warnings);
            }

            echo "   ✓ Service configuration validation completed\n";
        } catch (\Exception $e) {
            $this->errors[] = "Service configuration validation failed: " . $e->getMessage();
            echo "   ✗ Service configuration validation failed\n";
        }
    }

    /**
     * Validate field mappings
     */
    private function validateFieldMappings(): void
    {
        echo "2. Validating field mappings...\n";

        try {
            $modelFields = (new ReportData())->getFillable();
            $result = $this->crossLoadedService->validateDatabaseFields($modelFields);
            $this->validationResults['field_mappings'] = $result;

            if (!$result->isValid) {
                $this->errors = array_merge($this->errors, $result->errors);
            }

            if ($result->hasWarnings()) {
                $this->warnings = array_merge($this->warnings, $result->warnings);
            }

            echo "   ✓ Field mappings validation completed\n";
        } catch (\Exception $e) {
            $this->errors[] = "Field mappings validation failed: " . $e->getMessage();
            echo "   ✗ Field mappings validation failed\n";
        }
    }

    /**
     * Validate cross-loaded groups
     */
    private function validateCrossLoadedGroups(): void
    {
        echo "3. Validating cross-loaded groups...\n";

        try {
            $groups = $this->crossLoadedService->getCrossLoadedItemGroups();
            $mapping = $this->crossLoadedService->getFieldToDomainMap();

            foreach ($groups as $groupIndex => $group) {
                // Check group has at least 2 fields
                if (count($group) < 2) {
                    $this->warnings[] = "Cross-loaded group {$groupIndex} has only " . count($group) . " field(s)";
                }

                // Check all fields in group are mapped to different domains
                $domainsInGroup = [];
                foreach ($group as $field) {
                    if (isset($mapping[$field])) {
                        $domainsInGroup[] = $mapping[$field];
                    } else {
                        $this->errors[] = "Field {$field} in group {$groupIndex} not found in domain mapping";
                    }
                }

                $uniqueDomains = array_unique($domainsInGroup);
                if (count($uniqueDomains) < 2) {
                    $this->warnings[] = "Cross-loaded group {$groupIndex} fields are not truly cross-loaded (all in same domain)";
                }
            }

            echo "   ✓ Cross-loaded groups validation completed\n";
        } catch (\Exception $e) {
            $this->errors[] = "Cross-loaded groups validation failed: " . $e->getMessage();
            echo "   ✗ Cross-loaded groups validation failed\n";
        }
    }

    /**
     * Validate domain indicators
     */
    private function validateDomainIndicators(): void
    {
        echo "4. Validating domain indicators...\n";

        try {
            $indicators = $this->templateHelper->getDomainIndicators();
            $mapping = $this->crossLoadedService->getFieldToDomainMap();
            $modelFields = (new ReportData())->getFillable();

            foreach ($indicators as $domain => $domainIndicators) {
                foreach ($domainIndicators as $field => $config) {
                    // Check field exists in model
                    if (!in_array($field, $modelFields)) {
                        $this->errors[] = "Domain indicator field {$field} not found in ReportData model";
                    }

                    // Check field is mapped to correct domain
                    if (isset($mapping[$field]) && $mapping[$field] !== $domain) {
                        $this->errors[] = "Field {$field} mapped to '{$mapping[$field]}' but used in '{$domain}' indicators";
                    }

                    // Check message exists
                    if (empty($config['message'])) {
                        $this->warnings[] = "Field {$field} in domain {$domain} has empty message";
                    }
                }
            }

            echo "   ✓ Domain indicators validation completed\n";
        } catch (\Exception $e) {
            $this->errors[] = "Domain indicators validation failed: " . $e->getMessage();
            echo "   ✗ Domain indicators validation failed\n";
        }
    }

    /**
     * Validate constants consistency
     */
    private function validateConstantsConsistency(): void
    {
        echo "5. Validating constants consistency...\n";

        try {
            $constantsFields = ReportDataFields::getAllFields();
            $serviceMapping = $this->crossLoadedService->getFieldToDomainMap();
            $constantsMapping = ReportDataFields::getDomainMapping();
            $constantsGroups = ReportDataFields::getCrossLoadedGroups();
            $serviceGroups = $this->crossLoadedService->getCrossLoadedItemGroups();

            // Check domain mapping consistency
            foreach ($serviceMapping as $field => $domain) {
                if (isset($constantsMapping[$field]) && $constantsMapping[$field] !== $domain) {
                    $this->errors[] = "Domain mapping inconsistency for {$field}: service='{$domain}', constants='{$constantsMapping[$field]}'";
                }
            }

            // Check cross-loaded groups consistency
            $serviceGroupsFlat = [];
            foreach ($serviceGroups as $group) {
                foreach ($group as $field) {
                    $serviceGroupsFlat[] = $field;
                }
            }

            $constantsGroupsFlat = [];
            foreach ($constantsGroups as $group) {
                foreach ($group as $field) {
                    $constantsGroupsFlat[] = $field;
                }
            }

            $missingInConstants = array_diff($serviceGroupsFlat, $constantsGroupsFlat);
            $missingInService = array_diff($constantsGroupsFlat, $serviceGroupsFlat);

            foreach ($missingInConstants as $field) {
                $this->warnings[] = "Field {$field} in service groups but not in constants groups";
            }

            foreach ($missingInService as $field) {
                $this->warnings[] = "Field {$field} in constants groups but not in service groups";
            }

            echo "   ✓ Constants consistency validation completed\n";
        } catch (\Exception $e) {
            $this->errors[] = "Constants consistency validation failed: " . $e->getMessage();
            echo "   ✗ Constants consistency validation failed\n";
        }
    }

    /**
     * Validate model integration
     */
    private function validateModelIntegration(): void
    {
        echo "6. Validating model integration...\n";

        try {
            $report = new ReportData();
            
            // Test safe attribute access
            $testField = 'A_READ';
            $result = $report->safeGetAttribute($testField, 'default');
            if ($result !== 'default') {
                $this->warnings[] = "safeGetAttribute should return default for empty field";
            }

            // Test concern domains method
            $concernDomains = $report->getConcernDomains();
            if (!is_array($concernDomains)) {
                $this->errors[] = "getConcernDomains should return array";
            }

            // Test field validation
            if (!$report->hasValidValue('nonexistent_field')) {
                // This is expected behavior
            }

            echo "   ✓ Model integration validation completed\n";
        } catch (\Exception $e) {
            $this->errors[] = "Model integration validation failed: " . $e->getMessage();
            echo "   ✗ Model integration validation failed\n";
        }
    }

    /**
     * Test core functionality
     */
    private function testCoreFunctionality(): void
    {
        echo "7. Testing core functionality...\n";

        try {
            // Test dagger field determination
            $concernDomains = ['Academic Skills', 'Physical Health'];
            $daggerFields = $this->crossLoadedService->getFieldsRequiringDagger($concernDomains);
            
            if (!is_array($daggerFields)) {
                $this->errors[] = "getFieldsRequiringDagger should return array";
            }

            // Should include articulate fields since they're cross-loaded between these domains
            if (!isset($daggerFields['A_P_ARTICULATE_CL1']) || !isset($daggerFields['A_P_ARTICULATE_CL2'])) {
                $this->errors[] = "Articulate fields should require dagger when both Academic Skills and Physical Health are concerns";
            }

            // Test safe field value access
            $report = new ReportData();
            $report->A_READ = 'Test value';
            $value = $this->crossLoadedService->safeGetFieldValue($report, 'A_READ');
            
            if ($value !== 'Test value') {
                $this->errors[] = "safeGetFieldValue should return correct value";
            }

            // Test item formatting
            $formattedItem = $this->templateHelper->formatItemWithDagger('Test item', 'A_P_ARTICULATE_CL1', $daggerFields);
            if (!str_contains($formattedItem, '†')) {
                $this->errors[] = "formatItemWithDagger should add dagger symbol";
            }

            echo "   ✓ Core functionality testing completed\n";
        } catch (\Exception $e) {
            $this->errors[] = "Core functionality testing failed: " . $e->getMessage();
            echo "   ✗ Core functionality testing failed\n";
        }
    }

    /**
     * Generate validation report
     */
    public function generateReport(): void
    {
        $results = $this->validateImplementation();

        echo "\n=== CROSS-LOADED DOMAIN IMPLEMENTATION VALIDATION REPORT ===\n";
        echo "Errors: " . count($results['errors']) . "\n";
        echo "Warnings: " . count($results['warnings']) . "\n\n";

        if (!empty($results['errors'])) {
            echo "ERRORS:\n";
            foreach ($results['errors'] as $error) {
                echo "  ✗ {$error}\n";
            }
            echo "\n";
        }

        if (!empty($results['warnings'])) {
            echo "WARNINGS:\n";
            foreach ($results['warnings'] as $warning) {
                echo "  ⚠ {$warning}\n";
            }
            echo "\n";
        }

        if (empty($results['errors']) && empty($results['warnings'])) {
            echo "✅ All validations passed successfully!\n";
        } elseif (empty($results['errors'])) {
            echo "✅ Implementation is valid with some warnings.\n";
        } else {
            echo "❌ Implementation has errors that need to be addressed.\n";
        }

        echo "\nValidation Summary:\n";
        foreach ($results['validation_results'] as $test => $result) {
            if ($result instanceof ValidationResult) {
                $status = $result->isValid ? '✓' : '✗';
                echo "  {$status} {$test}: " . ($result->isValid ? 'PASS' : 'FAIL') . "\n";
            }
        }
    }
}

// Run the validator if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $validator = new CrossLoadedImplementationValidator();
    $validator->generateReport();
}