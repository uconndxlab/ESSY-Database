<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\CrossLoadedDomainService;

class CrossLoadedDomainCategorizationTest extends TestCase
{
    private CrossLoadedDomainService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CrossLoadedDomainService();
    }

    /** @test */
    public function test_rrbgg_pattern_fields()
    {
        $rrbggFields = [
            'A_B_IMPULSE_CL1', 'A_B_IMPULSE_CL2', 'B_CLINGY',
            'B_O_FAMSTRESS_CL1', 'B_O_FAMSTRESS_CL2',
            'B_O_NBHDSTRESS_CL1', 'B_O_NBHDSTRESS_CL2',
            'S_P_ACHES_CL1', 'S_P_ACHES_CL2', 'S_NERVOUS', 'S_SAD'
        ];

        foreach ($rrbggFields as $field) {
            // RRBGG pattern: red, red, blue, green, green
            $this->assertEquals('concerns', $this->service->categorizeFieldValue($field, 'almost always'));
            $this->assertEquals('concerns', $this->service->categorizeFieldValue($field, 'frequently'));
            $this->assertEquals('monitor', $this->service->categorizeFieldValue($field, 'sometimes'));
            $this->assertEquals('strengths', $this->service->categorizeFieldValue($field, 'occasionally'));
            $this->assertEquals('strengths', $this->service->categorizeFieldValue($field, 'almost never'));
        }
    }

    /** @test */
    public function test_rrbbg_pattern_fields()
    {
        $rrbbgFields = [
            'B_SNEAK', 'B_VERBAGGRESS', 'B_DESTRUCT',
            'B_O_HOUSING_CL1', 'B_O_HOUSING_CL2',
            'O_P_HUNGER_CL1', 'O_P_HUNGER_CL2'
        ];

        foreach ($rrbbgFields as $field) {
            // RRBBG pattern: red, red, blue, blue, green
            $this->assertEquals('concerns', $this->service->categorizeFieldValue($field, 'almost always'));
            $this->assertEquals('concerns', $this->service->categorizeFieldValue($field, 'frequently'));
            $this->assertEquals('monitor', $this->service->categorizeFieldValue($field, 'sometimes'));
            $this->assertEquals('monitor', $this->service->categorizeFieldValue($field, 'occasionally'));
            $this->assertEquals('strengths', $this->service->categorizeFieldValue($field, 'almost never'));
        }
    }

    /** @test */
    public function test_rrrrg_pattern_fields()
    {
        $rrrrGFields = ['B_PHYSAGGRESS', 'B_BULLY', 'B_PUNITIVE'];

        foreach ($rrrrGFields as $field) {
            // RRRRG pattern: red, red, red, red, green
            $this->assertEquals('concerns', $this->service->categorizeFieldValue($field, 'almost always'));
            $this->assertEquals('concerns', $this->service->categorizeFieldValue($field, 'frequently'));
            $this->assertEquals('concerns', $this->service->categorizeFieldValue($field, 'sometimes'));
            $this->assertEquals('concerns', $this->service->categorizeFieldValue($field, 'occasionally'));
            $this->assertEquals('strengths', $this->service->categorizeFieldValue($field, 'almost never'));
        }
    }

    /** @test */
    public function test_gbrrr_pattern_fields()
    {
        $gbrrrFields = ['P_SIGHT', 'P_HEAR'];

        foreach ($gbrrrFields as $field) {
            // GBRRR pattern: green, blue, red, red, red
            $this->assertEquals('strengths', $this->service->categorizeFieldValue($field, 'almost always'));
            $this->assertEquals('monitor', $this->service->categorizeFieldValue($field, 'frequently'));
            $this->assertEquals('concerns', $this->service->categorizeFieldValue($field, 'sometimes'));
            $this->assertEquals('concerns', $this->service->categorizeFieldValue($field, 'occasionally'));
            $this->assertEquals('concerns', $this->service->categorizeFieldValue($field, 'almost never'));
        }
    }

    /** @test */
    public function test_ggbbr_pattern_fields()
    {
        $ggbbrFields = ['O_RESOURCE'];

        foreach ($ggbbrFields as $field) {
            // GGBBR pattern: green, green, blue, blue, red
            $this->assertEquals('strengths', $this->service->categorizeFieldValue($field, 'almost always'));
            $this->assertEquals('strengths', $this->service->categorizeFieldValue($field, 'frequently'));
            $this->assertEquals('monitor', $this->service->categorizeFieldValue($field, 'sometimes'));
            $this->assertEquals('monitor', $this->service->categorizeFieldValue($field, 'occasionally'));
            $this->assertEquals('concerns', $this->service->categorizeFieldValue($field, 'almost never'));
        }
    }

    /** @test */
    public function test_ggbrr_default_pattern_fields()
    {
        // Test fields that should fall into default GGBRR pattern
        $defaultFields = [
            'A_READ', 'A_WRITE', 'A_MATH', 'S_CONTENT', 'S_PROSOCIAL',
            'O_RECIPROCAL', 'O_POSADULT', 'P_HEAR', 'P_ORAL',
            'O_P_hygiene_CL1', 'O_P_CLOTHES_CL1', 'S_FRIEND'
        ];

        foreach ($defaultFields as $field) {
            // GGBRR pattern: green, green, blue, red, red
            $this->assertEquals('strengths', $this->service->categorizeFieldValue($field, 'almost always'));
            $this->assertEquals('strengths', $this->service->categorizeFieldValue($field, 'frequently'));
            $this->assertEquals('monitor', $this->service->categorizeFieldValue($field, 'sometimes'));
            $this->assertEquals('concerns', $this->service->categorizeFieldValue($field, 'occasionally'));
            $this->assertEquals('concerns', $this->service->categorizeFieldValue($field, 'almost never'));
        }
    }

    /** @test */
    public function test_cross_loaded_fields_have_consistent_patterns()
    {
        // Test that cross-loaded fields use the same pattern across domains
        
        // Impulse items should both use RRBGG
        $this->assertEquals('strengths', $this->service->categorizeFieldValue('A_B_IMPULSE_CL1', 'occasionally'));
        $this->assertEquals('strengths', $this->service->categorizeFieldValue('A_B_IMPULSE_CL2', 'occasionally'));
        
        // Housing items should both use RRBBG
        $this->assertEquals('monitor', $this->service->categorizeFieldValue('B_O_HOUSING_CL1', 'occasionally'));
        $this->assertEquals('monitor', $this->service->categorizeFieldValue('B_O_HOUSING_CL2', 'occasionally'));
        
        // Hunger items should both use RRBBG
        $this->assertEquals('monitor', $this->service->categorizeFieldValue('O_P_HUNGER_CL1', 'sometimes'));
        $this->assertEquals('monitor', $this->service->categorizeFieldValue('O_P_HUNGER_CL2', 'sometimes'));
        
        // Hygiene items should both use GGBRR (default)
        $this->assertEquals('strengths', $this->service->categorizeFieldValue('O_P_hygiene_CL1', 'frequently'));
        $this->assertEquals('strengths', $this->service->categorizeFieldValue('O_P_hygiene_CL2', 'frequently'));
    }

    /** @test */
    public function test_invalid_frequency_values()
    {
        // Test that invalid frequency values default to concerns
        $this->assertEquals('concerns', $this->service->categorizeFieldValue('S_CONTENT', 'invalid'));
        $this->assertEquals('concerns', $this->service->categorizeFieldValue('S_CONTENT', ''));
        $this->assertEquals('concerns', $this->service->categorizeFieldValue('S_CONTENT', 'maybe'));
    }

    /** @test */
    public function test_case_insensitive_frequency_values()
    {
        // Test that frequency values are case insensitive
        $this->assertEquals('strengths', $this->service->categorizeFieldValue('S_CONTENT', 'ALMOST ALWAYS'));
        $this->assertEquals('strengths', $this->service->categorizeFieldValue('S_CONTENT', 'Frequently'));
        $this->assertEquals('monitor', $this->service->categorizeFieldValue('S_CONTENT', 'Sometimes'));
        $this->assertEquals('concerns', $this->service->categorizeFieldValue('S_CONTENT', 'OCCASIONALLY'));
    }

    /** @test */
    public function test_unknown_field_uses_default_pattern()
    {
        // Test that fields not explicitly categorized use GGBRR pattern
        $unknownField = 'UNKNOWN_FIELD_TEST';
        
        $this->assertEquals('strengths', $this->service->categorizeFieldValue($unknownField, 'almost always'));
        $this->assertEquals('strengths', $this->service->categorizeFieldValue($unknownField, 'frequently'));
        $this->assertEquals('monitor', $this->service->categorizeFieldValue($unknownField, 'sometimes'));
        $this->assertEquals('concerns', $this->service->categorizeFieldValue($unknownField, 'occasionally'));
        $this->assertEquals('concerns', $this->service->categorizeFieldValue($unknownField, 'almost never'));
    }

    /** @test */
    public function test_specific_pattern_edge_cases()
    {
        // Test specific edge cases mentioned in requirements
        
        // S_NERVOUS should be green for "occasionally" (RRBGG pattern)
        $this->assertEquals('strengths', $this->service->categorizeFieldValue('S_NERVOUS', 'occasionally'));
        
        // O_RESOURCE should be blue for "occasionally" (GGBBR pattern)
        $this->assertEquals('monitor', $this->service->categorizeFieldValue('O_RESOURCE', 'occasionally'));
        
        // B_PHYSAGGRESS should be red for everything except "almost never" (RRRRG pattern)
        $this->assertEquals('concerns', $this->service->categorizeFieldValue('B_PHYSAGGRESS', 'occasionally'));
        $this->assertEquals('strengths', $this->service->categorizeFieldValue('B_PHYSAGGRESS', 'almost never'));
        
        // P_SIGHT should be red for "sometimes" (GBRRR pattern)
        $this->assertEquals('concerns', $this->service->categorizeFieldValue('P_SIGHT', 'sometimes'));
    }
}