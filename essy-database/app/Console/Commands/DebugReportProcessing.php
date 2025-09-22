<?php

namespace App\Console\Commands;

use App\Models\ReportData;
use App\Services\CrossLoadedDomainService;
use App\Services\DecisionRulesService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DebugReportProcessing extends Command
{
    protected $signature = 'essy:debug-report 
                            {report_id : The ID of the report to debug}
                            {--field= : Specific field to debug (optional)}
                            {--domain= : Specific domain to debug (optional)}
                            {--show-all-fields : Show all fields, even those without data}
                            {--compare-methods : Compare DecisionRulesService vs CrossLoadedDomainService}
                            {--trace-unanswered : Trace why items appear as unanswered}';

    protected $description = 'Debug report item processing to identify missing items and field name issues';

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
        $reportId = $this->argument('report_id');
        
        $this->info("🔍 Debugging report processing for Report ID: {$reportId}");
        $this->newLine();

        // Load the report
        $report = ReportData::find($reportId);
        if (!$report) {
            $this->error("❌ Report with ID {$reportId} not found");
            return Command::FAILURE;
        }

        // Get concern domains
        $concernDomains = $report->getConcernDomains();
        $this->info("📋 Concern domains: " . (empty($concernDomains) ? 'None' : implode(', ', $concernDomains)));
        $this->newLine();

        // Debug specific field if requested
        if ($field = $this->option('field')) {
            $this->debugSpecificField($report, $field, $concernDomains);
            return Command::SUCCESS;
        }

        // Debug specific domain if requested
        if ($domain = $this->option('domain')) {
            $this->debugSpecificDomain($report, $domain, $concernDomains);
            return Command::SUCCESS;
        }

        // Compare processing methods if requested
        if ($this->option('compare-methods')) {
            $this->compareProcessingMethods($report, $concernDomains);
            return Command::SUCCESS;
        }

        // Trace unanswered items if requested
        if ($this->option('trace-unanswered')) {
            $this->traceUnansweredItems($report, $concernDomains);
            return Command::SUCCESS;
        }

        // Default: comprehensive debugging
        $this->comprehensiveDebug($report, $concernDomains);
        
        return Command::SUCCESS;
    }

    private function debugSpecificField(ReportData $report, string $field, array $concernDomains): void
    {
        $this->info("🔎 Debugging field: {$field}");
        $this->newLine();

        $fieldMessages = $this->crossLoadedService->getFieldMessages();
        $fieldToDomainMap = $this->crossLoadedService->getFieldToDomainMap();

        // Check if field exists in configuration
        if (!isset($fieldMessages[$field])) {
            $this->error("❌ Field '{$field}' not found in field messages configuration");
            $this->suggestSimilarFields($field, array_keys($fieldMessages));
            return;
        }

        if (!isset($fieldToDomainMap[$field])) {
            $this->error("❌ Field '{$field}' not found in field-to-domain mapping");
            return;
        }

        $domain = $fieldToDomainMap[$field];
        $message = $fieldMessages[$field];

        $this->info("📝 Field message: {$message}");
        $this->info("🏷️  Domain: {$domain}");
        $this->info("🎯 Is concern domain: " . (in_array($domain, $concernDomains) ? 'Yes' : 'No'));
        $this->newLine();

        // Get raw value from report
        $rawValue = $report->getAttribute($field);
        $this->info("📊 Raw value: " . ($rawValue ?? 'NULL'));

        // Get safe value
        $safeValue = $this->crossLoadedService->safeGetFieldValue($report, $field);
        $this->info("🔒 Safe value: " . ($safeValue ?? 'NULL'));

        // Check if field is cross-loaded
        $crossLoadedGroups = $this->crossLoadedService->getCrossLoadedItemGroups();
        $crossLoadedGroup = null;
        foreach ($crossLoadedGroups as $groupIndex => $group) {
            if (in_array($field, $group)) {
                $crossLoadedGroup = $group;
                break;
            }
        }

        if ($crossLoadedGroup) {
            $this->info("🔗 Cross-loaded group: " . implode(', ', $crossLoadedGroup));
            
            // Check values in all fields of the group
            foreach ($crossLoadedGroup as $groupField) {
                $groupValue = $this->crossLoadedService->safeGetFieldValue($report, $groupField);
                $groupDomain = $fieldToDomainMap[$groupField] ?? 'Unknown';
                $this->line("  {$groupField} ({$groupDomain}): " . ($groupValue ?? 'NULL'));
            }
        } else {
            $this->info("🔗 Cross-loaded: No");
        }

        $this->newLine();

        // Process the field through CrossLoadedDomainService
        if (in_array($domain, $concernDomains)) {
            $domainResults = $this->crossLoadedService->processDomainItems($report, $domain, $concernDomains);
            $this->info("🎯 CrossLoadedDomainService processing results for {$domain}:");
            
            $fieldFound = false;
            foreach (['strengths', 'monitor', 'concerns'] as $category) {
                foreach ($domainResults[$category] as $item) {
                    if (str_contains(strtolower($item), strtolower($message))) {
                        $this->line("  {$category}: {$item}");
                        $fieldFound = true;
                    }
                }
            }
            
            if (!$fieldFound) {
                $this->warn("  ⚠️  Field not found in domain processing results");
            }
        } else {
            $this->info("🎯 Domain not a concern - field would not be processed");
        }

        $this->newLine();

        // Try decision rule lookup
        $this->info("🔍 Decision rule lookup:");
        if ($safeValue) {
            // Extract frequency from value
            $frequency = trim(explode(',', $safeValue)[0]);
            $this->line("  Frequency: {$frequency}");
            
            // Try to find decision rule (this would need to be implemented in DecisionRulesService)
            // For now, just show what would be looked up
            $this->line("  Would look up decision rule for: field='{$field}', frequency='{$frequency}'");
        } else {
            $this->warn("  ⚠️  No value to lookup decision rule");
        }
    }

    private function debugSpecificDomain(ReportData $report, string $domain, array $concernDomains): void
    {
        $this->info("🏷️  Debugging domain: {$domain}");
        $this->newLine();

        $fieldToDomainMap = $this->crossLoadedService->getFieldToDomainMap();
        $fieldMessages = $this->crossLoadedService->getFieldMessages();

        // Get all fields for this domain
        $domainFields = array_filter($fieldToDomainMap, fn($d) => $d === $domain);
        
        if (empty($domainFields)) {
            $this->error("❌ No fields found for domain '{$domain}'");
            return;
        }

        $this->info("📋 Fields in domain: " . count($domainFields));
        $this->info("🎯 Is concern domain: " . (in_array($domain, $concernDomains) ? 'Yes' : 'No'));
        $this->newLine();

        // Process domain if it's a concern
        if (in_array($domain, $concernDomains)) {
            $domainResults = $this->crossLoadedService->processDomainItems($report, $domain, $concernDomains);
            
            $this->info("🎯 Domain processing results:");
            foreach (['strengths', 'monitor', 'concerns'] as $category) {
                $count = count($domainResults[$category]);
                $this->line("  {$category}: {$count} items");
                
                if ($this->option('show-all-fields') || $count > 0) {
                    foreach ($domainResults[$category] as $item) {
                        $this->line("    - {$item}");
                    }
                }
            }
            $this->newLine();
        }

        // Show detailed field analysis
        $this->info("📊 Field-by-field analysis:");
        foreach ($domainFields as $field => $fieldDomain) {
            $rawValue = $report->getAttribute($field);
            $safeValue = $this->crossLoadedService->safeGetFieldValue($report, $field);
            $message = $fieldMessages[$field] ?? 'No message';
            
            $this->line("  {$field}:");
            $this->line("    Message: {$message}");
            $this->line("    Raw: " . ($rawValue ?? 'NULL'));
            $this->line("    Safe: " . ($safeValue ?? 'NULL'));
            
            if (!$this->option('show-all-fields') && !$safeValue) {
                $this->line("    Status: No data - would not appear in report");
            } elseif ($safeValue) {
                $frequency = trim(explode(',', $safeValue)[0]);
                $category = $this->crossLoadedService->categorizeFieldValue($field, $frequency);
                $this->line("    Frequency: {$frequency}");
                $this->line("    Category: {$category}");
            }
            
            $this->newLine();
        }
    }

    private function compareProcessingMethods(ReportData $report, array $concernDomains): void
    {
        $this->info("⚖️  Comparing processing methods...");
        $this->newLine();

        $domains = ['Academic Skills', 'Behavior', 'Physical Health', 'Social & Emotional Well-Being', 'Supports Outside of School'];
        
        foreach ($domains as $domain) {
            if (!in_array($domain, $concernDomains)) {
                continue;
            }
            
            $this->info("🏷️  Domain: {$domain}");
            
            // CrossLoadedDomainService results
            $crossLoadedResults = $this->crossLoadedService->processDomainItems($report, $domain, $concernDomains);
            $crossLoadedTotal = array_sum(array_map('count', $crossLoadedResults));
            
            $this->line("  CrossLoadedDomainService: {$crossLoadedTotal} items");
            foreach (['strengths', 'monitor', 'concerns'] as $category) {
                $this->line("    {$category}: " . count($crossLoadedResults[$category]));
            }
            
            // TODO: Add DecisionRulesService comparison when available
            $this->line("  DecisionRulesService: [Not implemented in this debug command]");
            
            $this->newLine();
        }
    }

    private function traceUnansweredItems(ReportData $report, array $concernDomains): void
    {
        $this->info("🔍 Tracing unanswered items logic...");
        $this->newLine();

        $fieldMessages = $this->crossLoadedService->getFieldMessages();
        $fieldToDomainMap = $this->crossLoadedService->getFieldToDomainMap();
        
        // Simulate unanswered items logic
        $potentialUnansweredItems = [];
        
        foreach ($fieldMessages as $field => $message) {
            $domain = $fieldToDomainMap[$field] ?? null;
            
            // Skip if not in a concern domain
            if (!$domain || !in_array($domain, $concernDomains)) {
                continue;
            }
            
            $rawValue = $report->getAttribute($field);
            $safeValue = $this->crossLoadedService->safeGetFieldValue($report, $field);
            
            // Check if this would be considered "unanswered"
            $isUnanswered = !$safeValue || $safeValue === '' || trim($safeValue) === '-99';
            
            if ($isUnanswered) {
                $potentialUnansweredItems[] = [
                    'field' => $field,
                    'message' => $message,
                    'domain' => $domain,
                    'raw_value' => $rawValue,
                    'safe_value' => $safeValue,
                    'reason' => $this->determineUnansweredReason($rawValue, $safeValue)
                ];
            }
        }
        
        if (empty($potentialUnansweredItems)) {
            $this->info("✅ No items would be marked as unanswered");
            return;
        }
        
        $this->warn("⚠️  Found " . count($potentialUnansweredItems) . " potentially unanswered items:");
        $this->newLine();
        
        foreach ($potentialUnansweredItems as $item) {
            $this->line("❓ {$item['field']} ({$item['domain']})");
            $this->line("   Message: {$item['message']}");
            $this->line("   Raw value: " . ($item['raw_value'] ?? 'NULL'));
            $this->line("   Safe value: " . ($item['safe_value'] ?? 'NULL'));
            $this->line("   Reason: {$item['reason']}");
            $this->newLine();
        }
        
        // Check for field name mismatches
        $this->info("🔍 Checking for potential field name mismatches...");
        $this->checkFieldNameMismatches($report, $potentialUnansweredItems);
    }

    private function comprehensiveDebug(ReportData $report, array $concernDomains): void
    {
        $this->info("🔍 Comprehensive report debugging...");
        $this->newLine();

        // Basic report info
        $this->info("📋 Report Information:");
        $this->line("  ID: {$report->id}");
        $this->line("  Student: {$report->FN_STUDENT} {$report->LN_STUDENT}");
        $this->line("  Teacher: {$report->FN_TEACHER} {$report->LN_TEACHER}");
        $this->line("  School: {$report->SCHOOL}");
        $this->newLine();

        // Domain analysis
        $this->info("🏷️  Domain Analysis:");
        $domains = ['Academic Skills', 'Behavior', 'Physical Health', 'Social & Emotional Well-Being', 'Supports Outside of School'];
        
        foreach ($domains as $domain) {
            $isConcern = in_array($domain, $concernDomains);
            $this->line("  {$domain}: " . ($isConcern ? '🔴 Concern' : '🟢 Not a concern'));
            
            if ($isConcern) {
                $results = $this->crossLoadedService->processDomainItems($report, $domain, $concernDomains);
                $total = array_sum(array_map('count', $results));
                $this->line("    Processed items: {$total}");
            }
        }
        $this->newLine();

        // Field usage summary
        $this->info("📊 Field Usage Summary:");
        $fieldMessages = $this->crossLoadedService->getFieldMessages();
        $fieldsWithData = 0;
        $fieldsWithoutData = 0;
        
        foreach ($fieldMessages as $field => $message) {
            $value = $this->crossLoadedService->safeGetFieldValue($report, $field);
            if ($value) {
                $fieldsWithData++;
            } else {
                $fieldsWithoutData++;
            }
        }
        
        $this->line("  Fields with data: {$fieldsWithData}");
        $this->line("  Fields without data: {$fieldsWithoutData}");
        $this->line("  Total configured fields: " . count($fieldMessages));
        $this->newLine();

        // Cross-loaded analysis
        $this->info("🔗 Cross-loaded Analysis:");
        $crossLoadedGroups = $this->crossLoadedService->getCrossLoadedItemGroups();
        $this->line("  Total cross-loaded groups: " . count($crossLoadedGroups));
        
        $groupsWithData = 0;
        foreach ($crossLoadedGroups as $group) {
            $hasData = false;
            foreach ($group as $field) {
                if ($this->crossLoadedService->safeGetFieldValue($report, $field)) {
                    $hasData = true;
                    break;
                }
            }
            if ($hasData) {
                $groupsWithData++;
            }
        }
        
        $this->line("  Groups with data: {$groupsWithData}");
        $this->newLine();

        // Recommendations
        $this->info("💡 Recommendations:");
        if ($fieldsWithoutData > $fieldsWithData) {
            $this->warn("  ⚠️  Many fields have no data - check field name consistency");
        }
        
        if (empty($concernDomains)) {
            $this->info("  ℹ️  No concern domains - report would show minimal content");
        }
        
        $this->line("  💡 Use --field=FIELD_NAME to debug specific fields");
        $this->line("  💡 Use --domain=DOMAIN_NAME to debug specific domains");
        $this->line("  💡 Use --trace-unanswered to see why items appear as unanswered");
    }

    private function determineUnansweredReason(mixed $rawValue, mixed $safeValue): string
    {
        if ($rawValue === null) {
            return "Field is NULL in database";
        }
        
        if ($rawValue === '') {
            return "Field is empty string in database";
        }
        
        if (trim($rawValue) === '-99') {
            return "Field has -99 value (no response)";
        }
        
        if ($safeValue === null && $rawValue !== null) {
            return "Safe value extraction failed - possible data format issue";
        }
        
        return "Unknown reason";
    }

    private function checkFieldNameMismatches(ReportData $report, array $unansweredItems): void
    {
        // Get all actual field names from the report
        $reportAttributes = $report->getAttributes();
        $actualFields = array_keys($reportAttributes);
        
        // Get configured field names
        $fieldMessages = $this->crossLoadedService->getFieldMessages();
        $configuredFields = array_keys($fieldMessages);
        
        // Look for potential mismatches
        $potentialMismatches = [];
        
        foreach ($unansweredItems as $item) {
            $configuredField = $item['field'];
            
            // Look for similar field names in actual data
            $similarFields = [];
            foreach ($actualFields as $actualField) {
                $similarity = similar_text(strtolower($configuredField), strtolower($actualField), $percent);
                if ($percent > 70 && $configuredField !== $actualField) {
                    $similarFields[] = [
                        'field' => $actualField,
                        'similarity' => $percent,
                        'value' => $reportAttributes[$actualField]
                    ];
                }
            }
            
            if (!empty($similarFields)) {
                $potentialMismatches[$configuredField] = $similarFields;
            }
        }
        
        if (!empty($potentialMismatches)) {
            $this->warn("🔍 Potential field name mismatches found:");
            foreach ($potentialMismatches as $configuredField => $similarFields) {
                $this->line("  {$configuredField} might be:");
                foreach ($similarFields as $similar) {
                    $value = $similar['value'] ? "'{$similar['value']}'" : 'NULL';
                    $this->line("    - {$similar['field']} ({$similar['similarity']}% similar, value: {$value})");
                }
            }
        } else {
            $this->info("✅ No obvious field name mismatches detected");
        }
    }

    private function suggestSimilarFields(string $field, array $availableFields): void
    {
        $suggestions = [];
        
        foreach ($availableFields as $availableField) {
            $similarity = similar_text(strtolower($field), strtolower($availableField), $percent);
            if ($percent > 60) {
                $suggestions[] = [
                    'field' => $availableField,
                    'similarity' => $percent
                ];
            }
        }
        
        if (!empty($suggestions)) {
            // Sort by similarity
            usort($suggestions, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
            
            $this->info("💡 Did you mean:");
            foreach (array_slice($suggestions, 0, 5) as $suggestion) {
                $this->line("  - {$suggestion['field']} ({$suggestion['similarity']}% similar)");
            }
        }
    }
}