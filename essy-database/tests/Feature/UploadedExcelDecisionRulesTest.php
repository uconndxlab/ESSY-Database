<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\DecisionRulesService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UploadedExcelDecisionRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_decision_rules_service_uses_uploaded_excel_file()
    {
        $decisionService = app(DecisionRulesService::class);
        
        // Set the uploaded Excel path to the test file
        $decisionService->setUploadedExcelPath('../essy.xlsx');
        
        // Test that decision rules are loaded from the uploaded Excel file
        $result = $decisionService->getDecisionText('A_READ', 'Almost Always');
        $this->assertNotNull($result);
        $this->assertStringContains('Almost always meets grade-level expectations for reading skills', $result);
        
        $result = $decisionService->getDecisionText('B_BULLY', 'Sometimes');
        $this->assertNotNull($result);
        $this->assertStringContains('Sometimes bullies/has bullied another student', $result);
    }

    public function test_essential_items_still_work_with_uploaded_excel()
    {
        $decisionService = app(DecisionRulesService::class);
        
        // Set the uploaded Excel path
        $decisionService->setUploadedExcelPath('../essy.xlsx');
        
        // Test that Essential Items still use hardcoded text with bold formatting
        $result = $decisionService->getDecisionText('E_SHARM', 'Frequently');
        $this->assertNotNull($result);
        $this->assertStringContains('Frequently engages in <strong>self-harming behaviors</strong>', $result);
        
        $result = $decisionService->getDecisionText('E_BULLIED', 'Sometimes');
        $this->assertNotNull($result);
        $this->assertStringContains('Has sometimes been <strong>bullied</strong> by other students', $result);
    }

    public function test_fallback_to_database_when_no_uploaded_file()
    {
        $decisionService = app(DecisionRulesService::class);
        
        // Don't set uploaded Excel path - should fall back to database
        $result = $decisionService->getDecisionText('NONEXISTENT_ITEM', 'Sometimes');
        $this->assertNull($result); // Should return null when not found in database either
    }
}