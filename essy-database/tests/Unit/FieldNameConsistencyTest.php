<?php

namespace Tests\Unit;

use App\Models\ReportData;
use App\Services\CrossLoadedDomainService;
use Tests\TestCase;

class FieldNameConsistencyTest extends TestCase
{
    private CrossLoadedDomainService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CrossLoadedDomainService();
    }

    public function test_all_cross_loaded_fields_exist_in_model()
    {
        // Arrange
        $modelFields = (new ReportData())->getFillable();
        $crossLoadedGroups = $this->service->getCrossLoadedItemGroups();
        $fieldToDomainMap = $this->service->getFieldToDomainMap();

        $missingFields = [];

        // Act & Assert - Check cross-loaded groups
        foreach ($crossLoadedGroups as $groupIndex => $group) {
            foreach ($group as $field) {
                if (!in_array($field, $modelFields)) {
                    $missingFields[] = "Cross-loaded field '{$field}' in group '{$groupIndex}' not found in ReportData model";
                }
            }
        }

        // Act & Assert - Check field-to-domain mapping
        foreach ($fieldToDomainMap as $field => $domain) {
            if (!in_array($field, $modelFields)) {
                $missingFields[] = "Mapped field '{$field}' not found in ReportData model";
            }
        }

        // Assert no missing fields
        $this->assertEmpty($missingFields, "Field name inconsistencies found:\n" . implode("\n", $missingFields));
    }

    public function test_no_misspelled_hygiene_fields()
    {
        // Arrange
        $crossLoadedGroups = $this->service->getCrossLoadedItemGroups();
        $fieldToDomainMap = $this->service->getFieldToDomainMap();
        $fieldMessages = $this->service->getFieldMessages();

        $misspelledFields = [];

        // Check for misspelled HYGEINE in cross-loaded groups
        foreach ($crossLoadedGroups as $groupIndex => $group) {
            foreach ($group as $field) {
                if (str_contains($field, 'HYGEINE')) {
                    $misspelledFields[] = "Misspelled field '{$field}' found in cross-loaded group '{$groupIndex}'";
                }
            }
        }

        // Check for misspelled HYGEINE in field-to-domain mapping
        foreach ($fieldToDomainMap as $field => $domain) {
            if (str_contains($field, 'HYGEINE')) {
                $misspelledFields[] = "Misspelled field '{$field}' found in field-to-domain mapping";
            }
        }

        // Check for misspelled HYGEINE in field messages
        foreach ($fieldMessages as $field => $message) {
            if (str_contains($field, 'HYGEINE')) {
                $misspelledFields[] = "Misspelled field '{$field}' found in field messages";
            }
        }

        // Assert no misspelled fields
        $this->assertEmpty($misspelledFields, "Misspelled HYGEINE fields found:\n" . implode("\n", $misspelledFields));
    }

    public function test_hygiene_fields_use_correct_spelling()
    {
        // Arrange
        $fieldToDomainMap = $this->service->getFieldToDomainMap();
        $fieldMessages = $this->service->getFieldMessages();

        // Act & Assert - Verify correct spelling exists
        $this->assertArrayHasKey('O_P_HYGIENE_CL1', $fieldToDomainMap, 'O_P_HYGIENE_CL1 should exist in field-to-domain mapping');
        $this->assertArrayHasKey('O_P_HYGIENE_CL2', $fieldToDomainMap, 'O_P_HYGIENE_CL2 should exist in field-to-domain mapping');
        
        $this->assertArrayHasKey('O_P_HYGIENE_CL1', $fieldMessages, 'O_P_HYGIENE_CL1 should exist in field messages');
        
        // Verify correct domain mappings
        $this->assertEquals('Physical Health', $fieldToDomainMap['O_P_HYGIENE_CL1']);
        $this->assertEquals('Supports Outside of School', $fieldToDomainMap['O_P_HYGIENE_CL2']);
    }

    public function test_cross_loaded_hygiene_group_consistency()
    {
        // Arrange
        $crossLoadedGroups = $this->service->getCrossLoadedItemGroups();
        
        // Find the hygiene group
        $hygieneGroup = null;
        foreach ($crossLoadedGroups as $group) {
            if (in_array('O_P_HYGIENE_CL1', $group) && in_array('O_P_HYGIENE_CL2', $group)) {
                $hygieneGroup = $group;
                break;
            }
        }

        // Assert hygiene group exists and has correct fields
        $this->assertNotNull($hygieneGroup, 'Hygiene cross-loaded group should exist');
        $this->assertContains('O_P_HYGIENE_CL1', $hygieneGroup, 'Hygiene group should contain O_P_HYGIENE_CL1');
        $this->assertContains('O_P_HYGIENE_CL2', $hygieneGroup, 'Hygiene group should contain O_P_HYGIENE_CL2');
        $this->assertCount(2, $hygieneGroup, 'Hygiene group should contain exactly 2 fields');
    }
}