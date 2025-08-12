<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\CrossLoadedDomainService;

class DefaultPatternVerificationTest extends TestCase
{
    private CrossLoadedDomainService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CrossLoadedDomainService();
    }

    public function test_all_fields_from_domain_mapping_are_categorized()
    {
        // Get all fields from the domain mapping
        $allFields = array_keys($this->service->getFieldToDomainMap());
        
        // Define explicitly categorized fields
        $explicitlyCategorized = [
            // RRBGG
            'A_B_IMPULSE_CL1', 'A_B_IMPULSE_CL2', 'B_CLINGY',
            'B_O_FAMSTRESS_CL1', 'B_O_FAMSTRESS_CL2',
            'B_O_NBHDSTRESS_CL1', 'B_O_NBHDSTRESS_CL2',
            'S_P_ACHES_CL1', 'S_P_ACHES_CL2', 'S_NERVOUS', 'S_SAD',
            // RRBBG
            'B_SNEAK', 'B_VERBAGGRESS', 'B_DESTRUCT',
            'B_O_HOUSING_CL1', 'B_O_HOUSING_CL2',
            'O_P_HUNGER_CL1', 'O_P_HUNGER_CL2',
            // RRRRG
            'B_PHYSAGGRESS', 'B_BULLY', 'B_PUNITIVE',
            // GBRRR
            'P_SIGHT',
            // GGBBR
            'O_RESOURCE'
        ];
        
        // Fields that should use default GGBRR pattern
        $defaultPatternFields = array_diff($allFields, $explicitlyCategorized);
        
        echo "\n=== FIELDS USING DEFAULT GGBRR PATTERN ===\n";
        foreach ($defaultPatternFields as $field) {
            echo "- {$field}\n";
        }
        
        // Test that all default pattern fields follow GGBRR
        foreach ($defaultPatternFields as $field) {
            $this->assertEquals('strengths', $this->service->categorizeFieldValue($field, 'almost always'), 
                "Field {$field} should be 'strengths' for 'almost always' (GGBRR pattern)");
            $this->assertEquals('strengths', $this->service->categorizeFieldValue($field, 'frequently'), 
                "Field {$field} should be 'strengths' for 'frequently' (GGBRR pattern)");
            $this->assertEquals('monitor', $this->service->categorizeFieldValue($field, 'sometimes'), 
                "Field {$field} should be 'monitor' for 'sometimes' (GGBRR pattern)");
            $this->assertEquals('concerns', $this->service->categorizeFieldValue($field, 'occasionally'), 
                "Field {$field} should be 'concerns' for 'occasionally' (GGBRR pattern)");
            $this->assertEquals('concerns', $this->service->categorizeFieldValue($field, 'almost never'), 
                "Field {$field} should be 'concerns' for 'almost never' (GGBRR pattern)");
        }
        
        // Verify we have the expected number of default fields
        $expectedDefaultCount = count($allFields) - count($explicitlyCategorized);
        $this->assertEquals($expectedDefaultCount, count($defaultPatternFields));
        
        echo "\n=== SUMMARY ===\n";
        echo "Total fields: " . count($allFields) . "\n";
        echo "Explicitly categorized: " . count($explicitlyCategorized) . "\n";
        echo "Using default GGBRR pattern: " . count($defaultPatternFields) . "\n";
    }

    public function test_pattern_coverage_is_complete()
    {
        // Verify that every field in the domain mapping can be categorized
        $allFields = array_keys($this->service->getFieldToDomainMap());
        
        foreach ($allFields as $field) {
            $result = $this->service->categorizeFieldValue($field, 'sometimes');
            $this->assertContains($result, ['strengths', 'monitor', 'concerns'], 
                "Field {$field} should return a valid category");
        }
        
        $this->assertTrue(count($allFields) > 0, "Should have fields to test");
    }
}