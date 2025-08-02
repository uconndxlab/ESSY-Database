<?php

namespace Tests\Feature;

use App\Models\ReportData;
use App\Services\CrossLoadedDomainService;
use App\Services\ReportTemplateHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossLoadedDomainIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private CrossLoadedDomainService $crossLoadedService;
    private ReportTemplateHelper $templateHelper;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->crossLoadedService = app(CrossLoadedDomainService::class);
        $this->templateHelper = app(ReportTemplateHelper::class);
    }

    public function test_complete_cross_loaded_domain_workflow(): void
    {
        // Create a test report with cross-loaded concerns
        $report = $this->createTestReport();
        
        // Get concern domains
        $concernDomains = $report->getConcernDomains();
        $this->assertContains('Academic Skills', $concernDomains);
        $this->assertContains('Physical Health', $concernDomains);
        
        // Get fields requiring dagger
        $daggerFields = $this->crossLoadedService->getFieldsRequiringDagger($concernDomains);
        $this->assertArrayHasKey('A_P_S_ARTICULATE_CL1', $daggerFields);
        $this->assertArrayHasKey('A_P_S_ARTICULATE_CL2', $daggerFields);
        
        // Process domain items
        $academicIndicators = $this->templateHelper->getDomainIndicators()['Academic Skills'];
        $result = $this->templateHelper->processItemsForDomain('Academic Skills', $academicIndicators, $report);
        
        $this->assertFalse($result->hasErrors());
        $this->assertTrue($result->hasItems());
    }

    public function test_dagger_symbols_appear_correctly(): void
    {
        $report = $this->createTestReport();
        $concernDomains = $report->getConcernDomains();
        $daggerFields = $this->crossLoadedService->getFieldsRequiringDagger($concernDomains);
        
        // Test formatting with dagger
        $itemText = 'Student articulates clearly';
        $formattedText = $this->templateHelper->formatItemWithDagger($itemText, 'A_P_S_ARTICULATE_CL1', $daggerFields);
        
        $this->assertStringContainsString('†', $formattedText);
    }

    public function test_no_dagger_for_single_domain_concern(): void
    {
        // Create report with only one domain as concern
        $report = new ReportData();
        $report->fill([
            'A_DOMAIN' => 'an area of some concern',
            'B_DOMAIN' => 'an area of substantial strength',
            'S_DOMAIN' => 'neither an area of concern or strength',
            'P_DOMAIN' => 'an area of some strength',
            'O_DOMAIN' => 'an area of substantial strength',
            'ATT_DOMAIN' => 'neither an area of concern or strength',
            'A_P_S_ARTICULATE_CL1' => 'Sometimes',
        ]);
        
        $concernDomains = $report->getConcernDomains();
        $this->assertEquals(['Academic Skills'], $concernDomains);
        
        $daggerFields = $this->crossLoadedService->getFieldsRequiringDagger($concernDomains);
        $this->assertEmpty($daggerFields); // No cross-loaded items should have daggers
    }

    public function test_error_handling_in_integration(): void
    {
        // Create report with invalid data
        $report = new ReportData();
        $report->fill([
            'A_DOMAIN' => null, // Invalid domain rating
            'A_P_S_ARTICULATE_CL1' => '', // Empty field value
        ]);
        
        $concernDomains = $report->getConcernDomains();
        $this->assertEmpty($concernDomains); // Should handle gracefully
        
        $daggerFields = $this->crossLoadedService->getFieldsRequiringDagger($concernDomains);
        $this->assertIsArray($daggerFields);
    }

    public function test_configuration_validation(): void
    {
        $validationResult = $this->crossLoadedService->validateCrossLoadedConfiguration();
        
        $this->assertTrue($validationResult->isValid);
        $this->assertEmpty($validationResult->errors);
    }

    public function test_field_validation_against_model(): void
    {
        $modelFields = (new ReportData())->getFillable();
        $validationResult = $this->crossLoadedService->validateDatabaseFields($modelFields);
        
        $this->assertTrue($validationResult->isValid);
        $this->assertEmpty($validationResult->errors);
    }

    public function test_multiple_cross_loaded_groups(): void
    {
        $report = $this->createTestReportWithMultipleCrossLoaded();
        
        $concernDomains = $report->getConcernDomains();
        $daggerFields = $this->crossLoadedService->getFieldsRequiringDagger($concernDomains);
        
        // Should have multiple cross-loaded fields marked for dagger
        $expectedDaggerFields = [
            'A_P_S_ARTICULATE_CL1', 'A_P_S_ARTICULATE_CL2', // Articulate clearly
            'A_S_ADULTCOMM_CL1', 'A_S_ADULTCOMM_CL2',   // Communicate with adults
            'A_S_CONFIDENT_CL1', 'A_S_CONFIDENT_CL2',   // Confidence
        ];
        
        foreach ($expectedDaggerFields as $field) {
            $this->assertArrayHasKey($field, $daggerFields, "Field {$field} should have dagger");
        }
    }

    public function test_hygiene_field_spelling_variation_handling(): void
    {
        $report = new ReportData();
        $report->fill([
            'P_DOMAIN' => 'an area of some concern',
            'O_DOMAIN' => 'an area of substantial concern',
            'O_P_HYGEINE_CL1' => 'Sometimes', // Corrected spelling
            'O_P_HYGIENE_CL2' => 'Occasionally', // Correct spelling
        ]);
        
        $concernDomains = $report->getConcernDomains();
        $daggerFields = $this->crossLoadedService->getFieldsRequiringDagger($concernDomains);
        
        // Both hygiene fields should be handled
        $this->assertArrayHasKey('O_P_HYGEINE_CL1', $daggerFields);
        $this->assertArrayHasKey('O_P_HYGIENE_CL2', $daggerFields);
    }

    private function createTestReport(): ReportData
    {
        $report = new ReportData();
        $report->fill([
            'FN_STUDENT' => 'John',
            'LN_STUDENT' => 'Doe',
            'FN_TEACHER' => 'Jane',
            'LN_TEACHER' => 'Smith',
            'SCHOOL' => 'Test Elementary',
            'A_DOMAIN' => 'an area of some concern',
            'B_DOMAIN' => 'an area of substantial strength',
            'S_DOMAIN' => 'neither an area of concern or strength',
            'P_DOMAIN' => 'an area of some concern', // This makes articulate cross-loaded
            'O_DOMAIN' => 'an area of substantial strength',
            'ATT_DOMAIN' => 'neither an area of concern or strength',
            'A_P_S_ARTICULATE_CL1' => 'Sometimes',
            'A_P_S_ARTICULATE_CL2' => 'Occasionally',
            'A_READ' => 'Frequently',
            'A_WRITE' => 'Almost always',
        ]);
        
        return $report;
    }

    private function createTestReportWithMultipleCrossLoaded(): ReportData
    {
        $report = new ReportData();
        $report->fill([
            'A_DOMAIN' => 'an area of substantial concern',
            'S_DOMAIN' => 'an area of some concern',
            'P_DOMAIN' => 'an area of some concern',
            'B_DOMAIN' => 'neither an area of concern or strength',
            'O_DOMAIN' => 'an area of substantial strength',
            'ATT_DOMAIN' => 'neither an area of concern or strength',
            // Cross-loaded between Academic and Physical
            'A_P_S_ARTICULATE_CL1' => 'Sometimes',
            'A_P_S_ARTICULATE_CL2' => 'Occasionally',
            // Cross-loaded between Academic and Social/Emotional
            'A_S_ADULTCOMM_CL1' => 'Frequently',
            'A_S_ADULTCOMM_CL2' => 'Almost always',
            // Cross-loaded between Academic and Social/Emotional
            'A_S_CONFIDENT_CL1' => 'Sometimes',
            'A_S_CONFIDENT_CL2' => 'Occasionally',
        ]);
        
        return $report;
    }
}