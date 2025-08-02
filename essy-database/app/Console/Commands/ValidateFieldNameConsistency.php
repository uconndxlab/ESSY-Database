<?php

namespace App\Console\Commands;

use App\Models\ReportData;
use App\Services\CrossLoadedDomainService;
use App\Services\DecisionRulesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ValidateFieldNameConsistency extends Command
{
    protected $signature = 'essy:validate-field-names 
                            {--detailed : Show detailed field analysis}
                            {--fix-suggestions : Show suggestions for fixing issues}';

    protected $description = 'Validate field name consistency between configuration and ReportData model';

    private CrossLoadedDomainService $crossLoadedService;
    private DecisionRulesService $decisionRulesService;

    public function __construct(
        CrossLoadedDomainService $crossLoadedService,
        DecisionRulesService $decisionRulesService
    ) {
        parent::__construct();
        $this->crossLoadedService = $crossLoadedService;
        $this->decisionRulesService = $decisionRulesService;
    }

    public function handle(): int
    {
        $this->info('🔍 Validating field name consistency...');
        $this->newLine();

        $hasErrors = false;

        // 1. Validate ReportData model fields
        $modelValidation = $this->validateModelFields();
        if (!$modelValidation['valid']) {
            $hasErrors = true;
        }

        // 2. Validate CrossLoadedDomainService configuration
        $crossLoadedValidation = $this->validateCrossLoadedConfiguration();
        if (!$crossLoadedValidation['valid']) {
            $hasErrors = true;
        }

        // 3. Validate field-to-domain mapping completeness
        $mappingValidation = $this->validateFieldToDomainMapping();
        if (!$mappingValidation['valid']) {
            $hasErrors = true;
        }

        // 4. Validate cross-loaded group configuration
        $groupValidation = $this->validateCrossLoadedGroups();
        if (!$groupValidation['valid']) {
            $hasErrors = true;
        }

        // 5. Check database schema consistency
        $schemaValidation = $this->validateDatabaseSchema();
        if (!$schemaValidation['valid']) {
            $hasErrors = true;
        }

        // 6. Validate against actual data
        $dataValidation = $this->validateAgainstActualData();
        if (!$dataValidation['valid']) {
            $hasErrors = true;
        }

        $this->newLine();
        if ($hasErrors) {
            $this->error('❌ Field name validation completed with errors');
            return Command::FAILURE;
        } else {
            $this->info('✅ All field name validations passed');
            return Command::SUCCESS;
        }
    }

    private function validateModelFields(): array
    {
        $this->info('📋 Validating ReportData model fields...');
        
        $model = new ReportData();
        $fillableFields = $model->getFillable();
        $errors = [];
        $warnings = [];

        // Check for duplicate fields
        $duplicates = array_diff_assoc($fillableFields, array_unique($fillableFields));
        if (!empty($duplicates)) {
            $errors[] = 'Duplicate fields found in fillable array: ' . implode(', ', $duplicates);
        }

        // Check for common field name patterns that might indicate issues
        $suspiciousPatterns = [
            '/^[A-Z]+_[A-Z]+_[A-Z]+_CL\d+$/' => 'Cross-loaded field pattern',
            '/^[A-Z]+_[A-Z]+AGGRESS$/' => 'Aggression field pattern',
            '/HYGIEN/' => 'Hygiene field (check spelling)',
        ];

        foreach ($fillableFields as $field) {
            foreach ($suspiciousPatterns as $pattern => $description) {
                if (preg_match($pattern, $field)) {
                    $this->line("  Found {$description}: {$field}");
                }
            }
        }

        $this->info("  ✓ Found " . count($fillableFields) . " fillable fields");
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->error("  ❌ {$error}");
            }
        }

        if (!empty($warnings)) {
            foreach ($warnings as $warning) {
                $this->warn("  ⚠️  {$warning}");
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];
    }

    private function validateCrossLoadedConfiguration(): array
    {
        $this->info('🔗 Validating CrossLoadedDomainService configuration...');
        
        $model = new ReportData();
        $fillableFields = $model->getFillable();
        $fieldMessages = $this->crossLoadedService->getFieldMessages();
        $fieldToDomainMap = $this->crossLoadedService->getFieldToDomainMap();
        
        $errors = [];
        $warnings = [];

        // Check if all fields in getFieldMessages() exist in model
        foreach ($fieldMessages as $field => $message) {
            if (!in_array($field, $fillableFields)) {
                $errors[] = "Field '{$field}' from getFieldMessages() not found in ReportData fillable fields";
            }
        }

        // Check if all fields in fieldToDomainMap exist in model
        foreach ($fieldToDomainMap as $field => $domain) {
            if (!in_array($field, $fillableFields)) {
                $errors[] = "Field '{$field}' from fieldToDomainMap not found in ReportData fillable fields";
            }
        }

        // Check for fields in domain map but not in messages
        $missingMessages = array_diff(array_keys($fieldToDomainMap), array_keys($fieldMessages));
        foreach ($missingMessages as $field) {
            $warnings[] = "Field '{$field}' has domain mapping but no field message";
        }

        // Check for fields in messages but not in domain map
        $missingMappings = array_diff(array_keys($fieldMessages), array_keys($fieldToDomainMap));
        foreach ($missingMappings as $field) {
            $warnings[] = "Field '{$field}' has field message but no domain mapping";
        }

        $this->info("  ✓ Validated " . count($fieldMessages) . " field messages");
        $this->info("  ✓ Validated " . count($fieldToDomainMap) . " field-to-domain mappings");

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->error("  ❌ {$error}");
            }
        }

        if (!empty($warnings)) {
            foreach ($warnings as $warning) {
                $this->warn("  ⚠️  {$warning}");
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];
    }

    private function validateFieldToDomainMapping(): array
    {
        $this->info('🗺️  Validating field-to-domain mapping completeness...');
        
        $fieldToDomainMap = $this->crossLoadedService->getFieldToDomainMap();
        $validDomains = [
            'Academic Skills',
            'Behavior', 
            'Physical Health',
            'Social & Emotional Well-Being',
            'Supports Outside of School'
        ];
        
        $errors = [];
        $warnings = [];
        $domainCounts = [];

        // Check domain validity and count fields per domain
        foreach ($fieldToDomainMap as $field => $domain) {
            if (!in_array($domain, $validDomains)) {
                $errors[] = "Invalid domain '{$domain}' for field '{$field}'";
            }
            
            $domainCounts[$domain] = ($domainCounts[$domain] ?? 0) + 1;
        }

        // Report domain distribution
        foreach ($validDomains as $domain) {
            $count = $domainCounts[$domain] ?? 0;
            $this->line("  {$domain}: {$count} fields");
            
            if ($count === 0) {
                $warnings[] = "No fields mapped to domain '{$domain}'";
            }
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->error("  ❌ {$error}");
            }
        }

        if (!empty($warnings)) {
            foreach ($warnings as $warning) {
                $this->warn("  ⚠️  {$warning}");
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];
    }

    private function validateCrossLoadedGroups(): array
    {
        $this->info('👥 Validating cross-loaded group configuration...');
        
        $model = new ReportData();
        $fillableFields = $model->getFillable();
        $crossLoadedGroups = $this->crossLoadedService->getCrossLoadedItemGroups();
        $fieldToDomainMap = $this->crossLoadedService->getFieldToDomainMap();
        
        $errors = [];
        $warnings = [];

        foreach ($crossLoadedGroups as $groupIndex => $group) {
            $this->line("  Group {$groupIndex}: " . implode(', ', $group));
            
            // Check if all fields in group exist in model
            foreach ($group as $field) {
                if (!in_array($field, $fillableFields)) {
                    $errors[] = "Field '{$field}' in group {$groupIndex} not found in ReportData fillable fields";
                }
            }
            
            // Check if group has at least 2 fields (truly cross-loaded)
            if (count($group) < 2) {
                $warnings[] = "Group {$groupIndex} has only " . count($group) . " field(s) - not truly cross-loaded";
            }
            
            // Check if fields in group span multiple domains
            $domainsInGroup = [];
            foreach ($group as $field) {
                if (isset($fieldToDomainMap[$field])) {
                    $domainsInGroup[] = $fieldToDomainMap[$field];
                }
            }
            $uniqueDomains = array_unique($domainsInGroup);
            
            if (count($uniqueDomains) < 2) {
                $warnings[] = "Group {$groupIndex} fields are all in the same domain - may not need cross-loading";
            } else {
                $this->line("    Spans domains: " . implode(', ', $uniqueDomains));
            }
        }

        $this->info("  ✓ Validated " . count($crossLoadedGroups) . " cross-loaded groups");

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->error("  ❌ {$error}");
            }
        }

        if (!empty($warnings)) {
            foreach ($warnings as $warning) {
                $this->warn("  ⚠️  {$warning}");
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];
    }

    private function validateDatabaseSchema(): array
    {
        $this->info('🗄️  Validating database schema consistency...');
        
        $model = new ReportData();
        $fillableFields = $model->getFillable();
        $tableName = $model->getTable();
        
        $errors = [];
        $warnings = [];

        try {
            // Get actual database columns
            $columns = Schema::getColumnListing($tableName);
            
            // Check if all fillable fields exist as columns
            foreach ($fillableFields as $field) {
                if (!in_array($field, $columns)) {
                    $errors[] = "Fillable field '{$field}' does not exist as database column";
                }
            }
            
            // Check for columns that aren't in fillable (might be missing from model)
            $nonFillableColumns = array_diff($columns, $fillableFields);
            $systemColumns = ['id', 'created_at', 'updated_at']; // Expected system columns
            $unexpectedColumns = array_diff($nonFillableColumns, $systemColumns);
            
            if (!empty($unexpectedColumns)) {
                foreach ($unexpectedColumns as $column) {
                    $warnings[] = "Database column '{$column}' not in fillable fields - might be missing from model";
                }
            }
            
            $this->info("  ✓ Database has " . count($columns) . " columns");
            $this->info("  ✓ Model has " . count($fillableFields) . " fillable fields");
            
        } catch (\Exception $e) {
            $errors[] = "Failed to validate database schema: " . $e->getMessage();
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->error("  ❌ {$error}");
            }
        }

        if (!empty($warnings)) {
            foreach ($warnings as $warning) {
                $this->warn("  ⚠️  {$warning}");
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];
    }

    private function validateAgainstActualData(): array
    {
        $this->info('📊 Validating against actual report data...');
        
        $errors = [];
        $warnings = [];

        try {
            // Get a sample of report data to check field usage
            $sampleSize = 10;
            $reports = ReportData::take($sampleSize)->get();
            
            if ($reports->isEmpty()) {
                $warnings[] = "No report data found in database - cannot validate against actual data";
                return ['valid' => true, 'errors' => $errors, 'warnings' => $warnings];
            }
            
            $fieldMessages = $this->crossLoadedService->getFieldMessages();
            $fieldUsageStats = [];
            
            // Check field usage in actual data
            foreach ($reports as $report) {
                foreach ($fieldMessages as $field => $message) {
                    $value = $report->getAttribute($field);
                    $hasValue = $value !== null && $value !== '' && trim($value) !== '-99';
                    
                    if (!isset($fieldUsageStats[$field])) {
                        $fieldUsageStats[$field] = ['total' => 0, 'with_data' => 0];
                    }
                    
                    $fieldUsageStats[$field]['total']++;
                    if ($hasValue) {
                        $fieldUsageStats[$field]['with_data']++;
                    }
                }
            }
            
            // Report fields that never have data
            $fieldsWithoutData = [];
            foreach ($fieldUsageStats as $field => $stats) {
                if ($stats['with_data'] === 0) {
                    $fieldsWithoutData[] = $field;
                }
            }
            
            if (!empty($fieldsWithoutData)) {
                $this->warn("  ⚠️  Fields with no data in sample: " . implode(', ', $fieldsWithoutData));
            }
            
            $this->info("  ✓ Validated against {$sampleSize} report records");
            
            if ($this->option('detailed')) {
                $this->newLine();
                $this->info('📈 Field usage statistics:');
                foreach ($fieldUsageStats as $field => $stats) {
                    $percentage = round(($stats['with_data'] / $stats['total']) * 100, 1);
                    $this->line("  {$field}: {$stats['with_data']}/{$stats['total']} ({$percentage}%)");
                }
            }
            
        } catch (\Exception $e) {
            $errors[] = "Failed to validate against actual data: " . $e->getMessage();
        }

        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->error("  ❌ {$error}");
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];
    }
}