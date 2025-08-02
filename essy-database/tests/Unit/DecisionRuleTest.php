<?php

namespace Tests\Unit;

use App\Models\DecisionRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DecisionRuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        DecisionRule::create([
            'item_code' => 'TEST_001',
            'frequency' => 'Almost Always',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student almost always demonstrates strong academic skills.'
        ]);

        DecisionRule::create([
            'item_code' => 'TEST_001',
            'frequency' => 'Sometimes',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student sometimes demonstrates academic skills.'
        ]);

        DecisionRule::create([
            'item_code' => 'TEST_002',
            'frequency' => 'Frequently',
            'domain' => 'Behavior',
            'decision_text' => 'The student frequently exhibits appropriate behavior.'
        ]);
    }

    public function test_fillable_fields_are_correctly_defined()
    {
        $rule = new DecisionRule();
        $expectedFillable = ['item_code', 'frequency', 'domain', 'decision_text'];
        
        $this->assertEquals($expectedFillable, $rule->getFillable());
    }

    public function test_get_decision_text_returns_correct_text()
    {
        $decisionText = DecisionRule::getDecisionText('TEST_001', 'Almost Always');
        
        $this->assertEquals('The student almost always demonstrates strong academic skills.', $decisionText);
    }

    public function test_get_decision_text_returns_null_for_non_existent_combination()
    {
        $decisionText = DecisionRule::getDecisionText('NON_EXISTENT', 'Almost Always');
        
        $this->assertNull($decisionText);
    }

    public function test_get_decision_text_returns_null_for_non_existent_frequency()
    {
        $decisionText = DecisionRule::getDecisionText('TEST_001', 'Never');
        
        $this->assertNull($decisionText);
    }

    public function test_get_by_item_and_frequency_returns_correct_model()
    {
        $rule = DecisionRule::getByItemAndFrequency('TEST_001', 'Sometimes');
        
        $this->assertInstanceOf(DecisionRule::class, $rule);
        $this->assertEquals('TEST_001', $rule->item_code);
        $this->assertEquals('Sometimes', $rule->frequency);
        $this->assertEquals('The student sometimes demonstrates academic skills.', $rule->decision_text);
    }

    public function test_get_by_item_and_frequency_returns_null_for_non_existent()
    {
        $rule = DecisionRule::getByItemAndFrequency('NON_EXISTENT', 'Almost Always');
        
        $this->assertNull($rule);
    }

    public function test_by_item_code_scope_filters_correctly()
    {
        $rules = DecisionRule::byItemCode('TEST_001')->get();
        
        $this->assertCount(2, $rules);
        $this->assertTrue($rules->every(fn($rule) => $rule->item_code === 'TEST_001'));
    }

    public function test_by_frequency_scope_filters_correctly()
    {
        $rules = DecisionRule::byFrequency('Almost Always')->get();
        
        $this->assertCount(1, $rules);
        $this->assertEquals('Almost Always', $rules->first()->frequency);
    }

    public function test_by_domain_scope_filters_correctly()
    {
        $rules = DecisionRule::byDomain('Academic Skills')->get();
        
        $this->assertCount(2, $rules);
        $this->assertTrue($rules->every(fn($rule) => $rule->domain === 'Academic Skills'));
    }

    public function test_get_available_frequencies_returns_distinct_frequencies()
    {
        $frequencies = DecisionRule::getAvailableFrequencies();
        
        $this->assertCount(3, $frequencies);
        $this->assertContains('Almost Always', $frequencies);
        $this->assertContains('Sometimes', $frequencies);
        $this->assertContains('Frequently', $frequencies);
    }

    public function test_get_available_domains_returns_distinct_domains()
    {
        $domains = DecisionRule::getAvailableDomains();
        
        $this->assertCount(2, $domains);
        $this->assertContains('Academic Skills', $domains);
        $this->assertContains('Behavior', $domains);
    }

    public function test_get_item_codes_by_domain_returns_correct_codes()
    {
        $itemCodes = DecisionRule::getItemCodesByDomain('Academic Skills');
        
        $this->assertCount(1, $itemCodes);
        $this->assertContains('TEST_001', $itemCodes);
    }

    public function test_get_item_codes_by_domain_returns_empty_for_non_existent_domain()
    {
        $itemCodes = DecisionRule::getItemCodesByDomain('Non Existent Domain');
        
        $this->assertCount(0, $itemCodes);
    }

    public function test_model_can_be_created_with_all_required_fields()
    {
        $rule = DecisionRule::create([
            'item_code' => 'NEW_TEST',
            'frequency' => 'Occasionally',
            'domain' => 'Social Skills',
            'decision_text' => 'The student occasionally demonstrates social skills.'
        ]);

        $this->assertInstanceOf(DecisionRule::class, $rule);
        $this->assertEquals('NEW_TEST', $rule->item_code);
        $this->assertEquals('Occasionally', $rule->frequency);
        $this->assertEquals('Social Skills', $rule->domain);
        $this->assertEquals('The student occasionally demonstrates social skills.', $rule->decision_text);
    }

    public function test_unique_constraint_on_item_code_and_frequency()
    {
        // This should work fine - different frequency
        DecisionRule::create([
            'item_code' => 'TEST_001',
            'frequency' => 'Occasionally',
            'domain' => 'Academic Skills',
            'decision_text' => 'The student occasionally demonstrates academic skills.'
        ]);

        // This should fail due to unique constraint
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        DecisionRule::create([
            'item_code' => 'TEST_001',
            'frequency' => 'Almost Always', // Same as existing record
            'domain' => 'Academic Skills',
            'decision_text' => 'Duplicate entry should fail.'
        ]);
    }

    public function test_timestamps_are_properly_cast()
    {
        $rule = DecisionRule::first();
        
        $this->assertInstanceOf(\Carbon\Carbon::class, $rule->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $rule->updated_at);
    }

    public function test_model_uses_correct_table_name()
    {
        $rule = new DecisionRule();
        
        $this->assertEquals('decision_rules', $rule->getTable());
    }

    public function test_scopes_can_be_chained()
    {
        $rules = DecisionRule::byItemCode('TEST_001')
                            ->byFrequency('Sometimes')
                            ->byDomain('Academic Skills')
                            ->get();
        
        $this->assertCount(1, $rules);
        $rule = $rules->first();
        $this->assertEquals('TEST_001', $rule->item_code);
        $this->assertEquals('Sometimes', $rule->frequency);
        $this->assertEquals('Academic Skills', $rule->domain);
    }
}