<?php

namespace App\Services;

use App\Models\ReportData;
use App\ValueObjects\ValidationResult;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class CrossLoadedDomainService
{
    private array $crossLoadedItemGroups;
    private array $fieldToDomainMap;
    private LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? Log::channel('default');
        $this->initializeCrossLoadedConfiguration();
        $this->buildFieldToDomainMap();
    }

    /**
     * Get cross-loaded item groups configuration
     */
    public function getCrossLoadedItemGroups(): array
    {
        return $this->crossLoadedItemGroups;
    }

    /**
     * Get field to domain mapping
     */
    public function getFieldToDomainMap(): array
    {
        return $this->fieldToDomainMap;
    }

    /**
     * Get fields that require dagger symbols based on concern domains
     */
    public function getFieldsRequiringDagger(array $concernDomains): array
    {
        try {
            $fieldsThatNeedDagger = [];
            
            foreach ($this->crossLoadedItemGroups as $group) {
                // Get the domains represented in this cross-loaded group
                $domainsInGroup = [];
                $fieldsInConcernDomains = [];
                
                foreach ($group as $field) {
                    if (isset($this->fieldToDomainMap[$field])) {
                        $fieldDomain = $this->fieldToDomainMap[$field];
                        $domainsInGroup[$fieldDomain] = true;
                        
                        // Track which fields are in concern domains
                        if (in_array($fieldDomain, $concernDomains)) {
                            $fieldsInConcernDomains[] = $field;
                        }
                    }
                }
                
                // Only apply daggers if:
                // 1. Multiple domains in this group are concerns, AND
                // 2. We have fields in those concern domains
                $concernDomainsInGroup = array_intersect(array_keys($domainsInGroup), $concernDomains);
                
                if (count($concernDomainsInGroup) > 1 && !empty($fieldsInConcernDomains)) {
                    // Only mark fields that are actually in concern domains
                    foreach ($fieldsInConcernDomains as $field) {
                        $fieldsThatNeedDagger[$field] = true;
                    }
                }
            }
            
            return $fieldsThatNeedDagger;
        } catch (\Exception $e) {
            $this->logCrossLoadedError('Failed to determine fields requiring dagger', [
                'concern_domains' => $concernDomains,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Safely get field value from report with error handling
     */
    public function safeGetFieldValue(ReportData $report, string $field): ?string
    {
        try {
            if (!$this->isValidField($field)) {
                $this->logCrossLoadedError('Invalid field name accessed', [
                    'field' => $field,
                    'report_id' => $report->id ?? 'unknown'
                ]);
                return null;
            }

            $value = $report->getAttribute($field);
            
            // Handle various null/empty cases
            if ($value === null || $value === '' || trim($value) === '-99') {
                return null;
            }
            
            return trim($value);
        } catch (\Exception $e) {
            $this->logCrossLoadedError('Error accessing field value', [
                'field' => $field,
                'report_id' => $report->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Validate cross-loaded configuration integrity
     */
    public function validateCrossLoadedConfiguration(): ValidationResult
    {
        $errors = [];
        $warnings = [];
        
        try {
            // Validate that all fields in cross-loaded groups exist in ReportData model
            $modelFields = (new ReportData())->getFillable();
            
            foreach ($this->crossLoadedItemGroups as $groupIndex => $group) {
                foreach ($group as $field) {
                    if (!in_array($field, $modelFields)) {
                        $errors[] = "Field '{$field}' in group '{$groupIndex}' does not exist in ReportData model";
                    }
                }
                
                // Warn if group has only one field (not really cross-loaded)
                if (count($group) < 2) {
                    $warnings[] = "Group '{$groupIndex}' has only one field - not truly cross-loaded";
                }
            }
            
            // Validate field-to-domain mapping completeness
            foreach ($this->fieldToDomainMap as $field => $domain) {
                if (!in_array($field, $modelFields)) {
                    $errors[] = "Field '{$field}' in domain mapping does not exist in ReportData model";
                }
            }
            
        } catch (\Exception $e) {
            $errors[] = "Configuration validation failed: " . $e->getMessage();
        }
        
        return new ValidationResult(empty($errors), $errors, $warnings);
    }

    /**
     * Validate database fields against model
     */
    public function validateDatabaseFields(array $modelFields): ValidationResult
    {
        $errors = [];
        $warnings = [];
        
        try {
            // Check cross-loaded item groups
            foreach ($this->crossLoadedItemGroups as $groupIndex => $group) {
                foreach ($group as $field) {
                    if (!in_array($field, $modelFields)) {
                        $errors[] = "Cross-loaded field '{$field}' not found in model fields";
                    }
                }
            }
            
            // Check field-to-domain mapping
            foreach ($this->fieldToDomainMap as $field => $domain) {
                if (!in_array($field, $modelFields)) {
                    $errors[] = "Mapped field '{$field}' not found in model fields";
                }
            }
            
            // Check for potential typos in field names
            $this->checkForFieldNameTypos($modelFields, $warnings);
            
        } catch (\Exception $e) {
            $errors[] = "Database field validation failed: " . $e->getMessage();
        }
        
        return new ValidationResult(empty($errors), $errors, $warnings);
    }

    /**
     * Check for potential field name typos
     */
    private function checkForFieldNameTypos(array $modelFields, array &$warnings): void
    {
        // Known field name variations that might indicate typos
        $knownVariations = [
            // Add any known field name variations here
        ];
        
        foreach ($knownVariations as $field1 => $field2) {
            $field1Exists = in_array($field1, $modelFields);
            $field2Exists = in_array($field2, $modelFields);
            
            if ($field1Exists && $field2Exists) {
                $warnings[] = "Potential field name inconsistency: '{$field1}' vs '{$field2}'";
            }
        }
    }

    /**
     * Log cross-loaded domain errors with context
     */
    public function logCrossLoadedError(string $message, array $context = []): void
    {
        $this->logger->error('[CrossLoadedDomain] ' . $message, array_merge($context, [
            'timestamp' => now()->toISOString(),
            'service' => 'CrossLoadedDomainService'
        ]));
    }

    /**
     * Initialize cross-loaded item groups configuration
     */
    private function initializeCrossLoadedConfiguration(): void
    {
        $this->crossLoadedItemGroups = [
            ['A_P_ARTICULATE_CL1', 'A_P_ARTICULATE_CL2'], // Articulates clearly
            ['A_S_ADULTCOMM_CL1', 'A_S_ADULTCOMM_CL2'],   // Effectively communicates with adults
            ['A_B_CLASSEXPECT_CL1', 'A_B_CLASSEXPECT_CL2'], // Follows classroom expectations
            ['A_B_IMPULSE_CL1', 'A_B_IMPULSE_CL2'],       // Exhibits impulsivity
            ['A_S_CONFIDENT_CL1', 'A_S_CONFIDENT_CL2'],   // Displays confidence in self
            ['A_S_POSOUT_CL1', 'A_S_POSOUT_CL2'],         // Demonstrates positive outlook
            ['S_P_ACHES_CL1', 'S_P_ACHES_CL2'],           // Complains of aches
            ['B_O_HOUSING_CL1', 'B_O_HOUSING_CL2'],       // Unstable living situation
            ['B_O_FAMSTRESS_CL1', 'B_O_FAMSTRESS_CL2'],   // Family stressors
            ['B_O_NBHDSTRESS_CL1', 'B_O_NBHDSTRESS_CL2'], // Neighborhood stressors
            ['O_P_HUNGER_CL1', 'O_P_HUNGER_CL2'],         // Reports being hungry
            ['O_P_HYGEINE_CL1', 'O_P_HYGIENE_CL2'],       // Hygiene resources
            ['O_P_CLOTHES_CL1', 'O_P_CLOTHES_CL2'],       // Adequate clothing
            ['A_S_O_ACTIVITY3_CL1', 'A_S_O_ACTIVITY_CL2', 'A_S_O_ACTIVITY_CL3'] // Extracurricular activity
        ];
    }

    /**
     * Build field to domain mapping
     */
    private function buildFieldToDomainMap(): void
    {
        $this->fieldToDomainMap = [
            // Academic Skills
            'A_READ' => 'Academic Skills',
            'A_WRITE' => 'Academic Skills',
            'A_MATH' => 'Academic Skills',
            'A_P_ARTICULATE_CL1' => 'Academic Skills',
            'A_S_ADULTCOMM_CL1' => 'Academic Skills',
            'A_DIRECTIONS' => 'Academic Skills',
            'A_INITIATE' => 'Academic Skills',
            'A_PLANORG' => 'Academic Skills',
            'A_TURNIN' => 'Academic Skills',
            'A_B_CLASSEXPECT_CL1' => 'Academic Skills',
            'A_B_IMPULSE_CL1' => 'Academic Skills',
            'A_ENGAGE' => 'Academic Skills',
            'A_INTEREST' => 'Academic Skills',
            'A_PERSIST' => 'Academic Skills',
            'A_GROWTH' => 'Academic Skills',
            'A_S_CONFIDENT_CL1' => 'Academic Skills',
            'A_S_POSOUT_CL1' => 'Academic Skills',
            'A_S_O_ACTIVITY3_CL1' => 'Academic Skills',

            // Behavior
            'A_B_CLASSEXPECT_CL2' => 'Behavior',
            'A_B_IMPULSE_CL2' => 'Behavior',
            'B_CLINGY' => 'Behavior',
            'B_SNEAK' => 'Behavior',
            'BEH_VERBAGGRESS' => 'Behavior',
            'BEH_PHYSAGGRESS' => 'Behavior',
            'B_DESTRUCT' => 'Behavior',
            'B_BULLY' => 'Behavior',
            'B_PUNITIVE' => 'Behavior',
            'B_O_HOUSING_CL1' => 'Behavior',
            'B_O_FAMSTRESS_CL1' => 'Behavior',
            'B_O_NBHDSTRESS_CL1' => 'Behavior',

            // Physical Health
            'P_SIGHT' => 'Physical Health',
            'P_HEAR' => 'Physical Health',
            'A_P_ARTICULATE_CL2' => 'Physical Health',
            'A_ORAL' => 'Physical Health',
            'A_PHYS' => 'Physical Health',
            'P_PARTICIPATE' => 'Physical Health',
            'S_P_ACHES_CL1' => 'Physical Health',
            'O_P_HUNGER_CL1' => 'Physical Health',
            'O_P_HYGIENE_CL1' => 'Physical Health',
            'O_P_CLOTHES_CL1' => 'Physical Health',

            // Social & Emotional Well-Being
            'S_CONTENT' => 'Social & Emotional Well-Being',
            'A_S_CONFIDENT_CL2' => 'Social & Emotional Well-Being',
            'A_S_POSOUT_CL2' => 'Social & Emotional Well-Being',
            'S_P_ACHES_CL2' => 'Social & Emotional Well-Being',
            'S_NERVOUS' => 'Social & Emotional Well-Being',
            'S_SAD' => 'Social & Emotional Well-Being',
            'S_SOCIALCONN' => 'Social & Emotional Well-Being',
            'S_FRIEND' => 'Social & Emotional Well-Being',
            'S_PROSOCIAL' => 'Social & Emotional Well-Being',
            'S_PEERCOMM' => 'Social & Emotional Well-Being',
            'A_S_ADULTCOMM_CL2' => 'Social & Emotional Well-Being',
            'S_POSADULT' => 'Social & Emotional Well-Being',
            'S_SCHOOLCONN' => 'Social & Emotional Well-Being',
            'S_COMMCONN' => 'Social & Emotional Well-Being',
            'A_S_O_ACTIVITY_CL2' => 'Social & Emotional Well-Being',

            // Supports Outside of School
            'O_RECIPROCAL' => 'Supports Outside of School',
            'O_POSADULT' => 'Supports Outside of School',
            'O_ADULTBEST' => 'Supports Outside of School',
            'O_TALK' => 'Supports Outside of School',
            'O_ROUTINE' => 'Supports Outside of School',
            'O_FAMILY' => 'Supports Outside of School',
            'O_P_HUNGER_CL2' => 'Supports Outside of School',
            'O_P_HYGIENE_CL2' => 'Supports Outside of School',
            'O_P_CLOTHES_CL2' => 'Supports Outside of School',
            'O_RESOURCE' => 'Supports Outside of School',
            'B_O_HOUSING_CL2' => 'Supports Outside of School',
            'B_O_FAMSTRESS_CL2' => 'Supports Outside of School',
            'B_O_NBHDSTRESS_CL2' => 'Supports Outside of School',
            'A_S_O_ACTIVITY_CL3' => 'Supports Outside of School',
        ];
    }

    /**
     * Check if field name is valid
     */
    private function isValidField(string $field): bool
    {
        $modelFields = (new ReportData())->getFillable();
        return in_array($field, $modelFields);
    }

    /**
     * Get field messages for display - Updated to match exact specification wording
     */
    public function getFieldMessages(): array
    {
        return [
            // Academic Skills - Exact wording from specification
            'A_READ' => 'meets grade-level expectations for reading skills.',
            'A_WRITE' => 'meets expectations for grade-level writing skills.',
            'A_MATH' => 'meets expectations for grade-level math skills.',
            'A_P_ARTICULATE_CL1' => 'articulates clearly enough to be understood.',
            'A_S_ADULTCOMM_CL1' => 'communicates with adults effectively.',
            'A_DIRECTIONS' => 'understands directions.',
            'A_INITIATE' => 'initiates academic tasks.',
            'A_PLANORG' => 'demonstrates ability to plan, organize, focus, and prioritize tasks.',
            'A_TURNIN' => 'completes and turns in assigned work.',
            'A_B_CLASSEXPECT_CL1' => 'follows classroom expectations.',
            'A_B_IMPULSE_CL1' => 'exhibits impulsivity.',
            'A_ENGAGE' => 'engaged in academic activities.',
            'A_INTEREST' => 'shows interest in learning activities.',
            'A_PERSIST' => 'persists with challenging tasks.',
            'A_GROWTH' => 'demonstrates a growth mindset.',
            'A_S_CONFIDENT_CL1' => 'displays confidence in self.',
            'A_S_POSOUT_CL1' => 'demonstrates positive outlook.',
            'A_S_O_ACTIVITY3_CL1' => 'engaged in at least one extracurricular activity.',
            
            // Behavior - Exact wording from specification
            'A_B_CLASSEXPECT_CL2' => 'follows classroom expectations.',
            'A_B_IMPULSE_CL2' => 'exhibits impulsivity.',
            'B_CLINGY' => 'exhibits overly clingy or attention-seeking behaviors.',
            'B_SNEAK' => 'demonstrates sneaky or dishonest behavior.',
            'BEH_VERBAGGRESS' => 'engages in verbally aggressive behavior toward others.',
            'BEH_PHYSAGGRESS' => 'engages in physically aggressive behavior toward others.',
            'B_DESTRUCT' => 'engages in destructive behavior towards property.',
            'B_BULLY' => 'bullies/has bullied another student.',
            'B_PUNITIVE' => 'experiences/has experienced punitive or exclusionary discipline at school.',
            'B_O_HOUSING_CL1' => 'reports not having a stable living situation.',
            'B_O_FAMSTRESS_CL1' => 'family is experiencing significant stressors.',
            'B_O_NBHDSTRESS_CL1' => 'neighborhood is experiencing significant stressors.',
            
            // Physical Health - Exact wording from specification
            'P_SIGHT' => 'able to see, from a distance or up close.',
            'P_HEAR' => 'able to hear information.',
            'A_P_ARTICULATE_CL2' => 'articulates clearly enough to be understood.',
            'A_ORAL' => 'oral health appears to be addressed.',
            'A_PHYS' => 'physical health appears to be addressed.',
            'P_PARTICIPATE' => 'physical health allows for participation in school activities.',
            'S_P_ACHES_CL1' => 'complains of headaches, stomachaches, or body aches.',
            'O_P_HUNGER_CL1' => 'reports being hungry.',
            'O_P_HYGEINE_CL1' => 'appears to have the resources to address basic hygiene needs.',
            'O_P_CLOTHES_CL1' => 'shows up to school with adequate clothing.',
            
            // Social & Emotional Well-Being - Exact wording from specification
            'S_CONTENT' => 'appears content.',
            'A_S_CONFIDENT_CL2' => 'displays confidence in self.',
            'A_S_POSOUT_CL2' => 'demonstrates positive outlook.',
            'S_P_ACHES_CL2' => 'complains of headaches, stomachaches, or body aches.',
            'S_NERVOUS' => 'appears nervous, worried, tense, or fearful.',
            'S_SAD' => 'appears sad.',
            'S_SOCIALCONN' => 'has friends/social connections.',
            'S_FRIEND' => 'has at least one close friend at school.',
            'S_PROSOCIAL' => 'demonstrates prosocial skills.',
            'S_PEERCOMM' => 'communicates with peers effectively.',
            'A_S_ADULTCOMM_CL2' => 'communicates with adults effectively.',
            'S_POSADULT' => 'has a positive relationship with at least one adult in the school.',
            'S_SCHOOLCONN' => 'appears to experience a sense of connection in their school.',
            'S_COMMCONN' => 'appears to experience a sense of connection in their community.',
            'A_S_O_ACTIVITY_CL2' => 'engaged in at least one extracurricular activity.',
            
            // Supports Outside of School - Exact wording from specification
            'O_RECIPROCAL' => 'family-school communication is reciprocal.',
            'O_POSADULT' => 'has a positive adult outside of school with whom they feel close.',
            'O_ADULTBEST' => 'reports having an adult outside of school who wants them to do their best.',
            'O_TALK' => 'reports having someone outside of school to talk to about their interests and problems.',
            'O_ROUTINE' => 'shares having a caregiver who helps them with daily routines.',
            'O_FAMILY' => 'reports getting along with family members.',
            'O_P_HUNGER_CL2' => 'reports being hungry.',
            'O_P_HYGIENE_CL2' => 'appears to have the resources to address basic hygiene needs.',
            'O_P_CLOTHES_CL2' => 'shows up to school with adequate clothing.',
            'O_RESOURCE' => 'reports having access to resources (materials, internet) to complete schoolwork.',
            'B_O_HOUSING_CL2' => 'reports not having a stable living situation.',
            'B_O_FAMSTRESS_CL2' => 'family is experiencing significant stressors.',
            'B_O_NBHDSTRESS_CL2' => 'neighborhood is experiencing significant stressors.',
            'A_S_O_ACTIVITY_CL3' => 'engaged in at least one extracurricular activity.'
        ];
    }

    /**
     * Categorize field value into strengths, monitor, or concerns
     */
    public function categorizeFieldValue(string $field, string $value): string
    {
        $value = strtolower(trim($value));
        
        // Zero-tolerance fields (any occurrence is concern)
        $zeroToleranceFields = ['BEH_PHYSAGGRESS', 'B_BULLY', 'B_PUNITIVE'];
        
        // Special cases with reversed interpretation (negative items - lower frequency is better)
        $reversedFields = [
            // Impulsivity items (negative behavior)
            'A_B_IMPULSE_CL1', 'A_B_IMPULSE_CL2', 
            // Behavioral problems (negative behaviors)
            'B_CLINGY', 'B_SNEAK', 'BEH_VERBAGGRESS', 'B_DESTRUCT', 
            // Housing and family stressors (negative situations)
            'B_O_HOUSING_CL1', 'B_O_HOUSING_CL2', 'B_O_FAMSTRESS_CL1', 'B_O_FAMSTRESS_CL2', 
            'B_O_NBHDSTRESS_CL1', 'B_O_NBHDSTRESS_CL2',
            // Physical and emotional complaints (negative symptoms)
            'S_P_ACHES_CL1', 'S_P_ACHES_CL2', 'S_NERVOUS', 'S_SAD', 
            // Hunger (negative condition)
            'O_P_HUNGER_CL1', 'O_P_HUNGER_CL2'
        ];
        
        // Handle zero-tolerance fields first
        if (in_array($field, $zeroToleranceFields)) {
            return $value === 'almost never' ? 'strengths' : 'concerns';
        }
        
        // Handle reversed interpretation fields (negative items)
        if (in_array($field, $reversedFields)) {
            // For negative items, lower frequency is better
            if (in_array($value, ['almost never', 'occasionally'])) {
                return 'strengths';
            } elseif ($value === 'sometimes') {
                return 'monitor';
            } else {
                return 'concerns';
            }
        } else {
            // Handle positive items (higher frequency is better)
            if (in_array($value, ['almost always', 'frequently'])) {
                return 'strengths';
            } elseif ($value === 'sometimes') {
                return 'monitor';
            } else {
                return 'concerns';
            }
        }
    }

    /**
     * Process domain items and categorize them into strengths, monitor, and concerns
     */
    public function processDomainItems(ReportData $report, string $domain, array $concernDomains): array
    {
        $fieldMessages = $this->getFieldMessages();
        $fieldsThatNeedDagger = $this->getFieldsRequiringDagger($concernDomains);
        
        $results = ['strengths' => [], 'monitor' => [], 'concerns' => []];
        
        foreach ($this->fieldToDomainMap as $field => $fieldDomain) {
            if ($fieldDomain !== $domain) continue;
            if (!isset($fieldMessages[$field])) continue;
            
            $valueRaw = $this->safeGetFieldValue($report, $field);
            
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
            
            $prefix = ucfirst(strtolower($value));
            
            $itemSuffix = $hasConfidence ? ' *' : '';
            if (isset($fieldsThatNeedDagger[$field])) {
                $itemSuffix .= ' †';
            }
            
            $sentence = "{$prefix} {$fieldMessages[$field]}{$itemSuffix}";
            $category = $this->categorizeFieldValue($field, $value);
            
            $results[$category][] = $sentence;
        }
        
        return $results;
    }

    /**
     * Get value for cross-loaded item from its primary field if secondary is empty
     */
    private function getCrossLoadedValue(ReportData $report, string $field): ?string
    {
        foreach ($this->crossLoadedItemGroups as $group) {
            if (in_array($field, $group)) {
                // Try to get value from the first (primary) field in the group
                $primaryField = $group[0];
                if ($primaryField !== $field) {
                    return $this->safeGetFieldValue($report, $primaryField);
                }
            }
        }
        return null;
    }

    /**
     * Format an array of domains into a grammatically correct text list
     */
    public function formatDomainsToText(array $domains): string
    {
        $domains = array_values($domains); // reset keys to ensure order
        $count = count($domains);
        
        if ($count === 0) {
            return '';
        } elseif ($count === 1) {
            return $domains[0];
        } elseif ($count === 2) {
            return $domains[0] . ' and ' . $domains[1];
        } else {
            $last = array_pop($domains);
            return implode(', ', $domains) . ', and ' . $last;
        }
    }
}