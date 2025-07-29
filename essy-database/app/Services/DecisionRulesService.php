<?php

namespace App\Services;

use App\Models\DecisionRule;
use App\Models\ReportData;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Service for handling decision rules lookup functionality.
 * 
 * This service can be toggled on/off using the ESSY_USE_DECISION_RULES environment variable.
 * When disabled, the system falls back to the traditional concatenation approach.
 * 
 * Configuration:
 * - config('essy.use_decision_rules'): Enable/disable decision rules lookup
 * - config('essy.decision_rules_fallback'): Enable/disable fallback to concatenation
 * 
 * @see config/essy.php for configuration options
 * @see docs/CONFIGURATION.md for detailed configuration guide
 */
class DecisionRulesService
{
    private CrossLoadedDomainService $crossLoadedService;
    private LoggerInterface $logger;

    public function __construct(CrossLoadedDomainService $crossLoadedService, ?LoggerInterface $logger = null)
    {
        $this->crossLoadedService = $crossLoadedService;
        $this->logger = $logger ?? Log::channel('default');
    }

    /**
     * Get decision text for a specific item code and frequency combination
     *
     * @param string $itemCode
     * @param string $frequency
     * @return string|null
     */
    public function getDecisionText(string $itemCode, string $frequency): ?string
    {
        try {
            $decisionText = DecisionRule::getDecisionText($itemCode, $frequency);
            
            if (!$decisionText) {
                $this->logDecisionRuleError('Decision rule not found', [
                    'item_code' => $itemCode,
                    'frequency' => $frequency
                ]);
            }
            
            return $decisionText;
        } catch (\Exception $e) {
            $this->logDecisionRuleError('Error retrieving decision text', [
                'item_code' => $itemCode,
                'frequency' => $frequency,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Process domain items and categorize them into strengths, monitor, and concerns
     * Integrates with existing cross-loaded logic while using decision rules for text
     *
     * @param ReportData $report
     * @param string $domain
     * @param array $concernDomains
     * @return array
     */
    public function processDomainItems(ReportData $report, string $domain, array $concernDomains): array
    {
        try {
            $fieldMessages = $this->crossLoadedService->getFieldMessages();
            $fieldsThatNeedDagger = $this->crossLoadedService->getFieldsRequiringDagger($concernDomains);
            $fieldToDomainMap = $this->crossLoadedService->getFieldToDomainMap();
            
            $results = ['strengths' => [], 'monitor' => [], 'concerns' => []];
            
            foreach ($fieldToDomainMap as $field => $fieldDomain) {
                if ($fieldDomain !== $domain) continue;
                if (!isset($fieldMessages[$field])) continue;
                
                $valueRaw = $this->crossLoadedService->safeGetFieldValue($report, $field);
                
                // For cross-loaded items, if this field is empty, try to get value from primary field
                if (!$valueRaw && isset($fieldsThatNeedDagger[$field])) {
                    $valueRaw = $this->getCrossLoadedValue($report, $field);
                }
                
                // If still no value, skip this item (don't show items without frequency responses)
                if (!$valueRaw) continue;
                
                $hasConfidence = str_contains($valueRaw, ',');
                $value = trim(explode(',', $valueRaw)[0]);
                
                // Ensure we have a valid frequency response
                if (empty($value) || $value === '-99') continue;
                
                // Try to get decision rule text first
                // Use the field message text as the item code for lookup
                $fieldMessage = $fieldMessages[$field] ?? '';
                
                // Try multiple variations to match the Excel format
                $itemCodeVariations = [
                    ucfirst($fieldMessage), // "Communicates with adults effectively."
                    $fieldMessage, // Original case
                ];
                
                // Handle the common pattern where field message starts with lowercase verb
                // but Excel item_code starts with "Effectively"
                if (preg_match('/^communicates with (.+) effectively\.$/', $fieldMessage, $matches)) {
                    $itemCodeVariations[] = 'Effectively communicates with ' . $matches[1] . '.';
                }
                
                $decisionText = null;
                foreach ($itemCodeVariations as $itemCodeForLookup) {
                    $decisionText = $this->getDecisionText($itemCodeForLookup, $value);
                    if ($decisionText) {
                        break; // Found a match, stop trying
                    }
                }
                
                if ($decisionText) {
                    // Use decision rule text
                    $sentence = $decisionText;
                } else {
                    // Check if fallback is enabled
                    if (config('essy.decision_rules_fallback', true)) {
                        // Fallback to concatenation approach
                        $prefix = ucfirst(strtolower($value));
                        $sentence = "{$prefix} {$fieldMessages[$field]}";
                        
                        $this->logDecisionRuleError('Falling back to concatenation', [
                            'field' => $field,
                            'frequency' => $value,
                            'report_id' => $report->id ?? 'unknown'
                        ]);
                    } else {
                        // Fail hard - throw exception when decision rule not found
                        $this->logDecisionRuleError('Decision rule not found and fallback disabled', [
                            'field' => $field,
                            'frequency' => $value,
                            'report_id' => $report->id ?? 'unknown'
                        ]);
                        
                        throw new \Exception("Decision rule not found for field '{$field}' with frequency '{$value}' and fallback is disabled");
                    }
                }
                
                // Apply confidence and dagger symbols
                $itemSuffix = $hasConfidence ? ' *' : '';
                if (isset($fieldsThatNeedDagger[$field])) {
                    $itemSuffix .= ' †';
                }
                
                $sentence .= $itemSuffix;
                
                // Categorize using existing logic
                $category = $this->crossLoadedService->categorizeFieldValue($field, $value);
                
                $results[$category][] = $sentence;
            }
            
            return $results;
        } catch (\Exception $e) {
            $this->logDecisionRuleError('Error processing domain items', [
                'domain' => $domain,
                'report_id' => $report->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            // Check if fallback is enabled
            if (config('essy.decision_rules_fallback', true)) {
                // Fallback to original CrossLoadedDomainService implementation
                return $this->crossLoadedService->processDomainItems($report, $domain, $concernDomains);
            } else {
                // Re-throw the exception when fallback is disabled
                throw $e;
            }
        }
    }

    /**
     * Get value for cross-loaded item from other fields in the same group
     *
     * @param ReportData $report
     * @param string $field
     * @return string|null
     */
    private function getCrossLoadedValue(ReportData $report, string $field): ?string
    {
        try {
            $crossLoadedItemGroups = $this->crossLoadedService->getCrossLoadedItemGroups();
            
            foreach ($crossLoadedItemGroups as $group) {
                if (in_array($field, $group)) {
                    // Try to get value from other fields in the same group
                    foreach ($group as $groupField) {
                        if ($groupField !== $field) {
                            $value = $this->crossLoadedService->safeGetFieldValue($report, $groupField);
                            if ($value) {
                                return $value;
                            }
                        }
                    }
                }
            }
            return null;
        } catch (\Exception $e) {
            $this->logDecisionRuleError('Error getting cross-loaded value', [
                'field' => $field,
                'report_id' => $report->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Safely get field value from report with error handling
     *
     * @param ReportData $report
     * @param string $field
     * @return string|null
     */
    public function safeGetFieldValue(ReportData $report, string $field): ?string
    {
        return $this->crossLoadedService->safeGetFieldValue($report, $field);
    }

    /**
     * Log decision rule errors with context
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    private function logDecisionRuleError(string $message, array $context = []): void
    {
        $this->logger->warning('[DecisionRules] ' . $message, array_merge($context, [
            'timestamp' => now()->toISOString(),
            'service' => 'DecisionRulesService'
        ]));
    }
}