<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ReportData extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'batch_id', 'excel_file_path',
        'StartDate', 'EndDate', 'Status', 'IPAddress', 'Progress', 'Duration', 'Finished', 'RecordedDate',
        'ResponseId', 'RecipientLastName', 'RecipientFirstName', 'RecipientEmail', 'ExternalReference',
        'LocationLatitude', 'LocationLongitude', 'DistributionChannel', 'UserLanguage',
        'FN_STUDENT', 'LN_STUDENT', 'FN_TEACHER', 'LN_TEACHER', 'SCHOOL',
        'A_DOMAIN', 'ATT_DOMAIN', 'B_DOMAIN', 'P_DOMAIN', 'S_DOMAIN', 'O_DOMAIN',
        'COMMENTS_GATE1', 'TIMING_GATE1_FirstClick', 'TIMING_GATE1_LastClick', 'TIMING_GATE1_PageSubmit', 'TIMING_GATE1_ClickCount',
        'E_SHARM', 'E_BULLIED', 'E_EXCLUDE', 'E_WITHDRAW', 'E_REGULATE', 'E_RESTED',
        'COMMENTS_ESS', 'TIMING_ESS_FirstClick', 'TIMING_ESS_LastClick', 'TIMING_ESS_PageSubmit', 'TIMING_ESS_ClickCount',
        // Academic Skills - Updated field names to match Excel
        'A_READ', 'A_WRITE', 'A_MATH', 'A_P_S_ARTICULATE_CL1', 'A_P_S_ARTICULATE_CL2', 'A_P_S_ARTICULATE_CL3', 
        'A_S_ADULTCOMM_CL1', 'A_B_DIRECTIONS_CL1', 'A_B_DIRECTIONS_CL2', 'A_INITIATE', 'A_PLANORG',
        'A_TURNIN', 'A_B_CLASSEXPECT_CL1', 'A_B_IMPULSE_CL1', 'A_ENGAGE', 'A_INTEREST', 'A_PERSIST', 'A_GROWTH',
        'A_S_CONFIDENT_CL1', 'A_S_POSOUT_CL1', 'A_S_O_ACTIVITY_CL1',
        'COMMENTS_AS', 'TIMING_AS_FirstClick', 'TIMING_AS_LastClick', 'TIMING_AS_PageSubmit', 'TIMING_AS_ClickCount',
        // Behavior - Updated field names to match Excel
        'A_B_CLASSEXPECT_CL2', 'A_B_IMPULSE_CL2', 'B_CLINGY', 'B_SNEAK', 'B_VERBAGGRESS', 'B_PHYSAGGRESS',
        'B_DESTRUCT', 'B_BULLY', 'B_PUNITIVE', 'B_O_HOUSING_CL1', 'B_O_FAMSTRESS_CL1', 'B_O_NBHDSTRESS_CL1',
        'COMMENTS_BEH', 'TIMING_BEH_FirstClick', 'TIMING_BEH_LastClick', 'TIMING_BEH_PageSubmit', 'TIMING_BEH_ClickCount',
        // Physical Health - Updated field names to match Excel
        'P_SIGHT', 'P_HEAR', 'P_ORAL', 'P_PHYS', 'P_PARTICIPATE', 'S_P_ACHES_CL1',
        'O_P_HUNGER_CL1', 'O_P_HYGEINE_CL1', 'O_P_CLOTHES_CL1', 'COMMENTS_PH', 'TIMING_PH_FirstClick',
        'TIMING_PH_LastClick', 'TIMING_PH_PageSubmit', 'TIMING_PH_ClickCount',
        // Social & Emotional Well-Being - Updated field names to match Excel
        'S_CONTENT', 'A_S_CONFIDENT_CL2', 'A_S_POSOUT_CL2', 'S_P_ACHES_CL2', 'S_NERVOUS', 'S_SAD',
        'S_SOCIALCONN', 'S_FRIEND', 'S_PROSOCIAL', 'S_PEERCOMM', 'A_S_ADULTCOMM_CL2',
        'S_POSADULT', 'S_SCHOOLCONN', 'S_O_COMMCONN_CL1', 'S_O_COMMCONN_CL2', 'A_S_O_ACTIVITY_CL2',
        'COMMENTS_SEW', 'TIMING_SEW_FirstClick', 'TIMING_SEW_LastClick', 'TIMING_SEW_PageSubmit', 'TIMING_SEW_ClickCount',
        // Supports Outside of School - Updated field names to match Excel
        'O_RECIPROCAL', 'O_POSADULT', 'O_ADULTBEST', 'O_TALK', 'O_ROUTINE', 'O_FAMILY',
        'O_P_HUNGER_CL2', 'O_P_HYGEINE_CL2', 'O_P_CLOTHES_CL2', 'O_RESOURCE',
        'B_O_HOUSING_CL2', 'B_O_FAMSTRESS_CL2', 'B_O_NBHDSTRESS_CL2', 'A_S_O_ACTIVITY_CL3',
        'COMMENTS_SOS', 'TIMING_SOS_FirstClick', 'TIMING_SOS_LastClick', 'TIMING_SOS_PageSubmit', 'TIMING_SOS_ClickCount',
        'RELATION_CLOSE', 'RELATION_CONFLICT', 'COMMENTS_STR',
        'DEM_RACE', 'DEM_RACE_14_TEXT', 'DEM_ETHNIC', 'DEM_GENDER', 'DEM_ELL', 'DEM_IEP',
        'DEM_504', 'DEM_CI', 'DEM_GRADE', 'DEM_CLASSTEACH', 'SPEEDING_GATE1', 'SPEEDING_ESS', 'SPEEDING_GATE2', 'batch_id', 'created_at', 'updated_at'
    ];

    /**
     * Get domains that are marked as concerns
     */
    public function getConcernDomains(): array
    {
        try {
            $domainValues = [
                'Academic Skills' => $this->A_DOMAIN,
                'Behavior' => $this->B_DOMAIN,
                'Social & Emotional Well-Being' => $this->S_DOMAIN,
                'Physical Health' => $this->P_DOMAIN,
                'Supports Outside of School' => $this->O_DOMAIN,
                'Attendance' => $this->ATT_DOMAIN,
            ];

            $concernDomains = [];

            foreach ($domainValues as $domain => $rating) {
                if (!$rating) continue;

                $cleanRating = $this->getCleanRating($rating);
                
                if ($this->isDomainConcern($cleanRating)) {
                    $concernDomains[] = $domain;
                }
            }

            return $concernDomains;
        } catch (\Exception $e) {
            Log::error('[ReportData] Error getting concern domains', [
                'report_id' => $this->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get item value with safe access
     */
    public function getItemValue(string $field): ?string
    {
        try {
            return $this->safeGetAttribute($field);
        } catch (\Exception $e) {
            Log::error('[ReportData] Error getting item value', [
                'field' => $field,
                'report_id' => $this->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if field has valid value
     */
    public function hasValidValue(string $field): bool
    {
        try {
            $value = $this->safeGetAttribute($field);
            return $value !== null && $value !== '' && trim($value) !== '-99';
        } catch (\Exception $e) {
            Log::error('[ReportData] Error checking valid value', [
                'field' => $field,
                'report_id' => $this->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Safely get attribute with default value
     */
    public function safeGetAttribute(string $field, $default = null): mixed
    {
        try {
            if (!in_array($field, $this->fillable)) {
                Log::warning('[ReportData] Accessing non-fillable field', [
                    'field' => $field,
                    'report_id' => $this->id ?? 'unknown'
                ]);
                return $default;
            }

            $value = $this->getAttribute($field);
            
            if ($value === null || $value === '' || trim($value) === '-99') {
                return $default;
            }
            
            return $value;
        } catch (\Exception $e) {
            Log::error('[ReportData] Error in safe attribute access', [
                'field' => $field,
                'report_id' => $this->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return $default;
        }
    }

    /**
     * Validate domain rating
     */
    public function validateDomainRating(string $domain): bool
    {
        try {
            $domainField = $this->getDomainField($domain);
            if (!$domainField) {
                return false;
            }

            $rating = $this->safeGetAttribute($domainField);
            return $rating !== null && trim($rating) !== '';
        } catch (\Exception $e) {
            Log::error('[ReportData] Error validating domain rating', [
                'domain' => $domain,
                'report_id' => $this->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get clean rating without confidence flags
     */
    public function getCleanRating(string $rawValue): ?string
    {
        try {
            if (!$rawValue || trim($rawValue) === '') {
                return null;
            }

            // Remove confidence flag text and get just the rating
            $cleanRating = explode(',', $rawValue)[0];
            return trim($cleanRating);
        } catch (\Exception $e) {
            Log::error('[ReportData] Error cleaning rating', [
                'raw_value' => $rawValue,
                'report_id' => $this->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Check if rating has confidence flag
     */
    public function hasConfidenceFlag(string $rawValue): bool
    {
        try {
            return str_contains($rawValue, 'Check here');
        } catch (\Exception $e) {
            Log::error('[ReportData] Error checking confidence flag', [
                'raw_value' => $rawValue,
                'report_id' => $this->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if domain rating indicates concern
     */
    private function isDomainConcern(string $cleanRating): bool
    {
        $concernRatings = [
            'an area of some concern',
            'an area of substantial concern'
        ];

        return in_array(trim(strtolower($cleanRating)), $concernRatings);
    }

    /**
     * Get domain field name from domain name
     */
    private function getDomainField(string $domain): ?string
    {
        $domainMap = [
            'Academic Skills' => 'A_DOMAIN',
            'Behavior' => 'B_DOMAIN',
            'Social & Emotional Well-Being' => 'S_DOMAIN',
            'Physical Health' => 'P_DOMAIN',
            'Supports Outside of School' => 'O_DOMAIN',
            'Attendance' => 'ATT_DOMAIN',
        ];

        return $domainMap[$domain] ?? null;
    }
}

