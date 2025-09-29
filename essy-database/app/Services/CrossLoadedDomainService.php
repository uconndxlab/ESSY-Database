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
     * Initialize cross-loaded item groups configuration - Updated to match Excel field names
     */
    private function initializeCrossLoadedConfiguration(): void
    {
        $this->crossLoadedItemGroups = [
            ['A_P_S_ARTICULATE_CL1', 'A_P_S_ARTICULATE_CL2', 'A_P_S_ARTICULATE_CL3'], // Articulates clearly - added CL3
            ['A_S_ADULTCOMM_CL1', 'A_S_ADULTCOMM_CL2'],   // Effectively communicates with adults
            ['A_B_CLASSEXPECT_CL1', 'A_B_CLASSEXPECT_CL2'], // Follows classroom expectations
            ['A_B_IMPULSE_CL1', 'A_B_IMPULSE_CL2'],       // Exhibits impulsivity
            ['A_S_CONFIDENT_CL1', 'A_S_CONFIDENT_CL2'],   // Displays confidence in self
            ['A_S_POSOUT_CL1', 'A_S_POSOUT_CL2'],         // Demonstrates positive outlook
            ['A_B_DIRECTIONS_CL1', 'A_B_DIRECTIONS_CL2'], // Understands directions - new group
            ['S_P_ACHES_CL1', 'S_P_ACHES_CL2'],           // Complains of aches
            ['B_O_HOUSING_CL1', 'B_O_HOUSING_CL2'],       // Unstable living situation
            ['B_O_FAMSTRESS_CL1', 'B_O_FAMSTRESS_CL2'],   // Family stressors
            ['B_O_NBHDSTRESS_CL1', 'B_O_NBHDSTRESS_CL2'], // Neighborhood stressors
            ['O_P_HUNGER_CL1', 'O_P_HUNGER_CL2'],         // Reports being hungry
            ['O_P_HYGIENE_CL1', 'O_P_HYGIENE_CL2'],       // Hygiene resources - correct spelling to match Excel
            ['O_P_CLOTHES_CL1', 'O_P_CLOTHES_CL2'],       // Adequate clothing
            ['S_O_COMMCONN_CL1', 'S_O_COMMCONN_CL2'],     // Community connection - new group
            ['A_S_O_ACTIVITY_CL1', 'A_S_O_ACTIVITY_CL2', 'A_S_O_ACTIVITY_CL3'] // Extracurricular activity
        ];
    }

    /**
     * Build field to domain mapping - Updated to match Excel field names
     */
    private function buildFieldToDomainMap(): void
    {
        $this->fieldToDomainMap = [
            // Academic Skills - Updated field names to match Excel
            'A_READ' => 'Academic Skills',
            'A_WRITE' => 'Academic Skills',
            'A_MATH' => 'Academic Skills',
            'A_P_S_ARTICULATE_CL1' => 'Academic Skills',
            'A_P_S_ARTICULATE_CL3' => 'Academic Skills',
            'A_S_ADULTCOMM_CL1' => 'Academic Skills',
            'A_B_DIRECTIONS_CL1' => 'Academic Skills',
            'A_B_DIRECTIONS_CL2' => 'Behavior', // Cross-loaded between Academic (A) and Behavior (B)
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
            'A_S_O_ACTIVITY_CL1' => 'Academic Skills',

            // Behavior - Updated field names to match Excel
            'A_B_CLASSEXPECT_CL2' => 'Behavior',
            'A_B_IMPULSE_CL2' => 'Behavior',
            'B_CLINGY' => 'Behavior',
            'B_SNEAK' => 'Behavior',
            'B_VERBAGGRESS' => 'Behavior',
            'B_PHYSAGGRESS' => 'Behavior',
            'B_DESTRUCT' => 'Behavior',
            'B_BULLY' => 'Behavior',
            'B_PUNITIVE' => 'Behavior',
            'B_O_HOUSING_CL1' => 'Behavior',
            'B_O_FAMSTRESS_CL1' => 'Behavior',
            'B_O_NBHDSTRESS_CL1' => 'Behavior',

            // Physical Health - Updated field names to match Excel
            'P_SIGHT' => 'Physical Health',
            'P_HEAR' => 'Physical Health',
            'A_P_S_ARTICULATE_CL2' => 'Physical Health',
            'P_ORAL' => 'Physical Health',
            'P_PHYS' => 'Physical Health',
            'P_PARTICIPATE' => 'Physical Health',
            'S_P_ACHES_CL1' => 'Physical Health',
            'O_P_HUNGER_CL1' => 'Physical Health',
            'O_P_HYGIENE_CL1' => 'Physical Health',
            'O_P_CLOTHES_CL1' => 'Physical Health',

            // Social & Emotional Well-Being - Updated field names to match Excel
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
            'S_O_COMMCONN_CL1' => 'Social & Emotional Well-Being',
            'A_S_O_ACTIVITY_CL2' => 'Social & Emotional Well-Being',
            'A_P_S_ARTICULATE_CL3' => 'Social & Emotional Well-Being',

            // Supports Outside of School - Updated field names to match Excel
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
            'S_O_COMMCONN_CL2' => 'Supports Outside of School',

            // Gate 2 Essential Items
            'E_SHARM' => 'Gate 2 Essential Items',
            'E_BULLIED' => 'Gate 2 Essential Items', 
            'E_EXCLUDE' => 'Gate 2 Essential Items',
            'E_WITHDRAW' => 'Gate 2 Essential Items',
            'E_REGULATE' => 'Gate 2 Essential Items',
            'E_RESTED' => 'Gate 2 Essential Items',
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
     * Get field messages for display - Updated to match Excel field names
     */
    public function getFieldMessages(): array
    {
        return [
            // Academic Skills - Updated field names to match Excel
            'A_READ' => 'meets grade-level expectations for reading skills.',
            'A_WRITE' => 'meets expectations for grade-level writing skills.',
            'A_MATH' => 'meets expectations for grade-level math skills.',
            'A_P_S_ARTICULATE_CL1' => 'articulates clearly enough to be understood.',
            'A_P_S_ARTICULATE_CL3' => 'articulates clearly enough to be understood.',
            'A_S_ADULTCOMM_CL1' => 'communicates with adults effectively.',
            'A_B_DIRECTIONS_CL1' => 'understands directions.',
            'A_B_DIRECTIONS_CL2' => 'understands directions.',
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
            'A_S_O_ACTIVITY_CL1' => 'engaged in at least one extracurricular activity.',
            
            // Behavior - Updated field names to match Excel
            'A_B_CLASSEXPECT_CL2' => 'follows classroom expectations.',
            'A_B_IMPULSE_CL2' => 'exhibits impulsivity.',
            'B_CLINGY' => 'exhibits overly clingy or attention-seeking behaviors.',
            'B_SNEAK' => 'demonstrates sneaky or dishonest behavior.',
            'B_VERBAGGRESS' => 'engages in verbally aggressive behavior toward others.',
            'B_PHYSAGGRESS' => 'engages in physically aggressive behavior toward others.',
            'B_DESTRUCT' => 'engages in destructive behavior towards property.',
            'B_BULLY' => 'bullies/has bullied another student.',
            'B_PUNITIVE' => 'experiences/has experienced punitive or exclusionary discipline at school.',
            'B_O_HOUSING_CL1' => 'reports not having a stable living situation.',
            'B_O_FAMSTRESS_CL1' => 'family is experiencing significant stressors.',
            'B_O_NBHDSTRESS_CL1' => 'neighborhood is experiencing significant stressors.',
            
            // Physical Health - Updated field names to match Excel
            'P_SIGHT' => 'able to see, from a distance or up close.',
            'P_HEAR' => 'able to hear information.',
            'A_P_S_ARTICULATE_CL2' => 'articulates clearly enough to be understood.',
            'P_ORAL' => 'oral health appears to be addressed.',
            'P_PHYS' => 'physical health appears to be addressed.',
            'P_PARTICIPATE' => 'physical health allows for participation in school activities.',
            'S_P_ACHES_CL1' => 'complains of headaches, stomachaches, or body aches.',
            'O_P_HUNGER_CL1' => 'reports being hungry.',
            'O_P_HYGIENE_CL1' => 'appears to have the resources to address basic hygiene needs.',
            'O_P_CLOTHES_CL1' => 'shows up to school with adequate clothing.',
            
            // Social & Emotional Well-Being - Updated field names to match Excel
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
            'S_O_COMMCONN_CL1' => 'appears to experience a sense of connection in their community.',
            'S_O_COMMCONN_CL2' => 'appears to experience a sense of connection in their community.',
            'A_S_O_ACTIVITY_CL2' => 'engaged in at least one extracurricular activity.',
            
            // Supports Outside of School - Updated field names to match Excel
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
            'A_S_O_ACTIVITY_CL3' => 'engaged in at least one extracurricular activity.',
            
            // Gate 2 Essential Items
            'E_SHARM' => 'engages in self-harming behaviors.',
            'E_BULLIED' => 'has been bullied by other students.',
            'E_EXCLUDE' => 'experiences social exclusion in school.',
            'E_WITHDRAW' => 'avoids or withdraws from peers.',
            'E_REGULATE' => 'regulates emotions.',
            'E_RESTED' => 'appears well-rested.'
        ];
    }

    /**
     * Categorize field value into strengths, monitor, or concerns using pattern-based approach
     */
    public function categorizeFieldValue(string $field, string $value): string
    {
        $value = strtolower(trim($value));
        
        // Define categorization patterns (almost always, frequently, sometimes, occasionally, almost never)
        $patterns = [
            'GGBRR' => ['strengths', 'strengths', 'monitor', 'concerns', 'concerns'],     // Most common pattern
            'RRBGG' => ['concerns', 'concerns', 'monitor', 'strengths', 'strengths'],    // Negative items 
            'GGBBR' => ['strengths', 'strengths', 'monitor', 'monitor', 'concerns'],     // Resource-type items
            'RRBBG' => ['concerns', 'concerns', 'monitor', 'monitor', 'strengths'],      // Housing/hunger items
            'GBRRR' => ['strengths', 'monitor', 'concerns', 'concerns', 'concerns'],     // Vision items
            'RRRRG' => ['concerns', 'concerns', 'concerns', 'concerns', 'strengths'],    // Zero-tolerance items
            'GGRRR' => ['strengths', 'strengths', 'concerns', 'concerns', 'concerns'],   // Regulation/rest items
            'RRRGG' => ['concerns', 'concerns', 'concerns', 'strengths', 'strengths']    // Withdrawal items
        ];
        
        // Define field groups by pattern
        $fieldPatterns = [
            'RRBGG' => [
                'A_B_IMPULSE_CL1', 'A_B_IMPULSE_CL2', 'B_CLINGY', 
                'B_O_FAMSTRESS_CL1', 'B_O_FAMSTRESS_CL2', 
                'B_O_NBHDSTRESS_CL1', 'B_O_NBHDSTRESS_CL2',
                'S_P_ACHES_CL1', 'S_P_ACHES_CL2', 'S_NERVOUS', 'S_SAD'
            ],
            'RRBBG' => [
                'B_SNEAK', 'B_VERBAGGRESS', 'B_DESTRUCT',
                'B_O_HOUSING_CL1', 'B_O_HOUSING_CL2',
                'O_P_HUNGER_CL1', 'O_P_HUNGER_CL2'
            ],
            'RRRRG' => [
                'B_PHYSAGGRESS', 'B_BULLY', 'B_PUNITIVE',
                'E_SHARM', 'E_BULLIED', 'E_EXCLUDE'
            ],
            'GBRRR' => [
                'P_SIGHT', 'P_HEAR'
            ],
            'GGBBR' => [
                'O_RESOURCE'
            ],
            'GGRRR' => [
                'E_REGULATE', 'E_RESTED'
            ],
            'GGBRR' => [
                // Default pattern - all fields not explicitly listed above fall into this category
                // This includes: A_READ, A_WRITE, A_MATH, A_P_S_ARTICULATE_CL1, A_P_S_ARTICULATE_CL2, A_P_S_ARTICULATE_CL3,
                // A_S_ADULTCOMM_CL1, A_S_ADULTCOMM_CL2, A_B_DIRECTIONS_CL1, A_B_DIRECTIONS_CL2, A_INITIATE, A_PLANORG,
                // A_TURNIN, A_B_CLASSEXPECT_CL1, A_B_CLASSEXPECT_CL2, A_ENGAGE, A_INTEREST, A_PERSIST, A_GROWTH,
                // A_S_CONFIDENT_CL1, A_S_CONFIDENT_CL2, A_S_POSOUT_CL1, A_S_POSOUT_CL2, A_S_O_ACTIVITY_CL1, 
                // A_S_O_ACTIVITY_CL2, A_S_O_ACTIVITY_CL3, P_HEAR, P_ORAL, P_PHYS, P_PARTICIPATE, S_CONTENT,
                // S_SOCIALCONN, S_FRIEND, S_PROSOCIAL, S_PEERCOMM, S_POSADULT, S_SCHOOLCONN, S_O_COMMCONN_CL1,
                // S_O_COMMCONN_CL2, O_RECIPROCAL, O_POSADULT, O_ADULTBEST, O_TALK, O_ROUTINE, O_FAMILY,
                // O_P_HYGIENE_CL1, O_P_HYGIENE_CL2, O_P_CLOTHES_CL1, O_P_CLOTHES_CL2
            ]
        ];
        
        // Map frequency values to array indices
        $frequencyMap = [
            'almost always' => 0,
            'frequently' => 1,
            'sometimes' => 2,
            'occasionally' => 3,
            'almost never' => 4
        ];
        
        // Get frequency index
        if (!isset($frequencyMap[$value])) {
            // Invalid frequency value, default to concerns
            return 'concerns';
        }
        
        $frequencyIndex = $frequencyMap[$value];
        
        // Determine which pattern this field uses
        $patternKey = 'GGBRR'; // Default pattern
        foreach ($fieldPatterns as $pattern => $fields) {
            if (in_array($field, $fields)) {
                $patternKey = $pattern;
                break;
            }
        }
        
        // Return the category based on pattern and frequency
        return $patterns[$patternKey][$frequencyIndex];
    }

    /**
     * Get value for cross-loaded item from its primary field if secondary is empty
     */
    /**
     * Get value for cross-loaded item from other fields in the same group
     */
    private function getCrossLoadedValue(ReportData $report, string $field): ?string
    {
        try {
            // 1) Try alias for the requested field itself (handles misspellings like HYGEINE)
            if (isset($this->fieldAliasMap[$field])) {
                $aliasField = $this->fieldAliasMap[$field];
                $aliasValue = $report->getAttribute($aliasField);
                if (!($aliasValue === null || $aliasValue === '' || trim($aliasValue) === '-99')) {
                    return trim($aliasValue);
                }
            }

            // 2) Fall back to any other field in the same group
            foreach ($this->crossLoadedItemGroups as $group) {
                if (in_array($field, $group)) {
                    // Try to get value from ANY other field in the group
                    foreach ($group as $groupField) {
                        if ($groupField !== $field) {
                            $value = $this->safeGetFieldValue($report, $groupField);
                            if ($value !== null) {
                                return $value;
                            }
                        }
                    }
                }
            }
            return null;
        } catch (\Exception $e) {
            $this->logCrossLoadedError('Error getting cross-loaded value', [
                'field' => $field,
                'error' => $e->getMessage()
            ]);
            return null;
        }
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

    /**
     * Process gender display according to business rules
     * 
     * Rules:
     * - Report "Male" or "Female" as selected
     * - Report "Other" if "Additional Gender Category" is selected  
     * - Leave blank if "Not Sure" is selected
     */
    public function processGenderDisplay(?string $gender, ?string $genderText = null): string
    {
        if (empty($gender) || $gender === '-99') {
            return '';
        }

        $gender = trim($gender);
        
        // Handle standard gender selections
        if (in_array($gender, ['Male', 'Female'])) {
            return $gender;
        }
        
        // Handle "Additional Gender Category" or similar "Other" selections
        if (str_contains($gender, 'Additional') || str_contains($gender, 'Other')) {
            return 'Other';
        }
        
        // Handle "Not Sure" selections
        if (str_contains($gender, 'Not Sure') || str_contains($gender, 'Unsure')) {
            return '';
        }
        
        // For any other values, return as-is (fallback)
        return $gender;
    }
}