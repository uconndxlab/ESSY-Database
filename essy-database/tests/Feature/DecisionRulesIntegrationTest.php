<?php

namespace Tests\Feature;

use App\Models\DecisionRule;
use App\Models\ReportData;
use App\Services\CrossLoadedDomainService;
use App\Services\DecisionRulesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DecisionRulesIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_end_to_end_report_generation_with_decision_rules(): void
    {
        // Enable decision rules
        config(['essy.use_decision_rules' => true]);
        
        // Create multiple decision rules for different domains and frequencies
        DecisionRule::create([
            'item_code' => 'A_READ',
            'frequency' => 'Sometimes',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student sometimes meets grade-level expectations for reading skills'
        ]);
        
        DecisionRule::create([
            'item_code' => 'A_MATH',
            'frequency' => 'Frequently',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student frequently meets expectations for grade-level math skills'
        ]);
        
        DecisionRule::create([
            'item_code' => 'B_CLINGY',
            'frequency' => 'Occasionally',
            'domain' => 'Behavior',
            'decision_text' => 'The student occasionally exhibits overly clingy or attention-seeking behaviors'
        ]);
        
        // Create a comprehensive test report
        $report = ReportData::create([
            'FN_STUDENT' => 'John',
            'LN_STUDENT' => 'Doe',
            'FN_TEACHER' => 'Jane',
            'LN_TEACHER' => 'Smith',
            'SCHOOL' => 'Test Elementary',
            'EndDate' => now(),
            'A_DOMAIN' => 'an area of some concern',
            'B_DOMAIN' => 'an area of some concern',
            'S_DOMAIN' => 'neither an area of concern or strength',
            'P_DOMAIN' => 'an area of substantial strength',
            'O_DOMAIN' => 'an area of substantial strength',
            'ATT_DOMAIN' => 'neither an area of concern or strength',
            'A_READ' => 'Sometimes',
            'A_MATH' => 'Frequently',
            'B_CLINGY' => 'Occasionally',
            'DEM_RACE' => 'White',
            'DEM_ETHNIC' => 'No',
            'DEM_GRADE' => '3rd',
            'DEM_GENDER' => 'Male',
            'DEM_CLASSTEACH' => 'Ms. Johnson',
            'DEM_IEP' => 'No',
            'DEM_504' => 'No',
            'DEM_ELL' => 'No',
            'DEM_CI' => 'No',
            'RELATION_CLOSE' => 'Positive',
            'RELATION_CONFLICT' => 'No conflict',
            'SPEEDING_GATE1' => 0,
            'SPEEDING_ESS' => 0,
            'SPEEDING_GATE2' => 0,
            'E_SHARM' => 'Almost never',
            'E_BULLIED' => 'Almost never',
            'E_EXCLUDE' => 'Almost never',
            'E_WITHDRAW' => 'Almost never',
            'E_REGULATE' => 'Almost always',
            'E_RESTED' => 'Almost always',
        ]);
        
        // Render the view
        $response = $this->get("/reports/print/{$report->id}");
        
        $response->assertStatus(200);
        $response->assertSee('The student sometimes meets grade-level expectations for reading skills');
        $response->assertSee('The student frequently meets expectations for grade-level math skills');
        $response->assertSee('The student occasionally exhibits overly clingy or attention-seeking behaviors');
    }

    public function test_cross_loaded_domain_functionality_preservation(): void
    {
        // Enable decision rules
        config(['essy.use_decision_rules' => true]);
        
        // Create decision rules for cross-loaded items
        DecisionRule::create([
            'item_code' => 'A_P_S_ARTICULATE_CL1',
            'frequency' => 'Sometimes',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student sometimes articulates clearly enough to be understood'
        ]);
        
        DecisionRule::create([
            'item_code' => 'A_S_ADULTCOMM_CL1',
            'frequency' => 'Frequently',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student frequently communicates with adults effectively'
        ]);
        
        // Create a test report with cross-loaded concerns (both Academic and Physical Health are concerns)
        $report = ReportData::create([
            'FN_STUDENT' => 'John',
            'LN_STUDENT' => 'Doe',
            'FN_TEACHER' => 'Jane',
            'LN_TEACHER' => 'Smith',
            'SCHOOL' => 'Test Elementary',
            'EndDate' => now(),
            'A_DOMAIN' => 'an area of some concern',
            'B_DOMAIN' => 'an area of substantial strength',
            'S_DOMAIN' => 'an area of some concern', // This makes ADULTCOMM cross-loaded
            'P_DOMAIN' => 'an area of some concern', // This makes ARTICULATE cross-loaded
            'O_DOMAIN' => 'an area of substantial strength',
            'ATT_DOMAIN' => 'neither an area of concern or strength',
            'A_P_S_ARTICULATE_CL1' => 'Sometimes',
            'A_S_ADULTCOMM_CL1' => 'Frequently',
            'DEM_RACE' => 'White',
            'DEM_ETHNIC' => 'No',
            'DEM_GRADE' => '3rd',
            'DEM_GENDER' => 'Male',
            'DEM_CLASSTEACH' => 'Ms. Johnson',
            'DEM_IEP' => 'No',
            'DEM_504' => 'No',
            'DEM_ELL' => 'No',
            'DEM_CI' => 'No',
            'RELATION_CLOSE' => 'Positive',
            'RELATION_CONFLICT' => 'No conflict',
            'SPEEDING_GATE1' => 0,
            'SPEEDING_ESS' => 0,
            'SPEEDING_GATE2' => 0,
            'E_SHARM' => 'Almost never',
            'E_BULLIED' => 'Almost never',
            'E_EXCLUDE' => 'Almost never',
            'E_WITHDRAW' => 'Almost never',
            'E_REGULATE' => 'Almost always',
            'E_RESTED' => 'Almost always',
        ]);
        
        // Render the view
        $response = $this->get("/reports/print/{$report->id}");
        
        $response->assertStatus(200);
        // Should contain decision rule text with dagger symbols for cross-loaded items
        $response->assertSee('The student sometimes articulates clearly enough to be understood †');
        $response->assertSee('The student frequently communicates with adults effectively †');
    }

    public function test_confidence_indicator_and_dagger_symbol_handling(): void
    {
        // Enable decision rules
        config(['essy.use_decision_rules' => true]);
        
        // Create decision rules for cross-loaded items
        DecisionRule::create([
            'item_code' => 'A_P_S_ARTICULATE_CL1',
            'frequency' => 'Sometimes',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student sometimes articulates clearly enough to be understood'
        ]);
        
        // Create a test report with confidence flag and cross-loaded concern
        $report = ReportData::create([
            'FN_STUDENT' => 'John',
            'LN_STUDENT' => 'Doe',
            'FN_TEACHER' => 'Jane',
            'LN_TEACHER' => 'Smith',
            'SCHOOL' => 'Test Elementary',
            'EndDate' => now(),
            'A_DOMAIN' => 'an area of some concern',
            'B_DOMAIN' => 'an area of substantial strength',
            'S_DOMAIN' => 'neither an area of concern or strength',
            'P_DOMAIN' => 'an area of some concern', // This makes articulate cross-loaded
            'O_DOMAIN' => 'an area of substantial strength',
            'ATT_DOMAIN' => 'neither an area of concern or strength',
            'A_P_S_ARTICULATE_CL1' => 'Sometimes, Check here if you have less confidence in this response',
            'DEM_RACE' => 'White',
            'DEM_ETHNIC' => 'No',
            'DEM_GRADE' => '3rd',
            'DEM_GENDER' => 'Male',
            'DEM_CLASSTEACH' => 'Ms. Johnson',
            'DEM_IEP' => 'No',
            'DEM_504' => 'No',
            'DEM_ELL' => 'No',
            'DEM_CI' => 'No',
            'RELATION_CLOSE' => 'Positive',
            'RELATION_CONFLICT' => 'No conflict',
            'SPEEDING_GATE1' => 0,
            'SPEEDING_ESS' => 0,
            'SPEEDING_GATE2' => 0,
            'E_SHARM' => 'Almost never',
            'E_BULLIED' => 'Almost never',
            'E_EXCLUDE' => 'Almost never',
            'E_WITHDRAW' => 'Almost never',
            'E_REGULATE' => 'Almost always',
            'E_RESTED' => 'Almost always',
        ]);
        
        // Render the view
        $response = $this->get("/reports/print/{$report->id}");
        
        $response->assertStatus(200);
        // Should contain decision rule text with both confidence (*) and dagger (†) symbols in correct order
        $response->assertSee('The student sometimes articulates clearly enough to be understood * †');
    }

    public function test_scenarios_when_decision_rules_are_missing(): void
    {
        // Enable decision rules
        config(['essy.use_decision_rules' => true]);
        
        // Create only one decision rule, leaving others to fall back
        DecisionRule::create([
            'item_code' => 'A_READ',
            'frequency' => 'Sometimes',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student sometimes meets grade-level expectations for reading skills'
        ]);
        
        // Create a test report with items that have and don't have decision rules
        $report = ReportData::create([
            'FN_STUDENT' => 'John',
            'LN_STUDENT' => 'Doe',
            'FN_TEACHER' => 'Jane',
            'LN_TEACHER' => 'Smith',
            'SCHOOL' => 'Test Elementary',
            'EndDate' => now(),
            'A_DOMAIN' => 'an area of some concern',
            'B_DOMAIN' => 'an area of some concern',
            'S_DOMAIN' => 'neither an area of concern or strength',
            'P_DOMAIN' => 'an area of substantial strength',
            'O_DOMAIN' => 'an area of substantial strength',
            'ATT_DOMAIN' => 'neither an area of concern or strength',
            'A_READ' => 'Sometimes', // Has decision rule
            'A_MATH' => 'Frequently', // No decision rule - should fall back
            'B_CLINGY' => 'Occasionally', // No decision rule - should fall back
            'DEM_RACE' => 'White',
            'DEM_ETHNIC' => 'No',
            'DEM_GRADE' => '3rd',
            'DEM_GENDER' => 'Male',
            'DEM_CLASSTEACH' => 'Ms. Johnson',
            'DEM_IEP' => 'No',
            'DEM_504' => 'No',
            'DEM_ELL' => 'No',
            'DEM_CI' => 'No',
            'RELATION_CLOSE' => 'Positive',
            'RELATION_CONFLICT' => 'No conflict',
            'SPEEDING_GATE1' => 0,
            'SPEEDING_ESS' => 0,
            'SPEEDING_GATE2' => 0,
            'E_SHARM' => 'Almost never',
            'E_BULLIED' => 'Almost never',
            'E_EXCLUDE' => 'Almost never',
            'E_WITHDRAW' => 'Almost never',
            'E_REGULATE' => 'Almost always',
            'E_RESTED' => 'Almost always',
        ]);
        
        // Render the view
        $response = $this->get("/reports/print/{$report->id}");
        
        $response->assertStatus(200);
        // Should use decision rule for A_READ
        $response->assertSee('The student sometimes meets grade-level expectations for reading skills');
        // Should skip items without decision rules
        $response->assertSee('Frequently'); // Should see the frequency prefix for items
        $response->assertSee('Occasionally'); // Should see the frequency prefix for items
    }

    public function test_feature_toggle_functionality(): void
    {
        // Create decision rules
        DecisionRule::create([
            'item_code' => 'A_READ',
            'frequency' => 'Sometimes',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student sometimes meets grade-level expectations for reading skills'
        ]);
        
        // Create a test report
        $report = ReportData::create([
            'FN_STUDENT' => 'John',
            'LN_STUDENT' => 'Doe',
            'FN_TEACHER' => 'Jane',
            'LN_TEACHER' => 'Smith',
            'SCHOOL' => 'Test Elementary',
            'EndDate' => now(),
            'A_DOMAIN' => 'an area of some concern',
            'B_DOMAIN' => 'an area of substantial strength',
            'S_DOMAIN' => 'neither an area of concern or strength',
            'P_DOMAIN' => 'an area of substantial strength',
            'O_DOMAIN' => 'an area of substantial strength',
            'ATT_DOMAIN' => 'neither an area of concern or strength',
            'A_READ' => 'Sometimes',
            'DEM_RACE' => 'White',
            'DEM_ETHNIC' => 'No',
            'DEM_GRADE' => '3rd',
            'DEM_GENDER' => 'Male',
            'DEM_CLASSTEACH' => 'Ms. Johnson',
            'DEM_IEP' => 'No',
            'DEM_504' => 'No',
            'DEM_ELL' => 'No',
            'DEM_CI' => 'No',
            'RELATION_CLOSE' => 'Positive',
            'RELATION_CONFLICT' => 'No conflict',
            'SPEEDING_GATE1' => 0,
            'SPEEDING_ESS' => 0,
            'SPEEDING_GATE2' => 0,
            'E_SHARM' => 'Almost never',
            'E_BULLIED' => 'Almost never',
            'E_EXCLUDE' => 'Almost never',
            'E_WITHDRAW' => 'Almost never',
            'E_REGULATE' => 'Almost always',
            'E_RESTED' => 'Almost always',
        ]);
        
        // Test with decision rules enabled
        config(['essy.use_decision_rules' => true]);
        $response = $this->get("/reports/print/{$report->id}");
        $response->assertStatus(200);
        $response->assertSee('The student sometimes meets grade-level expectations for reading skills');
        
        // Test with decision rules disabled
        config(['essy.use_decision_rules' => false]);
        $response = $this->get("/reports/print/{$report->id}");
        $response->assertStatus(200);
        // Should use concatenation approach instead of decision rules
        $response->assertSee('Sometimes');
    }

    public function test_multiple_domain_processing_with_decision_rules(): void
    {
        // Enable decision rules
        config(['essy.use_decision_rules' => true]);
        
        // Create decision rules for multiple domains
        DecisionRule::create([
            'item_code' => 'A_READ',
            'frequency' => 'Sometimes',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student sometimes meets grade-level expectations for reading skills'
        ]);
        
        DecisionRule::create([
            'item_code' => 'B_CLINGY',
            'frequency' => 'Occasionally',
            'domain' => 'Behavior',
            'decision_text' => 'The student occasionally exhibits overly clingy or attention-seeking behaviors'
        ]);
        
        DecisionRule::create([
            'item_code' => 'S_NERVOUS',
            'frequency' => 'Frequently',
            'domain' => 'Social & Emotional Well-Being',
            'decision_text' => 'The student frequently appears nervous, worried, tense, or fearful'
        ]);
        
        DecisionRule::create([
            'item_code' => 'P_SIGHT',
            'frequency' => 'Sometimes',
            'domain' => 'Physical Health',
            'decision_text' => 'The student sometimes is able to see, from a distance or up close'
        ]);
        
        DecisionRule::create([
            'item_code' => 'O_RECIPROCAL',
            'frequency' => 'Almost Never',
            'domain' => 'Supports Outside of School',
            'decision_text' => 'Family-school communication is almost never reciprocal'
        ]);
        
        // Create a test report with all domains as concerns
        $report = ReportData::create([
            'FN_STUDENT' => 'John',
            'LN_STUDENT' => 'Doe',
            'FN_TEACHER' => 'Jane',
            'LN_TEACHER' => 'Smith',
            'SCHOOL' => 'Test Elementary',
            'EndDate' => now(),
            'A_DOMAIN' => 'an area of some concern',
            'B_DOMAIN' => 'an area of some concern',
            'S_DOMAIN' => 'an area of some concern',
            'P_DOMAIN' => 'an area of some concern',
            'O_DOMAIN' => 'an area of some concern',
            'ATT_DOMAIN' => 'neither an area of concern or strength',
            'A_READ' => 'Sometimes',
            'B_CLINGY' => 'Occasionally',
            'S_NERVOUS' => 'Frequently',
            'P_SIGHT' => 'Sometimes',
            'O_RECIPROCAL' => 'Almost Never',
            'DEM_RACE' => 'White',
            'DEM_ETHNIC' => 'No',
            'DEM_GRADE' => '3rd',
            'DEM_GENDER' => 'Male',
            'DEM_CLASSTEACH' => 'Ms. Johnson',
            'DEM_IEP' => 'No',
            'DEM_504' => 'No',
            'DEM_ELL' => 'No',
            'DEM_CI' => 'No',
            'RELATION_CLOSE' => 'Positive',
            'RELATION_CONFLICT' => 'No conflict',
            'SPEEDING_GATE1' => 0,
            'SPEEDING_ESS' => 0,
            'SPEEDING_GATE2' => 0,
            'E_SHARM' => 'Almost never',
            'E_BULLIED' => 'Almost never',
            'E_EXCLUDE' => 'Almost never',
            'E_WITHDRAW' => 'Almost never',
            'E_REGULATE' => 'Almost always',
            'E_RESTED' => 'Almost always',
        ]);
        
        // Render the view
        $response = $this->get("/reports/print/{$report->id}");
        
        $response->assertStatus(200);
        // Should contain decision rule text for all domains
        $response->assertSee('The student sometimes meets grade-level expectations for reading skills');
        $response->assertSee('The student occasionally exhibits overly clingy or attention-seeking behaviors');
        $response->assertSee('The student frequently appears nervous, worried, tense, or fearful');
        $response->assertSee('The student sometimes is able to see, from a distance or up close');
        $response->assertSee('Family-school communication is almost never reciprocal');
    }

    public function test_cross_loaded_functionality(): void
    {
        // Enable decision rules
        config(['essy.use_decision_rules' => true]);
        
        // Create decision rule for cross-loaded item
        DecisionRule::create([
            'item_code' => 'A_P_S_ARTICULATE_CL1',
            'frequency' => 'Sometimes',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student sometimes articulates clearly enough to be understood'
        ]);
        
        // Create a test report where the primary field is empty but secondary field has value
        $report = ReportData::create([
            'FN_STUDENT' => 'John',
            'LN_STUDENT' => 'Doe',
            'FN_TEACHER' => 'Jane',
            'LN_TEACHER' => 'Smith',
            'SCHOOL' => 'Test Elementary',
            'EndDate' => now(),
            'A_DOMAIN' => 'an area of some concern',
            'B_DOMAIN' => 'an area of substantial strength',
            'S_DOMAIN' => 'neither an area of concern or strength',
            'P_DOMAIN' => 'an area of some concern', // This makes articulate cross-loaded
            'O_DOMAIN' => 'an area of substantial strength',
            'ATT_DOMAIN' => 'neither an area of concern or strength',
            'A_P_S_ARTICULATE_CL1' => '', // Primary field empty
            'A_P_S_ARTICULATE_CL2' => 'Sometimes', // Secondary field has value
            'DEM_RACE' => 'White',
            'DEM_ETHNIC' => 'No',
            'DEM_GRADE' => '3rd',
            'DEM_GENDER' => 'Male',
            'DEM_CLASSTEACH' => 'Ms. Johnson',
            'DEM_IEP' => 'No',
            'DEM_504' => 'No',
            'DEM_ELL' => 'No',
            'DEM_CI' => 'No',
            'RELATION_CLOSE' => 'Positive',
            'RELATION_CONFLICT' => 'No conflict',
            'SPEEDING_GATE1' => 0,
            'SPEEDING_ESS' => 0,
            'SPEEDING_GATE2' => 0,
            'E_SHARM' => 'Almost never',
            'E_BULLIED' => 'Almost never',
            'E_EXCLUDE' => 'Almost never',
            'E_WITHDRAW' => 'Almost never',
            'E_REGULATE' => 'Almost always',
            'E_RESTED' => 'Almost always',
        ]);
        
        // Render the view
        $response = $this->get("/reports/print/{$report->id}");
        
        $response->assertStatus(200);
        // Should use the secondary field value and apply decision rule with dagger
        $response->assertSee('The student sometimes articulates clearly enough to be understood †');
    }

    public function test_service_integration_with_direct_service_calls(): void
    {
        // Enable decision rules
        config(['essy.use_decision_rules' => true]);
        
        // Create decision rules
        DecisionRule::create([
            'item_code' => 'A_READ',
            'frequency' => 'Sometimes',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student sometimes meets grade-level expectations for reading skills'
        ]);
        
        // Create test report
        $report = ReportData::create([
            'FN_STUDENT' => 'John',
            'LN_STUDENT' => 'Doe',
            'FN_TEACHER' => 'Jane',
            'LN_TEACHER' => 'Smith',
            'SCHOOL' => 'Test Elementary',
            'EndDate' => now(),
            'A_DOMAIN' => 'an area of some concern',
            'B_DOMAIN' => 'an area of substantial strength',
            'S_DOMAIN' => 'neither an area of concern or strength',
            'P_DOMAIN' => 'an area of substantial strength',
            'O_DOMAIN' => 'an area of substantial strength',
            'ATT_DOMAIN' => 'neither an area of concern or strength',
            'A_READ' => 'Sometimes',
            'DEM_RACE' => 'White',
            'DEM_ETHNIC' => 'No',
            'DEM_GRADE' => '3rd',
            'DEM_GENDER' => 'Male',
            'DEM_CLASSTEACH' => 'Ms. Johnson',
            'DEM_IEP' => 'No',
            'DEM_504' => 'No',
            'DEM_ELL' => 'No',
            'DEM_CI' => 'No',
            'RELATION_CLOSE' => 'Positive',
            'RELATION_CONFLICT' => 'No conflict',
            'SPEEDING_GATE1' => 0,
            'SPEEDING_ESS' => 0,
            'SPEEDING_GATE2' => 0,
            'E_SHARM' => 'Almost never',
            'E_BULLIED' => 'Almost never',
            'E_EXCLUDE' => 'Almost never',
            'E_WITHDRAW' => 'Almost never',
            'E_REGULATE' => 'Almost always',
            'E_RESTED' => 'Almost always',
        ]);
        
        // Test direct service integration
        $crossLoadedService = new CrossLoadedDomainService();
        $decisionRulesService = new DecisionRulesService($crossLoadedService);
        
        $concernDomains = ['Academic Skills'];
        $results = $decisionRulesService->processDomainItems($report, 'Academic Skills', $concernDomains);
        
        // Verify the service returns proper structure
        $this->assertIsArray($results);
        $this->assertArrayHasKey('strengths', $results);
        $this->assertArrayHasKey('monitor', $results);
        $this->assertArrayHasKey('concerns', $results);
        
        // Verify decision rule text is used
        $allItems = array_merge($results['strengths'], $results['monitor'], $results['concerns']);
        $foundDecisionRuleText = false;
        foreach ($allItems as $item) {
            if (str_contains($item, 'The student sometimes meets grade-level expectations for reading skills')) {
                $foundDecisionRuleText = true;
                break;
            }
        }
        $this->assertTrue($foundDecisionRuleText, 'Decision rule text should be found in processed items');
    }
}