<?php

namespace App\Services;

use App\Models\DecisionRule;
use App\Models\ReportData;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Service for handling decision rules lookup functionality.
 * 
 * This service provides contextually appropriate text based on decision rules.
 * 
 * Configuration:
 * - config('essy.use_decision_rules'): Enable/disable decision rules lookup
 * 
 * @see config/essy.php for configuration options
 * @see docs/CONFIGURATION.md for detailed configuration guide
 */
class DecisionRulesService
{
    private CrossLoadedDomainService $crossLoadedService;
    private LoggerInterface $logger;
    private ?string $uploadedExcelPath = null;
    private array $cachedDecisionRules = [];
    protected array $decisionRules = [];

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
            // First check if this is an Essential Item
            $essentialItemText = $this->getEssentialItemText($itemCode, $frequency);
            if ($essentialItemText) {
                return $essentialItemText;
            }
            
            // Get from database (decision rules are now stored during upload)
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
            // Log the start of domain processing
            $this->logDomainProcessingStart($report, $domain, $concernDomains);
            
            $fieldMessages = $this->crossLoadedService->getFieldMessages();
            $fieldsThatNeedDagger = $this->crossLoadedService->getFieldsRequiringDagger($concernDomains);
            $fieldToDomainMap = $this->crossLoadedService->getFieldToDomainMap();
            
            $results = ['strengths' => [], 'monitor' => [], 'concerns' => [], 'errored' => [], 'checkboxError' => []];
            $processedCrossLoadedGroups = []; // Track which cross-loaded groups we've already processed
            
            foreach ($fieldToDomainMap as $field => $fieldDomain) {
                if ($fieldDomain !== $domain) continue;
                if (!isset($fieldMessages[$field])) continue;
                
                // Check if this field is part of a cross-loaded group that we've already processed
                $crossLoadedGroups = $this->crossLoadedService->getCrossLoadedItemGroups();
                $skipField = false;
                foreach ($crossLoadedGroups as $groupIndex => $group) {
                    if (in_array($field, $group)) {
                        if (isset($processedCrossLoadedGroups[$groupIndex])) {
                            $skipField = true;
                            break;
                        }
                        $processedCrossLoadedGroups[$groupIndex] = $field;
                        break;
                    }
                }
                
                if ($skipField) continue;
                
                $valueRaw = $this->crossLoadedService->safeGetFieldValue($report, $field);
                
                // Log field value extraction
                $this->logFieldValueExtraction($field, $valueRaw, 'initial');
                
                // For cross-loaded items, if this field is empty, try to get value from other fields in the group
                if (!$valueRaw) {
                    // Use the CrossLoadedDomainService method to get cross-loaded values
                    $crossLoadedValue = null;
                    $crossLoadedGroups = $this->crossLoadedService->getCrossLoadedItemGroups();
                    foreach ($crossLoadedGroups as $group) {
                        if (in_array($field, $group)) {
                            foreach ($group as $groupField) {
                                if ($groupField !== $field) {
                                    $crossLoadedValue = $this->crossLoadedService->safeGetFieldValue($report, $groupField);
                                    if ($crossLoadedValue !== null) {
                                        break 2; // Break out of both loops
                                    }
                                }
                            }
                        }
                    }
                    $valueRaw = $crossLoadedValue;
                    $this->logFieldValueExtraction($field, $valueRaw, 'cross_loaded');
                }
                
                // If still no value, skip this item (don't show items without frequency responses)
                if (!$valueRaw) {
                    $this->logItemSkipped($field, 'no_value');
                    continue;
                }
                
                $hasConfidence = str_contains($valueRaw, ',');
                $value = trim(explode(',', $valueRaw)[0]);
                
                // Log value parsing
                $this->logValueParsing($field, $valueRaw, $value, $hasConfidence);
                
                // List of valid frequency values
                $validFrequencies = [
                    'Almost Always',
                    'Frequently',
                    'Sometimes',
                    'Occasionally',
                    'Almost Never'
                ];
                
                // Ensure we have a valid frequency response
                if (empty($value) || $value === '-99' || !in_array($value, $validFrequencies)) {
                    $this->logItemSkipped($field, 'invalid_frequency', ['value' => $value]);
                    if (str_contains($value, 'Check box')) {
                    $results['checkboxError'][] = $fieldMessages[$field] . ' (' . $field . ')';
                    } else {        
                        $results['errored'][] = $fieldMessages[$field] . ' (' . $field . ')';
                    }
                    continue;
                }
                
                // Try to get decision rule text first
                // Use the field message text as the item code for lookup
                $fieldMessage = $fieldMessages[$field] ?? '';
                
                // Generate comprehensive item code variations to handle new field name patterns
                $itemCodeVariations = $this->generateItemCodeVariations($field, $fieldMessage);
                
                $decisionText = null;
                $matchedVariation = null;
                foreach ($itemCodeVariations as $itemCodeForLookup) {
                    $decisionText = $this->getDecisionText($itemCodeForLookup, $value);
                    if ($decisionText) {
                        $matchedVariation = $itemCodeForLookup;
                        break; // Found a match, stop trying
                    }
                }
                
                // Log decision rule lookup attempt for debugging
                $this->logDecisionRuleLookup($field, $value, $itemCodeVariations, $matchedVariation, $decisionText !== null);
                
                if ($decisionText) {
                    // Use decision rule text
                    $sentence = $decisionText;
                    
                    // Log successful decision rule usage
                    $this->logDecisionRuleSuccess($field, $value, $matchedVariation, $decisionText);
                } else {
                    // Decision rule not found - skip this item but continue processing others
                    $this->logDecisionRuleError('Decision rule not found - skipping item', [
                        'field' => $field,
                        'frequency' => $value,
                        'variations_tried' => $itemCodeVariations,
                        'report_id' => $report->id ?? 'unknown'
                    ]);
                    // Skip this item and continue with the next one
                    continue;
                }
                
                // Apply confidence and dagger symbols
                $itemSuffix = $hasConfidence ? ' *' : '';
                if (isset($fieldsThatNeedDagger[$field])) {
                    $itemSuffix .= ' †';
                }
                
                $sentence .= $itemSuffix;
                
                // Categorize using existing logic
                $category = $this->crossLoadedService->categorizeFieldValue($field, $value);
                
                // Log item categorization and final placement
                $this->logItemCategorization($field, $value, $category, $sentence, $decisionText !== null);
                
                $results[$category][] = $sentence;
            }
            
