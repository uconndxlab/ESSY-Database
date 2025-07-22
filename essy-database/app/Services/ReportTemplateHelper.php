<?php

namespace App\Services;

use App\Models\ReportData;
use App\ValueObjects\ProcessedItem;
use App\ValueObjects\DomainProcessingResult;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class ReportTemplateHelper
{
    private CrossLoadedDomainService $crossLoadedService;
    private LoggerInterface $logger;

    public function __construct(CrossLoadedDomainService $service, ?LoggerInterface $logger = null)
    {
        $this->crossLoadedService = $service;
        $this->logger = $logger ?? Log::channel('default');
    }

    /**
     * Format item text with dagger symbol if needed
     */
    public function formatItemWithDagger(string $item, string $field, array $daggerFields): string
    {
        try {
            $formattedItem = trim($item);
            
            if (isset($daggerFields[$field])) {
                $formattedItem .= '†';
            }
            
            return $formattedItem;
        } catch (\Exception $e) {
            $this->handleProcessingError($e, "formatting item with dagger for field: {$field}");
            return $item; // Return original item on error
        }
    }

    /**
     * Process items for a specific domain
     */
    public function processItemsForDomain(string $domain, array $indicators, ReportData $report): DomainProcessingResult
    {
        $strengths = [];
        $monitor = [];
        $concerns = [];
        $errors = [];

        try {
            $concernDomains = $report->getConcernDomains();
            $daggerFields = $this->crossLoadedService->getFieldsRequiringDagger($concernDomains);

            foreach ($indicators as $field => $config) {
                $processedItem = $this->safeProcessItem($field, $config['message'] ?? '', $report);
                
                if ($processedItem === null) {
                    continue;
                }

                // Add dagger if needed
                if (isset($daggerFields[$field])) {
                    $processedItem = $processedItem->withDagger();
                }

                // Categorize based on rating value
                $value = $report->safeGetAttribute($field);
                if ($value) {
                    $category = $this->categorizeItemValue($value);
                    $processedItem = ProcessedItem::create(
                        $processedItem->text,
                        $category,
                        $processedItem->hasConfidence,
                        $processedItem->hasDagger
                    );

                    switch ($category) {
                        case 'strengths':
                            $strengths[] = $processedItem;
                            break;
                        case 'monitor':
                            $monitor[] = $processedItem;
                            break;
                        case 'concerns':
                            $concerns[] = $processedItem;
                            break;
                    }
                }
            }

        } catch (\Exception $e) {
            $error = "Error processing domain '{$domain}': " . $e->getMessage();
            $errors[] = $error;
            $this->handleProcessingError($e, "processing items for domain: {$domain}");
        }

        return DomainProcessingResult::create($strengths, $monitor, $concerns, $errors);
    }

    /**
     * Safely process a single item with validation and fallback logic
     */
    public function safeProcessItem(string $field, string $message, ReportData $report): ?ProcessedItem
    {
        try {
            $value = $this->crossLoadedService->safeGetFieldValue($report, $field);
            
            if ($value === null || trim($value) === '') {
                return null;
            }

            // Check for confidence flag
            $hasConfidence = str_contains($value, 'Check here');
            
            // Clean the message text
            $cleanMessage = trim($message);
            if (empty($cleanMessage)) {
                $cleanMessage = "Item {$field}"; // Fallback message
            }

            return ProcessedItem::create(
                $cleanMessage,
                'unknown', // Will be categorized later
                $hasConfidence,
                false // Dagger will be added later if needed
            );

        } catch (\Exception $e) {
            $this->handleProcessingError($e, "processing item for field: {$field}");
            return null;
        }
    }

    /**
     * Handle processing errors with logging
     */
    public function handleProcessingError(\Exception $e, string $context): void
    {
        $this->logger->error('[ReportTemplateHelper] Processing error', [
            'context' => $context,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Categorize item value into strengths, monitor, or concerns
     */
    private function categorizeItemValue(string $value): string
    {
        $cleanValue = trim(strtolower($value));
        
        // Remove confidence flag text for categorization
        $cleanValue = explode(',', $cleanValue)[0];
        $cleanValue = trim($cleanValue);

        // Define categorization rules based on common rating patterns
        $strengthPatterns = [
            'never',
            'rarely',
            'almost never',
            'not at all',
            'strongly disagree',
            'disagree'
        ];

        $concernPatterns = [
            'always',
            'almost always',
            'frequently',
            'often',
            'strongly agree',
            'agree'
        ];

        $monitorPatterns = [
            'sometimes',
            'occasionally',
            'neutral',
            'neither agree nor disagree'
        ];

        foreach ($strengthPatterns as $pattern) {
            if (str_contains($cleanValue, $pattern)) {
                return 'strengths';
            }
        }

        foreach ($concernPatterns as $pattern) {
            if (str_contains($cleanValue, $pattern)) {
                return 'concerns';
            }
        }

        foreach ($monitorPatterns as $pattern) {
            if (str_contains($cleanValue, $pattern)) {
                return 'monitor';
            }
        }

        // Default to monitor if we can't categorize
        return 'monitor';
    }

    /**
     * Get domain indicators configuration
     */
    public function getDomainIndicators(): array
    {
        return [
            'Academic Skills' => [
                'A_READ' => ['message' => 'Reads at grade level'],
                'A_WRITE' => ['message' => 'Writes at grade level'],
                'A_MATH' => ['message' => 'Performs math at grade level'],
                'A_P_ARTICULATE_CL1' => ['message' => 'Articulates clearly'],
                'A_S_ADULTCOMM_CL1' => ['message' => 'Effectively communicates with adults'],
                'A_DIRECTIONS' => ['message' => 'Follows directions'],
                'A_INITIATE' => ['message' => 'Initiates tasks'],
                'A_PLANORG' => ['message' => 'Plans and organizes'],
                'A_TURNIN' => ['message' => 'Turns in assignments'],
                'A_B_CLASSEXPECT_CL1' => ['message' => 'Follows classroom expectations'],
                'A_B_IMPULSE_CL1' => ['message' => 'Exhibits impulsivity'],
                'A_ENGAGE' => ['message' => 'Engages in learning'],
                'A_INTEREST' => ['message' => 'Shows interest in learning'],
                'A_PERSIST' => ['message' => 'Persists through challenges'],
                'A_GROWTH' => ['message' => 'Shows academic growth'],
                'A_S_CONFIDENT_CL1' => ['message' => 'Displays confidence in self'],
                'A_S_POSOUT_CL1' => ['message' => 'Demonstrates positive outlook'],
                'A_S_O_ACTIVITY3_CL1' => ['message' => 'Participates in extracurricular activities'],
            ],
            'Behavior' => [
                'A_B_CLASSEXPECT_CL2' => ['message' => 'Follows classroom expectations'],
                'A_B_IMPULSE_CL2' => ['message' => 'Exhibits impulsivity'],
                'B_CLINGY' => ['message' => 'Exhibits clingy behavior'],
                'B_SNEAK' => ['message' => 'Sneaks around'],
                'BEH_VERBAGGRESS' => ['message' => 'Shows verbal aggression'],
                'BEH_PHYSAGGRESS' => ['message' => 'Shows physical aggression'],
                'B_DESTRUCT' => ['message' => 'Destructive behavior'],
                'B_BULLY' => ['message' => 'Bullies others'],
                'B_PUNITIVE' => ['message' => 'Punitive toward others'],
                'B_O_HOUSING_CL1' => ['message' => 'Unstable living situation'],
                'B_O_FAMSTRESS_CL1' => ['message' => 'Family stressors'],
                'B_O_NBHDSTRESS_CL1' => ['message' => 'Neighborhood stressors'],
            ],
            'Physical Health' => [
                'P_SIGHT' => ['message' => 'Vision concerns'],
                'P_HEAR' => ['message' => 'Hearing concerns'],
                'A_P_ARTICULATE_CL2' => ['message' => 'Articulates clearly'],
                'A_ORAL' => ['message' => 'Oral motor skills'],
                'A_PHYS' => ['message' => 'Physical motor skills'],
                'P_PARTICIPATE' => ['message' => 'Participates in physical activities'],
                'S_P_ACHES_CL1' => ['message' => 'Complains of aches and pains'],
                'O_P_HUNGER_CL1' => ['message' => 'Reports being hungry'],
                'O_P_HYGIENE_CL1' => ['message' => 'Has hygiene resources'],
                'O_P_CLOTHES_CL1' => ['message' => 'Has adequate clothing'],
            ],
            'Social & Emotional Well-Being' => [
                'S_CONTENT' => ['message' => 'Appears content'],
                'A_S_CONFIDENT_CL2' => ['message' => 'Displays confidence in self'],
                'A_S_POSOUT_CL2' => ['message' => 'Demonstrates positive outlook'],
                'S_P_ACHES_CL2' => ['message' => 'Complains of aches and pains'],
                'S_NERVOUS' => ['message' => 'Appears nervous or anxious'],
                'S_SAD' => ['message' => 'Appears sad'],
                'S_SOCIALCONN' => ['message' => 'Socially connected'],
                'S_FRIEND' => ['message' => 'Has friends'],
                'S_PROSOCIAL' => ['message' => 'Shows prosocial behavior'],
                'S_PEERCOMM' => ['message' => 'Communicates with peers'],
                'A_S_ADULTCOMM_CL2' => ['message' => 'Effectively communicates with adults'],
                'S_POSADULT' => ['message' => 'Positive relationships with adults'],
                'S_SCHOOLCONN' => ['message' => 'Connected to school'],
                'S_COMMCONN' => ['message' => 'Connected to community'],
                'A_S_O_ACTIVITY_CL2' => ['message' => 'Participates in extracurricular activities'],
            ],
            'Supports Outside of School' => [
                'O_RECIPROCAL' => ['message' => 'Reciprocal relationships'],
                'O_POSADULT' => ['message' => 'Positive adult relationships'],
                'O_ADULTBEST' => ['message' => 'Adult who knows student best'],
                'O_TALK' => ['message' => 'Has someone to talk to'],
                'O_ROUTINE' => ['message' => 'Has consistent routines'],
                'O_FAMILY' => ['message' => 'Family support'],
                'O_P_HUNGER_CL2' => ['message' => 'Reports being hungry'],
                'O_P_HYGIENE_CL2' => ['message' => 'Has hygiene resources'],
                'O_P_CLOTHES_CL2' => ['message' => 'Has adequate clothing'],
                'O_RESOURCE' => ['message' => 'Has access to resources'],
                'B_O_HOUSING_CL2' => ['message' => 'Unstable living situation'],
                'B_O_FAMSTRESS_CL2' => ['message' => 'Family stressors'],
                'B_O_NBHDSTRESS_CL2' => ['message' => 'Neighborhood stressors'],
                'A_S_O_ACTIVITY_CL3' => ['message' => 'Participates in extracurricular activities'],
            ]
        ];
    }
}