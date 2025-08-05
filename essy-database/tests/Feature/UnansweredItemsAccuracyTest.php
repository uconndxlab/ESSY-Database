<?php

namespace Tests\Feature;

use App\Models\ReportData;
use App\Services\CrossLoadedDomainService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test to validate unanswered items calculation accuracy
 * 
 * This test ensures that only truly unanswered items appear in the unanswered section
 * and that items with data no longer appear as unanswered after the field name corrections.
 */
class UnansweredItemsAccuracyTest extends TestCase
{
    use RefreshDatabase;

    private CrossLoadedDomainService $crossLoadedService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->crossLoadedService = new CrossLoadedDomainService();
    }

    /** @test */
    public function it_does_not_show_items_with_valid_data_as_unanswered()
    {
        // Create a report with data for previously problematic fields
        $report = ReportData::create([
            'FN_STUDENT' => 'TestStudent',
            'LN_STUDENT' => 'TestLastName',
            
            // Fields that were previously showing as unanswered due to field name mismatches
            'A_B_DIRECTIONS_CL1' => 'Frequently', // Was A_DIRECTIONS
            'B_VERBAGGRESS' => 'Sometimes', // Was BEH_VERBAGGRESS
            'B_PHYSAGGRESS' => 'Almost Never', // Was BEH_PHYSAGGRESS
            'P_ORAL' => 'Sometimes', // Was A_ORAL
            'P_PHYS' => 'Sometimes', // Was A_PHYS
            'O_P_HYGEINE_CL1' => 'Sometimes', // Was O_P_HYGIENE_CL1 (spelling)
            'S_O_COMMCONN_CL1' => 'Sometimes', // Was S_COMMCONN
            
            // Some fields with no data (should be unanswered)
            'A_READ' => '',
            'A_WRITE' => '',
            'S_CONTENT' => '-99', // -99 indicates no response
        ]);

        $domains = ['Academic Skills', 'Behavior', 'Physical Health', 'Social & Emotional Well-Being'];
        
        // Process each domain and collect all items that have data
        $itemsWithData = [];
        $itemsWithoutData = [];
        
        foreach ($domains as $domain) {
            $result = $this->crossLoadedService->processDomainItems($report, $domain, $domains);
            $allItems = array_merge($result['strengths'], $result['monitor'], $result['concerns']);
            
            foreach ($allItems as $item) {
                $itemsWithData[] = $item;
            }
        }
        
        // Verify that previously problematic fields now appear in domain results
        $allItemsText = implode(' ', $itemsWithData);
        
        $this->assertStringContainsString('understands directions', $allItemsText, 
            'Directions field should not be unanswered when it has data');
        $this->assertStringContainsString('verbally aggressive behavior', $allItemsText,
            'Verbal aggression field should not be unanswered when it has data');
        $this->assertStringContainsString('physically aggressive behavior', $allItemsText,
            'Physical aggression field should not be unanswered when it has data');
        $this->assertStringContainsString('oral health appears to be addressed', $allItemsText,
            'Oral health field should not be unanswered when it has data');
        $this->assertStringContainsString('physical health appears to be addressed', $allItemsText,
            'Physical health field should not be unanswered when it has data');
        $this->assertStringContainsString('basic hygiene needs', $allItemsText,
            'Hygiene field should not be unanswered when it has data');
        $this->assertStringContainsString('sense of connection in their community', $allItemsText,
            'Community connection field should not be unanswered when it has data');
        
        // Verify that fields without data do NOT appear in domain results
        $this->assertStringNotContainsString('meets grade-level expectations for reading skills', $allItemsText,
            'Reading field should be unanswered when it has no data');
        $this->assertStringNotContainsString('meets expectations for grade-level writing skills', $allItemsText,
            'Writing field should be unanswered when it has no data');
        $this->assertStringNotContainsString('appears content', $allItemsText,
            'Content field should be unanswered when it has -99 value');
    }

    /** @test */
    public function it_correctly_handles_cross_loaded_items_with_partial_data()
    {
        // Create a report to test cross-loaded behavior
        $report = ReportData::create([
            'FN_STUDENT' => 'TestStudent',
            'LN_STUDENT' => 'TestLastName',
            
            // Cross-loaded group: articulate (appears in both Academic Skills and Physical Health)
            'A_P_S_ARTICULATE_CL1' => 'Sometimes', // Primary field has data
            'A_P_S_ARTICULATE_CL2' => '', // Secondary field empty - should use CL1 when processing Physical Health
            'A_P_S_ARTICULATE_CL3' => '', // Tertiary field empty
            
            // Cross-loaded group: directions (only in Academic Skills - won't get dagger)
            'A_B_DIRECTIONS_CL1' => 'Frequently', // Has data
            'A_B_DIRECTIONS_CL2' => '', // No data
            
            // Cross-loaded group: class expectations (appears in both Academic Skills and Behavior)
            'A_B_CLASSEXPECT_CL1' => 'Occasionally', // Primary field has data
            'A_B_CLASSEXPECT_CL2' => '', // Secondary field empty - should use CL1 when processing Behavior
        ]);

        // Set concern domains to include Academic Skills, Physical Health, and Behavior
        // This will make articulate and class expectations fields require dagger and enable cross-loaded behavior
        $concernDomains = ['Academic Skills', 'Physical Health', 'Behavior'];
        
        // Process Academic Skills domain
        $academicResult = $this->crossLoadedService->processDomainItems($report, 'Academic Skills', $concernDomains);
        $academicItems = array_merge($academicResult['strengths'], $academicResult['monitor'], $academicResult['concerns']);
        $academicText = implode(' ', $academicItems);
        
        // Should show directions item since CL1 has data (no dagger since only in Academic Skills)
        $this->assertStringContainsString('understands directions', $academicText,
            'Directions should appear when CL1 has data');
        
        // Should show articulate item with dagger since it appears in both Academic Skills and Physical Health
        $this->assertStringContainsString('articulates clearly enough to be understood. †', $academicText,
            'Articulate should appear with dagger when in multiple concern domains');
        
        // Should show class expectations with dagger since it appears in both Academic Skills and Behavior
        $this->assertStringContainsString('follows classroom expectations. †', $academicText,
            'Class expectations should appear with dagger when in multiple concern domains');
        
        // Process Physical Health domain to test cross-loaded behavior
        $physicalResult = $this->crossLoadedService->processDomainItems($report, 'Physical Health', $concernDomains);
        $physicalItems = array_merge($physicalResult['strengths'], $physicalResult['monitor'], $physicalResult['concerns']);
        $physicalText = implode(' ', $physicalItems);
        
        // Articulate CL2 is empty, but should use CL1 value since it needs dagger
        $this->assertStringContainsString('articulates clearly enough to be understood. †', $physicalText,
            'Articulate should appear in Physical Health domain via cross-loaded behavior');
        
        // Process Behavior domain to test cross-loaded behavior for class expectations
        $behaviorResult = $this->crossLoadedService->processDomainItems($report, 'Behavior', $concernDomains);
        $behaviorItems = array_merge($behaviorResult['strengths'], $behaviorResult['monitor'], $behaviorResult['concerns']);
        $behaviorText = implode(' ', $behaviorItems);
        
        // Class expectations CL2 is empty, but should use CL1 value since it needs dagger
        $this->assertStringContainsString('follows classroom expectations. †', $behaviorText,
            'Class expectations should appear in Behavior domain via cross-loaded behavior');
    }

    /** @test */
    public function it_handles_confidence_indicators_correctly()
    {
        // Create a report with confidence indicators (items marked with "Check box if NOT confident in rating")
        $report = ReportData::create([
            'FN_STUDENT' => 'TestStudent',
            'LN_STUDENT' => 'TestLastName',
            
            // Items with confidence indicators
            'P_HEAR' => 'Almost Always,Check box if NOT confident in rating',
            'P_PARTICIPATE' => 'Sometimes,Check box if NOT confident in rating',
            'O_P_HYGEINE_CL1' => 'Sometimes,Check box if NOT confident in rating',
            'S_SCHOOLCONN' => 'Occasionally,Check box if NOT confident in rating',
            
            // Items without confidence indicators
            'P_SIGHT' => 'Almost Always',
            'S_CONTENT' => 'Frequently',
        ]);

        $result = $this->crossLoadedService->processDomainItems($report, 'Physical Health', []);
        $allItems = array_merge($result['strengths'], $result['monitor'], $result['concerns']);
        $allItemsText = implode(' ', $allItems);
        
        // Items with confidence indicators should have asterisk
        $this->assertStringContainsString('Almost always able to hear information. *', $allItemsText,
            'Items with confidence indicators should have asterisk');
        $this->assertStringContainsString('Sometimes physical health allows for participation in school activities. *', $allItemsText,
            'Items with confidence indicators should have asterisk');
        $this->assertStringContainsString('Sometimes appears to have the resources to address basic hygiene needs. *', $allItemsText,
            'Items with confidence indicators should have asterisk');
        
        // Items without confidence indicators should not have asterisk
        $this->assertStringContainsString('Almost always able to see, from a distance or up close.', $allItemsText);
        $this->assertStringNotContainsString('Almost always able to see, from a distance or up close. *', $allItemsText,
            'Items without confidence indicators should not have asterisk');
    }

    /** @test */
    public function it_correctly_categorizes_items_by_frequency()
    {
        // Create a report with various frequency responses
        $report = ReportData::create([
            'FN_STUDENT' => 'TestStudent',
            'LN_STUDENT' => 'TestLastName',
            
            // Strength items (Almost Always, Frequently for positive items)
            'A_READ' => 'Almost Always',
            'A_WRITE' => 'Frequently',
            'S_CONTENT' => 'Almost Always',
            
            // Monitor items (Sometimes, Occasionally for most items)
            'A_ENGAGE' => 'Sometimes',
            'S_NERVOUS' => 'Occasionally',
            
            // Concern items (Almost Never, Occasionally for positive items; Frequently, Almost Always for negative items)
            'A_PERSIST' => 'Almost Never', // Positive item with low frequency = concern
            'B_VERBAGGRESS' => 'Frequently', // Negative item with high frequency = concern
            'B_BULLY' => 'Almost Always', // Negative item with very high frequency = concern
        ]);

        $result = $this->crossLoadedService->processDomainItems($report, 'Academic Skills', []);
        
        // Check strengths
        $strengthsText = implode(' ', $result['strengths']);
        $this->assertStringContainsString('Almost always meets grade-level expectations for reading skills', $strengthsText);
        $this->assertStringContainsString('Frequently meets expectations for grade-level writing skills', $strengthsText);
        
        // Check monitor
        $monitorText = implode(' ', $result['monitor']);
        $this->assertStringContainsString('Sometimes engaged in academic activities', $monitorText);
        
        // Check concerns
        $concernsText = implode(' ', $result['concerns']);
        $this->assertStringContainsString('Almost never persists with challenging tasks', $concernsText);
        
        // Test behavior domain for negative items
        $behaviorResult = $this->crossLoadedService->processDomainItems($report, 'Behavior', []);
        $behaviorConcerns = implode(' ', $behaviorResult['concerns']);
        $this->assertStringContainsString('Frequently engages in verbally aggressive behavior', $behaviorConcerns);
        $this->assertStringContainsString('Almost always bullies/has bullied another student', $behaviorConcerns);
    }

    /** @test */
    public function it_processes_real_imported_data_without_unanswered_items_bug()
    {
        // Simulate the actual imported data structure from essy.xlsx
        $report = ReportData::create([
            'FN_STUDENT' => 'Aaron',
            'LN_STUDENT' => 'Garcia',
            'FN_TEACHER' => 'Holly',
            'LN_TEACHER' => 'Reeves',
            'SCHOOL' => 'North School',
            
            // Academic Skills - using corrected field names from actual import
            'A_READ' => 'Almost Always',
            'A_WRITE' => 'Almost Always',
            'A_MATH' => 'Almost Always',
            'A_P_S_ARTICULATE_CL1' => 'Almost Always',
            'A_S_ADULTCOMM_CL1' => 'Sometimes',
            'A_B_DIRECTIONS_CL1' => 'Frequently', // This was previously unanswered due to field name mismatch
            'A_B_DIRECTIONS_CL2' => '', // Cross-loaded variant
            'A_INITIATE' => 'Frequently',
            'A_PLANORG' => 'Frequently',
            'A_TURNIN' => 'Sometimes',
            'A_B_CLASSEXPECT_CL1' => 'Sometimes',
            'A_B_IMPULSE_CL1' => 'Sometimes',
            'A_ENGAGE' => 'Sometimes',
            'A_INTEREST' => 'Sometimes',
            'A_PERSIST' => 'Sometimes',
            'A_GROWTH' => 'Occasionally',
            'A_S_CONFIDENT_CL1' => 'Occasionally',
            'A_S_POSOUT_CL1' => 'Almost Never',
            'A_S_O_ACTIVITY_CL1' => 'Almost Never',
            
            // Behavior - using corrected field names from actual import
            'B_CLINGY' => 'Almost Never',
            'B_SNEAK' => 'Sometimes',
            'B_VERBAGGRESS' => 'Sometimes', // This was previously unanswered due to field name mismatch
            'B_PHYSAGGRESS' => 'Almost Never', // This was previously unanswered due to field name mismatch
            'B_DESTRUCT' => 'Sometimes',
            'B_BULLY' => 'Frequently',
            'B_PUNITIVE' => 'Almost Never',
            'B_O_HOUSING_CL1' => 'Frequently',
            'B_O_FAMSTRESS_CL1' => 'Almost Always',
            'B_O_NBHDSTRESS_CL1' => 'Almost Always',
            
            // Physical Health - using corrected field names from actual import
            'P_SIGHT' => 'Almost Always',
            'P_HEAR' => 'Almost Always,Check box if NOT confident in rating',
            'P_ORAL' => 'Sometimes', // This was previously unanswered due to field name mismatch
            'P_PHYS' => 'Sometimes', // This was previously unanswered due to field name mismatch
            'P_PARTICIPATE' => 'Sometimes,Check box if NOT confident in rating',
            'S_P_ACHES_CL1' => 'Occasionally',
            'O_P_HUNGER_CL1' => 'Sometimes',
            'O_P_HYGEINE_CL1' => 'Sometimes,Check box if NOT confident in rating', // This was previously unanswered due to spelling
            'O_P_CLOTHES_CL1' => 'Almost Never',
            
            // Social & Emotional Well-Being - using corrected field names from actual import
            'S_CONTENT' => 'Almost Always',
            'S_NERVOUS' => 'Occasionally',
            'S_SAD' => 'Occasionally',
            'S_SOCIALCONN' => 'Sometimes',
            'S_FRIEND' => 'Sometimes',
            'S_PROSOCIAL' => 'Sometimes',
            'S_PEERCOMM' => 'Sometimes',
            'S_POSADULT' => 'Occasionally',
            'S_SCHOOLCONN' => 'Occasionally,Check box if NOT confident in rating',
            'S_O_COMMCONN_CL1' => 'Sometimes', // This was previously unanswered due to field name mismatch
            
            // Supports Outside of School
            'O_RECIPROCAL' => 'Almost Always',
            'O_ADULTBEST' => 'Almost Always',
            'O_TALK' => 'Almost Always',
            'O_ROUTINE' => 'Frequently',
            'O_FAMILY' => 'Sometimes',
            'O_RESOURCE' => 'Sometimes',
        ]);

        $domains = ['Academic Skills', 'Behavior', 'Physical Health', 'Social & Emotional Well-Being', 'Supports Outside of School'];
        
        // Process all domains and verify that previously problematic fields now appear
        $allProcessedItems = [];
        foreach ($domains as $domain) {
            $result = $this->crossLoadedService->processDomainItems($report, $domain, $domains);
            $domainItems = array_merge($result['strengths'], $result['monitor'], $result['concerns']);
            $allProcessedItems = array_merge($allProcessedItems, $domainItems);
        }
        
        $allItemsText = implode(' ', $allProcessedItems);
        
        // Verify that all previously problematic fields now appear in the results
        $this->assertStringContainsString('understands directions', $allItemsText,
            'Directions field should appear after field name correction');
        $this->assertStringContainsString('verbally aggressive behavior', $allItemsText,
            'Verbal aggression field should appear after field name correction');
        $this->assertStringContainsString('physically aggressive behavior', $allItemsText,
            'Physical aggression field should appear after field name correction');
        $this->assertStringContainsString('oral health appears to be addressed', $allItemsText,
            'Oral health field should appear after field name correction');
        $this->assertStringContainsString('physical health appears to be addressed', $allItemsText,
            'Physical health field should appear after field name correction');
        $this->assertStringContainsString('basic hygiene needs', $allItemsText,
            'Hygiene field should appear after field name correction (with Excel spelling)');
        $this->assertStringContainsString('sense of connection in their community', $allItemsText,
            'Community connection field should appear after field name correction');
        
        // Verify that confidence indicators are preserved
        $this->assertStringContainsString('Almost always able to hear information. *', $allItemsText,
            'Confidence indicators should be preserved');
        $this->assertStringContainsString('Sometimes appears to have the resources to address basic hygiene needs. *', $allItemsText,
            'Confidence indicators should be preserved for hygiene field');
        
        // Count total items processed to ensure we're not missing data
        $this->assertGreaterThan(30, count($allProcessedItems),
            'Should process a significant number of items from the imported data');
    }

    /** @test */
    public function it_handles_empty_and_invalid_values_correctly()
    {
        // Create a report with various empty and invalid values
        $report = ReportData::create([
            'FN_STUDENT' => 'TestStudent',
            'LN_STUDENT' => 'TestLastName',
            
            // Valid values
            'A_READ' => 'Almost Always',
            'A_WRITE' => 'Frequently',
            
            // Empty values (should not appear in results)
            'A_MATH' => '',
            'A_ENGAGE' => null,
            
            // Invalid values (should not appear in results)
            'A_INTEREST' => '-99',
            'A_PERSIST' => '   ', // Whitespace only
            
            // Valid values with extra whitespace (should be processed)
            'S_CONTENT' => '  Sometimes  ',
            'S_NERVOUS' => 'Occasionally ',
        ]);

        $academicResult = $this->crossLoadedService->processDomainItems($report, 'Academic Skills', []);
        $academicItems = array_merge($academicResult['strengths'], $academicResult['monitor'], $academicResult['concerns']);
        $academicText = implode(' ', $academicItems);
        
        // Valid values should appear
        $this->assertStringContainsString('reading skills', $academicText);
        $this->assertStringContainsString('writing skills', $academicText);
        
        // Empty and invalid values should not appear
        $this->assertStringNotContainsString('math skills', $academicText);
        $this->assertStringNotContainsString('engaged in academic activities', $academicText);
        $this->assertStringNotContainsString('interest in learning activities', $academicText);
        $this->assertStringNotContainsString('persists with challenging tasks', $academicText);
        
        // Values with whitespace should be processed correctly
        $socialResult = $this->crossLoadedService->processDomainItems($report, 'Social & Emotional Well-Being', []);
        $socialItems = array_merge($socialResult['strengths'], $socialResult['monitor'], $socialResult['concerns']);
        $socialText = implode(' ', $socialItems);
        
        $this->assertStringContainsString('appears content', $socialText);
        $this->assertStringContainsString('appears nervous', $socialText);
    }
}