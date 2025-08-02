<?php

namespace Tests\Feature;

use App\Models\ReportData;
use App\Services\CrossLoadedDomainService;
use App\Services\DecisionRulesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comprehensive test cases for field name corrections
 * 
 * This test class validates that the field name corrections implemented
 * to fix the "unanswered items" bug are working correctly.
 */
class FieldNameCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private CrossLoadedDomainService $crossLoadedService;
    private DecisionRulesService $decisionRulesService;
    private ReportData $testReport;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->crossLoadedService = new CrossLoadedDomainService();
        $this->decisionRulesService = new DecisionRulesService($this->crossLoadedService);
        
        // Create test report data with corrected field names
        $this->testReport = ReportData::create([
            'FN_STUDENT' => 'Aaron',
            'LN_STUDENT' => 'Garcia',
            'FN_TEACHER' => 'Holly',
            'LN_TEACHER' => 'Reeves',
            'SCHOOL' => 'North School',
            
            // Academic Skills - using corrected field names
            'A_READ' => 'Almost Always',
            'A_WRITE' => 'Almost Always', 
            'A_MATH' => 'Almost Always',
            'A_P_S_ARTICULATE_CL1' => 'Almost Always',
            'A_S_ADULTCOMM_CL1' => 'Sometimes',
            'A_B_DIRECTIONS_CL1' => 'Frequently', // Corrected from A_DIRECTIONS
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
            
            // Behavior - using corrected field names
            'A_B_CLASSEXPECT_CL2' => '',
            'A_B_IMPULSE_CL2' => '',
            'B_CLINGY' => 'Almost Never',
            'B_SNEAK' => 'Sometimes',
            'B_VERBAGGRESS' => 'Sometimes', // Corrected from BEH_VERBAGGRESS
            'B_PHYSAGGRESS' => 'Almost Never', // Corrected from BEH_PHYSAGGRESS
            'B_DESTRUCT' => 'Sometimes',
            'B_BULLY' => 'Frequently',
            'B_PUNITIVE' => 'Almost Never',
            'B_O_HOUSING_CL1' => 'Frequently',
            'B_O_FAMSTRESS_CL1' => 'Almost Always',
            'B_O_NBHDSTRESS_CL1' => 'Almost Always',
            
            // Physical Health - using corrected field names
            'P_SIGHT' => 'Almost Always',
            'P_HEAR' => 'Almost Always',
            'A_P_S_ARTICULATE_CL2' => '',
            'P_ORAL' => 'Sometimes', // Corrected from A_ORAL
            'P_PHYS' => 'Sometimes', // Corrected from A_PHYS
            'P_PARTICIPATE' => 'Sometimes',
            'S_P_ACHES_CL1' => 'Occasionally',
            'O_P_HUNGER_CL1' => 'Sometimes',
            'O_P_HYGEINE_CL1' => 'Sometimes', // Using Excel spelling (HYGEINE)
            'O_P_CLOTHES_CL1' => 'Almost Never',
            
            // Social & Emotional Well-Being - using corrected field names
            'S_CONTENT' => 'Almost Always',
            'A_S_CONFIDENT_CL2' => '',
            'A_S_POSOUT_CL2' => '',
            'S_P_ACHES_CL2' => '',
            'S_NERVOUS' => 'Occasionally',
            'S_SAD' => 'Occasionally',
            'S_SOCIALCONN' => 'Sometimes',
            'S_FRIEND' => 'Sometimes',
            'S_PROSOCIAL' => 'Sometimes',
            'S_PEERCOMM' => 'Sometimes',
            'A_S_ADULTCOMM_CL2' => '',
            'A_P_S_ARTICULATE_CL3' => '', // New field added
            'S_POSADULT' => 'Occasionally',
            'S_SCHOOLCONN' => 'Occasionally',
            'S_O_COMMCONN_CL1' => 'Sometimes', // Corrected from S_COMMCONN
            'A_S_O_ACTIVITY_CL2' => '',
            
            // Supports Outside of School - using corrected field names
            'O_RECIPROCAL' => 'Almost Always',
            'O_POSADULT' => '',
            'O_ADULTBEST' => 'Almost Always',
            'O_TALK' => 'Almost Always',
            'O_ROUTINE' => 'Frequently',
            'O_FAMILY' => 'Sometimes',
            'O_P_HUNGER_CL2' => '',
            'O_P_HYGEINE_CL2' => '', // Using Excel spelling
            'O_P_CLOTHES_CL2' => '',
            'O_RESOURCE' => 'Sometimes',
            'B_O_HOUSING_CL2' => '',
            'B_O_FAMSTRESS_CL2' => '',
            'B_O_NBHDSTRESS_CL2' => '',
            'A_S_O_ACTIVITY_CL3' => '',
            'S_O_COMMCONN_CL2' => '', // New cross-loaded variant
        ]);
    }

    /** @test */
    public function it_can_access_corrected_field_names()
    {
        // Test that previously problematic fields can now be accessed
        $this->assertEquals('Frequently', $this->testReport->A_B_DIRECTIONS_CL1);
        $this->assertEquals('Sometimes', $this->testReport->B_VERBAGGRESS);
        $this->assertEquals('Almost Never', $this->testReport->B_PHYSAGGRESS);
        $this->assertEquals('Sometimes', $this->testReport->P_ORAL);
        $this->assertEquals('Sometimes', $this->testReport->P_PHYS);
        $this->assertEquals('Sometimes', $this->testReport->S_O_COMMCONN_CL1);
        $this->assertEquals('Sometimes', $this->testReport->O_P_HYGEINE_CL1);
    }

    /** @test */
    public function it_processes_academic_skills_domain_correctly()
    {
        $concernDomains = ['Academic Skills'];
        $result = $this->crossLoadedService->processDomainItems($this->testReport, 'Academic Skills', $concernDomains);
        
        // Verify that the result has the expected structure
        $this->assertArrayHasKey('strengths', $result);
        $this->assertArrayHasKey('monitor', $result);
        $this->assertArrayHasKey('concerns', $result);
        
        // Combine all items to check for specific field processing
        $allItems = array_merge($result['strengths'], $result['monitor'], $result['concerns']);
        $allItemsText = implode(' ', $allItems);
        
        // Verify that corrected field names are processed correctly
        $this->assertStringContainsString('meets grade-level expectations for reading skills', $allItemsText);
        $this->assertStringContainsString('understands directions', $allItemsText);
        $this->assertStringContainsString('articulates clearly enough to be understood', $allItemsText);
        
        // Verify that items appear in appropriate categories based on their values
        $strengthsText = implode(' ', $result['strengths']);
        $this->assertStringContainsString('Almost always meets grade-level expectations for reading skills', $strengthsText);
    }

    /** @test */
    public function it_processes_behavior_domain_correctly()
    {
        $concernDomains = ['Behavior'];
        $result = $this->crossLoadedService->processDomainItems($this->testReport, 'Behavior', $concernDomains);
        
        // Verify that the result has the expected structure
        $this->assertArrayHasKey('strengths', $result);
        $this->assertArrayHasKey('monitor', $result);
        $this->assertArrayHasKey('concerns', $result);
        
        // Combine all items to check for specific field processing
        $allItems = array_merge($result['strengths'], $result['monitor'], $result['concerns']);
        $allItemsText = implode(' ', $allItems);
        
        // Verify that corrected field names are processed correctly
        $this->assertStringContainsString('engages in verbally aggressive behavior', $allItemsText);
        $this->assertStringContainsString('engages in physically aggressive behavior', $allItemsText);
        $this->assertStringContainsString('bullies/has bullied another student', $allItemsText);
        
        // Verify that items appear in appropriate categories
        $concernsText = implode(' ', $result['concerns']);
        $this->assertStringContainsString('Frequently bullies/has bullied another student', $concernsText);
    }

    /** @test */
    public function it_processes_physical_health_domain_correctly()
    {
        $concernDomains = ['Physical Health'];
        $result = $this->crossLoadedService->processDomainItems($this->testReport, 'Physical Health', $concernDomains);
        
        // Verify that the result has the expected structure
        $this->assertArrayHasKey('strengths', $result);
        $this->assertArrayHasKey('monitor', $result);
        $this->assertArrayHasKey('concerns', $result);
        
        // Combine all items to check for specific field processing
        $allItems = array_merge($result['strengths'], $result['monitor'], $result['concerns']);
        $allItemsText = implode(' ', $allItems);
        
        // Verify that corrected field names are processed correctly
        $this->assertStringContainsString('oral health appears to be addressed', $allItemsText);
        $this->assertStringContainsString('physical health appears to be addressed', $allItemsText);
        $this->assertStringContainsString('basic hygiene needs', $allItemsText);
        
        // Verify that items appear in appropriate categories
        $strengthsText = implode(' ', $result['strengths']);
        $this->assertStringContainsString('Almost always able to see', $strengthsText);
    }

    /** @test */
    public function it_processes_social_emotional_domain_correctly()
    {
        $concernDomains = ['Social & Emotional Well-Being'];
        $result = $this->crossLoadedService->processDomainItems($this->testReport, 'Social & Emotional Well-Being', $concernDomains);
        
        // Verify that the result has the expected structure
        $this->assertArrayHasKey('strengths', $result);
        $this->assertArrayHasKey('monitor', $result);
        $this->assertArrayHasKey('concerns', $result);
        
        // Combine all items to check for specific field processing
        $allItems = array_merge($result['strengths'], $result['monitor'], $result['concerns']);
        $allItemsText = implode(' ', $allItems);
        
        // Verify that corrected field names are processed correctly
        $this->assertStringContainsString('sense of connection in their community', $allItemsText);
        $this->assertStringContainsString('appears content', $allItemsText);
        $this->assertStringContainsString('appears nervous, worried, tense, or fearful', $allItemsText);
        
        // Verify that items appear in appropriate categories
        $strengthsText = implode(' ', $result['strengths']);
        $this->assertStringContainsString('Almost always appears content', $strengthsText);
    }

    /** @test */
    public function it_handles_cross_loaded_items_correctly()
    {
        $concernDomains = ['Academic Skills', 'Physical Health'];
        
        // Test directions cross-loaded group
        $directionsResult = $this->crossLoadedService->processDomainItems($this->testReport, 'Academic Skills', $concernDomains);
        $allDirectionsItems = array_merge($directionsResult['strengths'], $directionsResult['monitor'], $directionsResult['concerns']);
        $directionsText = implode(' ', $allDirectionsItems);
        $this->assertStringContainsString('understands directions', $directionsText);
        
        // Test articulate cross-loaded group - should have dagger since it appears in both domains
        $articulateResult = $this->crossLoadedService->processDomainItems($this->testReport, 'Academic Skills', $concernDomains);
        $allArticulateItems = array_merge($articulateResult['strengths'], $articulateResult['monitor'], $articulateResult['concerns']);
        $articulateText = implode(' ', $allArticulateItems);
        $this->assertStringContainsString('articulates clearly enough to be understood. †', $articulateText);
        
        // Test community connection cross-loaded group
        $commResult = $this->crossLoadedService->processDomainItems($this->testReport, 'Social & Emotional Well-Being', $concernDomains);
        $allCommItems = array_merge($commResult['strengths'], $commResult['monitor'], $commResult['concerns']);
        $commText = implode(' ', $allCommItems);
        $this->assertStringContainsString('sense of connection in their community', $commText);
    }

    /** @test */
    public function it_correctly_identifies_fields_requiring_dagger()
    {
        $concernDomains = ['Academic Skills', 'Physical Health'];
        $fieldsRequiringDagger = $this->crossLoadedService->getFieldsRequiringDagger($concernDomains);
        
        // Articulate fields should require dagger since they appear in both Academic Skills and Physical Health
        $this->assertArrayHasKey('A_P_S_ARTICULATE_CL1', $fieldsRequiringDagger);
        $this->assertArrayHasKey('A_P_S_ARTICULATE_CL2', $fieldsRequiringDagger);
    }

    /** @test */
    public function it_retrieves_decision_rules_with_corrected_field_names()
    {
        // Create some test decision rules first
        \App\Models\DecisionRule::create([
            'item_code' => 'A_READ',
            'frequency' => 'Almost Always',
            'domain' => 'Academic Skills',
            'decision_text' => 'Almost always meets grade-level expectations for reading skills.'
        ]);
        
        \App\Models\DecisionRule::create([
            'item_code' => 'A_B_DIRECTIONS_CL1',
            'frequency' => 'Frequently',
            'domain' => 'Academic Skills',
            'decision_text' => 'Frequently understands directions.'
        ]);
        
        // Test decision rule lookup with corrected field names
        $decisionText = $this->decisionRulesService->getDecisionText('A_READ', 'Almost Always');
        $this->assertNotNull($decisionText);
        $this->assertStringContainsString('reading skills', $decisionText);
        
        $decisionText = $this->decisionRulesService->getDecisionText('A_B_DIRECTIONS_CL1', 'Frequently');
        $this->assertNotNull($decisionText);
        $this->assertStringContainsString('directions', $decisionText);
    }

    /** @test */
    public function it_does_not_show_items_with_data_as_unanswered()
    {
        // Process all domains to ensure items with data are not marked as unanswered
        $domains = ['Academic Skills', 'Behavior', 'Physical Health', 'Social & Emotional Well-Being', 'Supports Outside of School'];
        
        foreach ($domains as $domain) {
            $result = $this->crossLoadedService->processDomainItems($this->testReport, $domain, $domains);
            
            // Check that fields with actual values are processed correctly
            foreach ($result as $field => $data) {
                if (!empty($data['value']) && $data['value'] !== '-99') {
                    $this->assertNotEmpty($data['message'], "Field {$field} should have a message");
                    $this->assertNotEmpty($data['value'], "Field {$field} should have a value");
                }
            }
        }
    }

    /** @test */
    public function it_handles_excel_spelling_variations_correctly()
    {
        // Test that the Excel spelling of HYGEINE is handled correctly
        $result = $this->crossLoadedService->processDomainItems($this->testReport, 'Physical Health', []);
        
        $allItems = array_merge($result['strengths'], $result['monitor'], $result['concerns']);
        $allItemsText = implode(' ', $allItems);
        
        $this->assertStringContainsString('basic hygiene needs', $allItemsText);
    }

    /** @test */
    public function it_validates_field_to_domain_mapping_completeness()
    {
        $fieldToDomainMap = $this->crossLoadedService->getFieldToDomainMap();
        
        // Verify that all corrected field names have domain mappings
        $this->assertArrayHasKey('A_B_DIRECTIONS_CL1', $fieldToDomainMap);
        $this->assertArrayHasKey('A_B_DIRECTIONS_CL2', $fieldToDomainMap);
        $this->assertArrayHasKey('B_VERBAGGRESS', $fieldToDomainMap);
        $this->assertArrayHasKey('B_PHYSAGGRESS', $fieldToDomainMap);
        $this->assertArrayHasKey('P_ORAL', $fieldToDomainMap);
        $this->assertArrayHasKey('P_PHYS', $fieldToDomainMap);
        $this->assertArrayHasKey('S_O_COMMCONN_CL1', $fieldToDomainMap);
        $this->assertArrayHasKey('S_O_COMMCONN_CL2', $fieldToDomainMap);
        $this->assertArrayHasKey('O_P_HYGEINE_CL1', $fieldToDomainMap);
        $this->assertArrayHasKey('O_P_HYGEINE_CL2', $fieldToDomainMap);
        
        // Verify correct domain assignments
        $this->assertEquals('Academic Skills', $fieldToDomainMap['A_B_DIRECTIONS_CL1']);
        $this->assertEquals('Academic Skills', $fieldToDomainMap['A_B_DIRECTIONS_CL2']);
        $this->assertEquals('Behavior', $fieldToDomainMap['B_VERBAGGRESS']);
        $this->assertEquals('Behavior', $fieldToDomainMap['B_PHYSAGGRESS']);
        $this->assertEquals('Physical Health', $fieldToDomainMap['P_ORAL']);
        $this->assertEquals('Physical Health', $fieldToDomainMap['P_PHYS']);
        $this->assertEquals('Social & Emotional Well-Being', $fieldToDomainMap['S_O_COMMCONN_CL1']);
        $this->assertEquals('Social & Emotional Well-Being', $fieldToDomainMap['S_O_COMMCONN_CL2']);
        $this->assertEquals('Physical Health', $fieldToDomainMap['O_P_HYGEINE_CL1']);
        $this->assertEquals('Supports Outside of School', $fieldToDomainMap['O_P_HYGEINE_CL2']);
    }

    /** @test */
    public function it_processes_real_imported_data_correctly()
    {
        // Create a test report that simulates the imported data structure
        $simulatedImportedReport = ReportData::create([
            'FN_STUDENT' => 'TestStudent',
            'LN_STUDENT' => 'TestLastName',
            'A_READ' => 'Almost Always',
            'A_B_DIRECTIONS_CL1' => 'Frequently',
            'B_VERBAGGRESS' => 'Sometimes',
            'B_PHYSAGGRESS' => 'Almost Never',
            'P_ORAL' => 'Sometimes',
            'P_PHYS' => 'Sometimes',
        ]);
        
        // Test that the imported data can be processed correctly
        $concernDomains = ['Academic Skills', 'Behavior', 'Physical Health'];
        
        // Process Academic Skills domain
        $academicResult = $this->crossLoadedService->processDomainItems($simulatedImportedReport, 'Academic Skills', $concernDomains);
        $academicText = implode(' ', array_merge($academicResult['strengths'], $academicResult['monitor'], $academicResult['concerns']));
        $this->assertStringContainsString('reading skills', $academicText);
        $this->assertStringContainsString('understands directions', $academicText);
        
        // Process Behavior domain
        $behaviorResult = $this->crossLoadedService->processDomainItems($simulatedImportedReport, 'Behavior', $concernDomains);
        $behaviorText = implode(' ', array_merge($behaviorResult['strengths'], $behaviorResult['monitor'], $behaviorResult['concerns']));
        $this->assertStringContainsString('verbally aggressive behavior', $behaviorText);
        $this->assertStringContainsString('physically aggressive behavior', $behaviorText);
        
        // Process Physical Health domain
        $physicalResult = $this->crossLoadedService->processDomainItems($simulatedImportedReport, 'Physical Health', $concernDomains);
        $physicalText = implode(' ', array_merge($physicalResult['strengths'], $physicalResult['monitor'], $physicalResult['concerns']));
        $this->assertStringContainsString('oral health', $physicalText);
        $this->assertStringContainsString('physical health', $physicalText);
    }
}