            // Log the completion of domain processing
            $this->logDomainProcessingComplete($report, $domain, $results);
            
            return $results;
        } catch (\Exception $e) {
            // Log the error but continue processing - return partial results
            $this->logDecisionRuleError('Error processing domain items - returning partial results', [
                'domain' => $domain,
                'report_id' => $report->id ?? 'unknown',
                'error' => $e->getMessage(),
                'items_processed' => count($results['strengths']) + count($results['monitor']) + count($results['concerns'])
            ]);
            
            // Return whatever results we have so far instead of failing completely
            return $results;
        }
    }





    /**
     * Generate comprehensive item code variations to handle new field name patterns
     * This method creates multiple variations of the field message to match against decision rules
     *
     * @param string $field
     * @param string $fieldMessage
     * @return array
     */
    private function generateItemCodeVariations(string $field, string $fieldMessage): array
    {
        $variations = [];
        
        // First try the field name itself (this is how decision rules are stored)
        $variations[] = $field;
        
        // Basic variations
        $variations[] = $fieldMessage; // Original case
        $variations[] = ucfirst($fieldMessage); // Capitalize first letter
        $variations[] = ucfirst(strtolower($fieldMessage)); // Proper case
        
        // Handle specific field patterns based on updated field names
        
        // Handle directions fields (A_B_DIRECTIONS_CL1/CL2)
        if (str_contains($field, 'DIRECTIONS')) {
            $variations[] = 'Understands directions.';
            $variations[] = 'understands directions.';
        }
        
        // Handle aggression fields (B_VERBAGGRESS, B_PHYSAGGRESS)
        if (str_contains($field, 'VERBAGGRESS')) {
            $variations[] = 'Engages in verbally aggressive behavior toward others.';
            $variations[] = 'engages in verbally aggressive behavior toward others.';
        }
        if (str_contains($field, 'PHYSAGGRESS')) {
            $variations[] = 'Engages in physically aggressive behavior toward others.';
            $variations[] = 'engages in physically aggressive behavior toward others.';
        }
        
        // Handle health fields (P_ORAL, P_PHYS)
        if (str_contains($field, 'P_ORAL')) {
            $variations[] = 'Oral health appears to be addressed.';
            $variations[] = 'oral health appears to be addressed.';
        }
        if (str_contains($field, 'P_PHYS')) {
            $variations[] = 'Physical health appears to be addressed.';
            $variations[] = 'physical health appears to be addressed.';
        }
        
        // Handle hygiene fields (O_P_HYGIENE_CL1/CL2)
        if (str_contains($field, 'HYGIENE')) {
            $variations[] = 'Appears to have the resources to address basic hygiene needs.';
            $variations[] = 'appears to have the resources to address basic hygiene needs.';
        }
        
        // Handle articulate fields (A_P_S_ARTICULATE_CL1/CL2/CL3)
        if (str_contains($field, 'ARTICULATE')) {
            $variations[] = 'Articulates clearly enough to be understood.';
            $variations[] = 'articulates clearly enough to be understood.';
        }
        
        // Handle community connection fields (S_O_COMMCONN_CL1/CL2)
        if (str_contains($field, 'COMMCONN')) {
            $variations[] = 'Appears to experience a sense of connection in their community.';
            $variations[] = 'appears to experience a sense of connection in their community.';
        }
        
        // Handle extracurricular activity fields (A_S_O_ACTIVITY_CL1/CL2/CL3)
        if (str_contains($field, 'ACTIVITY')) {
            $variations[] = 'Engaged in at least one extracurricular activity.';
            $variations[] = 'engaged in at least one extracurricular activity.';
        }
        
        // Handle communication fields pattern
        if (preg_match('/^communicates with (.+) effectively\.$/', $fieldMessage, $matches)) {
            $variations[] = 'Effectively communicates with ' . $matches[1] . '.';
            $variations[] = 'effectively communicates with ' . $matches[1] . '.';
        }
        
        // Handle cross-loaded field variations - remove CL1/CL2/CL3 suffixes for matching
        if (preg_match('/(.+)_CL[123]$/', $field, $matches)) {
            $baseField = $matches[1];
            // Try to match with base field message patterns
            $baseMessage = $this->getBaseFieldMessage($baseField);
            if ($baseMessage && $baseMessage !== $fieldMessage) {
                $variations[] = $baseMessage;
                $variations[] = ucfirst($baseMessage);
                $variations[] = ucfirst(strtolower($baseMessage));
            }
        }
        
        // Remove duplicates and empty values
        $variations = array_unique(array_filter($variations));
        
        return $variations;
    }
    
    /**
     * Get base field message for cross-loaded fields
     *
     * @param string $baseField
     * @return string|null
     */
    private function getBaseFieldMessage(string $baseField): ?string
    {
        // Map base field patterns to their expected messages
        $baseFieldMessages = [
            'A_P_S_ARTICULATE' => 'articulates clearly enough to be understood.',
            'A_S_ADULTCOMM' => 'communicates with adults effectively.',
            'A_B_CLASSEXPECT' => 'follows classroom expectations.',
            'A_B_IMPULSE' => 'exhibits impulsivity.',
            'A_S_CONFIDENT' => 'displays confidence in self.',
            'A_S_POSOUT' => 'demonstrates positive outlook.',
            'A_B_DIRECTIONS' => 'understands directions.',
            'S_P_ACHES' => 'complains of headaches, stomachaches, or body aches.',
            'B_O_HOUSING' => 'reports not having a stable living situation.',
            'B_O_FAMSTRESS' => 'family is experiencing significant stressors.',
            'B_O_NBHDSTRESS' => 'neighborhood is experiencing significant stressors.',
            'O_P_HUNGER' => 'reports being hungry.',
            'O_P_HYGIENE' => 'appears to have the resources to address basic hygiene needs.',
            'O_P_CLOTHES' => 'shows up to school with adequate clothing.',
            'S_O_COMMCONN' => 'appears to experience a sense of connection in their community.',
            'A_S_O_ACTIVITY' => 'engaged in at least one extracurricular activity.'
        ];
        
        return $baseFieldMessages[$baseField] ?? null;
    }
    
    /**
     * Log decision rule lookup attempts with detailed information
     *
     * @param string $field
     * @param string $frequency
     * @param array $variations
     * @param string|null $matchedVariation
     * @param bool $found
     * @return void
     */
    private function logDecisionRuleLookup(string $field, string $frequency, array $variations, ?string $matchedVariation, bool $found): void
    {
        if (config('essy.log_decision_rule_lookups', false)) {
            $this->logger->info('[DecisionRules] Decision rule lookup attempt', [
                'field' => $field,
                'frequency' => $frequency,
                'variations_tried' => $variations,
                'matched_variation' => $matchedVariation,
                'found' => $found,
                'total_variations' => count($variations),
                'timestamp' => now()->toISOString(),
                'service' => 'DecisionRulesService'
            ]);
        }
    }
    
    /**
     * Log successful decision rule usage
     *
     * @param string $field
     * @param string $frequency
     * @param string $matchedVariation
     * @param string $decisionText
     * @return void
     */
    private function logDecisionRuleSuccess(string $field, string $frequency, string $matchedVariation, string $decisionText): void
    {
        if (config('essy.log_decision_rule_lookups', false)) {
            $this->logger->info('[DecisionRules] Decision rule found and used', [
                'field' => $field,
                'frequency' => $frequency,
                'matched_variation' => $matchedVariation,
                'decision_text_preview' => substr($decisionText, 0, 50) . '...',
                'timestamp' => now()->toISOString(),
                'service' => 'DecisionRulesService'
            ]);
        }
    }
    

    

    

    
    /**
     * Log the start of domain processing
     *
     * @param ReportData $report
     * @param string $domain
     * @param array $concernDomains
     * @return void
     */
    private function logDomainProcessingStart(ReportData $report, string $domain, array $concernDomains): void
    {
        if (config('essy.log_decision_rule_lookups', false)) {
            $this->logger->info('[DecisionRules] Starting domain processing', [
                'report_id' => $report->id ?? 'unknown',
                'domain' => $domain,
                'concern_domains' => $concernDomains,
                'decision_rules_enabled' => config('essy.use_decision_rules', false),

                'timestamp' => now()->toISOString(),
                'service' => 'DecisionRulesService'
            ]);
        }
    }
    
    /**
     * Log the completion of domain processing
     *
     * @param ReportData $report
     * @param string $domain
     * @param array $results
     * @return void
     */
    private function logDomainProcessingComplete(ReportData $report, string $domain, array $results): void
    {
        if (config('essy.log_decision_rule_lookups', false)) {
            $this->logger->info('[DecisionRules] Domain processing complete', [
                'report_id' => $report->id ?? 'unknown',
                'domain' => $domain,
                'strengths_count' => count($results['strengths']),
                'monitor_count' => count($results['monitor']),
                'concerns_count' => count($results['concerns']),
                'total_items' => count($results['strengths']) + count($results['monitor']) + count($results['concerns']),
                'timestamp' => now()->toISOString(),
                'service' => 'DecisionRulesService'
            ]);
        }
    }
    
    /**
     * Log field value extraction
     *
     * @param string $field
     * @param string|null $value
     * @param string $source
     * @return void
     */
    private function logFieldValueExtraction(string $field, ?string $value, string $source): void
    {
        if (config('essy.debug_item_processing', false)) {
            $this->logger->debug('[DecisionRules] Field value extraction', [
                'field' => $field,
                'value' => $value,
                'source' => $source,
                'has_value' => $value !== null,
                'timestamp' => now()->toISOString(),
                'service' => 'DecisionRulesService'
            ]);
        }
    }
    
    /**
     * Log value parsing details
     *
     * @param string $field
     * @param string $rawValue
     * @param string $parsedValue
     * @param bool $hasConfidence
     * @return void
     */
    private function logValueParsing(string $field, string $rawValue, string $parsedValue, bool $hasConfidence): void
    {
        if (config('essy.debug_item_processing', false)) {
            $this->logger->debug('[DecisionRules] Value parsing', [
                'field' => $field,
                'raw_value' => $rawValue,
                'parsed_value' => $parsedValue,
                'has_confidence' => $hasConfidence,
                'timestamp' => now()->toISOString(),
                'service' => 'DecisionRulesService'
            ]);
        }
    }
    
    /**
     * Log when an item is skipped
     *
     * @param string $field
     * @param string $reason
     * @param array $context
     * @return void
     */
    private function logItemSkipped(string $field, string $reason, array $context = []): void
    {
        if (config('essy.debug_item_processing', false)) {
            $this->logger->debug('[DecisionRules] Item skipped', array_merge([
                'field' => $field,
                'reason' => $reason,
                'timestamp' => now()->toISOString(),
                'service' => 'DecisionRulesService'
            ], $context));
        }
    }
    
    /**
     * Log item categorization and final placement
     *
     * @param string $field
     * @param string $frequency
     * @param string $category
     * @param string $sentence
     * @param bool $usedDecisionRule
     * @return void
     */
    private function logItemCategorization(string $field, string $frequency, string $category, string $sentence, bool $usedDecisionRule): void
    {
        if (config('essy.debug_item_processing', false)) {
            $this->logger->debug('[DecisionRules] Item categorized', [
                'field' => $field,
                'frequency' => $frequency,
                'category' => $category,
                'sentence_preview' => substr($sentence, 0, 50) . '...',
                'used_decision_rule' => $usedDecisionRule,
                'timestamp' => now()->toISOString(),
                'service' => 'DecisionRulesService'
            ]);
        }
    }
    
    /**
     * Get Essential Item text for Proceed/Caution table items
     * These items have different text patterns than regular decision rules
     */
    private function getEssentialItemText(string $field, string $frequency): ?string
    {
        // Essential Items mapping based on Excel Essential Items table (rows 31-37)
        // Bold formatting added to match the original design
        $essentialItems = [
            'E_SHARM' => [
                'Almost Always' => 'Almost always engages in <strong>self-harming behaviors</strong>.',
                'Frequently' => 'Frequently engages in <strong>self-harming behaviors</strong>.',
                'Sometimes' => 'Sometimes engages in <strong>self-harming behaviors</strong>.',
                'Occasionally' => 'Occasionally engages in <strong>self-harming behaviors</strong>.',
                'Almost Never' => 'Almost never engages in <strong>self-harming behaviors</strong>.'
            ],
            'E_BULLIED' => [
                'Almost Always' => 'Has almost always been <strong>bullied</strong> by other students.',
                'Frequently' => 'Has frequently been <strong>bullied</strong> by other students.',
                'Sometimes' => 'Has sometimes been <strong>bullied</strong> by other students.',
                'Occasionally' => 'Has occasionally been <strong>bullied</strong> by other students.',
                'Almost Never' => 'Has almost never been <strong>bullied</strong> by other students.'
            ],
            'E_EXCLUDE' => [
                'Almost Always' => 'Almost always experiences <strong>social exclusion</strong> in school.',
                'Frequently' => 'Frequently experiences <strong>social exclusion</strong> in school.',
                'Sometimes' => 'Sometimes experiences <strong>social exclusion</strong> in school.',
                'Occasionally' => 'Occasionally experiences <strong>social exclusion</strong> in school.',
                'Almost Never' => 'Almost never experiences <strong>social exclusion</strong> in school.'
            ],
            'E_WITHDRAW' => [
                'Almost Always' => 'Almost always <strong>avoids or withdraws</strong> from peers.',
                'Frequently' => 'Frequently <strong>avoids or withdraws</strong> from peers.',
                'Sometimes' => 'Sometimes <strong>avoids or withdraws</strong> from peers.',
                'Occasionally' => 'Occasionally <strong>avoids or withdraws</strong> from peers.',
                'Almost Never' => 'Almost never <strong>avoids or withdraws</strong> from peers.'
            ],
            'E_REGULATE' => [
                'Almost Always' => 'Almost always <strong>regulates emotions</strong>.',
                'Frequently' => 'Frequently <strong>regulates emotions</strong>.',
                'Sometimes' => 'Sometimes <strong>regulates emotions</strong>.',
                'Occasionally' => 'Occasionally <strong>regulates emotions</strong>.',
                'Almost Never' => 'Almost never <strong>regulates emotions</strong>.'
            ],
            'E_RESTED' => [
                'Almost Always' => 'Almost always appears <strong>well-rested</strong>.',
                'Frequently' => 'Frequently appears <strong>well-rested</strong>.',
                'Sometimes' => 'Sometimes appears <strong>well-rested</strong>.',
                'Occasionally' => 'Occasionally appears <strong>well-rested</strong>.',
                'Almost Never' => 'Almost never appears <strong>well-rested</strong>.'
            ]
        ];
        
        return $essentialItems[$field][$frequency] ?? null;
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