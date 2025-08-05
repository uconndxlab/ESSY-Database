<?php

namespace Tests\Unit;

use Tests\TestCase;

class EssyConfigurationTest extends TestCase
{
    public function test_decision_rules_configuration_is_accessible()
    {
        // Test that configuration values can be accessed
        $useDecisionRules = config('essy.use_decision_rules');

        $this->assertIsBool($useDecisionRules);
    }

    public function test_decision_rules_configuration_defaults()
    {
        // Test default values when environment variables are not set
        $this->assertTrue(config('essy.use_decision_rules'));
    }

    public function test_decision_rules_configuration_can_be_disabled()
    {
        // Test that configuration can be overridden
        config(['essy.use_decision_rules' => false]);

        $this->assertFalse(config('essy.use_decision_rules'));
    }
}