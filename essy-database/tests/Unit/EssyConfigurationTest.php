<?php

namespace Tests\Unit;

use Tests\TestCase;

class EssyConfigurationTest extends TestCase
{
    public function test_decision_rules_configuration_is_accessible()
    {
        // Test that configuration values can be accessed
        $useDecisionRules = config('essy.use_decision_rules');
        $fallbackEnabled = config('essy.decision_rules_fallback');

        $this->assertIsBool($useDecisionRules);
        $this->assertIsBool($fallbackEnabled);
    }

    public function test_decision_rules_configuration_defaults()
    {
        // Test default values when environment variables are not set
        $this->assertTrue(config('essy.use_decision_rules'));
        $this->assertTrue(config('essy.decision_rules_fallback'));
    }

    public function test_decision_rules_configuration_can_be_disabled()
    {
        // Test that configuration can be overridden
        config(['essy.use_decision_rules' => false]);
        config(['essy.decision_rules_fallback' => false]);

        $this->assertFalse(config('essy.use_decision_rules'));
        $this->assertFalse(config('essy.decision_rules_fallback'));
    }
}